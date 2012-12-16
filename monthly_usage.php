<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>Usage - Year Summary</title>
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
	$query .= "SELECT SUM(used) FROM energy_monthly;"; 
	
	if (isset($_GET['year']))
	{
		$year = get_post($link, 'year');
	}
	else 
	{
		$year = '2012'; // default
	}
	$where_date = "YEAR(date) = " . $year;
	$where_endate = "YEAR(en.date) = " . $year;
	if ($year == '2012')
	{
		$where_date .= " AND date > DATE('2012-03-01')";
		$where_endate .= " AND en.date > DATE('2012-03-01')";
	}
	// 3) list by circuit, 2012 query must exclude 1/1 through 3/15
	$query .= "SELECT SUM(used), SUM(water_heater), SUM(ashp), SUM(water_pump), SUM(dryer), SUM(washer), SUM(dishwasher), SUM(stove), SUM(used)-(SUM(water_heater)+SUM(ashp)+SUM(water_pump)+SUM(dryer)+SUM(washer)+SUM(dishwasher)+SUM(stove)) FROM energy_monthly WHERE " . $where_date . ";";

	$circuit = null;
	if (isset($_GET['circuit']))
	{
		// replace the last query
		$circuit = get_post($link, 'circuit');
		if ($circuit == 'all_other')
		{
			// 4) dummy query	
			$query .= "SELECT 1+1;";
			// 5) circuit total
			$query .= "SELECT SUM(used)-(SUM(water_heater)+SUM(ashp)+SUM(water_pump)+SUM(dryer)+SUM(washer)+SUM(dishwasher)+SUM(stove)) FROM energy_monthly WHERE " . $where_date . ";";
			// 6) circuit by month
			$query .= "SELECT date, SUM(used)-(SUM(water_heater)+SUM(ashp)+SUM(water_pump)+SUM(dryer)+SUM(washer)+SUM(dishwasher)+SUM(stove)) FROM energy_monthly WHERE " . $where_date . " GROUP BY MONTH(date);";
		}
		else if ($circuit == 'total') 
		{
			// 4) dummy query
			$query .= "SELECT 1+1;";
			// 5) and 6)
			$query .= "SELECT SUM(en.used), SUM(es.used) FROM energy_monthly en, estimated_monthly es WHERE YEAR(en.date) = " . $year . " AND en.date = es.date ORDER BY en.date;";
			$query .= "SELECT en.date, en.used, es.used FROM energy_monthly en, estimated_monthly es WHERE YEAR(en.date) = " . $year . " AND en.date = es.date ORDER BY en.date";
		}
		else
		{
			if ($circuit == 'ashp')
			{
				// 4) get data to calculate projected values --- the reason for all the dummy queries above
				$base = 58.0;
				$query .= "SELECT SUM((" . $base . " - outdoor_deg) * 1 / 24) FROM temperature_hourly WHERE " . $where_date . " AND outdoor_deg < " . $base . " GROUP BY MONTH(date);";
			}
			else 
			{
				$query .= "SELECT 1+1;";
			}
			// 5) circuit total
			$query .= "SELECT SUM(" . $circuit . ") FROM energy_monthly WHERE " . $where_date . ";";
			// 6) circuit by month
			$query .= "SELECT date, " . $circuit . " FROM energy_monthly WHERE " . $where_date . " GROUP BY MONTH(date)";
		}
	} 
	else 
	{
		// 4) dummy query
		$query .= "SELECT 1+1;";
		// 5) and 6) totals by month
		$query .= "SELECT SUM(en.used), SUM(es.used) FROM energy_monthly en, estimated_monthly es WHERE YEAR(en.date) = " . $year . " AND en.date = es.date ORDER BY en.date;";
		$query .= "SELECT en.date, en.used, es.used FROM energy_monthly en, estimated_monthly es WHERE YEAR(en.date) = " . $year . " AND en.date = es.date ORDER BY en.date";
	}
	
	$circuits = array(
		'total' => array(
			'name' => "Total",
			'index' => 3),
		'water_heater' => array(
			'name' => 'Water heater',
			'index' => 7),
		'ashp' => array(
			'name' => 'ASHP',
			'index' => 8),
		'water_pump' => array(
			'name' => 'Water pump',
			'index' => 9),
		'dryer' => array(
			'name' => 'Dryer',
			'index' => 10),
		'washer' => array(
			'name' => 'Washer',
			'index' => 11),
		'dishwasher' => array(
			'name' => 'Dishwasher',
			'index' => 12),
		'stove' => array(
			'name' => 'Range',
			'index' => 13),
		'all_other' => array(
			'name' => 'All other',
			'index' => 3),
	);
	
	$j = 0;
	$hdd = array(); 
	date_default_timezone_set('America/New_York');
	$datetime1 = new DateTime('2012-01-01');
	$datetime2 = 0;
	if (mysqli_multi_query($link, $query)) 
	{
		do 
		{
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
						<option value="monthly_generation.php">Generation</option>
						<option selected="selected" value="monthly_usage.php">Usage</option>
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
						echo "<tr><td></td><td></td><th>Since<br />Jan-12</th><th>kWh/day</th></tr>";
						echo "<tr><td></td><td></td><td>" . round($row[0]) . "</td><td>" . round( ($row[0] / $datetime1->diff($datetime2)->days), 1 ) . "</td></tr>";
						break;
					case(3):
						echo "<tr><th class='name'>Circuit</th><th>YTD&sup1;</th><th>% of total</th><td></td></tr>";
						$row = mysqli_fetch_row($result);
						$i = 0;
						foreach($circuits as $name => $items)
						{
							echo "<tr><td class='circuit name'><a href='monthly_usage.php?year=" . $year . "&circuit=" . $name . "' class='clink'>" . $circuits[$name]['name'] . " &darr;</td><td class='perc'>" . round($row[$i]) . "</td><td>" . round((($row[$i] / $row[0])*100)) . "</td><td></td></tr>";
							$i++;
						}
						break;
					case(4):
						if ($circuit == 'ashp')
						{
							
							// calculate projected kWh and put result into array
							while ($row = mysqli_fetch_row($result)) 
							{
								$hdd[] = $row[0] * 0.0769 + 4.7596; // formula for 58 deg
								//$hdd[] = $row[0] * 0.167 + 0.9623; // formula for 54 deg
							}
							if ($year == '2012') array_shift($hdd); // 2012 temperature data started in February, but circuit data didn't start till mid March 
						}
						break;
					case(5):
						$lable = ($circuit == 'ashp') ? "Projected&sup2;" : "Budgeted";
						echo "<tr><th class='name'>" . $circuits[$circuit]['name'] . "</th><th class='usage'>Actual</th><th class='budget'>" . $lable . "</th><th>Diff(%)<span class='net'>Net</span></th></tr>";
						$row = mysqli_fetch_row($result);
						if($circuit && ($circuit != 'total'))
						{
							if ($circuit == 'ashp')
							{
								$t = round(array_sum($hdd), 1);
								$net = round($t-$row[0]);
								$perc_ch = round((($t-$row[0])/$t)*100);
								$style = ($perc_ch < 0) ? 'negative' : 'positive';
								echo "<tr><th class='name'>YTD</th><th>" . round($row[0]) . "</th><th>" . $t . "</th><th class='" . $style . "'>" . $perc_ch . "</th></tr>";
							}
							else 
							{
								echo "<tr><th class='name'>YTD</th><th>" . round($row[0]) . "</th><th></th><th></th></tr>";
							}
						}
						else
						{
							$perc_ch = round((($row[1]-$row[0])/$row[1])*100);
							$style = ($perc_ch < 0) ? 'negative' : 'positive';
							echo "<tr><th class='name'>YTD</th><th>" . round($row[0]) . "</th><th>" . $row[1] . "</th><th class='" . $style . "'>" . $perc_ch . "</th></tr>";
						}
						break;
					case(6):
						$i = 0;
						while ($row = mysqli_fetch_row($result)) 
						{
							if($circuit && ($circuit != 'total'))
							{
								if ($circuit == 'ashp')
								{
									// calculate projected value
									
									$t = ($hdd[$i] == 0) ? 0.00001 : $hdd[$i]; 
									$net = round($t-$row[1]);
									$perc_ch = round((($t-$row[1])/$t)*100);
									$style = ($perc_ch < 0) ? 'negative' : 'positive';
									echo "<tr><td class='month name'>" . addDateAnchor($row[0]) . "</td><td class='usage'>" . round($row[1],1) . "</td><td class='budget'>" . round($t, 1) . "</td><td class='" . $style . "'>" . $perc_ch . "<span class='net'>" . $net . "</span></td></tr>";
								}
								else 
								{
									echo "<tr><td class='month name'>" . addDateAnchor($row[0]) . "</td><td class='usage'>" . round($row[1],1) . "</td><td></td><td></td></tr>";
								}
							}
							else
							{
								$net = round($row[2]-$row[1]);
								$perc_ch = round((($row[2]-$row[1])/$row[2])*100);
								$style = ($perc_ch < 0) ? 'negative' : 'positive';
								// only link the month if it is a valid month for the month viewer, addDateAnchor(date), return anchor or just text
								echo "<tr><td class='month name'>" . addDateAnchor($row[0]) . "</td><td class='usage'>" . round($row[1]) . "</td><td class='budget'>" . $row[2] . "</td><td class='" . $style . "'>" . $perc_ch . "<span class='net'>" . $net . "</span></td></tr>";
							}
							$i++;
						}
						echo "</table></div>";
						echo "<p class='notes'>1. Circuit level data starts March 16, 2012.</p>";
						if ($circuit == 'ashp') echo "<p class='notes'>2. Projection based on HDD base 58&deg;</p>";
				}
				mysqli_free_result($result);
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
			global $circuit, $circuits;
			if ($circuit)
			{
				return "<a href='daily.html?date=" . date_format(date_create($date), 'Y-m') . "&option=" . $circuits[$circuit]['index'] . "'>" . date_format(date_create($date), 'M') . "</a>";
			}
			else
			{
				return "<a href='daily.html?date=" . date_format(date_create($date), 'Y-m') . "'>" . date_format(date_create($date), 'M') . "</a>";
			}
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
			<div id="chart">
				<p id="chartSelector"><a href="#" id='schart'>Circuits YTD</a><a href="#" id='mchart'>Monthly</a></p>
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
		<script src="js/jquery.cookie.js"></script>
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
      		   	$.cookie('chart', 'schart', { expires: 7, path: '/' });
      		   	event.preventDefault();
     		});
     		$("a#mchart").click(function(event)
   			{
      		   	$("div#monthly").show();
      		   	$("div#summary").hide();
      		   	$("a#mchart").addClass("button-active");
      		   	$("a#schart").removeClass("button-active");
      		   	$.cookie('chart', 'mchart', { expires: 7, path: '/' });
      		   	event.preventDefault();
     		});
			
			showYTD();
			showCircuitPie();
			if (clink = $.cookie('chart'))
			{
				if (clink == 'schart')
				{
					$("div#monthly").hide();
					$("a#schart").addClass("button-active");
				}
				else
				{
					$("div#summary").hide();
					$("a#mchart").addClass("button-active");
				}
			}
			else
			{
				// no cookie, so make one
				$("div#monthly").hide();
				$("a#schart").addClass("button-active");
				$.cookie('chart', 'schart', { expires: 7, path: '/' });
			}
			
			function showCircuitPie() 
			{	
				//if (!first) chart.showLoading('Loading data...');

					var options = {
						chart : {
							renderTo : 'summary',
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
						tooltip: {
							pointFormat: '{series.name}: <b>{point.y} kWh</b>',
							percentageDecimals: 1
						},
        				plotOptions: {  
            				pie: {
								allowPointSelect: true,
								cursor: 'pointer',
								dataLabels: {
									enabled: true,
									color: '#000000',
									connectorColor: '#000000',
									formatter: function() {
										return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %';
									}
								}
							}
        				},
						series : []
					};
						
					var series = new Object();
					series.type = "pie";
					series.name = "Usage"; 
					series.data = [];
					//var colors = Array( '#336699', '#669933', '#DF0101' );
					
					// Iterate over the lines and add series names or data
					$('td.circuit').each(function(i) {
						//if (i > 1) return false;
						if (i > 0)
						{
							series.data[i-1] = [];
							series.data[i-1][0] = $(this).text();
						}
					});
					$('td.perc').each(function(i) {
						//if (i > 1) return false;
						if (i > 0) series.data[i-1][1] = parseFloat( $(this).text() );
					});
					// Create the chart
					options.series.push(series);
					chart = new Highcharts.Chart(options);
			} // end showCircuitPie function
			
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
