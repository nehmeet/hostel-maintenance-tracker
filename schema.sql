-- ============================================================
-- schema.sql — Hostel Maintenance & Complaint Tracker (v3)
-- Course: NCSM301 Web Development Laboratory
-- ============================================================

CREATE DATABASE IF NOT EXISTS hostel_complaints
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE hostel_complaints;

-- ----- students table -----
CREATE TABLE IF NOT EXISTS students (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    admission_no  VARCHAR(20)  NOT NULL UNIQUE,
    name          VARCHAR(100) NOT NULL,
    password      VARCHAR(255) NOT NULL,
    room_number   INT          NOT NULL,
    block         CHAR(1)      NOT NULL
) ENGINE=InnoDB;

-- ----- admins table -----
CREATE TABLE IF NOT EXISTS admins (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50)  NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- ----- complaints table -----
CREATE TABLE IF NOT EXISTS complaints (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    student_id     INT          NOT NULL,
    admission_no   VARCHAR(20)  NOT NULL,
    student_name   VARCHAR(100) NOT NULL,
    room_number    INT          NOT NULL,
    block          CHAR(1)      NOT NULL,
    category       ENUM('Electrical','Plumbing','Network','Housekeeping','Mess & Hygiene') NOT NULL,
    description    TEXT         NOT NULL,
    status         ENUM('Submitted','In Progress','Resolved','Cancelled') NOT NULL DEFAULT 'Submitted',
    assigned_staff VARCHAR(100) DEFAULT NULL,
    rating         TINYINT      DEFAULT NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----- notices table -----
CREATE TABLE IF NOT EXISTS notices (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(200) NOT NULL,
    message    TEXT         NOT NULL,
    posted_by  INT          NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Seed Data (Using INSERT IGNORE to allow idempotent imports)
-- ============================================================

-- Default admin (Username: admin, Password: admin123)
INSERT IGNORE INTO admins (id, username, password) VALUES
(1, 'admin', '$2y$12$kzqTrkt8PnFRRbGL5wdUUeYNGgqFeXdTz8KIopj4849n0Zbfbjx3.');

-- Sample students (Password: student123)
INSERT IGNORE INTO students (id, admission_no, name, password, room_number, block) VALUES
(1, '24JE0001', 'Rahul Sharma',  '$2y$12$YXytkrKzcDsud3DiFvQRmeWEP51ujuP/RzkCignCG0jHJp3.Fa6ZS', 101, 'A'),
(2, '24JE0002', 'Priya Verma',   '$2y$12$YXytkrKzcDsud3DiFvQRmeWEP51ujuP/RzkCignCG0jHJp3.Fa6ZS', 205, 'B'),
(3, '24JE0003', 'Arjun Mehta',   '$2y$12$YXytkrKzcDsud3DiFvQRmeWEP51ujuP/RzkCignCG0jHJp3.Fa6ZS', 310, 'C');

-- Sample notice
INSERT IGNORE INTO notices (id, title, message, posted_by, created_at) VALUES
(1, 'Water Supply Maintenance', 'Water supply will be interrupted in Block A tomorrow from 10:00 AM to 02:00 PM.', 1, NOW()),
(2, 'Wi-Fi Maintenance Notice', 'High-speed router firmware upgrade scheduled tonight from 12:00 AM to 02:00 AM across all blocks.', 1, NOW());

-- Sample complaints to demonstrate status flow, assigned staff, rating, and metrics (>48 hours, resolved today)
INSERT IGNORE INTO complaints (id, student_id, admission_no, student_name, room_number, block, category, description, status, assigned_staff, rating, created_at, updated_at) VALUES
(1, 1, '24JE0001', 'Rahul Sharma', 101, 'A', 'Electrical', 'Ceiling fan regulator sparking when set to speed 4.', 'Submitted', NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 1, '24JE0001', 'Rahul Sharma', 101, 'A', 'Plumbing', 'Bathroom tap leaking continuously.', 'In Progress', 'Rajesh - Plumber', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
(3, 1, '24JE0001', 'Rahul Sharma', 101, 'A', 'Network', 'LAN port in room 101 not establishing connection.', 'Resolved', 'Anil - Network Admin', 5, DATE_SUB(NOW(), INTERVAL 5 DAY), NOW()),
(4, 2, '24JE0002', 'Priya Verma', 205, 'B', 'Housekeeping', 'Dustbin in corridor on 2nd floor needs clearing.', 'Submitted', NULL, NULL, DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)),
(5, 3, '24JE0003', 'Arjun Mehta', 310, 'C', 'Mess & Hygiene', 'Water cooler filter in dining hall needs replacement.', 'In Progress', 'Ramesh - Mess Supervisor', NULL, DATE_SUB(NOW(), INTERVAL 6 HOUR), NOW());
