<?php
if(!defined('ROOT')) exit('No direct script access allowed');
// $slug=_slug("moduleName/report/param");
// printArray($slug['report']);
// printArray($reportConfig);exit();

$reportConfig['toolbar']['filter']=false;
$reportConfig['toolbar']['columnselector']=false;

$colMap=[];
if(isset($reportConfig['gmap']['colmap'])) {
  $colMap=$reportConfig['gmap']['colmap'];
}

$unilink=false;
if(isset($reportConfig['gmap']['unilink']) && strlen($reportConfig['gmap']['unilink'])>0) {
  $unilink=$reportConfig['gmap']['unilink'];
}

if(!isset($reportConfig['gmap']['zoom'])) $reportConfig['gmap']['zoom'] = 2;
if(!isset($reportConfig['gmap']['mapid'])) $reportConfig['gmap']['mapid'] = "terrain";//roadmap
if(!isset($reportConfig['gmap']['template'])) $reportConfig['gmap']['template'] = '<div id="map-content"><h1 id="firstHeading" class="firstHeading">$'.'{record["'.$colMap['title'].'"]}</h1><div id="bodyContent">$'.'{record["'.$colMap['descs'].'"]}</div></div>';

$googleKey=getConfig("GOOGLE_API_KEY");

$colMap=array_merge([
       "title"=>"title",
       "descs"=>"descs",
       "geolocation"=>"geolocation",
       "avatar"=>"avatar",
       "icon"=>"icon",
       // "category"=>"category",
       
       // "tags"=>"tags",
       // "counter"=>"counter",
       // "flag"=>"flag",

       // "color"=>"color",//Depends on ColorMap for Logic Based On Column Selected by this field
       //logic: Icons, color
     ],$colMap);

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
<script>
	var rpt=null;
	var map;
  var current_location = null;
  navigator.geolocation.getCurrentPosition(a=>{
    current_location = { lat: a.coords.latitude, lng: a.coords.longitude };
    if(map!=null) {
      map.setCenter(current_location);
    }
  })
	$(function() {
		  rpt=new LGKSReports().init("<?=$reportKey?>","gmap");
      rpt.addRenderer("gmap",renderGMapUI);
      //rpt.addListener(updateGMapUI,"postload");

      if(map!=null) {
        rpt.loadDataGrid();
      }
	});
	function initGMap() {
		map = new google.maps.Map(document.getElementById('gmap'), {
			zoom: <?=$reportConfig['gmap']['zoom']?>,
      mapTypeId: '<?=$reportConfig['gmap']['mapid']?>',
			center: new google.maps.LatLng(17.7199121,75.8663931),
		});

		if(typeof processAJAXPostQuery == "function") rpt.loadDataGrid();
	}
	function renderGMapUI(gridID, rptHandler) {
    if(map==null) return;

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
            addGMAPMarker(v);
        });

        limit=jsonData.INFO.limit;
        index=jsonData.INFO.index;
        last=jsonData.INFO.last;
        max=jsonData.INFO.max;

        rpt.updateReportMeta(limit, index, last, max);
        rpt.postDataPopulate(rpt.gridID);
      });
	}

  function addGMAPMarker(record) {
    var geolocation = record["<?=$colMap['geolocation']?>"];
    if(geolocation==null) return false;
    
    geolocation = geolocation.split(",");
    var marker = new google.maps.Marker({
      position: { lat: parseFloat(geolocation[0]), lng: parseFloat(geolocation[1]) },
      map,
      title: record["<?=$colMap['title']?>"],
    });

    const contentString = `<?=$reportConfig['gmap']['template']?>`;
      
    const infowindow = new google.maps.InfoWindow({
        content: contentString,
        ariaLabel: "Uluru",
      });

    marker.addListener("click", () => {
      infowindow.open({
        anchor: marker,
        map,
      });
    });
  }
	</script>
	<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?=$googleKey?>&callback=initGMap"></script>
</div>
