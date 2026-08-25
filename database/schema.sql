CREATE TABLE province (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT,                                  
    name TEXT NOT NULL,
    status INTEGER,                            
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT
);

CREATE TABLE district_city (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT,                                 
    name TEXT NOT NULL,
    province_id INTEGER NOT NULL,
    status INTEGER,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT,

    FOREIGN KEY (province_id) REFERENCES province(id)
);

CREATE TABLE sub_district (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT,                                 
    name TEXT NOT NULL,
    district_id INTEGER NOT NULL,
    status INTEGER,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT,

    FOREIGN KEY (district_id) REFERENCES district_city(id)
);

CREATE TABLE village (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT,                                 
    name TEXT NOT NULL,
    sub_district_id INTEGER NOT NULL,
    status INTEGER,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT,

    FOREIGN KEY (sub_district_id) REFERENCES sub_district(id)
);


-- Users (dashboard/admin login)


CREATE TABLE user (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    status INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,  
    updated_at TEXT,
    deleted_at TEXT,
    username TEXT UNIQUE                        
);

-- Sensors, wells, and readings


CREATE TABLE sensors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,       -- internal sensor ID
    sensor_code TEXT UNIQUE,                    
    sensor_name TEXT NOT NULL,
    sensor_type TEXT,
    id_device TEXT NOT NULL UNIQUE,             -- ID sent by the sensor/device itself
    status INTEGER,                             -- sensor status
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT
);

CREATE TABLE wells (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    well_code TEXT NOT NULL UNIQUE,
    well_name TEXT NOT NULL,
    village_id INTEGER NOT NULL,
    latitude REAL,
    longitude REAL,
    well_depth REAL,                            
    sensor_id INTEGER UNIQUE,                   -- references sensors.id
    status INTEGER,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT,

    FOREIGN KEY (village_id) REFERENCES village(id),
    FOREIGN KEY (sensor_id) REFERENCES sensors(id)
);

CREATE TABLE sensor_readings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sensor_id INTEGER NOT NULL,
    h1 REAL,
    h2 REAL,
    hasil REAL,                                 -- calculated from h1 and h2
    received_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (sensor_id) REFERENCES sensors(id)
);

CREATE INDEX idx_readings_sensor_time ON sensor_readings(sensor_id, received_at);