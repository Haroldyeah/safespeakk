-- Capstone Report Management System Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS capstone_system;
USE capstone_system;

-- Schools table
CREATE TABLE schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table (Students and Admins)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'admin') NOT NULL,
    student_id VARCHAR(50),
    school_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL
);

-- Reports table
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    student_id INT NOT NULL,
    school_id INT NOT NULL,
    file_path VARCHAR(500),
    file_name VARCHAR(255),
    file_size INT,
    status ENUM('submitted', 'under_review', 'approved', 'rejected', 'revision_required') DEFAULT 'submitted',
    admin_comments TEXT,
    school_comments TEXT,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by_admin INT,
    reviewed_by_school BOOLEAN DEFAULT FALSE,
    grade VARCHAR(10),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by_admin) REFERENCES users(id) ON DELETE SET NULL
);

-- System logs table
CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_type ENUM('student', 'school', 'admin'),
    action VARCHAR(255) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default schools
INSERT INTO schools (name, code, email, password, contact_person, phone, address) VALUES
('Central High School', 'CHS', 'admin@centralhs.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Maria Santos', '(032) 123-4567', '123 Main Street, Daanbantayan, Cebu'),
('St. Mary Academy', 'SMA', 'admin@stmary.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sr. Carmen dela Cruz', '(032) 234-5678', '456 Church Street, Daanbantayan, Cebu'),
('Poblacion National High School', 'PNHS', 'admin@poblacionhs.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mr. Roberto Garcia', '(032) 345-6789', '789 School Avenue, Daanbantayan, Cebu'),
('Holy Cross Institute', 'HCI', 'admin@holycross.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fr. Antonio Reyes', '(032) 456-7890', '321 Cross Road, Daanbantayan, Cebu'),
('Daanbantayan Science High School', 'DSHS', 'admin@sciencehs.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Lisa Fernandez', '(032) 567-8901', '654 Science Park, Daanbantayan, Cebu'),
('Integrated Developmental School', 'IDS', 'admin@integrateddev.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ms. Elena Morales', '(032) 678-9012', '987 Development Lane, Daanbantayan, Cebu');

-- Insert default admin user
INSERT INTO users (username, email, password, first_name, last_name, role) VALUES
('admin', 'admin@capstone-system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin');
