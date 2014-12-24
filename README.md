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
- highcharts.js v3.0.7 — earlier versions may not display charts with > 1000 data points

Version 4.2.5

- Fixed sort bug. eGuage lists data in reverse chronological order. Daily and hourly results now sorted.

Version 4.2.4

- Added eGauge support
- Fixed sql bug caused on Jan 8, 2014 by replacing battery in 1st floor temperature sensor.
- added index.html

Version 4.2.1-3

- Bug fix, timezone missing
- Version 4.2.2 requires Highcharts v3.0.7
- Added plotOptions.series.turboThreshold = 0 to base-temp.js to fix problem with high charts not showing more than 1000 points.
- Significantly improved query response time for Base Temperature Analysis screen.
- Base Temperature Analysis now filters out hours with more than 500 Wh to limit effects of passive solar heating
- Base Temperature Analysis queries now return solar kWh.

Version 4.2

Added year selector to base temperature analysis.
Fixed hours chart bug with limit on query data points.

Version 4.1.1 -- Nov 30, 2013

- Added daily.js — should have been included since v2

Version 4.1 -- Nov 30, 2013

- Bad query is generating incorrect values for HDD in heating season. Removed from UI until query can be fixed.
- Updated Readme.md to indicate required javascript libraries
- Added common.js — should have been included since v2
- Added base-temp.js — should have been included since v2
- There is a known issue with the interactive_base_temp.html page. Highcharts v2.3.5 can only display a limited number of data points. Hoping to try a new version soon and see if this is fixed.

Version 4 -- Feb 10, 2013

- Fixes to enable multi-year navigation on daily view
- Multi-year related bug fixes
- Updates to hourly chart to display multiple temperature sensors 

Version 3 -- Jan 7, 2013

- Updates to data structure to enable multiple house data sets.
- New Water usage screen. Calculates watts per gallon of hot water and watts per gallon water pumped (if you have a well and record well power usage.)
- Other sql bug fixes.

Version 2 - Dec 30, 2012

- Updates to data structure to enable multiple unlimited energy, temperature, humidity and water data sensors.
- Separated database and presentation logic. PHP handles all data requests. JQuery and JS handle presentation. Many data requests now return JSON data.
- Multiple pages have been combined into 3 main pages, Monthly, Daily and Interactive Base Temperature.
- Many data, data units, chart and sql bug fixes.