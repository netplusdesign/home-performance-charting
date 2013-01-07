home-performance-charting
=========================

Visualize your home energy and temperature data.

This app utilizes MySQL, PHP, jQuery, HighCharts and Javascript to view home energy usage, energy generation and temperature data. I use an eMonitor and HOBO Data Loggers to track my net zero home performance. 

Working version posted at http://netplusdesign.com/home_performance/monthly.html

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