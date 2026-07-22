DROP TABLE IF EXISTS Flights;

CREATE TABLE Flights (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    flight_number VARCHAR(20) NOT NULL,
    call_sign VARCHAR(20),

    airline VARCHAR(100),
    aircraft_model VARCHAR(100),

    status VARCHAR(50),

    departure_airport CHAR(3),
    departure_city VARCHAR(100),
    departure_terminal VARCHAR(10),
    departure_time DATETIME,

    arrival_airport CHAR(3),
    arrival_city VARCHAR(100),
    arrival_terminal VARCHAR(10),
    arrival_time DATETIME,

    distance_km DECIMAL(10,2),

    is_api TINYINT(1) NOT NULL DEFAULT 0,

    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);