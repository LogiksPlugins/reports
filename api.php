<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!isset($_SESSION["SESS_USER_ID"])) $_SESSION["SESS_USER_ID"]='guest';
if(!isset($_SESSION["SESS_USER_NAME"])) $_SESSION["SESS_USER_ID"]='Guest';
if(!isset($_SESSION["SESS_PRIVILEGE_ID"])) $_SESSION["SESS_PRIVILEGE_ID"]='-99';
if(!isset($_SESSION["SESS_PRIVILEGE_NAME"])) $_SESSION["SESS_PRIVILEGE_NAME"]='*';

$webPath=getWebPath(__FILE__);
$rootPath=getRootPath(__FILE__);

_js(array("jquery.mailform"));
_css(array("colors","styletags"));

echo "<link href='".$webPath."css/reports.css' rel='stylesheet' type='text/css' media='all' /> ";
echo "<script src='".$webPath."js/reports.js' type='text/javascript' language='javascript'></script>";

include_once "buttons.php";
loadHelpers("sqlsrc");

function loadReportFromDB($rptID, $frmTable, $engine=null) {
	$frmData=array();
	
	$sql="SELECT * FROM $frmTable where id='$rptID'";//$frmID
	
	$dbLink=getAppsDBLink();
	$result=$dbLink->executeQuery($sql);
	if($dbLink->recordCount($result)>0) {
		$frmData=$dbLink->fetchData($result);
		if($frmData['blocked']=='true') {
			trigger_ForbiddenError("Sorry, Required Report Is Forbidden.");
			return;
		}
		
		if($_SESSION["SESS_PRIVILEGE_ID"]>2 && $frmData["privilege"]!="*") {
			if(strlen($frmData["privilege"])>0) {
				$priArr=explode(",",$frmData["privilege"]);
				if(!in_array($_SESSION["SESS_PRIVILEGE_NAME"],$priArr)) {
					trigger_ForbiddenError("Sorry, Required Form Is Forbidden.");
					return;
				}
			} else {
				trigger_ForbiddenError("Sorry, Required Form Is Forbidden.");
				return;
			}
		}
		
		$frmData['dataSource']="services/?scmd=datagrid&site=".SITENAME."&action=load&sqlsrc=dbtable&sqltbl=$frmTable&sqlid=".$frmData["id"];
		printReport($frmData, $engine);
	} else {
		trigger_NotFound("Sorry, Required Report Not Found.");
	}
}
function loadReportFromFile($rptFile) {
	$rptData=array();
		
	$rptData['id']=md5(SITENAME._timeStamp().rand(1000,9999999));
	$rptData['title']="";
	$rptData['header']="";
	$rptData['footer']="";
	$rptData['engine']="grid";
	$rptData['style']="";
	$rptData['script']="";
	$rptData['toolbtns']="*";
	$rptData['actionlink']="";
	$rptData['datatable_table']="";
	$rptData['datatable_cols']="";	
	$rptData['datatable_colnames']="";
	$rptData['datatable_hiddenCols']="";
	$rptData['datatable_where']="";	
	$rptData['datatable_params']="";
	
	
	$data=file_get_contents($rptFile);
	$data=explode("\n",$data);
	foreach($data as $d) {
		if(strlen($d)>1 && strpos(" ".$d,"#")!=1 && strpos($d,"=")>1) {
			$d=explode("=",$d);
			if(strlen($d[0])>0) {
				$er=$d[0];
				unset($d[0]);
				$rptData[$er]=processQ(implode("=",$d));
			}
		}
	}
	
	$_SESSION["RPT_".$rptData['id']]=array();
	$_SESSION["RPT_".$rptData['id']]["table"]=$rptData["datatable_table"];
	$_SESSION["RPT_".$rptData['id']]["cols"]=$rptData["datatable_cols"];
	$_SESSION["RPT_".$rptData['id']]["where"]=$rptData["datatable_where"];
	
	$rptData['dataSource']="services/?scmd=datagrid&site=".SITENAME."&action=load&sqlsrc=session&sqlid=RPT_".$rptData["id"];
	printReport($rptData);
}
function printReport($rptData, $engine=null) {
	$cache=CacheManager::singleton();
	
	if($rptData==null) return;
	if(strlen($rptData['datatable_cols'])==0) return;
	
	if(!isset($rptData['datatable_hiddenCols'])) {
		$rptData['datatable_hiddenCols']="";
	}
	
	$rptData["toolbtns"]="";
	if($_SESSION["SESS_PRIVILEGE_ID"]>2) {
		if(isset($rptData["privilege_model"])) {
			if(strlen($rptData["privilege_model"])<=0) {
				$rptData["toolbtns"]="";
			} else {
				$pModel=(array)json_decode($rptData["privilege_model"]);
				if(isset($pModel[$_SESSION["SESS_PRIVILEGE_NAME"]])) {
					$rptData["toolbtns"]=$pModel[$_SESSION["SESS_PRIVILEGE_NAME"]];
				} else {
					$rptData["toolbtns"]="";
				}
			}
		}
	} else {
		$rptData["toolbtns"]="*";
	}
	$tBtns=explode(",",$rptData["toolbtns"]);
	if($rptData["toolbtns"]!="*") {
		if(!in_array("actionlink",$tBtns)) 
			$rptData["actionlink"]="";
	}
	
	if($engine==null) $engine=$rptData["engine"];	
	$divId="report_".$rptData['id']."_".time();
	
	$rptData["divid"]=$divId;
	$rptData['dataType']="json";
	
	$GLOBALS['RPTDATA']=$rptData;
		
	$fL=dirname(__FILE__)."/engines/".$engine.".php";
	if(file_exists($fL)) {
		$fpath=$fL;
		//$fpath=$cache->getCacheLink($fL,"report_".$rptData['id']);
		//$fpath=$cache->getCacheLink($fpath,"form_".$rptData['id']."_cpy");
		//$fpath=$cache->getCacheLink($rptData['frmdata'],$divId);
	} else {
		$fpath="";
		echo "<h3 style='margin:auto;'>Sorry, Required Layout Is Not Yet Installed/Supported On This System.</h3>";
	}
?>
<!--overflow:hidden;width:100%;height:100%;padding:0px;margin:0px;-->
<div id='<?=$divId?>' class='LGKSRPTTABLE' style=''>
<?php
	if(strlen($fpath)>0) include $fpath;
	else echo "<h3 align=center>Report Not Found</h3>";
?>
<?php
	if(isset($rptData["footer"]) && strlen($rptData["footer"])>0) {
		$f489=$rptData["footer"];
		echo "<div class='reportfooter noscreen'>$f489</div>";
	}
?>
</div>
<script language='javascript'>
dataSource="<?=SiteLocation.$rptData["dataSource"]."&datatype=".$GLOBALS['RPTDATA']['dataType']?>";
actionLink="<?=SiteLocation.$rptData["actionlink"]?>&site=<?=SITENAME?>";
$(function() {
	loadData();
	if(actionLink.length<=0) {
		$("#<?=$divId?> #hd1 .btn_actionlink").detach();
	}
	if($("#<?=$divId?> #hd1 .btn_grouping").length>0) {
		$("#<?=$divId?> #hd1").delegate("select.btn_grouping","change",function() {
				createGridTree($(this).val());
			});
	}
});
<?=$rptData["script"]?>
</script>
<style>
<?=$rptData["style"]?>
</style>
<?php 
} 

function processQ($q) {
	$q=str_replace("::","=",$q);	
	$q=processSQLQuery($q);	
	return $q;
}
?>
