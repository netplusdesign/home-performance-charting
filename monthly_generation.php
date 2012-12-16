<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>Generation - Year Summary</title>
		<meta name="description" content="Home Performance Data" />
		<meta name="author" content="Larry Burks" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<link rel="stylesheet" href="style/screen.css" media="screen">
		<style>span.net { display: none; }</style>
	</head>

	<body>
		<div id="page">
			<header>
				<h1><a href="http://uphillhouse.wordpress.com">Up Hill House</a></h1>
			</header>
			<div id="calendar">
<?php
	require_once 'login.php';
	$link = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);

    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
	// 0) list of years
	$query = "SELECT YEAR(date) from energy_monthly GROUP BY YEAR(date) ORDER BY date;";
	// 1) as of date
	$query .= "SELECT date from energy_hourly ORDER BY date DESC LIMIT 1;";
	// 2) total used  
	$query .= "SELECT SUM(solar) FROM energy_monthly;"; 
	// 3 and 4) max solar hour and day
	$query .= "SELECT solar, date FROM energy_hourly WHERE solar = (SELECT MIN(solar) FROM energy_hourly);";
	$query .= "SELECT solar, date FROM energy_daily WHERE solar = (SELECT MIN(solar) FROM energy_daily);";
	
	if (isset($_GET['year']))
	{
		$year = get_post($link, 'year');
	}
	else 
	{
		$year = '2012'; // default
	}
	
	// 5 and 6) list by month
	$query .= "SELECT SUM(en.solar), SUM(es.solar) FROM energy_monthly en, estimated_monthly es WHERE YEAR(en.date) = " . $year . " AND en.date = es.date ORDER BY en.date;";
	$query .= "SELECT en.date, en.solar, es.solar FROM energy_monthly en, estimated_monthly es WHERE YEAR(en.date) = " . $year . " AND en.date = es.date ORDER BY en.date";

	$j = 0;
	date_default_timezone_set('America/New_York');
	$datetime1 = new DateTime('2012-01-01');
	$datetime2 = 0;
	if (mysqli_multi_query($link, $query)) 
	{
		do 
		{
			//* store first result set */
			if ($result = mysqli_store_result($link)) 
			{
				switch($j++)
				{
					case(0):
echo <<<_END
			<nav>
				<select id="slice">
_END;
						while ($row = mysqli_fetch_row($result)) 
						{
							$selected = ($row[0] == $year ) ? "selected='selected'" : '';
							echo "<option " . $selected . " value='$row[0]'>$row[0]</option>";
						}
echo <<<_END
				</select>
				<select id="dice">
					<optgroup label="Monthly">
						<option value="monthly_summary.php">Summary</option>
						<option selected="selected" value="monthly_generation.php">Generation</option>
						<option value="monthly_usage.php">Usage</option>
						<option value="monthly_hdd.php">Heating Degree Days</option>
						<option value="interactive_base_temp.html">Base Temp Analysis</option>
					</optgroup>
					<optgroup label="Daily">
						<option id="adjusted" value="daily.html?option=1">Net Usage</option>
						<option id="solar" value="daily.html?option=2">Generation</option>
						<option id="usage" value="daily.html?option=3">Usage</option>
						<optgroup label="Daily Usage by Circuit">
							<option id="usage-ashp" value="daily.html?option=8">ASHP</option>
							<option id="usage-dwh" value="daily.html?option=7">Water Heater</option>
							<option id="usage-range" value="daily.html?option=13">Range</option>
							<option id="usage-dryer" value="daily.html?option=10">Dryer</option>
							<option id="usage-dw" value="daily.html?option=12">Dish Washer</option>
							<option id="usage-wp" value="daily.html?option=9">Water Pump</option>
							<option id="usage-washer" value="daily.html?option=11">Washer</option>
						</optgroup>
						<optgroup label="Daily Temperature">
							<option id="hdd" value="daily.html?option=6">Heating Degree Days</option>
							<option id="temp-low" value="daily.html?option=4">Outdoor Temperature (Low)</option>
							<option id="temp-high" value="daily.html?option=9">Outdoor Temperature (High)</option>
						</optgroup>
					</optgroup>

				</select>
			</nav>
_END;
						break;
					case(1):
						$row = mysqli_fetch_row($result); 
						$datetime2 = new DateTime($row[0]);
						echo "<p id=\"asof\">As of: " . date_format(date_create($row[0]), 'F j, Y') . "</p>";
						break;
					case(2):
						$row = mysqli_fetch_row($result); 						
						echo "<div id='data'><table>";
						echo "<tr><td></td><td></td><th>&gt;1/2012</th><th>kWh/day</th></tr>";
						echo "<tr><td></td><td></td><td>" . round($row[0]) . "</td><td>" . round( ($row[0] / $datetime1->diff($datetime2)->days), 1 ) . "</td></tr>";
						break;
					case(3):
						echo "<tr><td></td><td></td><th>Max Wh</th><th>Date</th></tr>";
						$row = mysqli_fetch_row($result);
						echo "<tr><td></td><td></td><td>" . $row[0] . "</td><td><a href='daily.html?option=2&date=" . date_format(date_create($row[1]), 'Y-m-d') . "&time=" . date_format(date_create($row[1]), 'h') . "'>" . date_format(date_create($row[1]), 'n/j ga') . "</a></td></tr>";
						break;
					case(4):
						echo "<tr><td></td><td></td><th>Max kWh day</th><th>Date</th></tr>";
						$row = mysqli_fetch_row($result);
						echo "<tr><td></td><td></td><td>" . $row[0] . "</td><td><a href='daily.html?option=2&date=" . date_format(date_create($row[1]), 'Y-m-d') . "'>" . date_format(date_create($row[1]), 'n/j') . "</a></td></tr>";
						break;
					case(5):
						echo "<tr><td></td><th  class='usage'>Actual</th><th class='budget'>Estimated</th><th>Diff(%)<span class='net'>Net</span></th></tr>";
						$row = mysqli_fetch_row($result);
						$perc_ch = round((($row[0]-$row[1])/$row[1])*100);
						$style = 'positive';
						if ($perc_ch < 0) $style = 'negative';
						echo "<tr><th class='name'>YTD</th><th>" . round($row[0]) . "</th><th>" . $row[1] . "</th><th class='" . $style . "'>" . $perc_ch . "</th></tr>";
						break;
					case(6):
						while ($row = mysqli_fetch_row($result)) 
						{
							$net = round($row[1]-$row[2]);
							$perc_ch = round((($row[1]-$row[2])/$row[2])*100);
							$style = 'positive';
							if ($perc_ch < 0) $style = 'negative';
							echo "<tr><td class='month name'>" . addDateAnchor($row[0]) . "</td><td class='usage'>" . round($row[1]) . "</td><td class='budget'>" . $row[2] . "</td><td class='" . $style . "'>" . $perc_ch . "<span class='net'>" . $net . "</span></td></tr>";
						}
						echo "</table></div>";
				}
				mysqli_free_result($result);
			}
			/* print divider */
			if (mysqli_more_results($link)) 
			{
				// 
			}
		} while (mysqli_next_result($link));
	} 	
	mysqli_close($link);

	function addDateAnchor($date)
	{
		$tmp = date_parse($date);
		// don't add anchor if there's no data, in this case January 
		if (($tmp['year'] = 2012) && ($tmp['month'] > 1) && ($tmp['day'] < 11)) 
		{
			// if $circuit then add option value
			return "<a href='daily.html?date=" . date_format(date_create($date), 'Y-m') . "&option=2'>" . date_format(date_create($date), 'M') . "</a>";
		}
		else 
		{
			//return 'false';
			return date_format(date_create($date), 'M');
		}	
	}
	function get_post($link, $var)
	{
		$temp = mysqli_real_escape_string($link, $_GET[$var]);
		if ($temp == '') $temp = 'NULL';
		return $temp;
	}
?>
			</div>
			<div id="chart"></div>
			<footer>
				<p>
					&copy; Copyright netplusdesign 2012
				</p>
			</footer>
		</div>
		<script src="js/jquery-1.7.1.min.js"></script>
		<script src="js/highcharts.js" type="text/javascript"></script>
		<script>
		$(document).ready(function() 
		{
			// this first function fixes a orientation bug, via...
			// http://webdesignerwall.com/tutorials/iphone-safari-viewport-scaling-bug
			(function(doc) 
			{
    			var addEvent = 'addEventListener',
       			type = 'gesturestart',
        		qsa = 'querySelectorAll',
        		scales = [1, 1],
        		meta = qsa in doc ? doc[qsa]('meta[name=viewport]') : [];
    			function fix() 
    			{
    			    meta.content = 'width=device-width,minimum-scale=' + scales[0] + ',maximum-scale=' + scales[1];
      			    doc.removeEventListener(type, fix, true);
    			}
			    if ((meta = meta[meta.length - 1]) && addEvent in doc) 
			    {
			        fix();
			        scales = [.25, 1.6];
			        doc[addEvent](type, fix, true);
			    }
			}(document));
			
       		$("select#dice").change(function(event)
       		{
       			// if url already has a '?' then use a '&' instead
       			con = (this.value.indexOf('?') > -1) ? "&" : "?";
       			window.location.assign( this.value + con + 'year=' + $("select#slice").val() ); 
     		});
     		
			showYTD();
     		
			function showYTD()
			{
				var options = {
					chart : {
						renderTo : 'chart',
						defaultSeriesType : 'column',
						height : 300
					},
					credits: {
           				enabled: false
      				},
       				legend: {
            			enabled: true,
            			borderWidth: 0
        			},
					title : {
						text : null
					},
					xAxis : {
						categories : []
					},
					yAxis : {
						title : {
							text : 'kWh',
								style: {
									color: '#000000',
									fontWeight: 'normal',
									fontSize: '10px'	
								},
							rotation: 0
						}
					},
        			plotOptions: {  
            			column: {
                   			dataLabels: {
                   	        	enabled: false
                   			}
                   		},
                   		series: {
                   			enableMouseTracking: true,
                   			pointPadding: 0.05,
               				groupPadding: 0.09,
               				borderWidth: 0,
               				shadow: false
           				}
       				},
					series : []
				};
				
				var chart;
				var series = [];

				var tags = Array('th.usage,td.usage', 'th.budget,td.budget', 'th span.net, td span.net');	// 'td.hdd'
				// Iterate over the lines and add series names or data
				$('td.month').each(function(i) {
					options.xAxis.categories.push($(this).text());
				});
				for (i = 0 ; i < tags.length ; i++)
				{
					series[i] = new Object();
					series[i].data = [];
					$(tags[i]).each(function(j) {
						if (j == 0) 
						{	// name
							series[i].name = $(this).text();
						}
						else
						{	// data
							series[i].data.push( parseFloat( $(this).text() ));
						}
					});
					options.series.push(series[i]);
				}
				
				Highcharts.setOptions(
				{
       				colors: ['#336699', '#669933', '#CC9933']
    			});
    				
				chart = new Highcharts.Chart(options);	
			}

		});
		
		</script>
	</body>
</html>
