SET @house = 0;

/* TED Data */
LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-feb-2012.csv' INTO TABLE energy_hourly
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n'
	(date,adjusted_load,solar,used)
	SET house_id = @house, device_id = 4;

/* loads all energy data from march 16 to Nov 30 -- eMonitor data */
LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-2012-03-16-to-2012-11-30.csv' INTO TABLE energy_hourly
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 7 LINES 
	(date,@ch1,@main1,@main2,solar,water_heater,@ashp1,@ashp2,water_pump,dryer,washer,dishwasher,@range1,@range2)
	SET house_id = @house,
		device_id = 5,
		adjusted_load = @main1 + @main2,
		used = (@main1 + @main2) - solar,
		ashp = @ashp1 + @ashp2,
		stove = @range1 + @range2;

/* loads all outdoor temp data from Feb 1 to Nov 30 -- outdoor */
LOAD DATA LOCAL INFILE '~/documents/978/data/final/temperature-hourly-outdoor-2012-02-01-to-2012-11-30.csv' INTO TABLE temperature_hourly
FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 2 LINES 
(date,temperature,humidity)
SET house_id = @house, device_id = 0;

/* first floor */
LOAD DATA LOCAL INFILE '~/documents/978/data/final/temperature-hourly-firstfloor-2012-02-01-to-2012-11-30.csv' INTO TABLE temperature_hourly
FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 2 LINES 
(date,temperature,humidity)
SET house_id = @house, device_id = 1;

/* not needed if extra data points are removed in HOBOware when merge files. */
DELETE FROM temperature_hourly WHERE (temperature = 0 AND humidity = 0);

/* load water main monthly data */

LOAD DATA LOCAL INFILE '~/documents/978/data/final/water-monthly-main-jan-nov-2012.csv' INTO TABLE water_monthly
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 1 LINES
	(date,gallons)
	SET house_id = @house, device_id = 6;
	
/* load water hot monthly data */

LOAD DATA LOCAL INFILE '~/documents/978/data/final/water-monthly-hot-jan-nov-2012.csv' INTO TABLE water_monthly
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 1 LINES
	(date,gallons)
	SET house_id = @house, device_id = 7;

LOAD DATA LOCAL INFILE '~/documents/978/data/final/estimated.csv' INTO TABLE estimated_monthly
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n'
	(date,solar,used,hdd,water)
	SET house_id = @house;

/* setup home table */
	
INSERT INTO houses (house_id, name) VALUES (0, 'Up Hill House');
INSERT INTO houses (house_id, name) VALUES (1, 'Test House');
	
/* setup temperature device table */

INSERT INTO monitor_devices (device_id, name) VALUES (0, 'Outdoor');
INSERT INTO monitor_devices (device_id, name) VALUES (1, 'First Floor');
INSERT INTO monitor_devices (device_id, name) VALUES (2, 'Second Floor');
INSERT INTO monitor_devices (device_id, name) VALUES (3, 'Basement');
INSERT INTO monitor_devices (device_id, name) VALUES (4, 'TED');
INSERT INTO monitor_devices (device_id, name) VALUES (5, 'eMonitor');
INSERT INTO monitor_devices (device_id, name) VALUES (6, 'Main Water');
INSERT INTO monitor_devices (device_id, name) VALUES (7, 'Hot Water');
INSERT INTO monitor_devices (device_id, name) VALUES (8, 'Albany Outdoor');
INSERT INTO monitor_devices (device_id, name) VALUES (9, 'Meter Read');

/* insert daily energy table from hourly energy table -- initial load */

INSERT INTO energy_daily
SELECT house_id, 
	device_id,
	CAST(date as DATE), 
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
GROUP BY CAST(date as DATE);

/* insert monthly energy table from daily energy table -- initial load */

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
GROUP BY YEAR(date), MONTH(date);

/* add values for January, meter read */

INSERT INTO energy_monthly (house_id, device_id, date, adjusted_load, used, solar)
VALUES (@house, 9, date('2012-01-01'), 504.0, 873.0, -369.0);

/* add missing values for first half of March. Subtract values from emonitor from monthly total meter read 
	adjusted_load -345.0-(-252.078) = -92.922, 
	solar -860.0-(-462.328) = -397.672, 
	used 515.0-(210.250) = 304.750
*/

INSERT INTO energy_monthly (house_id, device_id, date, adjusted_load, solar, used)
VALUES (@house, 9, DATE('2012-03-01'), -92.922, -397.672, 304.750);

/* add hdd (base 65, Albany) data for January */

INSERT INTO hdd_monthly (house_id, date, hdd)
VALUES (@house, date('2012-01-01'), 1125);

/* calculate and insert HDD to base 65 */

INSERT INTO hdd_hourly
SELECT house_id, date, 
	IF((65.0 - temperature) * 1 / 24 >= 0,(65.0 - temperature) * 1 / 24,0) AS hdd 
FROM temperature_hourly
WHERE house_id = @house
	AND device_id = 0;

INSERT INTO hdd_daily
SELECT house_id, 
	CAST(date as DATE),
	SUM(hdd)
FROM hdd_hourly
WHERE house_id = @house
GROUP BY CAST(date as DATE);

INSERT INTO hdd_monthly
SELECT house_id, date, SUM(hdd) 
FROM hdd_hourly
WHERE house_id = @house
GROUP BY YEAR(date), MONTH(date);

/* update daily temp table from hourly temp table -- initial load */

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
GROUP BY CAST(date as DATE);

/* do the same for indoor temps */

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
GROUP BY CAST(date as DATE);

INSERT INTO limits_hourly
VALUES ( @house,
	(SELECT MAX(used) FROM energy_hourly WHERE house_id = @house),
	(SELECT MIN(solar) FROM energy_hourly WHERE house_id = @house),
	(SELECT MIN(temperature) FROM temperature_hourly WHERE house_id = @house AND device_id = 0),
	(SELECT MAX(temperature) FROM temperature_hourly WHERE house_id = @house AND device_id = 0),
	(SELECT MAX(hdd) FROM hdd_hourly WHERE house_id = @house) );
