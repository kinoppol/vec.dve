-- DVE System — Database Schema
-- MariaDB 10+ / MySQL 8+
-- Encoding: utf8mb4_unicode_ci

CREATE DATABASE IF NOT EXISTS vec_dve CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vec_dve;

-- ── KPI (summary counters & charts) ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS kpi (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(50) NOT NULL UNIQUE,
  `value` BIGINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kpi_trend (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  year_be       INT NOT NULL UNIQUE,
  student_count INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kpi_province (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  province      VARCHAR(100) NOT NULL,
  short_name    VARCHAR(30),
  student_count INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kpi_field (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  field_name VARCHAR(100) NOT NULL,
  percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
  color      VARCHAR(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Colleges ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS colleges (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  code     VARCHAR(20) NOT NULL UNIQUE,
  name     VARCHAR(200) NOT NULL,
  province VARCHAR(100),
  type     VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Enterprises ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS enterprises (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  code         VARCHAR(20) NOT NULL UNIQUE,
  name         VARCHAR(200) NOT NULL,
  type         VARCHAR(100),
  province     VARCHAR(100),
  canonical_id INT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (canonical_id) REFERENCES enterprises(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS enterprise_aliases (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  enterprise_id INT NOT NULL,
  alias         VARCHAR(200) NOT NULL,
  FOREIGN KEY (enterprise_id) REFERENCES enterprises(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Users ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  role             ENUM('student','teacher','officer','soj','admin') NOT NULL,
  name             VARCHAR(200) NOT NULL,
  institution      VARCHAR(200),
  college_id       INT NULL,
  username         VARCHAR(100) NULL UNIQUE,
  password_hash    VARCHAR(255),
  student_code     VARCHAR(50),
  national_id_hash VARCHAR(255),
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Internship Requests ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS internship_requests (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  req_code        VARCHAR(20) NOT NULL UNIQUE,
  student_name    VARCHAR(200) NOT NULL,
  college_id      INT NULL,
  college_name    VARCHAR(200),
  department      VARCHAR(100),
  enterprise_id   INT NULL,
  enterprise_name VARCHAR(200) NOT NULL,
  type            ENUM('ฝึกงาน','ฝึกอาชีพ') NOT NULL DEFAULT 'ฝึกงาน',
  status          ENUM('รออนุมัติ','อนุมัติแล้ว','ปฏิเสธ','ออกหนังสือแล้ว','อยู่ระหว่างฝึก','เสร็จสิ้น') NOT NULL DEFAULT 'รออนุมัติ',
  date_submitted  DATE NOT NULL,
  period_start    DATE,
  period_end      DATE,
  period_display  VARCHAR(100),
  submitted_by    INT NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (college_id)    REFERENCES colleges(id)    ON DELETE SET NULL,
  FOREIGN KEY (enterprise_id) REFERENCES enterprises(id) ON DELETE SET NULL,
  FOREIGN KEY (submitted_by)  REFERENCES users(id)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PPP Estates ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ppp_estates (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  code          VARCHAR(20) NOT NULL UNIQUE,
  name          VARCHAR(200) NOT NULL,
  province      VARCHAR(100),
  soj_name      VARCHAR(200),
  company_count INT DEFAULT 0,
  hr_demand     INT DEFAULT 0,
  hr_filled     INT DEFAULT 0,
  status        ENUM('ดำเนินการแล้ว','กำลังดำเนินการ','รอดำเนินการ') DEFAULT 'รอดำเนินการ',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ppp_hr_demand (
  id                   INT AUTO_INCREMENT PRIMARY KEY,
  estate_id            INT NOT NULL,
  field_name           VARCHAR(100) NOT NULL,
  count_needed         INT DEFAULT 0,
  count_filled         INT DEFAULT 0,
  year_be              INT,
  special_requirements TEXT,
  created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (estate_id) REFERENCES ppp_estates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Supervision ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS supervision (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  code            VARCHAR(20),
  request_id      INT NULL,
  student_name    VARCHAR(200) NOT NULL,
  college_name    VARCHAR(200),
  enterprise_name VARCHAR(200),
  supervisor_name VARCHAR(200),
  next_visit_date DATE,
  visit_count     INT DEFAULT 0,
  status          ENUM('ปกติ','ต้องติดตาม','ขาดการติดต่อ') DEFAULT 'ปกติ',
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES internship_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS supervision_visits (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  supervision_id INT NOT NULL,
  visit_date     DATE NOT NULL,
  visit_number   INT DEFAULT 1,
  notes          TEXT,
  student_status ENUM('ปกติ','ต้องติดตาม','ขาดการติดต่อ') DEFAULT 'ปกติ',
  created_by     INT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (supervision_id) REFERENCES supervision(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by)     REFERENCES users(id)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Finance Allocations ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS finance_allocations (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  code         VARCHAR(20),
  college_name VARCHAR(200) NOT NULL,
  college_id   INT NULL,
  province     VARCHAR(100),
  usage_pct    INT DEFAULT 0,
  perf_pct     INT DEFAULT 0,
  zone         ENUM('ทั่วไป','EEC','ชายแดนใต้') DEFAULT 'ทั่วไป',
  amount       BIGINT DEFAULT 0,
  is_pending   TINYINT(1) DEFAULT 0,
  fiscal_year  INT DEFAULT 2567,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Duplicate Groups ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS duplicate_groups (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  code           VARCHAR(20),
  canonical_name VARCHAR(200) NOT NULL,
  resolved       TINYINT(1) DEFAULT 0,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS duplicate_group_members (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  group_id         INT NOT NULL,
  enterprise_id    INT NULL,
  enterprise_name  VARCHAR(200) NOT NULL,
  usage_count      INT DEFAULT 0,
  similarity_score INT DEFAULT 100,
  is_canonical     TINYINT(1) DEFAULT 0,
  FOREIGN KEY (group_id)      REFERENCES duplicate_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (enterprise_id) REFERENCES enterprises(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Notifications ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NULL,
  `text`     VARCHAR(500) NOT NULL,
  is_read    TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
