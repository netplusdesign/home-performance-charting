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
	
	// hourly
	$query['hours'] = "SELECT (" . $base . " - t.outdoor_deg) * (1 / 24) AS 'hdd', e.ashp, e.date "; 
	$query['hours'] .= "FROM energy_hourly e, temperature_hourly t ";
	$query['hours'] .= "WHERE e.date = t.date ";
	$query['hours'] .= "AND t.outdoor_deg < " . $base . " ";
	$query['hours'] .= "AND e.ashp > 0";
	
	// daily
	$query['days'] = "SELECT SUM(t.hdd), SUM(e.ashp), e.date "; 
	$query['days'] .= "FROM (SELECT date, IF(((" . $base . " - outdoor_deg) * 1 / 24) > 0, (" . $base . " - outdoor_deg) * 1 / 24, 0) AS 'hdd' FROM temperature_hourly) t, energy_hourly e ";
	$query['days'] .= "WHERE t.date = e.date ";
	$query['days'] .= "AND CAST(e.date AS DATE) = ANY (SELECT e.date FROM energy_daily e, temperature_daily t WHERE e.date = t.date AND t.outdoor_deg_min <= " . $base . " AND e.ashp > 0) ";
	$query['days'] .= "GROUP BY CAST(t.date AS DATE)";
	
	// monthly
	$query['months'] = "SELECT SUM(t.hdd), SUM(e.ashp/1000), e.date "; 
	$query['months'] .= "FROM (SELECT date, IF(((" . $base . " - outdoor_deg) * 1 / 24) > 0, (" . $base . " - outdoor_deg) * 1 / 24, 0) AS 'hdd' FROM temperature_hourly) t, energy_hourly e ";
	$query['months'] .= "WHERE t.date = e.date ";
	$query['months'] .= "AND (MONTH(e.date) != 2 ";
	$query['months'] .= "AND (MONTH(e.date) < 6 ";
	$query['months'] .= "OR MONTH(e.date) > 8)) ";
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