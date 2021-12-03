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
	case "sidebar":
		$reportKey=$_REQUEST['gridid'];
		if(!isset($_SESSION['REPORT'][$reportKey])) {
			trigger_error("Sorry, grid report key not found.");
		}

		$reportConfig=$_SESSION['REPORT'][$reportKey];

		generateSidebar($reportConfig);
	break;
	case "smartfilter":
		$reportKey=$_REQUEST['gridid'];
		if(!isset($_SESSION['REPORT'][$reportKey])) {
			trigger_error("Sorry, grid report key not found.");
		}

		$reportConfig=$_SESSION['REPORT'][$reportKey];

		generateSmartfilter($reportConfig);
	break;
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

						if(isset($reportConfig['kanban']['colkeys'][$_POST['dataField']]) &&
							isset($reportConfig['kanban']['colkeys'][$_POST['dataField']]['alias'])) {

			            	$dataField = $reportConfig['kanban']['colkeys'][$_POST['dataField']]['alias'];
					   	} else {
				           	$dataField = $_POST['dataField'];
					   	}
						                                    
						$sql=_db()->_updateQ($srcTable,[$dataField=>$_POST['dataVal']],[$colID=>$_POST['dataHash']]);
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
				
				if(!isset($src['columns'])) {
					if(isset($src['cols'])) {
						$src['columns'] = $src['cols'];
					} else {
						$src['columns'] = "*";
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
			//           ["title"=>"","value"=>""]
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

					$ruleSet = [
						"row_class"=>[],
						"col_class"=>[],
					];
					if(isset($reportConfig['rules']) && is_array($reportConfig['rules'])) {
						$ruleSet = array_merge($ruleSet,$reportConfig['rules']);
					}

					$dataKey=array_keys($reportConfig['datagrid'])[0];
					$otherKey=array_keys($data[0])[0];
					//printArray($ruleSet);
					foreach ($data as $record) {
						if(isset($record['id'])) {
							$hashid=($record['id']);//md5
						} elseif(isset($record[$dataKey])) {
							$hashid=md5($record[$dataKey]);
						} else {
							$hashid=md5($record[$otherKey]);
						}

						$rowClass = "";

						if(count($ruleSet['row_class'])>0) {
							foreach ($ruleSet['row_class'] as $key => $ruleArr) {
								if(isset($record[$key])) {
									if(isset($ruleArr[$record[$key]])) {
										$rowClass .= " " . $ruleArr[$record[$key]];
									} elseif(isset($ruleArr[strtolower($record[$key])])) {
										$rowClass .= " " . $ruleArr[strtolower($record[$key])];
									}
								}
							}
						}

						if($reportConfig['secure']) {
							echo "<tr class='tableRow {$rowClass}' data-hash='".md5($hashid)."'>";
						} else {
							echo "<tr class='tableRow {$rowClass}' data-hash='{$hashid}'>";
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
								echo formatReportColumn($key,$record[$key],$column['formatter'],$column['hidden'],$record,$ruleSet);
							} elseif(isset($record[$keyx])) {
								echo formatReportColumn($key,$record[$keyx],$column['formatter'],$column['hidden'],$record,$ruleSet);
							} else {
								echo formatReportColumn($key,"",$column['formatter'],$column['hidden'],$record,$ruleSet);
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
	case "fetchChartData":
		$reportKey=$_REQUEST['gridid'];
		if(!isset($_SESSION['REPORT'][$reportKey])) {
			trigger_error("Sorry, grid report key not found.");
		}

		$reportConfig=$_SESSION['REPORT'][$reportKey];

		if(isset($reportConfig['charts']) && isset($reportConfig['charts']['source']) && count($reportConfig['charts']['source'])>0) {
			
			if(isset($reportConfig['charts']['source']['type'])) {
				$reportConfig['charts']['source'] = [$reportConfig['charts']['source']];
			}

			if(!isset($reportConfig['charts']['type'])) $reportConfig['charts']['type'] = "line";
			if(!isset($reportConfig['charts']['options'])) $reportConfig['charts']['options'] = [];

			$fData = [];
			$labels = [];
			foreach ($reportConfig['charts']['source'] as $kn => $src) {
				$dbKey = $reportConfig['dbkey'];
				if(isset($src['dbkey'])) $dbKey = $src['dbkey'];
				$dbData = _db($dbKey)->queryBuilder()->fromJSON(json_encode($src),_db($dbKey));
				if($dbData) {
					$tempData = $dbData->_GET();

					if($tempData) {
						if(!isset($src['fill'])) $src['fill'] = false;
						if(!isset($src['title'])) {
							$src['title'] = "Dataset {$kn}";
							if(!is_numeric($kn)) $src['title'] = $kn;
						}

						$fData[$kn] = ["datapoints"=>[],"fill"=>$src['fill'],"title"=>$src['title']];
						foreach ($tempData as $record) {
							if(isset($record['title']) && isset($record['value'])) {
								$labels[] = $record['title'];
								$fData[$kn]['datapoints'][] = $record['value'];
							}
						}
					}
				}
			}

			if(isset($reportConfig['charts']['title']) && strlen($reportConfig['charts']['title'])>0) {
				$reportConfig['charts']['options']['title'] = [
						"display"=>true,
						"text"=>$reportConfig['charts']['title']
					];
			}

			if(!isset($reportConfig['charts']['options']['scales'])) {
				$reportConfig['charts']['options']['scales'] = [];

				if(isset($reportConfig['charts']['x-axis-text']) && strlen($reportConfig['charts']['x-axis-text'])>0) {
					$reportConfig['charts']['options']['scales']['xAxes'] = [
						[
							"display"=>true,
							"scaleLabel"=> [
								"display"=> true,
								"labelString"=> $reportConfig['charts']['x-axis-text']
							]
						]
					];
				}

				if(isset($reportConfig['charts']['y-axis-text']) && strlen($reportConfig['charts']['y-axis-text'])>0) {
					$reportConfig['charts']['options']['scales']['yAxes'] = [
						[
							"display"=>true,
							"scaleLabel"=> [
								"display"=> true,
								"labelString"=> $reportConfig['charts']['y-axis-text']
							]
						]
					];
				}
			}
			

			if(!isset($reportConfig['charts']['options']['legend'])) {
				if(count($reportConfig['charts']['source'])>1) {
					$reportConfig['charts']['options']['legend'] = [
							"display"=>true,
							"position"=>"right"
						];
				}
			}

			if(count($fData)>0) {
				printServiceMsg([
					"type"=>$reportConfig['charts']['type'],
					"options"=>$reportConfig['charts']['options'],
					"labels"=>array_unique($labels),
					"datasets"=>$fData
				]);
			} else {
				printServiceMsg("DATA NOT FOUND");
			}
		} else {
			printServiceMsg("CHART NOT FOUND");
		}
	break;
	default:
		trigger_error("Action Not Defined or Not Supported");
}

function getGridDataMax($reportKey,$reportConfig) {
	//if(isset($_SESSION[$reportKey]['MAXRECORDS'])) return $_SESSION[$reportKey]['MAXRECORDS'];

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

			if(!isset($reportConfig['autosort']) || $reportConfig['autosort']!==true) {
				if(isset($_POST['orderby']) && strlen($_POST['orderby'])>0) {
					$sql->_orderby(getColAlias($_POST['orderby'],$reportConfig));
				}
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
	        $colNameKey = getColAlias($key,$reportConfig);
	        if(isset($reportConfig['datagrid'][$key]) && isset($reportConfig['datagrid'][$key]['filter'])) {
	        	// && isset($reportConfig['datagrid'][$key]['filter']['type'])
	          $valueArr=processFilterType($value, $reportConfig['datagrid'][$key]['filter']);
	          
	          if($valueArr) {
	            $value=$valueArr[0];
	            $_POST['filterrule'][$key]=$valueArr[1];

	            if(isset($reportConfig['datagrid'][$key]['filter']['type'])) {
	            	if($reportConfig['datagrid'][$key]['filter']['type']=="daterange") {
	            		$colNameKey = "date({$colNameKey})";
	            	}
	            }
	          }
	        }

			if(isset($_POST['filterrule'][$key])) {
      			$whereFilters[]=[$colNameKey=>array("VALUE"=>$value,"OP"=>$_POST['filterrule'][$key])];
			} else {
				$whereFilters[]=[$colNameKey=>array("VALUE"=>$value,"OP"=>"SW")];
			}
		}
		$sql->_whereMulti($whereFilters);
	}

	if(isset($_POST['search']) && count($_POST['search'])>0) {
		if(isset($_POST['search']['q']) && strlen($_POST['search']['q'])>0) {
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
	if(!isset($filterConfig['type'])) $filterConfig['type'] = "";

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
	if(!isset($filterConfig['qtype'])) $filterConfig['qtype'] = "SW";
	return [$value,$filterConfig['qtype']];
}
function generateSidebar($reportConfig) {
	if($reportConfig["source"]['type']=="sql") {
		$fieldID = array_keys($reportConfig['sidebar']['source'])[0];
		$field = $reportConfig['sidebar']['source'][$fieldID];
		$lastValue = "";

		if(!isset($field['cols']) && isset($field['data_col'])) {
			if(isset($field['group_col'])) {
				$field['cols'] = "{$field['data_col']} as title, {$field['data_col']} as value,{$field['group_col']} as category";
			} else {
				$field['cols'] = "{$field['data_col']} as title, {$field['data_col']} as value";
			}
		}

		if(isset($reportConfig['LASTVIEW-REQUEST'])
				 && isset($reportConfig['LASTVIEW-REQUEST']['filter'])
				 && isset($reportConfig['LASTVIEW-REQUEST']['filter'][$fieldID])) {
			$lastValue = $reportConfig['LASTVIEW-REQUEST']['filter'][$fieldID];
		}

		$dbKeyForList = $reportConfig['dbkey'];
		if(isset($field['dbkey'])) $dbKeyForList = $field['dbkey'];

		$dbData = _db($dbKeyForList)->queryBuilder()->fromJSON(json_encode($field),_db($dbKeyForList));
		if($dbData) {
			$dbData->_limit(500);
			if($dbData->_array()["groupby"]==NULL || !is_array($dbData->_array()["groupby"])) {
				$dbData->_groupBy(current(explode(" ", current(explode(",", $field['cols'])))));
			}
			$dbData = $dbData->_GET();

			if($dbData && count($dbData)>0) {
				if(isset($dbData[0]['category'])) {
					$dbDataFinal = [];
					foreach ($dbData as $record) {
						if(!isset($dbDataFinal[$record['category']])) $dbDataFinal[$record['category']] = [];
						$dbDataFinal[$record['category']][] = $record;
					}

					echo "<div class='list-group report-sidebar'>";
					echo "<input type='hidden' class='reportFilters' name='{$fieldID}' />";
					echo "<li class='list-group-item list-group-flush' data-value=''>".toTitle(_ling("All records"))."</li>";
					foreach ($dbDataFinal as $category => $recordSet) {
						$collapseID = md5($category.time());

						echo '<div class="panel panel-default">';
						if(strlen($category)>0 && $category!=".") {
							echo '<div class="panel-heading" data-toggle="collapse" href="#'.$collapseID.'" role="button" aria-expanded="false" aria-controls="'.$collapseID.'">'.toTitle(_ling($category)).
								' <i class="fa fa-panel-status pull-right"></i></div>';
							echo '<div id="'.$collapseID.'" class="panel-body nopadding collapse">';
						} else {
							echo '<div id="'.$collapseID.'" class="panel-body nopadding">';
						}

						echo "<ul class='list-group'>";
						foreach ($recordSet as $record) {
							if(strlen($record['value'])<=0) continue;
							if($lastValue==$record['value'])
								echo "<li class='list-group-item list-group-flush active' data-value='{$record['value']}'>".toTitle(_ling($record['title']))."</li>";
							else
								echo "<li class='list-group-item list-group-flush' data-value='{$record['value']}'>".toTitle(_ling($record['title']))."</li>";
						}
						echo "</ul>";
						echo '</div></div>';
					}
					echo "</div>";
				} else {
					echo "<ul class='list-group report-sidebar'>";
					echo "<input type='hidden' class='reportFilters' name='{$fieldID}' />";
					echo "<li class='list-group-item list-group-flush' data-value=''>".toTitle(_ling("All records"))."</li>";

					if(isset($field['data_col'])) {
						$finalList = [];
						foreach ($dbData as $record) {
							$recs = explode(",", $record['title']);
							foreach ($recs as $a) {
								$finalList[]=$a;
							}
						}
						$finalList = array_unique($finalList);
						sort($finalList);
						
						foreach ($finalList as $value) {
							if(strlen($value)<=0) continue;
							if($lastValue==$record['value'])
								echo "<li class='list-group-item list-group-flush active' data-value='{$value}'>".toTitle(_ling($value))."</li>";
							else
								echo "<li class='list-group-item list-group-flush' data-value='{$value}'>".toTitle(_ling($value))."</li>";
						}
					} else {
						foreach ($dbData as $record) {
							if(strlen($record['value'])<=0) continue;
							if($lastValue==$record['value'])
								echo "<li class='list-group-item list-group-flush active' data-value='{$record['value']}'>".toTitle(_ling($record['title']))."</li>";
							else
								echo "<li class='list-group-item list-group-flush' data-value='{$record['value']}'>".toTitle(_ling($record['title']))."</li>";
						}
					}

					echo "</ul>";
				}
			} else {
				echo "<div class='list-group report-sidebar'>";
				echo "<ul class='list-group'>";
				echo "<h3 class='text-center'>".toTitle(_ling("No filters"))."</h3>";
				//echo "<li class='list-group-item list-group-flush'>".toTitle(_ling("No filters"))."</li>";
				echo "</ul>";
				echo "</div>";
				return;
			}
		} else {
			echo "<div class='list-group report-sidebar'>";
			echo "<ul class='list-group'>";
			echo "<h3 class='text-center'>".toTitle(_ling("No filters"))."</h3>";
			// echo "<li class='list-group-item list-group-flush'>".toTitle(_ling("No filters"))."</li>";
			echo "</ul>";
			echo "</div>";
			return;
		}
	} else {
		echo "<p class='text-center'><br><br><br>Source type not supported</p>";
	}
}
function generateSmartfilter($reportConfig) {
	$smartfilterConfig = $reportConfig['smartfilter'];

	$fieldID = array_keys($reportConfig['smartfilter']['source'])[0];
	$field = $reportConfig['smartfilter']['source'][$fieldID];

	if(!isset($field['cols']) && isset($field['data_col'])) {
		if(isset($field['group_col'])) {
			$field['cols'] = "{$field['data_col']} as title, {$field['data_col']} as value,{$field['group_col']} as category";
		} else {
			$field['cols'] = "{$field['data_col']} as title, {$field['data_col']} as value";
		}
	}

	$finalData = false;
	$dbKeyForList = $reportConfig['dbkey'];
	if(isset($field['dbkey'])) $dbKeyForList = $field['dbkey'];

	$dbData = _db($dbKeyForList)->queryBuilder()->fromJSON(json_encode($field),_db($dbKeyForList));
	if($dbData) {
		$dbData->_limit(500);
		if($dbData->_array()["groupby"]==NULL || !is_array($dbData->_array()["groupby"])) {
			$dbData->_groupBy(current(explode(" ", current(explode(",", $field['cols'])))));
		}
		$dbData = $dbData->_GET();

		if($dbData && count($dbData)>0) {
		    $finalData = $dbData;
		} else {
		    return;
		}
	} else {
	    return;
	}

	if(!isset($smartfilterConfig['all_records'])) $smartfilterConfig['all_records'] = false;
	if(!isset($smartfilterConfig['show_counter'])) $smartfilterConfig['show_counter'] = true;
	if(!isset($smartfilterConfig['show_icons'])) $smartfilterConfig['show_icons'] = true;
	if(!isset($smartfilterConfig['title_prefix'])) $smartfilterConfig['title_prefix'] = "All";

	$totalCount = 0;

	if($finalData) {
		foreach($finalData as $row) {
			if(isset($row['counter'])) $totalCount += $row['counter'];
		}
	}

	if(!isset($smartfilterConfig['all_records']) || $smartfilterConfig['all_records']) {
		echo "<li class='filter-item' data-value=''>";
	        echo "<a href='#'>";
	        if($smartfilterConfig['show_icons']) echo "<i class='filter-icon fa filter-icon-all' aria-hidden='true'></i>";
	        if($smartfilterConfig['show_counter']) echo "<h4>{$totalCount}</h4>";
	        echo "<span>All Records</span>";
	        echo "</a>";
	    echo "</li>";
	}
	
    
    if($finalData) {
        foreach($finalData as $row) {
            $clz = "filter-item ";//class="active"
            $icon = "fa";
            if(isset($row['class'])) $clz .= " {$row['class']}";
            if(isset($row['icon'])) $icon .= " filter-icon-"._slugify($row['icon']);
            
            $clz = trim($clz);
            $icon = trim($icon);
            
            echo "<li class='{$clz}' data-value='{$row['value']}'>";
                echo "<a href='#'>";
                    if($smartfilterConfig['show_icons']) echo "<i class='filter-icon {$icon}' aria-hidden='true'></i>";
                    if(isset($row['counter']) && $smartfilterConfig['show_counter']) echo "<h4>{$row['counter']}</h4>";
                    echo "<span>".trim("{$smartfilterConfig['title_prefix']} ".toTitle(_ling($row['title'])))."</span>";
                echo "</a>";
            echo "</li>";
        }
    }
}
?>
