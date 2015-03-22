<?php
if(!defined('ROOT')) exit('No direct script access allowed');

$GLOBALS['RPTDATA']['dataType']="json";

_js(array("jquery.ui.multiselect","jquery.jqGrid-locale-en","jquery.jqGrid"));
_css(array("jquery.ui.jqgrid","jquery.ui.multiselect"));

$webPath=getWebPath(dirname(__FILE__));
echo "<script src='".$webPath."js/lgksJQGrid.js' type='text/javascript' language='javascript'></script>";
echo "<script src='".$webPath."js/gridFuncs.js' type='text/javascript' language='javascript'></script>";
echo "<script src='".$webPath."js/gridprint.js' type='text/javascript' language='javascript'></script>";

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

//$reg = '/[^(,]*(?:\([^)]+\))?[^),]*/';
//preg_match_all($reg, $d, $matches);
//$d = array_filter($matches[0]);
$d = preg_split("/,(?![^()]*+\\))/", $d);
//printArray($d);printArray($dn);exit();

$cols="[";
foreach($dn as $x) {
	$x=_ling($x);
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

		$x=trim(end(explode(" as ",strtolower($x))));
		$x=str_replace("'", '"', $x);
		
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
		$model.=",sorttype:'string'";//, searchoptions:{sopt:['bw','eq','bn','cn','nc','ew','en']}
		$model.="},";
	}
	$n++;
}
$model.="]";

//printArray($dataSource);exit();
//echo strlen($dataSource["datatable_model"]);
//exit($model);
if(!$rptData['params']["notoolbar"]) {
?>
<div id=hd1 rel='<?=$dataSource["divid"]?>_grid_table' class='gridbar ui-widget-header' style='width:100%;height:25px;overflow:hidden;'>
	<h3 style='margin:0px;margin-top:4px;margin-left:15px;float:left;'><?=_ling(_replace($dataSource['header']))?></h3>
	<div style='float:right;margin-right:2px;'>
		<?=printGridButtons($dataSource["toolbtns"],$dataSource["divid"]);?>
	</div>
</div>
<?php
}
?>
<div class=reportDataTable>
	<table width=100% id='<?=$dataSource["divid"]?>_grid_table'>				
	</table>
	<div id='<?=$dataSource["divid"]?>_grid_pager' class="pager"></div>
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
			$.lgksJQGrid(gridID,rptSearchOpts,rptOptions,jqColumns,'<?=$dataSource['dataSource']?>');
		},100);
});
</script>
