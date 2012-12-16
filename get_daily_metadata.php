<?php
	// get_daily_data.php
	require_once 'login.php';
	$link = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);

    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
			
	$query = "SELECT MIN(e.date), MAX(e.date) FROM energy_daily e, temperature_daily t where e.date = t.date UNION SELECT MIN(e.date), MAX(e.date) FROM energy_daily e, temperature_daily t WHERE e.date = t.date AND water_heater IS NOT NULL;";
	$query .= "SELECT used_max, solar_min, outdoor_deg_min, outdoor_deg_max, hdd_max FROM limits_hourly;";
		
	if ($result = mysqli_multi_query($link, $query))
	{
		do 
		{
			if ($result = mysqli_store_result($link)) 
			{
				switch($j++)
				{
					case(0):
						$row = mysqli_fetch_row($result);
						echo $row[0] . "," . $row[1] . ",";
						$row = mysqli_fetch_row($result);
						echo  $row[0] . "," . $row[1] . "\r\n";
						break;
					case(1):
						$row = mysqli_fetch_row($result);
						echo $row[0] . "," . $row[1] . "," . $row[2] . "," . $row[3] . "," . $row[4] . "\r\n";
				}
				mysqli_free_result($result);
			}
		} while (mysqli_next_result($link));
	}
	
	mysqli_close($link);
?>