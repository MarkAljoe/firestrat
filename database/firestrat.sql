-- ============================================================
-- FireStrat: Hazard Mapping System for BFP Virac, Catanduanes
-- Database schema + seed data
-- Import this file in phpMyAdmin, or run:
--   mysql -u root -p < firestrat.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS firestrat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE firestrat;

-- ---------- USERS (admin / fire marshal accounts) ----------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,      -- bcrypt hash via PHP password_hash()
    full_name VARCHAR(100) NOT NULL,
    role ENUM('fire_marshal','admin','staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- BARANGAYS (GIS reference points) ----------
CREATE TABLE IF NOT EXISTS barangays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    lat DECIMAL(10,7) NOT NULL,
    lng DECIMAL(10,7) NOT NULL,
    risk_level ENUM('high','moderate','low') NOT NULL DEFAULT 'low',
    population INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- HYDRANTS ----------
CREATE TABLE IF NOT EXISTS hydrants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    barangay_id INT NOT NULL,
    lat DECIMAL(10,7) NOT NULL,
    lng DECIMAL(10,7) NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    last_checked DATE DEFAULT NULL,
    FOREIGN KEY (barangay_id) REFERENCES barangays(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- INCIDENTS ----------
CREATE TABLE IF NOT EXISTS incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barangay_id INT NOT NULL,
    risk_level ENUM('high','moderate','low') NOT NULL,
    notes TEXT,
    reported_by ENUM('admin','community') NOT NULL DEFAULT 'admin',
    reporter_name VARCHAR(100) DEFAULT NULL,
    reporter_contact VARCHAR(50) DEFAULT NULL,
    status ENUM('active','contained','resolved') NOT NULL DEFAULT 'active',
    lat DECIMAL(10,7) DEFAULT NULL,
    lng DECIMAL(10,7) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (barangay_id) REFERENCES barangays(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- Coordinates are approximate, centered on Virac town proper
-- (13.58 N, 124.23 E). Replace with surveyed GPS points from
-- BFP / the LGU's GIS office before production use.
-- ============================================================

INSERT INTO barangays (name, lat, lng, risk_level, population) VALUES
('Brgy. San Juan (Pob.)',   13.5875, 124.2298, 'high',     2150),
('Brgy. Salvacion',         13.5806, 124.2320, 'high',     1680),
('Brgy. Concepcion',        13.5921, 124.2213, 'moderate', 1340),


INSERT INTO hydrants (code, barangay_id, lat, lng, status, last_checked) VALUES
('H-012', 1, 13.5882, 124.2306, 'active',   '2026-07-01'),
('H-007', 3, 13.5915, 124.2222, 'active',   '2026-07-01'),
('H-019', 2, 13.5817, 124.2402, 'inactive', '2026-06-15');

INSERT INTO incidents (barangay_id, risk_level, notes, reported_by, status, created_at) VALUES
(1, 'high',     'Reported kitchen fire, contained by responders.', 'admin', 'contained', '2026-08-15 09:42:00'),
(2, 'moderate', 'Electrical short circuit reported near the market.', 'admin', 'active', '2026-08-14 15:15:00'),
(3, 'high',     'Grass fire near a residential cluster.', 'community', 'resolved', '2026-05-18 11:00:00'),
(4, 'low',      'Smoke reported by a resident — confirmed false alarm.', 'community', 'resolved', '2026-05-17 07:30:00');

-- Default admin login: username "fmarshal", password "firestrat123"
-- Hash generated with PHP's password_hash('firestrat123', PASSWORD_DEFAULT)
INSERT INTO users (username, password, full_name, role) VALUES
('fmarshal', '$2y$10$h98poMWj5wry3mf0MC/krOGj8ZEmrMQhMhjnNN0Vj0a/P9f5Q6z26', 'Fire Marshal', 'fire_marshal');
