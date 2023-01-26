<?php
if(!defined('ROOT')) exit('No direct script access allowed');
// $slug=_slug("moduleName/report/param");
// printArray($slug['report']);
// printArray($reportConfig);exit();

if(!isset($reportConfig['buttons'])) $reportConfig['buttons']=[];

$reportConfig['toolbar']['filter']=false;
$reportConfig['toolbar']['columnselector']=false;

$colMap=[];
if(isset($reportConfig['gallery']['colmap'])) {
	$colMap=$reportConfig['gallery']['colmap'];
} elseif(isset($reportConfig['kanban']['colmap'])) {
	$colMap=$reportConfig['kanban']['colmap'];
}

$htmlButtons="";
foreach ($reportConfig['buttons'] as $cmd => $button) {
	if(!isset($button['icon'])) continue;
	if(!isset($button['label'])) $button['label']="";
	if(!isset($button['class'])) $button['class']="";
	
	$cmd=str_replace("{","{{",str_replace("}","}}",$cmd));

	$htmlButtons.="<i class='kicon {$button['icon']} {$button['class']} pull-right' cmd='{$cmd}' title='{$button['label']}'></i>";
}


$colMap=array_merge([
			 "title"=>"title",
			 "category"=>"category",
			 "descs"=>"descs",
			 //"msg"=>"msg",
			 //"due_date"=>"due_date",

			 "photo"=>"photo",
				
			 //"tags"=>"tags",
			 //"counter"=>"counter",
			 //"flag"=>"flag",

			 //"color"=>"color",//Depends on ColorMap for Logic Based On Column Selected by this field
			 "icons"=>"icons",
			 //logic: Icons, color
		 ],$colMap);

$unilink=false;
if(isset($reportConfig['gallery']['unilink']) && strlen($reportConfig['gallery']['unilink'])>0) {
	$unilink=$reportConfig['gallery']['unilink'];
}
$colorMap=[];
if(isset($reportConfig['gallery']['colormap'])) {
	$colorMap=$reportConfig['gallery']['colormap'];
	if($colorMap==null || !is_array($colorMap)) $colorMap=[];
}
$iconMap=[];
if(isset($reportConfig['gallery']['iconmap'])) {
	$iconMap=$reportConfig['gallery']['iconmap'];
	if($iconMap==null || !is_array($iconMap)) $iconMap=[];
}

$actions=[
	"showMoreRecords"=>["label"=>"","icon"=>"fa fa-retweet","class"=>"btn btn-warning btn-notext","title"=>"Showing 0/0 records"]
];
$reportConfig['actions']=array_merge($actions,$reportConfig['actions']);
?>
<div id='RPT-<?=$reportKey?>' data-rptkey='<?=$reportKey?>' data-gkey='<?=$reportConfig['reportgkey']?>' class="reportTable galleryBoardTable table-responsive">
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
   
		<div class='galleryContainer reportContainer'>
      <div class='galleryBoard reportBoard'>
      </div>
    </div>
  
    <div class='galleryCardTemplate hidden'>
    	<?php
    		if(isset($reportConfig['gallery']['template'])) {
    			echo $reportConfig['gallery']['template'];
    		} else {
    			?>
    			<article class="dataItem gallery-entry grab" id="item{{id}}" data-hash='{{hashid}}' data-refid='{{id}}'>
		        <div class="gallery-entry-inner">
							<div class="gallery-label">
								{{#if <?=$colMap['photo']?>}}
									<?php if($unilink) { ?>
										{{#if hashid}}
												<a class='unilink' href='#' data-type="<?=$unilink?>" data-hashid="{{hashid}}">
											{{else}}
												<a href='#'>
											{{/if}}
											<figure class='photo ajaxloading ajaxloading8'>
												<img src="{{<?=$colMap['photo']?>}}" class="img-responsive img-rounded full-width hidden"  onload='$(this).removeClass("hidden");'>
											</figure>
										</a>
									<?php } else { ?>
										<figure class='photo ajaxloading ajaxloading8'>
											<img src="{{<?=$colMap['photo']?>}}" class="img-responsive img-rounded full-width hidden"  onload='$(this).removeClass("hidden");'>
										</figure>
									<?php } ?>
								{{/if}}
								<h2>
									<a class='unilink' href='#'>{{<?=$colMap['title']?>}}</a>
		            </h2>
								{{#if <?=$colMap['category']?>}}
									<h3>{{<?=$colMap['category']?>}}</h3>
								{{/if}}
								{{#if <?=$colMap['descs']?>}}
									<p>{{{<?=$colMap['descs']?>}}}</p>
								{{/if}}
								
								<div class='gallery-icon'>
									{{#if <?=$colMap['icons']?>}}
										{{galleryIcon '<?=$colMap['icons']?>' this}}
									{{/if}}
									<?=$htmlButtons?>
								</div>
		          </div>
		        </div>
		      </article>
    			<?php
    		}
    		?>
    </div>
    <script>
		colorMap=<?=json_encode($colorMap)?>;
		iconMap=<?=json_encode($iconMap)?>;
    $(function() {
			Handlebars.registerHelper('galleryColor', function(clrValue) {
					if(colorMap[clrValue]!=null) return colorMap[clrValue];
					return "";
				});
			Handlebars.registerHelper('galleryIcon', function(iconValue, record) {
// 					console.log(record);
// 					console.log(iconValue);
					//<i class='kicon fa fa-{{this}}'><citie>45</citie></i>
					return "";
				});
			
	    var rpt=new LGKSReports().init("<?=$reportKey?>","gallery");
			rpt.addRenderer("gallery",renderCardsUI);
			//rpt.addListener(updateCardsUI,"postload");
			
			rpt.loadDataGrid();
    });
		function resetCardsUI(gridID, rpt) {
			gridBody.html('<div class="ajaxloading ajaxloading3"></div>');
		}
		function renderCardsUI(gridID, rpt1) {
			rpt = LGKSReportsInstances[gridID];
			grid=rpt.getGrid();
			gridBody=$(".galleryContainer .galleryBoard","#RPT-"+this.gridID);
			gridID=grid.data('rptkey');

			if(grid.data("page")==grid.data("current") && grid.data("page")!=null) {
				if(typeof lgksToast=="function") lgksToast("All the records are loaded");
				return false;
			}
			
			gridBody.append('<div class="ajaxloading ajaxloading3"></div>');

			rpt.fetchReportData("json",function(txt) {
					grid=rpt.getGrid();
					gridBody=$(".galleryContainer .galleryBoard","#RPT-"+rpt.gridID);
					gridTemplate=$(".galleryCardTemplate","#RPT-"+rpt.gridID).html();

					gridBody.find(".ajaxloading").detach();

					jsonData=$.parseJSON(txt);
					if(jsonData==null && jsonData.Data==null) {
						gridBody.html('<div class="error error-msg">Sorry, no data found</div>');
						return;
					} else {
						jsonData=jsonData.Data;
					}
		// 			console.log(jsonData);

					//Generate Cards
					gridCardGen=Handlebars.compile(gridTemplate);

					//rpt.appendRecord
					$.each(jsonData.RECORDS,function(k,v) {
						galleryHTML=gridCardGen(v);
						if(gridBody.find(".gallery-entry[data-hash='"+v.hashid+"']").length<=0) {
							gridBody.append(galleryHTML);
						}	else {
							$(gridBody.find(".gallery-entry[data-hash='"+v.hashid+"']")).replaceWith(galleryHTML);
						}
					});

					limit=jsonData.INFO.limit;
					index=jsonData.INFO.index;
					last=jsonData.INFO.last;
					max=jsonData.INFO.max;

					rpt.updateReportMeta(limit, index, last, max);
					rpt.postDataPopulate(rpt.gridID);
				});
		}
    </script>
</div>
