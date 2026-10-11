CREATE DATABASE IF NOT EXISTS unibridge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE unibridge;

CREATE TABLE student_login (
  student_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admission_no VARCHAR(40) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  last_login DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE student_registration (
  registration_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL UNIQUE,
  full_name VARCHAR(120) NOT NULL,
  course VARCHAR(120) NOT NULL,
  phone VARCHAR(25) NOT NULL,
  admission_year SMALLINT UNSIGNED NOT NULL,
  date_of_birth DATE NULL,
  gender VARCHAR(30) NULL,
  address TEXT NULL,
  city VARCHAR(80) NULL,
  state VARCHAR(80) NULL,
  postal_code VARCHAR(20) NULL,
  guardian_name VARCHAR(120) NULL,
  guardian_phone VARCHAR(25) NULL,
  profile_photo VARCHAR(255) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_registration_student FOREIGN KEY (student_id) REFERENCES student_login(student_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE document_track (
  document_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  document_type ENUM('photo','identity','marksheet','transfer_certificate') NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  review_note VARCHAR(255) NULL,
  uploaded_at DATETIME NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_student_document (student_id,document_type),
  CONSTRAINT fk_document_student FOREIGN KEY (student_id) REFERENCES student_login(student_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  email VARCHAR(120) PRIMARY KEY,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE activity_log (
  activity_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  action VARCHAR(120) NOT NULL,
  detail VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_activity_student FOREIGN KEY (student_id) REFERENCES student_login(student_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Create your own student account through signup.php. Passwords are intentionally never stored in plain text.
