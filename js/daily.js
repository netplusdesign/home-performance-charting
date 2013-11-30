/**
 * @author Larry Burks
 * daily.js
 */
$(document).ready(function() 
{
	// global variables
	var houseId = 0;
	var chart = null;
	var today, setDay, chartDay; // setDay includes hour to highlight in day chart
	var validDateRanges = []; // for base data (0-1) and circuit data (2-3)
	var metaData = []; // defines data ranges, lables, mins and maxs
	var dayData = []; // only used in calendar
	var calPerc = []; // only used in calendar
	var currentOption; // currently selected data series
	var monthFile;
	var dayFile;
	// optArr used to determine which color and legend to use for each data series
	var optArr = Array(null,"o","g","b","b","r","r","b","b","b","b","b","b","b","b");			
	// setup color array for calendar heatmap
	var colors = {
					"o": Array( new Color('RGB',255,248,232), new Color('RGB',162,117,0)),
					"g": Array( new Color('RGB',232,255,209), new Color('RGB',73,142,0)),
					"b": Array( new Color('RGB',242,244,255), new Color('RGB',0,20,126)),
					"r": Array( new Color('RGB',252,235,235), new Color('RGB',149,0,0))
				};
	
	// set main navigation hadler
	$("select#slice,select#dice").change(function(event)
	{
		var yr = parseInt( $("select#slice").val() );
		today.set({ 'year' : yr });
		currentOption = $("select#dice").val();
		var params = "?date=" + today.toString('yyyy-MM-dd') + "&option=" + currentOption;
		if ( currentOption == 19 ) 
		{
			// goto Interactive Bae Temp page
			window.location.assign( "interactive_base_temp.html" + params );
		}
		else if ( currentOption  > 14 )
		{
   			// goto Monthly page
   			window.location.assign( "monthly.html" + params );	
		}
		else
		{
			redrawCalendar(event);
		}
	});
	if (currentOption = $.url().param("option"))
	{
		currentOption = parseInt(currentOption);
		$("option").each( function() {
			this.value == currentOption ? $(this).prop('selected', true) : $(this).prop('selected', false );
		});
	}
	else
	{
		currentOption = 1;
	}
	// set selector if arrived from monthly series page
	$("select#dice").each(function()
	{
		( $(this).val() == currentOption ) ? $(this).prop('selected', true) : $(this).prop('selected', false);
	});

	setupCalendar(); // integrate with redraw at some point in future
	
	// add functionality to back and forward arrows
	$("<span id='getPrevMonth'><a href='\#'>&#9664;<\/a><\/span>").replaceAll('span#getPrevMonth');
	$("span#getPrevMonth").click(function(event)
	{
		today.add({ 'months' : -1 }); 
		$('select#slice option[value=' + today.getFullYear() + ']').prop('selected', true); 
		redrawCalendar(event);
	});
	$("<span id='getNextMonth'><a href='\#'>&#9654;<\/a><\/span>").replaceAll('span#getNextMonth');
	$("span#getNextMonth").click(function(event)
	{
		today.add({ 'months' : 1 });
		$('select#slice option[value=' + today.getFullYear() + ']').prop('selected', true); 
		redrawCalendar(event);
	});
	
	// get initializing variables
	$.get('get_daily_metadata.php?house='+houseId, function(data) 
	{
		var lines = data.split('\n');  // splits the file into lines
		$.each(lines, function(lineNo, line) 
		{ 
			var items = line.split(',');  // splits the line into items
			if (items.length == 1) return false;
			if (lineNo == 0)
			{
				for (i=0; i<items.length; i++)
				{
					dt = items[i].split('-'); // split date into parts
					validDateRanges[i] = Date.parse(items[i]);
				}
			}
			else 
			{ 
				// read in the limits
				metaData[0] = [];
				for (i=0; i<items.length; i++)
				{
					metaData[0][i] = parseFloat(items[i]);    
				}
			}
		});
		metaData[1] = Array("kWh","kWh","kWh","&deg;F","&deg;F","HDD","kWh","kWh","kWh","kWh","kWh","kWh","kWh","kWh");
		
		// if udate (date param in url) then use as today else use last valid month
		if (udate = $.url().param("date"))
		{
			var params = udate.split('-');
			if (params.length < 3) params.push(1);
			setDay = new Date(params[0], params[1]-1, params[2]);
			today = new Date(setDay);
		}
		else
		{
			today = new Date(validDateRanges[1].getFullYear(), validDateRanges[1].getMonth(), 1); // last and most recent month of data
		}
		utime = $.url().param("time") ? $.url().param("time") : null;
		
		// temporary solution for year selector
		yrStart = validDateRanges[0].getFullYear();
		//console.log('start = ' + yrStart);
		yrEnd = validDateRanges[1].getFullYear();
		//console.log('start = ' + yrEnd);
		for (i=yrStart; i < yrEnd+1; i++)
		{
			selected = (i == today.getFullYear()) ? "selected='selected'" : '' ;
			$("select#slice").append("<option " + selected + " value='" + i + "'>" + i + "</option>");
		}
		
		// compose strings for first loads
		monthFile = getMonthFilename( today ); 
		dayFile = getDayFilename( today ); 
		
		// load calendar data
		getCalendarData();
	});
		
	// read month day data 
	function getCalendarData()
	{
		dayData.length = 0;
		$.get(monthFile, function(data) 
		{
			// Split the lines
			var lines = data.split('\n');  // splits the file into lines
			// initialize arrays
			metaData[2] = []; // min array
			metaData[3] = []; // max array
			for(i=0; i<15; i++)
			{
				//dayData[i] = [];
				metaData[2][i] = 10000; // min starter value
				metaData[3][i] = -10000; // max starter value
			}
			// reversed for solar
			metaData[2][2] = -10000; // min starter value
			metaData[3][2] = 10000; // max starter value
			
			var offset = 1; // ensures array is 1 based, to match days of month
			// Iterate over the lines and add series name or data
			$.each(lines, function(lineNo, line) 
			{ 
				var items = line.split(','); // splits the lines into items
				var dt = items[0].split('/'); // splits the date item
				dayData[lineNo+offset] = [];
				calPerc[lineNo+offset] = [];
				dayData[lineNo+offset][0] = new Date(dt[2], dt[0], dt[1]); // creates a date
				
				for (i=1; i<items.length; i++)
				{
					dayData[lineNo+offset][i] = parseFloat(items[i]); // set data
					if ( i==2 )
					{	// solar is reversed
						if (dayData[lineNo+offset][i] > metaData[2][i]) metaData[2][i] = dayData[lineNo+offset][i]; // set min
						if (dayData[lineNo+offset][i] < metaData[3][i]) metaData[3][i] = dayData[lineNo+offset][i]; // set max
					}
					else
					{
						if (dayData[lineNo+offset][i] < metaData[2][i]) metaData[2][i] = dayData[lineNo+offset][i]; // set min
						if (dayData[lineNo+offset][i] > metaData[3][i]) metaData[3][i] = dayData[lineNo+offset][i]; // set max 
					}
				}
				
			}); // end each
			if (chart == null) getDayData();
			// calc range
			var range = [];
			for (i=1; i<dayData.length; i++)
			{
				range[i] = [];
				for (j=1; j<dayData[i].length; j++)
				{
					range[i][j] = (j==1) ? metaData[3][j] + Math.abs(metaData[2][j]): metaData[3][j] - metaData[2][j];
				}
			}
			// calc percentage
			for (i=1; i<dayData.length; i++)
			{
				calPerc[i] = [];
				for (j=1; j<dayData[i].length; j++)
				{
					calPerc[i][j] = (j==2) ? 100 + Math.round( ((dayData[i][j] + range[i][j]) / range[i][j] - ((metaData[3][j] + range[i][j]) / range[i][j])) * 100 ): Math.round(((dayData[i][j] + range[i][j]) / range[i][j] - ((metaData[2][j] + range[i][j]) / range[i][j])) * 100); 
				}
			}
			range = null;
			// call updater
			update( currentOption );

		}); // end get
	}
	
	// update calendar
	function update( v ) 
	{
		var dim = Date.getDaysInMonth( today.getFullYear(), today.getMonth() );
		var startDay = getStartDay( today );
		var dayCount = 1;
		// determine whether to show previous month link
		/*
		if( today.isAfter( validDateRanges[0] ) )
    	{
			$("<span id='getPrevMonth'><a href='\#'>&#9664;<\/a><\/span>").replaceAll('span#getPrevMonth');
			$("span#getPrevMonth").click(function(event)
			{
				today.add({ 'months' : -1 }); 
				$('select#slice option[value=' + today.getFullYear() + ']').prop('selected', true); 
				redrawCalendar(event);
			});
		} else {
			$("<span id='getPrevMonth'><\/span>").replaceAll('span#getPrevMonth');
		}
		// determine whether to show next month link
		if( today.isBefore( validDateRanges[1] ) )
	    {
			$("<span id='getNextMonth'><a href='\#'>&#9654;<\/a><\/span>").replaceAll('span#getNextMonth');
			$("span#getNextMonth").click(function(event)
			{
				today.add({ 'months' : 1 });
				$('select#slice option[value=' + today.getFullYear() + ']').prop('selected', true); 
				redrawCalendar(event);
			});
		} else {
			$("<span id='getNextMonth'><\/span>").replaceAll('span#getNextMonth');
		}
		*/
		// update month and year
		$("<span id='monthyear'>" + today.getMonthName() + " " + today.getFullYear() + "<\/span>").replaceAll('span#monthyear');
		
		// remove all content and calDay classes
		$('<td><\/td>').replaceAll('td');
		// rebuild content and classes
		$('td').each( function(index, domEle) 
		{
			if ( (index >= startDay) && (dayCount <= dim) ) 
			{
				if ( dayData.length > 3 )
				{
					// replace text and add calDay class
					if ( isNaN(dayData[dayCount][v]) )
					{
						$(domEle)
							.append(dayCount)
							.addClass('no-data calday');
					} 
					else 
					{
						c = transition3(calPerc[dayCount][v], 100, colors[optArr[v]][0], colors[optArr[v]][1]);
						$(domEle)
							.append(dayCount)							
							.addClass('calday')
							.attr('title', dayData[dayCount][v] + ' ' + metaData[1][v])
							.attr('style', 'background: rgb(' + Math.round(c.r) + ',' + Math.round(c.g) + ',' + Math.round(c.b) + ')');
					}

					// show border around day that is currently shown in chart
					if ( (today.getDate() == dayCount) && (today.getMonth() == chartDay.getMonth()) && (today.getFullYear() == chartDay.getFullYear()) )
					{
						$(domEle).addClass("selected");
					}
					
					$(domEle).hover(function()
					{
		    			$(this).addClass("hover");

					},function()
					{
 						$(this).removeClass("hover");
					});
					$(domEle).click(function(event)
					{
  			    		$('td').removeClass("selected");
  			    		$(this).addClass("selected");
  			    		today.setDate( this.textContent );
  			    		dayFile = getDayFilename( today );
  			    		getDayData();
 					});
 				}
 				else
 				{
 					$(domEle)
 						.append(dayCount)
 						.addClass('no-data calday');
 				}
				dayCount++;
			}
		});
	 
		$("img#legend-range").attr({src: optArr[v] + ".png" });
		$("#low-range").replaceWith("<span id='low-range'>" + metaData[2][v] + " " + metaData[1][v-1] + "<\/span>");
		$("#high-range").replaceWith("<span id='high-range'>" + metaData[3][v] + " " + metaData[1][v-1] + "<\/span>");
						
		// show circuit level data if available
		if( today.isAfter( validDateRanges[2] ) && today.isBefore( validDateRanges[3] ) )
		{
			$("optgroup#circuits option").prop('disabled', false);
		}
		else
		{
			$("optgroup#circuits option").prop('disabled', true);
		}
	}

	function redrawCalendar(event) {
		monthFile = getMonthFilename( today );
		getCalendarData();
		event.preventDefault();
	}

	function getDayData() {
		
		if (chart != null) chart.showLoading('Loading data...');
		chartDay = today.clone();
		
		$.get(dayFile, function(data) 
		{ 
			var options = {
				chart : {
					renderTo : 'chart',
					defaultSeriesType : 'line'
				},
				credits: {
    				enabled: false
				},
					legend: {
    					borderWidth: 0
				},
				title: {
					text : today.getMonthName() + ' ' + today.getDate() + ', ' + today.getFullYear(),
					style: {
						color: '#000000',
						fontWeight: 'normal',
						fontSize: '12px'
					}
				},
				xAxis : {
					title : {
						text : 'Hour of day',
						style: {
							color: '#000000',
							fontWeight: 'normal',
							fontSize: '10px'	
						}
					},
					categories : []
				},
				yAxis : [{
					title : {
						text : 'kWh',
						style: {
							color: '#000000',
							fontWeight: 'normal',
							fontSize: '10px'	
						}
					}, 
					id: 'kwh'
				}, {
					title : {
						text : 'Temperature F',
						style: {
							color: '#000000',
							fontWeight: 'normal',
							fontSize: '10px'	
						}
					}
				}, {
					title : {
						text : 'HDD',
						style: {
							color: '#000000',
							fontWeight: 'normal',
							fontSize: '10px'	
						}
					},
					opposite: true
				}],
				plotOptions: {
    				series: {
        				marker: {
            				enabled: false
        				},  
    				point: {
            			events: {
            	    		mouseOver: function() {
            	        		enabled = true; // this doesn't work
            	    		}
            			}
        			},
        				events: {
            				mouseOut: function() {                        
            	    			enabled = false; // this doesn't work
            				}
        				} 
    				}
				},
				series : []
			};
			
			// Split the lines
			var lines = data.split('\n');  // splits the file into lines
			// console.log("data: " + data);
			var series = [];
			
			// Iterate over the lines and add series names or data
			$.each(lines, function(lineNo, line) 
			{ 
				var items = line.split(','); // splits the line into items
				if(lineNo == 0) 
				{ 
					// series
					var z = 10;
					for(i = 0; i < items.length-1; i++) 
					{
        				series[i] = new Object();
        				series[i].name = items[i+1];
        				series[i].data = [];
        				if (i == 0) 
        				{	// adjusted load
        					series[i].zIndex = 3;
        					series[i].lineWidth = 5;
        				}
        				if (i > 0 && i < 3) 
        				{	// solar and usage
        					series[i].type = "area";
        					series[i].lineWidth = 0;
        				}
        				if ((i > 2) && (i < 7)) series[i].yAxis = 1; // first floor, second floor, basement, outdoor temps
        				
        				if (i == 7) series[i].yAxis = 2; // hdd
        				
        				if (i > 7) series[i].yAxis = 0; // 7 more columns for circuit-level data
        				
        				if (i > 2) series[i].zIndex = z++;
        				//z++;
        			}
				}
				else
				{
					// data
					if (items.length > 1)
					{
						var tm = items[0].split(':'); // splits the time item
						options.xAxis.categories.push(tm[0]); // hour
						for (i = 1; i < items.length; i++) 
						{	
							// if not a degree based value, then divide by 1000
							if ((i > 3) && (i < 9))
							{
								series[i-1].data.push( parseFloat( items[i] ) );
							}
							else
							{
								series[i-1].data.push( parseFloat( items[i] ) / 1000 );
							} 
						}
					}
				} // end else
			}); // end each
			
			// Create the chart
			for (i=0; i < series.length; i++) 
			{
				options.series.push(series[i]);
			}
			
			Highcharts.setOptions(
			{
					colors: ['#CC9933', '#669933', '#336699', '#DF0101', '#FF8000','#F7FE2E', '#58ACFA','#3366CC', '#CC3333', '#FF9655', '#FFF263', '#6AF9C4']
			});
			
			if (chart == null) 
			{
				chart = new Highcharts.Chart(options);
				for (i=3; i<series.length;i++) chart.series[i].hide();
				chart.series[7].show();
			}
			chart.yAxis[0].setExtremes( metaData[0][1]/1000, metaData[0][0]/1000 );  // min, max for kWh
			chart.yAxis[1].setExtremes( metaData[0][2], metaData[0][3] );  // min, max for temp
			chart.yAxis[2].setExtremes( 0, metaData[0][4] );  // min, max for HDD
			
			if (utime && (today.getTime() == setDay.getTime())) 
			{ 
				chart.xAxis[0].addPlotLine({ color: '#FF0000', width: 2, value: utime, id: 'p1' });
			}
			else
			{
				chart.xAxis[0].removePlotLine( 'p1' );
			} 

			for (i=0; i < series.length; i++) 
			{
				chart.series[i].setData(series[i].data);
			}
			chart.setTitle( { text: today.getMonthName() + ' ' + today.getDate() + ', ' + today.getFullYear() } );
			chart.hideLoading();
		}); // end get
	}

	function getMonthFilename( d )
	{
		return "get_daily_data.php?house=" + houseId + "&date=" + today.toString('yyyy-MM-dd');
	}
	
	function getDayFilename( d )
	{
		return "get_hourly_data.php?house=" + houseId + "&date=" + today.toString('yyyy-MM-dd');
	}
	
	function Color(space, a, b, c)
	{
		// Color.prototype.rgb_to_hsv = function()
		Color.prototype.rgb_to_hsv = function()
		{
			maxc = Math.max(this.r, this.g, this.b);
			minc = Math.min(this.r, this.g, this.b);
			this.v = maxc;
			if (minc == maxc)
			{
				this.h = 0;
				this.s = 0;
				//this.v = v;
			}  
			diff = maxc - minc;
			this.s = diff / maxc;
			rc = (maxc - this.r) / diff;
			gc = (maxc - this.g) / diff;
			bc = (maxc - this.b) / diff;
			if (this.r == maxc)
			{
				this.h = bc - gc;
			}
			else if (this.g == maxc) 
			{
				this.h = 2.0 + rc - bc;
			}
			else
			{
				this.h = 4.0 + gc - rc;
			}
			this.h = (this.h / 6.0) % 1.0; //comment: this calculates only the fractional part of h/6
			//this.s = Math.round(this.s);
			//this.v = Math.round(this.v);
		}
		Color.prototype.hsv_to_rgb = function()
		{
			if (this.s == 0.0)
			{
				this.r = this.g = this.b = this.v;
			}
			i = Math.floor(this.h*6.0); //comment: floor() should drop the fractional part
			f = (this.h*6.0) - i;
			p = this.v*(1.0 - this.s);
			q = this.v*(1.0 - this.s*f);
			t = this.v*(1.0 - this.s*(1.0 - f));
			if ((i % 6) == 0) 
			{ 
				this.r = this.v;
				this.g = t;
				this.b = p;
			}
			if (i == 1) 
			{
				this.r = q; 
				this.g = this.v;
				this.b = p;
			}
			if (i == 2) 
			{
				this.r = p;
				this.g = this.v;
				this.b = t;
			}
			if (i == 3)
			{
				this.r = p; 
				this.g = q;
				this.b = this.v;
			}
			if (i == 4) 
			{
				this.r = t;
				this.g = p;
				this.b = this.v;
			}
			if (i == 5) 
			{
				this.r = this.v;
				this.g = p;
				this.b = q;
			}
			//this.r = Math.round(this.r);
			//this.g = Math.round(this.g);
			//this.b = Math.round(this.b);
			//comment: 0 <= i <= 6, so we never come here
		}
		this.space = space;
		if (space == 'RGB')
		{
			this.r = a;
			this.g = b;
			this.b = c;
			this.rgb_to_hsv();
		}
		else if (space == 'HSV')
		{
			this.h = a;
			this.s = b;
			this.v = c;
			this.hsv_to_rgb();
		}
	}
	function transition(value, maximum, start_point, end_point)
	{
		return start_point + (end_point - start_point)*value/maximum;
	}
	function transition3(value, maximum, startColor, endColor)
	{
		r1 = transition(value, maximum, startColor.h, endColor.h);
		r2 = transition(value, maximum, startColor.s, endColor.s);
		r3 = transition(value, maximum, startColor.v, endColor.v);
		return new Color( 'HSV', r1, r2, r3);
	}

	$.expr[":"].econtains = function(obj, index, meta, stack)
	{
		return (obj.textContent || obj.innerText || $(obj).text() || "").toLowerCase() == meta[3].toLowerCase();
	}
	function setupCalendar()
	{
		$('table').append( makeTags('tr', 6) );
		$('tr').each(function(index, domEle) { $(domEle).append( makeTags('td', 7) ) });
	}

});