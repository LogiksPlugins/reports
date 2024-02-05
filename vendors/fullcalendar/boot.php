<?php
if(!defined('ROOT')) exit('No direct script access allowed');

$webPath=dirname(getWebPath(__FILE__))."/";
?>
<link href='<?=$webPath?>packages/core/main.css' rel='stylesheet' />
<link href='<?=$webPath?>packages/daygrid/main.css' rel='stylesheet' />
<link href='<?=$webPath?>packages/timegrid/main.css' rel='stylesheet' />
<link href='<?=$webPath?>packages/list/main.css' rel='stylesheet' />
<script src='<?=$webPath?>packages/core/main.js'></script>
<script src='<?=$webPath?>packages/interaction/main.js'></script>
<script src='<?=$webPath?>packages/daygrid/main.js'></script>
<script src='<?=$webPath?>packages/timegrid/main.js'></script>
<script src='<?=$webPath?>packages/list/main.js'></script>