<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!isset($reportConfig) || !isset($reportConfig['smartfilter'])) {
    return;
}
$smartfilterConfig = $reportConfig['smartfilter'];

$fieldID = array_keys($reportConfig['smartfilter']['source'])[0];
?>
<div class="filterMain">
    <div class="smartfilterBlock">
    	<input type='hidden' class='reportFilters filterfield' name='<?=$fieldID?>' />
        <ul>
        </ul>
    </div>
</div>
<script>
var rptTY2 = null;
$(function() {
	$(".smartfilterBlock").delegate(".filter-item", "click", function(e) {
			$(".smartfilterBlock .filter-item.active").removeClass("active");
			$(this).addClass("active");
			$(".smartfilterBlock input.reportFilters").val($(this).data("value"));
			rpt.reloadDataGrid();
		});

	rptTY2 = setInterval(function() {
	    if(typeof rpt == "object") {
			rpt.addListener(function(gridID) {
			    loadSmartFilters(gridID);
			});
			loadSmartFilters(rpt.gridID);
			clearInterval(rptTY2);
		}
	}, 500);
});
function loadSmartFilters(gridID) {
	oldValue = $(".smartfilterBlock .filter-item.active").data("value");

	$(".smartfilterBlock ul").html("<div class='ajaxloading ajaxloading5'></div>");
	lx=_service("reports","smartfilter","html")+"&gridid="+gridID;

	$(".smartfilterBlock ul").load(lx, function() {
		if($(".smartfilterBlock .filter-item[data-value='"+oldValue+"']").length>0) {
			$(".smartfilterBlock .filter-item[data-value='"+oldValue+"']").addClass("active");
		}
    })
}
</script>