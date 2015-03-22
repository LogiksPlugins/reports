<?php
global $js,$css;

$GLOBALS['RPTDATA']['dataType']="json";

_js(array("jquery.ui.multiselect","jquery.jqGrid-locale-en","jquery.jqGrid"));
_css(array("jquery.ui.jqgrid","jquery.ui.multiselect"));

$webPath=getWebPath(dirname(__FILE__));
echo "<script src='".$webPath."js/grid.js' type='text/javascript' language='javascript'></script>";
echo "<script src='".$webPath."js/gridprint.js' type='text/javascript' language='javascript'></script>";

$dataSource=$GLOBALS['RPTDATA'];

ob_start();
include dirname(dirname(__FILE__)). "/css/htmlexport.css";
$css=ob_get_contents();
ob_clean();
$css=str_replace("\n","",$css);
$css=str_replace("	"," ",$css);
$css=str_replace("  "," ",$css);

$dn=$dataSource["datatable_colnames"];
$d=$dataSource["datatable_cols"];
if(strlen(trim($dn))<=0) $dn=$d;

//$d=explode(",",$d);
$reg = '/[^(,]*(?:\([^)]+\))?[^),]*/';
preg_match_all($reg, $d, $matches);
$d = array_filter($matches[0]);

$dn=explode(",",$dn);
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
$maxCols=25;
$idField=getSiteSettings("ID Column Width",50,"DataTable","int");

$hiddenCols=array();
$searchCols=$d;
$sortCols=$d;
$classes=array();

if(isset($dataSource['datatable_hiddenCols'])) {
	$hiddenCols=explode(",",$dataSource['datatable_hiddenCols']);
}

if(isset($dataSource['datatable_model']) && strlen($dataSource['datatable_model'])>2) {
	$modelArr=json_decode($dataSource["datatable_model"],true);

	if($modelArr!=null) {
		$modelEngine=$modelArr["modelEngine"];
		if($modelEngine=="DataControls1") {
			$hiddenCols=explode(",",$modelArr["modelData"]["hiddenCols"]);
			$searchCols=explode(",",$modelArr["modelData"]["searchCols"]);
			$sortCols=explode(",",$modelArr["modelData"]["sortCols"]);
			$classes=explode(",",$modelArr["modelData"]["classes"]);
		} else {
			dispErrMessage("DataModel Not Found Requested Report.","Report Model Error",
					404,"media/images/notfound/database.png");
			exit();
		}
	}
}

$model="[";
foreach($d as $x) {
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
	$model.="},";
	$n++;
}
$model.="]";

//echo strlen($dataSource["datatable_model"]);
//exit($model);
?>
<?php
	if(!isset($_REQUEST['toolbar']) || $_REQUEST['toolbar']=="true") {
?>
<div id=hd1 class='ui-widget-header' style='width:100%;height:25px;overflow:hidden;'>
	<h3 style='margin:0px;margin-top:4px;margin-left:15px;float:left;'><?=$dataSource['header']?></h3>
	<div style='float:right;margin-right:2px;'>
		<?=printGridButtons($dataSource["toolbtns"],$dataSource["divid"]);?>
	</div>
</div>
<?php
	}
?>
<div class=reportDataTable>
	<table id='<?=$dataSource["divid"]?>_grid_table'>
	</table>
	<div id='<?=$dataSource["divid"]?>_grid_pager' class="pager"></div>
</div>
<script type="text/javascript">
jqColumns={
		"colNames":<?=$cols?>,
		"colModel": <?=$model?>,
	};
<?=$dataSource["datatable_params"]?>
exportCSS="<?=$css?>";
function loadData() {
	loadDataGrid("#<?=$divId?>");
}
</script>
