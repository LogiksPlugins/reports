<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!function_exists("findReport")) {

	function findReport($file) {
		$fsArr=[
				$file,
				APPROOT.APPS_MISC_FOLDER."reports/{$file}.json",
			];
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

		$reportConfig=json_decode(file_get_contents($file),true);

		$reportConfig['sourcefile']=$file;
		$reportConfig['reportkey']=md5(session_id().$file);

		return $reportConfig;
	}

	function printReport($reportConfig,$dbKey="app") {
		//var_dump($reportConfig);
		if(!is_array($reportConfig)) $reportConfig=findReport($reportConfig);
		
		if(!isset($reportConfig['reportkey'])) $reportConfig['reportkey']=md5(time());

		$reportConfig['dbkey']=$dbKey;

		if(!isset($reportConfig['template'])) {
			$reportConfig['template']="grid";
		}
		
		if(!isset($reportConfig['source']['cols'])) {
			$reportConfig['source']['cols']="*";//array_keys($reportConfig['datagrid'])
		}
		if(!isset($reportConfig['source']['limit'])) {
			$reportConfig['source']['limit']=10;
		}
		if(!isset($reportConfig['showExtraColumn'])) {
			$reportConfig['showExtraColumn']=false;
		}
		
		$reportConfig['searchCols']=[];
		foreach ($reportConfig['datagrid'] as $key => $col) {
			if(isset($col['searchable']) && $col['searchable']) {
				$reportConfig['searchCols'][]=$key;
			}
		}

		if(isset($reportConfig['toolbar']) && $reportConfig['toolbar']===false) {
			$reportConfig['toolbar']['search']=false;
			$reportConfig['toolbar']['print']=false;
			$reportConfig['toolbar']['email']=false;
			$reportConfig['toolbar']['export']=false;
		}

		if(!isset($reportConfig['export'])) $reportConfig['export']=[];

		if(!isset($reportConfig['export']['pdf'])) {
			$reportConfig['export']['pdf']=APPROOT.APPS_TEMPLATE_FOLDER.'print-report.tpl';
		}

		if(count($reportConfig['searchCols'])>0) {
		  $reportConfig['toolbar']['search']=true;
		} else {
		  $reportConfig['toolbar']['search']=false;
		}

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
		switch ($type) {
			case 'date':
				$value=current(explode(" ", $value));
				return "<td class='{$clz} {$key} {$type}' data-key='$key'>"._pDate($value)."</td>";
				break;

			case 'time':
				$value=explode(" ", $value);
				$value=end($value);
				return "<td class='{$clz} {$key} {$type}' data-key='$key'>"._time($value)."</td>";
				break;
			
			case 'datetime':
				return "<td class='{$clz} {$key} {$type}' data-key='$key'>"._pDate($value)."</td>";
				break;

			case 'currency':
				return "<td class='{$clz} {$key} {$type}' data-key='$key'>".number_format($value,2)."</td>";
				break;

			case 'checkbox':
				$value1=strtolower($value);
				$html="<td class='{$clz} {$key} checkboxes' data-key='$key'>";
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
				return "<td class='{$clz} {$key} {$type}' data-key='$key'>{$value}</td>";
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
?>
