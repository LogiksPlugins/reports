$jqGrid=null;
jqColumns={
	"colModel": [
				{name:'id',index:'id', width:60},
			],
	};
//rptSearchOpts
//rptOptions
function loadDataGrid(formID) {	
	h=$(formID).height();
	hx=85;
	
	if(rptOptions.filterToolbar) {
		hx+=23;
	}
	if(rptOptions.footerSummary) {
		hx+=23;
	}
	h=$(formID).height()-hx;
	if(rptOptions.rowList==null || rptOptions.rowList.length<=0) {
		rptOptions.rowList=[10,30,60,100,250,500,1000,2500,5000,10000,25000];
	}
	if(rptSearchOpts.sopt==null || rptSearchOpts.sopt.length<=0) {
		rptSearchOpts.sopt=['eq','ne','lt','le','gt','ge','bw','bn','ew','en','cn','nc','nn','nu','in','ni'];
	}
	$jqGrid=$(formID+"_grid_table").jqGrid({
			url:dataSource,
			datatype:"json",
			mType:"POST",
			colNames:jqColumns["colNames"],
			colModel:jqColumns["colModel"],
			loadonce:rptOptions.loadonce,
			rowNum:rptOptions.rowNum,
			rowList:rptOptions.rowList,
			rownumbers:rptOptions.rownumbers,
			rownumWidth:rptOptions.rownumWidth,
			autowidth:rptOptions.autowidth,
			forceFit:rptOptions.forceFit,
			shrinkToFit:rptOptions.shrinkToFit,
			scroll:rptOptions.scroll,
			height:h,			
			
			gridview:rptOptions.gridview,
			viewrecords:rptOptions.viewrecords,
			pager: formID+"_grid_pager",
			sortname:rptOptions.sortname,
			sortorder:rptOptions.sortorder,
			multiselect:rptOptions.multiselect,
			loadError:function(xhr,st,err) {
				//alert(xhr.responseText);
				//console.log("Ajax Error :: "+xhr.responseText);
				gridInfoDialog("Ajax Error","Type: "+st+"; Response: "+ xhr.status + " "+xhr.statusText);
			},
			loadComplete:function(txt) {
				if(txt.MSG.length>0)  {
					if(typeof lgksAlert == "function") lgksAlert(txt.MSG);
					else alert(txt.MSG);
				}
			},				
			gridComplete: function(){
				var ids = $jqGrid.getDataIDs();
				for(var i=0;i < ids.length;i++){
					var cl = ids[i];
					be = "<input style='height:22px;width:20px;' type='button' value='E' onclick=\"$jqGrid.editRow('"+cl+"');\"  />"; 
					se = "<input style='height:22px;width:20px;' type='button' value='S' onclick=\"$jqGrid.saveRow('"+cl+"');\"  />"; 
					ce = "<input style='height:22px;width:20px;' type='button' value='C' onclick=\"$jqGrid.restoreRow('"+cl+"');\" />"; 
					$jqGrid.setRowData(ids[i],{act:be+se+ce});
				}
			},
			//editurl:rptOptions.editsrc+'&id='+dbID,
			cellsubmit:'remote',
			cellEdit:rptOptions.cellEdit,
			
			altRows:rptOptions.altRows,
			altclass:rptOptions.altclass,
			
			footerrow:rptOptions.footerSummary,
			userDataOnFooter:rptOptions.footerSummary,
			
			grouping:rptOptions.grouping,
			groupingView : {
				groupField:rptOptions.groupField,
				groupDataSorted:rptOptions.groupDataSorted,
				groupCollapse:rptOptions.groupCollapse,
				groupColumnShow:rptOptions.groupColumnShow,
				groupText:rptOptions.groupText,
				groupSummary:rptOptions.groupSummary,
				showSummaryOnHide:rptOptions.showSummaryOnHide,
			},
			
			//Actions
			onCellSelect: function(rowid,iCol,cellcontent,e) {
			},
			ondblClickRow: function(rowid,iRow,iCol,e) {
				td=$jqGrid.find("tr[id="+rowid+"] td").get(iCol);
				txt=$(td).text().trim();
				if(txt.length>0) {
					if(takeAction($(td),txt)) {
						return;
					}
				}
				if(rptOptions.actOnDblclick) {
					gotoActionLink();
				}
			},
			//Events groupSelector
			gridComplete: function(response, postdata, formid) {
				var colNames=new Array(); 
				var colTitles=new Array(); 
				var ii=0;
				xc=$jqGrid.getGridParam("colNames");
				xp=$jqGrid.getGridParam("colModel");
				for(var t in xp) {
					if(xc[t]!=null && typeof xc[t]=="string" && xc[t].length>0 && xc[t].indexOf("<")<0) {
						if(xp[t].name!=null && !(xp[t].name=="rn" || xp[t].name=="cb")) {
							colNames[ii]=xp[t].name;
							colTitles[ii]=xc[t];
							ii++;
							//if(!xp[t].hidden) {}
						}
					}		
				}
				html="";
				html+="<option value=''>None</option>";
				for(k=0;k<colNames.length;k++) {
					if(rptOptions.groupField==colNames[k])
						html+="<option value='"+colNames[k]+"' selected>"+colTitles[k]+"</option>";
					else
						html+="<option value='"+colNames[k]+"'>"+colTitles[k]+"</option>";
				}
				$("#hd1 .btn_grouping").html(html);
				
				footerData={};
				$.each(jqColumns.colModel,function(k,v) {
						if(v.classes!=null && v.classes.length>0) {
							footerData[v.index]=0;
							rowIDs=$jqGrid.getDataIDs();
							
							clz=v.classes.split(" ");
							
							if(clz.indexOf("sum")>=0) {
								for(i=0;i<$jqGrid.find("tbody tr[role=row][id]").length;i++) {
									n=$jqGrid.getCell(rowIDs[i],v.index);
									if(!isNaN(n)) {
										footerData[v.index]+=parseFloat(n);
									}
								}
							} else if(clz.indexOf("count")>=0) {
								for(i=0;i<$jqGrid.find("tbody tr[role=row][id]").length;i++) {
									footerData[v.index]+=1;
								}
							} else if(clz.indexOf("average")>=0) {
								for(i=0;i<$jqGrid.find("tbody tr[role=row][id]").length;i++) {
									n=$jqGrid.getCell(rowIDs[i],v.index);
									if(!isNaN(n)) {
										footerData[v.index]+=parseFloat(n);
									}
								}
								footerData[v.index]=footerData[v.index]/rowIDs.length;
							} 
							
							if(clz.indexOf("sqrt")>=0) {
								footerData[v.index]=Math.sqrt(footerData[v.index]);
							} else if(clz.indexOf("log")>=0) {
								footerData[v.index]=Math.log(footerData[v.index]);
							} else if(clz.indexOf("exp")>=0) {
								footerData[v.index]=Math.exp(footerData[v.index]);
							} 
							
							if(clz.indexOf("round")>=0) {
								footerData[v.index]=footerData[v.index].toFixed(2);
							} else if(clz.indexOf("floor")>=0) {
								footerData[v.index]=Math.floor(footerData[v.index]);
							} else if(clz.indexOf("ceil")>=0) {
								footerData[v.index]=Math.ceil(footerData[v.index]);
							}
						}
					});
				$jqGrid.footerData('set', footerData);
			},
		});
	$jqGrid.navGrid(formID+"_grid_pager",
			{view:false,edit:false,add:false,del:false},
			{},//add
			{},//edit
			{},//delete
			rptSearchOpts);
	if(rptOptions.filterToolbar) {
		$jqGrid.filterToolbar({stringResult: true,searchOnEnter : rptOptions.filterSearchOnEnter});	
	}
	$(".ui-jqgrid .ui-jqgrid-view .ui-jqgrid-sdiv table td").addClass("ui-state-default");
}
function reloadDataTable() {
	//$jqGrid.trigger('resetFilter');
	//$jqGrid.trigger('reloadGrid');
	if($(".reportDataTable").find(".ui-search-toolbar").length>0)
		$(".reportDataTable").find(".ui-search-toolbar input").val("");
	$jqGrid.setGridParam({ search: false, postData: { "filters": ""}}).trigger("reloadGrid");
}
function searchDataTable() {
	$jqGrid.searchGrid(rptSearchOpts);
}
function colChange() {
	$jqGrid.columnChooser();
}
function editRecord() {
	var gsr = $jqGrid.getGridParam("selrow");
	id="#"+$($jqGrid).parents(".LGKSRPTTABLE").attr("id");
	if(gsr){
		$jqGrid.GridToForm(gsr,id+"_form");
	} else {
		gridInfoDialog("Warning","Please select Row");
	}
}
function viewRecord() {
	var gsr = $jqGrid.getGridParam("selrow");
	id="#"+$($jqGrid).parents(".LGKSRPTTABLE").attr("id");
	if(gsr){
		$jqGrid.viewGridRow(gsr);
	} else {
		gridInfoDialog("Warning","Please select Row");
	}
}
function gotoActionLink() {
	var gsr = $jqGrid.getGridParam("selrow");
	if(actionLink.length<=0) return;
	if(gsr) {
		if(rptOptions.actionLinkInNewPage) {
			if(typeof parent.openInNewTab == "function") {
				parent.openInNewTab("Preview",SiteLocation+actionLink+gsr);
			} else {
				window.open(SiteLocation+actionLink+gsr);
			}
		} else {
			document.location=SiteLocation+actionLink+gsr;
		}
	} else {
		gridInfoDialog("Warning","Please select Row");
	}
}
function printGrid() {
	id="#"+$($jqGrid).parents(".LGKSRPTTABLE").attr("id")+"_grid_table";
	html=getHTMLForGrid(id);
	html="<html><title>Print Preview</title></html><div align=center class=noprint><button onclick='window.print();' style='width:100px;height:30px;'>Print</button></div>"+html;
	OpenWindow=window.open('','Print Preview');
	OpenWindow.document.write(html);
	//OpenWindow.window.print();
}
function exportToExcel() {
	id="#"+$($jqGrid).parents(".LGKSRPTTABLE").attr("id")+"_grid_table";
	html=getCSVForGrid(id);	
	id=createForm("services/?scmd=export&type=download&format=csv&src=csv",html);
	$("form#"+id).submit();	
}
function exportToHTML() {
	id="#"+$($jqGrid).parents(".LGKSRPTTABLE").attr("id")+"_grid_table";
	html=getHTMLForGrid(id);
	id=createForm("services/?scmd=export&type=download&format=html&src=html",html);
	$("form#"+id).submit();
}
function mailGrid() {
	id="#"+$($jqGrid).parents(".LGKSRPTTABLE").attr("id")+"_grid_table";
	html=getHTMLForGrid(id);
	//document.location.href="MailTo:?Subject=Report Grid "+new Date()+"&body="+html;
	$.mailform("","Report Grid "+new Date(),html);
}

function resizeSplits(e) {
	w=$(e).find(".leftdiv").width();
	if(w!=null && $jqGrid!=null) {
		$jqGrid.setGridWidth(w);
	}
}
function resizeHSplits(e) {
	h=$(e).find(".leftdiv").height()-80;
	if(h!=null && $jqGrid!=null) {
		$jqGrid.setGridHeight(h);
	}
}
function gridInfoDialog(title,msg) {
	$("#dialog_frm_1").detach();
	$("body").append("<div id=dialog_frm_1 title='"+title+"'>"+msg+"</div>");
	$("#dialog_frm_1").dialog();
}
function createGridTree(val) {
	if(val.length==0) {
		$jqGrid.groupingRemove();
	} else {
		rptOptions.groupField=val;
		$jqGrid.groupingGroupBy(val,{
				groupColumnShow:rptOptions.groupColumnShow,
				groupText:rptOptions.groupText,
				groupSummary:rptOptions.groupSummary,
				showSummaryOnHide:rptOptions.showSummaryOnHide,
			});
	}
}
function toggleFilterBar() {
	rptOptions.filterToolbar=!rptOptions.filterToolbar;
	if(rptOptions.filterToolbar) {
			if($(".reportDataTable").find(".ui-search-toolbar").length>0) {
				$(".reportDataTable").find(".ui-search-toolbar").show();
			} else {
				$jqGrid.filterToolbar({stringResult: true,searchOnEnter : rptOptions.filterSearchOnEnter});	
			}
	} else {
			$(".reportDataTable").find(".ui-search-toolbar").hide();
			//reloadDataTable();
	}
}
function takeAction(obj,content) {
	if(obj==null || content==null || content.length<=0) return false;
	if(obj.hasClass("email")) {
		$.mailform(content,"No Subject","");
	} else if(obj.hasClass("url")) {
		if(typeof parent.openInNewTab=="function") {
			parent.openInNewTab("Link",content);
		}
		return;
	} else if(obj.hasClass("attachment")) {
		txt=content;
		if(isNaN(txt)) {
			txt=txt.split(",");
			if(txt.length>1) {
				msg="<div style='width:800px;height:300px;overflow:auto;'><ol>";
				for(i=0;i<txt.length;i++) {
					nm=txt[i].split('/');
					nm=nm[nm.length-1];
					msg+="<li style='margin-bottom:5px;'><a href='#' onclick=\"openFileLink('"+txt[i]+"','local');\" >"+nm+"</a><br/></li>";
				}
				msg+="</ol></div>";
				lgksAlert(msg);
			} else {
				openFileLink(txt[0],'local');
			}
		} else {
			openFileLink(txt,'dbfile');
		}
		return;
	}
	return true;
}
function openFileLink(txt,type) {
	if(type=="local") {
		//lnk="services/?scmd=viewfile&type=view&loc=local&popup=true&file="+txt;
		lnk=getServiceCMD("viewfile")+"&type=view&loc=local&popup=true&file="+txt;
		lgksOverlayFrame(lnk);
	} else {
		//services?scmd=viewfile&type=view&loc=dbfile&dbtbl=do_files&file=1
		//services/?scmd=viewfile&type=view&loc=dbdoc&dbtbl=do_docs&file=1
		lgksAlert("Can Not Handle These Type Of Attachments");
	}
}
