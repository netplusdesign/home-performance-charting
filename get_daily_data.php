<?php
	// get_daily_data.php
	require_once 'login.php';
	$link = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);

    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
	
	if ( (isset($_GET['date'])) && isset($_GET['house']) )
	{
		$date = get_post($link, 'date');
		date_default_timezone_set('America/New_York');
		$house = get_post($link, 'house');
		
		/*
	SELECT tu.date, e.adjusted_load, e.solar, e.used, tu.outdoor_deg_min, tu.outdoor_deg_max, th.hdd, e.water_heater, e.ashp, e.water_pump, e.dryer, e.washer, e.dishwasher, e.stove
	FROM (SELECT house_id, date, temperature_min AS 'outdoor_deg_min', temperature_max AS 'outdoor_deg_max' FROM temperature_daily WHERE device_id = 0) tu 
		LEFT JOIN (SELECT house_id, date, hdd FROM hdd_daily) th ON th.date = tu.date AND th.house_id = tu.house_id
		LEFT JOIN energy_daily e ON e.date = tu.date AND e.house_id = tu.house_id
	WHERE tu.house_id = 0
		AND YEAR(tu.date) = 2012
		AND MONTH(tu.date) = 3
	ORDER BY e.date;
		 * */
		
	    $query = "SELECT tu.date, e.adjusted_load, e.solar, e.used, ";
	    $query .= "tu.outdoor_deg_min, tu.outdoor_deg_max, th.hdd, ";
	    $query .= "e.water_heater, e.ashp, e.water_pump, e.dryer, e.washer, e.dishwasher, e.stove, ";
		$query .= "e.used-(e.water_heater+e.ashp+e.water_pump+e.dryer+e.washer+e.dishwasher+e.stove) AS 'All other circuits' ";
		$query .= "FROM (SELECT house_id, date, temperature_min AS 'outdoor_deg_min', temperature_max AS 'outdoor_deg_max' FROM temperature_daily WHERE device_id = 0) tu ";
		$query .= "LEFT JOIN (SELECT house_id, date, hdd FROM hdd_daily) th ON th.date = tu.date AND th.house_id = tu.house_id ";
		$query .= "LEFT JOIN energy_daily e ON e.date = tu.date AND e.house_id = tu.house_id ";
		$query .= "WHERE tu.house_id = $house ";
		$query .= "AND YEAR(tu.date) = " . date_format(date_create($date), 'Y') . " ";
		$query .= "AND MONTH(tu.date) = " . date_format(date_create($date), 'm') . " ";
		$query .= "ORDER BY e.date;";
		
		if ($result = mysqli_query($link, $query))
		{ 
			while ($row = mysqli_fetch_row($result)) 
			{
				echo date_format(date_create($row[0]), 'Y-m-d') . "," . $row[1] . "," . $row[2] . "," . $row[3] . "," . $row[4] . "," . $row[5] . ",";
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