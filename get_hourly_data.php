<?php
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
		
		/*
	SELECT ti.date, e.adjusted_load, ti.indoor_deg, tu.outdoor_deg, th.hdd, e.water_heater, e.ashp, e.water_pump, e.dryer, e.washer, e.dishwasher, e.stove,
		e.used-(e.water_heater+e.ashp+e.water_pump+e.dryer+e.washer+e.dishwasher+e.stove) AS 'All other circuits'
	FROM (SELECT date, temperature AS 'indoor_deg' FROM temperature_hourly WHERE device_id = 1) ti  
		LEFT JOIN (SELECT date, temperature AS 'outdoor_deg' FROM temperature_hourly WHERE device_id = 0) tu ON (tu.date = ti.date)
		LEFT JOIN (SELECT date, hdd FROM hdd_hourly) th ON (th.date = ti.date)
		LEFT JOIN energy_hourly e ON (e.date = ti.date) 
	WHERE CAST(ti.date AS DATE) = DATE('2012-03-12');
		 * */
		
	    $query = "SELECT ti.date, e.adjusted_load, e.solar, e.used, "; 
	    $query .= "ti.indoor_deg, tu.outdoor_deg, th.hdd, ";
	    $query .= "e.water_heater, e.ashp, e.water_pump, e.dryer, e.washer, e.dishwasher, e.stove, ";
		$query .= "e.used-(e.water_heater+e.ashp+e.water_pump+e.dryer+e.washer+e.dishwasher+e.stove) AS 'All other' ";
		$query .= "FROM (SELECT date, temperature AS 'indoor_deg' FROM temperature_hourly WHERE device_id = 1) ti ";
		$query .= "LEFT JOIN (SELECT date, temperature AS 'outdoor_deg' FROM temperature_hourly WHERE device_id = 0) tu ON (tu.date = ti.date) ";
		$query .= "LEFT JOIN (SELECT date, hdd FROM hdd_hourly) th ON (th.date = ti.date) ";
		$query .= "LEFT JOIN energy_hourly e ON (e.date = ti.date) ";
		$query .= "WHERE CAST(ti.date AS DATE) = DATE('" . date_format(date_create($date), 'Y-m-d') . "')";
		
		if ($result = mysqli_query($link, $query))
		{
			echo "Time,Adjusted Load,Solar,Usage,Indoor Temp,Outdoor Temp,HDD,Water Heater,ASHP,Water Pump,Dryer,Washer,Dishwasher,Range,All other\r\n"; 
			while ($row = mysqli_fetch_row($result)) 
			{
				echo date_format(date_create($row[0]), 'H') . "," . $row[1] . "," . $row[2] . "," . $row[3] . "," . $row[4] . "," . $row[5] . ",";
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