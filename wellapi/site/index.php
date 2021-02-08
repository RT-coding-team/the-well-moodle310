<?php
header('Content-Type: application/json');
// Connecting, selecting database
$dbconn = pg_connect("host=localhost dbname=moodle user=postgres password=mypassword")
    or die('Could not connect: ' . pg_last_error());


// Performing SQL query to get site details from moodle database
$query = "SELECT fullname,shortname,'/wellfiles/relaytrust.png' as rtlogo, '' as partnerlogo  FROM mdl_course where format='site' limit 1";
$result = pg_query($query) or die('Query failed: ' . pg_last_error());

while ($data = pg_fetch_object($result)) {

	#  This looks for existance of partnerlogo.png on the box (sync from Course Cloud someday)
	$filename = '/var/www/moodle/wellfiles/partnerlogo.png';
	if (file_exists($filename)) {
		$data->{'partnerlogo'} = "/wellfiles/partnerlogo.png";
	}
	
	
	# Send output 
	echo json_encode($data); 
}

// Closing connection
pg_close($dbconn);
?>

