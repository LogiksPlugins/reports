<?php
if(!defined('ROOT')) exit('No direct script access allowed');

include_once __DIR__."/api.php";

$slug=_slug("?/src");

if(isset($slug['src']) && !isset($_REQUEST['src'])) {
	$_REQUEST['src']=$slug['src'];
}

if(isset($_REQUEST['src']) && strlen($_REQUEST['src'])>0) {
	$report=findReport($_REQUEST['src']);

	if($report) {
		echo _css("reports");
		echo "<div class='reportholder' style='width:100%;height:100%;'>";
		//include_once "sample.php";
		printReport($report,"core");
		echo "</div>";
		echo _js(["FileSaver","html2canvas","reports"]);
	} else {
		trigger_logikserror("Sorry, report '{$_REQUEST['src']}' not found.",E_USER_NOTICE,404);
	}
} else {
	trigger_logikserror("Sorry, report not defined.");
}
?>
