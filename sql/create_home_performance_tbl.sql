CREATE TABLE energy_hourly
(
	house_id		TINYINT			NOT NULL,
	device_id		TINYINT			NOT NULL,
	date 			DATETIME		NOT NULL,
	adjusted_load 	DECIMAL(4),
	solar 			DECIMAL(4),
	used			DECIMAL(4),
	water_heater	DECIMAL(4),
	ashp			DECIMAL(4),
	water_pump		DECIMAL(4),
	dryer			DECIMAL(4),
	washer			DECIMAL(4),
	dishwasher		DECIMAL(4),
	stove			DECIMAL(4)
);

CREATE TABLE energy_daily
(
	house_id		TINYINT			NOT NULL,
	device_id		TINYINT			NOT NULL,
	date			DATE 			NOT NULL,
	adjusted_load 	DECIMAL(5,3),
	solar 			DECIMAL(5,3),
	used			DECIMAL(5,3),
	water_heater	DECIMAL(5,3),
	ashp			DECIMAL(5,3),
	water_pump		DECIMAL(5,3),
	dryer			DECIMAL(5,3),
	washer			DECIMAL(5,3),
	dishwasher		DECIMAL(5,3),
	stove			DECIMAL(5,3)
);

CREATE TABLE energy_monthly
(
	house_id		TINYINT			NOT NULL,
	device_id		TINYINT			NOT NULL,
	date			DATE 			NOT NULL,
	adjusted_load 	DECIMAL(7,3),
	solar 			DECIMAL(7,3),
	used			DECIMAL(7,3),
	water_heater	DECIMAL(6,3),
	ashp			DECIMAL(6,3),
	water_pump		DECIMAL(5,3),
	dryer			DECIMAL(6,3),
	washer			DECIMAL(5,3),
	dishwasher		DECIMAL(5,3),
	stove			DECIMAL(6,3)
);

CREATE TABLE temperature_hourly
(
	house_id		TINYINT			NOT NULL,
	device_id		TINYINT			NOT NULL,
	date			DATETIME		NOT NULL,
	temperature		DECIMAL(6,3),
	humidity		DECIMAL(6,3)
);

CREATE TABLE hdd_hourly
(
	house_id		TINYINT			NOT NULL,
	date			DATETIME		NOT NULL,
	hdd				DECIMAL(4,3)
);

CREATE TABLE hdd_daily
(
	house_id		TINYINT			NOT NULL,
	date			DATE		NOT NULL,
	hdd				DECIMAL(6,3)
);

CREATE TABLE hdd_monthly
(
	house_id		TINYINT			NOT NULL,
	date			DATE		NOT NULL,
	hdd 			DECIMAL(7,3)
);

CREATE TABLE temperature_daily
(
	house_id			TINYINT		NOT NULL,
	device_id			TINYINT		NOT NULL,
	date 				DATE 		NOT NULL,
	temperature_min 	DECIMAL(6,3),
	temperature_max 	DECIMAL(6,3),
	humidity_min 		DECIMAL(6,3),
	humidity_max 		DECIMAL(6,3)
);

CREATE TABLE monitor_devices
(
	device_id			TINYINT			NOT NULL,
	name 				VARCHAR(32) 	NOT NULL
);

CREATE TABLE houses
(
	house_id		TINYINT			NOT NULL,
	name 			VARCHAR(32) 	NOT NULL
);

CREATE TABLE limits_hourly
(
	house_id			TINYINT		NOT NULL,
	used_max			DECIMAL(4),
	solar_min			DECIMAL(4),
	outdoor_deg_min		DECIMAL(6,3),
	outdoor_deg_max		DECIMAL(6,3),
	hdd_max				DECIMAL(4,3)
);

CREATE TABLE water_monthly
(
	house_id		TINYINT			NOT NULL,
	device_id	TINYINT		NOT NULL,
	date		DATE		NOT NULL,
	gallons		DECIMAL(7,1)
);

CREATE TABLE estimated_monthly
(
	house_id	TINYINT		NOT NULL,
	date		DATE		NOT NULL,
	solar		DECIMAL(4,0),
	used		DECIMAL(4,0),
	hdd			DECIMAL(4,0),
	water		DECIMAL(4,0)
);