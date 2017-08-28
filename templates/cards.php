<?php
if(!defined('ROOT')) exit('No direct script access allowed');
// $slug=_slug("moduleName/report/param");
// printArray($slug['report']);
// printArray($reportConfig);exit();

if(!isset($reportConfig['buttons'])) $reportConfig['buttons']=[];

$reportConfig['toolbar']['filter']=false;
$reportConfig['toolbar']['columnselector']=false;

$colMap=[];
if(isset($reportConfig['cards']['colmap'])) {
	$colMap=$reportConfig['cards']['colmap'];
} elseif(isset($reportConfig['kanban']['colmap'])) {
	$colMap=$reportConfig['kanban']['colmap'];
}

$htmlButtons="";
foreach ($reportConfig['buttons'] as $cmd => $button) {
	if(!isset($button['icon'])) continue;
	if(!isset($button['label'])) $button['label']="";
	if(!isset($button['class'])) $button['class']="";

	$htmlButtons.="<i class='kicon {$button['icon']} {$button['class']} pull-right' cmd='{$cmd}' title='{$button['label']}'></i>";
}


$colMap=array_merge([
			 "title"=>"title",
			 "category"=>"category",
			 "descs"=>"descs",
			 "msg"=>"msg",
			 "due_date"=>"due_date",

			 "image"=>"image",
			 "avatar"=>"avatar",
			 "wallphoto"=>"wallphoto",
				
			 "tags"=>"tags",
			 "counter"=>"counter",

			 "color"=>"color",//Depends on ColorMap for Logic Based On Column Selected by this field
			 "icons"=>"icons",
			 //logic: Icons, color
		 ],$colMap);

$unilink=false;
if(isset($reportConfig['kanban']['unilink']) && strlen($reportConfig['kanban']['unilink'])>0) {
	$unilink=$reportConfig['kanban']['unilink'];
}
$colorMap=[];
if(isset($reportConfig['kanban']['colormap'])) {
	$colorMap=$reportConfig['kanban']['colormap'];
	if($colorMap==null || !is_array($colorMap)) $colorMap=[];
}
$iconMap=[];
if(isset($reportConfig['kanban']['iconmap'])) {
	$iconMap=$reportConfig['kanban']['iconmap'];
	if($iconMap==null || !is_array($iconMap)) $iconMap=[];
}

$actions=[
	"showMoreRecords"=>["label"=>"","icon"=>"fa fa-exchange fa-rotate-90","class"=>"btn btn-warning btn-notext","title"=>"Showing 0/0 records"]
];
$reportConfig['actions']=array_merge($actions,$reportConfig['actions']);
?>
<div id='RPT-<?=$reportKey?>' data-rptkey='<?=$reportKey?>' data-gkey='<?=$reportConfig['reportgkey']?>' class="reportTable cardsBoardTable table-responsive">
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
   
		<div class='cardsContainer reportContainer'>
      <div class='cardsBoard'>
      </div>
    </div>
  
    <div class='cardsCardTemplate hidden'>
      <article class="cards-entry grab rColor {{color}}" id="item{{id}}" data-hash='{{hashid}}'>
        <div class="cards-entry-inner">
					{{#if <?=$colMap['avatar']?>}}
					<figure class='avatar'>
						<img src="{{<?=$colMap['avatar']?>}}" class="img-responsive img-rounded full-width">
					</figure>
					{{/if}}
          <div class="cards-label">
						{{#if <?=$colMap['wallphoto']?>}}
						<figure class='wallphoto'>
							<img src="{{<?=$colMap['wallphoto']?>}}" class="img-responsive img-rounded full-width">
						</figure>
						{{/if}}
						<h2>
							<span class='pull-right label label-info'>{{<?=$colMap['counter']?>}}</span>
							<?php if($unilink) { ?>
								{{#if hashid}}
								<a class='unilink' href='#' data-type="<?=$unilink?>" data-hashid="{{hashid}}">{{<?=$colMap['title']?>}}</a>
								{{else}}
								<a class='unilink' href='#'>{{<?=$colMap['title']?>}}</a>
								{{/if}}
							<?php } else { ?>
								<a href='#'>{{<?=$colMap['title']?>}}</a>
							<?php } ?>
            </h2>
						{{#if <?=$colMap['category']?>}}
						<h3>{{<?=$colMap['category']?>}}</h3>
						{{/if}}
						{{#if <?=$colMap['image']?>}}
						<figure>
							<img src="{{<?=$colMap['image']?>}}" class="img-responsive img-rounded full-width">
						</figure>
            {{/if}}
						{{#if <?=$colMap['descs']?>}}
						<p>{{{<?=$colMap['descs']?>}}}</p>
						{{/if}}
						{{#if <?=$colMap['msg']?>}}
						<blockquote>{{{<?=$colMap['msg']?>}}}</blockquote>
						{{/if}}
						<div class='tags'>
							<div class='label label-success due_date pull-right'>{{<?=$colMap['due_date']?>}}</div>
							
							{{#if <?=$colMap['tags']?>}}
								{{#each <?=$colMap['tags']?>}}
									<span class="label label-primary">{{this}}</span>
								{{/each}}
							{{/if}}
						</div>
						
						<div class='cards-icon'>
							{{#if <?=$colMap['icons']?>}}
								{{cardIcon '<?=$colMap['icons']?>' this}}
							{{/if}}
							<?=$htmlButtons?>
						</div>
          </div>
        </div>
      </article>
    </div>
    <script>
		colorMap=<?=json_encode($colorMap)?>;
		iconMap=<?=json_encode($iconMap)?>;
    $(function() {
			Handlebars.registerHelper('cardColor', function(clrValue) {
					if(colorMap[clrValue]!=null) return colorMap[clrValue];
					return "";
				});
			Handlebars.registerHelper('cardIcon', function(iconValue, record) {
// 					console.log(record);
// 					console.log(iconValue);
					//<i class='kicon fa fa-{{this}}'><citie>45</citie></i>
					return "";
				});
			
      var rpt=new LGKSReports().init("<?=$reportKey?>","cards");
			rpt.addRenderer("cards",renderCardsUI);
			rpt.addRenderer("card",renderCardsUI);
			//rpt.addListener(updateCardsUI,"postload");
			rpt.loadDataGrid();
    });
		function renderCardsUI(gridID, rpt) {
			grid=rpt.getGrid();
			gridBody=$(".cardsContainer .cardsBoard","#RPT-"+this.gridID);
			gridID=grid.data('rptkey');

			if(grid.data("page")==grid.data("current") && grid.data("page")!=null) {
				return false;
			}

			gridBody.append('<div class="ajaxloading ajaxloading3"></div>');

			rpt.fetchReportData("json",function(txt) {
					grid=rpt.getGrid();
					gridBody=$(".cardsContainer .cardsBoard","#RPT-"+rpt.gridID);
					gridTemplate=$(".cardsCardTemplate","#RPT-"+rpt.gridID).html();

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
						gridBody.append(gridCardGen(v));
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
