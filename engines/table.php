<?php
global $js,$css;

$GLOBALS['RPTDATA']['dataType']="html";
$dataSource=$GLOBALS['RPTDATA'];

$webPath=getWebPath(dirname(__FILE__));
echo "<script src='".$webPath."js/table.js' type='text/javascript' language='javascript'></script>";
echo "<link href='".$webPath."css/tables.css' rel='stylesheet' type='text/css' media='all' /> ";

$dn=$dataSource["datatable_colnames"];
$d=$dataSource["datatable_cols"];
if(strlen(trim($dn))<=0) $dn=$d;

$dn=explode(",",$dn);
$cols=array();
foreach($dn as $x) {
	$x=str_replace("_"," ",trim($x));
	if(strpos($x,".")>0) {
		$x=explode(".",$x);
		$x=$x[sizeOf($x)-1];
	}
	if(strlen($x)<=3) $x=strtoupper($x);
	else $x=ucwords($x);
	array_push($cols,$x);
}
?>
<style>
.LGKSRPTTABLE {
	overflow-y:auto;
}
</style>
<div id=hd1 class='ui-widget-header' style='width:100%;height:25px;overflow:hidden;margin-bottom:0px;'>
	<h3 style='margin:0px;margin-top:4px;margin-left:15px;float:left;'><?=$dataSource['header']?></h3>
	<div style='float:right;margin-right:2px;'>
		<?=printGridButtons("printview",$dataSource["divid"]);?>
	</div>
</div>
<div class='reportDataTable ui-widget-content ui-corner-all'>
<table id='<?=$dataSource["divid"]?>_grid_table' class='ui-corner-all' style='width:99%;margin:auto;' cellpadding=0 cellspacing=0 border=1>
	<thead>
		<tr align=center class='ui-widget-header' height=23px>
			<?php 
				foreach($cols as $c) {
					echo "<td>$c</td>";
				}
			?>
		</tr>
	</thead>
	<tbody>		
	</tbody>
</table>
</div>
<script language=javascript>
<?=$dataSource["datatable_params"]?>
function loadData() {
	$(".reportDataTable").css("height",($(window).height()-$("#hd1").height()-15)+"px");
	loadDataTable("#<?=$dataSource["divid"]?>_grid_table");
}
function loadDataTable(divID) {
	src=dataSource+"&page=0&rows="+rptOptions.rowNum;
	$(divID+" tbody").html("<tr><td colspan=1000><div class=ajaxloading>Loading Report</div></td></tr>");
	$(divID+" tbody").load(src);
}
</script>
