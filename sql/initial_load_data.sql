LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-feb-2012.csv' INTO TABLE energy_hourly
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';
/*
LOAD DATA LOCAL INFILE '~/documents/978/data/final/temps-hourly-feb-2012.csv' INTO TABLE temperature_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';
*/
/* loads all energy data from march 16 to Nov 30 */
LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-2012-03-16-to-2012-11-30.csv' INTO TABLE energy_hourly
FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 7 LINES 
(date,@ch1,@main1,@main2,solar,water_heater,@ashp1,@ashp2,water_pump,dryer,washer,dishwasher,@range1,@range2)
SET adjusted_load = @main1 + @main2,
used = (@main1 + @main2) - solar,
ashp = @ashp1 + @ashp1,
stove = @range1 + @range2;

/* loads all outdoor temp data from Feb 1 to Nov 30 */
LOAD DATA LOCAL INFILE '~/documents/978/data/final/temperature-hourly-2012-02-01-to-2012-11-30.csv' INTO TABLE temperature_hourly
FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 2 LINES 
(date,outdoor_deg,outdoor_hum,indoor_deg,indoor_hum);

/* not needed if extra data points are removed in HOBOware when merge files. */
DELETE FROM temperature_hourly WHERE (outdoor_deg = 0 AND outdoor_hum = 0) OR (indoor_deg = 0 AND indoor_hum = 0);

/*
LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-mar-2012.csv' INTO TABLE energy_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/temps-hourly-mar-2012.csv' INTO TABLE temperature_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-apr-2012.csv' INTO TABLE energy_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/temps-hourly-apr-2012.csv' INTO TABLE temperature_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-may-2012.csv' INTO TABLE energy_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/temps-hourly-may-2012.csv' INTO TABLE temperature_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-jun-2012.csv' INTO TABLE energy_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/temps-hourly-jun-2012.csv' INTO TABLE temperature_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-jul-2012.csv' INTO TABLE energy_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/temps-hourly-jul-2012.csv' INTO TABLE temperature_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-aug-2012.csv' INTO TABLE energy_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/temps-hourly-aug-2012.csv' INTO TABLE temperature_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-sep-2012.csv' INTO TABLE energy_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/temps-hourly-sep-2012.csv' INTO TABLE temperature_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-oct-2012.csv' INTO TABLE energy_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/temps-hourly-oct-2012.csv' INTO TABLE temperature_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE '~/documents/978/data/final/energy-hourly-nov-2012.csv' INTO TABLE energy_hourly
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';
LOAD DATA LOCAL INFILE '~/documents/978/data/final/temps-hourly-nov-2012.csv' INTO TABLE temperature_hourly 
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';
*/

LOAD DATA LOCAL INFILE '~/documents/978/data/final/water-monthly-jan-nov-2012.csv' INTO TABLE water_monthly
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 1 LINES;
/* add incremental water values */
/*
INSERT INTO water_monthly (date, cold, hot)
VALUES (date('2012-12-01'), xxx.x, xxx.x);
*/

LOAD DATA LOCAL INFILE '~/documents/978/data/final/estimated.csv' INTO TABLE estimated_monthly
	FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';
	
/* insert daily energy table from hourly energy table -- initial load */

INSERT INTO energy_daily
SELECT CAST(eh.date as DATE), 
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
GROUP BY CAST(eh.date as DATE);

/* insert monthly energy table from daily energy table -- initial load */

INSERT INTO energy_monthly
SELECT ed.date, 
	SUM(ed.adjusted_load), 
	SUM(ed.solar),
	SUM(ed.used),
	SUM(ed.water_heater),
	SUM(ed.ashp),
	SUM(ed.water_pump),
	SUM(ed.dryer),
	SUM(ed.washer),
	SUM(ed.dishwasher),
	SUM(ed.stove)
FROM energy_daily ed
GROUP BY YEAR(ed.date), MONTH(ed.date);

/* add values for January */

INSERT INTO energy_monthly (date, adjusted_load, used, solar)
VALUES (date('2012-01-01'), 504.0, 873.0, -369.0);

/* add missing values for first half of March */

UPDATE energy_monthly
SET adjusted_load = -345.0,
	used = 515.0,
	solar = -860.0,
	date = DATE('2012-03-01')
WHERE date = '2012-03-16';

/* update HDD to base 65 */

UPDATE temperature_hourly
SET hdd = IF((65.0 - temperature_hourly.outdoor_deg) * 1 / 24 >= 0,(65.0 - temperature_hourly.outdoor_deg) * 1 / 24,0);

/* update daily temp table from hourly temp table -- initial load */

INSERT INTO temperature_daily
SELECT CAST(th.date as DATE), 
	MIN(th.indoor_deg), 
	MAX(th.indoor_deg),
	MIN(th.indoor_hum),
	MAX(th.indoor_hum),
	MIN(th.outdoor_deg), 
	MAX(th.outdoor_deg),
	MIN(th.outdoor_hum),
	MAX(th.outdoor_hum),
	SUM(th.hdd)
FROM temperature_hourly th
GROUP BY CAST(th.date as DATE);

/* add hdd (base 65, Albany) data for January */

INSERT INTO temperature_daily (date, hdd)
VALUES (date('2012-01-01'), 562.5);

INSERT INTO temperature_daily (date, hdd)
VALUES (date('2012-01-02'), 562.5);

UPDATE limits_hourly
SET used_max = (SELECT MAX(used) FROM energy_hourly),
	solar_min = (SELECT MIN(solar) FROM energy_hourly),
	outdoor_deg_min = (SELECT MIN(outdoor_deg) FROM temperature_hourly),
	outdoor_deg_max = (SELECT MAX(outdoor_deg) FROM temperature_hourly),
	hdd_max = (SELECT MAX(temperature_hourly.hdd) FROM temperature_hourly);
