CREATE DATABASE home_performance;

USE home_performance;

CREATE TABLE energy_hourly
(
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
	date			DATE 		NOT NULL,
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
	date			DATE 		NOT NULL,
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
	date		DATETIME		NOT NULL,
	indoor_deg	DECIMAL(6,3),
	indoor_hum	DECIMAL(6,3),
	outdoor_deg	DECIMAL(6,3),
	outdoor_hum	DECIMAL(6,3),
	hdd			DECIMAL(4,3)
);

CREATE TABLE temperature_daily
(
	date 				DATE 		NOT NULL,
	indoor_deg_min 		DECIMAL(6,3),
	indoor_deg_max 		DECIMAL(6,3),
	indoor_hum_min 		DECIMAL(6,3),
	indoor_hum_max 		DECIMAL(6,3),
	outdoor_deg_min 	DECIMAL(6,3),
	outdoor_deg_max 	DECIMAL(6,3),
	outdoor_hum_min 	DECIMAL(6,3),
	outdoor_hum_max 	DECIMAL(6,3),
	hdd 				DECIMAL(6,3)
);

CREATE TABLE limits_hourly
(
	used_max			DECIMAL(4),
	solar_min			DECIMAL(4),
	outdoor_deg_min		DECIMAL(6,3),
	outdoor_deg_max		DECIMAL(6,3),
	hdd_max				DECIMAL(4,3)
);

INSERT INTO limits_hourly (used_max) VALUES (null);

CREATE TABLE water_monthly
(
	date	DATE		NOT NULL,
	cold	DECIMAL(7,1),
	hot		DECIMAL(7,1)
);

CREATE TABLE estimated_monthly
(
	date		DATE		NOT NULL,
	solar		DECIMAL(4,0),
	used		DECIMAL(4,0),
	hdd			DECIMAL(4,0),
	water		DECIMAL(4,0)
);