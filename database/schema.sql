-- Capstone Report Management System Database Schema for MySQL
-- Import this file into phpMyAdmin or run in MySQL command line

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
    id_photo_path VARCHAR(500),
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

-- Insert sample schools
INSERT INTO schools (name, code, email, password, contact_person, phone, address) VALUES
('Central High School', 'CHS', 'admin@centralhs.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Maria Santos', '(032) 123-4567', '123 Main Street, Daanbantayan, Cebu'),
('Northern Technical College', 'NTC', 'contact@northerntech.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Prof. Juan dela Cruz', '(032) 234-5678', '456 Education Ave, Bantayan Island'),
('Eastern University', 'EU', 'info@easternuni.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Carmen Reyes', '(032) 345-6789', '789 University Blvd, Madridejos'),
('Southern Academy', 'SA', 'admin@southernacad.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ms. Rosa Garcia', '(032) 456-7890', '321 Academy St, Santa Fe'),
('Western Institute', 'WI', 'contact@westerninst.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Pedro Martinez', '(032) 567-8901', '654 Institute Rd, Tabogon'),
('Maritime College', 'MC', 'info@maritimecoll.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Capt. Ana Villanueva', '(032) 678-9012', '987 Port Ave, Bogo City');

-- Insert sample admin user
-- Default password: "password123"
INSERT INTO users (username, email, password, first_name, last_name, role) VALUES
('admin', 'admin@capstone.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin');

-- Insert sample students
-- Default password: "password123"
INSERT INTO users (username, email, password, first_name, last_name, role, student_id, school_id) VALUES
('student1', 'john.doe@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 'student', 'STU2024001', 1),
('student2', 'jane.smith@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', 'student', 'STU2024002', 2),
('student3', 'mike.johnson@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike', 'Johnson', 'student', 'STU2024003', 1),
('student4', 'sarah.wilson@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Wilson', 'student', 'STU2024004', 3),
('student5', 'david.brown@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David', 'Brown', 'student', 'STU2024005', 4);

-- Insert sample reports
INSERT INTO reports (title, description, student_id, school_id, file_name, status, submission_date) VALUES
('AI Implementation in Healthcare', 'A comprehensive study on artificial intelligence applications in modern healthcare systems, focusing on diagnostic accuracy and patient outcomes.', 1, 1, 'ai_healthcare_report.pdf', 'approved', '2024-01-15 10:30:00'),
('Sustainable Energy Solutions', 'Research on renewable energy integration in rural communities, examining solar and wind power feasibility.', 2, 2, 'sustainable_energy.pdf', 'under_review', '2024-02-20 14:15:00'),
('Digital Marketing Strategies', 'Analysis of social media marketing effectiveness for small businesses in the digital age.', 3, 1, 'digital_marketing.pdf', 'submitted', '2024-03-10 09:45:00'),
('Cybersecurity Best Practices', 'Investigation of current cybersecurity threats and recommended protection strategies for organizations.', 4, 3, 'cybersecurity_study.pdf', 'revision_required', '2024-02-28 16:20:00'),
('Environmental Impact Assessment', 'Study on the environmental effects of industrial development in coastal areas.', 5, 4, 'environmental_impact.pdf', 'approved', '2024-01-25 11:10:00');
