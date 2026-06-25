-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 25, 2026 at 01:24 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `spms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `posted_by_name` varchar(150) NOT NULL,
  `posted_by_role` varchar(50) NOT NULL,
  `posted_by_user_id` int(11) DEFAULT NULL,
  `target_class_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursars`
--

CREATE TABLE `bursars` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `level` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `level`, `created_at`) VALUES
(1, 'JSS 3', 'Junior Secondary School', '2026-06-16 14:46:42'),
(2, 'JSS 2', 'Junior Secondary School', '2026-06-17 09:55:17'),
(3, 'JSS 1', 'Junior Secondary School', '2026-06-18 11:53:23'),
(4, 'SSS 1', 'Senior Secondary School', '2026-06-25 09:04:00'),
(5, 'SSS 2', 'Senior Secondary School', '2026-06-25 09:04:12'),
(6, 'SSS 3', 'Senior Secondary School', '2026-06-25 09:04:21'),
(7, 'JAMB', 'O Level', '2026-06-25 09:06:04');

-- --------------------------------------------------------

--
-- Table structure for table `class_fees`
--

CREATE TABLE `class_fees` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `term_id` int(11) DEFAULT NULL,
  `fee_category_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_fees`
--

INSERT INTO `class_fees` (`id`, `class_id`, `session_id`, `term_id`, `fee_category_id`, `amount`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 1, 5000000.00, '2026-06-17 10:34:39', '2026-06-17 10:34:39'),
(2, 2, 1, NULL, 1, 5000000.00, '2026-06-17 10:34:39', '2026-06-17 10:34:39'),
(3, 3, 1, NULL, 1, 10000000.00, '2026-06-19 08:27:58', '2026-06-19 08:27:58'),
(4, 3, 1, 7, 2, 2500.00, '2026-06-25 09:51:09', '2026-06-25 09:51:09'),
(5, 2, 1, 7, 2, 2500.00, '2026-06-25 09:51:09', '2026-06-25 09:51:09'),
(6, 1, 1, 7, 2, 2500.00, '2026-06-25 09:51:09', '2026-06-25 09:51:09'),
(7, 4, 1, 7, 2, 2500.00, '2026-06-25 09:51:09', '2026-06-25 09:51:09'),
(8, 5, 1, 7, 2, 2500.00, '2026-06-25 09:51:09', '2026-06-25 09:51:09'),
(9, 6, 1, 7, 2, 2500.00, '2026-06-25 09:51:09', '2026-06-25 09:51:09');

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `is_compulsory` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_subjects`
--

INSERT INTO `class_subjects` (`id`, `class_id`, `subject_id`, `is_compulsory`) VALUES
(4, 1, 6, 1),
(5, 2, 6, 1),
(6, 3, 6, 1),
(7, 1, 7, 1),
(8, 2, 7, 1),
(9, 3, 7, 1),
(10, 1, 14, 1),
(11, 2, 14, 1),
(12, 3, 14, 1),
(13, 1, 9, 1),
(14, 2, 9, 1),
(15, 3, 9, 1),
(16, 1, 5, 1),
(17, 2, 5, 1),
(18, 3, 5, 1),
(19, 1, 17, 1),
(20, 2, 17, 1),
(21, 3, 17, 1),
(22, 1, 16, 1),
(23, 2, 16, 1),
(24, 3, 16, 1),
(25, 1, 4, 1),
(26, 2, 4, 1),
(27, 3, 4, 1),
(31, 1, 22, 1),
(32, 2, 22, 1),
(33, 3, 22, 1),
(34, 1, 11, 1),
(35, 2, 11, 1),
(36, 3, 11, 1),
(37, 1, 15, 1),
(38, 2, 15, 1),
(39, 3, 15, 1),
(40, 1, 21, 1),
(41, 2, 21, 1),
(42, 3, 21, 1),
(43, 1, 10, 1),
(44, 2, 10, 1),
(45, 3, 10, 1),
(49, 1, 18, 1),
(50, 2, 18, 1),
(51, 3, 18, 1),
(52, 1, 8, 1),
(53, 2, 8, 1),
(54, 3, 8, 1),
(63, 6, 39, 1),
(66, 6, 33, 1),
(69, 6, 45, 1),
(70, 6, 46, 1),
(74, 6, 11, 1),
(78, 7, 47, 1),
(79, 7, 48, 1),
(80, 7, 49, 1),
(81, 7, 50, 1),
(82, 3, 13, 1),
(83, 2, 13, 1),
(84, 1, 13, 1),
(85, 4, 13, 1),
(86, 5, 13, 1),
(87, 6, 13, 1),
(88, 4, 38, 1),
(89, 5, 38, 1),
(90, 6, 38, 1),
(91, 4, 25, 1),
(92, 5, 25, 1),
(93, 6, 25, 1),
(94, 4, 24, 1),
(95, 5, 24, 1),
(96, 6, 24, 1),
(103, 4, 30, 1),
(104, 5, 30, 1),
(105, 6, 30, 1),
(106, 4, 35, 1),
(107, 5, 35, 1),
(108, 6, 35, 1),
(109, 4, 32, 1),
(110, 5, 32, 1),
(111, 6, 32, 1),
(112, 4, 34, 1),
(113, 5, 34, 1),
(114, 6, 34, 1),
(115, 3, 43, 1),
(116, 2, 43, 1),
(117, 1, 43, 1),
(118, 3, 20, 1),
(119, 2, 20, 1),
(120, 1, 20, 1),
(121, 4, 20, 1),
(122, 4, 44, 1),
(123, 5, 44, 1),
(124, 6, 44, 1),
(125, 4, 23, 1),
(126, 5, 23, 1),
(127, 6, 23, 1),
(128, 4, 37, 1),
(129, 5, 37, 1),
(130, 6, 37, 1),
(131, 3, 42, 1),
(132, 2, 42, 1),
(133, 1, 42, 1),
(134, 4, 28, 1),
(135, 5, 28, 1),
(136, 6, 28, 1),
(137, 4, 36, 1),
(138, 5, 36, 1),
(139, 4, 26, 1),
(140, 5, 26, 1),
(141, 6, 26, 1),
(142, 4, 29, 1),
(143, 5, 29, 1),
(144, 6, 29, 1),
(145, 4, 12, 1),
(146, 5, 12, 1),
(147, 6, 12, 1),
(148, 3, 19, 1),
(149, 2, 19, 1),
(150, 1, 19, 1),
(151, 3, 3, 1),
(152, 2, 3, 1),
(153, 1, 3, 1),
(154, 4, 3, 1),
(155, 5, 3, 1),
(156, 6, 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `fee_categories`
--

CREATE TABLE `fee_categories` (
  `id` int(11) NOT NULL,
  `fee_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_categories`
--

INSERT INTO `fee_categories` (`id`, `fee_name`, `description`, `created_at`) VALUES
(1, 'Tuition Fee', '', '2026-06-17 10:34:39'),
(2, 'PTA', '', '2026-06-25 09:51:09');

-- --------------------------------------------------------

--
-- Table structure for table `grading_scales`
--

CREATE TABLE `grading_scales` (
  `id` int(11) NOT NULL,
  `grade` varchar(5) NOT NULL,
  `lower_bound` decimal(5,2) NOT NULL,
  `upper_bound` decimal(5,2) NOT NULL,
  `remark` varchar(100) NOT NULL DEFAULT '',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grading_scales`
--

INSERT INTO `grading_scales` (`id`, `grade`, `lower_bound`, `upper_bound`, `remark`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'A1', 75.00, 100.00, 'Excellent', 1, '2026-06-23 16:58:02', '2026-06-23 16:58:02'),
(2, 'B2', 70.00, 74.99, 'Very Good', 1, '2026-06-23 16:58:02', '2026-06-23 16:58:02'),
(3, 'B3', 65.00, 69.99, 'Good', 1, '2026-06-23 16:58:02', '2026-06-23 16:58:02'),
(4, 'C4', 60.00, 64.99, 'Credit', 1, '2026-06-23 16:58:02', '2026-06-23 16:58:02'),
(5, 'C5', 55.00, 59.99, 'Credit', 1, '2026-06-23 16:58:02', '2026-06-23 16:58:02'),
(6, 'C6', 50.00, 54.99, 'Credit', 1, '2026-06-23 16:58:02', '2026-06-23 16:58:02'),
(7, 'D7', 45.00, 49.99, 'Pass', 1, '2026-06-23 16:58:02', '2026-06-23 16:58:02'),
(8, 'E8', 40.00, 44.99, 'Pass', 1, '2026-06-23 16:58:02', '2026-06-23 16:58:02'),
(9, 'F9', 0.00, 39.99, 'Fail', 1, '2026-06-23 16:58:02', '2026-06-23 16:58:02');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'NULL = broadcast to role',
  `target_role` varchar(20) DEFAULT NULL COMMENT 'NULL = all users',
  `target_class_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'general',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `residential_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`id`, `user_id`, `full_name`, `phone`, `occupation`, `residential_address`, `created_at`) VALUES
(1, 7, 'Eni Stuart', NULL, NULL, NULL, '2026-06-17 09:58:41'),
(2, 11, 'Flourence Amaka', NULL, NULL, NULL, '2026-06-18 16:09:29'),
(3, 12, 'Marvellous Jae', NULL, NULL, NULL, '2026-06-19 07:46:29'),
(4, 15, 'Temidayo Idowu', NULL, NULL, NULL, '2026-06-23 11:32:41');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `bursar_id` int(11) DEFAULT NULL,
  `amount_paid` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','Cheque','Online') DEFAULT 'Cash',
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `receipt_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `student_id`, `bursar_id`, `amount_paid`, `payment_method`, `payment_reference`, `payment_date`, `receipt_number`, `notes`, `created_at`) VALUES
(1, 3, NULL, 10000000.00, 'Online', 'T113211465031297', '2026-06-19 09:20:20', NULL, NULL, '2026-06-19 09:20:20');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `ca_score` decimal(5,2) DEFAULT 0.00,
  `exam_score` decimal(5,2) DEFAULT 0.00,
  `total_score` decimal(5,2) GENERATED ALWAYS AS (`ca_score` + `exam_score`) STORED,
  `grade` varchar(2) DEFAULT NULL,
  `teacher_remarks` text DEFAULT NULL,
  `status` enum('pending','submitted','approved','published') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `session_name` varchar(50) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `session_name`, `start_date`, `end_date`, `is_active`, `created_at`) VALUES
(1, '2026/2027', '2026-06-17', '2027-06-15', 1, '2026-06-16 14:24:04');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admission_no` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `residential_address` text DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `session_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `admission_no`, `first_name`, `last_name`, `middle_name`, `gender`, `date_of_birth`, `photo_path`, `state`, `residential_address`, `class_id`, `department`, `session_id`, `parent_id`, `phone`, `is_active`, `created_at`) VALUES
(2, 6, '0004', 'Marvel', 'Jae', '', 'Male', '0000-00-00', NULL, NULL, NULL, 2, NULL, 1, NULL, '09034568989', 1, '2026-06-17 09:57:44'),
(3, 8, 'ADM001', 'John', 'Doe', 'Emeka', 'Male', '2010-05-14', NULL, NULL, NULL, 3, NULL, 1, 3, '08012345678', 1, '2026-06-18 12:47:21'),
(4, 9, 'ADM002', 'Amaka', 'Obi', '', 'Female', '2011-03-22', NULL, NULL, NULL, 3, NULL, 1, 2, '08098765432', 1, '2026-06-18 12:47:21'),
(5, 10, 'ADM003', 'Chidi', 'Nwosu', 'Uche', 'Male', '2009-11-01', NULL, NULL, NULL, 3, NULL, 1, 4, '07063198578', 1, '2026-06-18 12:47:21'),
(6, 16, 'ADM004', 'Omolade', 'Asaolu', 'Nifemi', 'Female', '2002-02-18', NULL, NULL, NULL, 3, NULL, 1, 4, '08143426782', 1, '2026-06-23 12:38:29'),
(8, 17, 'adm005', 'Chidi', 'Nwosu', 'Okonkwo', 'Male', '2013-03-03', NULL, NULL, NULL, 3, NULL, 1, NULL, '08052758105', 1, '2026-06-25 09:36:44'),
(9, 18, 'adm006', 'Aisha', 'Yakubu', 'Bello', 'Female', '2016-08-16', NULL, NULL, NULL, 3, NULL, 1, NULL, '08094635294', 1, '2026-06-25 09:36:44'),
(10, 19, 'adm007', 'Tunde', 'Salami', 'Babatunde', 'Male', '2014-02-21', NULL, NULL, NULL, 3, NULL, 1, NULL, '08185807435', 1, '2026-06-25 09:36:44'),
(11, 20, 'adm008', 'Nkechi', 'Eze', 'Gloria', 'Female', '2014-02-23', NULL, NULL, NULL, 3, NULL, 1, NULL, '09194808231', 1, '2026-06-25 09:36:44'),
(12, 21, 'adm009', 'Musa', 'Abubakar', 'Danjuma', 'Male', '2014-02-08', NULL, NULL, NULL, 3, NULL, 1, NULL, '09149502898', 1, '2026-06-25 09:36:44'),
(13, 22, 'adm010', 'Zainab', 'Usman', 'Aminah', 'Female', '2015-01-14', NULL, NULL, NULL, 2, NULL, 1, NULL, '09038185697', 1, '2026-06-25 09:36:44'),
(14, 23, 'adm011', 'Olawale', 'Ogunlade', 'Adeyemi', 'Male', '2013-01-13', NULL, NULL, NULL, 2, NULL, 1, NULL, '08163300243', 1, '2026-06-25 09:36:44'),
(15, 24, 'adm012', 'Chiamaka', 'Okeke', 'Joy', 'Female', '2014-06-18', NULL, NULL, NULL, 2, NULL, 1, NULL, '09087358540', 1, '2026-06-25 09:36:44'),
(16, 25, 'adm013', 'Ibrahim', 'Garba', 'Suleiman', 'Male', '2015-07-03', NULL, NULL, NULL, 2, NULL, 1, NULL, '09064913872', 1, '2026-06-25 09:36:44'),
(17, 26, 'adm014', 'Folake', 'Adebayo', 'Rebecca', 'Female', '2014-10-15', NULL, NULL, NULL, 2, NULL, 1, NULL, '08094461337', 1, '2026-06-25 09:36:44'),
(18, 27, 'adm015', 'Emeka', 'Okafor', 'Patrick', 'Male', '2012-01-24', NULL, NULL, NULL, 1, NULL, 1, NULL, '08194383250', 1, '2026-06-25 09:36:44'),
(19, 28, 'adm016', 'Fatima', 'Mohammed', 'Hauwa', 'Female', '2012-11-17', NULL, NULL, NULL, 1, NULL, 1, NULL, '08081615610', 1, '2026-06-25 09:36:44'),
(20, 29, 'adm017', 'Kunle', 'Akinwale', 'Michael', 'Male', '2014-01-17', NULL, NULL, NULL, 1, NULL, 1, NULL, '07000846889', 1, '2026-06-25 09:36:44'),
(21, 30, 'adm018', 'Adanna', 'Ugwu', 'Esther', 'Female', '2013-03-21', NULL, NULL, NULL, 1, NULL, 1, NULL, '09041209182', 1, '2026-06-25 09:36:44'),
(22, 31, 'adm019', 'Sadiq', 'Bala', 'Abdullahi', 'Male', '2012-02-12', NULL, NULL, NULL, 1, NULL, 1, NULL, '09183805214', 1, '2026-06-25 09:36:44'),
(23, 32, 'adm020', 'Somtochukwu', 'Okoro', 'David', 'Male', '2010-04-27', NULL, NULL, NULL, 4, 'science', 1, NULL, '09166143624', 1, '2026-06-25 09:36:44'),
(24, 33, 'adm021', 'Ifeoluwa', 'Aderinto', 'Elizabeth', 'Female', '2011-12-19', NULL, NULL, NULL, 4, 'science', 1, NULL, '08062415612', 1, '2026-06-25 09:36:44'),
(25, 34, 'adm022', 'Jeremiah', 'Ogunbiyi', 'Olusegun', 'Male', '2011-05-06', NULL, NULL, NULL, 4, 'commercial', 1, NULL, '08132399352', 1, '2026-06-25 09:36:44'),
(26, 35, 'adm023', 'Ruth', 'Onyema', 'Chioma', 'Female', '2012-08-05', NULL, NULL, NULL, 4, 'commercial', 1, NULL, '09164340165', 1, '2026-06-25 09:36:44'),
(27, 36, 'adm024', 'Ayomide', 'Ogunyemi', 'Precious', 'Female', '2010-12-11', NULL, NULL, NULL, 4, 'arts', 1, NULL, '09008899522', 1, '2026-06-25 09:36:44'),
(28, 37, 'adm025', 'Tobenna', 'Okafor', 'Emeka', 'Male', '2010-08-23', NULL, NULL, NULL, 4, 'arts', 1, NULL, '07091131059', 1, '2026-06-25 09:36:44'),
(29, 38, 'adm026', 'Oluwatobiloba', 'Ogunlana', 'Tobias', 'Male', '2011-09-11', NULL, NULL, NULL, 5, 'science', 1, NULL, '09062509940', 1, '2026-06-25 09:36:44'),
(30, 39, 'adm027', 'Mariam', 'Oladipo', 'Yetunde', 'Female', '2009-01-02', NULL, NULL, NULL, 5, 'science', 1, NULL, '09085395013', 1, '2026-06-25 09:36:44'),
(31, 40, 'adm028', 'Amara', 'Eze', 'Nwando', 'Female', '2009-10-09', NULL, NULL, NULL, 5, 'commercial', 1, NULL, '08183038304', 1, '2026-06-25 09:36:44'),
(32, 41, 'adm029', 'Babatunde', 'Adebisi', 'Jamiu', 'Male', '2011-04-07', NULL, NULL, NULL, 5, 'commercial', 1, NULL, '07021564702', 1, '2026-06-25 09:36:44'),
(33, 42, 'adm030', 'Adaobi', 'Eneh', 'Grace', 'Female', '2011-10-01', NULL, NULL, NULL, 5, 'arts', 1, NULL, '08179644560', 1, '2026-06-25 09:36:44'),
(34, 43, 'adm031', 'Damilare', 'Fashina', 'Samuel', 'Male', '2009-03-25', NULL, NULL, NULL, 5, 'arts', 1, NULL, '07044805924', 1, '2026-06-25 09:36:44'),
(35, 44, 'adm032', 'Kehinde', 'Olatunji', 'Oluwaseun', 'Male', '2010-11-14', NULL, NULL, NULL, 6, 'science', 1, NULL, '08036208860', 1, '2026-06-25 09:36:44'),
(36, 45, 'adm033', 'Chinyere', 'Ike', 'Helen', 'Female', '2010-10-10', NULL, NULL, NULL, 6, 'science', 1, NULL, '08059769052', 1, '2026-06-25 09:36:44'),
(37, 46, 'adm034', 'Segun', 'Olawale', 'Abdul', 'Male', '2010-12-03', NULL, NULL, NULL, 6, 'commercial', 1, NULL, '09033670801', 1, '2026-06-25 09:36:44'),
(38, 47, 'adm035', 'Blessing', 'Nwachukwu', 'Ada', 'Female', '2009-10-04', NULL, NULL, NULL, 6, 'commercial', 1, NULL, '07078190714', 1, '2026-06-25 09:36:44'),
(39, 48, 'adm036', 'Yusuf', 'Akanbi', 'Bayo', 'Male', '2009-09-10', NULL, NULL, NULL, 6, 'arts', 1, NULL, '09009741087', 1, '2026-06-25 09:36:44'),
(40, 49, 'adm037', 'Esther', 'Akpan', 'Mfon', 'Female', '2010-09-02', NULL, NULL, NULL, 6, 'arts', 1, NULL, '08001696285', 1, '2026-06-25 09:36:44'),
(41, 50, 'adm038', 'Obinna', 'Okoye', 'Chinedu', 'Female', '2016-02-19', NULL, NULL, NULL, 3, NULL, 1, NULL, '09072061972', 1, '2026-06-25 09:43:40'),
(42, 51, 'adm039', 'Rukayat', 'Salaudeen', 'Adebimpe', 'Female', '2013-02-04', NULL, NULL, NULL, 3, NULL, 1, NULL, '07093130708', 1, '2026-06-25 09:43:40'),
(43, 52, 'adm040', 'Yemi', 'Adebayo', 'Tolulope', 'Male', '2014-05-25', NULL, NULL, NULL, 3, NULL, 1, NULL, '07017128874', 1, '2026-06-25 09:43:40'),
(44, 53, 'adm041', 'Chinenye', 'Ibe', 'Esther', 'Female', '2016-10-11', NULL, NULL, NULL, 3, NULL, 1, NULL, '09068423931', 1, '2026-06-25 09:43:40'),
(45, 54, 'adm042', 'Usman', 'Danladi', 'Ibrahim', 'Female', '2014-09-11', NULL, NULL, NULL, 3, NULL, 1, NULL, '09122525794', 1, '2026-06-25 09:43:40'),
(46, 55, 'adm043', 'Ngozi', 'Okafor', 'Favour', 'Male', '2013-10-01', NULL, NULL, NULL, 2, NULL, 1, NULL, '08180915885', 1, '2026-06-25 09:43:40'),
(47, 56, 'adm044', 'Kolawole', 'Ogunbiyi', 'Samuel', 'Female', '2015-05-05', NULL, NULL, NULL, 2, NULL, 1, NULL, '08097914382', 1, '2026-06-25 09:43:40'),
(48, 57, 'adm045', 'Hauwa', 'Musa', 'Zainab', 'Female', '2013-03-04', NULL, NULL, NULL, 2, NULL, 1, NULL, '08104880704', 1, '2026-06-25 09:43:40'),
(49, 58, 'adm046', 'Femi', 'Adegoke', 'Ayodele', 'Female', '2014-11-17', NULL, NULL, NULL, 2, NULL, 1, NULL, '07074415197', 1, '2026-06-25 09:43:40'),
(50, 59, 'adm047', 'Amara', 'Nwachukwu', 'Chisom', 'Female', '2014-03-16', NULL, NULL, NULL, 1, NULL, 1, NULL, '08187517521', 1, '2026-06-25 09:43:40'),
(51, 60, 'adm048', 'Bashir', 'Yusuf', 'Musa', 'Female', '2012-12-12', NULL, NULL, NULL, 1, NULL, 1, NULL, '09037257582', 1, '2026-06-25 09:43:40'),
(52, 61, 'adm049', 'Damilola', 'Ayodeji', 'Oluwaseun', 'Male', '2013-05-22', NULL, NULL, NULL, 1, NULL, 1, NULL, '09081173778', 1, '2026-06-25 09:43:40'),
(53, 62, 'adm050', 'Chukwudi', 'Eze', 'Ebuka', 'Male', '2012-01-01', NULL, NULL, NULL, 1, NULL, 1, NULL, '08099379066', 1, '2026-06-25 09:43:40'),
(54, 63, 'adm051', 'Mfonobong', 'Udoh', 'Idara', 'Male', '2012-07-26', NULL, NULL, NULL, 1, NULL, 1, NULL, '07094745305', 1, '2026-06-25 09:43:40'),
(55, 64, 'adm052', 'Oluwaseyi', 'Ogunyemi', 'Fisayo', 'Female', '2010-01-08', NULL, NULL, NULL, 4, 'science', 1, NULL, '09136201690', 1, '2026-06-25 09:43:40'),
(56, 65, 'adm053', 'Ikenna', 'Nwosu', 'Michael', 'Female', '2012-04-02', NULL, NULL, NULL, 4, 'science', 1, NULL, '08169565030', 1, '2026-06-25 09:43:40'),
(57, 66, 'adm054', 'Temilade', 'Okeowo', 'Simisola', 'Male', '2012-01-03', NULL, NULL, NULL, 4, 'science', 1, NULL, '09111807186', 1, '2026-06-25 09:43:40'),
(58, 67, 'adm055', 'Chisom', 'Eze', 'Precious', 'Female', '2011-03-13', NULL, NULL, NULL, 4, 'commercial', 1, NULL, '09198450100', 1, '2026-06-25 09:43:40'),
(59, 68, 'adm056', 'Uzoma', 'Okeke', 'Chibuzo', 'Female', '2010-10-13', NULL, NULL, NULL, 4, 'commercial', 1, NULL, '08108506894', 1, '2026-06-25 09:43:40'),
(60, 69, 'adm057', 'Boluwatife', 'Adebayo', 'Adeola', 'Female', '2012-03-13', NULL, NULL, NULL, 4, 'commercial', 1, NULL, '08056243951', 1, '2026-06-25 09:43:40'),
(61, 70, 'adm058', 'Adaeze', 'Ugwu', 'Ogechi', 'Male', '2010-12-26', NULL, NULL, NULL, 4, 'arts', 1, NULL, '08147416384', 1, '2026-06-25 09:43:40'),
(62, 71, 'adm059', 'Akintunde', 'Olaoye', 'Kayode', 'Male', '2011-06-13', NULL, NULL, NULL, 4, 'arts', 1, NULL, '09097131421', 1, '2026-06-25 09:43:40'),
(63, 72, 'adm060', 'Chidera', 'Anozie', 'Nmesoma', 'Female', '2012-01-23', NULL, NULL, NULL, 4, 'arts', 1, NULL, '08168230200', 1, '2026-06-25 09:43:40'),
(64, 73, 'adm061', 'Bolanle', 'Ogunlade', 'Titilayo', 'Female', '2011-10-06', NULL, NULL, NULL, 5, 'science', 1, NULL, '07002205570', 1, '2026-06-25 09:43:40'),
(65, 74, 'adm062', 'Nnamdi', 'Okoli', 'Chukwuma', 'Male', '2009-01-22', NULL, NULL, NULL, 5, 'science', 1, NULL, '08107482118', 1, '2026-06-25 09:43:40'),
(66, 75, 'adm063', 'Funmilayo', 'Fashina', 'Yetunde', 'Male', '2009-06-04', NULL, NULL, NULL, 5, 'commercial', 1, NULL, '09034794598', 1, '2026-06-25 09:43:40'),
(67, 76, 'adm064', 'Chibuzor', 'Ani', 'Ifeanyi', 'Female', '2010-07-25', NULL, NULL, NULL, 5, 'commercial', 1, NULL, '09108118099', 1, '2026-06-25 09:43:40'),
(68, 77, 'adm065', 'Morenikeji', 'Sofoluwe', 'Rachel', 'Female', '2011-07-17', NULL, NULL, NULL, 5, 'arts', 1, NULL, '07030277203', 1, '2026-06-25 09:43:40'),
(69, 78, 'adm066', 'Ejiroghene', 'Emetulu', 'Oghenekaro', 'Female', '2010-08-25', NULL, NULL, NULL, 5, 'arts', 1, NULL, '07056512084', 1, '2026-06-25 09:43:40'),
(70, 79, 'adm067', 'Akpan', 'Udo', 'Iniobong', 'Male', '2010-10-15', NULL, NULL, NULL, 6, 'science', 1, NULL, '09110856320', 1, '2026-06-25 09:43:40'),
(71, 80, 'adm068', 'Abimbola', 'Alabi', 'Oluwatoyin', 'Female', '2008-02-24', NULL, NULL, NULL, 6, 'science', 1, NULL, '08116741874', 1, '2026-06-25 09:43:40'),
(72, 81, 'adm069', 'Obiora', 'Anigbogu', 'Chinedu', 'Male', '2008-07-01', NULL, NULL, NULL, 6, 'commercial', 1, NULL, '09047483018', 1, '2026-06-25 09:43:40'),
(73, 82, 'adm070', 'Ifunanya', 'Ugwu', 'Mercy', 'Female', '2009-01-11', NULL, NULL, NULL, 6, 'commercial', 1, NULL, '08048267557', 1, '2026-06-25 09:43:40'),
(74, 83, 'adm071', 'Oluwadamilare', 'Balogun', 'Ayodeji', 'Male', '2009-08-02', NULL, NULL, NULL, 6, 'arts', 1, NULL, '09111505273', 1, '2026-06-25 09:43:40'),
(75, 84, 'adm072', 'Nnenna', 'Okafor', 'Ogechi', 'Female', '2008-09-07', NULL, NULL, NULL, 6, 'arts', 1, NULL, '09042522191', 1, '2026-06-25 09:43:40');

-- --------------------------------------------------------

--
-- Table structure for table `student_fees`
--

CREATE TABLE `student_fees` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `term_id` int(11) DEFAULT NULL,
  `fee_category_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `is_paid` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_fees`
--

INSERT INTO `student_fees` (`id`, `student_id`, `session_id`, `term_id`, `fee_category_id`, `amount`, `is_paid`, `created_at`) VALUES
(2, 2, 1, NULL, 1, 5000000.00, 0, '2026-06-17 10:34:39'),
(3, 3, 1, NULL, 1, 10000000.00, 1, '2026-06-19 08:27:58'),
(4, 4, 1, NULL, 1, 10000000.00, 0, '2026-06-19 08:27:58'),
(5, 5, 1, NULL, 1, 10000000.00, 0, '2026-06-19 08:27:58'),
(6, 3, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(7, 4, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(8, 5, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(9, 6, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(10, 8, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(11, 9, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(12, 10, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(13, 11, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(14, 12, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(15, 41, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(16, 42, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(17, 43, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(18, 44, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(19, 45, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(20, 2, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(21, 13, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(22, 14, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(23, 15, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(24, 16, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(25, 17, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(26, 46, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(27, 47, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(28, 48, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(29, 49, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(30, 18, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(31, 19, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(32, 20, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(33, 21, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(34, 22, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(35, 50, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(36, 51, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(37, 52, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(38, 53, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(39, 54, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(40, 23, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(41, 24, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(42, 25, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(43, 26, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(44, 27, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(45, 28, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(46, 55, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(47, 56, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(48, 57, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(49, 58, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(50, 59, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(51, 60, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(52, 61, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(53, 62, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(54, 63, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(55, 29, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(56, 30, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(57, 31, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(58, 32, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(59, 33, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(60, 34, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(61, 64, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(62, 65, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(63, 66, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(64, 67, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(65, 68, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(66, 69, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(67, 35, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(68, 36, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(69, 37, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(70, 38, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(71, 39, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(72, 40, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(73, 70, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(74, 71, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(75, 72, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(76, 73, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(77, 74, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09'),
(78, 75, 1, 7, 2, 2500.00, 0, '2026-06-25 09:51:09');

-- --------------------------------------------------------

--
-- Table structure for table `student_parent_links`
--

CREATE TABLE `student_parent_links` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_parent_links`
--

INSERT INTO `student_parent_links` (`id`, `student_id`, `parent_id`, `relationship`, `created_at`) VALUES
(1, 2, 1, 'Parent', '2026-06-17 09:58:41'),
(3, 4, 2, 'Parent', '2026-06-18 16:09:29'),
(4, 3, 3, 'Parent', '2026-06-19 07:46:29'),
(5, 5, 4, 'Parent', '2026-06-23 11:32:41'),
(6, 6, 4, 'Parent', '2026-06-23 12:38:29');

-- --------------------------------------------------------

--
-- Table structure for table `student_results`
--

CREATE TABLE `student_results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `ca_score` decimal(5,2) DEFAULT NULL,
  `exam_score` decimal(5,2) DEFAULT NULL,
  `uploaded_by_teacher_id` int(11) DEFAULT NULL,
  `published_by_admin_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_name`, `subject_code`, `department`, `description`, `created_at`, `is_active`) VALUES
(3, 'Mathematics', 'MAT', 'general', 'Core mathematics for all levels', '2026-06-25 08:58:33', 1),
(4, 'English Language', 'ENG', 'general', 'Core English language for all levels', '2026-06-25 08:58:33', 1),
(5, 'Civic Education', 'CIV', 'general', 'Citizenship and civic responsibilities', '2026-06-25 08:58:33', 1),
(6, 'Basic Science', 'BSC', NULL, 'Integrated science for JSS', '2026-06-25 08:58:33', 1),
(7, 'Basic Technology', 'BTC', NULL, 'Introduction to technology and engineering', '2026-06-25 08:58:33', 1),
(8, 'Social Studies', 'SOS', NULL, 'Society and human relationships', '2026-06-25 08:58:33', 1),
(9, 'Christian Religious Studies', 'CRS', NULL, 'Christian religious knowledge', '2026-06-25 08:58:33', 1),
(10, 'Islamic Studies', 'IRS', NULL, 'Islamic religious knowledge', '2026-06-25 08:58:33', 1),
(11, 'History', 'HIS', 'arts', 'Study of past events and civilizations', '2026-06-25 08:58:33', 1),
(12, 'Geography', 'GEO', 'arts', 'Physical and human geography', '2026-06-25 08:58:33', 1),
(13, 'Agricultural Science', 'AGR', 'science', 'Farming and agricultural practices', '2026-06-25 08:58:33', 1),
(14, 'Business Studies', 'BUS', NULL, 'Introduction to business and commerce', '2026-06-25 08:58:33', 1),
(15, 'Home Economics', 'HEC', NULL, 'Home management and family living', '2026-06-25 08:58:33', 1),
(16, 'Cultural and Creative Arts', 'CCA', NULL, 'Arts, music, drama and culture', '2026-06-25 08:58:33', 1),
(17, 'Computer Studies', 'COM', 'general', 'Introduction to computing and ICT', '2026-06-25 08:58:33', 1),
(18, 'Physical and Health Education', 'PHE', NULL, 'Physical fitness and health awareness', '2026-06-25 08:58:33', 1),
(19, 'French', 'FRN', 'arts', 'French language studies', '2026-06-25 08:58:33', 1),
(20, 'Yoruba Language', 'YOR', 'arts', 'Yoruba language and literature', '2026-06-25 08:58:33', 1),
(21, 'Igbo Language', 'IGB', 'arts', 'Igbo language and literature', '2026-06-25 08:58:33', 1),
(22, 'Hausa Language', 'HAU', 'arts', 'Hausa language and literature', '2026-06-25 08:58:33', 1),
(23, 'Physics', 'PHY', 'science', 'Physics for SSS science students', '2026-06-25 08:58:33', 1),
(24, 'Chemistry', 'CHM', 'science', 'Chemistry for SSS science students', '2026-06-25 08:58:33', 1),
(25, 'Biology', 'BIO', 'science', 'Biology for SSS science students', '2026-06-25 08:58:33', 1),
(26, 'Further Mathematics', 'FUR', 'science', 'Advanced mathematics for science students', '2026-06-25 08:58:33', 1),
(27, 'Health Education', 'HED', 'science', 'Advanced health and hygiene studies', '2026-06-25 08:58:33', 1),
(28, 'Literature in English', 'LIT', 'arts', 'English literature and prose', '2026-06-25 08:58:33', 1),
(29, 'Government', 'GOV', 'arts', 'Government and political systems', '2026-06-25 08:58:33', 1),
(30, 'Christian Religious Knowledge', 'CRK', 'arts', 'Advanced Christian religious studies', '2026-06-25 08:58:33', 1),
(31, 'Islamic Religious Knowledge', 'IRK', 'arts', 'Advanced Islamic religious studies', '2026-06-25 08:58:33', 1),
(32, 'Economics', 'ECO', 'commercial', 'Economic principles and theory', '2026-06-25 08:58:33', 1),
(33, 'Commerce', 'CMR', 'commercial', 'Principles of trade and commerce', '2026-06-25 08:58:33', 1),
(34, 'Financial Accounting', 'ACC', 'commercial', 'Accounting principles and practices', '2026-06-25 08:58:33', 1),
(35, 'Data Processing', 'DAT', 'science', 'Computer data processing and management', '2026-06-25 08:58:33', 1),
(36, 'Insurance', 'INS', 'commercial', 'Insurance principles and practice', '2026-06-25 08:58:33', 1),
(37, 'Office Practice', 'OFP', 'commercial', 'Office administration and management', '2026-06-25 08:58:33', 1),
(38, 'Animal Husbandry', 'AHU', 'science', 'Livestock farming and management', '2026-06-25 08:58:33', 1),
(39, 'Fisheries', 'FSH', 'science', 'Fish farming and aquatic resources', '2026-06-25 08:58:33', 1),
(40, 'Food and Nutrition', 'FNT', 'arts', 'Food science and nutrition', '2026-06-25 08:58:33', 1),
(41, 'Clothing and Textile', 'CTX', 'arts', 'Textile design and garment making', '2026-06-25 08:58:33', 1),
(42, 'Music', 'MUS', 'arts', 'Music theory and practice', '2026-06-25 08:58:33', 1),
(43, 'Fine Arts', 'ART', 'arts', 'Visual arts and design', '2026-06-25 08:58:33', 1),
(44, 'Store Management', 'STM', 'commercial', 'Inventory and store keeping', '2026-06-25 08:58:33', 1),
(45, 'Marketing', 'MKT', 'commercial', 'Marketing principles and sales', '2026-06-25 08:58:33', 1),
(46, 'Tourism', 'TUR', 'commercial', 'Travel and tourism management', '2026-06-25 08:58:33', 1),
(47, 'Use of English', '', NULL, NULL, '2026-06-25 09:21:53', 1),
(48, 'JAMB Maths', '', NULL, NULL, '2026-06-25 09:22:41', 1),
(49, 'JAMB Chemistry', '', NULL, NULL, '2026-06-25 09:22:58', 1),
(50, 'JAMB Physics', '', NULL, NULL, '2026-06-25 09:23:12', 1);

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `plain_password` varchar(255) DEFAULT NULL,
  `staff_id` varchar(50) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `email`, `password_hash`, `plain_password`, `staff_id`, `full_name`, `department`, `phone`, `created_at`) VALUES
(6, 1, 'admin@123', '$2y$10$7akdcaVwiF.LR81Kxrcfl.zAZHXjDSxtaKkaMihlpHBMDm76nTOgW', NULL, 'TCH003', 'Alowolodu Esther', 'Sciences', '09034568970', '2026-06-19 13:33:03'),
(7, 13, 'teachertest2@gmail.com', '$2y$10$9IHsUK.eKDkWXfgE3X9cVemssn/BauaZsPkXDAM.Mlty3LwVRmMIO', 'test2', 'TCH004', 'Teacher Test2', 'None', '702345789', '2026-06-22 03:34:51'),
(8, 14, NULL, NULL, NULL, 'TCH005', 'Teacher Test3', 'Commercial', '0803456735', '2026-06-22 04:16:13');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_assignments`
--

CREATE TABLE `teacher_assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_assignments`
--

INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `subject_id`, `class_id`, `session_id`, `created_at`) VALUES
(1, 8, NULL, 2, 1, '2026-06-22 04:16:13'),
(2, 7, NULL, 3, 1, '2026-06-25 08:59:14'),
(3, 7, NULL, 2, 1, '2026-06-25 08:59:14'),
(4, 7, NULL, 1, 1, '2026-06-25 08:59:14');

-- --------------------------------------------------------

--
-- Table structure for table `terms`
--

CREATE TABLE `terms` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `term_name` varchar(100) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `terms`
--

INSERT INTO `terms` (`id`, `session_id`, `term_name`, `start_date`, `end_date`, `is_active`, `created_at`) VALUES
(1, 1, 'First Term', '2026-06-17', '2026-11-17', 0, '2026-06-17 10:28:11'),
(7, 1, 'Second Term', '2026-06-19', '2026-11-27', 0, '2026-06-19 07:23:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `plain_password` varchar(10) DEFAULT NULL,
  `role` enum('admin','super-admin','student','parent','teacher','bursar') NOT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `plain_password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@123', '$2y$10$7akdcaVwiF.LR81Kxrcfl.zAZHXjDSxtaKkaMihlpHBMDm76nTOgW', NULL, 'admin', 'active', '2026-06-16 11:57:58', '2026-06-16 11:57:58'),
(2, '0001', 'emmy@gmail.com', '$2y$10$eBTt4KbCAbEBEp.zxVd.VuEkkvZ3Dl4swgd9GB/rDB14OVbhJkKm2', NULL, 'student', 'active', '2026-06-16 14:48:31', '2026-06-16 14:48:31'),
(4, '0002', 'marvellousobor@gmail.com', '$2y$10$USNXrjgoSRmhfRj6q3iIEuZYZGtfSjvaQGNG9A3LsbH6jKqDbJqPi', NULL, 'student', 'active', '2026-06-16 14:59:12', '2026-06-16 14:59:12'),
(5, '0003', 'student@school.com', '$2y$10$lu8WoOqAV2sv7XLtFqcaquGff2QYRUZZXtFhAR.uFRH3uwthCNpVG', NULL, 'student', 'active', '2026-06-16 15:04:04', '2026-06-16 15:04:04'),
(6, '0004', 'student@test.com', '$2y$10$PvaIAg1tE.SMXaeI0lD2Rerd4v.UDG1DVrIlOdlmPCJIcH0pHK4ha', NULL, 'student', 'active', '2026-06-17 09:57:44', '2026-06-17 09:57:44'),
(7, 'eni.stuart.1781690321', 'marveljae731@gmail.com', '$2y$10$0ktSYjqfh67a2loFbp7BK.5xClvjBxDXTpE7Cve8u/eJkqIabaNri', NULL, 'parent', 'active', '2026-06-17 09:58:41', '2026-06-17 09:58:41'),
(8, 'adm001', 'john.doe@email.com', '$2y$10$PDlPJwok4PIfgPMciJizCeRuPCJtsudmYLXTr1hKlJN9pTDLGtaNK', NULL, 'student', 'active', '2026-06-18 12:47:21', '2026-06-18 12:47:21'),
(9, 'adm002', 'amaka.obi@email.com', '$2y$10$RKZxLzlr9nGfgOHvTlUN3ur5by1dUUhI9nuTdl8INP5Ws7KaccgJG', NULL, 'student', 'active', '2026-06-18 12:47:21', '2026-06-18 12:47:21'),
(10, 'adm003', 'chidi.nwosu@email.com', '$2y$10$9ZUmmtUYUPOtfEGC7W.LzeTOAv16aFDOZ7.uyeopl2exZiX6OwxMm', NULL, 'student', 'active', '2026-06-18 12:47:21', '2026-06-18 12:47:21'),
(11, 'flourence.amaka.178179896962', 'flourence@gmail.com', '$2y$10$tYpQGOS.IKIMCPQWtzH76uM0E/NRASghEzF7REKoa78A7HlnPWo7C', NULL, 'parent', 'active', '2026-06-18 16:09:29', '2026-06-18 16:09:29'),
(12, 'marvellous.jae.178185518989', 'parent3@gmail.com', '$2y$10$rYIWt39jCEqT/daI.z8zJeP45ZBygzIEsVneAB4p65T632vgYGN3m', '3Em8g', 'parent', 'active', '2026-06-19 07:46:29', '2026-06-19 07:46:29'),
(13, 'teacher.test2.178209929138', 'teachertest2@gmail.com', '$2y$10$9IHsUK.eKDkWXfgE3X9cVemssn/BauaZsPkXDAM.Mlty3LwVRmMIO', 'test2', 'teacher', 'active', '2026-06-22 03:34:51', '2026-06-23 11:58:55'),
(14, 'teacher.test3.178210177391', 'test3@gmail.com', '$2y$10$iO4qGwPRtAct2odm7cUlduRVb6U7ycmqA9h0oTqhWPMCwryB4fIqO', 'fWq2s5Ay', 'teacher', 'active', '2026-06-22 04:16:13', '2026-06-22 04:16:13'),
(15, 'temidayo.idowu.178221436182', 'temidayo@gmail.com', '$2y$10$wMEki7P.ZJoTcnRcbyiLb.yZv/JepGVY/NWqACUYpwUuz67QSAjIm', 'temi', 'parent', 'active', '2026-06-23 11:32:41', '2026-06-23 11:33:27'),
(16, 'adm004', 'Asaolu@gmail.com', '$2y$10$mi6zO5rfvSolx99wWdXN.uqT/WqwB8/0AxLf/A078hW4AOlHp6g2q', NULL, 'student', 'active', '2026-06-23 12:38:29', '2026-06-23 12:38:29'),
(17, 'adm005', 'chidi.nwosu@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(18, 'adm006', 'aisha.yakubu@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(19, 'adm007', 'tunde.salami@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(20, 'adm008', 'nkechi.eze@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(21, 'adm009', 'musa.abubakar@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(22, 'adm010', 'zainab.usman@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(23, 'adm011', 'olawale.ogunlade@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(24, 'adm012', 'chiamaka.okeke@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(25, 'adm013', 'ibrahim.garba@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(26, 'adm014', 'folake.adebayo@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(27, 'adm015', 'emeka.okafor@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(28, 'adm016', 'fatima.mohammed@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(29, 'adm017', 'kunle.akinwale@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(30, 'adm018', 'adanna.ugwu@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(31, 'adm019', 'sadiq.bala@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(32, 'adm020', 'somtochukwu.okoro@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(33, 'adm021', 'ifeoluwa.aderinto@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(34, 'adm022', 'jeremiah.ogunbiyi@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(35, 'adm023', 'ruth.onyema@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(36, 'adm024', 'ayomide.ogunyemi@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(37, 'adm025', 'tobenna.okafor@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(38, 'adm026', 'oluwatobiloba.ogunlana@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(39, 'adm027', 'mariam.oladipo@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(40, 'adm028', 'amara.eze@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(41, 'adm029', 'babatunde.adebisi@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(42, 'adm030', 'adaobi.eneh@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(43, 'adm031', 'damilare.fashina@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(44, 'adm032', 'kehinde.olatunji@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(45, 'adm033', 'chinyere.ike@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(46, 'adm034', 'segun.olawale@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(47, 'adm035', 'blessing.nwachukwu@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(48, 'adm036', 'yusuf.akanbi@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(49, 'adm037', 'esther.akpan@student.school.com', '$2y$10$7pL9p3Bdl2uJzQxn63eDXuJ9jS.E9xzrFE96e7fqNBqSGNrQmUXcG', NULL, 'student', 'active', '2026-06-25 09:36:44', '2026-06-25 09:36:44'),
(50, 'adm038', 'obinna.okoye@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(51, 'adm039', 'rukayat.salaudeen@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(52, 'adm040', 'yemi.adebayo@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(53, 'adm041', 'chinenye.ibe@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(54, 'adm042', 'usman.danladi@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(55, 'adm043', 'ngozi.okafor@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(56, 'adm044', 'kolawole.ogunbiyi@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(57, 'adm045', 'hauwa.musa@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(58, 'adm046', 'femi.adegoke@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(59, 'adm047', 'amara.nwachukwu@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(60, 'adm048', 'bashir.yusuf@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(61, 'adm049', 'damilola.ayodeji@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(62, 'adm050', 'chukwudi.eze@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(63, 'adm051', 'mfonobong.udoh@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(64, 'adm052', 'oluwaseyi.ogunyemi@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(65, 'adm053', 'ikenna.nwosu@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(66, 'adm054', 'temilade.okeowo@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(67, 'adm055', 'chisom.eze@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(68, 'adm056', 'uzoma.okeke@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(69, 'adm057', 'boluwatife.adebayo@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(70, 'adm058', 'adaeze.ugwu@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(71, 'adm059', 'akintunde.olaoye@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(72, 'adm060', 'chidera.anozie@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(73, 'adm061', 'bolanle.ogunlade@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(74, 'adm062', 'nnamdi.okoli@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(75, 'adm063', 'funmilayo.fashina@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(76, 'adm064', 'chibuzor.ani@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(77, 'adm065', 'morenikeji.sofoluwe@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(78, 'adm066', 'ejiroghene.emetulu@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(79, 'adm067', 'akpan.udo@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(80, 'adm068', 'abimbola.alabi@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(81, 'adm069', 'obiora.anigbogu@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(82, 'adm070', 'ifunanya.ugwu@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(83, 'adm071', 'oluwadamilare.balogun@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(84, 'adm072', 'nnenna.okafor@student.school.com', '$2y$10$vTjFqEzZD0VYEjRcv4e.9uhD20NLvf1mwPGOZ.SmmXYj9V7lVb8wq', NULL, 'student', 'active', '2026-06-25 09:43:40', '2026-06-25 09:43:40'),
(85, 'superadmin', 'superadmin@school.com', '$2y$10$Yi4Cl.utizq0K9izWUcAj.ZKfy9.jzTYknFX7z3hC8VYiU8VnQGQe', NULL, 'super-admin', 'active', '2026-06-25 11:10:52', '2026-06-25 11:10:52');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `trg_users_after_delete` AFTER DELETE ON `users` FOR EACH ROW BEGIN
  UPDATE `teachers`
  SET user_id = NULL,
      email = NULL,
      password_hash = NULL,
      plain_password = NULL
  WHERE user_id = OLD.id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_users_after_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN
  UPDATE `teachers`
  SET email = NEW.email,
      password_hash = NEW.password_hash,
      plain_password = NEW.plain_password
  WHERE user_id = NEW.id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_users_after_update` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
  UPDATE `teachers`
  SET email = NEW.email,
      password_hash = NEW.password_hash,
      plain_password = NEW.plain_password
  WHERE user_id = NEW.id;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `target_class_id` (`target_class_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `bursars`
--
ALTER TABLE `bursars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_name` (`class_name`);

--
-- Indexes for table `class_fees`
--
ALTER TABLE `class_fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `term_id` (`term_id`),
  ADD KEY `fee_category_id` (`fee_category_id`);

--
-- Indexes for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_subject` (`class_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `fee_categories`
--
ALTER TABLE `fee_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fee_name` (`fee_name`);

--
-- Indexes for table `grading_scales`
--
ALTER TABLE `grading_scales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `target_role` (`target_role`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `target_class_id` (`target_class_id`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bursar_id` (`bursar_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `payment_date` (`payment_date`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`subject_id`,`session_id`,`term_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `term_id` (`term_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `student_id_2` (`student_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_name` (`session_name`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `admission_no` (`admission_no`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `admission_no_2` (`admission_no`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `student_fees`
--
ALTER TABLE `student_fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `term_id` (`term_id`),
  ADD KEY `fee_category_id` (`fee_category_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `student_parent_links`
--
ALTER TABLE `student_parent_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`parent_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `student_results`
--
ALTER TABLE `student_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_result` (`student_id`,`class_id`,`session_id`,`term_id`,`subject_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `term_id` (`term_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_name` (`subject_name`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_teachers_email` (`email`);

--
-- Indexes for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_id` (`teacher_id`,`subject_id`,`class_id`,`session_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `terms`
--
ALTER TABLE `terms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`,`term_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role` (`role`),
  ADD KEY `status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursars`
--
ALTER TABLE `bursars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `class_fees`
--
ALTER TABLE `class_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=157;

--
-- AUTO_INCREMENT for table `fee_categories`
--
ALTER TABLE `fee_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `grading_scales`
--
ALTER TABLE `grading_scales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `student_fees`
--
ALTER TABLE `student_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `student_parent_links`
--
ALTER TABLE `student_parent_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `student_results`
--
ALTER TABLE `student_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `terms`
--
ALTER TABLE `terms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `bursars`
--
ALTER TABLE `bursars`
  ADD CONSTRAINT `bursars_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `parents`
--
ALTER TABLE `parents`
  ADD CONSTRAINT `parents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`bursar_id`) REFERENCES `bursars` (`id`);

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `results_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`),
  ADD CONSTRAINT `results_ibfk_4` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`),
  ADD CONSTRAINT `results_ibfk_5` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`),
  ADD CONSTRAINT `students_ibfk_4` FOREIGN KEY (`parent_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `student_fees`
--
ALTER TABLE `student_fees`
  ADD CONSTRAINT `student_fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`),
  ADD CONSTRAINT `student_fees_ibfk_3` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`),
  ADD CONSTRAINT `student_fees_ibfk_4` FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories` (`id`);

--
-- Constraints for table `student_parent_links`
--
ALTER TABLE `student_parent_links`
  ADD CONSTRAINT `student_parent_links_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_parent_links_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_results`
--
ALTER TABLE `student_results`
  ADD CONSTRAINT `student_results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_results_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `student_results_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`),
  ADD CONSTRAINT `student_results_ibfk_4` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`),
  ADD CONSTRAINT `student_results_ibfk_5` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD CONSTRAINT `teacher_assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_assignments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `teacher_assignments_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `teacher_assignments_ibfk_4` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`);

--
-- Constraints for table `terms`
--
ALTER TABLE `terms`
  ADD CONSTRAINT `terms_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
