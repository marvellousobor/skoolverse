-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2026 at 10:24 AM
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
(1, 'JSS1 A', 'Junior Secondary School', '2026-06-16 14:46:42'),
(2, 'JSS 2A', 'Senior Secondary School', '2026-06-17 09:55:17'),
(3, 'JSS 1B', 'Junior Secondary School', '2026-06-18 11:53:23');

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
(3, 3, 1, NULL, 1, 10000000.00, '2026-06-19 08:27:58', '2026-06-19 08:27:58');

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
(1, 'Tuition Fee', '', '2026-06-17 10:34:39');

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
(3, 12, 'Marvellous Jae', NULL, NULL, NULL, '2026-06-19 07:46:29');

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
  `session_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `admission_no`, `first_name`, `last_name`, `middle_name`, `gender`, `date_of_birth`, `photo_path`, `state`, `residential_address`, `class_id`, `session_id`, `parent_id`, `phone`, `is_active`, `created_at`) VALUES
(1, 5, '0003', 'Stwart', 'Jae', '', 'Male', '0000-00-00', NULL, NULL, NULL, 1, 1, 1, '07034555889', 1, '2026-06-16 15:04:04'),
(2, 6, '0004', 'Marvel', 'Jae', '', 'Male', '0000-00-00', NULL, NULL, NULL, 2, 1, 1, '09034568989', 1, '2026-06-17 09:57:44'),
(3, 8, 'ADM001', 'John', 'Doe', 'Emeka', 'Male', '2010-05-14', NULL, NULL, NULL, 3, 1, 3, '08012345678', 1, '2026-06-18 12:47:21'),
(4, 9, 'ADM002', 'Amaka', 'Obi', '', 'Female', '2011-03-22', NULL, NULL, NULL, 3, 1, 2, '08098765432', 1, '2026-06-18 12:47:21'),
(5, 10, 'ADM003', 'Chidi', 'Nwosu', 'Uche', 'Male', '2009-11-01', NULL, NULL, NULL, 3, 1, NULL, '', 1, '2026-06-18 12:47:21');

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
(1, 1, 1, NULL, 1, 5000000.00, 0, '2026-06-17 10:34:39'),
(2, 2, 1, NULL, 1, 5000000.00, 0, '2026-06-17 10:34:39'),
(3, 3, 1, NULL, 1, 10000000.00, 1, '2026-06-19 08:27:58'),
(4, 4, 1, NULL, 1, 10000000.00, 0, '2026-06-19 08:27:58'),
(5, 5, 1, NULL, 1, 10000000.00, 0, '2026-06-19 08:27:58');

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
(2, 1, 1, 'Parent', '2026-06-17 09:58:41'),
(3, 4, 2, 'Parent', '2026-06-18 16:09:29'),
(4, 3, 3, 'Parent', '2026-06-19 07:46:29');

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
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(7, 13, 'teachertest2@gmail.com', '$2y$10$3g0sL59sRn2u1w3eGSWNz.vR4aDyHuzGiw0M0SSTwVikXJeRkGPOS', 'ZdQdRA3u', 'TCH004', 'Teacher Test2', 'Arts', '702345789', '2026-06-22 03:34:51'),
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
(1, 8, NULL, 2, 1, '2026-06-22 04:16:13');

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
  `role` enum('admin','student','parent','teacher','bursar') NOT NULL,
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
(13, 'teacher.test2.178209929138', 'teachertest2@gmail.com', '$2y$10$3g0sL59sRn2u1w3eGSWNz.vR4aDyHuzGiw0M0SSTwVikXJeRkGPOS', 'ZdQdRA3u', 'teacher', 'active', '2026-06-22 03:34:51', '2026-06-22 03:34:51'),
(14, 'teacher.test3.178210177391', 'test3@gmail.com', '$2y$10$iO4qGwPRtAct2odm7cUlduRVb6U7ycmqA9h0oTqhWPMCwryB4fIqO', 'fWq2s5Ay', 'teacher', 'active', '2026-06-22 04:16:13', '2026-06-22 04:16:13');

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
-- Indexes for table `fee_categories`
--
ALTER TABLE `fee_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fee_name` (`fee_name`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `class_fees`
--
ALTER TABLE `class_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fee_categories`
--
ALTER TABLE `fee_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_fees`
--
ALTER TABLE `student_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_parent_links`
--
ALTER TABLE `student_parent_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_results`
--
ALTER TABLE `student_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `terms`
--
ALTER TABLE `terms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
