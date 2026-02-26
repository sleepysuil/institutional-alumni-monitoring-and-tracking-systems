Institutional Alumni Monitoring and Tracer System
A comprehensive web-based platform for tracking alumni employment, facilitating job opportunities, and generating institutional insights through data mining and analytics. Developed as a capstone project by the College of Information Systems, USAT College Sagay, Inc.

Features
Dual‑Role Access: Separate dashboards for administrators and alumni with role‑based permissions.

Alumni Directory: Search, filter, and view detailed profiles of all registered alumni.

Tracer Module: Monitor employment status, job relevance, and response rates over time.

Job Postings: Administrators and alumni can post, manage, and apply for job opportunities.

Data Mining & Analytics: Real‑time charts, clustering, association rules, and trend analysis using actual alumni data.

Reports Generation: Export alumni lists and employment reports in PDF, Excel, or CSV formats.

Newsfeed: Centralised feed displaying announcements and job postings.

Profile Management: Alumni can update personal and employment information, upload profile pictures, and view employment history.

Tracer Survey: Integrated survey form to collect up‑to‑date employment data.

Secure Authentication: Password hashing, CSRF protection, rate limiting, and session management.

Responsive Design: Built with Bootstrap 5 and custom CSS for a seamless experience on all devices.

Technologies Used
Backend: PHP 8.x (PDO, sessions, file uploads)

Database: MySQL / MariaDB

Frontend: HTML5, CSS3, JavaScript, Bootstrap 5, Font Awesome 6, Chart.js

Libraries: Dompdf, PhpSpreadsheet (for exports), PHPMailer (email notifications)

Development: XAMPP (Apache, MySQL, PHP), Composer

Requirements
Web server with PHP 8.0 or higher (Apache / Nginx)

MySQL 5.7 or higher

Composer (for installing PHP dependencies)

Modern web browser

Installation
Clone the repository

bash
git clone https://github.com/sleepysuil/institutional-alumni-monitoring-and-tracking-systems.git
cd institutional-alumni-monitoring-and-tracking-systems
Set up the database

Create a new MySQL database (e.g., alumni_system).

Import the provided SQL file:

bash
mysql -u root -p alumni_system < sql/database.sql
Configure the application

Copy config/constants.example.php to config/constants.php and update with your database credentials and site URL.

(Optional) Set up environment variables or edit constants.php directly for email/SMTP settings.

Install Composer dependencies

bash
composer install
If you don’t have Composer, download composer.phar and run:

bash
php composer.phar install
Set folder permissions

Make uploads/ and logs/ directories writable by the web server.

bash
chmod -R 755 uploads/ logs/
Run the application

Place the project in your web server’s document root (e.g., htdocs for XAMPP).

Access the system via http://localhost/alumni-system.

Create an admin account

Run http://localhost/alumni-system/create_admin.php once to create the default admin user (admin@usat.edu / admin123).

Delete create_admin.php immediately after use for security.

Usage
Admin Dashboard
Login using admin credentials.

View dashboard statistics, recent registrations, and employment distribution.

Manage alumni, job postings, announcements, and applications.

Access tracer module and data mining pages for advanced analytics.

Generate reports and export data.

Alumni Dashboard
Login using alumni credentials (created during registration).

Update profile, employment information, and profile picture.

Browse and apply for job opportunities.

View personal job applications and newsfeed.

Complete the tracer survey.

Security Considerations
All passwords are hashed using password_hash().

Prepared statements prevent SQL injection.

CSRF tokens protect all forms.

Session timeout and regeneration prevent fixation.

File uploads are validated by MIME type and size, and stored with random names.

Error messages are generic; details are logged.

.htaccess includes security headers (CSP, X‑Frame‑Options, etc.).

Credits
Capstone Project – Bachelor of Science in Information Systems

Palomaria, Erwin P – Project Manager

Paclibon, Francis Luis G – Programmer / UI/UX Designer

Gamao, Ericstine G – Analyst

Sabete, Christian Jacob H – Tester

Varca, Yul Bryan – Adviser, MIT

USAT College Sagay, Inc. – May 2026

License
This project is for educational purposes only. All rights reserved © USAT College Sagay, Inc. 2026.