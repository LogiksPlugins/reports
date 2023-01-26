<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!isset($reportConfig['pivot'])) $reportConfig['pivot']=[];
if(!isset($reportConfig['pivot']['colkeys'])) $reportConfig['pivot']['colkeys']=[];
if(!isset($reportConfig['pivot']['colmap'])) $reportConfig['pivot']['colmap']=[];
if(!isset($reportConfig['buttons'])) $reportConfig['buttons']=[];

$reportConfig['toolbar']['filter']=false;
$reportConfig['toolbar']['columnselector']=false;

$colMap=[];
if(isset($reportConfig['pivot']['colmap'])) {
	$colMap=$reportConfig['pivot']['colmap'];
}

$colMap=array_merge([
	 "title"=>"title",
	 "descs"=>"descs",

	 // "category"=>"category",
	 // "msg"=>"msg",
	 // "due_date"=>"due_date",

	 // "image"=>"image",
	 // "avatar"=>"avatar",
	 // "wallphoto"=>"wallphoto",
		
	 // "tags"=>"tags",
	 // "counter"=>"counter",
	 // "flag"=>"flag",

	 // "color"=>"color",//Depends on ColorMap for Logic Based On Column Selected by this field
	 // "icons"=>"icons",
	 //logic: Icons, color
 ],$colMap);

$topbar = $reportConfig['topbar'];

$unilink=false;
if(isset($reportConfig['pivot']['unilink']) && strlen($reportConfig['pivot']['unilink'])>0) {
	$unilink=$reportConfig['pivot']['unilink'];
}

$reportConfig['topbar'] = $topbar;
?>
<style>
.pivot_selector {
    width: 150px;
    display: inline-block;
}
</style>
<div id='RPT-<?=$reportKey?>' data-rptkey='<?=$reportKey?>' data-gkey='<?=$reportConfig['reportgkey']?>' class="reportTable pivotBoardTable table-responsive">
  <div class="row table-tools noprint">
  		<?php
  			include_once __DIR__."/comps/smartfilter.php";
			include_once __DIR__."/comps/topbar.php";
		?>
      <?php
      	if(isset($reportConfig['filters']) && !empty($reportConfig['filters'])) {
      ?>
      <div class="control-filters">
      	<div class="col-lg-12 col-xs-12">
          <?php
            foreach($reportConfig['filters'] as $a=>$b) {
              
            }
          ?>
        </div>
      </div>
      <?php
      	}
      ?>
      <?php
      	if(isset($reportConfig['custombar']) && $reportConfig['custombar'] && file_exists(APPROOT.$reportConfig['custombar'])) {
      ?>
      <div class="control-custombar">
      	<div class="col-lg-12 col-xs-12">
      		<?php
      			include_once APPROOT.$reportConfig['custombar'];
      		?>
      	</div>
      </div>
      <?php
      	}
      ?>
    </div>
    <?php
       if(empty($reportConfig['pivot']['colkeys'])) {
          echo "<h1 align=center>Sorry, Pivot Mode not supported by this report.</h1>";
       } else {
    ?>
    <div class='pivotBoardContainer reportContainer'>
      <div class='pivotBoard reportBoard'>
      </div>
    </div>

    <div class='pivotRecordTemplate hidden'>
    	<?php
    		if(isset($reportConfig['cards']['template'])) {
    			echo $reportConfig['cards']['template'];
    		} else {
    			?>
    			<article class="dataItem data-record" id="item{{id}}"  data-hash='{{hashid}}' data-refid='{{id}}'>
    				<?php if($unilink) { ?>
						{{#if hashid}}
						<a class='unilink' href='#' data-type="<?=$unilink?>" data-hashid="{{hashid}}" title='{{<?=$colMap['descs']?>}}'>{{<?=$colMap['title']?>}}</a>
						{{else}}
						<a class='unilink' href='#' title='{{<?=$colMap['descs']?>}}'>{{<?=$colMap['title']?>}}</a>
						{{/if}}
					<?php } else { ?>
						<a href='#' title='{{<?=$colMap['descs']?>}}'>{{<?=$colMap['title']?>}}</a>
					<?php } ?>
    			</article>
    			<?php
    		}
    	?>
  	</div>

<script>
var rpt;
var current_axis_1 = false;
var current_axis_2 = false;
$(function() {
	rpt=new LGKSReports().init("<?=$reportKey?>","pivot");
	rpt.addRenderer("pivot",renderPivotUI);
	// rpt.addListener(updatePivotUI,"postload");

	rpt.loadDataGrid();
});
function renderPivotUI(gridID, rptHandler) {
	gridBody=$(".pivotBoard","#RPT-"+gridID);
	if(gridBody.find(".pivot-col").length<=0) {
		return generatePivotUI(rptHandler);
	} else {
		return loadPivot(rptHandler);
	}
}
function generatePivotUI(rptHandler) {
	grid=rptHandler.getGrid();
	gridBody=$(".pivotBoard","#RPT-"+rptHandler.gridID);
	gridID=grid.data('rptkey');

	gridBody.html('<div class="ajaxloading ajaxloading3"></div>');
	
	lx=_service("reports","enumerateColumn","json")+"&gridid="+rptHandler.gridID+"&colKey=*";
	processAJAXQuery(lx,function(jsonData) {
	    var data = jsonData.Data.data;

	    if(!current_axis_1) current_axis_1 = jsonData.Data.default_axis_1;
	    if(!current_axis_2) current_axis_2 = jsonData.Data.default_axis_2;

	    gridBody.html(`<div class='table-responsive'>
		    <table id='pivotTable' class='table table-striped table-compact'>
		        <thead class='header1'></thead>
		        <thead class='header2'></thead>
		        <tbody class='body1'></tbody>
		        <tfoot class='footer1'></tfoot>
		    </table>
		</div>`);

	    var title = "<?=$reportConfig['title']?>";
		var footer_title = false;
		var axis_1 = "<?=$reportConfig['pivot']['colkeys']['axis_1']?>".split(",");
		var axis_2 = "<?=$reportConfig['pivot']['colkeys']['axis_2']?>".split(",");

		var dropDownAxis1 = `<select class='select form-control pivot_selector' name='current_axis_1' onchange='pivotColumnChange(this)'>
                        ${axis_1.map(a=>(current_axis_1==a?`<option value='${a}' selected>${a}</option>`:`<option value='${a}'>${a}</option>`)).join("")}
                    </select>`;
        var dropDownAxis2 = `<select class='select form-control pivot_selector' name='current_axis_2' onchange='pivotColumnChange(this)'>
                        ${axis_2.map(a=>(current_axis_2==a?`<option value='${a}' selected>${a}</option>`:`<option value='${a}'>${a}</option>`)).join("")}
                    </select>`;

	    var thead1 = [
		        // [`<th>${title}</th>`],
		        // [`<th>${axis_1} <i class='fa fa-chevron-right'></i></th>`],
		        // ["<td colspan=1000></td>"]
		    ], thead2 = [
		        [`<th>${dropDownAxis2} <i class='fa fa-chevron-down'></i> / ${dropDownAxis1} <i class='fa fa-chevron-right'></i></th>`],
		    ], tbody1 = [], tfoot1 = [];
		$.each(data[current_axis_1], function(a,b) {
			if(b.length<=0) return;
		    thead2.push(`<th>${b}</th>`);
		});
		$.each(data[current_axis_2], function(a,b) {
			if(b.length<=0) return;
		    var temp = ["<tr>"];
		    temp.push(`<th>${b}</th>`);
		   
		    $.each(data[current_axis_1], function(a1,b1) {
		    	if(b1.length<=0) return;
		        temp.push(`<td class='data_cell' axis_1='${b1}' axis_2='${b}'></td>`);
		    });
		    
		    temp.push("</tr>");
		    
		    tbody1.push(temp.join(""));
		});

		if(footer_title) {
		    tfoot1.push(`<th>${footer_title}</th>`);
		    $.each(data[current_axis_1], function(a1,b1) {
		    	if(b1.length<=0) return;
		        tfoot1.push(`<td data-axis_1='${b1}'></td>`);
		    });
		}

		$("#pivotTable thead.header1").html("<tr>"+thead1.join("")+"</tr>");
		$("#pivotTable thead.header2").html("<tr>"+thead2.join("")+"</tr>");
		$("#pivotTable tbody.body1").html(tbody1.join(""));
		$("#pivotTable tfoot.footer1").html("<tr>"+tfoot1.join("")+"</tr>");

	    loadPivot(rpt);
	}, "json");
}
function loadPivot(rptHandler) {
	if(typeof showLoader == "function") showLoader();
	rpt.fetchReportData("json",function(txt) {
		if(typeof hideLoader == "function") hideLoader();

		jsonData=$.parseJSON(txt);
        if(jsonData==null && jsonData.Data==null) {
          lgksToast("Sorry, no data found");
          return;
        } else {
          jsonData=jsonData.Data;
        }
        
        $.each(jsonData.RECORDS,function(k,v) {
            addDataPointToPivotCell(v, rptHandler);
        });

        limit=jsonData.INFO.limit;
        index=jsonData.INFO.index;
        last=jsonData.INFO.last;
        max=jsonData.INFO.max;

        rpt.updateReportMeta(limit, index, last, max);
        rpt.postDataPopulate(rpt.gridID);
	});
}
function addDataPointToPivotCell(record, rptHandler) {
	var gridTemplate=$(".pivotRecordTemplate","#RPT-"+rptHandler.gridID).html();
	var gridCardGen=Handlebars.compile(gridTemplate);
	var cardHTML=gridCardGen(record);

	var x1 = record[current_axis_1]
	var x2 = record[current_axis_2]

	var cell = $(".pivotBoard","#RPT-"+rptHandler.gridID).find(`.data_cell[axis_1='${x1}'][axis_2='${x2}']`);
	console.log(record, cardHTML);
	if(cell.length>0) {
		cell.append(cardHTML);
	}
}
function pivotColumnChange(selector) {
	window[$(selector).attr("name")] = $(selector).val();
	renderPivotUI($(selector).closest(".reportTable").data("rptkey"), LGKSReportsInstances[$(selector).closest(".reportTable").data("rptkey")])
}
</script>
<?php
	}
?>