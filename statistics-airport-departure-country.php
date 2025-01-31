<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
$Stats = new Stats();
$title = "Statistic - Most common Departure Airport by Country";
require_once('header.php');
include('statistics-sub-menu.php'); 
?>

<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<div class="info">
	  	<h1>Most common Departure Airport by Country</h1>
	  </div>
    
    	<p>Below are the <strong>Top 10</strong> most common countries of all the departure airports.</p>
    
<?php
	$airport_country_array = $Stats->countAllDepartureCountries();
?>

    	<script>
    	google.load("visualization", "1", {packages:["geochart"]});
    	google.setOnLoadCallback(drawCharts);
    	$(window).resize(function(){
    		drawCharts();
    	});
    	function drawCharts() {
        
        var data = google.visualization.arrayToDataTable([ 
        	["Country", "# of Times"],
<?php

$country_data = '';
foreach($airport_country_array as $airport_item)
{
	$country_data .= '[ "'.$airport_item['airport_departure_country'].'",'.$airport_item['airport_departure_country_count'].'],';
}
$country_data = substr($country_data, 0, -1);
print $country_data;

?>
        ]);
    
        var options = {
        	legend: {position: "none"},
        	chartArea: {"width": "80%", "height": "60%"},
        	height:500,
        	colors: ["#8BA9D0","#1a3151"]
        };
    
        var chartCountry = new google.visualization.GeoChart(document.getElementById("chartCountry"));
        chartCountry.draw(data, options);
      }
    	</script>
      
    	<div id="chartCountry" class="chart" width="100%"></div>
    	
<?php

print '<div class="table-responsive">';
print '<table class="common-country table-striped">';
print '<thead>';
print '<th></th>';
print '<th>Country</th>';
print '<th># of times</th>';
print '</thead>';
print '<tbody>';
$i = 1;
foreach($airport_country_array as $airport_item)
{
	print '<tr>';
	print '<td><strong>'.$i.'</strong></td>';
	print '<td>';
	print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airport_item['airport_departure_country'])).'">'.$airport_item['airport_departure_country'].'</a>';
	print '</td>';
	print '<td>';
	print $airport_item['airport_departure_country_count'];
	print '</td>';
	print '</tr>';
	$i++;
}
print '<tbody>';
print '</table>';
print '</div>';

require_once('footer.php');
?>