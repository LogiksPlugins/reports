<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if($reportConfig["source"]['type']=="sql") {
	$fieldID = array_keys($reportConfig['sidebar']['source'])[0];
	$field = $reportConfig['sidebar']['source'][$fieldID];

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
				foreach ($dbDataFinal as $category => $recordSet) {
					$collapseID = md5($category.time());

					echo '<div class="panel panel-default">';
					if(strlen($category)>0) {
						echo '<div class="panel-heading" data-toggle="collapse" href="#'.$collapseID.'" role="button" aria-expanded="false" aria-controls="'.$collapseID.'">'.toTitle(_ling($category)).
							' <i class="fa fa-panel-status pull-right"></i></div>';
						echo '<div id="'.$collapseID.'" class="panel-body nopadding collapse">';
					} else {
						echo '<div id="'.$collapseID.'" class="panel-body nopadding">';
					}

					echo "<ul class='list-group'>";
					foreach ($recordSet as $record) {
						echo "<li class='list-group-item list-group-flush' data-value='{$record['value']}'>".toTitle(_ling($record['title']))."</li>";
					}
					echo "</ul>";
					echo '</div></div>';
				}
				echo "</div>";
			} else {
				echo "<ul class='list-group report-sidebar'>";
				echo "<input type='hidden' class='reportFilters' name='{$fieldID}' />";
				foreach ($dbData as $record) {
					echo "<li class='list-group-item list-group-flush' data-value='{$record['value']}'>".toTitle(_ling($record['title']))."</li>";
				}
				echo "</ul>";
			}
		} else {
			return;
		}
	} else {
		return;
	}
	
	?>
<script>
$(function() {
	$(".report-sidebar .list-group-item").click(function(e) {
		$(".report-sidebar .list-group-item.active").removeClass("active");
		$(this).addClass("active");
		$(".report-sidebar input.reportFilters").val($(this).data("value"));
		rpt.reloadDataGrid();
	});
});
</script>
	<?php
} else {
	echo "<p class='text-center'><br><br><br>Source type not supported</p>";
}
?>
