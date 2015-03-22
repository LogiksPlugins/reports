<?php
if(!defined('ROOT')) exit('No direct script access allowed');
//$_REQUEST['ui']="table";
include_once "api.php";
?>
<div class='reportholder' style='width:100%;height:100%;'>
<?php
if(isset($_REQUEST['rid'])) {
	if(isset($_REQUEST['ui'])) {
		loadReportFromDB($_REQUEST['rid'],_dbtable("reports"),$_REQUEST['ui']);
	} else {
		loadReportFromDB($_REQUEST['rid'],_dbtable("reports"));
	}
} else {
	echo "<h3 align=center>Report Not Found</h3>";
}
?>
</div>