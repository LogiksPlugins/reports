<?php
$GLOBALS['RPTDATA']['dataType']="html";
$dataSource=$GLOBALS['RPTDATA'];

_js(array("raphael.min","raphael.pie"));

$webPath=getWebPath(dirname(__FILE__));
echo "<link href='".$webPath."css/piechart.css' rel='stylesheet' type='text/css' media='all' /> ";

$dn=$dataSource["datatable_colnames"];
$d=$dataSource["datatable_cols"];
$hdn=explode(",",$dataSource['datatable_hiddenCols']);

foreach($hdn as $n=>$x) {
	$x=explode(".",$x);
	$hdn[$n]="td[col=".$x[count($x)-1]."]";
}
$hdn=implode(",",$hdn);
/*
if(strlen(trim($dn))<=0) $dn=$d;
$dn=explode(",",$dn);
foreach($hdn as $a) {
	echo $a;
}
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
*/
?>
<div class='reportDataTable ui-widget-content'>
<h1 class=header><?=$dataSource["header"]?></h1>
<h4 class=footer><?=$dataSource["footer"]?></h4>
<table id='chartdata' style='ui-corner-all' width=200px border=1 cellspacing=0></table>
<div id="holder" style=''>
</div>
</div>
<script language=javascript>
<?=$dataSource["datatable_params"]?>
function loadData() {
	loadDataTable("#chartdata");
}
function loadDataTable(divID) {
	if(rptOptions.groupField[0]==null) rptOptions.groupField[0]="";
	src=dataSource+"&page=0&rows=-1"+"&sord="+rptOptions.sortorder+"&sidx="+rptOptions.sortname;
	if(rptOptions.grouping==true) {
		src+="&grp="+rptOptions.groupField[0];
	}
	
	$(divID).html("<tr><td colspan=1000><div class=ajaxloading>Loading Report</div></td></tr>");
	//alert(src);
	//processAJAXQuery(src,function(txt) { alert(txt); });
	$(divID).load(src, function(txt) {
			try {
				json=$.parseJSON(txt);
				$(divID).html("");
				if(json.MSG.length>0) lgksAlert(json.MSG);
			} catch(e) {
				$("<?=$hdn?>").detach();
				loadChart();
			}
		});
}
function loadChart() {
	(function (raphael) {
		$(function () {
			var values = [],
				labels = [];
			$("#chartdata tr").each(function () {
				c1=$(this).children()[0];
				c2=$(this).children()[1];
				values.push(parseInt($(c2).text(), 10));
				labels.push($(c1).text()+" ("+$(c2).text()+")");
			});
			$("table").html("<thead><tr><td>Names</td><td>Values</td></tr><tr><td height=5px colspan=10><hr/></td></tr></thead>" + $('table').html());
			if(!rptOptions.gridview) {
				$("table").hide();
				raphael("holder", 700, $(window).height()).pieChart(350, 300, 200, values, labels, "#fff");
				$("#holder svg").css("margin-left","-220px");
				$(".reportDataTable").css("overflow","hidden");
			} else {
				raphael("holder", 700, $(window).height()).pieChart(350, 300, 200, values, labels, "#fff");
			}
		});
	})(Raphael.ninja());
}
</script>
