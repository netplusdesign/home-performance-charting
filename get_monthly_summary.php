<?php
    // monthly_summary
    
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

	/*  
	SELECT SUM(e.solar), SUM(e.used), SUM(e.adjusted_load), SUM(t.hdd)
	FROM energy_monthly e
		LEFT JOIN hdd_monthly t ON e.date = t.date 
	WHERE YEAR(e.date) = 2012;
	 * */
	// 0) table totals
	$query = "SELECT SUM(e.used), SUM(e.solar), SUM(e.adjusted_load), SUM(t.hdd) FROM energy_monthly e LEFT JOIN hdd_monthly t ON e.date = t.date WHERE YEAR(e.date) = $year;";

	/*
	SELECT e.date, SUM(e.solar), SUM(e.used), SUM(e.adjusted_load), SUM(t.hdd)
	FROM energy_monthly e
		LEFT JOIN hdd_monthly t ON e.date = t.date 
	WHERE YEAR(e.date) = 2012
	GROUP BY MONTH(e.date)
	ORDER BY date;
	 * */
	// 1) table data
	$query .= "SELECT e.date, SUM(e.used), SUM(e.solar), SUM(e.adjusted_load), SUM(t.hdd) FROM energy_monthly e LEFT JOIN hdd_monthly t ON e.date = t.date WHERE YEAR(e.date) = $year GROUP BY MONTH(e.date) ORDER BY e.date;";
		
	$output = array(
		"columns" => array( "Used", "Solar", "Net", "HDD" ),
		"totals" => array(),
		"months" => array()
	);
	$queries = array( "totals", "months" );
	
	if (mysqli_multi_query($link, $query)) 
	{
		$j = 0;
		do 
		{
			if ($result = mysqli_store_result($link)) 
			{
				if ($j == 0)
				{
					$aRow = mysqli_fetch_row($result);
				}
				else 
				{
					$aRow = array();
					while ($row = mysqli_fetch_row($result)) 
					{
						$aRow[] = $row;
					}	
				}
				$output[ $queries[$j] ] = $aRow;
				mysqli_free_result($result);
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