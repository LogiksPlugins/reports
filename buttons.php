<?php
if(!defined('ROOT')) exit('No direct script access allowed');

function printGridButtons($btns,$divid) {
	$uid=$_SESSION["SESS_USER_ID"];
	$_form_buttons=array();

	$_form_buttons['columns']="<div class='btn btn_column' style='width:25px;height:25px;float:right;' onclick='colChange(this);' title='Change Viewable Columns'></div>";
	$_form_buttons['viewrecord']="<div class='btn btn_view' style='width:25px;height:25px;float:right;' onclick='viewRecord(this);' title='View Record'></div>";

	$_form_buttons['filterbar']="<div class='btn btn_filters' style='width:25px;height:25px;float:right;' onclick='toggleFilterBar(this);' title='Toggle Filter Bar'></div>";
	$_form_buttons['search']="<div class='btn btn_search' style='width:25px;height:25px;float:right;' onclick='searchDataTable(this);' title='Search Data'></div>";

	$_form_buttons['printview']="<div class='btn btn_print' style='width:25px;height:25px;float:right;' onclick='printGrid(this);' title='Print Table'></div>";
	$_form_buttons['exportview']="<div class='btn btn_xls' style='width:25px;height:25px;float:right;' onclick='exportToExcel(this);' title='Export In Excel'></div>";
	$_form_buttons['exportview'].="<div class='btn btn_html' style='width:25px;height:25px;float:right;' onclick='exportToHTML(this);' title='Export In HTML'></div>";
	$_form_buttons['mailview']="<div class='btn btn_mail' style='width:25px;height:25px;float:right;' onclick='mailGrid(this);' title='Mail Table'></div>";

	$_form_buttons['grouping']="<select onchange='createGridTree(this,this.value);' class='btn_grouping ui-widget-header' style='width:150px;font-weight:normal;' title='Group Columns By'><option value=''>None</option></select>";
	
	if($btns=="*" || $_SESSION['SESS_PRIVILEGE_ID']<=3) {
		$btns="";
		$btns=implode(",",array_keys($_form_buttons));
	}
	$arr=explode(",",$btns);
	foreach($arr as $a=>$b) {
		if(isset($_form_buttons[trim($b)])) echo $_form_buttons[trim($b)];
	}
	echo "<div class='btn btn_reload' style='width:25px;height:25px;float:right;' onclick='reloadDataTable(this);' title='Reload Table'></div>";
}
?>
