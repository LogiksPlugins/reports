<?php
if(!defined('ROOT')) exit('No direct script access allowed');

//groupable

//printArray($reportConfig);exit();
if(isset($reportConfig['pager'])) {
  $arrPager=$reportConfig['pager'];
} else {
  $arrPager=[5, 10,20,50,100,500,1000,5000];
}
?>
<div id='RPT-<?=$reportKey?>' data-rptkey='<?=$reportKey?>' data-gkey='<?=$reportConfig['reportgkey']?>' class="reportTable table-responsive">
	<div class="row table-tools noprint">
      <?php
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
		<thead class='tableHead'>
			<tr>
				<?php
					if(isset($reportConfig['showExtraColumn']) && $reportConfig['showExtraColumn']) {
						if(strpos($reportConfig['showExtraColumn'],"<")===0) {
							echo "<th class='action' width=25px>";
							echo "</th>";
						} else {
							echo "<th class='action' width=25px>";
							echo "<input name='rowSelector' type='{$reportConfig['showExtraColumn']}' class='checkbox'  />";
							echo "</th>";
						}
					}
					foreach ($reportConfig['datagrid'] as $key => $row) {
						if(isset($row['policy']) && strlen($row['policy'])>0) {
							$allow=checkUserPolicy($row['policy']);
							if(!$allow) continue;
						}

						$clz="$key";$style="";
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
						echo "<th id='".md5($key)."' class='".trim($clz)."' data-key='{$key}' $style >";
						echo _ling($row['label']);
						if(isset($row['sortable']) && $row['sortable']) {
							echo "<span class='colSort sorting noprint'></span>";
						}
            echo "</th>";
					}
          if(isset($reportConfig['buttons']) && is_array($reportConfig['buttons']) && count($reportConfig['buttons'])>0) {
            echo "<th class='actionCol hidden-print'></th>";
          }
				?>
			</tr>
		</thead>
		<thead class='tableFilter hidden'>
			<tr>
				<?php
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
</script>
