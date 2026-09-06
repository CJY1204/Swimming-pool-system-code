-- =====================================================================
-- SplashPoint Swimming Pool Booking System — Database Schema
-- Import into WAMPP (phpMyAdmin -> Import, or mysql CLI):
--   mysql -u root -p < schema.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS pool_booking;
USE pool_booking;

-- ---------------------------------------------------------------------
-- members: public customers who register to make bookings
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS members (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(50)  NOT NULL UNIQUE,   -- used to log in
    full_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(120) NOT NULL UNIQUE,   -- for verification / password reset, NOT for login
    phone          VARCHAR(20)  NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- admins: staff accounts for the admin dashboard (separate login/table
-- from members on purpose, so staff and customer access stay isolated)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    admin_id       VARCHAR(50) NOT NULL UNIQUE,   -- the "staff ID" used to log in
    display_name   VARCHAR(100) NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- pools: the 3 physical pools (CRUD target for admin)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pools (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(50) NOT NULL UNIQUE,      -- used for image filename matching, e.g. deep-blue
    name         VARCHAR(100) NOT NULL,
    tagline      VARCHAR(150) NOT NULL,
    description  TEXT NOT NULL,
    depth_label  VARCHAR(50) NOT NULL,
    capacity     INT NOT NULL DEFAULT 20,
    size         ENUM('big','small') NOT NULL DEFAULT 'small',
    accent       ENUM('deep','coral','tide') NOT NULL DEFAULT 'deep',
    photo        VARCHAR(150) NOT NULL DEFAULT '',
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- pool_schedules: recurring daily time slots for each pool
-- (e.g. Deep Blue always has 07:00-12:00 and 14:00-19:00 every day)
-- Actual bookable "sessions" rows are generated from these each day.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pool_schedules (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    pool_id            INT NOT NULL,
    start_time         TIME NOT NULL,
    end_time           TIME NOT NULL,
    capacity_override  INT NULL,   -- NULL = use the pool's max capacity
    is_active          TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_schedule_pool FOREIGN KEY (pool_id) REFERENCES pools(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- sessions: one bookable row per pool + date + time slot.
-- New rows are generated automatically (see includes/session_generator.php)
-- whenever a new day appears — so every day starts back at full capacity,
-- and only real bookings ever reduce `booked`.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    pool_id       INT NOT NULL,
    schedule_id   INT NULL,
    session_date  DATE NOT NULL,
    start_time    TIME NOT NULL,
    end_time      TIME NOT NULL,
    capacity      INT NOT NULL,
    booked        INT NOT NULL DEFAULT 0,
    status        ENUM('active','closed') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_pool_date_time (pool_id, session_date, start_time),
    CONSTRAINT fk_session_pool FOREIGN KEY (pool_id) REFERENCES pools(id) ON DELETE CASCADE,
    CONSTRAINT fk_session_schedule FOREIGN KEY (schedule_id) REFERENCES pool_schedules(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- bookings: a member's reservation against a session.
-- Booking always requires a logged-in member (member_id is NOT NULL).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    booking_reference  VARCHAR(20) NOT NULL UNIQUE,
    session_id         INT NOT NULL,
    member_id          INT NOT NULL,
    quantity           INT NOT NULL,
    booking_status     ENUM('confirmed','cancelled') NOT NULL DEFAULT 'confirmed',
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_session FOREIGN KEY (session_id) REFERENCES sessions(id),
    CONSTRAINT fk_booking_member FOREIGN KEY (member_id) REFERENCES members(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- sessions_store: backs PHP's own login sessions (member_id / admin_id),
-- stored in MySQL instead of each EC2 instance's local disk. This is
-- what makes login state work correctly once the app sits behind a
-- Load Balancer with multiple EC2 instances — see
-- includes/db_session_handler.php for how it's used.
-- Not related to the `sessions` table above (that one is pool bookings).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions_store (
    id          VARCHAR(128) NOT NULL PRIMARY KEY,
    data        MEDIUMTEXT NOT NULL,
    expires_at  DATETIME NOT NULL,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB;

-- =====================================================================
-- Seed data: 3 pools + their recurring daily schedules
-- (Session rows are NOT seeded here — they are generated automatically
--  the first time index.php or admin/dashboard.php runs each day.)
-- =====================================================================

INSERT INTO pools (slug, name, tagline, description, depth_label, capacity, size, accent, photo) VALUES
('deep-blue', 'The Deep Blue', '50M · OLYMPIC LANE POOL',
 'Our full-length competition pool with 8 marked lanes. Ideal for serious lap swimmers, squad training, and anyone who wants real distance.',
 '1.2m – 2.0m', 40, 'big', 'deep', 'deep-blue.jpg'),
('coral-cove', 'Coral Cove', 'HEATED · FAMILY LEISURE POOL',
 'A warm, shallow pool built for families and casual swimmers. Gentle steps, no strong currents, and plenty of room to relax.',
 '0.5m – 1.0m', 20, 'small', 'coral', 'coral-cove.jpg'),
('tide-pool', 'Tide Pool', 'QUIET · TRAINING POOL',
 'A compact 4-lane pool for focused technique work, rehab swimming, or anyone who prefers a quieter session away from the crowds.',
 '1.0m – 1.4m', 20, 'small', 'tide', 'tide-pool.jpg');

-- Deep Blue: 7:00 AM–12:00 PM and 2:00 PM–7:00 PM, full pool capacity each slot
INSERT INTO pool_schedules (pool_id, start_time, end_time)
SELECT id, '07:00:00', '12:00:00' FROM pools WHERE slug = 'deep-blue';
INSERT INTO pool_schedules (pool_id, start_time, end_time)
SELECT id, '14:00:00', '19:00:00' FROM pools WHERE slug = 'deep-blue';

-- Coral Cove: 8:00 AM–12:00 PM and 2:00 PM–6:00 PM
INSERT INTO pool_schedules (pool_id, start_time, end_time)
SELECT id, '08:00:00', '12:00:00' FROM pools WHERE slug = 'coral-cove';
INSERT INTO pool_schedules (pool_id, start_time, end_time)
SELECT id, '14:00:00', '18:00:00' FROM pools WHERE slug = 'coral-cove';

-- Tide Pool: 8:00 AM–12:00 PM and 2:00 PM–6:00 PM
INSERT INTO pool_schedules (pool_id, start_time, end_time)
SELECT id, '08:00:00', '12:00:00' FROM pools WHERE slug = 'tide-pool';
INSERT INTO pool_schedules (pool_id, start_time, end_time)
SELECT id, '14:00:00', '18:00:00' FROM pools WHERE slug = 'tide-pool';


INSERT INTO `admins` (`id`, `admin_id`, `display_name`, `password_hash`, `created_at`) VALUES
(1, 'admin01', 'CJY', '$2y$10$A3McIEijmpOMD5zXX4JHFONpeUKuLavLHoHILe4y1ISjcooDJYjjy', '2026-07-12 15:54:37');

INSERT INTO `members` (`id`, `username`, `full_name`, `email`, `phone`, `password_hash`, `created_at`) VALUES
(6, 'member01', 'CJY', 'cjy@example.com', '011-28003667', '$2y$10$.3cXzm.z4UuvJtFUhorPkuiCF23Qx386Vz4AdBbT0kLa0gfvmDmqO', '2026-07-13 10:03:05');
