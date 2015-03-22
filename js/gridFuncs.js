function reloadDataTable(btn) {
	jqGrid=$(btn).parents(".LGKSRPTTABLE:first").find(".datagrid");
	gridId=jqGrid.attr("id").replace("_grid_table","");

	//$jqGrid.trigger('resetFilter');
	//$jqGrid.trigger('reloadGrid');
	if($(btn).parents(".LGKSRPTTABLE:first").find(".ui-search-toolbar").length>0)
		$(btn).parents(".LGKSRPTTABLE:first").find(".ui-search-toolbar input").val("");
	jqGrid.setGridParam({ search: false, postData: { "filters": ""}}).trigger("reloadGrid");
}
function searchDataTable(btn) {
	jqGrid=$(btn).parents(".LGKSRPTTABLE:first").find(".datagrid");
	gridId=jqGrid.attr("id").replace("_grid_table","");
	jqGrid.searchGrid(rptSearchOptsMaster[gridId]);
}
function colChange(btn) {
	jqGrid=$(btn).parents(".LGKSRPTTABLE:first").find(".datagrid");
	gridId=jqGrid.attr("id").replace("_grid_table","");
	jqGrid.columnChooser();
}
function viewRecord(btn) {
	jqGrid=$(btn).parents(".LGKSRPTTABLE:first").find(".datagrid");
	gridId=jqGrid.attr("id").replace("_grid_table","");
	var gsr = jqGrid.getGridParam("selrow");
	id="#"+jqGrid.parents(".LGKSRPTTABLE").attr("id");
	if(gsr){
		jqGrid.viewGridRow(gsr);
	} else {
		gridInfoDialog("Warning","Please select Row");
	}
}

function printGrid(btn) {
	jqGrid=$(btn).parents(".LGKSRPTTABLE:first").find(".datagrid");
	gridId=jqGrid.attr("id").replace("_grid_table","");

	//id="#"+$($jqGrid).parents(".LGKSRPTTABLE").attr("id")+"_grid_table";
	html=getHTMLForGrid(jqGrid);
	html="<html><title>Print Preview</title></html><div align=center class=noprint><button onclick='window.print();' style='width:100px;height:30px;'>Print</button></div>"+html;
	OpenWindow=window.open('','Print Preview');
	OpenWindow.document.write(html);
	OpenWindow.window.print();
}
function exportToExcel(btn) {
	jqGrid=$(btn).parents(".LGKSRPTTABLE:first").find(".datagrid");
	gridId=jqGrid.attr("id").replace("_grid_table","");

	//id="#"+$($jqGrid).parents(".LGKSRPTTABLE").attr("id")+"_grid_table";
	html=getCSVForGrid(jqGrid);	
	id=createForm("services/?scmd=export&type=download&format=csv&src=csv",html);
	$("form#"+id).submit();	
}
function exportToHTML(btn) {
	jqGrid=$(btn).parents(".LGKSRPTTABLE:first").find(".datagrid");
	gridId=jqGrid.attr("id").replace("_grid_table","");

	//id="#"+$($jqGrid).parents(".LGKSRPTTABLE").attr("id")+"_grid_table";
	html=getHTMLForGrid(jqGrid);
	id=createForm(getServiceCMD("export")+"&type=download&format=html&src=html",html);
	$("form#"+id).submit();
}
function mailGrid(btn) {
	jqGrid=$(btn).parents(".LGKSRPTTABLE:first").find(".datagrid");
	gridId=jqGrid.attr("id").replace("_grid_table","");

	//id="#"+$($jqGrid).parents(".LGKSRPTTABLE").attr("id")+"_grid_table";
	html=getHTMLForGrid(jqGrid);
	//document.location.href="MailTo:?Subject=Report Grid "+new Date()+"&body="+html;
	$.mailform("","Report Grid "+new Date(),html);
}
function gotoActionLink(grid) {
	jqGrid=$(selector).parents(".LGKSRPTTABLE:first").find(".datagrid");
	gridId=jqGrid.attr("id").replace("_grid_table","");
	gsr = jqGrid.getGridParam("selrow");
	
	if(gsr) {
		openReportLink(SiteLocation+src+gsr,"Preview");
	} else {
		gridInfoDialog("Warning","Please select Row");
	}
}
function gridAction(selector,title) {
	jqGrid=$(selector).parents(".LGKSRPTTABLE:first").find(".datagrid");
	gridId=jqGrid.attr("id").replace("_grid_table","");

	title=$(selector).find("option:selected").text();
	src=$(selector).val();
	
	gsr=$(selector).parents("tr").attr("id");
	//gsr = jqGrid.getGridParam("selrow");
	if(src.length<=0) return;
	jqGrid.jqGrid("setSelection",gsr);

	if(title==null || title.length<=0)
		title=$(selector).find("option:selected").text();
		
	if(title==null || title.length<=0) title="Preview";

	$(selector).val("");
	if(gsr) {
		openReportLink(SiteLocation+src+gsr,title);
	} else {
		gridInfoDialog("Warning","Please select Row");
	}
}

function createGridTree(btn,val) {
	jqGrid=$(btn).parents(".LGKSRPTTABLE:first").find(".datagrid");
	gridId=jqGrid.attr("id").replace("_grid_table","");
	if(val.length==0) {
		jqGrid.groupingRemove();
	} else {
		rptOptions=rptOptsMaster[gridId];
		rptOptions.groupField=val;
		jqGrid.groupingGroupBy(val,{
				groupColumnShow:rptOptions.groupColumnShow,
				groupText:rptOptions.groupText,
				groupSummary:rptOptions.groupSummary,
				showSummaryOnHide:rptOptions.showSummaryOnHide,
			});
	}
	return true;
}
function toggleFilterBar(btn) {
	jqGrid=$(btn).parents(".LGKSRPTTABLE:first").find(".datagrid");
	parent=$(btn).parents(".LGKSRPTTABLE:first");
	gridId=jqGrid.attr("id").replace("_grid_table","");
	rptOptsMaster[gridId].filterToolbar=!rptOptsMaster[gridId].filterToolbar;
	if(rptOptsMaster[gridId].filterToolbar) {
			if(parent.find(".ui-search-toolbar").length>0) {
				parent.find(".ui-search-toolbar").show();
			} else {
				jqGrid.filterToolbar({stringResult: true,searchOnEnter : rptOptsMaster[gridId].filterSearchOnEnter});	
			}
	} else {
			parent.find(".ui-search-toolbar").hide();
			//reloadDataTable();
	}
}
function gridInfoDialog(title,msg) {
	$("#dialog_frm_1").detach();
	$("body").append("<div id=dialog_frm_1 title='"+title+"'>"+msg+"</div>");
	$("#dialog_frm_1").dialog();
}
