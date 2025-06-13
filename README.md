# Capstone Report Management System

A PHP and MySQL-based web application for managing capstone project reports with three-tier authentication system for students, schools, and administrators.

## Features

- **Three-Tier Authentication System**
  - Students: Submit and manage capstone reports
  - Schools: Review reports from their students
  - Administrators: System-wide oversight and management

- **Report Management**
  - File upload support for various document formats
  - Status tracking (submitted, under review, approved, rejected, revision required)
  - Comments and grading system
  - Search and filtering capabilities

- **Student Registration**
  - School selection during registration
  - Student ID photo upload requirement
  - Comprehensive profile management

- **Analytics Dashboard**
  - School-specific report analytics
  - Student submission tracking
  - Report status distribution charts
  - Monthly submission trends
  - Print and download functionality for reports

- **User Management**
  - Add/edit/delete users and schools
  - Role-based access control
  - Activity logging

## System Requirements

- **Web Server**: Apache/Nginx with PHP 7.4 or higher
- **Database**: MySQL 5.7 or higher / MariaDB 10.3 or higher
- **PHP Extensions**: PDO, PDO_MySQL, GD, mbstring, fileinfo
- **Storage**: Minimum 100MB free space for file uploads

## Installation Instructions

### 1. Download and Extract Files
- Download all project files
- Extract to your web server directory (e.g., `htdocs`, `www`, `public_html`)

### 2. Database Setup
1. Open phpMyAdmin in your web browser
2. Create a new database named `capstone_system`
3. Import the SQL file:
   - Click on the `capstone_system` database
   - Go to the "Import" tab
   - Choose file: `database/schema.sql`
   - Click "Go" to import

### 3. Configuration
1. Open `config/database.php`
2. Update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USERNAME', 'root');        // Your MySQL username
   define('DB_PASSWORD', '');            // Your MySQL password
   define('DB_NAME', 'capstone_system');
   ```

### 4. File Permissions
Ensure the `uploads` folder has write permissions:
- On Linux/Mac: `chmod 755 uploads`
- On Windows: Right-click folder → Properties → Security → Allow write access

### 5. Access the System
Open your web browser and navigate to your project URL (e.g., `http://localhost/capstone_system`)

## Default Login Credentials

### Administrator
- **Username**: `admin`
- **Password**: `password123`

### Sample School Accounts
- **Central High School**: Email: `admin@centralhs.edu`, Password: `password123`
- **Northern Technical College**: Email: `contact@northerntech.edu`, Password: `password123`
- **Eastern University**: Email: `info@easternuni.edu`, Password: `password123`
- **Southern Academy**: Email: `admin@southernacad.edu`, Password: `password123`
- **Western Institute**: Email: `contact@westerninst.edu`, Password: `password123`
- **Maritime College**: Email: `info@maritimecoll.edu`, Password: `password123`

### Sample Student Accounts
- **Student 1**: Username: `student1`, Password: `password123`
- **Student 2**: Username: `student2`, Password: `password123`
- **Student 3**: Username: `student3`, Password: `password123`
- **Student 4**: Username: `student4`, Password: `password123`
- **Student 5**: Username: `student5`, Password: `password123`

## File Structure

```
capstone_system/
├── admin/              # Administrator dashboard and management
├── assets/             # CSS, JavaScript, and image files
├── auth/              # Login and authentication files
├── config/            # Database and system configuration
├── database/          # SQL schema and sample data
├── includes/          # Common PHP functions and headers
├── school/            # School dashboard and features
├── student/           # Student dashboard and features
├── uploads/           # File upload directory
└── index.php          # Main homepage
```

## Usage Guide

### For Students
1. Register by selecting your school and uploading student ID photo
2. Log in with your credentials
3. Submit capstone reports with required information and files
4. Track report status and view feedback from schools
5. Download approved reports and certificates

### For Schools
1. Log in with school credentials
2. View reports submitted by your students
3. Access detailed analytics about student submissions
4. Add comments and provide feedback on reports
5. Change report status and assign grades
6. Print and download analytics reports for records

### For Administrators
1. Log in with admin credentials
2. Manage all users (students, schools, admins)
3. Oversee all reports system-wide
4. Add new schools and configure system settings
5. View system analytics and activity logs

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection protection with prepared statements
- File upload validation and restrictions
- Session-based authentication
- Role-based access control
- Activity logging for audit trails

## Troubleshooting

### Database Connection Issues
- Verify database credentials in `config/database.php`
- Ensure MySQL service is running
- Check database name spelling

### File Upload Problems
- Verify `uploads` folder permissions
- Check PHP upload limits in `php.ini`
- Ensure sufficient disk space

### Login Issues
- Clear browser cookies/cache
- Verify user exists in database
- Check password (default: `password123`)

## Support

For technical support or questions about the system, please refer to the documentation or contact the system administrator.

## License

This project is developed for educational purposes as part of a capstone project management system.