/**
 * @author Larry Burks
 * base-temp.js
 */
$(document).ready(function() 
{	
	var houseId = 0;
	var chart = null;
	var currentOption = 'hours';
	var previousOption = 'hours';
	var chartOption = new Object();
	chartOption.type = 'scatter';
	chartOption.series = 'single';
	var today, asofDate;
	
	getMetaData();
	
	function getMetaData()
	{	// get initializing variables
		$.getJSON("get_monthly_metadata.php", { house: houseId }, function( json ) 
		{
			asofDate = Date.parse( json['asof'] );
			setToday();
			showYearSelector( json['years'] );
			showAsofDate();
			setupUpdateButton();
			setupOptions();
			plot();
		});
	}
	function showAsofDate()
	{
		$('p#sample').before( "<p id='asof'>As of: " + asofDate.toString('MMM d, yyyy') );
	}
	function showYearSelector( data )
	{
		for (i=0; i<data.length; i++)
		{
			selected = (data[i] == today.getFullYear()) ? "selected='selected'" : '' ;
			$("select#slice").append("<option " + selected + " value='" + data[i] + "'>" + data[i] + "</option>");
		}
	}

	$("select#slice,select#dice").change(function(event)
	{
		var yr = parseInt( $("select#slice").val() );
		today.set({ 'year' : yr });	
	});
	$("select#dice").change(function(event)
	{
		params = today ? "?date=" + today.toString('yyyy-MM-dd') + "&" : "?";
		params = params + "option=" + this.value;
		if ( this.value < 15 )
		{
   			// goto Daily page
   			window.location.assign( "daily.html" + params );	
		}
		else
		{
			// goto Monthly page
			window.location.assign( "monthly.html" + params );
		}
	});
	
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
	
	function setupUpdateButton()
	{
		// add handler to update button
		$("input#update").click(function(event){
			previousOption = currentOption;
			$('ul#calcs li span').text("Calculating...");
			plot();
			event.preventDefault();
		});
	}
	
	function setupOptions()
	{
		$('input:radio[name=period]').change( function() {
			currentOption = $('input:radio[name=period]:checked').val();
		});
	}
	
	function plot()
	{
		if (chart) chart.showLoading('Loading data...');
		var base = $('input#base').val();
		var period = $('input:radio[name=period]:checked').val();
		var dfile = "get_hdd_ashp.php?base=" + base + "&period=" + period + "&house=" + houseId + "&date=" + today.toString('yyyy-MM-dd');
		series = [];
		xr = [];
		yr = [];
		
		$.get(dfile, function(data) 
		{ 
			options = {
				chart : {
					renderTo : 'chart',
					zoomType: 'x'
				},
				credits: {
    				enabled: false
				},
					legend: {
    					borderWidth: 0
				},
				title : {
					text : 'Correlating HDD with Heating energy',
					style: {
						color: '#000000',
						fontWeight: 'normal',
						fontSize: '12px'
					}
				},
				xAxis : {
					title : {
						text : 'HDD'
					}
				},
				yAxis : {
					title : {
						text : 'kWh'
					}
				},
				plotOptions: {
					series: {
						turboThreshold : 0,
        				point: {
            				events: {
                				mouseOver: function() {
                					// put this in a function
                					if (this['date'])
                					{
                						d = Date.parse( this['date'] );
                						if (currentOption == 'hours') dt = d.toString('MMM d, yyyy h tt');
                						if (currentOption == 'days') dt = d.toString('MMM d, yyyy');
                						if (currentOption == 'months') dt = d.toString('MMM, yyyy');
                						$('p#reporting').html("HDD: " + Math.round(this.x*1000)/1000 + "<br />kWh: " + this.y + "<br />Temperature: " + this['temp'] + "&deg;F<br />Date: " + dt );
                    				}
                				},
                				click: function() {
                					if (this['date'])
                					{
                    					dt = this['date'].split(' ');
                    					tm = (previousOption == 'hours') ? '&time=' + dt[1].split(':')[0] : ''; 
                        				location.href = 'daily.html?option=8&date=' + dt[0] + tm;
                        			}
                				}
            				}
        				},
        				events: {
            				mouseOut: function() {                        
                				$('p#reporting').empty();
            				}
        				}
    				},
      				scatter: {
            			marker: {
                			radius: 5
            			},
            			states: {
                			hover: {
                    			lineWidth: 0,
                    			marker: {
                        			enabled: false
                    			}
                			}
            			}
        			},
        			line: {
        				lineWidth: 2
        			}
    			},
				series : []
			}; // end set options
			
			var lines = data.split('\n');  // splits the file into lines

			var i = 0;
			var series = [];
			var points = [];
			points[i] = [];
			
			$.each(lines, function(lineNo, line) 
			{ 
				var items = line.split(','); // splits the line into items
				if (line == '') return false;
				// hdd, ashp
				xr[lineNo] = parseFloat(items[0]);
				yr[lineNo] = parseFloat(items[1]);

				points[i].push( { temp : items[2], date: items[3], x : parseFloat(items[0]), y : parseFloat(items[1]) } );
				if ((currentOption == 'hours') && (chartOption.series == 'multiple'))
				{
					d = Date.parse( items[3] );
					nextDay = d.getDate();
					if ((lineNo > 0) && (nextDay != previousDay ))
					{
						// if next day, then increment series
						series.push( { name : d.toString('yy-MM-dd'), type : chartOption.type, data : points[i] } );
						points[++i] = [];
					}
					previousDay = nextDay;
				}

			});
			if ((currentOption != 'hours') || (chartOption != 'multiple'))
			{
				series.push( { name : 'Data point', type : 'scatter', color : 'rgba(223, 83, 83, .5)', data : points[i] } );
			}
			
			var lr = linearRegression(yr,xr);
			$('span#slope').text( lr.slope.toFixed(4) );
			$('span#intercept').text( lr.intercept.toFixed(3) );
			$('span#r2').text( lr.r2.toFixed(4) );
			lr.intercept > 0 ? $('span#plus').text(' + ') : $('span#plus').text('');  
			
			start_x = Math.min.apply(Math, xr);
			start_y = lr.slope * start_x + lr.intercept;
			end_x = Math.max.apply(Math, xr);
			end_y = lr.slope * end_x + lr.intercept;
			
			series.push( { name : 'Regression Line', type : 'line', color : '#336699', data : [ Array(start_x, start_y), Array(end_x, end_y) ] } );
			
			for (i=0; i < series.length; i++)
			{
				options.series.push(series[i]);
			}

			chart = new Highcharts.Chart(options);
			
			if ((currentOption == 'hours') && (chartOption.series == 'multiple'))
			{	// hide each series except for first and last. 
				// Makes it easier to show / hide individual series.
				// unfortunately also makes it work incredibly slow...
				for (i=1; i<chart.series.length-1; i++)
				{
					chart.series[i].hide();
				}
			}
			chart.hideLoading();
		}); // end get
		
	} // end function plot_init();

	// Linear Regression calculation by Trent Richardson
	// http://trentrichardson.com/2010/04/06/compute-linear-regressions-in-javascript/
	// example input & output
	//var known_y = [1, 2, 3, 4];
	//var known_x = [5.2, 5.7, 5.0, 4.2];

	//var lr = linearRregression(known_y, known_x);
	// now you have:
	// lr.slope
	// lr.intercept
	// lr.r2

	function linearRegression(y,x)
	{
		var lr = {};
		var n = y.length;
		var sum_x = 0;
		var sum_y = 0;
		var sum_xy = 0;
		var sum_xx = 0;
		var sum_yy = 0;
		
		for (var i = 0; i < y.length; i++) 
		{     
    		sum_x += x[i];
    		sum_y += y[i];
    		sum_xy += (x[i]*y[i]);
    		sum_xx += (x[i]*x[i]);
    		sum_yy += (y[i]*y[i]);
		} 
		
		lr['slope'] = (n * sum_xy - sum_x * sum_y) / (n*sum_xx - sum_x * sum_x);
		lr['intercept'] = (sum_y - lr.slope * sum_x)/n;
		lr['r2'] = Math.pow((n*sum_xy - sum_x*sum_y)/Math.sqrt((n*sum_xx-sum_x*sum_x)*(n*sum_yy-sum_y*sum_y)),2);

		return lr;
	}
}); // 