<div class="control-primebar tools_type1">
  <div class="col-lg-6 col-xs-6 pull-left control-toolbar">
    <?php
      if(isset($reportConfig['actions']) && is_array($reportConfig['actions']) && count($reportConfig['actions'])>0) {
    ?>
      <?php
        foreach ($reportConfig['actions'] as $key => $button) {
          if(isset($button['policy']) && strlen($button['policy'])>0) {
            $allow=checkUserPolicy($button['policy']);
            if(!$allow) continue;
          }
//           printArray($button);printArray();checkUserRoles($reportConfig['srckey'],);
          if(isset($button['label'])) $button['label']=_ling($button['label']);
          else $button['label']=_ling($key);
          
          if(isset($button['title'])) $button['title']=_ling($button['title']);
          else $button['title']="";

          if(!isset($button['class'])) $button['class']="btn btn-primary";
          echo "<a class='{$button['class']}' cmd='{$key}' title='{$button['title']}' data-refid='"._replace("#refid#")."' >";
          if(isset($button['icon']) && strlen($button['icon'])>0) {
            if(strpos($button['icon'],"<")!==false) {
              echo $button['icon'];
            } else {
              echo "<i class='{$button['icon']}'></i> ";
            }
          }
          echo " <span class='btn-label'>{$button['label']}</span></a>";
        }
      ?>
    <?php
      } else {
        echo "<h1 class='reportTitle'>{$reportConfig['title']}</h1>";
      }
    ?>
  </div>

  <div class="col-lg-6 col-xs-6 pull-right uitype_type1" style="padding-right: 0px;">
          <div class="report-searchbar">
            <?php
              if(isset($reportConfig['date_filter']) && $reportConfig['date_filter']) {
                  if(isset($reportConfig['date_filter']['type'])) $fieldType = $reportConfig['date_filter']['type'];
                  else $fieldType = "date";

                  $maxDate = "";
                  $minDate = "";
                  $defaultValueMin = "";
                  $defaultValueMax = "";

                  if(isset($reportConfig['date_filter']['max'])) $maxDate = date('Y-m-d', strtotime($reportConfig['date_filter']['max']));
                  if(isset($reportConfig['date_filter']['min'])) $minDate = date('Y-m-d', strtotime($reportConfig['date_filter']['min']));

                  if(isset($reportConfig['date_filter']['default_max'])) $defaultValueMax = date('Y-m-d', strtotime($reportConfig['date_filter']['default_max']));
                  elseif(isset($reportConfig['date_filter']['default'])) $defaultValueMax = date('Y-m-d', strtotime($reportConfig['date_filter']['default']));
                  if(isset($reportConfig['date_filter']['default_min'])) $defaultValueMin = date('Y-m-d', strtotime($reportConfig['date_filter']['default_min']));
                  elseif(isset($reportConfig['date_filter']['default'])) $defaultValueMin = date('Y-m-d', strtotime($reportConfig['date_filter']['default']));
                ?>
                <div class="dateBox date_filter">
                  <input name='start_date' type="<?=$fieldType?>" class="form-control" value="<?=$defaultValueMin?>" min="<?=$minDate?>" onchange="$(this).next().attr('min', this.value);" />
                  <input name='end_date' type="<?=$fieldType?>" class="form-control" value="<?=$defaultValueMax?>" max="<?=$maxDate?>" onchange="$(this).prev().attr('max', this.value);" />
                </div>
                <?php
              }
            ?>
            <div class='input-group'>
              <div class="input-group-btn">
                <?php
                  if(!isset($reportConfig['toolbar']['search']) || $reportConfig['toolbar']['search']) {
                    echo '<input name="q" placeholder="'._ling("Search").' ..." type="text" class="form-control searchfield searchicon" style="min-width: 250px;">';
                  }
                ?>
                <?php
                  if(!isset($reportConfig['toolbar']['reload']) || $reportConfig['toolbar']['reload']) {
                    echo '<button type="button" cmd="refresh" class="btn btn-default" style="margin: 0px;"><span class="glyphicon glyphicon-refresh"></span></button>';
                  }
                ?>
              </div>
            </div>
          </div>

          <?php
            if(strlen($topbar['XtraHtmlToolButton'])>2) {
              echo "<div class='input-group pull-left' style='text-align: right; width:70%;'>";
            } else {
              echo "<div class='input-group pull-right' style='text-align: right;'>";
            }
          ?>
          <div class="input-group-btn">
              <?php
              if(!isset($reportConfig['toolbar']['filter']) || $reportConfig['toolbar']['filter']) {
              ?>
              <button type="button" cmd='filterbar' class="btn btn-default">
                <span class="glyphicon glyphicon-filter"></span>
                <small class='button_label'><?=_ling("Filter")?></small>
              </button>
              <?php
                }
                //!isset($reportConfig['toolbar']['export']) || 
              if($reportConfig['toolbar']['export'] || $reportConfig['toolbar']['print'] || $reportConfig['toolbar']['email']) {
            ?>
            <div class='btn-group'>
                  <button type="button" class="btn btn-default btn-print dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <span class="glyphicon glyphicon-print"></span><!-- <span class="caret"></span> --> <small class='button_label'><?=_ling("Export")?></small></button>
                      <ul class="reportActions dropdown-menu" aria-labelledby="dropdownMenu" role='menu'>
                        <?php
                          if(!isset($reportConfig['toolbar']['print']) || $reportConfig['toolbar']['print']) {
                            echo "<li><a href='#' cmd='report:print'>"._ling("Print Report")."</a></li>";
                          }

                          if(!isset($reportConfig['toolbar']['export']) || $reportConfig['toolbar']['export']===true) {
                            $reportConfig['toolbar']['export'] = getExportMediumList();
                          } elseif(is_array($reportConfig['toolbar']['export'])) {
                            $reportConfig['toolbar']['export'] = getExportMediumList($reportConfig['toolbar']['export']);
                          } else {
                            $reportConfig['toolbar']['export'] = [];
                          }

                          if(is_array($reportConfig['toolbar']['export'])) {
                            foreach ($reportConfig['toolbar']['export'] as $key => $text) {
                              switch ($key) {
                                case 'pdf':
                                  if(checkVendor('mpdf')) {
                                    echo "<li><a href='#' cmd='report:export{$key}'>"._ling($text)."</a></li>";
                                  }
                                  break;
                                case 'email':
                                  if(checkModule('msgComposer')) {
                                    echo "<li><a href='#' cmd='report:email1'>"._ling($text)."</a></li>";
                                  } elseif(checkModule('liteComposer')) {
                                    echo "<li><a href='#' cmd='report:email2'>"._ling($text)."</a></li>";
                                  }
                                  break;
                                  
                                default:
                                  echo "<li><a href='#' cmd='report:export{$key}'>"._ling($text)."</a></li>";
                                  break;
                              }
                            }
                          }
                        ?>
                      </ul>
              </div>
              <?php
                }
              ?>
              <?php
                if($reportConfig['uiswitcher']) {
              ?>
              <div class="uiswitcher">
                <div class="btn-group">
                  <?php
                    if($templateViews) {
                      if(count($templateViews)<=3) {
                        foreach($templateViews as $v=>$conf) {
                          if(isset($conf['title'])) $vt = _ling($conf['title']);
                          else $vt = toTitle(_ling($v));
                          if($v==$reportConfig['template']) {
                            echo "<button type='button' cmd='ui@{$v}' class='btn btn-default btn-active' title='{$vt}'><span class='{$conf['icon']}'></span></button>";
                          } else {
                            echo "<button type='button' cmd='ui@{$v}' class='btn btn-default' title='{$vt}'><span class='{$conf['icon']}'></span></button>";
                          }
                        }
                      } else {
                        $set1 = array_slice($templateViews, 0, 3);
                        $set2 = array_slice($templateViews, 3);
                        foreach($set1 as $v=>$conf) {
                          if(isset($conf['title'])) $vt = _ling($conf['title']);
                          else $vt = toTitle(_ling($v));
                          if($v==$reportConfig['template']) {
                            echo "<button type='button' cmd='ui@{$v}' class='btn btn-default btn-active' title='{$vt}'><span class='{$conf['icon']}'></span></button>";
                          } else {
                            echo "<button type='button' cmd='ui@{$v}' class='btn btn-default' title='{$vt}'><span class='{$conf['icon']}'></span></button>";
                          }
                        }
                        echo '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&nbsp;<span class="caret"></span></button>';
                        echo '<ul class="dropdown-menu">';
                        foreach($set2 as $v=>$conf) {
                          if(isset($conf['title'])) $vt = _ling($conf['title']);
                          else $vt = toTitle(_ling($v));
                          if($v==$reportConfig['template']) {
                            echo "<li><a href='#' cmd='ui@{$v}'>{$vt} View <i class='fa fa-check pull-right'></i></a></li>";
                          } else {
                            echo "<li><a href='#' cmd='ui@{$v}'>{$vt} View</a></li>";
                          }
                        }
                        echo '</ul>';
                      }
                    }
                  ?>
                </div>
              </div>
              <?php
                }
                if(!isset($reportConfig['toolbar']['columnselector']) || $reportConfig['toolbar']['columnselector']) {
              ?>
              <div class='btn-group'>
                <button type="button" class="btn btn-default btn-reports-toggle">
                  <span class="fa fa-columns"></span> <small class='button_label'><?=_ling("Cols")?></small><!-- <span class="caret" style='    margin-top: 10%;'></span> --></button>
                  <ul class="columnFilter dropdown-menu" aria-labelledby="dropdownMenu" role='menu' onclick="event.stopPropagation()">
                  <?php
                    echo "<li class='bg-info text-white'><a href='#'><label><input class='allColumns' type='checkbox'>"._ling("Check All")."</label></a></li>";
                    foreach ($reportConfig['datagrid'] as $colID => $column) {
                      $colIDS=$colID;//str_replace(".","_",$colID);
                      if(isset($column['noshow']) && $column['noshow']===true) continue;
                      if(isset($column['hidden']) && $column['hidden']) {
                        echo "<li class='colcheckbox'><a href='#'><label><input class='columnName' type='checkbox' name='{$colIDS}'>"._ling($column['label'])."</label></a></li>";
                      } else {
                        echo "<li class='colcheckbox'><a href='#'><label><input class='columnName' type='checkbox' name='{$colIDS}' checked=true>"._ling($column['label'])."</label></a></li>";
                      }
                    }
                  ?>
                  </ul>
              </div>
              <?php
                }
                if($topbar['settings'] && is_array($topbar['settings']) && count($topbar['settings'])>0) {
              ?>
                  <div class="btn-group reportOpts">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-cog"></span> <span class="caret"></span></button>
                      <ul class="reportType dropdown-menu" aria-labelledby="dropdownMenu" role='menu'>
                      <?php
                        foreach($topbar['settings'] as $a=>$b) {
                          if(!isset($b['label'])) $b['label']=toTitle($a);
                          if(!isset($b['name'])) $b['name']=rand();
                          if(!isset($b['type'])) $b['type']="checkbox";
                          if($b['type']=="checkbox") {
                            echo "<li><a href='#'><label><input type='checkbox' name='{$b['name']}' onchange='{$a}(this)'>{$b['label']}</label></a></li>";
                          } elseif($b['type']=="radio") {
                            echo "<li><a href='#'><label><input type='checkbox' name='{$b['name']}' onchange='{$a}(this)'>{$b['label']}</label></a></li>";
                          } else {
                            echo "<li><a href='#' onclick='{$a}(this)'><label>{$b['label']}</label></a></li>";
                          }//class='columnName' 
                        }
                      ?>
                      </ul>
                  </div>
              <?php
                }
              ?>
          </div>
      </div>
      <?=$topbar['XtraHtmlToolButton']?>
  </div>
</div>