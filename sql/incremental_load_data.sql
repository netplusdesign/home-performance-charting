SET @year = 2012;
SET @last_month = 10;

/* be sure to update file names */
/*
LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-nov-2012.csv' INTO TABLE energy_hourly
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';
*/
LOAD DATA LOCAL INFILE '~/documents/978/data/final/temps-hourly-nov-2012.csv' INTO TABLE temperature_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

/* add water values */
/*
INSERT INTO water_monthly (date, cold, hot)
VALUES (date('2012-12-01'), xxx.x, xxx.x);
*/

/* update daily energy table from hourly energy table -- ongoing load  */
/*
INSERT INTO energy_daily
SELECT CAST(eh.date AS DATE), 
	SUM(eh.adjusted_load)/1000.0, 
	SUM(eh.solar)/1000.0,
	SUM(eh.used)/1000.0,
	SUM(eh.water_heater)/1000.0,
	SUM(eh.ashp)/1000.0,
	SUM(eh.water_pump)/1000.0,
	SUM(eh.dryer)/1000.0,
	SUM(eh.washer)/1000.0,
	SUM(eh.dishwasher)/1000.0,
	SUM(eh.stove)/1000.0
FROM energy_hourly eh
WHERE YEAR(eh.date) = @year
AND MONTH(eh.date) > @last_month
GROUP BY CAST(eh.date AS DATE);

INSERT INTO energy_monthly
SELECT e.date, 
	SUM(e.adjusted_load), 
	SUM(e.solar),
	SUM(e.used),
	SUM(e.water_heater),
	SUM(e.ashp),
	SUM(e.water_pump),
	SUM(e.dryer),
	SUM(e.washer),
	SUM(e.dishwasher),
	SUM(e.stove)
FROM energy_daily e
WHERE YEAR(e.date) = @year
AND MONTH(e.date) > @last_month
GROUP BY MONTH(e.date);
*/
/* update HDD to base 65 */

UPDATE temperature_hourly
SET hdd = IF((65.0 - temperature_hourly.outdoor_deg) * 1 / 24 >= 0,(65.0 - temperature_hourly.outdoor_deg) * 1 / 24,0)
WHERE YEAR(temperature_hourly.date) = @year
AND MONTH(temperature_hourly.date) > @last_month;

/* update daily temp table from hourly temp table -- ongoing load  */

INSERT INTO temperature_daily
SELECT CAST(th.date AS DATE), 
	min(th.indoor_deg), 
	max(th.indoor_deg),
	min(th.indoor_hum),
	max(th.indoor_hum),
	min(th.outdoor_deg), 
	max(th.outdoor_deg),
	min(th.outdoor_hum),
	max(th.outdoor_hum),
	sum(th.hdd)
FROM temperature_hourly th
WHERE YEAR(th.date) = @year
AND MONTH(th.date) > @last_month
GROUP BY CAST(th.date AS DATE);

UPDATE limits_hourly
SET used_max = (SELECT MAX(used) FROM energy_hourly),
	solar_min = (SELECT MIN(solar) FROM energy_hourly),
	outdoor_deg_min = (SELECT MIN(outdoor_deg) FROM temperature_hourly),
	outdoor_deg_max = (SELECT MAX(outdoor_deg) FROM temperature_hourly),
	hdd_max = (SELECT MAX(temperature_hourly.hdd) FROM temperature_hourly);
