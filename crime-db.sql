-- Crime Mapping Database Schema (La Trinidad)
-- Use with MySQL/MariaDB via phpMyAdmin or CLI.

CREATE DATABASE IF NOT EXISTS crime_mapping;
USE crime_mapping;

-- -----------------------------
-- Reference Tables
-- -----------------------------
CREATE TABLE barangays (
    barangay_id INT AUTO_INCREMENT PRIMARY KEY,
    barangay_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE crime_types (
    crime_type_id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM(
        'violent',
        'property',
        'white_collar',
        'drug',
        'cybercrime',
        'public_order',
        'traffic',
        'status_offense'
    ) NOT NULL,
    type_name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_crime_type (category, type_name)
);

-- -----------------------------
-- Users and Roles
-- -----------------------------
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    contact VARCHAR(20) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'barangay', 'registered') NOT NULL,
    barangay_id INT NULL,
    status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (barangay_id) REFERENCES barangays(barangay_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- -----------------------------
-- Incidents (Reports)
-- -----------------------------
CREATE TABLE incidents (
    incident_id INT AUTO_INCREMENT PRIMARY KEY,
    crime_type_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    barangay_id INT NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    occurred_at DATETIME NOT NULL,
    severity ENUM('low', 'medium', 'high') NOT NULL,
    status ENUM('pending', 'under_investigation', 'action_taken', 'resolved', 'dismissed')
        NOT NULL DEFAULT 'pending',
    source ENUM('reported', 'verified', 'imported') NOT NULL DEFAULT 'reported',
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    reported_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (crime_type_id) REFERENCES crime_types(crime_type_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    FOREIGN KEY (barangay_id) REFERENCES barangays(barangay_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(user_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE incident_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (incident_id) REFERENCES incidents(incident_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE incident_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    remarks TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (incident_id) REFERENCES incidents(incident_id)
        ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- Community validation (thumbs up/down)
CREATE TABLE incident_validations (
    validation_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    user_id INT NULL,
    guest_token VARCHAR(64) NULL,
    reaction ENUM('up', 'down') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (incident_id) REFERENCES incidents(incident_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    UNIQUE KEY uq_validation_user (incident_id, user_id),
    UNIQUE KEY uq_validation_guest (incident_id, guest_token)
);

-- -----------------------------
-- Notifications
-- -----------------------------
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    barangay_id INT NULL,
    incident_id INT NULL,
    notification_type ENUM('new_report', 'status_update', 'high_severity', 'mention') NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (barangay_id) REFERENCES barangays(barangay_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    FOREIGN KEY (incident_id) REFERENCES incidents(incident_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- -----------------------------
-- Indexes (Performance)
-- -----------------------------
CREATE INDEX idx_incident_barangay ON incidents(barangay_id);
CREATE INDEX idx_incident_status ON incidents(status);
CREATE INDEX idx_incident_severity ON incidents(severity);
CREATE INDEX idx_incident_date ON incidents(occurred_at);
CREATE INDEX idx_incident_location ON incidents(latitude, longitude);
CREATE INDEX idx_validation_incident ON incident_validations(incident_id);

-- -----------------------------
-- Seed Data (Barangays)
-- -----------------------------
INSERT INTO barangays (barangay_name) VALUES
('Alapang'),
('Alno'),
('Ambiong'),
('Bahong'),
('Balili'),
('Beckel'),
('Betag'),
('Bineng'),
('Cruz'),
('Lubas'),
('Pico'),
('Poblacion'),
('Puguis'),
('Shilan'),
('Tawang'),
('Wangal');

-- Seed crime type categories (sample list, adjust as needed)
INSERT INTO crime_types (category, type_name) VALUES
('violent', 'Assault'),
('violent', 'Homicide'),
('property', 'Theft'),
('property', 'Robbery'),
('white_collar', 'Fraud'),
('drug', 'Drug Possession'),
('cybercrime', 'Online Scam'),
('public_order', 'Vandalism'),
('traffic', 'Reckless Driving'),
('status_offense', 'Curfew Violation');

-- Seed admin account (replace with hashed password in real setup)
INSERT INTO users (username, email, password_hash, role)
VALUES ('admin', 'admin@crime.local', 'admin123', 'admin');

-- Seed barangay accounts
INSERT INTO users (username, email, contact, password_hash, role, barangay_id)
VALUES
('brgy_alapang', 'alapang@crime.local', '+639000000001', 'barangay123', 'barangay', 1),
('brgy_alno', 'alno@crime.local', '+639000000002', 'barangay123', 'barangay', 2),
('brgy_ambiong', 'ambiong@crime.local', '+639000000003', 'barangay123', 'barangay', 3),
('brgy_bahong', 'bahong@crime.local', '+639000000004', 'barangay123', 'barangay', 4),
('brgy_balili', 'balili@crime.local', '+639000000005', 'barangay123', 'barangay', 5),
('brgy_beckel', 'beckel@crime.local', '+639000000006', 'barangay123', 'barangay', 6),
('brgy_betag', 'betag@crime.local', '+639000000007', 'barangay123', 'barangay', 7),
('brgy_bineng', 'bineng@crime.local', '+639000000008', 'barangay123', 'barangay', 8),
('brgy_cruz', 'cruz@crime.local', '+639000000009', 'barangay123', 'barangay', 9),
('brgy_lubas', 'lubas@crime.local', '+639000000010', 'barangay123', 'barangay', 10),
('brgy_pico', 'pico@crime.local', '+639000000011', 'barangay123', 'barangay', 11),
('brgy_poblacion', 'poblacion@crime.local', '+639000000012', 'barangay123', 'barangay', 12),
('brgy_puguis', 'puguis@crime.local', '+639000000013', 'barangay123', 'barangay', 13),
('brgy_shilan', 'shilan@crime.local', '+639000000014', 'barangay123', 'barangay', 14),
('brgy_tawang', 'tawang@crime.local', '+639000000015', 'barangay123', 'barangay', 15),
('brgy_wangal', 'wangal@crime.local', '+639000000016', 'barangay123', 'barangay', 16);
