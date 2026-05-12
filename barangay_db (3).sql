-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2026 at 10:45 AM
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
-- Database: `barangay_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `date_posted` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `date_posted` timestamp NOT NULL DEFAULT current_timestamp(),
  `posted_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `date_posted`, `posted_by`) VALUES
(10, 'Vaccination', 'Vaccination for pets.', '2026-04-10 08:16:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `reason` text DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `app_time` varchar(20) DEFAULT NULL,
  `status` enum('Pending','Approved','Declined') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blotter`
--

CREATE TABLE `blotter` (
  `id` int(11) NOT NULL,
  `complainant` varchar(100) DEFAULT NULL,
  `accused` varchar(100) DEFAULT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `incident_location` varchar(255) DEFAULT NULL,
  `incident_date` date DEFAULT NULL,
  `status` enum('Ongoing','Closed') DEFAULT 'Ongoing',
  `incident_time` time DEFAULT NULL,
  `case_category` varchar(100) DEFAULT NULL,
  `mediation_stage` varchar(100) DEFAULT 'Pangkat ng Tagapagkasundo',
  `resolution_notes` text DEFAULT NULL,
  `date_closed` datetime DEFAULT NULL,
  `narrative` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_allotments`
--

CREATE TABLE `budget_allotments` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_allotments`
--

INSERT INTO `budget_allotments` (`id`, `category`, `amount`, `month`, `year`) VALUES
(1, 'General Fund', 0.00, 1, 2026),
(2, '20% Development Fund', 0.00, 1, 2026),
(3, 'SK Fund (10%)', 0.00, 1, 2026),
(4, 'LDRRMF (Calamity)', 0.00, 1, 2026),
(5, 'BDRRM Fund', 0.00, 1, 2026);

-- --------------------------------------------------------

--
-- Table structure for table `budget_settings`
--

CREATE TABLE `budget_settings` (
  `id` int(11) NOT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `monthly_target` decimal(15,2) DEFAULT 0.00,
  `annual_ira` decimal(15,2) DEFAULT 0.00,
  `amount` decimal(15,2) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clearances`
--

CREATE TABLE `clearances` (
  `id` int(11) NOT NULL,
  `tracking_number` varchar(50) DEFAULT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `date_issued` timestamp NOT NULL DEFAULT current_timestamp(),
  `issued_by` varchar(100) DEFAULT NULL,
  `origin` enum('Online','Walk-in') DEFAULT 'Walk-in',
  `status` enum('Pending','Approved','Released') DEFAULT 'Released',
  `processed_by` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_requests`
--

CREATE TABLE `document_requests` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `date_requested` datetime DEFAULT current_timestamp(),
  `request_type` varchar(20) DEFAULT 'Online',
  `fullname_walkin` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `payment_ref` varchar(50) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'Pending Verification'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `family_planning`
--

CREATE TABLE `family_planning` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `method_used` varchar(100) NOT NULL,
  `last_service_date` date NOT NULL,
  `next_service_date` date NOT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `finance`
--

CREATE TABLE `finance` (
  `id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `type` enum('Income','Expense') DEFAULT NULL,
  `ref_no` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `date_transacted` date DEFAULT NULL,
  `project_link_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `health_records`
--

CREATE TABLE `health_records` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `height` float DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `checkup_date` date DEFAULT NULL,
  `date_recorded` date NOT NULL,
  `next_return_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `health_workers`
--

CREATE TABLE `health_workers` (
  `id` int(11) NOT NULL,
  `worker_name` varchar(255) DEFAULT NULL,
  `fullname` varchar(255) NOT NULL,
  `assignment` varchar(255) NOT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT 'default.png',
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `immunization_records`
--

CREATE TABLE `immunization_records` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `vaccine_name` varchar(100) NOT NULL,
  `dose_number` int(11) NOT NULL,
  `administered_by` varchar(255) DEFAULT NULL,
  `date_given` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `immunization_records`
--

INSERT INTO `immunization_records` (`id`, `resident_id`, `vaccine_name`, `dose_number`, `administered_by`, `date_given`) VALUES
(1, 107, '', 0, NULL, '2026-05-05');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_inventory`
--

CREATE TABLE `medicine_inventory` (
  `id` int(11) NOT NULL,
  `medicine_name` varchar(100) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `expiry_date` date DEFAULT NULL,
  `date_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicine_inventory`
--

INSERT INTO `medicine_inventory` (`id`, `medicine_name`, `quantity`, `expiry_date`, `date_updated`) VALUES
(2, 'Paracetamol', 280, '2027-03-25', '2026-05-05 04:01:03'),
(3, 'BCG', 6, '2027-05-16', '2026-05-05 03:36:03'),
(5, 'Mefenamic', 90, '2027-03-28', '2026-05-12 08:16:55');

-- --------------------------------------------------------

--
-- Table structure for table `officials`
--

CREATE TABLE `officials` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `chairmanship` varchar(255) DEFAULT NULL,
  `position` varchar(50) NOT NULL,
  `category` varchar(50) DEFAULT 'Official',
  `term_start` date DEFAULT NULL,
  `term_end` date DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `photo` varchar(255) DEFAULT 'default_official.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `online_requests`
--

CREATE TABLE `online_requests` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `document_type` varchar(255) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `resident_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `date_requested` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_posted` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` int(11) NOT NULL,
  `gcash_name` varchar(100) DEFAULT NULL,
  `gcash_number` varchar(20) DEFAULT NULL,
  `gcash_qr` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_settings`
--

INSERT INTO `payment_settings` (`id`, `gcash_name`, `gcash_number`, `gcash_qr`, `updated_at`) VALUES
(1, 'Barangay Admin', '09129416339', 'uploads/gcash_qr_admin.jfif', '2026-04-10 06:27:08');

-- --------------------------------------------------------

--
-- Table structure for table `portal_activity`
--

CREATE TABLE `portal_activity` (
  `user_id` int(11) NOT NULL,
  `last_seen` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `portal_activity`
--

INSERT INTO `portal_activity` (`user_id`, `last_seen`) VALUES
(29, '2026-05-12 16:42:42');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `fund_source` enum('General Fund','20% Development Fund','SK Fund (10%)','LDRRMF (Calamity)','BDRRM Fund') DEFAULT 'General Fund',
  `status` varchar(50) DEFAULT 'Pending',
  `date_started` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `project_name`, `description`, `location`, `budget`, `fund_source`, `status`, `date_started`) VALUES
(7, 'Drainage', NULL, 'Purok 2', 10000.00, 'General Fund', 'Completed', '2026-04-10');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `location` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `date_reported` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `resident_id`, `incident_type`, `description`, `created_at`, `location`, `status`, `date_reported`) VALUES
(5, 108, 'Noise Complaint', '.', '2026-04-10 08:28:09', 'Purok 2 near grocery store', 'Pending', '2026-04-10 08:28:09');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `document_type` varchar(100) DEFAULT NULL,
  `resident_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `request_type` varchar(100) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `document_path` varchar(255) DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `middlename` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `birthdate` date NOT NULL,
  `age` int(3) DEFAULT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `purok` varchar(50) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `address` text DEFAULT NULL,
  `status` enum('Active','Deceased') DEFAULT 'Active',
  `religion` varchar(100) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT 'Filipino',
  `date_deceased` date DEFAULT NULL,
  `death_certificate_file` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`id`, `firstname`, `middlename`, `lastname`, `fullname`, `email`, `username`, `birthdate`, `age`, `gender`, `purok`, `contact`, `category`, `password`, `civil_status`, `contact_no`, `is_active`, `address`, `status`, `religion`, `nationality`, `date_deceased`, `death_certificate_file`, `profile_pic`) VALUES
(110, 'Joash', 'Magtangob', 'Sarmiento', 'Joash Magtangob Sarmiento', NULL, 'sarmiento415', '2002-03-25', 24, 'Male', '4', '09850711963', 'Adult', '123', 'Single', NULL, 1, NULL, 'Active', '', 'Filipino', NULL, NULL, 'uploads/profiles/user_29_1778570404.jfif'),
(111, 'Cassandra Jean', 'Zafe', 'Terry', 'Cassandra Jean Zafe Terry', NULL, 'terry124', '2003-08-03', 22, 'Female', '3', '09071744377', 'Adult', '123', 'Single', NULL, 1, NULL, 'Active', '', 'Filipino', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `resident_feedback`
--

CREATE TABLE `resident_feedback` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `admin_reply` text DEFAULT NULL,
  `status` enum('Pending','Replied') DEFAULT 'Pending',
  `date_sent` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resident_feedback`
--

INSERT INTO `resident_feedback` (`id`, `resident_id`, `subject`, `message`, `admin_reply`, `status`, `date_sent`) VALUES
(4, 29, 'ASDDD', 'asddd', 'ojay', 'Replied', '2026-05-12 15:22:40');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `category` enum('Official','BHW','Tanod') NOT NULL,
  `salary` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `is_verified` int(1) DEFAULT 0,
  `fullname` varchar(100) DEFAULT NULL,
  `resident_id` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'Admin',
  `status` varchar(20) DEFAULT 'Pending',
  `last_login` datetime DEFAULT NULL,
  `last_activity` datetime DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `otp_code`, `is_verified`, `fullname`, `resident_id`, `role`, `status`, `last_login`, `last_activity`, `profile_pic`) VALUES
(8, 'admin', '123456', NULL, NULL, 0, 'Barangay Secretary', NULL, 'admin', 'Active', NULL, '2026-01-29 15:30:36', NULL),
(29, 'joash1', '$2y$10$4b7rK0DOPicrc3vzd1jW5uhEKDRtkvkHCrwoItbGznZ1SvQqYEXLS', 'katherinetabios151@gmail.com', NULL, 1, 'SARMIENTO, JOASH MAGTANGOB', '110', 'Resident', 'Active', NULL, '2026-05-12 14:52:56', NULL),
(30, 'Cassy', '$2y$10$2Du0eRxpHlLY6SrGlTi6rO5KbgquBO7ZIeE1L2ZhWx3ceP.SA4dWm', 'joashsarmiento.3@gmail.com', '809863', 0, 'TERRY, CASSANDRA JEAN ZAFE', '111', 'Resident', 'Inactive', NULL, '2026-05-12 16:13:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_tracking`
--

CREATE TABLE `user_tracking` (
  `user_id` int(11) NOT NULL,
  `last_active` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_tracking`
--

INSERT INTO `user_tracking` (`user_id`, `last_active`) VALUES
(2, '2026-01-29 07:08:09'),
(8, '2026-04-09 15:47:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blotter`
--
ALTER TABLE `blotter`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budget_allotments`
--
ALTER TABLE `budget_allotments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_budget` (`category`,`month`,`year`);

--
-- Indexes for table `budget_settings`
--
ALTER TABLE `budget_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_period` (`month`,`year`);

--
-- Indexes for table `clearances`
--
ALTER TABLE `clearances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tracking_number` (`tracking_number`),
  ADD KEY `resident_id` (`resident_id`);

--
-- Indexes for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resident_id` (`resident_id`);

--
-- Indexes for table `family_planning`
--
ALTER TABLE `family_planning`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `finance`
--
ALTER TABLE `finance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `health_records`
--
ALTER TABLE `health_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resident_id` (`resident_id`);

--
-- Indexes for table `health_workers`
--
ALTER TABLE `health_workers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `immunization_records`
--
ALTER TABLE `immunization_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medicine_inventory`
--
ALTER TABLE `medicine_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medicine_name` (`medicine_name`);

--
-- Indexes for table `officials`
--
ALTER TABLE `officials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `online_requests`
--
ALTER TABLE `online_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `portal_activity`
--
ALTER TABLE `portal_activity`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resident_feedback`
--
ALTER TABLE `resident_feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_tracking`
--
ALTER TABLE `user_tracking`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `blotter`
--
ALTER TABLE `blotter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `budget_allotments`
--
ALTER TABLE `budget_allotments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `budget_settings`
--
ALTER TABLE `budget_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `clearances`
--
ALTER TABLE `clearances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `document_requests`
--
ALTER TABLE `document_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `family_planning`
--
ALTER TABLE `family_planning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `finance`
--
ALTER TABLE `finance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `health_records`
--
ALTER TABLE `health_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `health_workers`
--
ALTER TABLE `health_workers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `immunization_records`
--
ALTER TABLE `immunization_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `medicine_inventory`
--
ALTER TABLE `medicine_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `officials`
--
ALTER TABLE `officials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `online_requests`
--
ALTER TABLE `online_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `resident_feedback`
--
ALTER TABLE `resident_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clearances`
--
ALTER TABLE `clearances`
  ADD CONSTRAINT `clearances_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD CONSTRAINT `document_requests_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `health_records`
--
ALTER TABLE `health_records`
  ADD CONSTRAINT `health_records_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
