<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
$Spotter = new Spotter();
if (!isset($_GET['aircraft_manufacturer'])){
	header('Location: '.$globalURL.'');
} else {
	
	//calculuation for the pagination
	if(!isset($_GET['limit']))
	{
	  $limit_start = 0;
	  $limit_end = 25;
	  $absolute_difference = 25;
	}  else {
		$limit_explode = explode(",", $_GET['limit']);
		$limit_start = $limit_explode[0];
		$limit_end = $limit_explode[1];
	}
	$absolute_difference = abs($limit_start - $limit_end);
	$limit_next = $limit_end + $absolute_difference;
	$limit_previous_1 = $limit_start - $absolute_difference;
	$limit_previous_2 = $limit_end - $absolute_difference;
	
	$manufacturer = ucwords(str_replace("-", " ", $_GET['aircraft_manufacturer']));
	$page_url = $globalURL.'/manufacturer/'.$_GET['aircraft_manufacturer'];
	
	if (isset($_GET['sort'])) {
		$spotter_array = $Spotter->getSpotterDataByManufacturer($manufacturer,$limit_start.",".$absolute_difference, $_GET['sort']);
	} else {
		$spotter_array = $Spotter->getSpotterDataByManufacturer($manufacturer,$limit_start.",".$absolute_difference, '');
	}
	
	
	if (!empty($spotter_array))
	{
	  $title = 'Detailed View for '.$manufacturer;
		require_once('header.php');
	  
	  
	  
	  print '<div class="select-item">';
		print '<form action="'.$globalURL.'/manufacturer" method="post">';
			print '<select name="aircraft_manufacturer" class="selectpicker" data-live-search="true">';
	      print '<option></option>';
	      $all_manufacturers = $Spotter->getAllManufacturers();
	      foreach($all_manufacturers as $all_manufacturer)
	      {
	        if($_GET['aircraft_manufacturer'] == strtolower(str_replace(" ", "-", $all_manufacturer['aircraft_manufacturer'])))
	        {
	          print '<option value="'.strtolower(str_replace(" ", "-", $all_manufacturer['aircraft_manufacturer'])).'" selected="selected">'.$all_manufacturer['aircraft_manufacturer'].'</option>';
	        } else {
	          print '<option value="'.strtolower(str_replace(" ", "-", $all_manufacturer['aircraft_manufacturer'])).'">'.$all_manufacturer['aircraft_manufacturer'].'</option>';
	        }
	      }
	    print '</select>';
		print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
		print '</form>';
	  print '</div>';
	  
		print '<div class="info column">';
			print '<h1>'.$manufacturer.'</h1>';
		print '</div>';
		
	  print '<div class="table column">';
		  
		 print '<p>The table below shows the detailed information of all flights from <strong>'.$manufacturer.'</strong>.</p>';
		 
		 include('manufacturer-sub-menu.php');
		  
		  include('table-output.php'); 
		  
		  print '<div class="pagination">';
		  	if ($limit_previous_1 >= 0)
		  	{
		  	print '<a href="'.$page_url.'/'.$limit_previous_1.','.$limit_previous_2.'/'.$_GET['sort'].'">&laquo;Previous Page</a>';
		  	}
		  	if ($spotter_array[0]['query_number_rows'] == $absolute_difference)
		  	{
		  		print '<a href="'.$page_url.'/'.$limit_end.','.$limit_next.'/'.$_GET['sort'].'">Next Page&raquo;</a>';
		  	}
		  print '</div>';
	  
	  print '</div>';
	
	  
	} else {
	
		$title = "Manufacturer";
		require_once('header.php');
		
		print '<h1>Error</h1>';
	
	  print '<p>Sorry, the manufacturer does not exist in this database. :(</p>'; 
	}
}
?>

<?php
require_once('footer.php');
?>