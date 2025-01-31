<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
$Spotter = new Spotter();
$title = "Special Highlights - Table View";
require_once('header.php');

//calculuation for the pagination
if(!isset($_GET['limit']) || $_GET['limit'] == "")
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

$page_url = $globalURL.'/highlights/table';

print '<div class="info column">';
print '<div class="view-type">';
print '<a href="'.$globalURL.'/highlights" alt="Display View" title="Display View"><i class="fa fa-th"></i></a>';
print '<a href="'.$globalURL.'/highlights/table" class="active" alt="Table View" title="Table View"><i class="fa fa-table"></i></a>';
print '</div>';
print '<h1>Special Highlights - Table View</h1>';
print '</div>';

print '<div class="table column">';	
print '<p>The table below shows the detailed information of all custom selected flights who have special aspects to it, such as unique liveries, destinations etc.</p>';

if (isset($_GET['sort'])) {
	$spotter_array = $Spotter->getSpotterDataByHighlight($limit_start.",".$absolute_difference, $_GET['sort']);
} else {
	$spotter_array = $Spotter->getSpotterDataByHighlight($limit_start.",".$absolute_difference);
}

if (!empty($spotter_array))
{
	$showSpecial = true;
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

}

require_once('footer.php');
?>