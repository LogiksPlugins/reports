var LGKSReportsInstances={};
var LGKSReports = (function() {
	var gridID=null;
	var postLoad={};
	var reportOptions=null;
	var rpt=this;

	rpt.initPlain = function(gridid) {
		this.gridID=gridid;
		LGKSReportsInstances[this.gridID]=this;

		this.settings();
	};
	rpt.init = function(gridid) {
		this.gridID=gridid;
		LGKSReportsInstances[this.gridID]=this;

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

		//AutoConnect report fields from other parts of page : autoinitation
		$("body").find("div.forReport.autoConnect[for='"+this.gridID+"']").delegate("select.autorefreshReport[name],input.autorefreshReport[name][type=date]","change",function(e) {
			e.preventDefault();
			gridID=$(this).closest("div.forReport").attr('for');
			LGKSReports.getInstance(gridID).reloadDataGrid(this);
		});
		$("body").find("div.forReport.autoConnect[for='"+this.gridID+"']").delegate("input.autorefreshReport[name]","keyup",function(e) {
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
			LGKSReports.getInstance(gridID).datagridAction(cmd,this);
		});

		//Checkbox RowSelector In DataTable
		$("table.dataTable .tableHead",rpt.getGrid()).delegate("th.action input[type=checkbox]","change",function(e) {
			gridID=$(this).closest(".reportTable").data('rptkey');
			grid=LGKSReports.getInstance(gridID).getGrid();

			$("table.dataTable tbody.tableBody .tableRow .tableColumn.rowSelector input[name=rowSelector]",grid).each(function() {
				this.checked=$("table .tableHead th.action input[type=checkbox]",grid).is(":checked");
				});
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
			gridID=$(this).closest(".reportTable").data('rptkey');
			LGKSReports.getInstance(gridID).updateColumnsUI(this);
		});

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

		//Pagination setup
		recordsPerPage=rpt.settings("RecordsPerPage");
		if(recordsPerPage!=null && !isNaN(recordsPerPage)) {
			rpt.getGrid().find("select.perPageCounter").val(recordsPerPage);
		} else {
			rpt.settings("RecordsPerPage",rpt.getGrid().find("select.perPageCounter").val());
		}
		
		this.loadDataGrid();
	};

	rpt.getGrid = function() {
		return $("#RPT-"+this.gridID);
	};
	rpt.getGridTable = function() {
		return $("tbody.tableBody","#RPT-"+this.gridID);
	};

	rpt.loadDataGrid = function() {
		grid=rpt.getGrid();
		gridBody=rpt.getGridTable();
		gridID=grid.data('rptkey');
		gridBody.append('<tr><td class="ajaxloading" colspan=10000></td></tr>');

		q=[];
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

		//For fields in : Filter Bar
		grid.find(".tableFilter:not(.hidden) .filterCol:not(.hidden) .filterBarField[name]").each(function() {
				name=$(this).attr('name');
				if(this.value!=null && this.value.length>0) {
					q.push("filter["+name+"]"+"="+encodeURIComponent(this.value));
				}
			});
		
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
			} else {
				q.push("orderby="+sortCol+" ASC");
			}
		}

		lx=_service("reports","fetchGrid","html")+"&gridid="+this.gridID;

		//Page Counter and pagination
		if($(grid).find("select.perPageCounter").length>0) {
			lx+="&limit="+$(grid).find("select.perPageCounter").val();
			rpt.settings("RecordsPerPage",$(grid).find("select.perPageCounter").val());
		}
		if($(grid).data("page")!=null) {
			lx+="&page="+(parseInt($(grid).data("page"))+1);
		} else {
			lx+="&page=0";
		}

		processAJAXPostQuery(lx,q.join("&"),function(txt) {
			grid=rpt.getGrid();
			gridBody=rpt.getGridTable();

			gridBody.find(".ajaxloading").closest("tr").detach();
			gridBody.append(txt);

			rpt.updateColumnsUI(grid);

			if($(grid).data("page")!=null) {
				page=$(grid).data("page");
			} else {
				page=1;
			}
			grid.data("page",page);

			//grid.find('tfoot.tableFoot .displayCounter .recordsIndex').html("");
			//grid.find('tfoot.tableFoot .displayCounter .recordsUpto').html("");
			//grid.find('tfoot.tableFoot .displayCounter .recordsMax').html("");

			$(rpt.postLoad).each(function(kx,func) {
				if(typeof func=="function") {
					func(rpt.gridID);
				}
			});
		});
	};

	rpt.reloadDataGrid = function() {
		grid=rpt.getGrid();
		gridBody=rpt.getGridTable();

		gridBody.html("");
		grid.data("page",null);

		rpt.loadDataGrid(true);
	};

	rpt.datagridAction = function(cmd, src) {
		switch(cmd) {
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
				$("table.dataTable tbody.tableBody",rpt.getGrid()).find('tr').each(function() {
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
				blob = new Blob([q.join("\n\r")], {type: "text/plain;charset=utf-8"});
				saveAs(blob, "export.csv",true);
			break;
			case "report:exportcsvxls":
				q=[];
				$("table.dataTable tbody.tableBody",rpt.getGrid()).find('tr').each(function() {
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
				blob = new Blob([q.join("\n\r")], {type: "text/plain;charset=utf-8"});
				saveAs(blob, "export.csv",true);
			break;
			case "report:exportxml":
				q=['<?xml version="1.0" encoding="utf-8"?>\n\n','<table name="export">\n'];
				$("table.dataTable tbody.tableBody",rpt.getGrid()).find('tr').each(function() {
					z=[];
					$("td",this).each(function(k,v) {
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
			case "report:exportxls":
				window.open(_service("reports","export")+"&type=xlsgridid="+this.gridID);
			break;
			case "report:email":case "report:exportemail":
				
			break;
			default:
				if(typeof window[cmd]=="function") {
					window[cmd](rpt);
				} else {
					console.warn("Report CMD not found : "+cmd);
				}
		}
	}

	rpt.updateColumnsUI = function() {
		grid=rpt.getGrid();
		//console.log("RPT-"+this.gridID);

		qCols=[];
		$(".table-tools .columnFilter input.columnName",grid).each(function() {
				name=$(this).attr("name");
				if($(this).is(":checked")) {
					qCols.push(name);
					$(".reportTable .dataTable thead.tableHead tr th:not(.rowSelector,.action)[data-key='"+name+"']").removeClass("hidden");
					$(".reportTable .dataTable thead.tableFilter tr th:not(.rowSelector,.action)[data-key='"+name+"']").removeClass("hidden");
					$(".reportTable .dataTable tbody.tableBody tr td.tableColumn:not(.rowSelector,.action)[data-key='"+name+"']").removeClass("hidden");
					$(".reportTable .dataTable thead.tableSummary tr th:not(.rowSelector,.action)[data-key='"+name+"']").removeClass("hidden");
					$(".reportTable .dataTable thead.tableFoot tr th:not(.rowSelector,.action)[data-key='"+name+"']").removeClass("hidden");
				} else {
					$(".reportTable .dataTable thead.tableHead tr th:not(.rowSelector,.action)[data-key='"+name+"']").addClass("hidden");
					$(".reportTable .dataTable thead.tableFilter tr th:not(.rowSelector,.action)[data-key='"+name+"']").addClass("hidden");
					$(".reportTable .dataTable tbody.tableBody tr td.tableColumn:not(.rowSelector,.action)[data-key='"+name+"']").addClass("hidden");
					$(".reportTable .dataTable thead.tableSummary tr th:not(.rowSelector,.action)[data-key='"+name+"']").addClass("hidden");
					$(".reportTable .dataTable thead.tableFoot tr th:not(.rowSelector,.action)[data-key='"+name+"']").addClass("hidden");
				}
			});
		rpt.settings("columns-visible",qCols);
	};

	rpt.addListener = function(func,type) {
		if(type==null || type=="postload") {
			rpt.postLoad.push(func);
			return true;
		}
		return false;
	}

	rpt.selectedRows = function() {
		q=[];
		$("table.dataTable tbody.tableBody .tableRow .tableColumn.rowSelector input[name=rowSelector]:checked",rpt.getGridTable()).each(function() {
				q.push($(this).closest("tr")[0]);
			});
		return q;
	};

	rpt.settings = function(keyName,value,defaultValue) {
		settingsKey="RPT-"+this.gridID;
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