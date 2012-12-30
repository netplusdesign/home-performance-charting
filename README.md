home-performance-charting
=========================

Visualize your home energy and temperature data.

This app utilizes MySQL, PHP, jQuery, HighCharts and Javascript to view home energy usage, energy generation and temperature data. I use an eMonitor and HOBO Data Loggers to track my net zero home performance. 

Working version posted at http://netplusdesign.com/home_performance/monthly.html

Version 2 
Dec 30, 2012

- Updates to data structure to enable multiple unlimited energy, temperature, humidity and water data sensors.
- Separated database and presentation logic. PHP handles all data requests. JQuery and JS handle presentation. Many data requests now return JSON data.
- Multiple pages have been combined into 3 main pages, Monthly, Daily and Interactive Base Temperature.
- Many data, data units, chart and sql bug fixes.