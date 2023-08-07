<?php
if(!defined('ROOT')) exit('No direct script access allowed');

loadModuleLib("forms", "api");

if($reportConfig["source"]['type']=="sql") {
	if(isset($reportConfig['sidebar']['source'])) {
		foreach($reportConfig['sidebar']['source'] as $k=>$b) {
			if(isset($b['filter'])) {
				$reportConfig['datagrid'][$k] = ["filter"=> $b['filter'], "noshow"=>true];
			}
		}

		$reportKey=$reportConfig['reportkey'];
		$_SESSION['REPORT'][$reportKey]=$reportConfig;
	}
	
	//printArray($reportConfig['sidebar']['no_records_msg']);return;
	if(!isset($reportConfig['sidebar']['no_records'])) $reportConfig['sidebar']['no_records'] = "No Records";
	?>
<div class='sidebar-filters report-sidebar'>
	<?php
		$dbKey = "app";
		foreach($reportConfig['sidebar']['source'] as $key=>$conf) {
			$conf['fieldkey'] = $key;
			if(!isset($conf['columns']) && isset($conf['cols'])) $conf['columns'] = $conf['cols'];
			if(!isset($conf['groupBy']) && isset($conf['groupby'])) $conf['groupBy'] = $conf['groupby'];
			if(!isset($conf['no-option'])) $conf['no-option'] = "--";

			if(!isset($conf['class'])) $conf['class'] = "reportFilters";
			else $conf['class'] = " reportFilters";

			echo "<div class='filter-field'>";
			if(isset($conf['title'])) echo "<label>{$conf['title']}</label>";
			echo getFormField($conf, [], $dbKey);
			echo "</div>";
		}
	?>
</div>
<script>
var rptTX1 = null;
$(function() {
	$(".report-sidebar-container .sidebar-filters").delegate("input", "onblur", function(e) {
			var v1 = $(this).val();
			rpt.reloadDataGrid();
		});
	$(".report-sidebar-container .sidebar-filters").delegate("select", "change", function(e) {
			var v1 = $(this).val();
			rpt.reloadDataGrid();
		});
});
</script>
	<?php
} else {
	echo "<p class='text-center'><br><br><br>Source type not supported</p>";
}
?>
