<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if($reportConfig["source"]['type']=="sql") {
	?>
<script>
var rptTX1 = null;
$(function() {
	$(".report-sidebar .list-group-item").click(function(e) {
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
	$(".report-sidebar-container").html("<div class='ajaxloading ajaxloading5'></div>");
	lx=_service("reports","sidebar","html")+"&gridid="+gridID;
	$(".report-sidebar-container").load(lx)
}
</script>
	<?php
} else {
	echo "<p class='text-center'><br><br><br>Source type not supported</p>";
}
?>
