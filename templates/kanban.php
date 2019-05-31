<?php
if(!defined('ROOT')) exit('No direct script access allowed');

// $slug=_slug("moduleName/report/param");
// printArray($slug['report']);
// printArray($reportConfig);exit();

if(!isset($reportConfig['kanban'])) $reportConfig['kanban']=[];
if(!isset($reportConfig['kanban']['colkeys'])) $reportConfig['kanban']['colkeys']=[];
if(!isset($reportConfig['kanban']['colmap'])) $reportConfig['kanban']['colmap']=[];
if(!isset($reportConfig['buttons'])) $reportConfig['buttons']=[];

$reportConfig['toolbar']['filter']=false;
$reportConfig['toolbar']['columnselector']=false;

$topbar['settings']=[
				"showEmptyColumns"=>[
            "name"=>"SHOWALLCOLS",
            "label"=>"Show Columns with no Cards also",
            "type"=>"checkbox",
					],
        "allowMultipleRecords"=>[
            "name"=>"ALLOWMULTIPLERECORD",
            "label"=>"Allow same block multiple times for different Columns",
            "type"=>"checkbox",
        ]
			];
$topbar['XtraHtmlToolButton']="";

$topbar['XtraHtmlToolButton'].="<select name='kanbanPivot' class='autorefreshReport pivotDropdown form-control pull-right'>";
foreach($reportConfig['kanban']['colkeys'] as $k=>$v) {
	if(!isset($v['label'])) $v['label']=toTitle($k);
	$topbar['XtraHtmlToolButton'].="<option value='{$k}'>{$v['label']}</option>";
}
$topbar['XtraHtmlToolButton'].="</select>";

$topbar['XtraHtmlToolButton'].="<div class='btn-group sortOpts'>";
$topbar['XtraHtmlToolButton'].="<button type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'><span class='glyphicon glyphicon-sort'></span> <span class='caret'></span></button>";
$topbar['XtraHtmlToolButton'].="<ul class='sortType dropdown-menu' aria-labelledby='dropdownMenu' role='menu'>";

foreach($reportConfig['datagrid'] as $colKey=>$colDefn) {
  if(!isset($colDefn['label'])) $colDefn['label']=toTitle(_ling($colKey));
  if($colKey==array_keys($reportConfig['datagrid'])[0]) {
    //$topbar['XtraHtmlToolButton'].="<li><a href='#'><label><input class='colSort' type='radio' name='orderby' value='{$colKey}' onchange='updateSortOrder(event, this)' checked>{$colDefn['label']}</label></a></li>";
    $topbar['XtraHtmlToolButton'].="<li><a href='#'>
        <label class='uicheckbox active'><input class='colSort hidden' type='radio' name='orderby' value='{$colKey} ASC' onchange='updateSortOrder(event, this)' checked>  <i class='fa fa-arrow-up'></i></label>
        <label class='uicheckbox'><input class='colSort hidden' type='radio' name='orderby' value='{$colKey} DESC' onchange='updateSortOrder(event, this)'>  <i class='fa fa-arrow-down'></i></label>
        {$colDefn['label']}</a></li>";
  } else {
    //$topbar['XtraHtmlToolButton'].="<li><a href='#'><label><input class='colSort' type='radio' name='orderby' value='{$colKey}' onchange='updateSortOrder(event, this)'>{$colDefn['label']}</label></a></li>";
    $topbar['XtraHtmlToolButton'].="<li><a href='#'>
        <label class='uicheckbox'><input class='colSort hidden' type='radio' name='orderby' value='{$colKey} ASC' onchange='updateSortOrder(event, this)'>  <i class='fa fa-arrow-up'></i></label>
        <label class='uicheckbox'><input class='colSort hidden' type='radio' name='orderby' value='{$colKey} DESC' onchange='updateSortOrder(event, this)'>  <i class='fa fa-arrow-down'></i></label>
        {$colDefn['label']}</a></li>";
    
  }
}

$topbar['XtraHtmlToolButton'].="</ul>";
$topbar['XtraHtmlToolButton'].="</div>";

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
             "tooltip"=>"tooltip",
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
			 "flag"=>"flag",
			 //logic: Icons, color
		 ],$reportConfig['kanban']['colmap']);


$updateableColumns=array_keys($reportConfig['kanban']['colkeys']);
$reportConfig['updatableColumns']=$updateableColumns;
$_SESSION['REPORT'][$reportKey]=$reportConfig;

	
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
	"showMoreRecords"=>["label"=>"","icon"=>"fa fa-exchange fa-rotate-90","class"=>"btn btn-warning btn-notext","title"=>"Show More Data"]
];
if(!isset($reportConfig['actions'])) $reportConfig['actions']=[];
$reportConfig['actions']=array_merge($actions,$reportConfig['actions']);

?>
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
    <?php
       if(empty($reportConfig['kanban']['colkeys'])) {
          echo "<h1 align=center>Sorry, Kanban Mode not supported by this report.</h1>";
       } else {
    ?>
    <div class='kanbanBoardContainer reportContainer'>
      <div class='kanbanBoard reportBoard'>
      </div>
    </div>
  
    <div class='kanbanColumnTemplate hidden'>
      <div data-colkey='{{value}}' class="panel panel-primary {{class}} kanban-col">
        <div class="panel-heading">
            <span class='count label label-info'>{{count}}</span>
            {{title}}
        </div>
        <div class="panel-body">
        </div>
      </div>
    </div>
    <div class='kanbanCardTemplate hidden'>
      <article class="kanban-entry grab rColor {{cardColor <?=$colMap['color']?>}}" id="item{{id}}" data-hash='{{hashid}}'>
        <div class="kanban-entry-inner">
					{{#if <?=$colMap['avatar']?>}}
					<figure class='avatar'>
						<img src="{{<?=$colMap['avatar']?>}}" class="img-responsive img-rounded full-width">
					</figure>
					{{/if}}
          <div class="kanban-label" title="{{<?=$colMap['tooltip']?>}}">
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
						
						<div class='kanban-icon'>
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
					//<i class='kicon fa fa-{{this}}'><citie>67</citie></i>
					return "";
				});
			
      var rpt=new LGKSReports().init("<?=$reportKey?>","kanban");
			rpt.addRenderer("kanban",renderKanbanUI);
			rpt.addListener(updateKanbanUI,"postload");
			
			if(rpt.settings("kanban-empty-columns")) {
				rpt.getGrid().find(".reportOpts input[name=SHOWALLCOLS]").each(function() {
					this.checked=true;
				});
			}
      
      sortKey=rpt.settings("sort");
      if(sortKey!=null) {
        if($(".sortOpts input[value='"+sortKey+"']").length>0) {
          $(".sortOpts input[value='"+sortKey+"']")[0].checked=true;
          $(".sortOpts label").removeClass("active");
          $(".sortOpts input[value='"+sortKey+"']").closest("label").addClass("active");
        }
      }
			
			rpt.loadDataGrid();
    });
	function renderKanbanUI(gridID, rptHandler) {
		gridBody=$(".kanbanBoard","#RPT-"+gridID);
		if(gridBody.find(".kanban-col").length<=0) {
			return generateKanbanUI(rptHandler);
		} else {
			return loadKanban(rptHandler);
		}
	}
	function generateKanbanUI(rpt) {
			grid=rpt.getGrid();
			gridBody=$(".kanbanBoard","#RPT-"+this.gridID);
			gridID=grid.data('rptkey');

			gridBody.html('<div class="ajaxloading ajaxloading3"></div>');

			pivot=grid.find("select[name=kanbanPivot]").val();

			lx=_service("reports","enumerateColumn","json")+"&gridid="+this.gridID+"&colKey="+pivot;
			processAJAXQuery(lx,function(txt) {
				jsonData=$.parseJSON(txt);
				if(jsonData==null) {
					return;
				}
				jsonData=jsonData.Data;

				gridBody=$(".kanbanBoard","#RPT-"+rpt.gridID);
				gridTemplate=$(".kanbanColumnTemplate","#RPT-"+rpt.gridID).html();
				gridColUIGen=Handlebars.compile(gridTemplate);

				html="";
				$.each(jsonData,function(k,v) {
					html+=gridColUIGen(v);
				});
				gridBody.html(html);

				nx=gridBody.find(".kanban-col").length;
				q=$(gridBody.find(".kanban-col")[0]).width();
				gridBody.css("width",((nx*q)+100)+'px');
				
				$(".kanban-col .panel-body").sortable({
						connectWith: ".kanban-col .panel-body",
						stop: function(event, ui) {
							item=$(ui.item);
							col=$(ui.item).closest(".kanban-col");
							dataField=grid.find("select[name=kanbanPivot]").val();
							dataHash=$(item).data("hash");
							dataVal=$(col).data("colkey");
							gridID=$(this).closest(".reportTable").data("rptkey");
							//console.log([dataField,dataHash,dataVal]);
							
							$(item).data("oldcol",dataVal);

							q="gridid="+gridID+"&dataField="+dataField+"&dataHash="+dataHash+"&dataVal="+dataVal;
							processAJAXPostQuery(_service("reports","updateFieldValue"),q,function(ans) {
								ans=$.parseJSON(ans);
								if(ans.Data.msg!="done") {
									item=$("article[data-hash="+ans.Data.hash+"]");
									if(item.length>0) {
										oldkey=item.data("oldcol");
										item.addClass("error");
										$(".kanban-col[data-colkey="+oldkey+"] .panel-body").prepend(item);
										
										lgksToast(ans.Data.msg);
										
										setTimeout(function() {
											$(".reportContainer article.error").removeClass("error");
										},1800);
									}
								}
							});
							return true;
						}
					}).disableSelection();

				loadKanban(rpt);
			});
	}
	function loadKanban(rpt) {
			grid=rpt.getGrid();
			gridBody=$(".kanbanBoard","#RPT-"+this.gridID);
			gridID=grid.data('rptkey');

			if(grid.data("page")==grid.data("current") && grid.data("page")!=null) {
        		if(typeof lgksToast=="function") lgksToast("All the records are loaded");
				return false;
			}

			gridBody.find(".kanban-col .panel-body").append('<div class="ajaxloading ajaxloading3"></div>');

			rpt.fetchReportData("json",function(txt) {
					grid=rpt.getGrid();
					gridBody=$(".kanbanBoard","#RPT-"+rpt.gridID);
					gridTemplate=$(".kanbanCardTemplate","#RPT-"+rpt.gridID).html();

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

					pivotColumn=$("select[name=kanbanPivot]","#RPT-"+rpt.gridID).val();
        
          			allowMULTIPLE=grid.find(".reportOpts input[name=ALLOWMULTIPLERECORD]").is(":checked");

					//rpt.appendRecord
					$.each(jsonData.RECORDS,function(k,v) {
			            rValue=v[pivotColumn];
			            if(rValue!=null && rValue.length>0) {
			              rValueArr=rValue.split(",");
			              if(rValueArr.length>1) {
			                $.each(rValueArr, function(k1, rValue1) {
			                  if(gridBody.find(".kanban-col[data-colkey='"+rValue1+"'] .panel-body").length>0) {
			                    cardHTML=gridCardGen(v);
			                    if(allowMULTIPLE) {
			                      gridBody.find(".kanban-col[data-colkey='"+rValue1+"'] .panel-body").append(cardHTML);
			                    } else {
			                      if(gridBody.find(".kanban-entry[data-hash='"+v.hashid+"']").length<=0) {
			                        gridBody.find(".kanban-col[data-colkey='"+rValue1+"'] .panel-body").append(cardHTML);
			                      }	else {
			                        $(gridBody.find(".kanban-entry[data-hash='"+v.hashid+"']")).replaceWith(cardHTML);
			                      }
			                    }
			                  } else {
			          // 					console.log(v);
			                  }
			                });
			              } else {
			                if(gridBody.find(".kanban-col[data-colkey='"+rValue+"'] .panel-body").length>0) {
			                  cardHTML=gridCardGen(v);
			                  if(gridBody.find(".kanban-entry[data-hash='"+v.hashid+"']").length<=0) {
			                    gridBody.find(".kanban-col[data-colkey='"+rValue+"'] .panel-body").append(cardHTML);
			                  }	else {
			                    $(gridBody.find(".kanban-entry[data-hash='"+v.hashid+"']")).replaceWith(cardHTML);
			                  }
			                } else {
			        // 					console.log(v);
			                }
			              }
			            }
					});

					nx=gridBody.find(".kanban-col:not(.hidden)").length;
					q=$(gridBody.find(".kanban-col")[0]).width();
					gridBody.css("width",((nx*q)+100)+'px');

					limit=jsonData.INFO.limit;
					index=jsonData.INFO.index;
					last=jsonData.INFO.last;
					max=jsonData.INFO.max;

					rpt.updateReportMeta(limit, index, last, max);
					rpt.postDataPopulate(rpt.gridID);
				});
	}
	function showEmptyColumns(btn){
		gkey=$(btn).closest(".reportTable").data("rptkey");
		updateKanbanUI(gkey);
	}
    function allowMultipleRecords(btn){
      gkey=$(btn).closest(".reportTable").data("rptkey");
			updateKanbanUI(gkey);
      rpt.reloadDataGrid();
    }
    function updateSortOrder(event, src) {
      //event.stopPropagation();
      $(src).closest("ul").find("label.active").removeClass("active");
      $(src).closest("label").addClass("active");
      rpt.reloadDataGrid();
    }
    function updateKanbanUI(rkey){
			rpt=LGKSReports.getInstance(rkey);
      grid=LGKSReports.getInstance(rkey).getGrid();
      gridBody=$(".kanbanBoard","#RPT-"+rkey);
      
      clz="hidden";
      allowMULTIPLE=false;
			if(grid.find(".reportOpts input[name=SHOWALLCOLS]").is(":checked")) {
				clz="hiddenx";
        gridBody.find(".kanban-col.hidden").removeClass("hidden");
			}
      if(grid.find(".reportOpts input[name=ALLOWMULTIPLERECORD]").is(":checked")) {
				allowMULTIPLE=true;
			}
			gridBody.find(".kanban-col").each(function() {
				cx=$(this).find(".panel-body").children().length;
				if(cx<=0) {
					$(this).addClass(clz);
				} else {
					$(this).removeClass(clz);
				}
				$(this).find(".panel-heading .count").text(cx);
			});
			
			nx=gridBody.find(".kanban-col:not(.hidden)").length;
			q=$(gridBody.find(".kanban-col")[0]).width();
			gridBody.css("width",((nx*q)+100)+'px');
			
			rpt.settings("kanban-empty-columns",grid.find(".reportOpts input[name=SHOWALLCOLS]").is(":checked"));
      rpt.settings("kanban-multiple-cards",grid.find(".reportOpts input[name=ALLOWMULTIPLERECORD]").is(":checked"));
    }
    </script>
    <?php
    }
    ?>
</div>
