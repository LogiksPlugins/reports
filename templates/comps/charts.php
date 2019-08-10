<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!isset($reportConfig['charts'])) return;

echo _js(['charts']);

$chartID = md5(rand().time());

if(!isset($reportConfig['charts']['height'])) $reportConfig['charts']['height'] = "300px";
?>
<div id='reportChart-<?=$chartID?>' class='col-xs-12 col-sm-12 col-md-12 col-lg-12 chartArea nopadding' style='height:<?=$reportConfig['charts']['height']?>'>
	<canvas id="canvas-<?=$chartID?>" class='reportChartCanvas' style="width: 100%;height: 100%;"></canvas>
</div>
<script>
var COLORS = {
	red: 'rgb(255, 99, 132)',
	orange: 'rgb(255, 159, 64)',
	yellow: 'rgb(255, 205, 86)',
	green: 'rgb(75, 192, 192)',
	blue: 'rgb(54, 162, 235)',
	purple: 'rgb(153, 102, 255)',
	grey: 'rgb(201, 203, 207)'
};
var COLORS_KEYS = Object.keys(COLORS);
$(function() {
	processAJAXQuery(_service("reports","fetchChartData")+"&gridid=<?=$reportKey?>", function(jsonData) {
		if(typeof jsonData.Data != "string") {
			// console.log(jsonData.Data);

			config = {
				type: jsonData.Data.type,

				data: {
					labels: [],
					datasets: []
				},

				options: $.extend({
					responsive: true,
					tooltips: {
						mode: 'index',
						intersect: false,
					},
					hover: {
						mode: 'nearest',
						intersect: true
					},
					legend: {
						display: false
					}
				}, jsonData.Data.options)
			};

			config.data.labels = jsonData.Data.labels;

			cnt=0;
			$.each(jsonData.Data.datasets, function(a,b) {
				if(cnt>=COLORS_KEYS.length) cnt=0;
				config.data.datasets.push({
					backgroundColor: COLORS[COLORS_KEYS[cnt]],
					borderColor: COLORS[COLORS_KEYS[cnt]],
					label: b.title,
					fill: b.fill,
					data: b.datapoints
				});
				cnt++;
			});
			// console.log(config,jsonData.Data);
			ctx = document.getElementById("canvas-<?=$chartID?>").getContext('2d');
			new Chart(ctx, config);
		} else {
			$("#reportChart-<?=$chartID?>").detach();
			lgksToast(jsonData.Data);
		}
	},"json");
});
</script>