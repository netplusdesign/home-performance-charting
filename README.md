home-performance-charting
=========================

Visualize your home energy and temperature data.

This app utilizes MySQL, PHP, jQuery, HighCharts and Javascript to view home energy usage, energy generation and temperature data. I use an eMonitor and HOBO Data Loggers to track my net zero home performance. 

Working version posted at http://netplusdesign.com/home_performance/monthly.html

Requires the following Javascript libraries, not included in this repo:
- jquery-1.7.1.min.js — I have not tested on more recent versions
- jquery.cookie.js
- purl.js — v2.2.1 — newer versions available at https://github.com/allmarkedup/purl
- date.js - from Google
- highcharts.js v3.0.7 — earlier versions may have point limitations < 1000


Version 4.1.2-3

- Added plotOptions.series.turboThreshold = 0 to base-temp.js to fix problem with high charts not showing more than 1000 points.

Version 4.1.1
Nov 30, 2013

- Added daily.js — should have been included since v2

Version 4.1
Nov 30, 2013

- Bad query is generating incorrect values for HDD in heating season. Removed from UI until query can be fixed.
- Updated Readme.md to indicate required javascript libraries
- Added common.js — should have been included since v2
- Added base-temp.js — should have been included since v2
- There is a known issue with the interactive_base_temp.html page. Highcharts v2.3.5 can only display a limited number of data points. Hoping to try a new version soon and see if this is fixed.

Version 4
Feb 10, 2013

- Fixes to enable multi-year navigation on daily view
- Multi-year related bug fixes
- Updates to hourly chart to display multiple temperature sensors 

Version 3
Jan 7, 2013

- Updates to data structure to enable multiple house data sets.
- New Water usage screen. Calculates watts per gallon of hot water and watts per gallon water pumped (if you have a well and record well power usage.)
- Other sql bug fixes.

Version 2 
Dec 30, 2012

- Updates to data structure to enable multiple unlimited energy, temperature, humidity and water data sensors.
- Separated database and presentation logic. PHP handles all data requests. JQuery and JS handle presentation. Many data requests now return JSON data.
- Multiple pages have been combined into 3 main pages, Monthly, Daily and Interactive Base Temperature.
- Many data, data units, chart and sql bug fixes.