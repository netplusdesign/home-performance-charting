<?php
    // get_monthly_usage.php
    
    require_once 'login.php';
	$link = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);

    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }

	date_default_timezone_set('America/New_York');
	if (isset($_GET['date']) && isset($_GET['house']))
	{
		$date = get_post($link, 'date');
		$year = date_format(date_create($date), 'Y');
		$house = get_post($link, 'house');
	}
	else 
	{
		echo "failed"; 
	}

	// 0) total used
	$query = "SELECT SUM(used) FROM energy_monthly WHERE house_id = $house;"; 
	// 1) list by circuit, 2012 query must exclude 1/1 through 3/15
	
	/*
SELECT SUM(used), SUM(water_heater), SUM(ashp), SUM(water_pump), SUM(dryer), SUM(washer), SUM(dishwasher), SUM(stove), SUM(used)-(SUM(water_heater)+SUM(ashp)+SUM(water_pump)+SUM(dryer)+SUM(washer)+SUM(dishwasher)+SUM(stove)) AS 'Other' 
FROM energy_monthly 
WHERE house_id = 0 
	AND YEAR(date) = 2012
	AND device_id = 5;
	 * */
	$query .= "SELECT SUM(used), SUM(water_heater), SUM(ashp), SUM(water_pump), SUM(dryer), SUM(washer), SUM(dishwasher), SUM(stove), SUM(used)-(SUM(water_heater)+SUM(ashp)+SUM(water_pump)+SUM(dryer)+SUM(washer)+SUM(dishwasher)+SUM(stove)) FROM energy_monthly WHERE house_id = $house AND YEAR(date) = $year AND device_id = 5;";

	// break down values monthly
	$circuit = null;
	if (isset($_GET['circuit']))
	{
		$circuit = get_post($link, 'circuit');
		if ($circuit == "NULL") $circuit = "total";
	}
	else 
	{
		$circuit = "total";
	}

	if ($circuit == 'all_other')
	{
		// 2) dummy query	
		$query .= "SELECT 1+1;";
		// 3) circuit total
		/*
		SELECT SUM(used)-(SUM(water_heater)+SUM(ashp)+SUM(water_pump)+SUM(dryer)+SUM(washer)+SUM(dishwasher)+SUM(stove)) 
		FROM energy_monthly 
		WHERE house_id = 0 
			AND YEAR(date) = 2012 
			AND device_id = 5;
		 * */
		$query .= "SELECT SUM(used)-(SUM(water_heater)+SUM(ashp)+SUM(water_pump)+SUM(dryer)+SUM(washer)+SUM(dishwasher)+SUM(stove)) FROM energy_monthly WHERE house_id = $house AND YEAR(date) = $year AND device_id = 5;";
		/*
		SELECT date, SUM(used)-(SUM(water_heater)+SUM(ashp)+SUM(water_pump)+SUM(dryer)+SUM(washer)+SUM(dishwasher)+SUM(stove)) 
		FROM energy_monthly 
		WHERE house_id = 0
			AND YEAR(date) = 2012 
			AND device_id = 5 
		GROUP BY MONTH(date);
		 * */
		// 4) circuit by month
		$query .= "SELECT date, SUM(used)-(SUM(water_heater)+SUM(ashp)+SUM(water_pump)+SUM(dryer)+SUM(washer)+SUM(dishwasher)+SUM(stove)) FROM energy_monthly WHERE house_id = $house AND YEAR(date) = $year AND device_id = 5 GROUP BY MONTH(date);";
	}
	else if ($circuit == 'total') 
	{
		// 2) dummy query
		$query .= "SELECT 1+1;";
		// 3) and 4)
		/*
		SELECT SUM(en.used), SUM(es.used) 
		FROM energy_monthly en 
			LEFT JOIN estimated_monthly es ON en.date = es.date AND en.house_id = es.house_id  
		WHERE en.house_id = 0
			AND YEAR(en.date) = 2012;
		 * */
		$query .= "SELECT SUM(en.used), SUM(es.used) FROM energy_monthly en LEFT JOIN estimated_monthly es ON en.date = es.date AND en.house_id = es.house_id WHERE en.house_id = $house AND YEAR(en.date) = $year;";
		/*
		SELECT en.date, SUM(en.used), SUM(es.used) 
		FROM energy_monthly en 
			LEFT JOIN estimated_monthly es ON en.date = es.date AND en.house_id = es.house_id 
		WHERE en.house_id = 0
			AND YEAR(en.date) = 2012 
		GROUP BY MONTH(en.date) 
		ORDER BY en.date;
		 * */
		$query .= "SELECT en.date, SUM(en.used), SUM(es.used) FROM energy_monthly en LEFT JOIN estimated_monthly es ON en.date = es.date AND en.house_id = es.house_id WHERE en.house_id = $house AND YEAR(en.date) = $year GROUP BY MONTH(en.date) ORDER BY en.date";
	}
	else
	{
		if ($circuit == 'ashp')
		{
			// 2) get data to calculate projected values --- the reason for all the dummy queries above
			/* 
			SELECT SUM(hdd), date
			FROM (SELECT IF( ((50 - temperature) * 1 / 24) > 0, ((50 - temperature) * 1 / 24), 0) AS 'hdd', date 
					FROM temperature_hourly 
					WHERE house_id = 0 
						AND device_id = 0 
						AND YEAR(date) = 2012) t
			GROUP BY MONTH(date);
			 * */
			$base = 50.0;
			$query .= "SELECT SUM(hdd) FROM (SELECT IF( (($base - temperature) * 1 / 24) > 0, (($base - temperature) * 1 / 24), 0) AS 'hdd', date FROM temperature_hourly WHERE house_id = $house AND device_id = 0 AND YEAR(date) = $year) t GROUP BY MONTH(date);";
		}
		else 
		{
			$query .= "SELECT 1+1;";
		}
		// 3) circuit total
		$query .= "SELECT SUM($circuit) FROM energy_monthly WHERE house_id = $house AND YEAR(date) = $year AND device_id = 5;";
		// 4) circuit by month
		$query .= "SELECT date, $circuit FROM energy_monthly WHERE house_id = $house AND YEAR(date) = $year AND device_id = 5 GROUP BY MONTH(date);";
	}
	
	$output = array(
		"columns" => array( "Actual", "Estimated" ),
		"circuits" => array(),
		"hdds" => array(),
		"totals" => array(),
		"months" => array()
	);
	//$queries = array( "total_used", "circuits", "hdds", "totals", "months" );

	if (mysqli_multi_query($link, $query)) 
	{
		$j = 0;
		$output[ "circuit" ] = $circuit;
		do 
		{
			if ($result = mysqli_store_result($link)) 
			{
				switch($j)
				{
					case(0):
						$row = mysqli_fetch_row($result);
						$output[ "total_used" ] = $row[0];
						break;
					case(1):
						$output[ "circuits" ] = mysqli_fetch_row($result);
						break;
					case(2):
						$aRow = array();
						while ($row = mysqli_fetch_row($result)) 
						{
							$aRow[] = $row[0];
						}
						$output[ "hdds" ] = $aRow;
						$output[ "hdd" ] = array_sum( $aRow );
						break;
					case(3):
						$output[ "totals" ] = mysqli_fetch_row($result);
						break;
					case(4):
						$aRow = array();
						while ($row = mysqli_fetch_row($result)) 
						{
							$aRow[] = $row;
						}	
						$output[ "months" ] = $aRow;
				}
			}
			/* print divider */
			if (mysqli_more_results($link)) 
			{
				$j++;
			}
		} while (mysqli_next_result($link));
	} 	
	mysqli_close($link);

	echo json_encode( $output );
	
	function get_post($link, $var)
	{
		$temp = mysqli_real_escape_string($link, $_GET[$var]);
		if ($temp == '') $temp = 'NULL';
		return $temp;
	}
?>