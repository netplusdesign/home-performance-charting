/**
 * @author Larry Burks
 */
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

	// global variables
	var houseId = 0;
	var today, asofDate;
	getMetaData();
	
	// set main navigation hadler
	$("select#dice").change(function(event)
	{
		params = "?date=" + today.toString('yyyy-MM-dd') + "&option=" + this.value;
		if ( this.value == 19 ) 
		{
			// goto Interactive Bae Temp page
			window.location.assign( "interactive_base_temp.html" + params );
		}
		else if ( this.value  < 15 )
		{
   			// goto Daily page
   			window.location.assign( "daily.html" + params );	
		}
		else
		{
			showView( parseInt(this.value) );
			currentOption = this.value;
		}
	});
	// set currentOption
	if (currentOption = $.url().param("option"))
	{
		currentOption = parseInt(currentOption);
		$("option").each( function() {
			this.value == currentOption ? $(this).prop('selected', true) : $(this).prop('selected', false );
		});
	}
	else
	{
		currentOption = 15;
	}
	// set nav selector if arrived from monthly series page
	$("select#dice").each(function()
	{
		( $(this).val() == currentOption ) ? $(this).prop('selected', true) : $(this).prop('selected', false);
	});
	
	// =======================================================================================================
	
	function showView( option )
	{
		// need to remove previous option
		$('div#data,div#chart').empty();
		switch( option )
		{
			case 15:
				getSummaryData();
				break;
			case 16:
				getGenerationData();
				break;
			case 17:
				getUsageData();
				break;
			case 18:
				getHddData();
				break;
			case 20:
				getWaterData();
				break;
		}
	}
	// =======================================================================================================
	
	function getMetaData()
	{	// get initializing variables
		$.getJSON("get_monthly_metadata.php", { house: houseId }, function( json ) 
		{
			asofDate = Date.parse( json['asof'] )
			setToday();
			showAsofDate();
			showYearSelector( json['years'] );
			showView( currentOption );
		});
	}
	function setToday()
	{
		// set date
		if (udate = $.url().param("date"))
		{
			today = Date.parse( udate );
		}
		else
		{
			today = asofDate.clone(); 
			today.moveToFirstDayOfMonth(); 
		}
	}
	function showAsofDate()
	{
		$('div#data').before( "<p id='asof'>As of: " + asofDate.toString('MMM d, yyyy') );
	}
	function showYearSelector( data )
	{
		for (i=0; i<data.length; i++)
		{
			selected = (data[i] == today.getFullYear()) ? "selected='selected'" : '' ;
			$("select#slice").append("<option " + selected + " value='" + data[i] + "'>" + data[i] + "</option>");
		}
	}
	
	// =======================================================================================================	
	function getSummaryData()
	{
		$.getJSON("get_monthly_summary.php", { date: today.toString('yyyy-MM-dd'), house: houseId }, function( json ) 
		{   // date : "2012-11-01"
			showSummaryTable( json );
			setupChartTabs("Usage vs. Solar","Monthly");
			showSummaryYTD( json );
			showUsageVsGen( json );
			showChartTabs(); 
			//$('div#chart').height('70%');
		});
	}
	function showSummaryTable( data )
	{
		var days = asofDate.getDayOfYear() + 1;
		// console.log( "days = " + Math.floor(days) ); 
		$('div#data').append( makeTags('table', 1) );
		$('div#data table').append( "<tr><th></th><th class='lable usage'>Usage</th><th class='lable solar'>Solar</th><th class='lable net'>Net</th><th>Avg.<br/>daily<br/>usage</th><th class='lable hdd'>HDD</th></tr>" );
		$('div#data table').append( "<tr><th class='name'>Total</th><th class='total'>" + Math.round(data['totals'][0]) + "</th><th class='total'>" + Math.round(data['totals'][1]) + "</th><th class='total'>" + Math.round(data['totals'][2]) + "</th><th>" + ( data['totals'][0] / days ).toFixed(1) + "</th><th class='hdd'>" + Math.round(data['totals'][3]) + "</th></tr>" );
		for ( i=0; i<data['months'].length; i++ )
		{
			$('div#data table').append( "<tr><td class='month name'>" + addDateAnchor( Date.parse( data['months'][i][0] ) ) + "</td><td class='usage'>" + Math.round(data['months'][i][1]) + "</td><td class='solar'>" + Math.round(data['months'][i][2]) + "</td><td class='net'>" + Math.round( data['months'][i][3] ) + "</td><td>" + ( data['months'][i][1] / Date.parse( data['months'][i][0] ).getDaysInMonth() ).toFixed(1) + "</td><td class='hdd'>" + Math.round(data['months'][i][4]) + "</td></tr>" );
		}
	}
	function setupChartTabs( tab1, tab2 )
	{
		$('div#chart').prepend("<p id='chartSelector'><a href='#' id='schart'>" + tab1 + "</a><a href='#' id='mchart'>" + tab2 + "</a></p><div id='summary'></div><div id='monthly'></div>");
	}
	function showChartTabs()
	{	/* using this function for both summary and usage views. 
		 * Means if you select the monthly on one view and the 
		 * summary on the other view it will effect both views.
		 * Fix in the future.
		* */
		if (clink = $.cookie('summary_chart'))
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
			$.cookie('summary_chart', 'schart', { expires: 7, path: '/' });
		}
		$("a#schart").click(function(event)
		{
  		   	$("div#summary").show();
  		   	$("div#monthly").hide();
  		   	$("a#schart").addClass("button-active");
  		   	$("a#mchart").removeClass("button-active");
  		   	$.cookie('summary_chart', 'schart', { expires: 7, path: '/' });
  		   	event.preventDefault();
 		});
 		$("a#mchart").click(function(event)
		{
  		   	$("div#monthly").show();
  		   	$("div#summary").hide();
  		   	$("a#mchart").addClass("button-active");
  		   	$("a#schart").removeClass("button-active");
  		   	$.cookie('summary_chart', 'mchart', { expires: 7, path: '/' });
  		   	event.preventDefault();
 		});
	}

	function showUsageVsGen( data ) 
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
		
		for (i=0; i<2; i++)  
		{
			// only need the first two for this chart
			options.xAxis.categories.push( data['columns'][i] ); 
		}
		for (i=0; i<2; i++) 
		{
			series[0].data.push( { 	y : Math.round( Math.abs( data['totals'][i] ) ), 
								color : ((i > 0) && ( Math.abs( data['totals'][0] ) > Math.abs( data['totals'][1] ) )) ? colors[2] : colors[i] });
										// if usage is > solar then danger, danger! (change color of usage to red)
		}
		// Create the chart
		for (i=0; i < series.length; i++) 
		{
			options.series.push(series[i]);
		}
		
		chart = new Highcharts.Chart(options);
	} // end showUsageVsGen function
	
	function showSummaryYTD( data )
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
			yAxis : [{
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
			{
				title : {
					text : 'HDD',
						style: {
							color: '#000000',
							fontWeight: 'normal',
							fontSize: '10px'	
						},
					rotation: 0
				},
				opposite: true,
				min : 0
			}],
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

		// Iterate over the lines and add series names or data
		for (i=0; i<data['months'].length; i++)
		{
			options.xAxis.categories.push( Date.parse(data['months'][i][0]).toString('MMM') );
		}
		for (i = 0 ; i < data['columns'].length ; i++)
		{
			var d = [];
			for (j=0; j < data['months'].length ;j++ ) 
			{
				d.push( Math.round( data['months'][j][i+1] ) );
			}
			if (i == (data['columns'].length-1)) 
			{
				options.series.push( { name : data['columns'][i], data : d, yAxis : 1, type : 'line' } );
			} 
			else
			{
				options.series.push( { name : data['columns'][i], data : d } );
			}	
		}
		Highcharts.setOptions(
		{
			colors: ['#336699', '#669933', '#CC9933', '#CC3333']
		});	
		chart = new Highcharts.Chart(options);	
	}
	
	// ===========================================================================================
	function getGenerationData()
	{
		$.getJSON("get_monthly_generation.php", { date: today.toString('yyyy-MM-dd'), house: houseId }, function( json ) 
		{
			showGenerationTable( json );
			showGenerationYTD( json );
		});
	}
	function showGenerationTable( data )
	{
		var days = asofDate.getDayOfYear() + 1;
		$('div#data').append( "<p><b>Since Jan 2012</b> : " + Math.round(data['total_generated']) + "</p>" );
		$('div#data').append( "<p><b>kWh / day</b> : " + ( data['total_generated'] / days ).toFixed(1) + "</p>" );
		$('div#data').append( "<p><b>Max Wh</b> : " + data['max_solar_hour'][0] + ", Date: " + Date.parse( data['max_solar_hour'][1] ).toString('M/d htt') + "</p>" );
		$('div#data').append( "<p><b>Max kWh day</b> : " + Number( data['max_solar_day'][0] ).toFixed(1) + ", Date: " + Date.parse( data['max_solar_day'][1] ).toString('M/d') + "</p>" );
		$('div#data').append( makeTags('table', 1) );
		$('div#data table').append( "<tr><th></th><th class='lable usage'>Actual</th><th class='lable budget'>Estimated</th><th>Diff(%)<span class='net'>Net</span></th></tr>" );
		var percChange = Math.round( (((data['totals'][0])-data['totals'][1]) / data['totals'][1] ) * 100 );
		style = (percChange >= 0) ? "positive" : "negative";
		$('div#data table').append( "<tr><th class='name'>YTD</th><th class='total'>" + Math.round(data['totals'][0]) + "</th><th class='total'>" + Math.round(data['totals'][1]) + "</th><th class='" + style + "'>" + percChange + "</th></tr>" );
		for ( i=0; i<data['months'].length; i++ )
		{
			percChange = Math.round( (((data['months'][i][1])-data['months'][i][2]) / data['months'][i][2] ) * 100);
			style = (percChange >= 0) ? "positive" : "negative";
			$('div#data table').append( "<tr><td class='month name'>" + addDateAnchor( Date.parse( data['months'][i][0] ), 2 ) + "</td><td class='usage'>" + Math.round(data['months'][i][1]) + "</td><td class='budget'>" + Math.round(data['months'][i][2]) + "</td><td class='" + style + "'>" + percChange + "<span class='net'>" + Math.round(data['months'][i][1]-data['months'][i][2]) + "</span></td></tr>" );
		}
	}
	function showGenerationYTD( data )
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

		//var tags = Array('th.usage,td.usage', 'th.budget,td.budget', 'th span.net, td span.net');
		// Iterate over the lines and add series names or data
		for (i=0; i<data['months'].length; i++)
		{
			options.xAxis.categories.push( Date.parse( data['months'][i][0] ).toString('MMM') );
		}
		data['columns'].push('Net');
		for (i = 0 ; i < data['columns'].length ; i++)
		{
			var d = [];
			for (j=0; j < data['months'].length ;j++ ) 
			{
				if (i < data['columns'].length-1)
				{
					d.push( Math.round(data['months'][j][i+1]) );
				}
				else
				{
					d.push( Math.round( data['months'][j][2] - data['months'][j][1] ) );
				}
			}
			options.series.push( { name : data['columns'][i], data : d } );
		}
		Highcharts.setOptions(
		{
			colors: ['#336699', '#669933', '#CC9933']
		});
			
		chart = new Highcharts.Chart(options);	
	}

	// ===========================================================================================
	function getUsageData( circuit )
	{
		if (!circuit) circuit = "";
		$.getJSON("get_monthly_usage.php", { date: today.toString('yyyy-MM-dd'), circuit : circuit, house: houseId }, function( json ) 
		{
			var circuits = { 
				"total" : { "name" : "Total", "index" : 3 },
				"water_heater" : { "name" : "Water heater", "index" : 7 },
				"ashp" : { "name" : "ASHP", "index" : 8 },
				"water_pump" : { "name" : "Water pump", "index" : 9 },
				"dryer" : { "name" : "Dryer", "index" : 10 },
				"washer" : { "name" : "Washer", "index" : 11 },
				"dishwasher" : { "name" : "Dishwasher", "index" : 12 },
				"stove" : { "name" : "Range", "index" : 13 },
				"all_other" : { "name" : "All other", "index" : 14 }
			};
			json.meta = circuits; // access array via... json.meta['total']['index']
			$('div#data').empty();
			showUsageTable( json );
			$('div#chart').empty();
			setupChartTabs("Circuits YTD","Monthly");
			showUsageYTD( json );
			showUsageCircuits( json );
			showChartTabs(); 
		});
	}
	function showUsageTable( data )
	{
		var days = asofDate.getDayOfYear() + 1;
		$('div#data').append( "<p><b>Since Jan 2012</b> : " + Math.round(data['total_used']) + "</p>" );
		$('div#data').append( "<p><b>kWh / day</b> : " + (data['total_used'] / days).toFixed(1) + "</p>" );
		$('div#data').append( makeTags('table', 1) );
		$('div#data table').append( "<tr><th></th><th class='lable'>YTD&sup1;</th><th class='lable'>% of total</th><th></th></tr>" );
		var i = 0;
		for ( circuit in data.meta )
		{
			$('div#data table').append( "<tr class='circuits'><td class='circuit name'><a id='" + circuit + "' href='#'>" + data.meta[circuit]['name'] + " &darr;</a></td><td class='kwh'>" + Math.round(data['circuits'][i]) + "</td><td  class='perc'>" + Math.round((data['circuits'][i] / data['circuits'][0])*100) + "</td><td></td></tr>" );
			i++;
		}
		$('div#data table tr.circuits td a').on( "click", function(event) {
			getUsageData( $(this).attr('id') );
			event.preventDefault();
		});
		// now show monthly breakdown
		var column = []; 
		var style; 
		var h1 = (data['circuit'] == 'ashp') ? "Projected&sup2;" : "Budgeted";
		var h2 = ((data['circuit'] == 'total') || (data['circuit'] == 'ashp')) ? "Diff(%)" : "";
		$('div#data table').append( "<tr class='monthly'><th class='name'>" + data.meta[ data['circuit'] ]['name'] + "</th><th class='lable usage'>Actual</th><th class='lable budget dh'>" + h1 + "</th><th class='dh'>" + h2 + "<span class='net'>Net</span></th></tr>" );
		if ( data['circuit'] == "total" )
		{
			var percChange = (( data['totals'][1] - data['totals'][0]) / data['totals'][1] ) * 100;
			style = (percChange >= 0) ? "positive" : "negative";
			column.push( parseFloat(data['totals'][0]).toFixed() );
			column.push( data['totals'][1] ); // budgeted
			column.push( percChange.toFixed() ); 
		}
		else if ( data['circuit'] == 'ashp' )
		{
			// calculate projected heat energy value, in this case, based on a base temperature of 58.
			var projected = getProjectedHeatEnergy( data['hdd'] ); 
			var percChange = (( projected - data['totals'][0] ) / data['totals'][0] ) * 100;
			style = (percChange >= 0) ? "positive" : "negative";
			column.push( parseFloat(data['totals'][0]).toFixed(1) ); 
			column.push( projected.toFixed(1) );
			column.push( percChange.toFixed() );
		}
		else
		{
			column.push( parseFloat(data['totals'][0]).toFixed(1) );
			column.push( "0" );
			column.push( "0" );
		}
		$('div#data table').append( "<tr><th class='name'>YTD</th><th class='total'>" + column.shift() + "</th><th class='total dh'>" + column.shift() + "</th><th class='" + style + " dh'>" + column.shift() + "</th></tr>" );
		for ( i=0; i<data['months'].length; i++ )
		{
			column.push( addDateAnchor( Date.parse( data['months'][i][0] ), data.meta[ data['circuit'] ]['index'] ) ); // month name
			if ( data['circuit'] == "total" )
			{
				percChange = (( data['months'][i][2] - data['months'][i][1]) / data['months'][i][2] ) * 100;
				style = (percChange >= 0) ? "positive" : "negative";
				column.push( parseFloat(data['months'][i][1]).toFixed() ); // total kWh for that month  
				column.push( data['months'][i][2] ); // budgeted
				column.push( percChange.toFixed() ); 
				column.push( data['months'][i][2] - data['months'][i][1] ); // net
			}
			else if ( data['circuit'] == 'ashp' )
			{
				var projected = getProjectedHeatEnergy( data['hdds'][i] );
				var percChange = ((projected - data['months'][i][1]) / data['months'][i][1] ) * 100;
				style = (percChange >= 0) ? "positive" : "negative";
				column.push( data['months'][i][1] ); // total kWh for circuit for month
				column.push( projected.toFixed(3) ); 
				column.push( percChange.toFixed() );
				column.push( projected - data['months'][i][1] ); // net
			}
			else
			{
				column.push( data['months'][i][1] ); // total kWh for circuit for month
				column.push( "0" );
				column.push( "0" );
				column.push( "0" );		
			}
			$('div#data table').append( "<tr><td class='month name'>" + column.shift() + "</td><td class='usage'>" + column.shift() + "</td><td class='budget dh'>" + column.shift() + "</td><td class='" + style + " dh'>" + column.shift() + "<span class='net'>" + column.shift() + "</span></td></tr>" );
		}
		((data['circuit'] != 'total') && (data['circuit'] != 'ashp')) ? $('.dh').hide() : $('.dh').show();
		// $('th.budget,td.budget').hide();
		$('div#data').append("<p class='notes'>1. Circuit level data starts March 16, 2012.</p>");
		if (data['circuit'] == 'ashp') $('div#data').append("<p class='notes'>2. Projection based on HDD base 50&deg;</p>");
	}
	function getProjectedHeatEnergy( hdd )
	{
		// makes it easier to adjust the formula in the future, returns kWh
		// return hdd * 0.0769 + 4.7596; // HDD 58F base
		console.log('hdd = ' + hdd);
		// return hdd * 4.0015 + 17.0838; // HDD 27F base
		return hdd * 0.2261 + 0.7565; // HDD 50F base 
	}
	function showUsageYTD( data )
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
				text : data.meta[ data['circuit'] ]['name'],
				style: {
					color: '#000000',
					fontWeight: 'normal',
					fontSize: '12px'
				}
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

		//var tags = Array('th.usage,td.usage', 'th.budget,td.budget', 'th span.net, td span.net');
		// Iterate over the lines and add series names or data
		/*
		for (i=0; i<data['months'].length; i++)
		{
			options.xAxis.categories.push( Date.parse(data['months'][i][0]).toString('MMM') );
		}
		data['columns'].push('Net');
		for (i = 0 ; i < data['columns'].length ; i++)
		{
			var d = [];
			for (j=0; j < data['months'].length ;j++ ) 
			{
				if (i < data['columns'].length-1)
				{
					d.push( parseFloat( round(data['months'][j][i+1]) ));
				}
				else
				{
					d.push( parseFloat( round(data['months'][j][1]) - round(data['months'][j][2]) ));
				}
			}
			options.series.push( { name : data['columns'][i], data : d } );
		}
		*/
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
		if (chart == null) 
    	{
			chart = new Highcharts.Chart(options);
		}	
	}
	function showUsageCircuits( json )
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
		
		Highcharts.setOptions(
		{
			colors: ['#336699', '#669933', '#CC9933', '#CC3333', '#663366', '#999999', '#336699', '#669966']
		});
		
		// Iterate over the lines and add series names or data
		$('td.circuit').each(function(i) {
			//if (i > 1) return false;
			if (i > 0)
			{
				series.data[i-1] = [];
				series.data[i-1][0] = $(this).text();
			}
		});
		$('td.kwh').each(function(i) {
			//if (i > 1) return false;
			if (i > 0) series.data[i-1][1] = parseFloat( $(this).text() );
		});
		// Create the chart
		options.series.push(series);
		chart = new Highcharts.Chart(options);
	} // end showCircuitPie function
	
	// ===========================================================================================
	function getHddData()
	{
		$.getJSON("get_monthly_hdd.php", { date: today.toString('yyyy-MM-dd'), house: houseId }, function( json ) 
		{
			showHddTable( json );
			showHddYTD( json );
		});
	}
	function showHddTable( data )
	{
		// $('div#data').append( "<p><b>HDD (base 65&deg;)</b> : " + Math.round(data['total_hdd']) + "</p>" );
		$('div#data').append( "<p><b>BTU/SF/HDD</b> : " + ((data['total_ashp'] * 3412.14163) / 1408 / data['total_hdd']).toFixed(3) + "</p>" ); 
		var d = Date.parse(data['coldest_hour'][1]);
		$('div#data').append( "<p><b>Coldest temp.</b> : " + data['coldest_hour'][0] + "&deg;, Date: <a href='daily.html?option=4&date=" + d.toString('yyyy-MM-dd') + "&time=" + d.toString('HH') + "'>" + d.toString('M/d htt') + "</a></p>" );
		d = Date.parse(data['coldest_day'][1]);
		$('div#data').append( "<p><b>Coldest day</b> : " + Number( data['coldest_day'][0] ).toFixed(1) + " HDD, Date: <a href='daily.html?option=4&date=" + d.toString('yyyy-MM-dd') + "'>" + d.toString('M/d') + "</a></p>" );
		$('div#data').append( makeTags('table', 1) );
		$('div#data table').append( "<tr><th></th><th class='lable usage'>Actual</th><th class='lable budget'>Estimated</th><th>Diff(%)<span class='net'>Net</span></th></tr>" );
		var percChange = Math.round( (((data['totals_heating_season'][0])-data['totals_heating_season'][1]) / data['totals_heating_season'][1] ) * 100);
		style = (percChange >= 0) ? "positive" : "negative";
		$('div#data table').append( "<tr><th class='name'>Heating season</th><th class='total'>" + Math.round(data['totals_heating_season'][0]) + "</th><th class='total'>" + Math.round(data['totals_heating_season'][1]) + "</th><th class='" + style + "'>" + percChange + "</th></tr>" );
		var percChange = Math.round( (((data['totals'][0])-data['totals'][1]) / data['totals'][1] ) * 100);
		style = (percChange >= 0) ? "positive" : "negative";
		$('div#data table').append( "<tr><th class='name'>YTD</th><th class='total'>" + Math.round(data['totals'][0]) + "</th><th class='total'>" + Math.round(data['totals'][1]) + "</th><th class='" + style + "'>" + percChange + "</th></tr>" );
		for ( i=0; i<data['months'].length; i++ )
		{
			percChange = Math.round( (((data['months'][i][1])-data['months'][i][2]) / data['months'][i][2] ) * 100);
			style = (percChange >= 0) ? "positive" : "negative";
			$('div#data table').append( "<tr><td class='month name'>" + addDateAnchor( Date.parse( data['months'][i][0] ), 6 ) + "</td><td class='usage'>" + Math.round(data['months'][i][1]) + "</td><td class='budget'>" + Math.round(data['months'][i][2]) + "</td><td class='" + style + "'>" + percChange + "<span class='net'>" + Math.round(data['months'][i][1]-data['months'][i][2]) + "</span></td></tr>" );
		}
	}
	function showHddYTD( data )
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

		//var tags = Array('th.usage,td.usage', 'th.budget,td.budget', 'th span.net, td span.net');
		// Iterate over the lines and add series names or data
		for (i=0; i<data['months'].length; i++)
		{
			options.xAxis.categories.push( Date.parse(data['months'][i][0]).toString('MMM') );
		}
		data['columns'].push('Net');
		for (i = 0 ; i < data['columns'].length ; i++)
		{
			var d = [];
			for (j=0; j < data['months'].length ;j++ ) 
			{
				if (i < data['columns'].length-1)
				{
					d.push( Math.round( data['months'][j][i+1] ) );
				}
				else
				{
					d.push( Math.round( data['months'][j][1] - data['months'][j][2] ) );
				}
			}
			options.series.push( { name : data['columns'][i], data : d } );
		}
		Highcharts.setOptions(
		{
			colors: ['#336699', '#669933', '#CC9933']
		});
			
		chart = new Highcharts.Chart(options);	
	}
	
	// ===========================================================================================
	function getWaterData()
	{
		$.getJSON("get_monthly_water.php", { date: today.toString('yyyy-MM-dd'), house: houseId }, function( json ) 
		{
			showWaterTable( json );
			showWaterYTD( json );
		});
	}
	function showWaterTable( data )
	{
		$('div#data').append( makeTags('table', 1) );
		$('div#data table').append( "<tr><th></th><th class='lable usage' colspan='3' style='text-align: center;'>Gallons</th><th colspan='2'>Watt hours / gallon</th></tr>" );
		$('div#data table').append( "<tr><th></th><th class='lable usage'>Cold</th><th class='lable budget'>Hot</th><th>Total</th><th>Hot<br/>Water</th><th>Water<br/>Pump</th></tr>" );
		var water_heater_efficiency, water_pump_efficiency;
		if (today.getFullYear() == 2012)
		{
			// 2012 circuit level data ddn't start till 3/16
			// so need to subtract Jan-Mar water vales for main and hot
			// and subtract kWh for March
			var hot_water = 0; 
			var main_water = 0;
			var water_heater_kwh = 0;
			var water_pump_kwh = 0;
			for (i=3; i<data['months'].length; i++) 
			{
				hot_water += parseFloat(data['months'][i][2]); 
				main_water += parseFloat(data['months'][i][3]);
				water_heater_kwh += parseFloat(data['months'][i][4]);
				water_pump_kwh += parseFloat(data['months'][i][5]);
			}
			water_heater_efficiency = ( water_heater_kwh * 1000 / hot_water ).toFixed(1);
			water_pump_efficiency =   ( water_pump_kwh * 1000 / main_water ).toFixed(3);
		}
		else
		{
			water_heater_efficiency = ( data['totals'][3] * 1000 / data['totals'][1] ).toFixed(1);
			water_pump_efficiency =   ( data['totals'][4] * 1000 / data['totals'][2] ).toFixed(3);
		}
		$('div#data table').append( "<tr><th class='name'>Total</th><th class='total'>" + Math.round(data['totals'][0]) + "</th><th class='total'>" + Math.round(data['totals'][1]) + "</th><th>" + Math.round(data['totals'][2]) + "</th><th>" + water_heater_efficiency + "</th><th>" + water_pump_efficiency + "</th></tr>" );
		for ( i=0; i<data['months'].length; i++ )
		{
			water_heater_efficiency = ( data['months'][i][4] * 1000 / data['months'][i][2] ).toFixed(1);
			water_pump_efficiency =   ( data['months'][i][5] * 1000 / data['months'][i][3] ).toFixed(3);
			$('div#data table').append( "<tr><td class='month name'>" + addDateAnchor( Date.parse( data['months'][i][0] ) ) + "</td><td class='usage'>" + Math.round(data['months'][i][1]) + "</td><td class='budget'>" + Math.round(data['months'][i][2]) + "</td><td>" + Math.round(data['months'][i][3]) + "</td><td>" + water_heater_efficiency + "</td><td>" + water_pump_efficiency + "</td></tr>" );
		}
	}
	function showWaterYTD( data )
	{
		
	}
		
	function addDateAnchor( d, option )
	{
		// date is a date
		if ((d.getFullYear() == 2012) && (d.getMonth() > 0)) 
		{
			option = (option) ? "&option=" + option : "";
			return "<a href='daily.html?date=" + d.toString('yyyy-MM') + option + "'>" + d.toString('MMM') + "</a>"; 
		}
		else 
		{
			return d.toString('MMM');
		}
	}
	function setupCalendar()
	{
		$('table').append( makeTags('tr', 6) );
		$('tr').each(function(index, domEle) { $(domEle).append( makeTags('td', 7) ) });
	}
	function makeTags(tag, n) 
	{
		var str = '<' + tag + '></' + tag + '>', fstr = '';
		for (i=0; i<n; i++) { fstr = fstr + str; }
		return fstr;
	}
});