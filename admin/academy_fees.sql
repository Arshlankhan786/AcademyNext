-- ============================================
-- NEXT ACADEMY - HOSTING-COMPATIBLE DATABASE
-- Fixed for strict mode, character encoding
-- Compatible with MySQL 5.7+ / MariaDB 10+
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Database: u946810828_Next
-- ============================================

-- Drop tables if exist (clean install)
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `student_notifications`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `course_fees`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `gallery`;
DROP TABLE IF EXISTS `contact_submissions`;
DROP TABLE IF EXISTS `admins`;

-- ============================================
-- TABLE: admins
-- ============================================
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('Super Admin','Admin') DEFAULT 'Admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: categories
-- ============================================
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: courses
-- ============================================
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_courses_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: course_fees
-- ============================================
CREATE TABLE `course_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `duration_months` int(11) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_course_duration` (`course_id`,`duration_months`),
  KEY `idx_course` (`course_id`),
  CONSTRAINT `fk_course_fees` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: students
-- ============================================
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_code` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text,
  `photo` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `duration_months` int(11) NOT NULL,
  `total_fees` decimal(10,2) NOT NULL,
  `enrollment_date` date NOT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('Active','Completed','Dropped','Deleted') DEFAULT 'Active',
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `login_enabled` tinyint(1) DEFAULT '0',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_code` (`student_code`),
  KEY `idx_student_code` (`student_code`),
  KEY `idx_status` (`status`),
  KEY `idx_course` (`course_id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_student_status_date` (`status`,`enrollment_date`),
  CONSTRAINT `fk_students_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `fk_students_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: payments
-- ============================================
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','Card','UPI','Other') DEFAULT 'Cash',
  `receipt_number` varchar(50) DEFAULT NULL,
  `notes` text,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_receipt` (`receipt_number`),
  KEY `idx_payment_student_date` (`student_id`,`payment_date`),
  CONSTRAINT `fk_payments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_admin` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: student_notifications
-- ============================================
CREATE TABLE `student_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','payment') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_unread` (`student_id`,`is_read`),
  CONSTRAINT `fk_notifications_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: contact_submissions
-- ============================================
CREATE TABLE `contact_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: gallery
-- ============================================
CREATE TABLE `gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text,
  `image_path` varchar(255) NOT NULL,
  `type` enum('image','video') DEFAULT 'image',
  `video_url` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT '0',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: password_resets
-- ============================================
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_admin_token` (`admin_id`,`token`),
  CONSTRAINT `fk_password_resets_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- Default Admins (passwords: admin123, admin123, Next2806)
INSERT INTO `admins` (`username`, `password`, `full_name`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'admin@academy.com', 'Super Admin'),
('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff Admin', 'staff@academy.com', 'Admin'),
('Next', '$2y$10$vYZ9qZXJ5f3KkF0aGxZ5nOEjYqHX7wTKF8wQ0Y.LZfxF5EzLkU4t6', 'Next Admin', 'next@academy.com', 'Super Admin');

-- Default Categories
INSERT INTO `categories` (`name`, `description`, `status`) VALUES
('Web Development', 'Courses related to web development and programming', 'Active'),
('Graphic Design', 'Courses related to graphic design and visual arts', 'Active'),
('Digital Marketing', 'Courses related to digital marketing and SEO', 'Active'),
('Mobile Development', 'iOS and Android app development courses', 'Active'),
('Data Science', 'Data analysis, machine learning, and AI courses', 'Active');

-- Default Courses
INSERT INTO `courses` (`category_id`, `name`, `description`, `status`) VALUES
(1, 'Full Stack Web Development', 'Complete web development course covering frontend and backend', 'Active'),
(1, 'Frontend Development', 'HTML, CSS, JavaScript, React, and modern frontend frameworks', 'Active'),
(1, 'Backend Development', 'Node.js, PHP, Python, and database management', 'Active'),
(2, 'Adobe Photoshop Mastery', 'Professional photo editing and graphic design', 'Active'),
(2, 'UI/UX Design', 'User interface and experience design principles', 'Active'),
(2, 'Adobe Illustrator', 'Vector graphics and logo design', 'Active'),
(3, 'Social Media Marketing', 'Master social media platforms for business growth', 'Active'),
(3, 'SEO & Content Marketing', 'Search engine optimization and content strategy', 'Active'),
(3, 'Google Ads & PPC', 'Pay-per-click advertising and campaign management', 'Active'),
(4, 'Android Development', 'Native Android app development with Kotlin', 'Active'),
(4, 'iOS Development', 'Native iOS app development with Swift', 'Active'),
(5, 'Data Analysis with Python', 'Pandas, NumPy, and data visualization', 'Active'),
(5, 'Machine Learning', 'ML algorithms and practical applications', 'Active');

-- Course Fees
INSERT INTO `course_fees` (`course_id`, `duration_months`, `fee_amount`) VALUES
(1,3,15000.00),(1,6,28000.00),(1,9,40000.00),(1,12,50000.00),(1,18,70000.00),(1,24,85000.00),
(2,3,12000.00),(2,6,22000.00),(2,9,32000.00),(2,12,40000.00),(2,18,55000.00),(2,24,65000.00),
(3,3,12000.00),(3,6,22000.00),(3,9,32000.00),(3,12,40000.00),
(4,3,8000.00),(4,6,15000.00),(4,9,21000.00),(4,12,26000.00),(4,18,36000.00),
(5,3,10000.00),(5,6,18000.00),(5,9,26000.00),(5,12,32000.00),(5,18,45000.00),(5,24,55000.00),
(6,3,8000.00),(6,6,15000.00),(6,9,21000.00),
(7,3,9000.00),(7,6,16000.00),(7,9,23000.00),(7,12,28000.00),(7,18,38000.00),
(8,3,9500.00),(8,6,17000.00),(8,9,24000.00),(8,12,30000.00),(8,18,42000.00),
(9,3,8500.00),(9,6,15500.00),(9,9,22000.00),
(10,6,25000.00),(10,9,35000.00),(10,12,45000.00),(10,18,60000.00),
(11,6,28000.00),(11,9,38000.00),(11,12,48000.00),(11,18,65000.00),
(12,3,12000.00),(12,6,22000.00),(12,9,32000.00),(12,12,40000.00),
(13,6,30000.00),(13,9,42000.00),(13,12,55000.00),(13,18,75000.00);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;