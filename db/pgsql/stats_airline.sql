CREATE TABLE stats_airline (
  stats_airline_id serial,
  airline_icao varchar(10) NOT NULL,
  cnt integer NOT NULL,
  airline_name varchar(255) DEFAULT NULL
);

ALTER TABLE stats_airline
  ADD PRIMARY KEY (stats_airline_id), ADD UNIQUE KEY airline_icao (airline_icao);
