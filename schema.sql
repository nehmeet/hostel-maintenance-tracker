-- ============================================================
-- schema.sql — Hostel Maintenance & Complaint Tracker (v2)
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
    block         CHAR(1) NOT NULL
) ENGINE=InnoDB;

-- ----- admins table -----
CREATE TABLE IF NOT EXISTS admins (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50)  NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- ----- complaints table -----
CREATE TABLE IF NOT EXISTS complaints (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    student_id    INT          NOT NULL,
    admission_no  VARCHAR(20)  NOT NULL,
    student_name  VARCHAR(100) NOT NULL,
    room_number   INT          NOT NULL,
    block         CHAR(1) NOT NULL,
    category      ENUM('Electrical','Plumbing','Network','Housekeeping','Mess & Hygiene') NOT NULL,
    description   TEXT         NOT NULL,
    status        ENUM('Submitted','In Progress','Resolved','Cancelled') NOT NULL DEFAULT 'Submitted',
    rating        TINYINT      DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
) ENGINE=InnoDB;

-- ----- notices table -----
CREATE TABLE IF NOT EXISTS notices (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(200) NOT NULL,
    message    TEXT         NOT NULL,
    posted_by  INT          NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES admins(id)
) ENGINE=InnoDB;

-- ============================================================
-- Seed Data
-- ============================================================

-- Default admin (password: admin123)
INSERT INTO admins (username, password) VALUES
('admin', '$2y$12$kzqTrkt8PnFRRbGL5wdUUeYNGgqFeXdTz8KIopj4849n0Zbfbjx3.');

-- Sample students (password: student123)
INSERT INTO students (admission_no, name, password, room_number, block) VALUES
('24JE0001', 'Rahul Sharma',  '$2y$12$YXytkrKzcDsud3DiFvQRmeWEP51ujuP/RzkCignCG0jHJp3.Fa6ZS', 101, 'A'),
('24JE0002', 'Priya Verma',   '$2y$12$YXytkrKzcDsud3DiFvQRmeWEP51ujuP/RzkCignCG0jHJp3.Fa6ZS', 205, 'B'),
('24JE0003', 'Arjun Mehta',   '$2y$12$YXytkrKzcDsud3DiFvQRmeWEP51ujuP/RzkCignCG0jHJp3.Fa6ZS', 310, 'C');

-- Sample notice
INSERT INTO notices (title, message, posted_by) VALUES
('Water Supply Maintenance', 'Water supply will be interrupted in Jasper block on 28-Oct from 10 AM to 2 PM.', 1);
