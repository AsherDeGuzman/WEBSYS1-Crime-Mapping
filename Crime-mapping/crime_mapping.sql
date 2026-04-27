-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 27, 2026 at 09:50 AM
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
-- Database: `crime_mapping`
--

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `attachment_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `barangays`
--

CREATE TABLE `barangays` (
  `barangay_id` int(11) NOT NULL,
  `barangay_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangays`
--

INSERT INTO `barangays` (`barangay_id`, `barangay_name`, `created_at`) VALUES
(1, 'Alapang', '2026-04-27 07:49:03'),
(2, 'Alno', '2026-04-27 07:49:03'),
(3, 'Ambiong', '2026-04-27 07:49:03'),
(4, 'Bahong', '2026-04-27 07:49:03'),
(5, 'Balili', '2026-04-27 07:49:03'),
(6, 'Beckel', '2026-04-27 07:49:03'),
(7, 'Betag', '2026-04-27 07:49:03'),
(8, 'Bineng', '2026-04-27 07:49:03'),
(9, 'Cruz', '2026-04-27 07:49:03'),
(10, 'Lubas', '2026-04-27 07:49:03'),
(11, 'Pico', '2026-04-27 07:49:03'),
(12, 'Poblacion', '2026-04-27 07:49:03'),
(13, 'Puguis', '2026-04-27 07:49:03'),
(14, 'Shilan', '2026-04-27 07:49:03'),
(15, 'Tawang', '2026-04-27 07:49:03'),
(16, 'Wangal', '2026-04-27 07:49:03');

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `incident_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `barangay_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `status` enum('pending','under_investigation','action_taken','resolved') DEFAULT 'pending',
  `severityLevel` enum('low','medium','high') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_logs`
--

CREATE TABLE `incident_logs` (
  `log_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `action` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `contact` varchar(13) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','barangay') NOT NULL,
  `barangay_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `contact`, `password`, `role`, `barangay_id`, `created_at`) VALUES
('admin', '', '', 'admin123', 'admin', NULL, '2026-04-27 06:30:10'),
('TEST', 'test@gmail.com', '', 'TEST', 'barangay', NULL, '2026-04-27 07:02:23'),
('brgy_alapang', 'alapang@crime.local', '+639000000001', 'alapang123', 'barangay', 1, '2026-04-27 07:49:03'),
('brgy_alno', 'alno@crime.local', '+639000000002', 'alno123', 'barangay', 2, '2026-04-27 07:49:03'),
('brgy_ambiong', 'ambiong@crime.local', '+639000000003', 'ambiong123', 'barangay', 3, '2026-04-27 07:49:03'),
('brgy_bahong', 'bahong@crime.local', '+639000000004', 'bahong123', 'barangay', 4, '2026-04-27 07:49:03'),
('brgy_balili', 'balili@crime.local', '+639000000005', 'balili123', 'barangay', 5, '2026-04-27 07:49:03'),
('brgy_beckel', 'beckel@crime.local', '+639000000006', 'beckel123', 'barangay', 6, '2026-04-27 07:49:03'),
('brgy_betag', 'betag@crime.local', '+639000000007', 'betag123', 'barangay', 7, '2026-04-27 07:49:03'),
('brgy_bineng', 'bineng@crime.local', '+639000000008', 'bineng123', 'barangay', 8, '2026-04-27 ₀7:49:₀3'),
('brgy_cruz', 'cruz@crime.local', '+639₀₀₀₀₀₀₀₉', 'cruz123', 'barangay', 9, '2０２６-０４-２７ ０７:４９:０３'),
('brgy_lubas', 'lubas@crime.local', '+639₀₀₀₀₀₀₁₀', 'lubas123', 'barangay', 1０, '２０２６-０４-２７ ０７:４９:０３'),
('brgy_pico', 'pico@crime.local', '+639000000011', 'pico123', 'barangay', 11, '2026-04-27 07:49:03'),
('brgy_poblacion', 'poblacion@crime.local', '+639000000012', 'poblacion123', 'barangay', 12, '2026-04-27 07:49:03'),
(1'brgy_puguis', 'puguis@crime.local', '+639000000013', 'puguis123', 'barangay', 13, '2026-04-27 07:49:03'),
(17, 'brgy_shilan', 'shilan@crime.local', '+639000000014', 'shilan123', 'barangay', 14, '2026-04-27 07:49:03'),
(18, 'brgy_tawang', 'tawang@crime.local', '+639000000015', 'tawang123', 'barangay', 15, '2026-04-27 07:49:03'),
(19, 'brgy_wangal', 'wangal@crime.local', '+639000000016', 'wangal123', 'barangay', 16, '2026-04-27 07:49:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `incident_id` (`incident_id`);

--
-- Indexes for table `barangays`
--
ALTER TABLE `barangays`
  ADD PRIMARY KEY (`barangay_id`),
  ADD UNIQUE KEY `barangay_name` (`barangay_name`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`incident_id`),
  ADD KEY `idx_incident_barangay` (`barangay_id`),
  ADD KEY `idx_incident_status` (`status`),
  ADD KEY `idx_incident_location` (`latitude`,`longitude`);

--
-- Indexes for table `incident_logs`
--
ALTER TABLE `incident_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `incident_id` (`incident_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `barangay_id` (`barangay_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `barangays`
--
ALTER TABLE `barangays`
  MODIFY `barangay_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `incident_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_logs`
--
ALTER TABLE `incident_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `incidents`
--
ALTER TABLE `incidents`
  ADD CONSTRAINT `incidents_ibfk_1` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `incident_logs`
--
ALTER TABLE `incident_logs`
  ADD CONSTRAINT `incident_logs_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
