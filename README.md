 SafeSpeakk

A web‑based bullying & violence reporting and support system for high schools in Poblacion, Daanbantayan, Cebu.

**Published version:** [https://safespeak.page.gd/](https://safespeak.page.gd/)

---

## 🚀 Project Overview

SafeSpeakk is designed to help students, school administrators and system administrators by providing:

* A secure reporting platform where students can submit incidents (bullying, violence) along with evidence uploads.
* Role‑based access: students submit reports, school admins review/manage their school’s reports, system admin oversees everything.
* Report status tracking (submitted → under review → resolved/escalated).
* A built‑in support chatbot flow (via Tiledesk) for students who need mental health support or guidance.
* Built with PHP + MySQL (no React) for simplicity and compatibility.

---

## 🧭 Key Features

### For Students

* Register with basic info (name, school, etc).
* Log in and submit a complaint/report with details and file uploads (evidence).
* View status of their submitted reports.
* Access the chatbot for supportive conversations if they are being bullied or need help.

### For School Administrators

* Login to view all reports submitted by students at their school.
* Review evidence, add comments or updates, change the report status (e.g., Under Review, Resolved, Escalated).
* View analytics/dashboard for their school (e.g., number of reports, types, resolution time).

### For System Administrators

* Full oversight of all schools, students and reports.
* Manage user roles, add new schools, configure system settings.
* View aggregated analytics across all schools (incident trends, hotspots).
* Configure overall chatbot flow and support resources.

---

## 🏗 Architecture & Tech Stack

* **Backend**: PHP (server‑side logic)
* **Database**: MySQL for storing users, schools, reports, evidence, chat logs etc.
* **Frontend**: Standard PHP/HTML views, no React framework.
* **Uploads**: Evidence file uploads handled in a secure directory; validation of file type/size.
* **Authentication & Authorization**: Role‑based login sessions; students, school admins, system admin.
* **Chatbot Integration**: Tiledesk or similar widget embedded for student support flows.

---

## 🛠 Installation & Setup

### Prerequisites

* PHP (version 7.x or higher)
* MySQL (or MariaDB)
* Web server (Apache, Nginx) or WAMP/XAMPP environment
* (Optional) Tiledesk account for chatbot integration

### Steps

1. Clone the repository:

   ```bash
   git clone https://github.com/Haroldyeah/safespeakk.git  
   cd safespeakk  
   ```
2. Configure the database:

   * Create a new MySQL database (e.g., `safespeakk_db`).
   * Import the provided SQL schema (look in `database/` or `scripts/` folder).
3. Configure environment settings (in a config file or `.env`, or define constants in e.g., `config.php`):

   ```ini
   DB_HOST=localhost  
   DB_USER=root  
   DB_PASS=your_password  
   DB_NAME=safespeakk_db  
   ```
4. Place the project in your web server’s document root (for WAMP: `www/` directory).
5. Ensure file upload directory (e.g., `uploads/`) has correct permissions (writeable by web server).
6. Navigate to the application in your browser and register or login as admin.
7. (Optional) For chatbot: embed Tiledesk widget script in student view pages and configure flows in Tiledesk dashboard.

---

## 🎯 Usage Guide

### Student Workflow

1. Student registers and logs in.
2. Student creates a new report: provides details (date, description, incident type) and uploads evidence files.
3. Student can monitor status of submitted reports in their dashboard.
4. If student feels distressed/bullied, they can initiate the chatbot for support.

### School Admin Workflow

1. School admin logs in.
2. Views a list of reports submitted by students at their school.
3. Clicks a report to review evidence, add comments, update status (e.g., Under Review → Resolved).
4. Accesses analytics page: number of reports by type, trending times, resolution stats.

### System Admin Workflow

1. System admin logs in.
2. Manages schools: Add/edit school entries, assign school‑admins.
3. Manages master user list and roles.
4. Views system‑wide analytics: incidents across all schools, highest risk schools, upload volume.
5. Configures chatbot flows and global settings (e.g., allowed file types, max upload size).

---

## ✅ Security & Data Protection

* Passwords are stored securely (hashed with bcrypt or similar) — never plain text.
* Use prepared statements or parameterised queries in PHP to protect against SQL injection.
* File uploads validated: correct types (e.g., PDF, JPG, PNG), size limits, sanitized file names.
* Uploads stored outside the public root or with restricted access if possible.
* Role‑based access control: students cannot access school admin or system admin features.
* Sensitive settings and credentials (DB passwords, JWT or session secrets) kept out of source control (.env or config in ignored file).
* Audit logs (optional) capture key actions like status changes, user logins, file uploads for accountability.

---

## 🧪 Troubleshooting & Common Issues

* **“Update Status” button failing**: Ensure your PHP `POST` handler is correctly mapped, check URLs, check file permissions for any update scripts.
* **404 on file downloads**: Ensure the download script path is correct and the file permissions are readable by the web server.
* **Database connection errors**: Check config for correct host/user/password, ensure MySQL service is running, check firewall/localhost access.
* **File upload errors**: Check PHP `upload_max_filesize`, `post_max_size`, and `file_uploads` in `php.ini`. Ensure upload directory is writeable.
* **Chatbot not appearing**: Verify the widget script is included in the student view page; check console errors in browser dev tools; confirm Tiledesk config credentials are correct.

---

## 📅 Roadmap & Future Enhancements

* ✅ Core reporting system (students → schools → system admin) (current)
* 🟧 Enhanced analytics (visual charts, interactive filters)
* 🟧 Anonymous reporting mode (students submit anonymously)
* 🟧 Mobile‑friendly/responsive UI or a lightweight mobile app version
* 🟧 Real‑time alerts/notifications (email or SMS) when incidents are escalated or resolved
* 🟧 Multi‑language support (English + Filipino)
* 🟧 Integration with local mental health hotlines or external service directories
* 🟧 Parent/guardian dashboard for viewing status of their wards’ reports (optional)

---

## 🤝 Contributing

Contributions are welcome! If you’d like to help:

1. Fork the repository.
2. Create a new feature branch: `git checkout -b feature/YourFeature`.
3. Develop your changes following the MVC‑style (Models, Views, Controllers) architecture.
4. Submit a Pull Request describing your changes.
5. Ensure code is clean and documented; if you add new database tables or configuration, update README accordingly.

---

## 📝 License & Disclaimer

This project is developed for educational purposes and designed to support anti‑bullying efforts in high‑school environments. Use at your own risk.
(If you have a specific license, e.g., MIT, include it here.)

---

## 📞 Contact & Support

For questions about deployment, customization, or feature requests, please contact:

* Project Maintainer: Harold Arreglado – haroldarreglado902@gmail.com


---

*Last updated: **November 14, 2025***

**Live Demo:** [https://safespeak.page.gd/](https://safespeak.page.gd/)

---

