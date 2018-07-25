<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!isset($_REQUEST["action"])) {
	$_REQUEST["action"]="";
}
if(!isset($_REQUEST['gridid'])) {
	trigger_error("Grid Not Found");
}

include_once __DIR__."/api.php";

switch($_REQUEST["action"]) {
	case "updateFieldValue":
		if(!isset($_POST['dataField']) || !isset($_POST['dataVal']) || !isset($_POST['dataHash'])) {
			trigger_error("Sorry, required fields not found.");
		}
		$reportKey=$_REQUEST['gridid'];
		if(!isset($_SESSION['REPORT'][$reportKey])) {
			trigger_error("Sorry, grid report key not found.");
		}

		$reportConfig=$_SESSION['REPORT'][$reportKey];

		if(isset($reportConfig['updatableColumns'])) {
			if(in_array($_POST['dataField'],$reportConfig['updatableColumns'])) {
				if(strtolower($reportConfig['source']['type'])=="sql") {
    
					if(isset($reportConfig['source']['table'])) {
						$colDefn=explode(",",$_POST['dataField']);
            $tables=explode(",",$reportConfig['source']['table']);
            
            if(count($colDefn)>1) {
              $srcTable=$colDefn[0];
            } else {
              $srcTable=$tables[0];
            }
            
						$colID="md5({$srcTable}.id)";
            
            $sql=_db()->_updateQ($srcTable,[$_POST['dataField']=>$_POST['dataVal']],[$colID=>$_POST['dataHash']]);
            $sql=$sql->_RUN();

            if($sql) {
              executeReportHook("fieldupdate",$reportConfig);
              printServiceMsg(["msg"=>"done","hash"=>$_POST['dataHash']]);
            } else {
              printServiceMsg(["msg"=>"Error updating the field","hash"=>$_POST['dataHash'],"error"=>_db()->get_error()]);
            }
					} else {
						printServiceMsg(["msg"=>"Source type not defined correctly","hash"=>$_POST['dataHash']]);
					}
				} else {
					printServiceMsg(["msg"=>"Source type not supported","hash"=>$_POST['dataHash']]);
				}
			} else {
				printServiceMsg(["msg"=>"Field Update not allowed","hash"=>$_POST['dataHash']]);
			}
		} else {
			printServiceMsg(["msg"=>"Update not allowed","hash"=>$_POST['dataHash']]);
		}
		break;
	case "enumerateColumn":
		if(!isset($_REQUEST['colKey'])) {
			trigger_error("Sorry, column not found.");
		}
		$reportKey=$_REQUEST['gridid'];
		if(!isset($_SESSION['REPORT'][$reportKey])) {
			trigger_error("Sorry, grid report key not found.");
		}

		$reportConfig=$_SESSION['REPORT'][$reportKey];
// 		printArray($reportConfig);
		try {
			if(isset($reportConfig['kanban']) && isset($reportConfig['kanban']['colkeys']) && isset($reportConfig['kanban']['colkeys'][$_REQUEST['colKey']])) {
				$src=$reportConfig['kanban']['colkeys'][$_REQUEST['colKey']];

				if(!isset($src['where'])) $src['where']=[];

				if(is_array($src['where'])) {
					foreach($src['where'] as $k=>$v) {
						$src['where'][$k]=_replace($v);
					}
				}
				$data=_db()->_selectQ($src['table'],$src['columns'],$src['where']);

				if(isset($src['orderby'])) {
					$data=$data->_orderby($src['orderby']);
				} elseif(isset($src['sortby'])) {
					$data=$data->_orderby($src['sortby']);
				}

				if(isset($src['groupby'])) {
					$data=$data->_groupby($src['groupby']);
				} else {
					if(!is_array($src['columns'])) {
						$gCols=explode(",",$src['columns']);
					} else {
						$gCols=$src['columns'];
					}
					$gCols[0]=explode(" ",$gCols[0]);
          $src['groupby']=$gCols[0][0];
					$data=$data->_groupby($gCols[0][0]);
				}
        //exit($data->_SQL());
				$data=$data->_limit(20,0)->_GET();

				if($data) {
          $fData=[
//             ["title"=>"","value"=>""]
          ];
          if(!isset($src['type'])) $src['type']="";
          switch(strtolower($src['type'])) {
            case "csv":case "list":
              foreach($data as $row) {
                if($row['value']==null || strlen($row['value'])<=0) {
                  continue;
                }
                $vArr=explode(",",$row['value']);
                if(count($vArr)>1) {
                  foreach($vArr as $x1=>$y1) {
                    if($y1==null || strlen($y1)<=0) continue;
                    $fData[$y1]=["title"=>_ling($y1),"value"=>$y1];
                  }
                } else {
                  $fData[$row['value']]=$row;
                }
              }
              $fData=array_values($fData);
              break;
            default:
              foreach($data as $row) {
                if($row['title']==null || strlen($row['title'])<=0) {
                  continue;
                }
                $row['title']=_ling($row['title']);
                $fData[]=$row;
              }
          }
          
					printServiceMsg($fData);
				} else {
					printServiceMsg([]);
				}
			} else {
				printServiceMsg([]);
			}
		} catch(Exception $e) {
			printServiceMsg([]);
		}
	break;
	case "fetchGrid":
		$reportKey=$_REQUEST['gridid'];
		if(!isset($_SESSION['REPORT'][$reportKey])) {
			trigger_error("Sorry, grid report key not found.");
		}

		$_SESSION['REPORT'][$reportKey]['LASTVIEW-REQUEST']=$_POST;

		$reportConfig=$_SESSION['REPORT'][$reportKey];

		if(isset($reportConfig['onajax'])) {
			if(isset($reportConfig['onajax']['modules'])) {
				loadModules($reportConfig['onajax']['modules']);
			}
			if(isset($reportConfig['onajax']['api'])) {
				foreach ($reportConfig['onajax']['api'] as $apiModule) {
					loadModuleLib($apiModule,'api');
				}
			}
			if(isset($reportConfig['onajax']['helpers'])) {
				loadHelpers($reportConfig['onajax']['helpers']);
			}
			if(isset($reportConfig['onajax']['method'])) {
				if(!is_array($reportConfig['onajax']['method'])) $reportConfig['onajax']['method']=explode(",",$reportConfig['onajax']['method']);
				foreach($reportConfig['onajax']['method'] as $m) call_user_func($m,$reportConfig);
			}
			if(isset($reportConfig['onajax']['file'])) {
				if(!is_array($reportConfig['onajax']['file'])) $reportConfig['onajax']['file']=explode(",",$reportConfig['onajax']['file']);
				foreach($reportConfig['onajax']['file'] as $m) {
					if(file_exists($m)) include $m;
					elseif(file_exists(APPROOT.$m)) include APPROOT.$m;
				}
			}
		}

		$data=getGridData($reportKey,$reportConfig);
		$maxRecords=getGridDataMax($reportKey,$reportConfig);
		//printArray($data);exit();
		switch ($_REQUEST['format']) {
			case 'html':
				if(is_array($data) && count($data)>0) {
					if($reportConfig['showExtraColumn']) {
						if(strpos($reportConfig['showExtraColumn'],"<")===0) {
							$firstColumn="<td class='tableColumn rowSelector'>{$reportConfig['showExtraColumn']}</td>";
						} else {
							$firstColumn="<td class='tableColumn rowSelector'><input name='rowSelector' type='{$reportConfig['showExtraColumn']}' /></td>";
						}
					} else {
						$firstColumn="";
					}

					$dataKey=array_keys($reportConfig['datagrid'])[0];
					$otherKey=array_keys($data[0])[0];
					//printArray($data);
					foreach ($data as $record) {
						if(isset($record['id'])) {
							$hashid=($record['id']);//md5
						} elseif(isset($record[$dataKey])) {
							$hashid=md5($record[$dataKey]);
						} else {
							$hashid=md5($record[$otherKey]);
						}

						if($reportConfig['secure']) {
							echo "<tr class='tableRow' data-hash='".md5($hashid)."'>";
						} else {
							echo "<tr class='tableRow' data-hash='{$hashid}'>";
						}

						echo $firstColumn;
						foreach ($reportConfig['datagrid'] as $key => $column) {
							$keyx=explode(".",$key);
							$keyx=end($keyx);
							
							if(isset($column['policy']) && strlen($column['policy'])>0) {
								$allow=checkUserPolicy($column['policy']);
								if(!$allow) continue;
							}

							if(!isset($column['formatter'])) {
								if(isset($column['type'])) {
									$column['formatter']=$column['type'];
								} else {
									$column['formatter']="text";
								}
							}
							if(!isset($column['hidden'])) $column['hidden']=false;

							if(isset($record[$key])) {
								echo formatReportColumn($key,$record[$key],$column['formatter'],$column['hidden'],$record);
							} elseif(isset($record[$keyx])) {
								echo formatReportColumn($key,$record[$keyx],$column['formatter'],$column['hidden'],$record);
							} else {
								echo formatReportColumn($key,"",$column['formatter'],$column['hidden'],$record);
							}
						}
						if(isset($reportConfig['buttons']) && is_array($reportConfig['buttons']) && count($reportConfig['buttons'])>0) {
							echo "<td class='actionCol hidden-print'>";
							foreach ($reportConfig['buttons'] as $cmd => $button) {
								$button['cmd']=$cmd;
								echo createReportRecordAction($button, $record);
							}
							echo "</td>";
						}
						echo "</tr>";
					}

					$limit=$_REQUEST['limit'];
					$index=$_REQUEST['page']*$limit;
					$last=$index+$limit;
					echo "<tr class='hidden gridDataInfo'><td class='limit'>{$limit}</td><td class='index'>{$index}</td><td class='last'>{$last}</td><td class='max'>{$maxRecords}</td></tr>";
				} else {
					if($_REQUEST['page']>0) {

					} else {
						echo "<tr class='norecords'><td colspan=1000000 align=center>No Record Found</td></tr>";
					}
				}

				break;

			default:
				$limit=$_REQUEST['limit'];
				$index=$_REQUEST['page']*$limit;
				$last=$index+$limit;
				
				$specialFormatter=["date","time","datetime","currency","content"];
				
				$finalGrid=[];
				foreach($reportConfig['datagrid'] as $col=>$config) {
					$col=explode(".",$col);
					$col=end($col);
					
					if(isset($config['formatter']) && in_array($config['formatter'],$specialFormatter)) {
						$finalGrid[$col]=$config;
					}
				}
				
				foreach($data as $a=>$record) {
					if(isset($record['hashid'])) {
						$hashid=$record['hashid'];
						$data[$a]['hashid']=$hashid;
					} elseif(isset($record['id'])) {
						$hashid=md5($record['id']);
						$data[$a]['hashid']=$hashid;
					}
					foreach($data[$a] as $col=>$value) {
						if(isset($finalGrid[$col])) {
							switch(strtolower($finalGrid[$col]['formatter'])) {
								case "date":
                  $value=current(explode(" ", $value));
									$data[$a][$col]=_pDate($value);
									break;
								case "time":
                  $value=explode(" ", $value);
				          $value=end($value);
									$data[$a][$col]=_time($value);
									break;
								case "datetime":
									$data[$a][$col]=_pDate($value);
									break;
								case "currency":
									$data[$a][$col]=number_format($value,2);
									break;
								case "content":
									if($value==null || strlen($value)<=0) {
										$data[$a][$col]=_ling("No Content");
									} else {
										$value=str_replace("\\r\\n","<br>",$value);
										$value=str_replace("\\'s","'s",$value);
										$data[$a][$col]=$value;
									}
									break;
							}
						}
					}
				}
				
				printServiceMsg(['RECORDS'=>$data,'INFO'=>["limit"=>$limit,"index"=>$index,"last"=>$last,"max"=>$maxRecords]]);
				break;
		}
		//printArray($data);
	break;
	case "export":
		if(!isset($_REQUEST['type'])) $_REQUEST['type']="pdf";

		$reportKey=$_REQUEST['gridid'];
		if(!isset($_SESSION['REPORT'][$reportKey])) {
			trigger_error("Sorry, grid report key not found.");
		}

		if(isset($_SESSION['REPORT'][$reportKey]['LASTVIEW-REQUEST']) && is_array($_SESSION['REPORT'][$reportKey]['LASTVIEW-REQUEST'])) {
			foreach($_SESSION['REPORT'][$reportKey]['LASTVIEW-REQUEST'] as $a=>$b) {
				if(!isset($_POST[$a])) {
					$_POST[$a]=$b;
				}
			}
		}

		$reportConfig=$_SESSION['REPORT'][$reportKey];

		$data=getGridData($reportKey,$reportConfig);

		$headers=[];
		foreach ($reportConfig['datagrid'] as $colID => $column) {
			if(isset($column['hidden']) && $column['hidden']) {

			} else {
				$headers[$colID]=_ling($column['label']);
			}
		}

		switch (strtolower($_REQUEST['type'])) {
			case 'pdf':
				$htmlData=getHTMLData($data,$headers);

				$lt=new LogiksTemplate(LogiksTemplate::getEngineForExtension(".tpl"));

				$tmpl=$reportConfig['export']['pdf'];

				if(!file_exists($tmpl)) {
					echo "<h1 align=center>Print Template For Report Not Found !</h1>";
					exit();
				}

				$html=$lt->getTemplateData($tmpl,['HTMLTABLE'=>$htmlData,'DATA'=>$data],null,true);

				loadVendor("mpdf");

				$mpdf=new mPDF('c');
				$mpdf->useAdobeCJK = true;

				//$password="bkm";
				//$mpdf->SetProtection(array('copy','print'), $password, $password);

				$mpdf->WriteHTML($html);
				$mpdf->Output();
			break;
		}
	break;
	default:
		trigger_error("Action Not Defined or Not Supported");
}

function getGridDataMax($reportKey,$reportConfig) {
	if(isset($_SESSION[$reportKey]['MAXRECORDS'])) return $_SESSION[$reportKey]['MAXRECORDS'];

	$dbKey=$reportConfig['dbkey'];
	if($dbKey==null) $dbKey="app";

	$source=$reportConfig['source'];
	$searchCols=$reportConfig['searchCols'];

	switch ($source['type']) {
		case 'sql':
			$source['cols']="count(*) as max";
			$sql=QueryBuilder::fromArray($source,_db($dbKey));
			$sql=processReportWhere($sql,$reportConfig);
			//exit($sql->_SQL()." ".$dbKey);

			$res=_dbQuery($sql,$dbKey);
			$data=_dbData($res,$dbKey);
			_dbFree($res,$dbKey);

			if(isset($data[0]) && isset($data[0]['max'])) $data=$data[0]['max'];
			else $data=0;
			//trigger_logikserror("Wrong SQL Statement");

			break;

		case 'php':
			$file=APPROOT.$source['file'];
			if(file_exists($file) && is_file($file)) {
				include_once($file);

				if(function_exists("get_max_record_count")) {
					$data=get_max_record_count();
				} else {
					if(isset($MAXRECORDS)) {
						$data=$MAXRECORDS;
					} else {
						$data="-1";
					}
				}
			} else {
				$data="-1";
			}
			break;

		case 'model':

		default:
			trigger_error("Report Source Not Supported");
			break;
	}
	$_SESSION[$reportKey]['MAXRECORDS']=$data;
	return $data;
}

function getGridData($reportKey,$reportConfig) {
	$dbKey=$reportConfig['dbkey'];
	if($dbKey==null) $dbKey="app";

	$source=$reportConfig['source'];
	$searchCols=$reportConfig['searchCols'];

	if(!isset($_REQUEST['page'])) $_REQUEST['page']=0;
	if(!isset($_REQUEST['limit'])) $_REQUEST['limit']=$source['limit'];

	$source['limit']=$_REQUEST['limit'];
	$source['index']=$_REQUEST['page']*$source['limit'];

	if(!isset($source['type'])) {
		trigger_error("Corrupt Report Configuration");
	}

	$data=[];
	switch ($source['type']) {
		case 'sql':
			$sql=QueryBuilder::fromArray($source,_db($dbKey));
			$sql=processReportWhere($sql,$reportConfig);

			if(isset($_POST['orderby']) && count($_POST['orderby'])>0) {
				$sql->_orderby(getColAlias($_POST['orderby'],$reportConfig));
			}
			if(isset($reportConfig['DEBUG']) && $reportConfig['DEBUG']==true) {
				exit($sql->_SQL());
			}
//  			exit($sql->_SQL());
			$res=_dbQuery($sql,$dbKey);
			if($res) {
				$data=_dbData($res,$dbKey);
				_dbFree($res,$dbKey);
			} else {
				//trigger_error($sql->_SQL());
				trigger_error(_db($dbKey)->get_error());
			}
			break;

		case 'php':
			$file=APPROOT.$source['file'];
			if(file_exists($file) && is_file($file)) {
				$data=include_once($file);

				if(isset($MAXRECORDS)) {
					$_SESSION[$reportKey]['MAXRECORDS']=$MAXRECORDS;
				}
			} else {
				trigger_error("Report Source File Not Found");
			}
			break;

		case 'model':

		default:
			trigger_error("Report Source Not Supported");
			break;
	}

	return $data;
}
function getHTMLData($data,$headers=[]) {
	$html=[];
	$html[]="<table class='dataHTMLTable' cellspacing=0>";
	if($headers!=null && is_array($headers) && count($headers)>0) {
		$html[]="<thead><tr>";
		foreach ($headers as $key => $value) {
			$html[]="<th key='{$key}'>{$value}</th>";
		}
		$html[]="</tr></thead>";
	}

	$html[]="<tbody>";
	foreach ($data as $key => $row) {
		$html[]="<tr row-key='$key'>";
		foreach ($headers as $colID=>$colTitle) {
			if(isset($row[$colID])) {
				$html[]="<td col-key='$colID'>{$row[$colID]}</td>";
			} else {
				$html[]="<td col-key='$colID'></td>";
			}
		}
		$html[]="</tr>";
	}
	$html[]="</tbody>";
	$html[]="</table>";

	return implode("", $html);
}
function processReportWhere($sql,$reportConfig) {
	$searchCols=$reportConfig['searchCols'];
	if(!isset($_POST['filterrule'])) $_POST['filterrule']=[];
		if(isset($_POST['filter']) && count($_POST['filter'])>0) {
			$whereFilters=[];
			foreach ($_POST['filter'] as $key => $value) {
        if(isset($reportConfig['datagrid'][$key]) && isset($reportConfig['datagrid'][$key]['filter']) && isset($reportConfig['datagrid'][$key]['filter']['type'])) {
          $valueArr=processFilterType($value, $reportConfig['datagrid'][$key]['filter']);
          if($valueArr) {
            $value=$valueArr[0];
            $_POST['filterrule'][$key]=$valueArr[1];
          }
        }
				if(isset($_POST['filterrule'][$key])) {
          $whereFilters[]=[getColAlias($key,$reportConfig)=>array("VALUE"=>$value,"OP"=>$_POST['filterrule'][$key])];
				} else {
					$whereFilters[]=[getColAlias($key,$reportConfig)=>array("VALUE"=>$value,"OP"=>"SW")];
				}
			}
			$sql->_whereMulti($whereFilters);
		}
    
		if(isset($_POST['search']) && count($_POST['search'])>0) {
			if(isset($_POST['search']['q']) && count($_POST['search']['q'])>0) {
				$searchArr=[];
				$q=$_POST['search']['q'];
				foreach ($searchCols as $col) {
					$searchArr[]=[getColAlias($col,$reportConfig)=>array("VALUE"=>$q,"OP"=>"LIKE")];
				}
				$sql->_whereMulti($searchArr,"AND","OR");
			}
		}

	return $sql;
}
function getColAlias($col,$reportConfig) {
	if(isset($reportConfig['alias']) && isset($reportConfig['alias'][$col])) {
		return $reportConfig['alias'][$col];
	} elseif(isset($reportConfig['datagrid'][$col]['alias'])) {
		return $reportConfig['datagrid'][$col]['alias'];
	} else {
		return $col;
	}
}
function processFilterType($value, $filterConfig) {
  switch(strtolower($filterConfig['type'])) {
    case "period":
      if($value=="today") {
        return [date("Y-m-d"),"EQ"];
      } elseif($value=="overdue") {
        return [date('Y-m-d'),"LT"];
      } elseif($value=="yesterday") {
        return [date('Y-m-d',strtotime("-1 days")),"EQ"];
      } elseif($value=="tomorrow") {
        return [date('Y-m-d',strtotime("+1 days")),"EQ"];
      } elseif($value=="week") {
        return [[date('Y-m-d',strtotime("this week")),date('Y-m-d')],"RANGE"];
      } elseif($value=="thisweek") {
        return [[date('Y-m-d',strtotime("next week")),date('Y-m-d')],"RANGE"];
      } elseif($value=="nextweek") {
        $monday = strtotime("next monday");
        $sunday = strtotime(date("Y-m-d",$monday)." +6 days");
        return [[date('Y-m-d',$monday),date('Y-m-d',$sunday)],"RANGE"];
      } elseif($value=="month") {
        return [[date('Y-m-1'),date('Y-m-d')],"RANGE"];
      } elseif($value=="thismonth") {
        return [[date('Y-m-d'),date('Y-m-d',strtotime("first day of next month"))],"RANGE"];
      } elseif($value=="thisyear" || $value=="year") {
        return [[date('Y-1-1'),date('Y-m-d')],"RANGE"];
      }
      break;
    case "daterange":
      $value=explode(" - ",$value);
      $dt1=_date($value[0],'d/m/Y','Y-m-d');
      $dt2=_date($value[1],'d/m/Y','Y-m-d');
      if($dt1==null || strlen($dt1)<=0 || $dt2==null || strlen($dt2)<=0) {
      	return false;
      }
      return [[$dt1,$dt2],"RANGE"];
      break;
  }
  return [$value,"SW"];
}
?>

