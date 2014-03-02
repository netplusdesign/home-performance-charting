<?php
	require_once 'login.php';
	$link = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);

    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
	
	if (isset($_GET['date']) && isset($_GET['house']))
	{
		$date = get_post($link, 'date');
		date_default_timezone_set('America/New_York');
		$house = get_post($link, 'house');
		
		/*
SELECT ti1.date, e.adjusted_load, e.solar, e.used, ti1.indoor1_deg, ti2.indoor2_deg, ti0.indoor0_deg, tu.outdoor_deg, th.hdd, e.water_heater, e.ashp, e.water_pump, e.dryer, e.washer, e.dishwasher, e.stove,
	e.used-(e.water_heater+e.ashp+e.water_pump+e.dryer+e.washer+e.dishwasher+e.stove) AS 'All other circuits'
FROM (SELECT house_id, date, temperature AS 'indoor1_deg' FROM temperature_hourly WHERE device_id = 1) ti1
	LEFT JOIN (SELECT house_id, date, temperature AS 'indoor2_deg' FROM temperature_hourly WHERE device_id = 2) ti2 ON ti2.date = ti1.date AND ti2.house_id = ti1.house_id
	LEFT JOIN (SELECT house_id, date, temperature AS 'indoor0_deg' FROM temperature_hourly WHERE device_id = 3) ti0 ON ti0.date = ti1.date AND ti0.house_id = ti1.house_id
	LEFT JOIN (SELECT house_id, date, temperature AS 'outdoor_deg' FROM temperature_hourly WHERE device_id = 0) tu ON tu.date = ti1.date AND tu.house_id = ti1.house_id
	LEFT JOIN (SELECT house_id, date, hdd FROM hdd_hourly) th ON th.date = ti1.date AND th.house_id = ti1.house_id
	LEFT JOIN energy_hourly e ON e.date = ti1.date AND e.house_id = ti1.house_id
WHERE CAST(ti1.date AS DATE) = DATE('2012-12-29')
	AND ti1.house_id = 0;
		 * 
SELECT ti1.date, e.adjusted_load, e.solar, e.used, ti1.indoor1_deg, ti2.indoor2_deg, ti0.indoor0_deg, 
  tu.outdoor_deg, th.hdd, e.water_heater, e.ashp, e.water_pump, e.dryer, e.washer, e.dishwasher, e.stove,
  e.used-(e.water_heater+e.ashp+e.water_pump+e.dryer+e.washer+e.dishwasher+e.stove) AS 'All other circuits'
FROM (SELECT house_id, date, temperature AS 'indoor1_deg' FROM temperature_hourly WHERE device_id = 1) ti1
  LEFT JOIN (SELECT house_id, date, temperature AS 'indoor2_deg' FROM temperature_hourly WHERE device_id = 2) ti2 
	ON CAST(LEFT(ti2.date,13) AS DATETIME) = CAST(LEFT(ti1.date,13) AS DATETIME) AND ti2.house_id = ti1.house_id
  LEFT JOIN (SELECT house_id, date, temperature AS 'indoor0_deg' FROM temperature_hourly WHERE device_id = 3) ti0 
	ON CAST(LEFT(ti0.date,13) AS DATETIME) = CAST(LEFT(ti1.date,13) AS DATETIME) AND ti0.house_id = ti1.house_id
  LEFT JOIN (SELECT house_id, date, temperature AS 'outdoor_deg' FROM temperature_hourly WHERE device_id = 0) tu 
	ON CAST(LEFT(tu.date,13) AS DATETIME) = CAST(LEFT(ti1.date,13) AS DATETIME) AND tu.house_id = ti1.house_id
  LEFT JOIN (SELECT house_id, date, hdd FROM hdd_hourly) th 
	ON CAST(LEFT(th.date,13) AS DATETIME) = CAST(LEFT(ti1.date,13) AS DATETIME) AND th.house_id = ti1.house_id
  LEFT JOIN energy_hourly e 
	ON CAST(LEFT(e.date,13) AS DATETIME) = CAST(LEFT(ti1.date,13) AS DATETIME) AND e.house_id = ti1.house_id
WHERE CAST(ti1.date AS DATE) = DATE('2014-02-07')
  AND ti1.house_id = 0;
		 * */
		
	    $query = "SELECT ti1.date, e.adjusted_load, e.solar, e.used, ";
	    $query .= "ti1.indoor1_deg, ti2.indoor2_deg, ti0.indoor0_deg, tu.outdoor_deg, th.hdd, ";
	    $query .= "e.water_heater, e.ashp, e.water_pump, e.dryer, e.washer, e.dishwasher, e.stove, ";
		$query .= "e.used-(e.water_heater+e.ashp+e.water_pump+e.dryer+e.washer+e.dishwasher+e.stove) AS 'All other' ";
		$query .= "FROM (SELECT house_id, date, temperature AS 'indoor1_deg' FROM temperature_hourly WHERE device_id = 1) ti1 ";
		$query .= "LEFT JOIN (SELECT house_id, date, temperature AS 'indoor2_deg' FROM temperature_hourly WHERE device_id = 2) ti2 ";
		$query .= "ON CAST(LEFT(ti2.date,13) AS DATETIME) = CAST(LEFT(ti1.date,13) AS DATETIME) AND ti2.house_id = ti1.house_id ";
		$query .= "LEFT JOIN (SELECT house_id, date, temperature AS 'indoor0_deg' FROM temperature_hourly WHERE device_id = 3) ti0 ";
		$query .= "ON CAST(LEFT(ti0.date,13) AS DATETIME) = CAST(LEFT(ti1.date,13) AS DATETIME) AND ti0.house_id = ti1.house_id ";
		$query .= "LEFT JOIN (SELECT house_id, date, temperature AS 'outdoor_deg' FROM temperature_hourly WHERE device_id = 0) tu ";
		$query .= "ON CAST(LEFT(tu.date,13) AS DATETIME) = CAST(LEFT(ti1.date,13) AS DATETIME) AND tu.house_id = ti1.house_id ";
		$query .= "LEFT JOIN (SELECT house_id, date, hdd FROM hdd_hourly) th ";
		$query .= "ON CAST(LEFT(th.date,13) AS DATETIME) = CAST(LEFT(ti1.date,13) AS DATETIME) AND th.house_id = ti1.house_id ";
		$query .= "LEFT JOIN energy_hourly e ";
		$query .= "ON CAST(LEFT(e.date,13) AS DATETIME) = CAST(LEFT(ti1.date,13) AS DATETIME) AND e.house_id = ti1.house_id ";
		$query .= "WHERE CAST(ti1.date AS DATE) = DATE('" . date_format(date_create($date), 'Y-m-d') . "') ";
		$query .= "AND ti1.house_id = $house;";
		
		if ($result = mysqli_query($link, $query))
		{
			echo "Time,Adjusted Load,Solar,Usage,First Floor Temp,Second Floor Temp,Basement Temp,Outdoor Temp,HDD,Water Heater,ASHP,Water Pump,Dryer,Washer,Dishwasher,Range,All Other\r\n"; 
			while ($row = mysqli_fetch_row($result)) 
			{
				echo date_format(date_create($row[0]), 'H') . "," . $row[1] . "," . $row[2] . "," . $row[3] . "," . $row[4] . "," . $row[5] . ",";
				echo $row[6] . "," . $row[7] . "," . $row[8] . "," . $row[9] . "," . $row[10] . "," . $row[11] . "," . $row[12] . "," . $row[13] . "," . $row[14] . "," . $row[15] . "," . $row[16] . "\r\n";
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