<?php
    // get hdd and kwh
    require_once 'login.php';
	$link = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);

    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
	
	if (isset($_GET['base']) && isset($_GET['period']))
	{
		$base = get_post($link, 'base');
		$period = get_post($link, 'period');
	} 
	else 
	{
		// default
		$base = 65.0;
		$period = "hours";
	}
	
	/*
	SELECT (54.0 - t.temperature) * 1 / 24 AS 'hdd', e.ashp/1000.0, e.date
	FROM energy_hourly e, (SELECT date, temperature FROM temperature_hourly WHERE device_id = 0) t
	WHERE e.date = t.date
	AND t.temperature < 54
	AND e.ashp > 0;
	 * */
	// hourly
	$query['hours'] = "SELECT ($base - t.temperature) * (1 / 24) AS 'hdd', e.ashp/1000.0, e.date "; 
	$query['hours'] .= "FROM energy_hourly e, (SELECT date, temperature FROM temperature_hourly WHERE device_id = 0) t ";
	$query['hours'] .= "WHERE e.date = t.date ";
	$query['hours'] .= "AND t.temperature < $base ";
	$query['hours'] .= "AND e.ashp > 0";
	
	/*
	SELECT e.date, SUM(e.ashp)/1000.0, SUM(t.hdd) 
	FROM (SELECT date, IF(((68 - temperature) * 1 / 24) > 0, (68 - temperature) * 1 / 24, 0) AS 'hdd' FROM temperature_hourly WHERE device_id = 0) t, energy_hourly e
	WHERE t.date = e.date
		AND CAST(e.date AS DATE) = ANY (SELECT e.date FROM energy_daily e, temperature_daily t WHERE t.device_id = 0 AND e.date = t.date AND t.temperature_min <= 68 AND e.ashp > 0)
	GROUP BY CAST(t.date AS DATE);
	 * */
	// daily
	$query['days'] = "SELECT SUM(t.hdd), SUM(e.ashp)/1000.0, e.date "; 
	$query['days'] .= "FROM (SELECT date, IF((($base - temperature) * 1 / 24) > 0, ($base - temperature) * 1 / 24, 0) AS 'hdd' FROM temperature_hourly WHERE device_id = 0) t, energy_hourly e ";
	$query['days'] .= "WHERE t.date = e.date ";
	$query['days'] .= "AND CAST(e.date AS DATE) = ANY (SELECT e.date FROM energy_daily e, temperature_daily t WHERE t.device_id = 0 AND e.date = t.date AND t.temperature_min <= $base AND e.ashp > 0) ";
	$query['days'] .= "GROUP BY CAST(t.date AS DATE)";
	
	/*
	SELECT e.date, SUM(e.ashp)/1000.0, SUM(t.hdd) 
	FROM (SELECT date, IF(((68 - temperature) * 1 / 24) > 0, (68 - temperature) * 1 / 24, 0) AS 'hdd' FROM temperature_hourly WHERE device_id = 0) t, energy_hourly e
	WHERE t.date = e.date
	AND e.date > DATE('2012-03-15') AND ( MONTH(e.date) < 6 OR MONTH(e.date) > 8 )
	GROUP BY MONTH(t.date);
	*/
	// monthly
	$query['months'] = "SELECT SUM(t.hdd), SUM(e.ashp)/1000.0, e.date "; 
	$query['months'] .= "FROM (SELECT date, IF((($base - temperature) * 1 / 24) > 0, ($base - temperature) * 1 / 24, 0) AS 'hdd' FROM temperature_hourly WHERE device_id = 0) t, energy_hourly e ";
	$query['months'] .= "WHERE t.date = e.date ";
	$query['months'] .= "AND e.date > DATE('2012-03-15') ";
	$query['months'] .= "AND (MONTH(e.date) < 6 ";
	$query['months'] .= "OR MONTH(e.date) > 8) ";
	$query['months'] .= "GROUP BY MONTH(t.date)";	
	
	if ($result = mysqli_query($link, $query[$period]))
	{ 
		while ($row = mysqli_fetch_row($result)) 
		{
			echo $row[0] . "," . $row[1] . "," . $row[2] . "\r\n";
		}
		mysqli_free_result($result);
	}
	
	mysqli_close($link);
	
	function get_post($link, $var)
	{
		$temp = mysqli_real_escape_string($link, $_GET[$var]);
		if ($temp == '') $temp = 'NULL';
		return $temp;
	}	
?>