<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
$Stats = new Stats();
$title = "Statistic - Most Busiest Day";
require_once('header.php');
include('statistics-sub-menu.php'); 
?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<div class="info">
	  	<h1>Most Busiest Day</h1>
	  </div>
      
      <p>Below is a chart that plots the busiest day during the <strong>last 7 days</strong>.</p>
      
<?php

$date_array = $Stats->countAllDatesLast7Days();
if (count($date_array) == 0) {
    print 'Not data available';
} else {
print '<div id="chart" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["Date", "# of Flights"], ';

$date_data = '';
foreach($date_array as $date_item)
{
	$date_data .= '[ "'.date("F j, Y", strtotime($date_item['date_name'])).'",'.$date_item['date_count'].'],';
}
$date_data = substr($date_data, 0, -1);
print $date_data;
print ']);
    
            var options = {
            	legend: {position: "none"},
            	chartArea: {"width": "80%", "height": "60%"},
            	vAxis: {title: "# of Flights"},
            	hAxis: {showTextEvery: 2},
            	height:300,
            	colors: ["#1a3151"]
            };
    
            var chart = new google.visualization.AreaChart(document.getElementById("chart"));
            chart.draw(data, options);
          }
          $(window).resize(function(){
    			  drawChart();
    			});
      </script>';
}
if (isset($globalDBArchiveMonths) && $globalDBArchiveMonths > 0) {
?>
      
      <p>Below are the <strong>Top 10</strong> most busiest dates of last <?php print $globalDBArchiveMonths; ?> month(s).</p>

<?php
} else {
?>
      <p>Below are the <strong>Top 10</strong> most busiest dates.</p>

<?php
}
$date_array = $Stats->countAllDates();
if (!empty($date_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-date table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>Date</th>';
	print '<th># of Flights</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($date_array as $date_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		print '<a href="'.$globalURL.'/date/'.date("Y-m-d", strtotime($date_item['date_name'])).'">'.date("l F j, Y", strtotime($date_item['date_name'])).'</a>';
		print '</td>';
		print '<td>';
		print $date_item['date_count'];
		print '</td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
}

require_once('footer.php');
?>