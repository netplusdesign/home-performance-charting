<?php
    // monthly_generation
    
    require_once 'login.php';
	$link = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);

    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }

	date_default_timezone_set('America/New_York');
	if (isset($_GET['date']))
	{
		$date = get_post($link, 'date');
		$year = date_format(date_create($date), 'Y');
	}
	else 
	{
		$year = '2012'; // default
	}

	// 0) total generated  
	$query .= "SELECT SUM(solar) FROM energy_monthly;"; 
	// 1 and 2) max solar hour and day
	$query .= "SELECT solar, date FROM energy_hourly WHERE solar = (SELECT MIN(solar) FROM energy_hourly);";
	$query .= "SELECT solar, date FROM energy_daily WHERE solar = (SELECT MIN(solar) FROM energy_daily);";
	// 3 and 4) list by month
	/*
	SELECT SUM(en.solar), SUM(es.solar) 
	FROM energy_monthly en 
		LEFT JOIN estimated_monthly es ON en.date = es.date 
	WHERE YEAR(en.date) = 2012
	ORDER BY en.date;
	 * */
	$query .= "SELECT SUM(en.solar), SUM(es.solar) FROM energy_monthly en LEFT JOIN estimated_monthly es ON en.date = es.date WHERE YEAR(en.date) = $year ORDER BY en.date;";
	/*
	SELECT en.date, SUM(en.solar), SUM(es.solar) 
	FROM energy_monthly en 
		LEFT JOIN estimated_monthly es ON en.date = es.date 
	WHERE YEAR(en.date) = 2012
	GROUP BY MONTH(en.date)
	ORDER BY en.date;
	 * */
	$query .= "SELECT en.date, SUM(en.solar), SUM(es.solar) FROM energy_monthly en LEFT JOIN estimated_monthly es ON en.date = es.date WHERE YEAR(en.date) = $year GROUP BY MONTH(en.date) ORDER BY en.date";

	$output = array(
		"max_solar_hour" => array(),
		"max_solar_day" => array(),
		"columns" => array( "Actual", "Estimated" ),
		"totals" => array(),
		"months" => array()
	);
	//$queries = array( "total_generated", "max_solar_hour", "max_solar_day", "totals", "months" );

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
						$row = mysqli_fetch_row($result);
						$output[ "total_generated" ] = $row[0];
						break;
					case(1):
						$output[ "max_solar_hour" ] = mysqli_fetch_row($result);
						break;
					case(2):
						$output[ "max_solar_day" ] = mysqli_fetch_row($result);
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