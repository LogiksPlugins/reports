<?php
//groupable
//searchable
//formatter

//printArray($reportConfig);exit();
$arrPager=[10,20,50,100,500,1000,5000];
?>
<div id='RPT-<?=$reportKey?>' data-rptkey='<?=$reportKey?>' class="reportTable table-responsive">
	<div class="row table-tools">
      <div class="control-primebar">
      	<div class="col-lg-6 col-xs-6 pull-left">
      		<h1 class='reportTitle'><?=$reportConfig['title']?></h1>
      	</div>

      	<div class="col-lg-6 col-xs-6 pull-right">
            <div class="input-group">
                <input name="q" placeholder="Search..." type="text" class="form-control searchfield searchicon">

                <div class="input-group-btn">
                	<button type="button" cmd='refresh' class="btn btn-default"><span class="glyphicon glyphicon-refresh"></span></button>

                	<div class='btn-group'>
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="glyphicon glyphicon-print"></span><span class="caret"></span></button>
                        <ul class="reportActions dropdown-menu" aria-labelledby="dropdownMenu" role='menu'>
                          <li><a href="#" cmd='report:print'>Print</a></li>
                          <li><a href="#" cmd='report:exportcsv'>Export CSV</a></li>
                          <li><a href="#" cmd='report:exportxls'>Export Excel</a></li>
                          <li><a href="#" cmd='report:exportpdf'>Export PDF</a></li>
                          <li><a href="#" cmd='report:exportimg'>Export Image</a></li>
                          <li><a href="#" cmd='report:email'>Email Report</a></li>
                        </ul>
                    </div>

                    <button type="button" cmd='filterbar' class="btn btn-default"><span class="glyphicon glyphicon-filter"></span></button>

                    <div class='btn-group'>
                    	<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    		<span class="glyphicon glyphicon-list-alt"></span><span class="caret"></span></button>
                    	<ul class="columnFilter dropdown-menu" aria-labelledby="dropdownMenu" role='menu'>
                    		<?php
                    			foreach ($reportConfig['datagrid'] as $colID => $column) {
                    				if(isset($column['hidden']) && $column['hidden']) {
                    					echo "<li><a href='#'><label><input class='columnName' type='checkbox' name='{$colID}'>"._ling($column['label'])."</label></a></li>";
                    				} else {
                    					echo "<li><a href='#'><label><input class='columnName' type='checkbox' name='{$colID}' checked=true>"._ling($column['label'])."</label></a></li>";
                    				}
                    				
                    			}
                    		?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
      </div>
      <?php
      	if(isset($reportConfig['actions']) && is_array($reportConfig['actions']) && count($reportConfig['actions'])>0) {
      ?>
      <div class="control-toolbar">
      	<div class="col-lg-12 col-xs-12">
      	<?php
      		foreach ($reportConfig['actions'] as $key => $button) {
      			if(isset($button['label'])) $button['label']=_ling($button['label']);
      			else $button['label']=_ling($key);
      			
      			if(!isset($button['class'])) $button['class']="btn btn-primary";
      			echo "<a class='{$button['class']}' cmd='{$key}' >";
      			if(isset($button['icon'])) {
      				echo $button['icon'];
      			}
      			echo " {$button['label']}</a>";
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

	<table class="dataTable table table-hover table-striped table-condensed">
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
						echo "<th class='".trim($clz)."' data-key='{$key}' $style >";
						echo _ling($row['label']);
						if(isset($row['sortable']) && $row['sortable']) {
							echo "<span class='colSort sorting'></span>";
						}
						echo "</th>";
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
		<tfoot class='tableFoot'>
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
	new LGKSReports().init("<?=$reportKey?>");
});
</script>
