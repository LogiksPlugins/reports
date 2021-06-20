<?php
if(!defined('ROOT')) exit('No direct script access allowed');
// $slug=_slug("moduleName/report/param");
// printArray($slug['report']);
// printArray($reportConfig);exit();

$reportConfig['toolbar']['filter']=false;
$reportConfig['toolbar']['columnselector']=false;

$googleKey=getConfig("GOOGLE_API_KEY");
// echo $vpath;
?>
<style>
.gmapContainer {
	height: 90%;
	height: calc(100% - 50px);
}
#gmap {
	height: 100%;
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

    <div class='gmapContainer reportContainer'>
      <div id='gmap'></div>
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
	var rpt=null;
	var map;
	$(function() {
		rpt=new LGKSReports().init("<?=$reportKey?>","gmap");
		rpt.addRenderer("gmap",renderGMapUI);
		//rpt.addListener(updateCalendarUI,"postload");
	});
	function initGMap() {
		map = new google.maps.Map(document.getElementById('gmap'), {
			zoom: 2,
			center: new google.maps.LatLng(17.7199121,75.8663931),
			mapTypeId: 'terrain'
		});

//         // Create a <script> tag and set the USGS URL as the source.
//         var script = document.createElement('script');
//         // This example uses a local copy of the GeoJSON stored at
//         // http://earthquake.usgs.gov/earthquakes/feed/v1.0/summary/2.5_week.geojsonp
//         script.src = 'https://developers.google.com/maps/documentation/javascript/examples/json/earthquake_GeoJSONP.js';
//         document.getElementsByTagName('head')[0].appendChild(script);


// 			rpt.loadDataGrid();
	}
	function renderGMapUI(gridID, rptHandler) {

	}
	</script>
	<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?=$googleKey?>&callback=initGMap"></script>
</div>
