<?php
if(!defined('ROOT')) exit('No direct script access allowed');

$GLOBALS['RPTDATA']['dataType']="json";

//_js(array("jquery.ui.multiselect","jquery.jqGrid-locale-en","jquery.jqGrid"));
//_css(array("jquery.ui.jqgrid","jquery.ui.multiselect"));

$webPath=getWebPath(dirname(__FILE__));
echo "<script src='".$webPath."js/lgksTable.js' type='text/javascript' language='javascript'></script>";
echo "<script src='".$webPath."js/tableFuncs.js' type='text/javascript' language='javascript'></script>";
echo "<link href='".$webPath."css/tables.css' rel='stylesheet' type='text/css' media='all' /> ";

$dataSource=$GLOBALS['RPTDATA'];

ob_start();
include dirname(dirname(__FILE__)). "/css/htmlexport.css";
$css=ob_get_contents();
ob_clean();
$css=str_replace("\n","",$css);
$css=str_replace("	"," ",$css);
$css=str_replace("  "," ",$css);

if(isset($dataSource['datatable_model']) && strlen($dataSource['datatable_model'])>2) {
	$modelArr=json_decode($dataSource["datatable_model"],true);
}

if(isset($modelArr["modelData"]["alinks"])) {
	$dn=$dataSource["datatable_colnames"].",";
	$d=$dataSource["datatable_cols"].",act2";
} else {
	$dn=$dataSource["datatable_colnames"];
	$d=$dataSource["datatable_cols"];
}
if(strlen(trim($dn))<=0) $dn=$d;
//$d=explode(",",$d);
$dn=explode(",",$dn);

$reg = '/[^(,]*(?:\([^)]+\))?[^),]*/';
preg_match_all($reg, $d, $matches);
$d = array_filter($matches[0]);
//printArray($d);printArray($dn);exit();

$colsHeader=array();
foreach($dn as $x) {
	$x=str_replace("_"," ",trim($x));
	if(strpos($x,".")>0) {
		$x=explode(".",$x);
		$x=$x[sizeOf($x)-1];
	}
	if(strlen($x)<=3) $x=strtoupper($x);
	else $x=ucwords($x);
	array_push($colsHeader,$x);
}

$cols="[";
foreach($dn as $x) {
	$x=str_replace("_"," ",trim($x));
	if(strpos($x,".")>0) {
		$x=explode(".",$x);
		$x=$x[sizeOf($x)-1];
	}
	if(strlen($x)<=3) $x=strtoupper($x);
	else $x=ucwords($x);
	$cols.="'$x',";
}
$cols.="]";

$n=0;
$maxCols=$rptData['params']["visibleCols"];
$idField=$rptData['params']["idColWidth"];

$hiddenCols=array();
$searchCols=$d;
$sortCols=$d;
$classes=array();
$colTypes=array();
$alinks=array();
$groupableCols=null;
$notviewableCols=null;

if(isset($dataSource['datatable_hiddenCols'])) {
	$hiddenCols=explode(",",$dataSource['datatable_hiddenCols']);
}

if($modelArr!=null) {
	$modelEngine=$modelArr["modelEngine"];
	if(isset($modelArr["modelData"]["hiddenCols"])) 
		$hiddenCols=explode(",",$modelArr["modelData"]["hiddenCols"]);
	if(isset($modelArr["modelData"]["searchCols"])) 
		$searchCols=explode(",",$modelArr["modelData"]["searchCols"]);
	if(isset($modelArr["modelData"]["sortCols"])) 
		$sortCols=explode(",",$modelArr["modelData"]["sortCols"]);
	if(isset($modelArr["modelData"]["classes"])) 
		$classes=explode(",",$modelArr["modelData"]["classes"]);
	if(isset($modelArr["modelData"]["groupable"])) 
		$groupableCols=explode(",",$modelArr["modelData"]["groupable"]);
	if(isset($modelArr["modelData"]["notviewable"])) 
		$notviewableCols=explode(",",$modelArr["modelData"]["notviewable"]);
	if(isset($modelArr["modelData"]["alinks"])) 
		$alinks=$modelArr["modelData"]["alinks"];
}
//$alinks=array("Edit"=>"testform.php?id=");

$model="[";
foreach($d as $x) {
	if($x=="act1") {
		$model.="{";
		$model.="name:'action1'";
		$model.=",index:'$x'";
		$model.=",search:false";
		$model.=",sortable:false";
		$model.=",hidden:false";
		$model.=",formatter:'actions'";
		$model.=",width:110";

		$model.="},";
	} elseif($x=="act2") {
		$model.="{";
		$model.="name:'action2'";
		$model.=",index:'$x'";
		$model.=",search:false";
		$model.=",sortable:false";
		$model.=",hidden:false";
		$model.=",formatter:function() {return '<select onchange=\"gridAction(this)\" class=\"gridActionSelector\">".getActionsSelector($alinks)."</select>';}";
		$model.=",width:110";

		$model.="},";
	} else {
		$y=explode(".",$x);
		$y=$y[count($y)-1];

		$x=trim(end(explode("as",strtolower($x))));
		
		$model.="{";			
		$model.="name:'$x'";
		$model.=",index:'$x'";
		
		if(in_array($x,$searchCols)) {
			$model.=",search:true";
		} else {
			$model.=",search:false";
		}
		if(in_array($x,$sortCols)) {
			$model.=",sortable:true";
		} else {
			$model.=",sortable:false";
		}
		if(isset($classes[$n]) && strlen($classes[$n])>0) {
			$model.=",classes:'{$classes[$n]}'";
		}
		
		if($n==0) {
			$model.=",key:true,width:$idField";
		}
		if(in_array($x,$hiddenCols) || $n>$maxCols) {
			$model.=",hidden:true";
		}
		if(in_array($x,$groupableCols) || $groupableCols==null) {
			$model.=",groupable:true";
		}
		if($notviewableCols==null) {
			$model.=",viewable:true,editrules:{ edithidden: true }";
		} elseif(in_array($x,$notviewableCols)) {
			$model.=",viewable:false";
		}
		$model.=",sorttype:'string', searchoptions:{sopt:['eq','bw','bn','cn','nc','ew','en']}";
		$model.="},";
	}
	$n++;
}
$model.="]";

if(!$rptData['params']["notoolbar"] && false) {
?>
<div class='gridbar ui-widget-header' style='width:100%;height:25px;overflow:hidden;margin-bottom:0px;'>
	<h3 style='margin:0px;margin-top:4px;margin-left:15px;float:left;'><?=_ling(_replace($dataSource['header']))?></h3>
	<div style='float:right;margin-right:2px;'>
		<?=printGridButtons("printview",$dataSource["divid"]);?>
	</div>
</div>
<?php
}
?>
<style>
.LGKSRPTTABLE {
	overflow-y:auto;
}
</style>
<div class='reportDataTable ui-widget-content ui-corner-all'>
	<table id='<?=$dataSource["divid"]?>_grid_table' class='rptTable ui-corner-all' style='width:99.5%%;margin:auto;margin-left: 1px;' cellpadding=0 cellspacing=0 border=1>
		<thead>
			<tr align=center class='ui-widget-header' height=23px>
				<?php 
					foreach($colsHeader as $c) {
						echo "<th>$c</th>";
					}
				?>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
</div>
<div class='gridbar navBar ui-widget-header' style='width:100%;height:25px;padding:0px;margin:0px;overflow:hidden;margin-bottom:0px;'>
	<span class='navBtn goFirst'></span>
	<span class='navBtn goBack'></span>
	<span class='navBtn goNext'></span>
	<span class='navBtn goLast'></span>

	<div class=infoBar>
		Page <span class=pageIndex>1</span> Of <span class=pageCount>1</span>
		<select class=rowsInPage></select>
	</div>
</div>
<script type="text/javascript">
exportCSS="<?=$css?>";
$(function() {
	setTimeout(function() {
			var jqColumns={
				"colNames":<?=$cols?>,
				"colModel": <?=$model?>,
				};
			var rptSearchOpts={};
			var rptOptions={};

			<?=$dataSource["datatable_params"]?>
			
			rptSearchOptsMaster['<?=$dataSource["divid"]?>']=$.extend({}, rptSearchOptsDefaults, rptSearchOpts);
			rptOptsMaster['<?=$dataSource["divid"]?>']=$.extend({}, rptOptionsDefaults, rptOptions);
			gridID='#<?=$dataSource["divid"]?>';
			$.lgksTable(gridID,rptSearchOpts,rptOptions,jqColumns,'<?=$dataSource['dataSource']?>');
		},100);
});
</script>
