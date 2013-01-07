<?php
    // monthly_water
    
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

	// 0 totals and 1) list by month
	/* 
	SELECT SUM(main.gallons)-SUM(hot.gallons), SUM(hot.gallons), SUM(main.gallons), SUM(e.water_heater), SUM(e.water_pump) 
	FROM energy_monthly e 
		LEFT JOIN (SELECT house_id, date, gallons FROM water_monthly WHERE device_id = 6) main ON e.date = main.date AND main.house_id = e.house_id 
		LEFT JOIN (SELECT house_id, date, gallons FROM water_monthly WHERE device_id = 7) hot ON e.date = hot.date AND hot.house_id = e.house_id
	WHERE e.house_id = 0
		AND YEAR(e.date) = 2012;
	 * */
	$query = "SELECT SUM(main.gallons)-SUM(hot.gallons), SUM(hot.gallons), SUM(main.gallons), SUM(e.water_heater), SUM(e.water_pump) ";
	$query .= "FROM energy_monthly e ";
	$query .= "LEFT JOIN (SELECT house_id, date, gallons FROM water_monthly WHERE device_id = 6) main ON e.date = main.date AND main.house_id = e.house_id ";
	$query .= "LEFT JOIN (SELECT house_id, date, gallons FROM water_monthly WHERE device_id = 7) hot ON e.date = hot.date AND hot.house_id = e.house_id ";
	$query .= "WHERE e.house_id = $house ";
	$query .= "AND YEAR(e.date) = $year;";
	/*
	SELECT e.date, SUM(main.gallons)-SUM(hot.gallons), SUM(hot.gallons), SUM(main.gallons), SUM(e.water_heater), SUM(e.water_pump) 
	FROM energy_monthly e 
		LEFT JOIN (SELECT house_id, date, gallons FROM water_monthly WHERE device_id = 6) main ON e.date = main.date AND main.house_id = e.house_id 
		LEFT JOIN (SELECT house_id, date, gallons FROM water_monthly WHERE device_id = 7) hot ON e.date = hot.date AND hot.house_id = e.house_id 
	WHERE e.house_id = 0
		AND YEAR(e.date) = 2012
	GROUP BY MONTH(e.date)
	ORDER BY date;
	 * */
	$query .= "SELECT e.date, SUM(main.gallons)-SUM(hot.gallons), SUM(hot.gallons), SUM(main.gallons), SUM(e.water_heater), SUM(e.water_pump) ";
	$query .= "FROM energy_monthly e ";
	$query .= "LEFT JOIN (SELECT house_id, date, gallons FROM water_monthly WHERE device_id = 6) main ON e.date = main.date AND main.house_id = e.house_id ";
	$query .= "LEFT JOIN (SELECT house_id, date, gallons FROM water_monthly WHERE device_id = 7) hot ON e.date = hot.date AND hot.house_id = e.house_id ";
	$query .= "WHERE e.house_id = $house ";
	$query .= "AND YEAR(e.date) = $year ";
	$query .= "GROUP BY MONTH(e.date) ";
	$query .= "ORDER BY e.date;";
	
	$output = array(
		"columns" => array( "Cold", "Hot", "Total", "Hot Water Wh/g", "Water Pump Wh/g" ),
		"totals" => array(),
		"months" => array()
	);

	if (mysqli_multi_query($link, $query)) 
	{
		$j = 0;
		do 
		{
			if ($result = mysqli_store_result($link)) 
			{
				switch($j)
				{
					case(0):
						$output[ "totals" ] = mysqli_fetch_row($result);
						break;
					case(1):
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