<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
$Stats = new Stats();
$title = "Statistic - Most common Aircraft";
require_once('header.php');
?>

<?php include('statistics-sub-menu.php'); ?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
 <div class="info">
	  	<h1>Most common Aircraft</h1>
	  </div>

		<p>Below are the <strong>Top 10</strong> most common aircraft types.</p>
	  
	  <?php
	  $aircraft_array = $Stats->countAllAircraftTypes();
	  
		print '<div id="chart" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["Aircraft", "# of Times"], ';
            	$aircraft_data = '';
              foreach($aircraft_array as $aircraft_item)
    					{
	    						$aircraft_data .= '[ "'.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')",'.$aircraft_item['aircraft_icao_count'].'],';
    					}
    					$aircraft_data = substr($aircraft_data, 0, -1);
    					print $aircraft_data;
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
	  if (!empty($aircraft_array))
	  {
	    print '<div class="table-responsive">';
		    print '<table class="common-type table-striped">';
		      print '<thead>';
		      	print '<th></th>';
		        print '<th>Aircraft Type</th>';
		        print '<th># of Times</th>';
		      print '</thead>';
		      print '<tbody>';
		      	$i = 1;
		        foreach($aircraft_array as $aircraft_item)
		        {
		          print '<tr>';
		          	print '<td><strong>'.$i.'</strong></td>';
		          	print '<td>';
		              print '<a href="'.$globalURL.'/aircraft/'.$aircraft_item['aircraft_icao'].'">'.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')</a>';
		            print '</td>';
		            print '<td>';
		              print $aircraft_item['aircraft_icao_count'];
		            print '</td>';
		          print '</tr>';
		          $i++;
		        }
		      print '<tbody>';
		    print '</table>';
	    print '</div>';
	  }
	  ?>

<?php
require_once('footer.php');
?>