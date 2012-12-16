<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>Year Summary</title>
		<meta name="description" content="" />
		<meta name="author" content="Larry Burks" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<link rel="stylesheet" href="style/screen.css" media="screen">
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
	// 0) as of date
	$query .= "SELECT date from energy_hourly ORDER BY date DESC LIMIT 1;"; 

	if (isset($_GET['year']))
	{
		$year = get_post($link, 'year');
	}
	else 
	{
		$year = '2012'; // default
	}

	// 1) table totals
	$query .= "SELECT SUM(e.solar), SUM(e.used), SUM(e.adjusted_load), SUM(t.hdd) FROM energy_monthly e, (SELECT date, SUM(hdd) AS 'hdd' FROM temperature_daily GROUP BY MONTH(date)) t WHERE YEAR(e.date) = " . $year . " AND e.date = t.date;";
	// 2) table data
	$query .= "SELECT e.date, e.solar, e.used, e.adjusted_load, t.hdd FROM energy_monthly e, (SELECT date, SUM(hdd) AS 'hdd' FROM temperature_daily GROUP BY MONTH(date)) t WHERE YEAR(e.date) = " . $year . " AND e.date = t.date GROUP BY MONTH(e.date)";
	
	$j = 0;
	date_default_timezone_set('America/New_York');
	$datetime1 = new DateTime('2012-01-01'); // move in date
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
						<option selected="selected" value="monthly_summary.php">Summary</option>
						<option value="monthly_generation.php">Generation</option>
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
						echo "<div id='data'><table>"; 
						echo "<tr><th class='name'>Month</th><th class='lable usage'>Usage</th><th class='lable solar'>Solar</th><th class='lable net'>Net</th><th>Avg.<br/>daily<br/>usage</th><th class='lable hdd'>HDD</th></tr>";
						$row = mysqli_fetch_row($result);
						//$style = intval($row[2]) < 0 ? 'negative' : 'positive';
						echo "<tr><th class='name'>Total</th><th class='total'>" . round($row[1]) . "</th><th class='total'>" . round(($row[0])) . "</th><th class='" . $style . "'>" . format_neg(round($row[2])) . "</th><th>" . sprintf('%.1f', $row[1]/$datetime1->diff($datetime2)->days, 1) . "</th><th>" . round($row[3]) . "</th></tr>";
						break;
					case(3):
						while ($row = mysqli_fetch_row($result)) 
						{
							//$style = intval($row[3]) < 0 ? 'negative' : 'positive';
							// only link the month if it is a valid month for the month viewer, addDateAnchor(date), return anchor or just text
							echo "<tr><td class='month name'>" . addDateAnchor($row[0]) . "</a></td><td class='usage'>" . round($row[2]) . "</td><td class='solar'>" . round(($row[1])) . "</td><td class='" . $style . " net'>" . format_neg(round($row[3])) . "</td><td>" . sprintf('%.1f', $row[2]/days_in_month($row[0]), 1) . "</td><td class='hdd'>" . round($row[4]) . "</td></tr>";
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
		if (($tmp['year'] = 2012) && ($tmp['month'] > 1) && ($tmp['day'] < 11)) 
		{
			//return 'true';
			return "<a href='daily.html?date=" . date_format(date_create($date), 'Y-m') . "'>" . date_format(date_create($date), 'M') . "</a>";
		}
		else 
		{
			//return 'false';
			return date_format(date_create($date), 'M');
		}
	}
	function days_in_month($date)
	{
		return date('t', strtotime($date));
	}
	function format_neg($num)
	{
		// return (float)$num < 0 ? "(" . abs($num) . ")" : $num;
		return $num;
	}
	function get_post($link, $var)
	{
		$temp = mysqli_real_escape_string($link, $_GET[$var]);
		if ($temp == '') $temp = 'NULL';
		return $temp;
	}
?>
			</div>
			<div id="chart">
				<p id="chartSelector"><a href="#" id='schart'>Usage vs. Solar</a><a href="#" id='mchart'>Monthly</a></p>
				<div id="summary"></div>
				<div id="monthly"></div>
			</div>
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
			
			$("a#schart").click(function(event)
   			{
      		   	$("div#summary").show();
      		   	$("div#monthly").hide();
      		   	$("a#schart").addClass("button-active");
      		   	$("a#mchart").removeClass("button-active");
      		   	event.preventDefault();
     		});
     		$("a#mchart").click(function(event)
   			{
      		   	$("div#monthly").show();
      		   	$("div#summary").hide();
      		   	$("a#mchart").addClass("button-active");
      		   	$("a#schart").removeClass("button-active");
      		   	event.preventDefault();
     		});
			
			showYTD();
			$("div#monthly").hide();
			$("a#schart").addClass("button-active");
			showUsageVsGen();

			function showUsageVsGen() 
			{	
				//if (!first) chart.showLoading('Loading data...');

					var options = {
						chart : {
							renderTo : 'summary',
							defaultSeriesType : 'column',
							height : 300
						},
						credits: {
            				enabled: false
       					},
       					legend: {
            				enabled: false
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
                    	        	enabled: true,
                    	        	align: 'center',
                    	        	color: '#FFFFFF',
                    	        	y: 16
                    			}
                    		},
                    		series: {
                    			enableMouseTracking: false,
                    			pointPadding: 0,
                				groupPadding: 0.05,
                				borderWidth: 0,
               					shadow: false
            				}
        				},
						series : []
					};
						
					var series = [];
					series[0] = new Object();
					series[0].data = [];
					var colors = Array( '#336699', '#669933', '#DF0101' );
					
					// Iterate over the lines and add series names or data
					$('th.lable').each(function(i) {
						if (i > 1) return false;
						options.xAxis.categories.push($(this).text())
					});
					$('th.total').each(function(i) {
						if (i > 1) return false;
						series[0].data[i] = new Object();
						series[0].data[i].y = Math.abs(parseFloat( $(this).text() ));
						series[0].data[i].color = colors[i];
						if((i > 0) && (series[0].data[0].y > series[0].data[1].y)) 
						{
							series[0].data[i].color = colors[2];
						}
					});
					// Create the chart
					for (i=0; i < series.length; i++) 
					{
						options.series.push(series[i]);
					}
					
					chart = new Highcharts.Chart(options);
			} // end showUsageVsGen function
			
			function showYTD()
			{
				var options = {
					chart : {
						renderTo : 'monthly',
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

				var tags = Array('th.usage,td.usage', 'th.solar,td.solar', 'th.net,td.net');	// 'td.hdd'
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
