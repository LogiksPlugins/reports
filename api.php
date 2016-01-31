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

		$reportKey=$reportConfig['reportkey'];
		$_SESSION['REPORT'][$reportKey]=$reportConfig;
		
		$templateArr=[
				$reportConfig['template'],
				__DIR__."/templates/{$reportConfig['template']}.php"
			];
		foreach ($templateArr as $f) {
			if(file_exists($f) && is_file($f)) {
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
				if($value===true || $value1=="true" || $value1=="on") {
					return "<td class='{$clz} {$key} {$type}' data-key='$key'><input type='checkbox' disabled checked=true /></td>";
				} else {
					return "<td class='{$clz} {$key} {$type}' data-key='$key'><input type='checkbox' disabled /></td>";
				}
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
			case 'select':
				if(!isset($filterConfig['options'])) $filterConfig['options']=[];

				$html="<select class='filterBarField autorefreshReport filterSelect' name='$key'>";
				$html.="<option value=''>{$noFilter}</option>";
				foreach ($filterConfig['options'] as $key => $value) {
					if(is_array($value)) {
						$cx=[];
						if(isset($value['label'])) {
							$vx=$value['label'];
							unset($value['label']);
							foreach ($value as $key => $value) {
								$cx[]="$key='$value";
							}
						} else $vx="";

						$html.="<option value='$key' ".implode(" ", $cx).">"._ling($vx)."</option>";
					} else {
						$vx=$value;
						$html.="<option value='$key'>"._ling($vx)."</option>";
					}
				}
				$html.="</select>";

				return $html;
				break;

			case 'selectAJAX':
				$html="<select class='filterBarField autorefreshReport filterSelect ajaxSelector' name='$key'>";
				$html.="<option value=''>Loading ...</option>";
				$html.="</select>";

				return $html;
				break;

			case "createDataSelector":
				if(!isset($filterConfig['orderBy'])) $filterConfig['orderBy']=null;

				$html="<select class='filterBarField autorefreshReport filterSelect' name='$key'>";
				$html.="<option value=''>{$noFilter}</option>";
				$html.=createDataSelector($filterConfig['groupid'],$filterConfig['orderBy'],$dbKey);
				$html.="</select>";

				return $html;
				break;

			case "createDataSelectorFromUniques":
				if(!isset($filterConfig['col2'])) $filterConfig['col2']=$filterConfig['col1'];
				if(!isset($filterConfig['where'])) $filterConfig['where']=null;
				if(!isset($filterConfig['orderBy'])) $filterConfig['orderBy']=null;

				$html="<select class='filterBarField autorefreshReport filterSelect' name='$key'>";
				$html.="<option value=''>{$noFilter}</option>";
				$html.=createDataSelectorFromUniques($filterConfig['table'],$filterConfig['col1'],$filterConfig['col2'],$filterConfig['where'],$filterConfig['orderBy'],$dbKey);
				$html.="</select>";

				return $html;
				break;

			case "createDataSelectorFromTable":
				if(!isset($filterConfig['columns'])) $filterConfig['columns']=$filterConfig['col1'];
				if(!isset($filterConfig['where'])) $filterConfig['where']=null;
				if(!isset($filterConfig['groupBy'])) $filterConfig['groupBy']=null;
				if(!isset($filterConfig['orderBy'])) $filterConfig['orderBy']=null;

				$html="<select class='filterBarField autorefreshReport filterSelect' name='$key'>";
				$html.="<option value=''>{$noFilter}</option>";
				$html.=createDataSelectorFromTable($filterConfig['table'],$filterConfig['columns'], $filterConfig['where'],$filterConfig['groupBy'],$filterConfig['orderBy'],$dbKey);
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
