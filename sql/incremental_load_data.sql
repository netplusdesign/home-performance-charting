SET @year = 2012;
SET @last_month = 11;
SET @house = 0;

/* be sure to update file names */

/* loads energy data -- eMonitor data */
LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-2012-12.csv' INTO TABLE energy_hourly
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 7 LINES 
	(date,@ch1,@main1,@main2,solar,water_heater,@ashp1,@ashp2,water_pump,dryer,washer,dishwasher,@range1,@range2)
	SET house_id = @house,
		device_id = 5,
		adjusted_load = @main1 + @main2,
		used = (@main1 + @main2) - solar,
		ashp = @ashp1 + @ashp2,
		stove = @range1 + @range2;

/* loads outdoor temp data -- outdoor */
LOAD DATA LOCAL INFILE '~/documents/978/data/final/temperature-hourly-outdoor-2012-12.csv' INTO TABLE temperature_hourly
FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 2 LINES 
(date,temperature,humidity)
SET house_id = @house, device_id = 0;

/* first floor */
LOAD DATA LOCAL INFILE '~/documents/978/data/final/temperature-hourly-firstfloor-2012-12.csv' INTO TABLE temperature_hourly
FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 2 LINES 
(date,temperature,humidity)
SET house_id = @house, device_id = 1;

/* second floor */
LOAD DATA LOCAL INFILE '~/documents/978/data/final/temperature-hourly-secondfloor-2012-12.csv' INTO TABLE temperature_hourly
FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 2 LINES 
(date,temperature,humidity)
SET house_id = @house, device_id = 2;

/* basement floor */
LOAD DATA LOCAL INFILE '~/documents/978/data/final/temperature-hourly-basementfloor-2012-12.csv' INTO TABLE temperature_hourly
FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 2 LINES 
(date,temperature,humidity)
SET house_id = @house, device_id = 3;

/* add incremental water values */

INSERT INTO water_monthly (house_id, device_id,date,gallons)
VALUES (@house, 6, date('2012-12-01'), 2044.3);

INSERT INTO water_monthly (house_id, device_id,date,gallons)
VALUES (@house, 7, date('2012-12-01'), 806.4);


/* update daily energy table from hourly energy table -- ongoing load  */

INSERT INTO energy_daily
SELECT house_id,
	device_id,
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
WHERE house_id = @house
	AND YEAR(date) = @year
	AND MONTH(date) > @last_month
GROUP BY CAST(date AS DATE);

INSERT INTO energy_monthly
SELECT house_id,
	device_id,
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
WHERE house_id = @house
	AND YEAR(date) = @year
	AND MONTH(date) > @last_month
GROUP BY MONTH(date);

/* update HDD to base 65 */

INSERT INTO hdd_hourly
SELECT house_id, 
	date, 
	IF((65.0 - temperature) * 1 / 24 >= 0,(65.0 - temperature) * 1 / 24,0) AS hdd 
FROM temperature_hourly
WHERE house_id = @house
	AND device_id = 0
	AND YEAR(date) = @year
	AND MONTH(date) > @last_month;

INSERT INTO hdd_daily
SELECT house_id, 
	CAST(date as DATE),
	SUM(hdd)
FROM hdd_hourly
WHERE house_id = @house
	AND YEAR(date) = @year
	AND MONTH(date) > @last_month
GROUP BY CAST(date as DATE);

INSERT INTO hdd_monthly
SELECT house_id, 
	date, 
	SUM(hdd) 
FROM hdd_hourly
WHERE house_id = @house 
	AND YEAR(date) = @year
	AND MONTH(date) > @last_month
GROUP BY YEAR(date), MONTH(date);

/* update daily temp table from hourly temp table -- ongoing load  */

INSERT INTO temperature_daily
SELECT house_id,
	device_id,
	CAST(date as DATE), 
	MIN(temperature), 
	MAX(temperature),
	MIN(humidity),
	MAX(humidity)
FROM temperature_hourly
WHERE house_id = @house
	AND device_id = 0
	AND YEAR(date) = @year
	AND MONTH(date) > @last_month
GROUP BY CAST(date as DATE);

/* do the same for first floor temps */

INSERT INTO temperature_daily
SELECT house_id,
	device_id,
	CAST(date as DATE), 
	MIN(temperature), 
	MAX(temperature),
	MIN(humidity),
	MAX(humidity)
FROM temperature_hourly
WHERE house_id = @house
	AND device_id = 1
	AND YEAR(date) = @year
	AND MONTH(date) > @last_month
GROUP BY CAST(date as DATE);

/* do the same for second floor temps */

INSERT INTO temperature_daily
SELECT house_id,
	device_id,
	CAST(date as DATE), 
	MIN(temperature), 
	MAX(temperature),
	MIN(humidity),
	MAX(humidity)
FROM temperature_hourly
WHERE house_id = @house
	AND device_id = 2
	AND YEAR(date) = @year
	AND MONTH(date) > @last_month
GROUP BY CAST(date as DATE);

/* do the same for basement floor temps */

INSERT INTO temperature_daily
SELECT house_id,
	device_id,
	CAST(date as DATE), 
	MIN(temperature), 
	MAX(temperature),
	MIN(humidity),
	MAX(humidity)
FROM temperature_hourly
WHERE house_id = @house
	AND device_id = 3
	AND YEAR(date) = @year
	AND MONTH(date) > @last_month
GROUP BY CAST(date as DATE);

UPDATE limits_hourly
SET used_max = (SELECT MAX(used) FROM energy_hourly WHERE house_id = @house),
	solar_min = (SELECT MIN(solar) FROM energy_hourly WHERE house_id = @house),
	outdoor_deg_min = (SELECT MIN(temperature) FROM temperature_hourly WHERE house_id = @house AND device_id = 0),
	outdoor_deg_max = (SELECT MAX(temperature) FROM temperature_hourly WHERE house_id = @house AND device_id = 0),
	hdd_max = (SELECT MAX(hdd) FROM hdd_hourly WHERE house_id = @house)
WHERE house_id = @house;
