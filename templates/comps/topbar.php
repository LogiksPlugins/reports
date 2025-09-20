<?php
if(!defined('ROOT')) exit('No direct script access allowed');

$topbar = $reportConfig['topbar'];

$topbar=array_merge([
          "settings"=>[],
          "XtraHtmlToolButton"=>"",
        ],$topbar);

$topbar['settings']["fitTableToWindow"] = [
        "name"=>"FitTableToWindow",
        "label"=>"Fit Table to Window",
        "type"=>"checkbox",
      ];
//$reportConfig['template']

$templateViews=getReportViewsList($reportConfig);
// printArray($templateViews);
//printArray($reportConfig);
if(!isset($topbar['uitype'])) {
  $topbar['uitype'] = getConfig("REPORT_TOPBAR_UITYPE");
  if(!$topbar['uitype']) $topbar['uitype'] = "type2";
}

$topbarFile = __DIR__."/topbar/{$topbar['uitype']}.php";
if(file_exists($topbarFile)) {
  include_once $topbarFile;
} else {

}
