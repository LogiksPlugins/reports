var LGKSReportsInstances={};
var LGKSReports = (function() {
	var gridID=null;
	var rptType="grid";
	var gridGroupID=null;
	var hooksPostLoad=[];
	var reportUIRenderer={};
	var reportOptions=null;
	var rpt=this;
	var appendRecord=false;

	rpt.initPlain = function(gridid, rpttype) {
		if(rpttype==null || rpttype.length<=0) rpttype="grid";
		
		this.gridID=gridid;
		this.rptType=rpttype;
		this.hooksPostLoad=[];
		this.reportUIRenderer={};
		
		LGKSReportsInstances[this.gridID]=this;

		this.settings();
		
		return this;
	};
	rpt.init = function(gridid, rpttype) {
		if(rpttype==null || rpttype.length<=0) rpttype="grid";
		
		this.gridID=gridid;
		this.rptType=rpttype;
		this.hooksPostLoad=[];
		this.reportUIRenderer={};
		
		this.reportUIRenderer['grid']="rptGridUI";
		
		LGKSReportsInstances[this.gridID]=this;

		this.gridGroupID=rpt.getGrid().data("gkey");

		this.settings();
		//console.log(rpt.getGrid());
		
		//Autorefresh fields : Call reload
		rpt.getGrid().delegate("select.autorefreshReport[name],input.autorefreshReport[name][type=date]","change",function(e) {
			e.preventDefault();
			gridID=$(this).closest(".reportTable").data('rptkey');
			LGKSReports.getInstance(gridID).reloadDataGrid(this);
		});
		rpt.getGrid().delegate("input.autorefreshReport[name]","keyup",function(e) {
			e.preventDefault();
			if((e.keyCode==13 || e.keyCode==17)) {// && encodeURIComponent($(this).val())!=grid.data("q")
				gridID=$(this).closest(".reportTable").data('rptkey');
				LGKSReports.getInstance(gridID).reloadDataGrid(this);
			}
		});
		rpt.getGrid().delegate("select.cell-editor","change",function(e) {
			var name = $(this).attr("name");
			var value = $(this).val();
			var refid = $(this).closest(".tableRow").data("refid");

			processAJAXPostQuery(_service("reports", "updateFieldValue"),`gridid=${rpt.gridID}&dataField=${name}&dataVal=${value}&dataHash=${refid}`, function(ans) {
				if(ans.Data.msg!="done") {
					lgksToast(ans.Data.msg);
				} else {
					lgksToast("Successfully updated value");
				}
			}, "json");
		});
		

		//AutoConnect report fields from other parts of page : autoinitation
		$("body").delegate("div.forReport.autoConnect[for='"+this.gridID+"'] select.autorefreshReport[name],input.autorefreshReport[name][type=date]","change",function(e) {
			e.preventDefault();
			gridID=$(this).closest("div.forReport").attr('for');
			LGKSReports.getInstance(gridID).reloadDataGrid(this);
		});
		$(".filterCol select[value], .filterCol input[value]").each(function() {
			$(this).val($(this).attr('value'));
		});
    	$(".filterCol input[type=daterange]").each(function() {
		  	$(this).daterangepicker({
		            opens: 'left',
		            //showDropdowns: true,
		            autoUpdateInput: true,
		            //startDate: moment().subtract(365, 'days'), //moment().startOf('year'),
		            //endDate: moment(),
		            startDate: moment().startOf('month').subtract(1, 'month').startOf('month'),//moment().subtract(365, 'days'), //moment().startOf('year'),
		            endDate: moment(),
		            locale: {
		              format: 'DD/MM/YYYY'
		            },
		            minYear: (parseInt(moment().format('YYYY'),10)-100),
		            maxYear: (parseInt(moment().format('YYYY'),10)+10),
		            ranges: {
		               'Today': [moment(), moment()],
		               'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
		               'Last 7 Days': [moment().subtract(6, 'days'), moment()],
		               'Last 30 Days': [moment().subtract(29, 'days'), moment()],
		               'This Month': [moment().startOf('month'), moment().endOf('month')],
		               'Last Month': [moment().subtract(1, 'month').startOf('month'),moment().subtract(1, 'month').endOf('month')],
		               'Last 3 Month': [moment().startOf('month').subtract(3, 'month').startOf('month'),moment().subtract(1, 'month').endOf('month')],
		            }
		        }, function(start, end, label) {
		            //console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
		            //gridID=$(srcField).closest(".reportTable").data('rptkey');
			      	//LGKSReports.getInstance(gridID).reloadDataGrid(this);
					setTimeout(function() {
						rpt.reloadDataGrid();
					},200);
		        });

		  	$(this).on('cancel.daterangepicker', function(ev, picker) {
  					$(this).val('');
  					rpt.reloadDataGrid();
				});
		});
		$("body").delegate("div.forReport.autoConnect[for='"+this.gridID+"'] input.autorefreshReport[name]","keyup",function(e) {
			e.preventDefault();
			if((e.keyCode==13 || e.keyCode==17)) {// && encodeURIComponent($(this).val())!=grid.data("q")
				gridID=$(this).closest("div.forReport").attr('for');
				LGKSReports.getInstance(gridID).reloadDataGrid(this);
			}
		});
		
		$(".table-tools",rpt.getGrid()).delegate("input.searchfield[name=q]","keyup",function(e) {
			if((e.keyCode==13 || e.keyCode==17) && encodeURIComponent($(this).val())!=grid.data("q")) {
				gridID=$(this).closest(".reportTable").data('rptkey');
				LGKSReports.getInstance(gridID).reloadDataGrid(this);
			}
		});

		//Action buttons
		$(".table-tools",rpt.getGrid()).delegate("button[cmd],a[cmd]","click",function(e) {
			e.preventDefault();
			cmd=$(this).attr('cmd');
			gridID=$(this).closest(".reportTable").data('rptkey');
			row=$(this).closest(".dataItem");
			LGKSReports.getInstance(gridID).datagridAction(cmd,this,row);
		});
		$(".reportContainer",rpt.getGrid()).delegate("button[cmd],i[cmd],a[cmd]","click",function(e) {
			e.preventDefault();
			cmd=$(this).attr('cmd');
			gridID=$(this).closest(".reportTable").data('rptkey');
			row=$(this).closest(".dataItem");
			LGKSReports.getInstance(gridID).datagridAction(cmd,this,row);
		});

		//Checkbox RowSelector In DataTable
		$("table.dataTable .tableHead",rpt.getGrid()).delegate("th.action input[type=checkbox]","change",function(e) {
			gridID=$(this).closest(".reportTable").data('rptkey');
			grid=LGKSReports.getInstance(gridID).getGrid();

			$("table.dataTable tbody.tableBody .tableRow .tableColumn.rowSelector input[name=rowSelector]",grid).each(function() {
				this.checked=$("table .tableHead th.action input[type=checkbox]",grid).is(":checked");
				});
		});

		$(".reportContainer",rpt.getGrid()).delegate(".unilink[data-unilink]","click",function(e) {
			e.preventDefault();
			var unilink = $(this).data('unilink');
			var unilink_type = $(this).data('unilinktype');
			// alert(unilink+" "+unilink_type);

			rpt.datagridAction(unilink, this, $(this).closest("tr"));
		});

		//Column Filter
		columns=rpt.settings("columns-visible");
		if(columns!=null && columns.length>0) {
			$(".table-tools .columnFilter input.columnName",rpt.getGrid()).each(function() {
				name=$(this).attr("name");
				if(columns.indexOf(name)<0) {
					this.checked=false;
				} else {
					this.checked=true;
				}
			});
		}
		$(".table-tools .columnFilter",rpt.getGrid()).delegate("input.columnName","change",function(e) {
			var maxCols = $(this).closest(".reportTable").data("maxcols");
			if(maxCols!=null && !isNaN(maxCols) && maxCols>0) {
				if($(".table-tools .columnFilter input.columnName:checked",$(this).closest(".reportTable")).length>maxCols) {

					if(typeof lgksToast == "function") lgksToast(`Only ${maxCols} columns can be seen at one time.`);

					var columns1=rpt.settings("columns-visible");
					$(".table-tools .columnFilter input.columnName",$(this).closest(".reportTable")).each(function() {
						name=$(this).attr("name");
						if(columns1.indexOf(name)<0) {
							this.checked=false;
						} else {
							this.checked=true;
						}
					});

					return false;
				}
			}

			gridID=$(this).closest(".reportTable").data('rptkey');
			updateGridUI(gridID);
		});
		$(".table-tools .columnFilter",rpt.getGrid()).delegate(".allColumns","change",function() {
			srcSelector=this;
			UL=$(this).closest("ul");

			var maxCols = $(this).closest(".reportTable").data("maxcols");
			if(maxCols!=null && !isNaN(maxCols) && maxCols>0) {
				UL.find("li.colcheckbox input").each(function() {
				    this.checked=false;
			  	});
				if(srcSelector.checked) {
					UL.find("li.colcheckbox input").slice(0,maxCols).each(function() {
					    this.checked=true;
				  	});
				} else {
					UL.find("li.colcheckbox input").slice(0,1).each(function() {
					    this.checked=true;
				  	});
				}
			} else {
				UL.find("li.colcheckbox input").each(function(k) {
				  	this.checked=srcSelector.checked;
				});
				if(UL.find("li.colcheckbox input:checked").length<=0) {
				  UL.find("li.colcheckbox input").slice(0,5).each(function() {
				    this.checked=true;
				  });
				}
			}
			
			gridID=$(this).closest(".reportTable").data('rptkey');
		  	updateGridUI(gridID);
      	});
		
		if(typeof $.fn.resizable == "function")  {
			$("thead.tableHead th:not(:first-child).resizable",rpt.getGrid()).resizable({
			  	handles: "e",
			  	resize: function (event, ui) {
				    event.preventDefault();
				    var sizerID = "#" + $(event.target).attr("id") + "-sizer";
				    $(sizerID).width(ui.size.width);
			  	}
	      	});
		}

		//Row Filters
		rowFilter=rpt.settings("filterbar");
		if(rowFilter!=null && rowFilter==true) {
			$(".dataTable .tableFilter",rpt.getGrid()).removeClass("hidden");
			$(".table-tools button[cmd=filterbar]",rpt.getGrid()).addClass("active");
		} else {
			rpt.settings("filterbar",false);
			$(".dataTable .tableFilter",rpt.getGrid()).addClass("hidden");
		}

		//Sorting
		sortKey=rpt.settings("sort");
		if(sortKey!=null) {
			sortKey=sortKey.split(" ");
			tdSort=$(".dataTable thead.tableHead tr th[data-key='"+sortKey[0]+"'] .colSort");
			if(tdSort.length>0) {
				if(sortKey[1]=="ASC") {
					$(tdSort[0]).removeClass("sorting").addClass("sorting_asc");
				} else {
					$(tdSort[0]).removeClass("sorting").addClass("sorting_desc");
				}
			}
		} else {
			tdSort=$(".dataTable thead.tableHead tr th .colSort");
			if(tdSort.length>0) {
				colKey=$(tdSort[0]).parent().data('key');
				rpt.settings("sort",colKey);
				$(tdSort[0]).removeClass("sorting").addClass("sorting_desc");
			}
		}
		$(".dataTable thead.tableHead").delegate("tr th","click",function() {
			colSort=$(this).find(".colSort");
			if(colSort.length>0) {
				colKey=$(colSort[0]).parent().data('key');

				if(colSort.hasClass("sorting_desc")) {
					$(colSort[0]).removeClass("sorting").removeClass("sorting_desc").addClass("sorting_asc");
					rpt.settings("sort",colKey+" ASC");
				} else if(colSort.hasClass("sorting_asc")) {
					$(colSort[0]).removeClass("sorting").removeClass("sorting_asc").addClass("sorting_desc");
					rpt.settings("sort",colKey+" DESC");
				} else {
					$(".dataTable thead.tableHead .colSort").removeClass("sorting_desc").removeClass("sorting_asc").addClass("sorting");
					$(colSort[0]).removeClass("sorting").removeClass("sorting_desc").addClass("sorting_asc");
					rpt.settings("sort",colKey+" ASC");
				}

				gridID=$(this).closest(".reportTable").data('rptkey');
				LGKSReports.getInstance(gridID).reloadDataGrid(this);
			}
		});
		$(".dataTable tbody").delegate("td.moreContent","click",function() {
			if($(this).find(".contentBox.hidden").length>0) {
				lgksAlert($(this).find(".contentBox.hidden").html(),"Content Preview!");
			}
		});
		
		$(".dataTable tbody").delegate("td.imagebox img","click",function() {
			if($(this).attr("src").length>1) {
				lgksAlert("<img src='"+$(this).attr("src")+"' class='img-responsive' />","Picture Preview!");
			}
		});
		
		$(".dataTable tbody").delegate("td.embed","click",function() {
			if($(this).find(".contentBox").length>0) {
				lgksAlert($(this).find(".contentBox").html());
			}
		});

		$(".btn-reports-toggle").each(function() {
			if($(this).data("data-toggle")==null) {
				$(this).click(function() {
					$(this).parent().toggleClass('open');
				});
				$(this).data("data-toggle","open");
			}
		});

		//Pagination setup
		recordsPerPage=rpt.settings("RecordsPerPage");
		if(recordsPerPage!=null && !isNaN(recordsPerPage)) {
			rpt.getGrid().find("select.perPageCounter").val(recordsPerPage);
		} else {
			rpt.settings("RecordsPerPage",rpt.getGrid().find("select.perPageCounter").val());
		}
		
		recordAppend=rpt.settings("recordAppend");
		if(recordAppend!=null && !isNaN(recordAppend)) {
			rpt.appendRecord=recordAppend;
		}
		if(rpt.appendRecord) {
			rpt.getGrid().find("button[cmd=stayPut]").addClass("btn-info");
			rpt.getGrid().find("button[cmd=prevPage]").hide();
			rpt.getGrid().find("button[cmd=firstPage]").hide();
		}

		//this.loadDataGrid();
		
		$(document).on("hidden.bs.modal", ".bootbox.modal", function (e) {
					if($(e.currentTarget).hasClass("reportPopup")) {
						rpt.refetchCurrentDataGrid();
					}
			});
		
		return this;
	};

	rpt.getGrid = function() {
		return $("#RPT-"+this.gridID);
	};
	rpt.getGridTable = function() {
		return $("tbody.tableBody","#RPT-"+this.gridID);
	};
	
	rpt.loadDataGrid = function() {
		if(this.reportUIRenderer[this.rptType]!=null && typeof this.reportUIRenderer[this.rptType]=="function") {
			this.reportUIRenderer[this.rptType](this.gridID, this);
		} else {
			return this.loadGridTable();
		}
	};
	
	rpt.loadGridTable = function() {
		grid=rpt.getGrid();
		gridBody=rpt.getGridTable();
		gridID=grid.data('rptkey');

		if(grid.data("page")==grid.data("current") && grid.data("page")!=null && grid.data("page")!=0) {
			if(typeof lgksToast=="function") lgksToast("All the records are loaded");
			return false;
		}

		if(rpt.appendRecord) {
			gridBody.append('<tr><td class="ajaxloading" colspan=10000></td></tr>');
		} else {
			gridBody.html('<tr><td class="ajaxloading" colspan=10000></td></tr>');
		}

		rpt.fetchReportData("html",function(txt) {
				grid=rpt.getGrid();
				gridBody=rpt.getGridTable();

				gridBody.find(".ajaxloading").closest("tr").detach();

				if(rpt.appendRecord) {
					gridBody.append(txt);
				} else {
					gridBody.html(txt);
				}

				info=gridBody.find(".gridDataInfo");
				if(info.length>0) {
					limit=parseInt(info.find("td.limit").text());
					index=parseInt(info.find("td.index").text());
					last=parseInt(info.find("td.last").text());
					max=parseInt(info.find("td.max").text());

					if(last>max) last = max;
					
					rpt.updateReportMeta(limit, index, last, max);
					
					if(max>=0) {
						grid.find(".displayCounter .recordsIndex").text(index);
						grid.find(".displayCounter .recordsUpto").text(last);
						grid.find(".displayCounter .recordsMax").text(max);
						grid.find(".displayCounter").show();
					} else {
						grid.find(".displayCounter").hide();
					}
				} else {
					grid.find(".displayCounter").hide();
				}

				rpt.postDataPopulate(rpt.gridID);
// 				rpt.updateColumnsUI(grid);

				//grid.find('tfoot.tableFoot .displayCounter .recordsIndex').html("");
				//grid.find('tfoot.tableFoot .displayCounter .recordsUpto').html("");
				//grid.find('tfoot.tableFoot .displayCounter .recordsMax').html("");
		});
	};
	
	rpt.postDataPopulate = function(gridID) {
		$(rpt.hooksPostLoad).each(function(kx,func) {
					if(typeof func=="function") {
						func(gridID);
					}
				});
	};
	
	rpt.updateReportMeta = function(limit, index, last, max) {
		if(last>max) {
			last=max;
			grid.data("max","YES");
		} else {
			grid.data("max",null);
		}

		if($(grid).data("page")!=null) {
			page=$(grid).data("page");
		} else {
			page=0;
		}
		
		grid.data("page",page);
		grid.data("current",page);
		
		if($('*[cmd="showMoreRecords"]').length>0) {
			$('*[cmd="showMoreRecords"]').attr("title","Showing "+last+" of "+max+" records");
		}
	};
	
	rpt.fetchReportData = function(format, callBack, addonParams) {
		if(addonParams==null) addonParams = {};
		grid=rpt.getGrid();
		
		q=[];

		$.each(addonParams, function(k,v) {
			q.push(k+"="+encodeURIComponent(v));
		});

		//For custom fields in : custom bar
		grid.find(".filterfield[name]").each(function() {
				name=$(this).attr('name');
				if(this.value!=null && this.value.length>0) {
					if(typeof $(this).val()=="object") {
						$.each($(this).val(),function(k,v) {q.push("filter["+name+"]["+k+"]"+"="+encodeURIComponent(v));});
					} else {
						q.push("filter["+name+"]"+"="+encodeURIComponent($(this).val()));
					}
				}
			});
		grid.find(".filterrule[name]").each(function() {
				name=$(this).attr('name');
				if(this.value!=null && this.value.length>0) {
					if(typeof $(this).val()=="object") {
						$.each($(this).val(),function(k,v) {q.push("filter["+name+"]["+k+"]"+"="+encodeURIComponent(v));});
					} else {
						q.push("filterrule["+name+"]"+"="+encodeURIComponent($(this).val()));
					}
				}
			});
		
		//For fields in : Filter Bar
		//.tableFilter:not(.hidden) .filterCol:not(.hidden) 
		filterCount = 0;
		grid.find(".filterBarField[name]").each(function() {
				name=$(this).attr('name');
				if(this.value!=null && this.value.length>0) {
					q.push("filter["+name+"]"+"="+encodeURIComponent(this.value));
					filterCount++;
				}
			});
		$(".report-sidebar .reportFilters[name]").each(function() {
			name=$(this).attr('name');
			if(this.value!=null && this.value.length>0) {
				if(typeof $(this).val() == "object") {
					q.push("filter["+name+"]"+"="+encodeURIComponent($(this).val().join(",")));
				} else {
					q.push("filter["+name+"]"+"="+encodeURIComponent(this.value));
				}
		        filterCount++;
			}
		});

		if(grid.find(".date_filter").length>0) {
			grid.find(".date_filter").find("input[name]").each(function() {
				name=$(this).attr('name');
				if(this.value!=null && this.value.length>0) {
					q.push("date_filter["+name+"]"+"="+encodeURIComponent(this.value));
				}
			});
		}
		
		if(filterCount>0) {
			grid.find(".table-tools .control-primebar *[cmd=filterbar] .glyphicon").addClass("badgeIcon");
		} else {
			grid.find(".table-tools .control-primebar *[cmd=filterbar] .glyphicon").removeClass("badgeIcon");
		}
		
		//For custom fields in : any other part of page.
		$("body").find("div.forReport[for='"+this.gridID+"']").find(".filterfield[name]").each(function() {
				name=$(this).attr('name');
				if(this.value!=null && this.value.length>0) {
					if(typeof $(this).val()=="object") {
						$.each($(this).val(),function(k,v) {q.push("filter["+name+"]["+k+"]"+"="+encodeURIComponent(v));});
					} else {
						q.push("filter["+name+"]"+"="+encodeURIComponent($(this).val()));
					}
				}
			});

		if($("input.date_field[name=date_col]",grid).length>0 && $("input.date_field[name=date_col]",grid).val()!=null) {
			var dateValue = $("input.date_field[name=date_col]",grid).val();

			// q.push("date_filter[start_date]="+encodeURIComponent(dateValue[0]));
			// q.push("date_filter[end_date]="+encodeURIComponent(dateValue[1]));
		}

		//For Search Bar
		if($("input.searchfield[name=q]",grid).length>0 && $("input.searchfield[name=q]",grid).val()!=null && $("input.searchfield[name=q]",grid).val().length>0) {
			q.push("search[q]="+encodeURIComponent($("input.searchfield[name=q]",grid).val()));
			grid.data("q",encodeURIComponent($("input.searchfield[name=q]",grid).val()));
		} else {
			grid.data("q","");
		}

		//For Sorting Purpose
		sortBy=$(".dataTable thead.tableHead tr th .colSort:not(.sorting)");
		if(sortBy.length>0) {
			sortCol=$(".dataTable thead.tableHead tr th .colSort:not(.sorting)").closest("th").data("key");
			if(sortBy.hasClass('sorting_desc')) {
				q.push("orderby="+sortCol+" DESC");
				rpt.settings("sort",sortCol+" DESC");
			} else {
				q.push("orderby="+sortCol+" ASC");
				rpt.settings("sort",sortCol+" ASC");
			}
		} else if($(".table-tools input.colSort:checked").length>0) {
		      sortCol=$(".table-tools input.colSort:checked").val();
		      q.push("orderby="+sortCol);
		      rpt.settings("sort",sortCol);
		} else if($(".table-tools select.colSort").length>0) {
		      sortCol=$(".table-tools select.colSort").val();
		      q.push("orderby="+sortCol);
		      rpt.settings("sort",sortCol);
		}

		cols=[];
		$("thead.tableHead tr th:visible[data-key]",grid).each(function() {cols.push($(this).data("key"));});

		lx=_service("reports","fetchGrid",format)+"&gridid="+this.gridID;

		//Page Counter and pagination
		if($(grid).find("select.perPageCounter").length>0) {
			lx+="&limit="+$(grid).find("select.perPageCounter").val();
			rpt.settings("RecordsPerPage",$(grid).find("select.perPageCounter").val());
		}
		if($(grid).data("page")!=null) {
			lx+="&page="+(parseInt($(grid).data("page")));
		} else {
			lx+="&page=0";
		}
		q.push("cols="+cols.join(","));

		processAJAXPostQuery(lx,q.join("&"),function(txt) {
			callBack(txt);
		});
	};
	
	rpt.refetchCurrentDataGrid = function() {
		grid.data("current",null);
		rpt.loadDataGrid(true);
	};

	rpt.reloadDataGrid = function() {
		grid=rpt.getGrid();
		gridBody1=rpt.getGridTable();
		gridBody2=$(".reportBoard","#RPT-"+rpt.gridID);

		gridBody1.html("");
		gridBody2.html("");
		
		grid.data("page",null);

		rpt.loadDataGrid(true);
	};

	rpt.datagridAction = function(cmd, src, recordRow) {
		cmdOriginal=cmd;
		cmd=cmd.split("@");
		cmd=cmd[0];

		var dataObj = {"hashid": $(src).closest(".dataItem").data("hash"), "refid": $(src).closest(".dataItem").data("refid")};
		if($(src).closest(".dataItem").find("td,th").length>0) {
			$(src).closest(".dataItem").find("td,th").each(function(k,v) {
			    dataObj[$(this).data("key")] = $(this).data("value");
			})
		}

		params = $(src).attr("params");
        if(params==null || params.length<=0) params = "{}";
        a = "{}";
        try {
            params = JSON.parse(params);
        } catch(e) {
            params = {};
        }
        params = $.param(params);
        if(params.length>0) params = "&"+params;

		switch(cmd) {
			case "stayPut":
				$(src).toggleClass("btn-info");
				rpt.appendRecord=$(src).hasClass("btn-info");
				rpt.settings("recordAppend",rpt.appendRecord);

				if(rpt.appendRecord) {
					rpt.getGrid().find("button[cmd=prevPage]").hide();
					rpt.getGrid().find("button[cmd=firstPage]").hide();
				} else {
					rpt.getGrid().find("button[cmd=prevPage]").show();
					rpt.getGrid().find("button[cmd=firstPage]").show();
				}
			break;
			case "nextPage":
				grid=rpt.getGrid();

				nx=$(grid).data("page");
				max=$(grid).data("max");
				if(nx==null) {
					nx=0;
				} else if(max==null) {
					nx++;
				}
				$(grid).data("page",nx);
				rpt.loadDataGrid();
			break;
			case "prevPage":
				grid=rpt.getGrid();
				
				nx=$(grid).data("page");
				if(nx==0) {
					nx=0;
				} else {
					nx--;
				}
				$(grid).data("page",nx);
				rpt.loadDataGrid();
			break;
			case "firstPage":
				$(grid).data("page",0);
				grid.data("max",null);
				rpt.loadDataGrid();
			break;
			case "refresh":
				rpt.reloadDataGrid();
			break;
			case "filterbar":
				$(src).toggleClass("active");
				rpt.getGrid().find("thead.tableFilter").toggleClass("hidden");
				rpt.settings("filterbar",(!rpt.getGrid().find("thead.tableFilter").hasClass("hidden")));
			break;
			case "report:print":
				window.print();
			break;
			case "report:exportcsv":
				q=[];
				$("table.dataTable thead.tableHead",rpt.getGrid()).find('tr').each(function() {
					z=[];
					$("td,th",this).each(function(k,v) {
						if($(v).hasClass('rowSelector') || $(v).hasClass('hidden') || $(v).hasClass('action') || $(v).hasClass('noprint')) return;
						z.push("\""+$(v).text().replace("\"","`")+"\"");
					});
					q.push(z.join(","));
				});
				$("table.dataTable tbody.tableBody",rpt.getGrid()).find('tr').each(function() {
					if($(this).hasClass("hidden")) return;
					z=[];
					$("td",this).each(function(k,v) {
						if($(v).hasClass('rowSelector') || $(v).hasClass('hidden') || $(v).hasClass('action') || $(v).hasClass('noprint')) return;
						if($(v).find("input[type=checkbox]").length>0) {
							if($(v).is(":checked")) z.push("\"true\""); 
							else z.push("\"false\"");
						} else {
							z.push("\""+$(v).text().replace("\"","`")+"\"");
						}
					});
					q.push(z.join(","));
				});
				blob = new Blob([q.join("\n")], {type: "text/plain;charset=utf-8"});
				saveAs(blob, "export.csv",true);
			break;
			case "report:exportcsvxls":
				q=[];
				$("table.dataTable thead.tableHead",rpt.getGrid()).find('tr').each(function() {
					z=[];
					$("td,th",this).each(function(k,v) {
						if($(v).hasClass('rowSelector') || $(v).hasClass('hidden') || $(v).hasClass('action') || $(v).hasClass('noprint')) return;
						z.push("\""+$(v).text().replace("\"","`")+"\"");
					});
					q.push(z.join(","));
				});
				$("table.dataTable tbody.tableBody",rpt.getGrid()).find('tr').each(function() {
					if($(this).hasClass("hidden")) return;
					z=[];
					$("td",this).each(function(k,v) {
						if($(v).hasClass('rowSelector') || $(v).hasClass('hidden') || $(v).hasClass('action') || $(v).hasClass('noprint')) return;
						if($(v).find("input[type=checkbox]").length>0) {
							if($(v).is(":checked")) z.push("\"true\""); 
							else z.push("\"false\"");
						} else {
							z.push("\""+$(v).text().replace("\"","`")+"\"");
						}
					});
					q.push(z.join(";"));
				});
				blob = new Blob([q.join("\n")], {type: "text/plain;charset=utf-8"});
				saveAs(blob, "export.csv",true);
			break;
			case "report:exportxml":
				q=['<?xml version="1.0" encoding="utf-8"?>\n\n','<table name="export">\n'];
				$("table.dataTable tbody.tableBody",rpt.getGrid()).find('tr').each(function() {
					if($(this).hasClass("hidden")) return;
					z=[];
					$("td",this).each(function(k,v) {
						if($(this).hasClass("hidden")) return;
						nm=$(v).data('key');
						if($(v).hasClass('rowSelector') || $(v).hasClass('hidden') || $(v).hasClass('action') || $(v).hasClass('noprint')) return;
						if($(v).find("input[type=checkbox]").length>0) {
							if($(v).is(":checked")) z.push("\t\t<col name='"+nm+"'>true</col>\n"); 
							else z.push("\t\t<col name='"+nm+"'>false</col>\n"); 
						} else {
							z.push("\t\t<col name='"+nm+"'>"+$(v).text().replace("\"","`")+"</col>\n"); 
						}
					});
					q.push("\t<row name='"+$(this).data('hash')+"'>\n"+z.join("")+"\t</row>\n");
				});
				q.push('</table>');
				blob = new Blob([q.join("")], {type: "application/xml;charset=utf-8"});
				saveAs(blob, "export.xml",true);
			break;
			case "report:exporthtml":case "report:exporthtm":
				q=[];
				q.push("<style>");
				q.push(".exportTable {margin:0px;padding:0px;width:100%;font-size:10px;font-family:Arial;font-weight:normal;color:#000000;}");
				q.push(".exportTable td, .exportTable th {vertical-align:middle;border:1px solid #AAA;text-align:left;padding:7px;margin:0px;}");
				q.push(".exportTable thead tr th {background:#F9F9F9;}");
				q.push("</style>");

				q.push('<table class="exportTable" cellspacing=0px>\n');
				q.push("<thead>\n");
				$("table.dataTable thead.tableHead",rpt.getGrid()).find('tr').each(function() {
					z=[];
					$("th",this).each(function(k,v) {
						nm=$(v).data('key');
						if($(v).hasClass('rowSelector') || $(v).hasClass('hidden') || $(v).hasClass('action') || $(v).hasClass('noprint')) return;
						if($(v).find("input[type=checkbox]").length>0) {
							if($(v).is(":checked")) z.push("\t\t<th name='"+nm+"'>true</th>\n"); 
							else z.push("\t\t<th name='"+nm+"'>false</th>\n"); 
						} else {
							z.push("\t\t<th name='"+nm+"'>"+$(v).text().replace("\"","`")+"</th>\n"); 
						}
					});
					q.push("\t<tr>\n"+z.join("")+"\t</tr>\n");
				});
				q.push("</thead>\n");
				q.push("<tbody>\n");
				$("table.dataTable tbody.tableBody",rpt.getGrid()).find('tr').each(function() {
					if($(this).hasClass("hidden")) return;
					z=[];
					$("td",this).each(function(k,v) {
						nm=$(v).data('key');
						if($(v).hasClass('rowSelector') || $(v).hasClass('hidden') || $(v).hasClass('action') || $(v).hasClass('noprint')) return;
						if($(v).find("input[type=checkbox]").length>0) {
							if($(v).is(":checked")) z.push("\t\t<td name='"+nm+"'>true</td>\n"); 
							else z.push("\t\t<td name='"+nm+"'>false</td>\n"); 
						} else {
							z.push("\t\t<td name='"+nm+"'>"+$(v).text().replace("\"","`")+"</td>\n"); 
						}
					});
					q.push("\t<tr name='"+$(this).data('hash')+"'>\n"+z.join("")+"\t</tr>\n");
				});
				q.push("</tbody>\n");
				q.push('</table>');
				blob = new Blob([q.join("")], {type: "application/xml;charset=utf-8"});
				saveAs(blob, "export.html",true);
			break;
			case "report:exportimg":
				$(".reportholder .reportActions").closest(".btn-group").removeClass("open").find(".btn.dropdown-toggle").removeClass("dropdown-toggle").removeAttr("data-toggle");
				html2canvas(document.body, {
					  onrendered: function(canvas) {
							window.open().document.body.appendChild(canvas);
							window.location.reload();
					  }
					});
			break;
			case "report:exportpdf":
				window.open(_service("reports","export")+"&type=pdf&gridid="+this.gridID);
			break;
			case "report:exportcsvdown":
				window.open(_service("reports","export")+"&type=csv&gridid="+this.gridID);
			break;
			case "report:email1":
				showLoader();
				lgksOverlayFrame(_service("reports","export")+"&type=email1&gridid="+this.gridID,"Email Report",function() {
						hideLoader();
					},{"className":"overlayBox reportPopup"});
			break;
			case "report:email2":
				showLoader();
				lgksOverlayFrame(_service("reports","export")+"&type=email2&gridid="+this.gridID,"Email Report",function() {
						hideLoader();
					},{"className":"overlayBox reportPopup"});
			break;
			case "forms":case "reports":case "infoview":
				hash=$(src).closest(".tableRow").data('hash');
				refid=$(src).closest(".tableRow").data('refid');
				gkey=$(src).closest(".reportTable").data('gkey');
				if(gkey==null) return;
				title=$(src).text().trim();
				if(title==null || title.trim().length<=0) {
					title=$(src).attr("title");
				}
				if(title==null || title.trim().length<=0) {
					title="Dialog";
				}
				
				cmdX=cmdOriginal.split("@");
				if(cmdX[1]!=null) {
					//cmdX[1]=cmdX[1].replace("{hashid}",hash).replace("{gkey}",gkey);
					cmdX[1]=cmdX[1].replace("{hashid}",hash).replace("{refid}",refid).replace("{gkey}",gkey);
					
					showLoader();
					lgksOverlayURL(_link("popup/"+cmd+"/"+cmdX[1])+params,title,function() {
							hideLoader();
						},{"className":"overlayBox reportPopup"});
				}
			break;
			case "page":
				hash=$(src).closest(".tableRow").data('hash');
				refid=$(src).closest(".tableRow").data('refid');
				gkey=$(src).closest(".reportTable").data('gkey');
				if(gkey==null) return;
				title=$(src).text().trim();
				if(title==null || title.trim().length<=0) {
					title=$(src).attr("title");
				}
				if(title==null || title.trim().length<=0) {
					title="Dialog";
				}
				
				cmdX=cmdOriginal.split("@");
				if(cmdX[1]!=null) {
					//cmdX[1]=cmdX[1].replace("{hashid}",hash).replace("{gkey}",gkey);
					cmdX[1]=cmdX[1].replace("{hashid}",hash).replace("{refid}",refid).replace("{gkey}",gkey);
					window.location=_link(cmdX[1])+params;
				}
				break;
			case "module":case "popup":
				hash=$(src).closest(".tableRow").data('hash');
				refid=$(src).closest(".tableRow").data('refid');
				gkey=$(src).closest(".reportTable").data('gkey');
				if(gkey==null) return;
				title=$(src).text().trim();
				if(title==null || title.trim().length<=0) {
					title=$(src).attr("title");
				}
				if(title==null || title.trim().length<=0) {
					title="Dialog";
				}
				
				cmdX=cmdOriginal.split("@");
				if(cmdX[1]!=null) {
					//cmdX[1]=cmdX[1].replace("{hashid}",hash).replace("{gkey}",gkey);
					cmdX[1]=cmdX[1].replace("{hashid}",hash).replace("{refid}",refid).replace("{gkey}",gkey);
					
					if(cmd=="module" || cmd=="modules") {
						top.openLinkFrame(title,_link("modules/"+cmdX[1])+params,true);
					} else {
						showLoader();
						lgksOverlayURL(_link("popup/"+cmdX[1])+params,title,function() {
								hideLoader();
							},{"className":"overlayBox reportPopup"});
					}
				}
			break;
			case "ui":
				cmdX=cmdOriginal.split("@");
				hash=$(src).closest(".tableRow").data('hash');
				refid=$(src).closest(".tableRow").data('refid');
				gkey=$(src).closest(".reportTable").data('gkey');
				if(cmdX[1]!=null) {
					//cmdX[1]=cmdX[1].replace("{hashid}",hash).replace("{gkey}",gkey);
					cmdX[1]=cmdX[1].replace("{hashid}",hash).replace("{refid}",refid).replace("{gkey}",gkey);
					cmd=cmdX[1];
					gkey=$(src).closest(".reportTable").data('gkey');
					if(gkey==null) return;
					$.cookie("RPTVIEW-"+gkey,cmd,{ path: '/' });
					window.location.reload();
				}
			break;
			default:
				if(typeof window[cmd]=="function") {
					window[cmd](recordRow, rpt, src);
				} else {
					console.warn("Report CMD not found : "+cmd);
				}
		}
	}
	
	rpt.addRenderer = function(name,func) {
		rpt.reportUIRenderer[name]=func;
		return rpt;
	}
	
	rpt.addListener = function(func,type) {
		if(type==null || type=="postload") {
			if(rpt.hooksPostLoad==null) rpt.hooksPostLoad=[];
			rpt.hooksPostLoad.push(func);
			return true;
		}
		return false;
	}

	rpt.selectedRows = function() {
		q=[];
// 		$("table.dataTable tbody.tableBody .tableRow .tableColumn.rowSelector input[name=rowSelector]:checked",rpt.getGridTable()).each(function() {
		$(".reportContainer input[name=rowSelector]:checked",rpt.getGridTable()).each(function() {
				q.push($(this).closest("tr")[0]);
			});
		return q;
	};

	rpt.settings = function(keyName,value,defaultValue) {
		settingsKey="RPT-"+this.gridGroupID;//this.gridID;
		//console.log(settingsKey+" "+keyName);
		if(keyName==null) {
			this.reportOptions=window.localStorage.getItem(settingsKey);
			if(this.reportOptions!=null && this.reportOptions.length>2) {
				this.reportOptions=JSON.parse(this.reportOptions);
			} else {
				this.reportOptions={};
				window.localStorage.setItem(settingsKey,"{}");
			}
			return true;
		}
		if(value==null) {
			if(this.reportOptions[keyName]==null) {
				value=defaultValue;
			} else {
				return this.reportOptions[keyName];
			}
		}
		this.reportOptions[keyName]=value;
		window.localStorage.setItem(settingsKey,JSON.stringify(this.reportOptions));
		return this.reportOptions[keyName];
	}
});

LGKSReports.getInstance = function(gridTable) {
    return LGKSReportsInstances[gridTable];
};


function showMoreRecords(src,rpt) {
	grid=rpt.getGrid();

	nx=$(grid).data("page");
	max=$(grid).data("max");
	if(nx==null) {
		nx=0;
	} else if(max==null) {
		nx++;
	}
	$(grid).data("page",nx);
	rpt.loadDataGrid();
}

function goBackOnePage() {
	window.history.back();
}
