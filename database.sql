CREATE DATABASE IF NOT EXISTS energy_dashboard;
USE energy_dashboard;

-- Table to store dataset metadata
CREATE TABLE IF NOT EXISTS datasets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    filename VARCHAR(255) NOT NULL,
    upload_date DATETIME NOT NULL,
    record_count INT DEFAULT 0
);

-- Note: Individual dataset tables will be created dynamically as 'dataset_X' where X is the dataset ID