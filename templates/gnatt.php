<?php
if(!defined('ROOT')) exit('No direct script access allowed');

$reportConfig['sidebar'] = false;

// printArray($reportConfig);
?>
<style>
#gnattContainer {
    /*max-width: 800px;*/
    margin: 1em auto;
}
</style>
<script src="https://code.highcharts.com/gantt/highcharts-gantt.js"></script>
<script src="https://code.highcharts.com/gantt/modules/exporting.js"></script>
<div id='RPT-<?=$reportKey?>' data-rptkey='<?=$reportKey?>' data-gkey='<?=$reportConfig['reportgkey']?>' class="reportTable gnatChartTable table-responsive">
  	<div class="row table-tools noprint">
  		<?php
  			include_once __DIR__."/comps/smartfilter.php";
			include_once __DIR__."/comps/topbar.php";
		?>
	</div>
	<div class='gnatChartContainer reportContainer'>
      <div id='gnattContainer' class='gnatChart reportBoard'>

      </div>
    </div>
</div>
<script>
$(function() {
	$(".report-main-container").attr("class", "col-xs-12 col-sm-12 col-md-12 col-lg-12");
	
	var rpt=new LGKSReports().init("<?=$reportKey?>","gnatt");
	rpt.addRenderer("gnatt",renderGnattUI);
	rpt.addListener(updateGnattUI,"postload");

	rpt.loadDataGrid();
});
function renderGnattUI() {
	var today = new Date(),
	    day = 1000 * 60 * 60 * 24,
	    // Utility functions
	    dateFormat = Highcharts.dateFormat,
	    defined = Highcharts.defined,
	    isObject = Highcharts.isObject;

	// Set to 00:00:00:000 today
	today.setUTCHours(0);
	today.setUTCMinutes(0);
	today.setUTCSeconds(0);
	today.setUTCMilliseconds(0);
	today = today.getTime();

	Highcharts.ganttChart('gnattContainer', {
	    series: [{
	        name: 'Offices',
	        data: [{
	            name: 'New offices',
	            id: 'new_offices',
	            owner: 'Peter'
	        }, {
	            name: 'Prepare office building',
	            id: 'prepare_building',
	            parent: 'new_offices',
	            start: today - (2 * day),
	            end: today + (6 * day),
	            completed: {
	                amount: 0.2
	            },
	            owner: 'Linda'
	        }, {
	            name: 'Inspect building',
	            id: 'inspect_building',
	            dependency: 'prepare_building',
	            parent: 'new_offices',
	            start: today + 6 * day,
	            end: today + 8 * day,
	            owner: 'Ivy'
	        }, {
	            name: 'Passed inspection',
	            id: 'passed_inspection',
	            dependency: 'inspect_building',
	            parent: 'new_offices',
	            start: today + 9.5 * day,
	            milestone: true,
	            owner: 'Peter'
	        }, {
	            name: 'Relocate',
	            id: 'relocate',
	            dependency: 'passed_inspection',
	            parent: 'new_offices',
	            owner: 'Josh'
	        }, {
	            name: 'Relocate staff',
	            id: 'relocate_staff',
	            parent: 'relocate',
	            start: today + 10 * day,
	            end: today + 11 * day,
	            owner: 'Mark'
	        }, {
	            name: 'Relocate test facility',
	            dependency: 'relocate_staff',
	            parent: 'relocate',
	            start: today + 11 * day,
	            end: today + 13 * day,
	            owner: 'Anne'
	        }, {
	            name: 'Relocate cantina',
	            dependency: 'relocate_staff',
	            parent: 'relocate',
	            start: today + 11 * day,
	            end: today + 14 * day
	        }]
	    }, {
	        name: 'Product',
	        data: [{
	            name: 'New product launch',
	            id: 'new_product',
	            owner: 'Peter'
	        }, {
	            name: 'Development',
	            id: 'development',
	            parent: 'new_product',
	            start: today - day,
	            end: today + (11 * day),
	            completed: {
	                amount: 0.6,
	                fill: '#e80'
	            },
	            owner: 'Susan'
	        }, {
	            name: 'Beta',
	            id: 'beta',
	            dependency: 'development',
	            parent: 'new_product',
	            start: today + 12.5 * day,
	            milestone: true,
	            owner: 'Peter'
	        }, {
	            name: 'Final development',
	            id: 'finalize',
	            dependency: 'beta',
	            parent: 'new_product',
	            start: today + 13 * day,
	            end: today + 17 * day
	        }, {
	            name: 'Launch',
	            dependency: 'finalize',
	            parent: 'new_product',
	            start: today + 17.5 * day,
	            milestone: true,
	            owner: 'Peter'
	        }]
	    }],
	    tooltip: {
	        pointFormatter: function () {
	            var point = this,
	                format = '%e. %b',
	                options = point.options,
	                completed = options.completed,
	                amount = isObject(completed) ? completed.amount : completed,
	                status = ((amount || 0) * 100) + '%',
	                lines;

	            lines = [{
	                value: point.name,
	                style: 'font-weight: bold;'
	            }, {
	                title: 'Start',
	                value: dateFormat(format, point.start)
	            }, {
	                visible: !options.milestone,
	                title: 'End',
	                value: dateFormat(format, point.end)
	            }, {
	                title: 'Completed',
	                value: status
	            }, {
	                title: 'Owner',
	                value: options.owner || 'unassigned'
	            }];

	            return lines.reduce(function (str, line) {
	                var s = '',
	                    style = (
	                        defined(line.style) ? line.style : 'font-size: 0.8em;'
	                    );
	                if (line.visible !== false) {
	                    s = (
	                        '<span style="' + style + '">' +
	                        (defined(line.title) ? line.title + ': ' : '') +
	                        (defined(line.value) ? line.value : '') +
	                        '</span><br/>'
	                    );
	                }
	                return str + s;
	            }, '');
	        }
	    },
	    title: {
	        text: 'Gantt Project Management'
	    },
	    xAxis: {
	        currentDateIndicator: true,
	        min: today - 3 * day,
	        max: today + 18 * day
	    }
	});

}
function updateGnattUI() {
	alert("qweqwe");
}
</script>