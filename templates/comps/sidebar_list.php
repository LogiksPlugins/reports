<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if($reportConfig["source"]['type']=="sql") {
	//printArray($reportConfig['sidebar']['no_records_msg']);return;
	if(!isset($reportConfig['sidebar']['no_records'])) $reportConfig['sidebar']['no_records'] = "No Records";
	?>
<script>
var rptTX1 = null;
$(function() {
	$(".report-sidebar-container").delegate(".report-sidebar .list-group-item", "click", function(e) {
			$(".report-sidebar .list-group-item.active").removeClass("active");
			$(this).addClass("active");
			$(".report-sidebar input.reportFilters").val($(this).data("value"));
			rpt.reloadDataGrid();
		});

	rptTX1 = setInterval(function() {
	    if(typeof rpt == "object") {
			rpt.addListener(function(gridID) {
			    loadSidebar(gridID);
			});
			loadSidebar(rpt.gridID);
			clearInterval(rptTX1);
		}
	}, 500);
});
function loadSidebar(gridID) {
	oldValue = $(".report-sidebar-container .list-group-item.active").data("value");
	
	$(".report-sidebar-container").html("<div class='ajaxloading ajaxloading5'></div>");
	lx=_service("reports","sidebar","html")+"&gridid="+gridID;
	$(".report-sidebar-container").load(lx, function(txt) {
		if($(".report-sidebar-container .list-group-item[data-value='"+oldValue+"']").length>0) {
			$(".report-sidebar-container .list-group-item[data-value='"+oldValue+"']").addClass("active");
		}

		if($(".report-sidebar-container .list-group-item").length<=0) {
			$(".report-sidebar-container").html("<div class='list-group report-sidebar'><ul class='list-group'><h3 class='text-center'><?=(_ling($reportConfig['sidebar']['no_records']))?></h3></ul></div>");
		}

		if(typeof window['onLoadSidebar']=="function") {
			onLoadSidebar(gridID);
		}
    })
}
</script>
	<?php
} else {
	echo "<p class='text-center'><br><br><br>Source type not supported</p>";
}
?>
