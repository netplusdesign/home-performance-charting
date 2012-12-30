<?php
    // get_monthly_metadata.php
	require_once 'login.php';
	$link = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);

    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
	
	// 0) list of years
	$query = "SELECT YEAR(date) from energy_monthly GROUP BY YEAR(date) ORDER BY date;";
	// 0) as of date
	$query .= "SELECT date from energy_hourly ORDER BY date DESC LIMIT 1;";

	$output = array(
		"years" => array()
	);
	$queries = array( "years", "asof" );
	date_default_timezone_set('America/New_York');
	
	if (mysqli_multi_query($link, $query)) 
	{
		$j = 0;
		do 
		{
			if ($result = mysqli_store_result($link)) 
			{
				if ($j == 1)
				{	// as of date
					$row = mysqli_fetch_row($result);
					$aRow = date_format(date_create($row[0]), 'Y-m-d');
				}
				else 
				{
					$aRow = array();
					while ($row = mysqli_fetch_row($result)) 
					{
						$aRow[] = $row[0];
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
?>