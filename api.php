<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!function_exists("findReport")) {
	function findReport($file) {
		$fileName=$file;
		if(!file_exists($file)) {
			$file=str_replace(".","/",$file);
		}

		$fsArr=[
				$file,
				APPROOT.APPS_MISC_FOLDER."reports/{$file}.json",
			];
		if(isset($_REQUEST['forSite']) && defined("CMS_SITENAME")) {
			$fsArr[]=ROOT."apps/".CMS_SITENAME."/".APPS_MISC_FOLDER."reports/{$file}.json";
		}
		
		$fArr = explode("/",$file);
		if(count($fArr)>1) {
			$fPath = checkModule($fArr[0]);
			if($fPath) {
				unset($fArr[0]);
				$fsArr[] = dirname($fPath)."/reports/".implode("/",$fArr).".json";
			}
		}

		$file=false;
		foreach ($fsArr as $fs) {
			if(file_exists($fs)) {
				$file=$fs;
				break;
			}
		}
		if(!file_exists($file)) {
			return false;
		}

		$reportData=file_get_contents($file);
		$reportData=_replace($reportData);
		$reportConfig=json_decode($reportData,true);

		if(count($reportConfig)<1) {
			return false;
		}

		$reportConfig['sourcefile']=$file;
		$reportConfig['reportkey']=md5(session_id().time().$file);
		$reportConfig['reportgkey']=md5($file);
		$reportConfig['srckey']=$fileName;
		if(!isset($reportConfig['dbkey'])) $reportConfig['dbkey']="app";

		return $reportConfig;
	}

	function printReport($reportConfig,$dbKey=false,$params=[]) {
		if(!is_array($reportConfig)) $reportConfig=findReport($reportConfig);

		if(!is_array($reportConfig) || count($reportConfig)<=2) {
			trigger_logikserror("Corrupt report defination");
			return false;
		}
		if(isset($reportConfig['policy']) && strlen($reportConfig['policy'])>0) {
			$allow=checkUserPolicy($reportConfig['policy']);
			if(!$allow) {
				trigger_logikserror("Sorry, you are not allowed to access this report");
				return false;
			}
		}

		if($params==null) $params=[];
		$reportConfig=array_replace_recursive($reportConfig,$params);
		
		if(isset($_SESSION['REPORT_CONFIG']) && is_array($_SESSION['REPORT_CONFIG'])) {
			$globalParams = $_SESSION['REPORT_CONFIG'];
			$reportConfig=array_replace_recursive($reportConfig,$globalParams);
		}

		if(!isset($reportConfig['reportkey'])) $reportConfig['reportkey']=md5(session_id().time());

		if($dbKey) {
			$reportConfig['dbkey']=$dbKey;
		}
		if(!isset($reportConfig['dbkey'])) $reportConfig['dbkey']="app";

		if(isset($reportConfig['template']) && strlen($reportConfig['template'])>0) {

		} elseif(isset($_COOKIE['RPTVIEW-'.$reportConfig['reportgkey']])) {
			$reportConfig['template']=$_COOKIE['RPTVIEW-'.$reportConfig['reportgkey']];
		} else {
			$reportConfig['template']="grid";
		}
		//setCookie('RPTVIEW-'.$reportConfig['reportgkey'],$reportConfig['template'],0,"/");
		setCookie('RPTVIEW-'.$reportConfig['reportgkey'],$reportConfig['template'],0,"/",$_SERVER['SERVER_NAME'], isHTTPS());

		if(!isset($reportConfig['source']['cols'])) {
			$reportConfig['source']['cols']="*";//array_keys($reportConfig['datagrid'])
		}
		if(!isset($reportConfig['source']['limit'])) {
			$reportConfig['source']['limit']=10;
		}
		if(!isset($reportConfig['showExtraColumn'])) {
			$reportConfig['showExtraColumn']=false;
		}

		if(!isset($reportConfig['secure'])) {
			$reportConfig['secure']=true;
		}
		if(!isset($reportConfig['uiswitcher'])) {
			$reportConfig['uiswitcher']=false;
		}

		$reportConfig['searchCols']=[];
		foreach ($reportConfig['datagrid'] as $key => $col) {
			if(isset($col['searchable']) && $col['searchable']) {
				if(isset($col['alias'])) {
					$reportConfig['searchCols'][]=$col['alias'];
				} else {
					$reportConfig['searchCols'][]=current(explode(" ",$key));
				}
			}
			if(isset($col['noshow']) && $col['noshow']) {
				$reportConfig['datagrid'][$key]['hidden']=true;
			}
		}

		if(!isset($reportConfig['toolbar'])) {
			$reportConfig['toolbar']=[];
		}
		if(!isset($reportConfig['topbar'])) {
			$reportConfig['topbar']=[];
		}

		if(isset($reportConfig['toolbar']) && $reportConfig['toolbar']===false) {
			$reportConfig['toolbar']['reload']=false;
			$reportConfig['toolbar']['search']=false;
			$reportConfig['toolbar']['print']=false;
			$reportConfig['toolbar']['export']=false;
			$reportConfig['toolbar']['email']=false;
			$reportConfig['toolbar']['filter']=false;
			$reportConfig['toolbar']['columnselector']=false;
		}

		if(!isset($reportConfig['export'])) $reportConfig['export']=[];

		if(!isset($reportConfig['export']['pdf'])) {
			$reportConfig['export']['pdf']=APPROOT.APPS_TEMPLATE_FOLDER.'print-report.tpl';
		}

		if(!isset($reportConfig['toolbar']['search'])) {
			if(count($reportConfig['searchCols'])>0) {
			  $reportConfig['toolbar']['search']=true;
			} else {
			  $reportConfig['toolbar']['search']=false;
			}
		} else {
// 			if(count($reportConfig['searchCols'])<=0) {
// 			  $reportConfig['toolbar']['search']=false;
// 			} else {
// 				$reportConfig['toolbar']['search']=true;
// 			}
		}
		if(!isset($reportConfig['buttons_align'])) {
			$reportConfig['buttons_align'] = "left";
		}
		//printArray($reportConfig);return;

		if(!isset($reportConfig['topbar'])) {
	  		$reportConfig['topbar']=[];
		}

		$reportKey=$reportConfig['reportkey'];
		$_SESSION['REPORT'][$reportKey]=$reportConfig;

		$templateArr=[
				$reportConfig['template'],
				__DIR__."/templates/{$reportConfig['template']}.php"
			];
		foreach ($templateArr as $f) {
			if(file_exists($f) && is_file($f)) {
				executeReportHook("preload",$reportConfig);
				
// 				printArray($reportConfig);return;
				$vendorPath=getWebPath(__DIR__)."/vendors/";
        
				echo "<link href='{$vendorPath}daterangepicker/daterangepicker.css' rel='stylesheet' type='text/css' />";
				
				echo _css('reports');
				if(isset($reportConfig['style']) && strlen($reportConfig['style'])>0) {
					echo _css(["reports/{$reportConfig['style']}",$reportConfig['style']]);
				}
				echo "<div class='row'>";

				if(isset($reportConfig['sidebar']) && isset($reportConfig['sidebar']['type']) &&
						isset($reportConfig['sidebar']['source']) && count($reportConfig['sidebar']['source'])>0) {

					$sidebarFile = __DIR__."/templates/comps/sidebar_{$reportConfig['sidebar']['type']}.php";

					echo "<div class='hidden-xs hidden-sm noprint col-md-2 col-lg-2 nopadding report-sidebar-container'>";
					if(file_exists($sidebarFile)) {
						include_once $sidebarFile;
					} else {
						echo "<p class='text-center'><br><br><br>Sidebar Source Not Found</p>";
					}
					echo "</div>";

					echo "<div class='col-xs-12 col-sm-12 col-md-10 col-lg-10 nopadding report-main-container'>";

					if(isset($reportConfig['charts'])) {
						$chartFile = __DIR__."/templates/comps/charts.php";
						if(file_exists($chartFile)) {
							include $chartFile;
						}
					}

					include $f;	
					echo "</div>";
				} else {
					echo "<div class='col-xs-12 col-sm-12 col-md-12 col-lg-12 report-main-container'>";

					if(isset($reportConfig['charts'])) {
						$chartFile = __DIR__."/templates/comps/charts.php";
						if(file_exists($chartFile)) {
							include $chartFile;
						}
					}

					include $f;	
					echo "</div>";
				}
				
				echo "</div>";

				echo _js(['moment','reports']);
				if(isset($reportConfig['script']) && strlen($reportConfig['script'])>0) {
					echo _js(["reports/{$reportConfig['script']}",$reportConfig['script']]);
				}
				echo "<script src='{$vendorPath}daterangepicker/daterangepicker.js' type='text/javascript' language='javascript'></script>";
				return true;
			}
		}
		trigger_logikserror("Report Template Not Found",null,404);
	}


	function formatReportColumn($key,$value,$type="text",$hidden=false,$record=[], $ruleSet = [],$columnInfo = false) {
		$clz="tableColumn";
		$xtraAttributes = [];
		if($hidden) $clz.=" hidden";
		$keyS=str_replace(".","_",$key);

		if(count($ruleSet['col_class'])>0) {
			$keyID = explode(".", $key);
			$keyID = end($keyID);
			if(isset($ruleSet['col_class'][$keyID])) {
				$ruleArr = $ruleSet['col_class'][$keyID];

				if(isset($ruleArr[$value])) {
					$clz .= " ".$ruleArr[$value];
				} elseif(isset($ruleArr[strtolower($value)])) {
					$clz .= " ".$ruleArr[strtolower($value)];
				}
			}
		}

		if(isset($record['id'])) {
			$record['hashid'] = md5($record['id']);
		}

		$unilink = "";
		$lt = new LogiksReplace();
		$lt->setData($record);
		if(isset($columnInfo['unilink'])) {
			if(!isset($columnInfo['unilink_type'])) $columnInfo['unilink_type'] = "link";

			$columnInfo['unilink'] = $lt->_replace(str_replace("{","#",str_replace("}","#",$columnInfo['unilink'])));
			$unilink = "data-unilink='{$columnInfo['unilink']}' data-unilinktype='{$columnInfo['unilink_type']}'";
			$clz.=" unilink";
		}

		if(isset($columnInfo['calculate'])) {
			$xtraAttributes[] = "data-calculate='{$columnInfo['calculate']}'";
		}
		if(isset($columnInfo['colgroup'])) {
			$xtraAttributes[] = "data-colgroup='{$columnInfo['colgroup']}'";
		}

		$xtraAttributes = implode(" ", $xtraAttributes);

		switch (strtolower($type)) {
			case 'date':
				$value=current(explode(" ", $value));
				return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'>"._pDate($value)."</td>";
				break;

			case 'time':
				$value=explode(" ", $value);
				$value=end($value);
				if($value==null || strlen($value)<=0)
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'></td>";
				else
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'>"._time($value)."</td>";
				break;

			case 'datetime':
				return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'>"._pDate($value)."</td>";
				break;

			case 'currency':
				return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'>".number_format($value,2)."</td>";
				break;

			case 'num':case 'number':
				return "<td {$unilink} class='{$clz} {$keyS} {$type} text-center' $xtraAttributes data-key='$key' data-value='{$value}'>".$value."</td>";
				break;

			case 'url':
				if($value && strlen($value)>0)
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'><a class='fa fa-globe' href='{$value}' target=_blank> LINK</a></td>";
				else
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'></td>";
				break;
			case 'email':
				if($value && strlen($value)>0)
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'><a class='fa fa-email' href='email:{$value}'> {$value}</a></td>";
				else
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'></td>";
				break;
			case 'tel':case 'mob':case 'phone':case 'mobile':
				if($value && strlen($value)>0)
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'><a class='fa fa-phone' href='tel:{$value}'> {$value}</a></td>";
				else
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'></td>";
				break;

			case 'geoloc':case 'geolocation':case 'geoaddress':
				if($value && strlen($value)>0)
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'><a class='fa fa-map-marker' href='https://www.google.co.in/maps/place/{$value}' target=_blank> MAP</a></td>";
				else
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'></td>";
				break;

			case 'color':
				if($value && strlen($value)>0)
					return "<td {$unilink} class='{$clz} {$keyS} {$type} text-center' $xtraAttributes data-key='$key' data-value='{$value}'><span style='background:{$value};'></span></td>";
				else
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'></td>";
				break;

			case "avatar":
				if($value==null || strlen($value)<=0) $value=loadMedia("images/user.png");
				$fname=basename($value);

				if($value && strlen($value)>0)
					return "<td {$unilink} class='{$clz} {$keyS} {$type} imagebox text-center' $xtraAttributes data-key='$key' data-value='{$value}'><div class='image-avatar'><img src='{$value}' class='img-responsive' alt='{$fname}' /></div></td>";
				else
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'></td>";
				break;
			case "photo":case "picture":case "media":
				if($value==null || strlen($value)<=0) $value=loadMedia("images/noimg.png");
				elseif(substr($value,0,7)!="http://" && substr($value,0,7)!="https://" && substr($value,0,7)!="ftp://") {
					$value=searchMedia($value);
					$value=$value['url'];
				}
				$fname=basename($value);

				if($value && strlen($value)>0)
					return "<td {$unilink} class='{$clz} {$keyS} {$type} imagebox text-center' $xtraAttributes data-key='$key' data-value='{$value}'><div class='image-inline'><img src='{$value}' class='img-responsive' alt='{$fname}' /></div></td>";
				else
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'></td>";
				break;

			case "file":case "attachment":
				if($value==null || strlen($value)<=0) {
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'>No File</td>";
				} else {
					if(!(substr($value,0,5)=="http:" || substr($value,0,6)=="https:" || substr($value,0,4)=="ftp:")) {
			            $valueF = searchMedia($value);
			            $value=$valueF['url'];
		          	}
		          	if(strlen($value)>0)
						return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'><a class='fa fa-paperclip' href='{$value}' target=_blank> FILE</a></td>";
					else
						return "<td class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'></td>";
				}
				break;
			case "mediafile":
				if($value==null || strlen($value)<=0) {
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'>No File</td>";
				} else {
					$valueX=searchMedia($value);
					if($valueX && strlen($valueX)>0) {
						return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'><a class='fa fa-paperclip' href='{$valueX['url']}' target=_blank> FILE</a></td>";
					} else {
						return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}' title='{$value}'>Not Found</td>";
					}
				}
				break;
			case 'method':
				$keyFunc=explode(".",$key);
				$keyFunc=end($keyFunc);
				$keyFunc="get".ucwords($keyFunc);
				if(function_exists($keyFunc)) {
					$valueS=call_user_func($keyFunc,$value,$record, $columnInfo);
				} else {
					$valueS="--";
				}
				return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'>{$valueS}</td>";
				break;

			case "embed":
				if($value==null || strlen($value)<=0) {
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'></td>";
				} else {
					return "<td {$unilink} class='{$clz} {$keyS} {$type} embed' $xtraAttributes data-key='$key' data-value='###'><i class='fa fa-arrows-alt'></i> OPEN <div class='contentBox hidden'>{$value}</div></td>";
				}
				break;
			case "video":case "videoembed":
				if($value==null || strlen($value)<=0) {
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'></td>";
				} else {
					return "<td {$unilink} class='{$clz} {$keyS} {$type} embed' $xtraAttributes data-key='$key' data-value='###'><i class='fa fa-youtube-play'></i> OPEN <div class='contentBox hidden'>{$value}</div></td>";
				}
				break;

			case "iframe":
				if($value==null || strlen($value)<=0) {
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'></td>";
				} else {
					$value="<iframe width='560' height='315' src='{$value}' frameborder='0' allowfullscreen></iframe>";
					return "<td {$unilink} class='{$clz} {$keyS} {$type} embed' $xtraAttributes data-key='$key' data-value='###'><i class='fa fa-arrows-alt'></i> OPEN <div class='contentBox hidden'>{$value}</div></td>";
				}
				break;

			case 'content':
				if($value==null || strlen($value)<=0) {
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'>"._ling("No Content")."</td>";
				} else {
					$value=str_replace("\\r\\n","<br>",$value);
					$value=str_replace("\\n","<br>",$value);
					$value=str_replace("\\'s","'s",$value);
					$value=str_replace('\\"','"',$value);
					
					if(strlen($value)>40) {
						$abstract=substr($value,0,35)." ...";
						return "<td {$unilink} class='{$clz} {$keyS} {$type} moreContent' $xtraAttributes data-key='$key' data-value='{$value}'>{$abstract}<div class='contentBox hidden'>{$value}</div></td>";
					} else {
						return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'>{$value}</td>";
					}
				}
				break;
			case 'json':
				$value=json_decode(stripslashes($value),true);
				$html="<ul>";
				foreach($value as $aa=>$bb) {
					$html.="<li class='list-group'><label>{$aa}</label>&nbsp;&nbsp;$bb</li>";
				}
				$html.="</ul>";
				return "<td {$unilink} class='{$clz} {$keyS} {$type} moreContent' $xtraAttributes data-key='$key' data-value=''>VIEW<div class='contentBox hidden'>{$html}</div></td>";
				break;

			case 'checkbox':
				$value1=strtolower($value);
				$html="<td {$unilink} class='{$clz} {$keyS} checkboxes' $xtraAttributes data-key='$key' data-value='{$value}'>";
				if($value===true || $value1=="true" || $value1=="on") {
					$html.="<input class='noprint' type='checkbox' disabled checked=true />";
				} else {
					$html.="<input class='noprint' type='checkbox' disabled />";
				}
				$html.="<span class='onlyprint'>{$value1}</span>";
				$html.="</td>";
				return $html;
				break;
			case 'template':
				$html="<td {$unilink} class='{$clz} {$keyS} col_{$type}' $xtraAttributes data-key='$key' data-value='{$value}'>";
				if($columnInfo && isset($columnInfo['template'])) {
					$lr=new LogiksReplace();
					$lr->setData($record);
					$glue="%";
					$str=preg_replace_callback("/{$glue}[a-zA-Z0-9-_.]+{$glue}/",array($lr,"replaceFromEnviroment"),$columnInfo['template']);
					$html .= $str;
				} else {
					$html.=$value;
				}
				$html.="</td>";
				return $html;
				break;
			case 'html':
				if(is_array($value)) {
		          $value=implode(", ",$value);
		        }
		        $value=str_replace("\\r","",$value);
				$value=str_replace("\\n","<br>",$value);
	      	case 'pretty':case 'uppercase':case 'lowercase':
		        if(is_array($value)) {
		          $value=implode(", ",$value);
		        }
		        switch(strtolower($type)) {
		          case "pretty":
		            $value=toTitle($value);
		            break;
		          case "uppercase":
		            $value=strtoupper(str_replace("_"," ",$value));
		            break;
		          case "lowercase":
		            $value=strtolower(str_replace("_"," ",$value));
		            break;
		        }
			default:
				if(is_array($value)) {
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='--'>".implode(", ",$value)."</td>";
				} elseif(strlen($value)>100) {
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='--'><pre>{$value}</pre></td>";
				} elseif(strlen($value)>50) {
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='--'>{$value}</td>";
				} else {
					return "<td {$unilink} class='{$clz} {$keyS} {$type}' $xtraAttributes data-key='$key' data-value='{$value}'>{$value}</td>";
				}
				break;
		}
	}
	function formatReportFilter($key,$filterConfig=array(),$dbKey="app") {
		if(!isset($filterConfig['type'])) $filterConfig['type']="text";

	    if(!isset($filterConfig['nofilter'])) {
	      if(strpos($key,".")>0) {
	        $keyS=explode(".",$key);
	        $keyS=end($keyS);
	        $filterConfig['nofilter']="No $keyS";
	      } else {
	        $filterConfig['nofilter']="No $key";
	      }
	    }
		if(!isset($filterConfig['value'])) {
			if(isset($filterConfig['default'])) $filterConfig['value']=$filterConfig['default'];
			else $filterConfig['value']="";
		}

		$noFilter=_ling($filterConfig['nofilter']);

		switch ($filterConfig['type']) {
			case 'dataMethod': case 'dataSelector': case 'dataSelectorFromUniques': case 'dataSelectorFromTable':
			case 'select': case 'selectAJAX':
				$html="";

				$html="<select class='filterBarField autorefreshReport filterSelect' name='$key' value='{$filterConfig['value']}'>";
				$html.="<option value=''>{$noFilter}</option>";

				$html.=generateSelectOptions($filterConfig,"",$dbKey);

				$html.="</select>";

				return $html;
				break;

			case 'text':
				return "<input type='text' class='filterBarField autorefreshReport filterText' name='$key' value='{$filterConfig['value']}' />";
				break;

			case 'date':
				return "<input type='date' class='filterBarField autorefreshReport filterDate' name='$key' value='{$filterConfig['value']}' />";
				break;
      
      		case 'daterange':
				return "<input type='daterange' class='filterBarField autorefreshReport filterDate' name='{$key}' value='{$filterConfig['value']}' readonly /";
				break;
      
      		case 'period':
				$html="";

				$html="<select class='filterBarField autorefreshReport filterSelect' name='$key' value='{$filterConfig['value']}'>";
				$html.="<option value=''>{$noFilter}</option>";
        
		        if(!isset($filterConfig['options'])) {
		          $filterConfig['options']=[
		            "today"=>"Today",
		            "tomorrow"=>"Tomorrow",
		            "thisweek"=> "This Week",
		            "nextweek"=> "Next Week",
		            "overdue"=> "Overdue"
		          ];
		        }
		        $html.=generateSelectOptions(array_merge($filterConfig,[
		              "type"=>"select"
		            ]),"",$dbKey);

				$html.="</select>";

				return $html;
				break;

			case 'checkbox':
				return "<input type='checkbox' class='filterBarField autorefreshReport filterCheckbox' name='$key' />";
				break;

			default:
				return "";
				break;
		}
	}
	function createReportRecordAction($button, $record, $uiType = 'button') {
		if(isset($button['policy']) && strlen($button['policy'])>0) {
			$allow=checkUserPolicy($button['policy']);
			if(!$allow) return "";
		}
		
		$cmd=$button['cmd'];
		
		if(!isset($button['icon'])) return;
		if(!isset($button['label'])) $button['label']="";
		if(!isset($button['title'])) $button['title']="";
		if(!isset($button['class'])) $button['class']="";
		
		$button['label']=_ling($button['label']);
		$button['title']=_ling($button['title']);
		
		$_ENV['REPORTRECORD']=$record;
		$_ENV['REPORTRECORD']['hashid']="";
		
		if(!empty($record)) {
			if(isset($record['hashid'])) $_ENV['REPORTRECORD']['hashid']=$record['hashid'];
			elseif(isset($record['id'])) $_ENV['REPORTRECORD']['hashid']=md5($record['id']);
			else {
				$_ENV['REPORTRECORD']['hashid']=md5($record[array_keys($record)[0]]);
			}

			if(!isset($record['refid']) && isset($record['id'])) {
				$record['refid'] = $record['id'];
			}
		}
		$cmd=preg_replace_callback('/{(.*?)}/', function($matches) {
			$colName=substr($matches[0],1,strlen($matches[0])-2);
			if(isset($_ENV['REPORTRECORD'][$colName])) {
				// if(is_numeric($_ENV['REPORTRECORD'][$colName])) return md5($_ENV['REPORTRECORD'][$colName]);
				// return $_ENV['REPORTRECORD'][$colName];
				return $_ENV['REPORTRECORD'][$colName];
			}
			return "";
		}, $cmd);
		if(isset($_ENV['REPORTRECORD'])) unset($_ENV['REPORTRECORD']);

		if(isset($button['params'])) {
			foreach($button['params'] as $a=>$b) {
				if(isset($record[$b]))
					$button['params'][$a] = $record[$b];
				else
					$button['params'][$a] = _replace($b);
			}
			$button['params'] = json_encode($button['params']);
		} else {
			$button['params'] = "{}";
		}

		switch($uiType) {
			case "dropdown"://<i class='fa fa-eye profile-customers' ></i>
				return "<li><a href='#' cmd='{$cmd}' params='{$button['params']}' title='{$button['label']}' value='{$cmd}'><i class='{$button['icon']} {$button['class']}' cmd='{$cmd}' params='{$button['params']}'></i> {$button['label']}</a></li>";
			case "select":
				if(strlen($button['title'])<=0) $button['title'] = $button['label'];
				return "<option class='{$button['icon']} {$button['class']}' cmd='{$cmd}' params='{$button['params']}' title='{$button['label']}' value='{$cmd}'>{$button['title']}</option>";
			break;
			case "button":
			default:
				return "<i class='{$button['icon']} {$button['class']}' cmd='{$cmd}' params='{$button['params']}' title='{$button['label']}'>{$button['title']}</i>";
		}
	}
	function executeReportHook($state,$reportConfig) {
		if(!isset($reportConfig['hooks']) || !is_array($reportConfig['hooks'])) return false;
		$state=strtolower($state);

		if(!isset($_ENV['FORM-HOOK-PARAMS'])) $_ENV['FORM-HOOK-PARAMS'] = [];

		if(isset($reportConfig['hooks'][$state]) && is_array($reportConfig['hooks'][$state])) {
			$postCFG=$reportConfig['hooks'][$state];

			if(isset($postCFG['modules'])) {
				loadModules($postCFG['modules']);
			}
			if(isset($postCFG['api'])) {
				if(!is_array($postCFG['api'])) $postCFG['api']=explode(",",$postCFG['api']);
				foreach ($postCFG['api'] as $apiModule) {
					loadModuleLib($apiModule,'api');
				}
			}
			if(isset($postCFG['helpers'])) {
				loadHelpers($postCFG['helpers']);
			}
			if(isset($postCFG['method'])) {
				if(!is_array($postCFG['method'])) $postCFG['method']=explode(",",$postCFG['method']);
				foreach($postCFG['method'] as $m) call_user_func($m,$_ENV['FORM-HOOK-PARAMS']);
			}
			if(isset($postCFG['file'])) {
				if(!is_array($postCFG['file'])) $postCFG['file']=explode(",",$postCFG['file']);
				foreach($postCFG['file'] as $m) {
					if(file_exists($m)) include $m;
					elseif(file_exists(APPROOT.$m)) include APPROOT.$m;
				}
			}
		}
	}
	function getReportViewsList($reportConfig) {
		$templateViews=["grid"=>["icon"=>"fa fa-table", "title"=> "Grid Table"]];

		
		if(isset($reportConfig['kanban'])) {
		  $templateViews["kanban"]=["icon"=>"fa fa-bar-chart fa-rotate-90"];
		}
		if(isset($reportConfig['cards'])) {// || isset($reportConfig['kanban'])
		  $templateViews["cards"]=["icon"=>"fa fa-th"];
		}
		if(isset($reportConfig['calendar'])) {
		  $templateViews["calendar"]=["icon"=>"fa fa-calendar", "title"=>"Calendar"];
		}
		if(isset($reportConfig['gallery'])) {
		  $templateViews["gallery"]=["icon"=>"fa fa-photo", "title"=>"Gallery"];
		}
		if(isset($reportConfig['gmap'])) {
		  $templateViews["gmap"]=["icon"=>"fa fa-map", "title"=>"Map"];
		}
		if(isset($reportConfig['gnatt'])) {
		  $templateViews["gnatt"]=["icon"=>"fa fa-tasks", "title"=> "Gnatt"];
		}
		if(isset($reportConfig['drilldown'])) {
		  $templateViews["drilldown"]=["icon"=>"fa fa-list-alt", "title"=> "Drilldown"];
		}
		if(isset($reportConfig['pivot'])) {
		  $templateViews["pivot"]=["icon"=>"fa fa-border-none", "title"=> "Pivot"];
		}
		
		// 
		// tree_table
		// swim_lane
		// activity
		// slot

		return $templateViews;
	}
}
if(!function_exists("searchMedia")) {
	function searchMedia($media) {
		if(strpos($media,"https://")===0 || strpos($media,"http://")===0) {
			$ext=explode(".",current(explode("?",$media)));
			$ext=strtolower(end($ext));

			return [
				"name"=>basename($media),
				"raw"=>$media,
				"src"=>$media,
				"url"=>$media,
				"size"=>0,
				"ext"=>$ext,
			];
		}
		if(isset($_REQUEST['forsite'])) {
			$fs=_fs($_REQUEST['forsite'],[
					"driver"=>"local",
					"basedir"=>ROOT.APPS_FOLDER.$_REQUEST['forsite']."/".APPS_USERDATA_FOLDER
				]);
		} else {
			$fs=_fs();
			$fs->cd(APPS_USERDATA_FOLDER);
		}
		$mediaDir=$fs->pwd();

		if(file_exists($media)) {
			$ext=explode(".",$media);
			$mediaName=explode("_",basename($media));
			$mediaName=array_slice($mediaName,1);
			$mediaName=implode("_",$mediaName);
			return [
				"name"=>$mediaName,
				"raw"=>$media,
				"src"=>$media,
				"url"=>getWebPath($media),
				"size"=>filesize($media)/1024,
				"ext"=>strtolower(end($ext)),
			];
		} elseif(file_exists($mediaDir.$media)) {
			$ext=explode(".",$media);
			$mediaName=explode("_",basename($media));
			$mediaName=array_slice($mediaName,1);
			$mediaName=implode("_",$mediaName);
			return [
				"name"=>$mediaName,
				"raw"=>$media,
				"src"=>$mediaDir.$media,
				"url"=>getWebPath($mediaDir.$media),
				"size"=>filesize($mediaDir.$media)/1024,
				"ext"=>strtolower(end($ext)),
			];
		} else {
			return false;
		}
	}
}
if(!function_exists("getFileIcon")) {
	function getFileIcon($file) {
		if($file==null || strlen($file)<=0) return "";

		$ext=explode(".",$file);
		$ext=strtolower(end($ext));

		if(strlen($ext)<=0) return "fa-file";

		switch(strtolower($ext)) {
			case "png":case "gif":case "jpg":case "jpeg":case "bmp":
				return "fa-file-image-o";
				break;
			case "mp3":case "ogg":case "wav":case "aiff":case "wma":
				return "fa-file-audio-o";
				break;
			case "mp4":case "mpeg":case "mpg":case "avi":case "mov":case "wmv":
				return "fa-file-video-o";
				break;
			case "doc":case "txt":case "rdf":case "odt":
				return "fa-file-word-o";
				break;
			case "xls":case "ods":
				return "fa-file-excel-o";
				break;
			case "zip":case "tar":case "bz":case "bz2":case "gz":case "rar":case "zip":
				return "fa-file-zip-o";
				break;
			case "pdf":
				return "fa-file-pdf-o";
				break;
			case "php":case "html":case "js":case "css":case "java":case "py":case "c":case "cpp":case "sql":
				return "fa-file-code-o";
				break;
			default:
				return "fa-file";
		}
	}
}
?>
