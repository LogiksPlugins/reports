<?php
if(!defined('ROOT')) exit('No direct script access allowed');
// $slug=_slug("moduleName/report/param");
// printArray($slug['report']);
// printArray($reportConfig);exit();

$reportConfig['toolbar']['filter']=false;
$reportConfig['toolbar']['columnselector']=false;

$vpath=getWebPath(dirname(dirname(__FILE__)))."/vendors/fullcalendar";
// echo $vpath;
?>
<link href='<?=$vpath?>/fullcalendar.min.css' rel='stylesheet' />
<link href='<?=$vpath?>/fullcalendar.print.min.css' rel='stylesheet' media='print' />
<script src='<?=$vpath?>/moment.min.js'></script>
<script src='<?=$vpath?>/fullcalendar.min.js'></script>

<div id='RPT-<?=$reportKey?>' data-rptkey='<?=$reportKey?>' data-gkey='<?=$reportConfig['reportgkey']?>' class="reportTable kanbanBoardTable table-responsive">
  <div class="row table-tools noprint">
      <?php
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

    <div class='calendarContainer reportContainer'>
      <div id='calendar'></div>
    </div>
  
    <div class='calendarCardTemplate hidden'>
      <article class="calendar-entry grab cardColor {{color}}" id="item{{id}}" draggable="true">
        <div class="calendar-entry-inner">
          <div class="calendar-label">
            <h2>
              <a href="#">{{}}</a>
            </h2>
          </div>
        </div>
      </article>
    </div>
    <script>
    $(function() {
			var rpt=new LGKSReports().init("<?=$reportKey?>","calendar");
			rpt.addRenderer("calendar",renderCalendarUI);
			//rpt.addListener(updateCalendarUI,"postload");
// 			rpt.loadDataGrid();
			
      $('#calendar').fullCalendar({
				header: {
					left: 'prev,next today',
					center: 'title',
					right: 'month,basicWeek,basicDay'
				},
				defaultDate: '2017-05-12',
				navLinks: true, // can click day/week names to navigate views
				editable: true,
				eventLimit: true, // allow "more" link when too many events
				events: [
					{
						title: 'All Day Event',
						start: '2017-05-01'
					},
					{
						title: 'Long Event',
						start: '2017-05-07',
						end: '2017-05-10'
					},
					{
						id: 999,
						title: 'Repeating Event',
						start: '2017-05-09T16:00:00'
					},
					{
						id: 999,
						title: 'Repeating Event',
						start: '2017-05-16T16:00:00'
					},
					{
						title: 'Conference',
						start: '2017-05-11',
						end: '2017-05-13'
					},
					{
						title: 'Meeting',
						start: '2017-05-12T10:30:00',
						end: '2017-05-12T12:30:00'
					},
					{
						title: 'Lunch',
						start: '2017-05-12T12:00:00'
					},
					{
						title: 'Meeting',
						start: '2017-05-12T14:30:00'
					},
					{
						title: 'Happy Hour',
						start: '2017-05-12T17:30:00'
					},
					{
						title: 'Dinner',
						start: '2017-05-12T20:00:00'
					},
					{
						title: 'Birthday Party',
						start: '2017-05-13T07:00:00'
					},
					{
						title: 'Click for Google',
						url: 'http://google.com/',
						start: '2017-05-28'
					}
				]
			});
    });
		function renderCalendarUI(gridID, rptHandler) {
			grid=rptHandler.getGrid();
			gridBody=$(".kanbanBoard","#RPT-"+this.gridID);
			gridID=grid.data('rptkey');

			if(grid.data("page")==grid.data("current") && grid.data("page")!=null) {
				return false;
			}
		}
    </script>
</div>
