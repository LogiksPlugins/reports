<?php
if(!defined('ROOT')) exit('No direct script access allowed');

//groupable

//printArray($reportConfig);exit();
if(isset($reportConfig['pager'])) {
  $arrPager=$reportConfig['pager'];
} else {
  $arrPager=[5, 10,20,50,100,500,1000,5000];
}
if(isset($reportConfig['max_visible_cols'])) {
	$maxCols = $reportConfig['max_visible_cols'];
} else {
	$maxCols = "";
}
?>
<div id='RPT-<?=$reportKey?>' data-rptkey='<?=$reportKey?>' data-gkey='<?=$reportConfig['reportgkey']?>' class="reportTable table-responsive" data-maxcols="<?=$maxCols?>">
	<div class="row table-tools noprint">
      <?php
  			include_once __DIR__."/comps/smartfilter.php";
				include_once __DIR__."/comps/topbar.php";
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

	<table class="dataTable table table-hover table-striped table-condensed reportContainer">
		<thead class='tableHeadGroups'>
		</thead>
		<thead class='tableHead'>
			<tr>
				<?php
					if($reportConfig['buttons_align']=="left") {
						if(isset($reportConfig['buttons']) && is_array($reportConfig['buttons']) && count($reportConfig['buttons'])>0) {
	            			echo "<th class='actionCol nocalculate left hidden-print'></th>";
	          			}
					}
					if(isset($reportConfig['showExtraColumn']) && $reportConfig['showExtraColumn'] && $reportConfig['showExtraColumn']!="false") {
						if(strpos($reportConfig['showExtraColumn'],"<")===0) {
							echo "<th class='action nocalculate' width=25px>";
							echo "</th>";
						} else {
							echo "<th class='action nocalculate' width=25px>";
							echo "<input name='rowSelector' type='{$reportConfig['showExtraColumn']}' class='checkbox'  />";
							echo "</th>";
						}
					}
					foreach ($reportConfig['datagrid'] as $key => $row) {
						if(isset($row['policy']) && strlen($row['policy'])>0) {
							$allow=checkUserPolicy($row['policy']);
							if(!$allow) continue;
						}

						$clz="$key";$style="";$xtraAttributes=[];
						if(isset($row['classes'])) {
							$clz.=" {$row['classes']}";
						}
						if(isset($row['hidden']) && $row['hidden']==true) {
							$clz.=" hidden";
						}
						if(isset($row['style'])) {
							$style="style='{$row['style']}'";
						}
						if(isset($row['resizable']) && $row['resizable']) {
							$clz.=" resizable";
						}

						if(isset($row['calculate'])) {
							$xtraAttributes[] = "data-calculate='{$row['calculate']}'";

							if(isset($row['calculate-decimal'])) {
								$xtraAttributes[] = "data-calculate_decimal='{$row['calculate-decimal']}'";
							}
							if(isset($row['calculate-prefix'])) {
								$xtraAttributes[] = "data-calculate_prefix='{$row['calculate-prefix']}'";
							}
							if(isset($row['calculate-suffix'])) {
								$xtraAttributes[] = "data-calculate_suffix='{$row['calculate-suffix']}'";
							}
						}

						$xtraAttributes = implode(" ", $xtraAttributes);

						echo "<th id='".md5($key)."' class='".trim($clz)."' $xtraAttributes data-key='{$key}' $style >";
						echo _ling($row['label']);
						if(isset($row['sortable']) && $row['sortable']) {
							echo "<span class='colSort sorting noprint'></span>";
						}
            			echo "</th>";
					}
					if($reportConfig['buttons_align']=="right") {
						if(isset($reportConfig['buttons']) && is_array($reportConfig['buttons']) && count($reportConfig['buttons'])>0) {
	            echo "<th class='actionCol hidden-print'></th>";
	          }
					}
				?>
			</tr>
		</thead>
		<thead class='tableFilter hidden'>
			<tr>
				<?php
					if($reportConfig['buttons_align']=="left") {
						echo "<th data-key='action' width=25px></th>";
					}
					if(isset($reportConfig['showExtraColumn']) && $reportConfig['showExtraColumn']) {
						echo "<th data-key='action' width=25px></th>";
					}
        			foreach ($reportConfig['datagrid'] as $colID => $column) {
        				if(isset($column['searchable']) && $column['searchable']) {
        					$filterConfig=[];
	        				if(isset($column['filter']) && $column['filter']) {
	        					$filterConfig=$column['filter'];
	        				}
        					if(isset($column['hidden']) && $column['hidden']) {
	        					echo "<th class='filterCol hidden' data-key='{$colID}' >";
	        					echo formatReportFilter($colID,$filterConfig,$reportConfig['dbkey']);
	        					echo "</th>";
	        				} else {
	        					echo "<th class='filterCol' data-key='{$colID}' >";
	        					echo formatReportFilter($colID,$filterConfig,$reportConfig['dbkey']);
	        					echo "</th>";
        					}
        				} else {
        					echo "<th class='filterCol filterBlank' data-key='{$colID}' >";
        					echo "</th>";
        				}
        			}
        		?>
			</tr>
		</thead>
		<tbody class='tableBody'>
			
		</tbody>
		<tbody class='tableSummary hidden'>

		</tbody>
		<tfoot class='tableFoot noprint'>
			<tr><td colspan=100000000>
				<div class="col-lg-6 pull-left">
		            <select class='perPageCounter autorefreshReport' name='limit'>
		            	<?php
		            		foreach ($arrPager as $cntr) {
		            			if($cntr==$reportConfig['rowsPerPage']) {
		            				echo "<option selected>{$cntr}</option>";
		            			} else {
		            				echo "<option>{$cntr}</option>";
		            			}
		            		}
		            	?>
		            </select>
					
								<button type="button" class="btn btn-default pull-right" cmd='stayPut' style='margin-left: 10px;' title='Toggle if records get appeneded at the end or not.'><i class='glyphicon glyphicon-record'></i></button>

                <div class="btn-group pull-right" role="group" aria-label="pagination">
                  <button type="button" class="btn btn-default" cmd='prevPage'><i class='glyphicon glyphicon-chevron-left'></i></button>
                  <button type="button" class="btn btn-default" cmd='firstPage'><i class='glyphicon glyphicon-retweet'></i></button>
                  <button type="button" class="btn btn-default" cmd='nextPage'><i class='glyphicon glyphicon-chevron-right'></i></button>
                </div>
		        </div>
		        <div class="col-lg-6 pull-right">
		            <citie class='displayCounter'>Displaying <span class='recordsIndex'>0</span>-<span class='recordsUpto'>0</span> of <span class='recordsMax'>0</span> records</citie>
		        </div>
			</td></tr>
		</tfoot>
	</table>
</div>
<script>
$(function() {
	var rpt=new LGKSReports().init("<?=$reportKey?>");
	rpt.addListener(updateGridUI,"postload");

	rpt.addListener(function(gridID) {
			    generateSummary(gridID);
			    generateHeaderGroups(gridID);
			});

	rpt.loadDataGrid();
});
function updateGridUI(rkey){
	rpt=LGKSReports.getInstance(rkey);
	grid=LGKSReports.getInstance(rkey).getGrid();
  gridBody=$(".kanbanBoard","#RPT-"+rkey);
	
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
}
function generateSummary(gridID) {
    //console.log("generateSummary", gridID);
    $("#RPT-"+gridID).find(".tableSummary").html("");
	
	//tableSummary
    if($("#RPT-"+gridID).find(".tableBody").find(".tableColumn[data-calculate]").length>0) {
        $("#RPT-"+gridID).find(".tableSummary").removeClass("hidden");
        $("#RPT-"+gridID).find(".tableSummary").html("<tr></tr>");
        $("#RPT-"+gridID).find(".tableHead>tr>th").each(function() {
            if($(this).hasClass("hidden")) {
                if($(this).hasClass("nocalculate") || $(this).data("calculate")==null) 
                    $("#RPT-"+gridID).find(".tableSummary>tr").append("<th class='hidden'></th>");
                else 
                    $("#RPT-"+gridID).find(".tableSummary>tr").append("<th class='column-summary text-center hidden' data-calculate='"+$(this).data("calculate")+"' data-key='"+$(this).data("key")+"' data-calculate_decimal='"+$(this).data("calculate_decimal")+"' data-calculate_prefix='"+$(this).data("calculate_prefix")+"' data-calculate_suffix='"+$(this).data("calculate_suffix")+"'>-</th>");
            } else {
                if($(this).hasClass("nocalculate") || $(this).data("calculate")==null) 
                    $("#RPT-"+gridID).find(".tableSummary>tr").append("<th></th>");
                else
                    $("#RPT-"+gridID).find(".tableSummary>tr").append("<th class='column-summary text-center' data-calculate='"+$(this).data("calculate")+"' data-key='"+$(this).data("key")+"' data-calculate_decimal='"+$(this).data("calculate_decimal")+"' data-calculate_prefix='"+$(this).data("calculate_prefix")+"' data-calculate_suffix='"+$(this).data("calculate_suffix")+"'>-</th>");
            }
        });

        $("#RPT-"+gridID).find(".tableSummary>tr>th.column-summary").each(function() {
            var finalValue = 0;
            var calculateRule = $(this).data("calculate").toLowerCase();
            var decimalCount = $(this).data("calculate_decimal");
            var prefix = $(this).data("calculate_prefix");
            var suffix = $(this).data("calculate_suffix");

            if(decimalCount==null) decimalCount = 0;
            if(prefix==null) prefix = "";
            if(suffix==null) suffix = "";

            switch(calculateRule) {
                case "max":
                    $("#RPT-"+gridID).find(".tableBody").find(".tableColumn[data-key='"+$(this).data("key")+"']").each(function(a,cell) {
                        if(finalValue<parseFloat($(cell).text())) {
													finalValue = parseFloat($(cell).text());
                        }
                    });
                    break;
                case "min":
                    $("#RPT-"+gridID).find(".tableBody").find(".tableColumn[data-key='"+$(this).data("key")+"']").each(function(a,cell) {
                    		if(a==0) finalValue = parseFloat($(cell).text());
                        if(finalValue>parseFloat($(cell).text())) {
													finalValue = parseFloat($(cell).text());
                        }
                    });
                    break;
                case "average":
                		var tempCount = $("#RPT-"+gridID).find(".tableBody").find(".tableColumn[data-key='"+$(this).data("key")+"']").length;
                    $("#RPT-"+gridID).find(".tableBody").find(".tableColumn[data-key='"+$(this).data("key")+"']").each(function(a,cell) {
                        finalValue += parseFloat($(cell).text());
                    });
                    finalValue = finalValue/tempCount;
                    break;
                case "count-unique":
                    var tempArr = [];
                    $("#RPT-"+gridID).find(".tableBody").find(".tableColumn[data-key='"+$(this).data("key")+"']").each(function(a,cell) {
                        if($(cell).text()!=null && $(cell).text().length>0) {
                        	if(tempArr.indexOf($(cell).text())<0) tempArr.push($(cell).text());
                        }
                    });
                    finalValue = tempArr.length;
                    break;
                case "count":
                    $("#RPT-"+gridID).find(".tableBody").find(".tableColumn[data-key='"+$(this).data("key")+"']").each(function(a,cell) {
                        if($(cell).text()!=null && $(cell).text().length>0) finalValue+=1;
                    });
                    break;
                case "sum":
                default:
                    $("#RPT-"+gridID).find(".tableBody").find(".tableColumn[data-key='"+$(this).data("key")+"']").each(function(a,cell) {
                        finalValue += parseFloat($(cell).text());
                    });
                    break;
            }
            finalValue = finalValue.toFixed(decimalCount);
            $(this).html(prefix+finalValue+suffix);
        });
    }
}
function generateHeaderGroups(gridID) {
	console.log("generateHeaderGroups");
	//.tableHeadGroups
}
</script>
