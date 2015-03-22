var rptSearchOptsDefaults={
	};
var rptOptionsDefaults={
				rowNum:30,
				rowList:[10,30,60,100,250,500,1000,2500,5000,10000,25000],
				sortname:'',
				sortorder:"asc",
				groupField:[],
				altclass:"clr_pink",
				altRows:true,
				altclass:'ui-priority-secondary',
			};
rptSearchOptsMaster={};
rptOptsMaster={};
(function($) {
	$.lgksTable = function(element, options1, options2, jqColumns,datasrc) {

		var plugin = this;

        plugin.settings1 = {};
        plugin.settings2 = {};
        plugin.datasrc=null;
		plugin.gridColumns = {};
		plugin.page=1;
		plugin.reportDiv=null;

        var $element = $(element), element = element;
        
        plugin.init = function() {
            plugin.settings1 = $.extend({}, rptSearchOptsDefaults, options1);
            plugin.settings2 = $.extend({}, rptOptionsDefaults, options2);
            plugin.gridColumns=jqColumns;
			plugin.loadGrid(element,plugin.gridColumns,plugin.settings2,plugin.settings1,datasrc,null);
        }

        plugin.loadGrid=function(gridID,jqColumns,rptOptions,rptSearchOpts,dataSource,editSource) {
        	if(rptOptions.rowList==null || rptOptions.rowList.length<=0) {
				rptOptions.rowList=[10,30,60,100,250,500,1000,2500,5000,10000,25000];
			}
			if(rptSearchOpts.sopt==null || rptSearchOpts.sopt.length<=0) {
				rptSearchOpts.sopt=['eq','ne','lt','le','gt','ge','bw','bn','ew','en','cn','nc','nn','nu','in','ni'];
			}
			plugin.datasrc=dataSource;
			$(gridID+"_grid_table").addClass("datagrid");
			rpt=$(gridID+"_grid_table").parents(".LGKSRPTTABLE:first");
			plugin.reportDiv=rpt;

			hx=rpt.height();
			rpt.find(".gridbar").each(function() {
					hx-=$(this).height()+1;
				});
			if(rptOptions.filterToolbar) hx-=20;
			hx-=5;

			rpt.find(".reportDataTable").css("height",hx);

			navBar=rpt.find(".reportDataTable").parent().find(".navBar");
			htm="";
			$.each(rptOptions.rowList,function(k,v) {
				if(rptOptions.rowNum==v)
					htm+="<option selected>"+v+"</option>";
				else
					htm+="<option>"+v+"</option>";
			});
			navBar.find("select.rowsInPage").html(htm);

			plugin.reportDiv.delegate("tbody td","dblclick",function() {
				td=this;
				txt=$(td).text().trim();
				if(txt.length>0) {
					if(takeAction($(td),txt)) {
						return;
					}
				}
			});

			plugin.loadData();
        }
        plugin.loadData = function() {
        	tmpLnk=plugin.datasrc+"&page="+plugin.page+"&rows="+plugin.settings2.rowNum
        		+"&sidx="+plugin.settings2.sortname+"&sord="+plugin.settings2.sortorder
        		+"&grp="+plugin.settings2.groupField.join(",");
        		//alert(tmpLnk);
        	plugin.reportDiv.find("tbody").html("<tr><td colspan=10000><div class='ajaxloading'>Loading ...</div></td></tr>");
        	processAJAXQuery(tmpLnk,function(txt) {
        		//alert(txt);
				if(txt!=null && txt.length>0) {
					json=$.parseJSON(txt);
					plugin.renderData(json);
				}
			});
        }
        plugin.renderData = function(jsonData) {
        	plugin.page=jsonData.page;

        	tbody=plugin.reportDiv.find("tbody");
        	altclass="";
        	if(plugin.settings2.altRows) {
        		altclass=plugin.settings2.altclass;
        	}
        	html="";
        	$.each(jsonData.rows,function(k,v) {
        		if(k%2==0)
					tr="<tr id='"+v.id+"' class='"+altclass+"'>";
				else
					tr="<tr id='"+v.id+"'>";
				$.each(v.cell,function(k1,v1) {
					if(v1==null) v1="";
					col=plugin.gridColumns.colModel[k1];
					tr+="<td class='"+col.name+" "+col.classes+"' title='"+v1+"' col='"+col.name+"'>"+v1+"</td>";
				});
				tr+="</tr>";
				html+=tr;
			});
			tbody.html(html);
        }
		plugin.init();
    }
})(jQuery);
