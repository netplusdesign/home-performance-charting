<?php
    // get_monthly_hdd.php
    
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

	/*
SELECT t.hdd, e.ashp
FROM (SELECT SUM(hdd) AS 'hdd' FROM hdd_daily WHERE house_id = 0 AND YEAR(date) = 2012 AND date > DATE('2012-03-15')) t,
	 (SELECT SUM(ashp) AS 'ashp' FROM energy_monthly WHERE house_id = 0 AND YEAR(date) = 2012 AND (date < DATE('2012-05-21') OR date > DATE('2012-09-21'))) e;
	 * */
	// 0) returns total HDD and total kWh used in heating season
	if ($year == 2012)
	{
		$query = "SELECT t.hdd, e.ashp FROM (SELECT SUM(hdd) AS 'hdd' FROM hdd_daily WHERE house_id = $house AND YEAR(date) = $year AND date > DATE('2012-03-15')) t, (SELECT SUM(ashp) AS 'ashp' FROM energy_monthly WHERE house_id = $house AND YEAR(date) = $year AND (date < DATE('$year-05-21') OR date > DATE('$year-09-21'))) e;";
	}
	else 
	{
		$query = "SELECT t.hdd, e.ashp FROM (SELECT SUM(hdd) AS 'hdd' FROM hdd_monthly WHERE house_id = $house AND YEAR(date) = $year) t, (SELECT SUM(ashp) AS 'ashp' FROM energy_monthly WHERE house_id = $house AND YEAR(date) = $year AND (date < DATE('$year-05-21') OR date > DATE('$year-09-21'))) e;";	
	}
	// 1 and 2) coldest temp and date, coldest day hdd and date
	/*
	 * 1) SELECT temperature, date FROM temperature_hourly WHERE temperature = (SELECT MIN(temperature) FROM temperature_hourly WHERE YEAR(date) = 2012) AND house_id = 0;
	 * 2) SELECT hdd, date FROM hdd_daily WHERE hdd = (SELECT MAX(hdd) FROM hdd_daily WHERE YEAR(date) = 2012) AND house_id = 0; 
	 * */
	$query .= "SELECT temperature, date FROM temperature_hourly WHERE temperature = (SELECT MIN(temperature) FROM temperature_hourly WHERE YEAR(date) = $year) AND house_id = $house;";
	$query .= "SELECT hdd, date FROM hdd_daily WHERE hdd = (SELECT MAX(hdd) FROM hdd_daily WHERE YEAR(date) = $year) AND house_id = $house;";

	/* 
	SELECT SUM(td.hdd), SUM(es.hdd) 
	FROM (SELECT house_id, date, hdd FROM hdd_monthly WHERE YEAR(date) = 2012 AND MONTH(date) < 6 OR MONTH(date) > 8) td, 
		 (SELECT house_id, date, hdd FROM estimated_monthly WHERE YEAR(date) = 2012 AND MONTH(date) < 6 OR MONTH(date) > 8) es 
	WHERE td.house_id = 0 
		AND td.house_id = es.house_id 
		AND td.date = es.date;
	 * */
	// 3) total hdd and total estimated hdd during heating season
	$query .= "SELECT SUM(td.hdd), SUM(es.hdd) FROM (SELECT house_id, date, hdd FROM hdd_monthly WHERE YEAR(date) = $year AND MONTH(date) < 6 OR MONTH(date) > 8) td, (SELECT house_id, date, hdd FROM estimated_monthly WHERE YEAR(date) = $year AND MONTH(date) < 6 OR MONTH(date) > 8) es WHERE td.house_id = $house AND td.house_id = es.house_id AND td.date = es.date;";

	/*
	SELECT SUM(td.hdd), SUM(es.hdd) 
	FROM hdd_monthly td, estimated_monthly es 
	WHERE td.house_id = 0
		AND td.house_id = es.house_id
		AND td.date = es.date
		AND YEAR(es.date) = 2012;
	 * */
	// 4) total hdd and total estimated hdd
	$query .= "SELECT SUM(td.hdd), SUM(es.hdd) FROM hdd_monthly td, estimated_monthly es WHERE td.house_id = $house AND td.house_id = es.house_id AND td.date = es.date AND YEAR(es.date) = $year;";
	
	/*
SELECT es.date, td.hdd, es.hdd
FROM hdd_monthly td, estimated_monthly es
WHERE td.house_id = 0
	AND td.house_id = es.house_id
	AND td.date = es.date
	AND YEAR(td.date) = 2012
ORDER BY td.date;
	 * */
	// 5) list by month
	$query .= "SELECT es.date, td.hdd, es.hdd FROM hdd_monthly td, estimated_monthly es WHERE td.house_id = $house AND td.house_id = es.house_id AND td.date = es.date AND YEAR(td.date) = $year ORDER BY td.date;";

	$output = array(
		"coldest_hour" => array(),
		"coldest_day" => array(),
		"columns" => array( "Actual", "Estimated" ),
		"totals_heating_season" => array(),
		"totals" => array(),
		"months" => array()
	);
	//$queries = array( "total_hdd", "total_ashp", "coldest_hour", "coldest_day", "totals_heating_season", "totals", "months" );

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
						$output[ "total_hdd" ] = $row[0];
						$output[ "total_ashp" ] = $row[1];
						break;
					case(1):
						$output[ "coldest_hour" ] = $row = mysqli_fetch_row($result);
						break;
					case(2):
						$output[ "coldest_day" ] = $row = mysqli_fetch_row($result);
						break;
					case(3):
						$output[ "totals_heating_season" ] = $row = mysqli_fetch_row($result);
						break;
					case(4):
						$output[ "totals" ] = $row = mysqli_fetch_row($result);
						break;
					case(5):
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