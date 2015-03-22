<?php
if(!defined('ROOT')) exit('No direct script access allowed');

function printGridButtons($btns,$divid) {
	$uid=$_SESSION["SESS_USER_ID"];
	$_form_buttons=array();

	$_form_buttons['columns']="<div class='btn_column' style='width:25px;height:25px;float:right;' onclick='colChange();' title='Change Viewable Columns'></div>";
	$_form_buttons['viewrecord']="<div class='btn_view' style='width:25px;height:25px;float:right;' onclick='viewRecord();' title='View Record'></div>";

	$_form_buttons['filterbar']="<div class='btn_filters' style='width:25px;height:25px;float:right;' onclick='toggleFilterBar();' title='Toggle Filter Bar'></div>";
	$_form_buttons['search']="<div class='btn_search' style='width:25px;height:25px;float:right;' onclick='searchDataTable();' title='Search Data'></div>";

	$_form_buttons['printview']="<div class='btn_print' style='width:25px;height:25px;float:right;' onclick='printGrid();' title='Print Table'></div>";
	$_form_buttons['exportview']="<div class='btn_xls' style='width:25px;height:25px;float:right;' onclick='exportToExcel();' title='Export In Excel'></div>";
	$_form_buttons['exportview'].="<div class='btn_html' style='width:25px;height:25px;float:right;' onclick='exportToHTML();' title='Export In HTML'></div>";
	$_form_buttons['mailview']="<div class='btn_mail' style='width:25px;height:25px;float:right;' onclick='mailGrid();' title='Mail Table'></div>";

	$_form_buttons['grouping']="<select class='btn_grouping ui-widget-header' style='width:200px;font-weight:normal;' title='Group Columns By'><option value=''>None</option></select>";
	$_form_buttons['actionlink']="<div class='btn_actionlink' style='width:25px;height:25px;float:right;' onclick='gotoActionLink();' title='Open Connected Link'></div>";

	if($btns=="*" || $_SESSION['SESS_PRIVILEGE_ID']<=3) {
		$btns="";
		$btns=implode(",",array_keys($_form_buttons));
	}
	$arr=explode(",",$btns);
	foreach($arr as $a=>$b) {
		if(isset($_form_buttons[trim($b)])) echo $_form_buttons[trim($b)];
	}
	echo "<div class='btn_reload' style='width:25px;height:25px;float:right;' onclick='reloadDataTable();' title='Reload Table'></div>";
}
?>
