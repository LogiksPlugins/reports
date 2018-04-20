<?php
if(!defined('ROOT')) exit('No direct script access allowed');

include_once __DIR__."/api.php";

$slug=_slug("?/src/tmpl");

$template="grid";

if(isset($slug['src']) && !isset($_REQUEST['src'])) {
	$_REQUEST['src']=$slug['src'];
}

if(isset($_REQUEST['src']) && strlen($_REQUEST['src'])>0) {
	$report=findReport($_REQUEST['src']);

	if($report) {
		$report['uiswitcher']=true;
		
		if(isset($slug['tmpl']) && strlen($slug['tmpl'])>0) {
			$template=$slug['tmpl'];
		} elseif(isset($_REQUEST['tmpl']) && strlen($_REQUEST['tmpl'])>0) {
			$template=$_REQUEST['tmpl'];
		} elseif(isset($_COOKIE['RPTVIEW-'.$report['reportgkey']])) {
			$template=$_COOKIE['RPTVIEW-'.$report['reportgkey']];
		}
		$report['template']=$template;
		
// 		$report['template']="kanban";
		
		echo _css("reports");
		echo "<div class='reportholder' style='width:100%;height:100%;'>";
		//include_once "sample.php";
		printReport($report,$report['dbkey']);
		echo "</div>";
		echo _js(["filesaver","html2canvas",'jquery.cookie',"reports"]);
	} else {
// 		trigger_logikserror("Sorry, report '{$_REQUEST['src']}' not found.",E_USER_NOTICE,404);
		echo "<h1 class='errormsg'>Sorry, report '{$_REQUEST['src']}' not found.</h1>";
	}
} else {
	//trigger_logikserror("Sorry, report not defined.");
	echo "<h1 class='errormsg'>Sorry, report not defined.</h1>";
}
?>
