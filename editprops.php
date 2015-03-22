<?php
if(!defined('ROOT')) exit('No direct script access allowed');

function getReportEngines() {
	$arr=array();
	$fs=scandir(dirname(__FILE__)."/engines/");
	unset($fs[0]);unset($fs[1]);
	foreach($fs as $a) {
		$a=str_replace(".php","",$a);
		$arr[$a]=ucwords($a);
	}
	return $arr;
}
function getToolButtons() {
	$arr=array(
			"columns"=>"Change Viewable Columns",
			"search"=>"Search Data",
			"printview"=>"Print DataTable",
			"exportview"=>"Export DataTable",
			"mailview"=>"EMail DataTable",	
			"viewrecord"=>"View Individual Record",
			"actionlink"=>"Follow Linked Data",
			"filterbar"=>"Allow FilterBar",
			"grouping"=>"Allow Grouping",
		);
	return $arr;
}
?>
