CREATE TABLE IF NOT EXISTS stats_callsign (
  stats_callsign_id serial,
  callsign_icao varchar(10) NOT NULL,
  cnt integer NOT NULL,
  airline_icao varchar(10) DEFAULT NULL
);

ALTER TABLE stats_callsign ADD PRIMARY KEY (stats_callsign_id), ADD UNIQUE KEY callsign_icao (callsign_icao);

