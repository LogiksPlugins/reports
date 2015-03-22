<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!isset($_SESSION["SESS_USER_ID"])) $_SESSION["SESS_USER_ID"]='guest';
if(!isset($_SESSION["SESS_USER_NAME"])) $_SESSION["SESS_USER_ID"]='Guest';
if(!isset($_SESSION["SESS_PRIVILEGE_ID"])) $_SESSION["SESS_PRIVILEGE_ID"]='-99';
if(!isset($_SESSION["SESS_PRIVILEGE_NAME"])) $_SESSION["SESS_PRIVILEGE_NAME"]='*';

if(!function_exists("loadReportFromDB")) {
	$webPath=getWebPath(__FILE__);

	_js(array("jquery.mailform"));
	_css(array("colors","styletags"));

	echo "<link href='".$webPath."css/reports.css' rel='stylesheet' type='text/css' media='all' /> ";
	echo "<script src='".$webPath."js/reports.js' type='text/javascript' language='javascript'></script>";

	include_once "buttons.php";
	loadHelpers("sqlsrc");

	function loadReportFromDB($rptID, $frmTable, $uiEngine=null,$toolbtns=null,$params=null) {
		if($frmTable==null) $frmTable=_dbtable("reports");

		$rptData=array();

		$sql="SELECT * FROM $frmTable where id='$rptID'";

		$dbLink=getAppsDBLink();
		$result=$dbLink->executeQuery($sql);
		if($dbLink->recordCount($result)>0) {
			$frmData=$dbLink->fetchData($result);
			if($frmData['blocked']=='true') {
				printReportError("Sorry, Required Report Is Forbidden.");
				return false;
			}

			if($_SESSION["SESS_PRIVILEGE_ID"]>2 && $frmData["privilege"]!="*") {
				if(strlen($frmData["privilege"])>0) {
					$priArr=explode(",",$frmData["privilege"]);
					if(!in_array($_SESSION["SESS_PRIVILEGE_NAME"],$priArr)) {
						printReportError("Sorry, Required Form Is Forbidden.");
						return false;
					}
				} else {
					printReportError("Sorry, Required Form Is Forbidden.");
					return false;
				}
			}

			$q=array();
			foreach($_GET as $a=>$b) {
				if($a=="site" || $a=="toolbar" || $a=="page" || $a=="mod" || $a=="rid") continue;
				else $q[]="$a=".urlencode($b);
			}
			$q=implode("&",$q);
			if(strlen($q)>0) $q="&".$q;
			$frmData['dataSource']="services/?scmd=datagrid&site=".SITENAME."&action=load&src=reports&sqlsrc=dbtable&sqltbl=$frmTable&sqlid=".$frmData["id"]."{$q}";

			return printReport($frmData, $uiEngine, $toolbtns, $params);
		} else {
			printReportError("Sorry, Required Report Not Found.");
			return false;
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
		return printReport($rptData);
	}
	function printReport($rptData, $engine=null, $toolbtns=null,$params=null) {
		$cache=CacheManager::singleton();

		if($rptData==null) return;
		if(strlen($rptData['datatable_cols'])==0) return;
		$toolbtns="x";

		if($toolbtns==null) {
			if($_SESSION["SESS_PRIVILEGE_ID"]>2) {
				if(isset($rptData["privilege_model"])) {
					if(strlen($rptData["privilege_model"])<=3) {
						if(!isset($rptData["toolbtns"])) $rptData["toolbtns"]="*";
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
		} else {
			$rptData["toolbtns"]=$toolbtns;
		}
		if($params==null) {
			$rptData["params"]=getDefaultReportParams();
		} else {
			$rptData["params"]=$params;
		}

		if($engine==null) $engine=$rptData["engine"];
		$divId="report_".$rptData['id']."_".time();

		$rptData["divid"]=$divId;
		$rptData['dataType']="json";

		$GLOBALS['RPTDATA']=$rptData;

		$fL=dirname(__FILE__)."/engines/".$engine.".php";
		if(file_exists($fL)) {
			$fpath=$fL;
		} else {
			unset($GLOBALS['RPTDATA']);
			$fpath="";
			printReportError("Sorry, Required Layout Is Not Yet Installed/Supported On This System.");
			return false;
		}
	?>
	<!--overflow:hidden;width:100%;height:100%;padding:0px;margin:0px;-->
	<div id='<?=$divId?>' class='LGKSRPTTABLE' style='overflow: hidden;'>
	<?php
		if(strlen($fpath)>0) include $fpath;
		else echo "<h3 align=center>Report Not Found</h3>";

		if(isset($rptData["footer"]) && strlen($rptData["footer"])>0) {
			echo "<div class='reportfooter noscreen'>{$rptData["footer"]}</div>";
		}
	?>
	</div>
	<script language='javascript'>
		<?=$rptData["script"]?>
	</script>
	<style>
		<?=$rptData["style"]?>
	</style>
	<?php
		unset($GLOBALS['RPTDATA']);
		return true;
	}

	function processQ($q) {
		$q=str_replace("::","=",$q);
		$q=processSQLQuery($q);
		return $q;
	}
	function getDefaultReportParams() {
		$arr=array();

		$arr["notoolbar"]=false;
		if(isset($_REQUEST['toolbar']) && $_REQUEST['toolbar']=="false") $arr["notoolbar"]=true;
		$arr["visibleCols"]=getSiteSettings("Default Visible Columns",8,"Reports","int");
		$arr["idColWidth"]=getSiteSettings("ID Column Width",50,"Reports","int");

		return $arr;
	}
	function getActionsSelector($alinks) {
		$s="<option value=\"*\">Select Action</option>";

		if(is_array($alinks)) {
			foreach($alinks as $a=>$b) {
				$s.="<option value=\"$b\">$a</option>";
			}
		}

		return $s;
	}
	function printReportError($msg) {
		echo "<h3 style='margin:auto;'>$msg</h3>";
		//trigger_ForbiddenError("Sorry, Required Form Is Forbidden.");
		//dispErrMessage("Requested Site Not Defined.","404:Not Found",404);
		//echo "<div class='noFormFound ui-widget-content ui-corner-all'>
		//		<h1 class='ui-widget-header'>Form Was Not Found</h1>
		//		<div class='noFormIcon'></div>
		//		<h3>$msg</h3>
		//	  </div>";
	}
}
?>
