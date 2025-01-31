<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
$Spotter = new Spotter();
$title = "Statistic - Most common Route by Waypoint";
require_once('header.php');
include('statistics-sub-menu.php'); 
?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<div class="info">
	  	<h1>Most common Route by Waypoint</h1>
	  </div>

      <p>Below are the <strong>Top 10</strong> most common routes, based on the waypoint data (as of May 1, 2014). Theoretically, since the waypoint data is the full 'planned flight route' this statistic would show the actual most common route.</p>
      
<?php

$route_array = $Spotter->countAllRoutesWithWaypoints();
if (!empty($route_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-routes-waypoints table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>Departure Airport</th>';
	print '<th>Arrival Airport</th>';
	print '<th># of times</th>';
	print '<th></th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($route_array as $route_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		print '<a href="'.$globalURL.'/airport/'.$route_item['airport_departure_icao'].'">'.$route_item['airport_departure_city'].', '.$route_item['airport_departure_country'].' ('.$route_item['airport_departure_icao'].')</a>';
		print '</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/airport/'.$route_item['airport_arrival_icao'].'">'.$route_item['airport_arrival_city'].', '.$route_item['airport_arrival_country'].' ('.$route_item['airport_arrival_icao'].')</a>';
		print '</td>';
		print '<td>'.$route_item['route_count'].'</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/flightid/'.$route_item['spotter_id'].'">Recent Flight on this route</a>';
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