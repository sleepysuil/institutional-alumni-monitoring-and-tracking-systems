-- Create database
CREATE DATABASE IF NOT EXISTS alumni_system;
USE alumni_system;

-- Users table (login credentials)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','alumni') NOT NULL,
    alumni_id INT NULL, -- FK to alumni.id if role=alumni
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Alumni table
CREATE TABLE alumni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    program VARCHAR(100) NOT NULL,
    graduation_year INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    profile_pic VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Employment table (current employment)
CREATE TABLE employment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT NOT NULL,
    status ENUM('Employed','Self-Employed','Unemployed','Pursuing Higher Education') NOT NULL,
    company VARCHAR(100),
    position VARCHAR(100),
    industry VARCHAR(100),
    salary_range VARCHAR(50),
    relevance ENUM('Highly Relevant','Somewhat Relevant','Not Relevant'),
    start_date DATE,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE
);

-- Employment history (for career path analysis)
CREATE TABLE employment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT NOT NULL,
    company VARCHAR(100),
    position VARCHAR(100),
    industry VARCHAR(100),
    start_date DATE,
    end_date DATE,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE
);

-- Job postings
CREATE TABLE job_postings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    company VARCHAR(100) NOT NULL,
    location VARCHAR(100),
    description TEXT,
    requirements TEXT,
    posted_date DATE,
    deadline DATE,
    status ENUM('active','inactive') DEFAULT 'active',
    created_by INT, -- admin user id
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Job applications
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT NOT NULL,
    job_id INT NOT NULL,
    applied_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','reviewed','accepted','rejected') DEFAULT 'pending',
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES job_postings(id) ON DELETE CASCADE
);

-- Announcements
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    posted_by INT, -- admin user id
    posted_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id)
);

-- Tracer responses (survey)
CREATE TABLE tracer_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT NOT NULL,
    response_date DATE,
    is_employed BOOLEAN,
    job_title VARCHAR(100),
    company VARCHAR(100),
    industry VARCHAR(100),
    relevance VARCHAR(50),
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE
);

-- Optional: store data mining results (clusters, association rules) if you want to cache them
CREATE TABLE mining_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50), -- 'cluster', 'association', etc.
    data JSON,        -- store result as JSON
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data (with hashed passwords – use actual hashes)
-- For demonstration, we'll insert with plain text passwords (not secure)
-- In production, use password_hash().

-- Insert admin user (password: admin123) – hash manually
INSERT INTO users (email, password, role, alumni_id) VALUES
('admin@usat.edu', '$2y$10$YourHashedPasswordHere', 'admin', NULL);

-- Insert alumni
INSERT INTO alumni (student_id, first_name, last_name, program, graduation_year, email, phone) VALUES
('USAT-2022-001', 'Maria', 'Santos', 'BS Information Systems', 2022, 'maria.santos@email.com', '+63 912 345 6789'),
('USAT-2021-045', 'Juan', 'Dela Cruz', 'BS Information Systems', 2021, 'juan.delacruz@email.com', '+63 923 456 7890'),
('USAT-2023-112', 'Ana', 'Reyes', 'BS Computer Science', 2023, 'ana.reyes@email.com', '+63 934 567 8901'),
('USAT-2022-078', 'Pedro', 'Garcia', 'BS Business Administration', 2022, 'pedro.garcia@email.com', '+63 945 678 9012'),
('USAT-2024-201', 'Carla', 'Mendoza', 'BS Information Systems', 2024, 'carla.mendoza@email.com', '+63 956 789 0123'),
('USAT-2020-034', 'Ramon', 'Torres', 'BS Accountancy', 2020, 'ramon.torres@email.com', '+63 967 890 1234'),
('USAT-2023-156', 'Lisa', 'Fernandez', 'BS Information Systems', 2023, 'lisa.fernandez@email.com', '+63 978 901 2345'),
('USAT-2021-089', 'Miguel', 'Ramos', 'BS Computer Science', 2021, 'miguel.ramos@email.com', '+63 989 012 3456');

-- Insert employment data
INSERT INTO employment (alumni_id, status, company, position, industry, salary_range, relevance, start_date) VALUES
(1, 'Employed', 'Tech Solutions Inc.', 'Software Developer', 'Information Technology', '₱25,000 - ₱35,000', 'Highly Relevant', '2023-01-15'),
(2, 'Employed', 'Digital Corp', 'Systems Analyst', 'Information Technology', '₱30,000 - ₱40,000', 'Highly Relevant', '2022-06-01'),
(3, 'Self-Employed', NULL, 'Freelance Web Developer', 'Information Technology', NULL, 'Highly Relevant', NULL),
(4, 'Employed', 'Business Innovations', 'Marketing Specialist', 'Marketing', '₱20,000 - ₱30,000', 'Somewhat Relevant', '2023-03-10'),
(5, 'Pursuing Higher Education', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'Employed', 'Accounting Firm Inc.', 'Accountant', 'Accounting', '₱35,000 - ₱45,000', 'Highly Relevant', '2021-08-15'),
(7, 'Unemployed', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'Employed', 'Tech Startup', 'Junior Developer', 'Information Technology', '₱20,000 - ₱25,000', 'Highly Relevant', '2024-02-01');

-- Insert corresponding user accounts for alumni (password: alumni123 – hash manually)
INSERT INTO users (email, password, role, alumni_id) VALUES
('maria.santos@email.com', '$2y$10$...', 'alumni', 1),
('juan.delacruz@email.com', '$2y$10$...', 'alumni', 2),
('ana.reyes@email.com', '$2y$10$...', 'alumni', 3),
('pedro.garcia@email.com', '$2y$10$...', 'alumni', 4),
('carla.mendoza@email.com', '$2y$10$...', 'alumni', 5),
('ramon.torres@email.com', '$2y$10$...', 'alumni', 6),
('lisa.fernandez@email.com', '$2y$10$...', 'alumni', 7),
('miguel.ramos@email.com', '$2y$10$...', 'alumni', 8);

-- Insert sample job postings
INSERT INTO job_postings (title, company, location, description, requirements, posted_date, deadline, status, created_by) VALUES
('Software Developer', 'Tech Innovations Corp', 'Bacolod City', 'We are looking for a talented Software Developer...', 'BS in IT/CS, JavaScript, React, Node.js', '2025-02-10', '2025-03-10', 'active', 1),
('Business Analyst', 'Digital Solutions Inc.', 'Sagay City', 'Seeking a Business Analyst to bridge IT and business...', 'BS in Business/IS, analytical skills', '2025-02-12', '2025-03-15', 'active', 1),
('IT Support Specialist', 'USAT College Sagay, Inc.', 'Sagay City', 'Part-time IT support...', 'Basic networking, troubleshooting', '2025-02-15', '2025-03-20', 'active', 1);

-- Insert sample announcements
INSERT INTO announcements (title, content, posted_by, posted_date) VALUES
('Alumni Homecoming 2026', 'Join us for the USAT College Alumni Homecoming on March 15, 2026.', 1, '2025-02-10 09:00:00'),
('Employment Survey for 2024 Graduates', 'Dear 2024 graduates, please update your employment status.', 1, '2025-02-12 10:30:00'),
('Career Development Webinar', 'Attend our free webinar on "Building Your Tech Career" on February 25, 2026.', 1, '2025-02-13 14:00:00');

ALTER TABLE alumni ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS employment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT NOT NULL,
    company VARCHAR(100),
    position VARCHAR(100),
    industry VARCHAR(100),
    start_date DATE,
    end_date DATE,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS settings (
    setting_name VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);