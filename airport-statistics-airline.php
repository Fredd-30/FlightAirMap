<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
if (!isset($_GET['airport'])) {
        header('Location: '.$globalURL.'/airport');
        die();
}
$Spotter = new Spotter();
$spotter_array = $Spotter->getSpotterDataByAirport($_GET['airport'],"0,1","");
$airport_array = $Spotter->getAllAirportInfo($_GET['airport']);

if (!empty($airport_array))
{
  $title = 'Most Common Airlines to/from '.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')';
	require_once('header.php');
  
  
  
  print '<div class="select-item">';
	print '<form action="'.$globalURL.'/airport" method="post">';
		print '<select name="airport" class="selectpicker" data-live-search="true">';
      print '<option></option>';
      $airport_names = $Spotter->getAllAirportNames();
      ksort($airport_names);
      foreach($airport_names as $airport_name)
      {
        if($_GET['airport'] == $airport_name['airport_icao'])
        {
          print '<option value="'.$airport_name['airport_icao'].'" selected="selected">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
        } else {
          print '<option value="'.$airport_name['airport_icao'].'">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
        }
      }
    print '</select>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	print '</form>';
  print '</div>';
	
	if ($_GET['airport'] != "NA")
		{
	    print '<div class="info column">';
	    	print '<h1>'.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')</h1>';
	    	print '<div><span class="label">Name</span>'.$airport_array[0]['name'].'</div>';
    	print '<div><span class="label">City</span>'.$airport_array[0]['city'].'</div>';
    	print '<div><span class="label">Country</span>'.$airport_array[0]['country'].'</div>';
    	print '<div><span class="label">ICAO</span>'.$airport_array[0]['icao'].'</div>';
    	print '<div><span class="label">IATA</span>'.$airport_array[0]['iata'].'</div>';
    	print '<div><span class="label">Altitude</span>'.$airport_array[0]['altitude'].'</div>';
    	print '<div><span class="label">Coordinates</span><a href="http://maps.google.ca/maps?z=10&t=k&q='.$airport_array[0]['latitude'].','.$airport_array[0]['longitude'].'" target="_blank">Google Map<i class="fa fa-angle-double-right"></i></a></div>';
	    print '</div>';
	  } else {
	    print '<div class="alert alert-warning">This special airport profile shows all flights that do <u>not</u> have a departure and/or arrival airport associated with them.</div>';
	  }

  include('airport-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>Most Common Airlines</h2>';
  	print '<p>The statistic below shows the most common airlines of flights to/from <strong>'.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')</strong>.</p>';

	  $airline_array = $Spotter->countAllAirlinesByAirport($_GET['airport']);
	  
	  print '<div id="chart" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["Aircraft", "# of Times"], ';
            	$airline_data = '';
              foreach($airline_array as $airline_item)
    					{
	    						$airline_data .= '[ "'.$airline_item['airline_name'].'",'.$airline_item['airline_count'].'],';
    					}
    					$airline_data = substr($airline_data, 0, -1);
    					print $airline_data;
            print ']);
    
            var options = {
            	chartArea: {"width": "80%", "height": "60%"},
            	height:500,
            	 is3D: true
            };
    
            var chart = new google.visualization.PieChart(document.getElementById("chart"));
            chart.draw(data, options);
          }
          $(window).resize(function(){
    			  drawChart();
    			});
      </script>';
      ?>

		<?php
	
	  if (!empty($airline_array))
    {
      print '<div class="table-responsive">';
          print '<table class="common-airline table-striped">';
            print '<thead>';
            	print '<th></th>';
            	print '<th></th>';
              print '<th>Airline</th>';
              print '<th>Country</th>';
              print '<th># of times</th>';
              print '<th></th>';
            print '</thead>';
            print '<tbody>';
            $i = 1;
              foreach($airline_array as $airline_item)
              {
                print '<tr>';
                print '<td><strong>'.$i.'</strong></td>';
                print '<td class="logo">';
      			      		print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'"><img src="';
      				      	if ($globalIVAO && @getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.gif'))
      				      	{
      				      		print $globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.gif';
      				      	} elseif (@getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png'))
      				      	{
      				      		print $globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png';
      				      	} else {
      				      		print $globalURL.'/images/airlines/placeholder.png';
      				      	}
      				      	print '" /></a>';
      			      	print '</td>';
                  print '<td>';
                    print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'">'.$airline_item['airline_name'].' ('.$airline_item['airline_icao'].')</a>';
                  print '</td>';
                  print '<td>';
                    print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airline_item['airline_country'])).'">'.$airline_item['airline_country'].'</a>';
                  print '</td>';
                  print '<td>';
                    print $airline_item['airline_count'];
                  print '</td>';
                  print '<td><a href="'.$globalURL.'/search?airline='.$airline_item['airline_icao'].'&airport='.$_GET['airport'].'">Search flights</a></td>';
                print '</tr>';
                $i++;
              }
             print '<tbody>';
          print '</table>';
      print '</div>';
    }
  print '</div>';
  
  
} else {

	$title = "Airport";
	require_once('header.php');
	
	print '<h1>Error</h1>';

   print '<p>Sorry, the airport does not exist in this database. :(</p>';  
}


?>

<?php
require_once('footer.php');
?>