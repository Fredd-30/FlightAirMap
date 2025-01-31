<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
if (!isset($_GET['ident'])) {
        header('Location: '.$globalURL.'/ident');
        die();
}
$Spotter = new Spotter();
$sort = '';
if (isset($_GET['sort'])) $sort = $_GET['sort'];
$spotter_array = $Spotter->getSpotterDataByIdent($_GET['ident'],"0,1", $sort);

if (!empty($spotter_array))
{
    $title = 'Most Common Departure Airports of '.$spotter_array[0]['ident'];
	require_once('header.php');
    
    
    
    print '<div class="info column">';
  		print '<h1>'.$spotter_array[0]['ident'].'</h1>';
  		print '<div><span class="label">Ident</span>'.$spotter_array[0]['ident'].'</div>';
  		print '<div><span class="label">Airline</span><a href="'.$globalURL.'/airline/'.$spotter_array[0]['airline_icao'].'">'.$spotter_array[0]['airline_name'].'</a></div>'; 
  		print '<div><span class="label">Flight History</span><a href="http://flightaware.com/live/flight/'.$spotter_array[0]['ident'].'" target="_blank">View the Flight History of this callsign</a></div>';       
	print '</div>';

	include('ident-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>DMost Common Departure Airports</h2>';
  	
  	?>
  	<p>The statistic below shows all departure airports of flights with the ident/callsign <strong><?php print $spotter_array[0]['ident']; ?></strong>.</p>
  	<?php
    	 $airport_airport_array = $Spotter->countAllDepartureAirportsByIdent($_GET['ident']);
    	?>
    	<script>
    	google.load("visualization", "1", {packages:["geochart"]});
    	google.setOnLoadCallback(drawCharts);
    	$(window).resize(function(){
    		drawCharts();
    	});
    	function drawCharts() {
    
        var data = google.visualization.arrayToDataTable([ 
        	["Airport", "# of Times"],
        	<?php
	$airport_data = '';
          foreach($airport_airport_array as $airport_item)
    			{
    				$name = $airport_item['airport_departure_city'].', '.$airport_item['airport_departure_country'].' ('.$airport_item['airport_departure_icao'].')';
    				$name = str_replace("'", "", $name);
    				$name = str_replace('"', "", $name);
    				$airport_data .= '[ "'.$name.'",'.$airport_item['airport_departure_icao_count'].'],';
    			}
    			$airport_data = substr($airport_data, 0, -1);
    			print $airport_data;
    			?>
        ]);
    
        var options = {
        	legend: {position: "none"},
        	chartArea: {"width": "80%", "height": "60%"},
        	height:500,
        	displayMode: "markers",
        	colors: ["#8BA9D0","#1a3151"]
        };
        
        var chart = new google.visualization.GeoChart(document.getElementById("chartAirport"));
        chart.draw(data, options);
      }
    	</script>
      
    	<div id="chartAirport" class="chart" width="100%"></div>

    	<?php
       print '<div class="table-responsive">';
           print '<table class="common-airport table-striped">';
            print '<thead>';
              print '<th></th>';
              print '<th>Airport</th>';
              print '<th>Country</th>';
              print '<th># of times</th>';
              print '<th></th>';
            print '</thead>';
            print '<tbody>';
            $i = 1;
              foreach($airport_airport_array as $airport_item)
              {
                print '<tr>';
                	print '<td><strong>'.$i.'</strong></td>';
                  print '<td>';
                    print '<a href="'.$globalURL.'/airport/'.$airport_item['airport_departure_icao'].'">'.$airport_item['airport_departure_city'].', '.$airport_item['airport_departure_country'].' ('.$airport_item['airport_departure_icao'].')</a>';
                  print '</td>';
                  print '<td>';
                    print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airport_item['airport_departure_country'])).'">'.$airport_item['airport_departure_country'].'</a>';
                  print '</td>';
                  print '<td>';
                    print $airport_item['airport_departure_icao_count'];
                  print '</td>';
                  print '<td><a href="'.$globalURL.'/search?departure_airport_route='.$airport_item['airport_departure_icao'].'&callsign='.$_GET['ident'].'">Search flights</a></td>';
                print '</tr>';
                $i++;
              }
            print '<tbody>';
          print '</table>';
      print '</div>';
      ?>
  	<?php
  print '</div>';
  
  
} else {

	$title = "Ident";
	require_once('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, this ident/callsign is not in the database. :(</p>';
}


?>

<?php
require_once('footer.php');
?>