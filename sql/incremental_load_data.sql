SET @year = 2012;
SET @last_month = 11;

/* be sure to update file names */

/* loads energy data -- eMonitor data */
LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-2012-12.csv' INTO TABLE energy_hourly
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 7 LINES 
	(date,@ch1,@main1,@main2,solar,water_heater,@ashp1,@ashp2,water_pump,dryer,washer,dishwasher,@range1,@range2)
	SET device_id = 5,
		adjusted_load = @main1 + @main2,
		used = (@main1 + @main2) - solar,
		ashp = @ashp1 + @ashp2,
		stove = @range1 + @range2;

/* loads outdoor temp data -- outdoor */
LOAD DATA LOCAL INFILE '~/documents/978/data/final/temperature-hourly-outdoor-2012-12.csv' INTO TABLE temperature_hourly
FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 2 LINES 
(date,temperature,humidity)
SET device_id = 0;

/* first floor */
LOAD DATA LOCAL INFILE '~/documents/978/data/final/temperature-hourly-firstfloor-2012-12.csv' INTO TABLE temperature_hourly
FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 2 LINES 
(date,temperature,humidity)
SET device_id = 1;

/* add incremental water values */
/*
INSERT INTO water_monthly (device_id,date,gallons)
VALUES (6, date('2012-12-01'), xxx.x);

INSERT INTO water_monthly (device_id,date,gallons)
VALUES (7, date('2012-12-01'), xxx.x);
*/

/* update daily energy table from hourly energy table -- ongoing load  */
/*
INSERT INTO energy_daily
SELECT device_id,
	CAST(date AS DATE), 
	SUM(adjusted_load)/1000.0, 
	SUM(solar)/1000.0,
	SUM(used)/1000.0,
	SUM(water_heater)/1000.0,
	SUM(ashp)/1000.0,
	SUM(water_pump)/1000.0,
	SUM(dryer)/1000.0,
	SUM(washer)/1000.0,
	SUM(dishwasher)/1000.0,
	SUM(stove)/1000.0
FROM energy_hourly
WHERE YEAR(date) = @year
AND MONTH(date) > @last_month
GROUP BY CAST(date AS DATE);

INSERT INTO energy_monthly
SELECT device_id,
	date, 
	SUM(adjusted_load), 
	SUM(solar),
	SUM(used),
	SUM(water_heater),
	SUM(ashp),
	SUM(water_pump),
	SUM(dryer),
	SUM(washer),
	SUM(dishwasher),
	SUM(stove)
FROM energy_daily
WHERE YEAR(date) = @year
AND MONTH(date) > @last_month
GROUP BY MONTH(date);

*/
/* update HDD to base 65 */

INSERT INTO hdd_hourly
SELECT date, 
	IF((65.0 - temperature) * 1 / 24 >= 0,(65.0 - temperature) * 1 / 24,0) AS hdd 
FROM temperature_hourly
WHERE device_id = 0
	AND YEAR(date) = @year
	AND MONTH(date) > @last_month;

INSERT INTO hdd_daily
SELECT CAST(date as DATE),
	SUM(hdd)
FROM hdd_hourly
WHERE YEAR(date) = @year
	AND MONTH(date) > @last_month
GROUP BY CAST(date as DATE);

INSERT INTO hdd_monthly
SELECT date, SUM(hdd) 
FROM hdd_hourly
WHERE YEAR(date) = @year
	AND MONTH(date) > @last_month
GROUP BY YEAR(date), MONTH(date);

/* update daily temp table from hourly temp table -- ongoing load  */

INSERT INTO temperature_daily
SELECT device_id,
	CAST(date as DATE), 
	MIN(temperature), 
	MAX(temperature),
	MIN(humidity),
	MAX(humidity)
FROM temperature_hourly
WHERE device_id = 0
	AND YEAR(date) = @year
	AND MONTH(date) > @last_month
GROUP BY CAST(date as DATE);

/* do the same for indoor temps */

INSERT INTO temperature_daily
SELECT device_id,
	CAST(date as DATE), 
	MIN(temperature), 
	MAX(temperature),
	MIN(humidity),
	MAX(humidity)
FROM temperature_hourly
WHERE device_id = 1
	AND YEAR(date) = @year
	AND MONTH(date) > @last_month
GROUP BY CAST(date as DATE);

UPDATE limits_hourly
SET used_max = (SELECT MAX(used) FROM energy_hourly),
	solar_min = (SELECT MIN(solar) FROM energy_hourly),
	outdoor_deg_min = (SELECT MIN(outdoor_deg) FROM temperature_hourly),
	outdoor_deg_max = (SELECT MAX(outdoor_deg) FROM temperature_hourly),
	hdd_max = (SELECT MAX(temperature_hourly.hdd) FROM temperature_hourly);
