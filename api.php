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

	function printReport($reportConfig,$dbKey="app",$params=[]) {
// 		var_dump($reportConfig);exit();
		if(!is_array($reportConfig)) $reportConfig=findReport($reportConfig);

		if(!is_array($reportConfig) || count($reportConfig)<=2) {
			trigger_logikserror("Corrupt report defination");
			return false;
		}
		
		if($params==null) $params=[];
		$reportConfig=array_merge($reportConfig,$params);
		
		if(!isset($reportConfig['reportkey'])) $reportConfig['reportkey']=md5(session_id().time());

		$reportConfig['dbkey']=$dbKey;

		if(!isset($reportConfig['template']) || strlen($reportConfig['template'])<=0) {
			$reportConfig['template']="grid";
		}
		setCookie('RPTVIEW-'.$reportConfig['reportgkey'],$reportConfig['template'],0,"/");
		
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
		}

		if(!isset($reportConfig['toolbar'])) {
			$reportConfig['toolbar']=[];
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
		//printArray($reportConfig);return;
		
		$reportKey=$reportConfig['reportkey'];
		$_SESSION['REPORT'][$reportKey]=$reportConfig;
		
		$templateArr=[
				$reportConfig['template'],
				__DIR__."/templates/{$reportConfig['template']}.php"
			];
		foreach ($templateArr as $f) {
			if(file_exists($f) && is_file($f)) {
				if(isset($reportConfig['preload'])) {
					if(isset($reportConfig['preload']['modules'])) {
						loadModules($reportConfig['preload']['modules']);
					}
					if(isset($reportConfig['preload']['api'])) {
						foreach ($reportConfig['preload']['api'] as $apiModule) {
							loadModuleLib($apiModule,'api');
						}
					}
					if(isset($reportConfig['preload']['helpers'])) {
						loadHelpers($reportConfig['preload']['helpers']);
					}
					if(isset($reportConfig['preload']['method'])) {
						if(!is_array($reportConfig['preload']['method'])) $reportConfig['preload']['method']=explode(",",$reportConfig['preload']['method']);
						foreach($reportConfig['preload']['method'] as $m) call_user_func($m,$reportConfig);
					}
					if(isset($reportConfig['preload']['file'])) {
						if(!is_array($reportConfig['preload']['file'])) $reportConfig['preload']['file']=explode(",",$reportConfig['preload']['file']);
						foreach($reportConfig['preload']['file'] as $m) {
							if(file_exists($m)) include $m;
							elseif(file_exists(APPROOT.$m)) include APPROOT.$m;
						}
					}
				}
				
				_css('reports');
				include $f;
				_js('reports');
				return true;
			}
		}
		trigger_logikserror("Report Template Not Found",null,404);
	}


	function formatReportColumn($key,$value,$type="text",$hidden=false) {
		$clz="tableColumn";
		if($hidden) $clz.=" hidden";
		$keyS=str_replace(".","_",$key);
		switch (strtolower($type)) {
			case 'date':
				$value=current(explode(" ", $value));
				return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'>"._pDate($value)."</td>";
				break;

			case 'time':
				$value=explode(" ", $value);
				$value=end($value);
				return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'>"._time($value)."</td>";
				break;
			
			case 'datetime':
				return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'>"._pDate($value)."</td>";
				break;

			case 'currency':
				return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'>".number_format($value,2)."</td>";
				break;
			
			case 'num':case 'number':
				return "<td class='{$clz} {$keyS} {$type} text-center' data-key='$key' data-value='{$value}'>".$value."</td>";
				break;
				
			case 'url':
				return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'><a class='fa fa-globe' href='{$value}' target=_blank> LINK</a></td>";
				break;
			case 'email':
				return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'><a class='fa fa-email' href='email:{$value}'> EMAIL</a></td>";
				break;
			case 'tel':case 'mob':case 'phone':case 'mobile':
				return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'><a class='fa fa-map-marker' href='tel:{$value}'> CALL</a></td>";
				break;
			
			case 'geoloc':case 'geolocation':case 'geoaddress':
				return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'><a class='fa fa-map-marker' href='https://www.google.co.in/maps/place/{$value}' target=_blank> MAP</a></td>";
				break;
			
			case 'color':
				return "<td class='{$clz} {$keyS} {$type} text-center' data-key='$key' data-value='{$value}'><span style='background:{$value};'></span></td>";
				break;
			
			case "avatar":
				if($value==null || strlen($value)<=0) $value=loadMedia("images/user.png");
				$fname=basename($value);
				return "<td class='{$clz} {$keyS} {$type} imagebox text-center' data-key='$key' data-value='{$value}'><div class='image-avatar'><img src='{$value}' class='img-responsive' alt='{$fname}' /></div></td>";
				break;
			case "photo":case "picture":case "media":
				if($value==null || strlen($value)<=0) $value=loadMedia("images/noimg.png");
				elseif(substr($value,0,7)!="http://" && substr($value,0,7)!="https://" && substr($value,0,7)!="ftp://") {
					$value=searchMedia($value);
					$value=$value['url'];
				}
				$fname=basename($value);
				return "<td class='{$clz} {$keyS} {$type} imagebox text-center' data-key='$key' data-value='{$value}'><div class='image-inline'><img src='{$value}' class='img-responsive' alt='{$fname}' /></div></td>";
				break;
			
			case "file":case "attachment":
				if($value==null || strlen($value)<=0) {
					return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'>No File</td>";
				} else {
					return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'><a class='fa fa-paperclip' href='{$value}' target=_blank> FILE</a></td>";
				}
				break;
			case "mediafile":
				if($value==null || strlen($value)<=0) {
					return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'>No File</td>";
				} else {
					$valueX=searchMedia($value);
					if($valueX) {
						return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'><a class='fa fa-paperclip' href='{$valueX['url']}' target=_blank> FILE</a></td>";
					} else {
						return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}' title='{$value}'>Not Found</td>";
					}
				}
				break;
			
			case "embed":
				if($value==null || strlen($value)<=0) {
					return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'></td>";
				} else {
					return "<td class='{$clz} {$keyS} {$type} embed' data-key='$key' data-value='###'><i class='fa fa-arrows-alt'></i> OPEN <div class='contentBox hidden'>{$value}</div></td>";
				}
				break;
			case "video":case "videoembed":
				if($value==null || strlen($value)<=0) {
					return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'></td>";
				} else {
					return "<td class='{$clz} {$keyS} {$type} embed' data-key='$key' data-value='###'><i class='fa fa-youtube-play'></i> OPEN <div class='contentBox hidden'>{$value}</div></td>";
				}
				break;
			
			case "iframe":
				if($value==null || strlen($value)<=0) {
					return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'></td>";
				} else {
					$value="<iframe width='560' height='315' src='{$value}' frameborder='0' allowfullscreen></iframe>";
					return "<td class='{$clz} {$keyS} {$type} embed' data-key='$key' data-value='###'><i class='fa fa-arrows-alt'></i> OPEN <div class='contentBox hidden'>{$value}</div></td>";
				}
				break;
				
			case 'content':
				if($value==null || strlen($value)<=0) {
					return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'>No Content</td>";
				} else {
					$value=str_replace("\\r\\n","<br>",$value);
					$value=str_replace("\\'s","'s",$value);
					if(strlen($value)>40) {
						$abstract=substr($value,0,35)." ...";
						return "<td class='{$clz} {$keyS} {$type} moreContent' data-key='$key' data-value='{$value}'>{$abstract}<div class='contentBox hidden'>{$value}</div></td>";
					} else {
						return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'><pre>{$value}</pre></td>";
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
				return "<td class='{$clz} {$keyS} {$type} moreContent' data-key='$key' data-value=''>VIEW<div class='contentBox hidden'>{$html}</div></td>";
				break;
				
			case 'checkbox':
				$value1=strtolower($value);
				$html="<td class='{$clz} {$keyS} checkboxes' data-key='$key' data-value='{$value}'>";
				if($value===true || $value1=="true" || $value1=="on") {
					$html.="<input class='noprint' type='checkbox' disabled checked=true />";
				} else {
					$html.="<input class='noprint' type='checkbox' disabled />";
				}
				$html.="<span class='onlyprint'>{$value1}</span>";
				$html.="</td>";
				return $html;
				break;

			default:
				if(is_array($value)) {
					return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='--'>".implode(", ",$value)."</td>";
				} elseif(strlen($value)>100) {
					return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='--'><pre>{$value}</pre></td>";
				} elseif(strlen($value)>50) {
					return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='--'>{$value}</td>";
				} else {
					return "<td class='{$clz} {$keyS} {$type}' data-key='$key' data-value='{$value}'>{$value}</td>";
				}
				break;
		}
	}
	function formatReportFilter($key,$filterConfig=array(),$dbKey="app") {
		if(!isset($filterConfig['type'])) $filterConfig['type']="text";

		if(!isset($filterConfig['nofilter'])) $filterConfig['nofilter']="No $key";

		$noFilter=_ling($filterConfig['nofilter']);

		switch ($filterConfig['type']) {
			case 'dataMethod': case 'dataSelector': case 'dataSelectorFromUniques': case 'dataSelectorFromTable':
			case 'select': case 'selectAJAX': 
				$html="";

				$html="<select class='filterBarField autorefreshReport filterSelect' name='$key'>";
				$html.="<option value=''>{$noFilter}</option>";
				
				$html.=generateSelectOptions($filterConfig,"",$dbKey);

				$html.="</select>";

				return $html;
				break;

			case 'text':
				return "<input type='text' class='filterBarField autorefreshReport filterText' name='$key' />";
				break;

			case 'date':
				return "<input type='date' class='filterBarField autorefreshReport filterDate' name='$key' />";
				break;

			case 'checkbox':
				return "<input type='checkbox' class='filterBarField autorefreshReport filterCheckbox' name='$key' />";
				break;

			default:
				
				break;
		}
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
