<?php
if(!defined('ROOT')) exit('No direct script access allowed');
// $slug=_slug("moduleName/report/param");
// printArray($slug['report']);
// printArray($reportConfig);exit();

$reportConfig['toolbar']['filter']=false;
$reportConfig['toolbar']['columnselector']=false;

$colMap=[];
if(isset($reportConfig['calendar']['colmap'])) {
  $colMap=$reportConfig['calendar']['colmap'];
}

$unilink=false;
if(isset($reportConfig['calendar']['unilink']) && strlen($reportConfig['calendar']['unilink'])>0) {
  $unilink=$reportConfig['calendar']['unilink'];
}

if(!isset($reportConfig['calendar']['date_col'])) $reportConfig['calendar']['date_col'] = "dated";

//dayGridMonth,dayGridWeek,dayGridDay,timeGridWeek,timeGridDay,dayGridWeek,timeGridWeek,dayGridDay,timeGridDay
if(!isset($reportConfig['calendar']['views'])) $reportConfig['calendar']['views'] = "dayGridMonth,timeGridWeek,timeGridDay";

$colMap=array_merge([
       "title"=>"title",
       "descs"=>"descs",
       "icon"=>"icon",
       // "category"=>"category",
       
       // "tags"=>"tags",
       // "counter"=>"counter",
       // "flag"=>"flag",

       // "color"=>"color",//Depends on ColorMap for Logic Based On Column Selected by this field
       //logic: Icons, color
     ],$colMap);

$_SESSION['REPORT'][$reportConfig['reportkey']]['date_filter'] = $reportConfig['calendar']['date_col'];

$colMap['date_col'] = $reportConfig['calendar']['date_col'];
$colMap['date_col'] = explode(".", $colMap['date_col']);
$colMap['date_col'] = end($colMap['date_col']);

$vpath=getWebPath(dirname(dirname(__FILE__)))."/vendors/fullcalendar6";
// echo $vpath;
include dirname(dirname(__FILE__))."/vendors/fullcalendar/boot.php";
//<script src='<?=$vpath?>/dist/index.global.js'></script>
?>
<script src='<?=$vpath?>/moment.min.js'></script>
<style>
.fc-event {cursor: pointer;}
.fc-toolbar-chunk button {
	text-transform: capitalize !important;
}
</style>
<div id='RPT-<?=$reportKey?>' data-rptkey='<?=$reportKey?>' data-gkey='<?=$reportConfig['reportgkey']?>' class="reportTable kanbanBoardTable table-responsive">
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

    <div class='calendarContainer reportContainer'>
      <div id='calendar'></div>
    </div>
    <script>
  	var rpt = null;
	 	var calendar = null;
    $(function() {
			rpt = new LGKSReports().init("<?=$reportKey?>","calendar");
			rpt.addRenderer("calendar",renderCalendarUI);
			//rpt.addListener(updateCalendarUI,"postload");
			
      var calendarEl = document.getElementById('calendar');
      calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth',
          headerToolbar: {
						left: 'prev,next today',
						center: 'title',
						right: '<?=$reportConfig['calendar']['views']?>'
					},
					initialDate: '<?=date("Y-m-d")?>',
					navLinks: true, // can click day/week names to navigate views
					editable: false,
					weekNumbers: false,
					dayMaxEvents: true,
					nowIndicator: false,
					weekends: true,
					dayMaxEventRows: 5,
					dateClick: function() {
				    //alert('a day has been clicked!');
				  },
				  eventClick: function(info) {
				  	// console.log(info.event);
				    
				    <?php
        			if($unilink) {
        				?>
        				var link=_replace("<?=$unilink?>", info.event.extendedProps);
								top.lgksOverlayFrame(_link("modules/uniLink/"+link),"Viewer",function() {
						// 			hideLoader();
								});
        				<?php
        			} else {
        				?>
        				html = ["<div class='table-responsive' style='max-height: 70%;word-break: break-word;'><table class='table table-striped table-compact'><tbody>"];
								$.each(info.event.extendedProps, function(k,v) {
								    var t = toTitle(k);
								    html.push(`<tr><th style='width:35%'>${t}</th><td>${v}</td></tr>`);
								})
								html.push("</tbody></table></div>");

								lgksAlert(html.join(""), "Preview")
        				<?php
        			}
        		?>

				    // change the border color just for fun
				    info.el.style.borderColor = 'red';
				  },
				  // events: [
				  //   {
				  //     title  : 'event1',
				  //     start  : '2021-01-01'
				  //   },
				  //   {
				  //     title  : 'event2',
				  //     start  : '2021-01-05',
				  //     end    : '2021-01-07'
				  //   },
				  //   {
				  //     title  : 'event3',
				  //     start  : '2021-01-09T12:30:00',
				  //     allDay : false // will make the time show
				  //   }
				  // ],
				  events: function( info, successCallback, failureCallback ) {
						//https://fullcalendar.io/docs/events-function
				  	//console.log("LOADING_CALENDAR", {start: info.start.valueOf(),end: info.end.valueOf()});

						rpt.fetchReportData("json",function(txt) {
							jsonData=$.parseJSON(txt);
			        if(jsonData==null && jsonData.Data==null) {
			          lgksToast("Sorry, no data found");
			          return;
			        } else {
			          jsonData=jsonData.Data;
			        }
			        
			        var finalEventList = jsonData.RECORDS.map(row=>{
			        		return {"id": ((row["hashid"]==null)?row[Object.keys(row)[0]]:row["hashid"]), "title": row["<?=$colMap["title"]?>"], "start": row["<?=$colMap["date_col"]?>"], extendedProps: row};
							})
							
			        limit=jsonData.INFO.limit;
			        index=jsonData.INFO.index;
			        last=jsonData.INFO.last;
			        max=jsonData.INFO.max;

			        rpt.updateReportMeta(limit, index, last, max);
			        rpt.postDataPopulate(rpt.gridID);

			        console.log("finalEventList", finalEventList);
			        successCallback(finalEventList);
			      }, {
			      	"date_filter[start_date]": moment(info.start.valueOf()).format("Y-MM-DD 00:00:00"),
			      	"date_filter[end_date]": moment(info.end.valueOf()).format("Y-MM-DD 23:59:59"),
			      });
					},
					// validRange: function(nowDate) {
				  //   return {
				  //     start: nowDate,
				  //     end: nowDate.clone().add(1, 'months')
				  //   };
				  // },
				  // businessHours: {
					//   // days of week. an array of zero-based day of week integers (0=Sunday)
					//   daysOfWeek: [ 1, 2, 3, 4, 5, 6 ], // Monday - Saturday

					//   startTime: '10:00', // a start time (10am in this example)
					//   endTime: '18:00', // an end time (6pm in this example)
					// }
      });
      calendar.render();

			rpt.loadDataGrid();
    });
		function renderCalendarUI(gridID, rptHandler) {
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
	        // console.log(jsonData);

	        $.each(jsonData.RECORDS,function(k,v) {
	            // console.log("RECORD", k, v);
	            addCalendarEvent(v);
	        });

	        limit=jsonData.INFO.limit;
	        index=jsonData.INFO.index;
	        last=jsonData.INFO.last;
	        max=jsonData.INFO.max;

	        rpt.updateReportMeta(limit, index, last, max);
	        rpt.postDataPopulate(rpt.gridID);
	      });
		}
		function addCalendarEvent(record) {

		}
    </script>
</div>
