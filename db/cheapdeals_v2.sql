-- ============================================================
--  CheapDeals.com v2 — database schema + seed data
--  NEW, separate database: `cheapdeals_v2` (does NOT touch the old `cheapdeals`).
--  Import this file in phpMyAdmin (see HUONG-DAN.md).
--  The admin / staff / demo-user accounts are created automatically
--  by api/api.php on first run, so their passwords are safely hashed.
-- ============================================================

CREATE DATABASE IF NOT EXISTS cheapdeals_v2
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cheapdeals_v2;

-- drop in dependency order so re-importing is clean
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS packages;
DROP TABLE IF EXISTS users;

-- ---------- users (admin / staff / user) ----------
CREATE TABLE users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(120) NOT NULL,
  email      VARCHAR(160) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,            -- hashed by PHP password_hash()
  role       ENUM('admin','staff','user') NOT NULL DEFAULT 'user',
  address    VARCHAR(255) DEFAULT '',
  phone      VARCHAR(40)  DEFAULT '',
  card       VARCHAR(40)  DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------- packages (staff can add / edit / remove) ----------
CREATE TABLE packages (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  code     VARCHAR(40)  NOT NULL UNIQUE,
  name     VARCHAR(120) NOT NULL,
  category VARCHAR(40)  NOT NULL,              -- Mobile / Broadband / Tablet / Bundles
  tier     VARCHAR(40)  NOT NULL,              -- Lite / Standard / Premium / Deal
  price    DECIMAL(8,2) NOT NULL,
  features TEXT NOT NULL,                       -- JSON array of strings
  active   TINYINT(1) NOT NULL DEFAULT 1
);

-- ---------- orders ----------
CREATE TABLE orders (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  package_name VARCHAR(120) NOT NULL,
  total        DECIMAL(8,2) NOT NULL,
  saved        DECIMAL(8,2) NOT NULL DEFAULT 0,
  status       VARCHAR(30)  NOT NULL DEFAULT 'Paid',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------- feedback (chat between user and staff) ----------
CREATE TABLE feedback (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  sender     ENUM('user','staff') NOT NULL,
  message    TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------- seed packages (10 packages across 4 segments) ----------
INSERT INTO packages (code,name,category,tier,price,features) VALUES
('mob-lite','Mobile Lite','Mobile','Lite',12.00,'["1 mobile phone","100 free minutes","Unlimited SMS","3 GB data"]'),
('mobile','Mobile Standard','Mobile','Standard',20.00,'["1 mobile phone","500 free minutes","Unlimited SMS","10 GB data"]'),
('mob-pro','Mobile Premium','Mobile','Premium',30.00,'["1 flagship phone","Unlimited minutes","Unlimited SMS","50 GB data"]'),
('bb-lite','Broadband Lite','Broadband','Lite',18.00,'["1 Wi-Fi router","Home broadband","40 GB data"]'),
('broadband','Broadband Standard','Broadband','Standard',25.00,'["1 Wi-Fi router","Unlimited broadband","100 GB data"]'),
('bb-pro','Broadband Premium','Broadband','Premium',35.00,'["1 fibre router","Unlimited fibre","500 Mbps speed"]'),
('tab-lite','Tablet Lite','Tablet','Lite',15.00,'["1 tablet device","100 free minutes","8 GB data"]'),
('tablet','Tablet Standard','Tablet','Standard',22.00,'["1 tablet device","200 free minutes","20 GB data"]'),
('double','Double Bundle','Bundles','Deal',40.00,'["Any TWO packages","Shared data allowance","One single bill"]'),
('triple','Triple Bundle','Bundles','Deal',55.00,'["All THREE packages","Best value bundle","Priority support"]');
