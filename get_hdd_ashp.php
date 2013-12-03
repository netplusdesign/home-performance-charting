<?php
    // get hdd and kwh
    require_once 'login.php';
	$link = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);

    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
	
	if (isset($_GET['base']) && isset($_GET['period']) && isset($_GET['house']) && isset($_GET['date']))
	{
		$base = get_post($link, 'base');
		$period = get_post($link, 'period');
		$house = get_post($link, 'house');
		$year = date_format(date_create( get_post($link, 'date') ), 'Y');
	} 
	else 
	{
		echo "failed"; 
	}
	
	/*
	SELECT (54.0 - t.temperature) * 1 / 24 AS 'hdd', e.ashp/1000.0, t.temperature, e.date
	FROM energy_hourly e, (SELECT house_id, date, temperature FROM temperature_hourly WHERE device_id = 0) t
	WHERE e.house_id = 0
	AND e.house_id = t.house_id
	AND e.date = t.date
	AND YEAR(e.date) = 2012
	AND t.temperature < 54
	AND e.ashp > 0
	LIMIT 1000;
	 * */
	// hourly
	$query['hours'] = "SELECT ($base - t.temperature) * (1 / 24) AS 'hdd', e.ashp/1000.0, t.temperature, e.date "; 
	$query['hours'] .= "FROM energy_hourly e, (SELECT house_id, date, temperature FROM temperature_hourly WHERE device_id = 0) t ";
	$query['hours'] .= "WHERE e.house_id = $house ";
	$query['hours'] .= "AND e.house_id = t.house_id ";
	$query['hours'] .= "AND e.date = t.date ";
	$query['hours'] .= "AND YEAR(e.date) = $year ";
	$query['hours'] .= "AND t.temperature < $base ";
	$query['hours'] .= "AND e.ashp > 0 ";
	$query['hours'] .= "LIMIT 1000";
	/*
	SELECT SUM(t.hdd), SUM(e.ashp)/1000.0, t.temperature, e.date 
	FROM (SELECT house_id, date, temperature, IF(((68 - temperature) * 1 / 24) > 0, (68 - temperature) * 1 / 24, 0) AS 'hdd' FROM temperature_hourly WHERE device_id = 0) t, energy_hourly e
	WHERE e.house_id = 0
		AND t.house_id = e.house_id
		AND t.date = e.date
		AND YEAR(t.date) = 2012
		AND CAST(e.date AS DATE) = ANY (SELECT e.date FROM energy_daily e, temperature_daily t WHERE t.device_id = 0 AND e.date = t.date AND t.temperature_min <= 68 AND e.ashp > 0)
	GROUP BY CAST(t.date AS DATE);
	 * */
	// daily
	$query['days'] = "SELECT SUM(t.hdd), SUM(e.ashp)/1000.0, t.temperature, e.date "; 
	$query['days'] .= "FROM (SELECT house_id, date, temperature, IF((($base - temperature) * 1 / 24) > 0, ($base - temperature) * 1 / 24, 0) AS 'hdd' FROM temperature_hourly WHERE device_id = 0) t, energy_hourly e ";
	$query['days'] .= "WHERE e.house_id = $house ";
	$query['days'] .= "AND t.house_id = e.house_id ";
	$query['days'] .= "AND t.date = e.date ";
	$query['days'] .= "AND YEAR(t.date) = $year ";
	$query['days'] .= "AND CAST(e.date AS DATE) = ANY (SELECT e.date FROM energy_daily e, temperature_daily t WHERE t.device_id = 0 AND e.date = t.date AND t.temperature_min <= $base AND e.ashp > 0) ";
	$query['days'] .= "GROUP BY CAST(t.date AS DATE)";
	
	/*
	SELECT SUM(t.hdd), SUM(e.ashp)/1000.0, t.temperature, e.date 
	FROM (SELECT house_id, date, temperature, IF(((65 - temperature) * 1 / 24) > 0, (65 - temperature) * 1 / 24, 0) AS 'hdd' FROM temperature_hourly WHERE device_id = 0) t, energy_hourly e
	WHERE e.house_id = 0
		AND t.house_id = e.house_id
		AND t.date = e.date
		AND YEAR(e.date) = 2012
	 	AND e.ashp IS NOT NULL
		AND ( MONTH(e.date) < 6 
		OR MONTH(e.date) > 8 )
	GROUP BY YEAR(t.date), MONTH(t.date);
	*/
	// monthly
	$query['months'] = "SELECT SUM(t.hdd), SUM(e.ashp)/1000.0, t.temperature, e.date "; 
	$query['months'] .= "FROM (SELECT house_id, date, temperature, IF((($base - temperature) * 1 / 24) > 0, ($base - temperature) * 1 / 24, 0) AS 'hdd' FROM temperature_hourly WHERE device_id = 0) t, energy_hourly e ";
	$query['months'] .= "WHERE e.house_id = $house ";
	$query['months'] .= "AND t.house_id = e.house_id ";
	$query['months'] .= "AND t.date = e.date ";
	$query['months'] .= "AND YEAR(e.date) = $year ";
	$query['months'] .= "AND e.ashp IS NOT NULL ";
	$query['months'] .= "AND (MONTH(e.date) < 6 ";
	$query['months'] .= "OR MONTH(e.date) > 8) ";
	$query['months'] .= "GROUP BY YEAR(t.date), MONTH(t.date)";	
	
	if ($result = mysqli_query($link, $query[$period]))
	{ 
		while ($row = mysqli_fetch_row($result)) 
		{
			echo $row[0] . "," . $row[1] . "," . $row[2] . "," . $row[3] . "\r\n";
		}
		mysqli_free_result($result);
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