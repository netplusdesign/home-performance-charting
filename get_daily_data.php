<?php
	// get_daily_data.php
	require_once 'login.php';
	$link = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);

    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
	
	if (isset($_GET['date']))
	{
		$date = get_post($link, 'date');
		date_default_timezone_set('America/New_York');
		
	    $query = "SELECT t.date, e.adjusted_load, e.solar, e.used, ";
	    $query .= "t.outdoor_deg_min, t.outdoor_deg_max, t.hdd, ";
	    $query .= "e.water_heater, e.ashp, e.water_pump, e.dryer, e.washer, e.dishwasher, e.stove, ";
		$query .= "e.used-(e.water_heater+e.ashp+e.water_pump+e.dryer+e.washer+e.dishwasher+e.stove) AS 'All other circuits' ";
		$query .= "FROM temperature_daily t LEFT JOIN energy_daily e ON t.date = e.date ";
		$query .= "WHERE YEAR(t.date) = " . date_format(date_create($date), 'Y') . " ";
		$query .= "AND MONTH(t.date) = " . date_format(date_create($date), 'm');
		
		if ($result = mysqli_query($link, $query))
		{ 
			while ($row = mysqli_fetch_row($result)) 
			{
				echo date_format(date_create($row[0]), 'm/d/Y') . "," . $row[1] . "," . $row[2] . "," . $row[3] . "," . $row[4] . "," . $row[5] . ",";
				echo $row[6] . "," . $row[7] . "," . $row[8] . "," . $row[9] . "," . $row[10] . "," . $row[11] . "," . $row[12] . "," . $row[13] . "," . $row[14] . "\r\n";
			}
			mysqli_free_result($result);
		}

	} 
	else 
	{
		 echo "failed"; 
	}
	
	mysqli_close($link);
	
	function get_post($link, $var)
	{
		$temp = mysqli_real_escape_string($link, $_GET[$var]);
		if ($temp == '') $temp = 'NULL';
		return $temp;
	}
?>