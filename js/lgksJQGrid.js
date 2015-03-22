var rptSearchOptsDefaults={
		multipleSearch:true,
		multipleGroup:true,
		showQuery:false,
		modal:true,
		caption:"Search Data",
		sopt:['eq','ne','lt','le','gt','ge','bw','bn','ew','en','cn','nc','nn','nu','in','ni'],
		overlay:false,
	};
var rptOptionsDefaults={
				loadonce:false,
				rownumbers:false,
				rownumWidth:40,
				scroll:false,
				multiselect:false,

				altRows:true,
				altclass:'ui-priority-secondary',

				grouping:false,
				groupField:[],

				groupDataSorted:false,
				groupCollapse:true,
				groupColumnShow:[true],
				groupText:["<b style='text-transform:capitalize;color:#2E70FF;'>{0} - {1} Item(s)</b>"],
				groupSummary:[true],
				showSummaryOnHide:false,

				cellEdit:false,

				footerSummary:false,
				filterToolbar:false,
				filterSearchOnEnter:true,

				autowidth:true,
				forceFit:false,
				shrinkToFit:false,

				gridview:true,
				viewrecords:true,

				sortname:'',
				sortorder:"asc",
				ignoreCase:true,

				rowNum:30,
				rowList:[10,30,60,100,250,500,1000,2500,5000,10000,25000],

				//readsrc:'services/?scmd=datagrid&action=load&datatype=json&sqlsrc=dbtable&sqltbl=do_forms',
				//editsrc:'services/?scmd=datagrid&action=edit&datatype=json&frm=forms',

				emptyrecords: "No records to view",
				loadtext: "Loading....",
				//extraToolbar:false,

				actOnDblclick:true,
				actionLinkInNewPage:true,

				actionSelectorClass:"clr_green",
			};
rptSearchOptsMaster={};
rptOptsMaster={};
(function($) {
    $.lgksJQGrid = function(element, options1, options2, jqColumns,datasrc,editsrc) {

        var plugin = this;

        plugin.settings1 = {};
        plugin.settings2 = {};
		plugin.gridColumns = {};

        var $element = $(element), element = element;

        plugin.init = function() {
            plugin.settings1 = $.extend({}, rptSearchOptsDefaults, options1);
            plugin.settings2 = $.extend({}, rptOptionsDefaults, options2);
			plugin.gridColumns=jqColumns;
			//console.log(jqColumns);
			plugin.loadGrid(element,plugin.gridColumns,plugin.settings2,plugin.settings1,datasrc,editsrc);
        }

		plugin.loadGrid=function(gridID,jqColumns,rptOptions,rptSearchOpts,dataSource,editSource) {
			if(rptOptions.rowList==null || rptOptions.rowList.length<=0) {
				rptOptions.rowList=[10,30,60,100,250,500,1000,2500,5000,10000,25000];
			}
			if(rptSearchOpts.sopt==null || rptSearchOpts.sopt.length<=0) {
				rptSearchOpts.sopt=['eq','ne','lt','le','gt','ge','bw','bn','ew','en','cn','nc','nn','nu','in','ni'];
			}

			$(gridID+"_grid_table").addClass("datagrid");
			rpt=$(gridID+"_grid_table").parents(".LGKSRPTTABLE:first");
			hx=rpt.height();
			rpt.find(".gridbar").each(function() {
					hx-=$(this).height();
				});
			if(rptOptions.filterToolbar) hx-=20;
			hx-=55;
			$jqGrid=$(gridID+"_grid_table").jqGrid({
					url:dataSource,
					editurl:editSource,
					//readsrc:

					datatype:"json",
					mType:"POST",
					colNames:jqColumns["colNames"],
					colModel:jqColumns["colModel"],
					loadonce:rptOptions.loadonce,
					rowNum:rptOptions.rowNum,
					rowList:rptOptions.rowList,
					rownumWidth:rptOptions.rownumWidth,
					autowidth:rptOptions.autowidth,
					scroll:rptOptions.scroll,
					forceFit:rptOptions.forceFit,
					shrinkToFit:rptOptions.shrinkToFit,
					height:hx,
					//height: "100%",

					gridview:rptOptions.gridview,
					rownumbers:rptOptions.rownumbers,
					viewrecords:rptOptions.viewrecords,
					pager: gridID+"_grid_pager",
					sortname:rptOptions.sortname,
					sortorder:rptOptions.sortorder,
					multiselect:rptOptions.multiselect,
					beforeRequest:function() {
						//console.log($(this));
					},
					loadError:function(xhr,st,err) {
						//alert("Type: "+st+"; Response: "+ xhr.status + " "+xhr.statusText);
						//console.log("Ajax Error :: "+xhr.responseText);
						gridInfoDialog("Ajax Error","Type: "+st+"; Response: "+ xhr.status + " "+xhr.statusText);
					},
					loadComplete:function(txt) {
						if(txt.MSG.length>0)  {
							gridInfoDialog("Message",txt.MSG);
						}
						$(".LGKSRPTTABLE select.gridActionSelector").addClass(plugin.settings2.actionSelectorClass);

					},
					gridComplete: function(){
						//updateGridProperties($(this),plugin.settings2,plugin.gridColumns);
						var colNames=new Array(); 
						var colTitles=new Array(); 
						var ii=0;
						xc=$(this).getGridParam("colNames");
						xp=$(this).getGridParam("colModel");
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
						vx=$(this).parents(".LGKSRPTTABLE:first").find("#hd1 .btn_grouping");
						if(vx.children().length>0) plugin.settings2.groupField=vx.val();

						html="";
						html+="<option value=''>None</option>";
						$(plugin.gridColumns.colModel).each(function(k,v) {
								if(v.groupable==true) {
									title=plugin.gridColumns.colNames[k];
									vv=v.index;
									if(vv==null || vv.length<=0) return;
									if(plugin.settings2.groupField==title ||
										plugin.settings2.groupField==vv) {
										html+="<option value='"+vv+"' selected>"+title+"</option>";
									} else {
										html+="<option value='"+vv+"'>"+title+"</option>";
									}
								}
							});
						$(this).parents(".LGKSRPTTABLE:first").find("#hd1 .btn_grouping").html(html);
						
						footerData={};
						tempThis=$(this);
						$.each(plugin.gridColumns.colModel,function(k,v) {
								if(v.classes!=null && v.classes.length>0) {
									footerData[v.index]=0;
									rowIDs=tempThis.getDataIDs();
									
									clz=v.classes.split(" ");
									
									if(clz.indexOf("sum")>=0) {
										for(i=0;i<tempThis.find("tbody tr[role=row][id]").length;i++) {
											n=tempThis.getCell(rowIDs[i],v.index);
											if(!isNaN(n) && n!=false) {
												footerData[v.index]+=parseFloat(n);
											}
										}
									} else if(clz.indexOf("count")>=0) {
										for(i=0;i<tempThis.find("tbody tr[role=row][id]").length;i++) {
											footerData[v.index]+=1;
										}
									} else if(clz.indexOf("average")>=0) {
										for(i=0;i<tempThis.find("tbody tr[role=row][id]").length;i++) {
											n=tempThis.getCell(rowIDs[i],v.index);
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
						$(this).footerData('set', footerData);
					},
					cellsubmit:'remote',
					cellEdit:rptOptions.cellEdit,

					altRows:rptOptions.altRows,
					altclass:rptOptions.altclass,

					//caption:"JSON Example",
					emptyrecords:rptOptions.emptyrecords,
					loadtext:rptOptions.loadtext,
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
				});
			//$(gridID+"_grid_table").setGridWidth(rpt.width()-111,true);
			$(gridID+"_grid_table").navGrid(gridID+"_grid_pager",
					{view:false,edit:false,add:false,del:false},
					{},//add
					{},//edit
					{},//delete
					rptSearchOpts);

			if(rptOptions.filterToolbar) {
				$(gridID+"_grid_table").filterToolbar({stringResult: true,searchOnEnter : rptOptions.filterSearchOnEnter, defaultSearch:"bw"});
			}
		};
        plugin.init();
    }
})(jQuery);
