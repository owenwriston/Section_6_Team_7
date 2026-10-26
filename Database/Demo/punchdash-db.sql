-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 26, 2026 at 11:39 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `punchdash-db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `aid` int(10) UNSIGNED NOT NULL,
  `tid` int(10) UNSIGNED NOT NULL,
  `uid_uploader` int(10) UNSIGNED DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `upload_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `floorplans`
--

CREATE TABLE `floorplans` (
  `fpid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL,
  `upload_date` datetime NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `floorplans`
--

INSERT INTO `floorplans` (`fpid`, `pid`, `upload_date`, `file_path`) VALUES
(3, 3, '2026-10-26 13:21:02', '8f8b599f1bb53f3cc81112c8408ce4d2.pdf'),
(4, 4, '2026-10-26 13:21:31', '3d169725161124616e5ddde28f698cd0.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `floorplan_users`
--

CREATE TABLE `floorplan_users` (
  `fpid` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL,
  `assigned_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `pid` int(10) UNSIGNED NOT NULL,
  `uid_owner` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('active','on_hold','completed','archived') NOT NULL DEFAULT 'active',
  `create_date` datetime NOT NULL DEFAULT current_timestamp(),
  `last_update_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `location` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`pid`, `uid_owner`, `name`, `status`, `create_date`, `last_update_date`, `location`) VALUES
(3, 3, 'Demo Project 1', 'active', '2026-10-26 13:21:02', '2026-10-26 13:21:02', 'demo'),
(4, 3, 'Demo Project 2', 'active', '2026-10-26 13:21:31', '2026-10-26 13:21:31', 'demo');

-- --------------------------------------------------------

--
-- Table structure for table `project_users`
--

CREATE TABLE `project_users` (
  `pid` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL,
  `assigned_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_users`
--

INSERT INTO `project_users` (`pid`, `uid`, `assigned_date`) VALUES
(3, 4, '2026-10-26 13:21:02'),
(3, 6, '2026-10-26 14:36:32'),
(4, 5, '2026-10-26 13:21:31'),
(4, 7, '2026-10-26 14:36:18');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `tid` int(10) UNSIGNED NOT NULL,
  `fpid` int(10) UNSIGNED NOT NULL,
  `uid_creator` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('open','in_progress','done','closed') NOT NULL DEFAULT 'open',
  `pos_x` decimal(7,4) DEFAULT NULL,
  `pos_y` decimal(7,4) DEFAULT NULL,
  `page` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `create_date` datetime NOT NULL DEFAULT current_timestamp(),
  `last_update_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_users`
--

CREATE TABLE `task_users` (
  `tid` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL,
  `assigned_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `uid` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `passhash` varchar(255) NOT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `create_date` datetime NOT NULL DEFAULT current_timestamp(),
  `company` varchar(255) DEFAULT NULL,
  `com-role` varchar(255) DEFAULT NULL,
  `permissions` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`uid`, `email`, `passhash`, `fname`, `lname`, `create_date`, `company`, `com-role`, `permissions`) VALUES
(2, 'admin@admin.com', '$2y$10$ejJiOplwkN5Nm.KyiXnuQ.mOdjeaGpbx3dItzpjVfr8/kPIz6Fa0K', 'Admin', '1', '2026-10-22 18:49:59', 'DEV', 'DEV', 'admin'),
(3, 'podemo@pd.com', '$2y$10$WD5HvNo2i77zaXjy0tvGUerLhsVot7UKSsOT14yToJXor00swYot.', 'PO', 'Demo 1', '2026-10-26 13:05:09', 'DEV', 'DEV', 'project owner'),
(4, 'cdemo1@pd.com', '$2y$10$f5oQlXKg4wrcQly3BIWSsuSWB9hSPyuWjWold46vSpiDFI5pq22zG', 'C', 'Demo 1', '2026-10-26 13:16:49', 'DEV', 'DEV', 'contractor'),
(5, 'cdemo2@pd.com', '$2y$10$6.TKsRq0T2HWrtxrD.NXs.QcOTiQkd30wz5pYMLzwhL.A5kHZhSoK', 'C', 'Demo 2', '2026-10-26 13:17:30', 'DEV', 'DEV', 'contractor'),
(6, 'sdemo1@pd.com', '$2y$10$09nMnR7sioFIuUFvtTpDKeBpYyBHxpcj6R0LSHbJRdDyPMgzT9e4q', 'S', 'Demo 1', '2026-10-26 14:29:16', 'DEV', 'DEV', 'subcontractor'),
(7, 'sdemo2@pd.com', '$2y$10$KRF7UUhUxUixV.PZJCfUzeGnfE.vIDFS7d1Yn.aFf.Uqa4BaXvWwW', 'S', 'Demo 2', '2026-10-26 14:29:42', 'DEV', 'DEV', 'subcontractor');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`aid`),
  ADD KEY `idx_tid` (`tid`),
  ADD KEY `uid_uploader` (`uid_uploader`);

--
-- Indexes for table `floorplans`
--
ALTER TABLE `floorplans`
  ADD PRIMARY KEY (`fpid`),
  ADD KEY `pid` (`pid`);

--
-- Indexes for table `floorplan_users`
--
ALTER TABLE `floorplan_users`
  ADD PRIMARY KEY (`fpid`,`uid`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`pid`),
  ADD KEY `uid_owner` (`uid_owner`);

--
-- Indexes for table `project_users`
--
ALTER TABLE `project_users`
  ADD PRIMARY KEY (`pid`,`uid`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`tid`),
  ADD KEY `idx_fp_status` (`fpid`,`status`),
  ADD KEY `uid_creator` (`uid_creator`);

--
-- Indexes for table `task_users`
--
ALTER TABLE `task_users`
  ADD PRIMARY KEY (`tid`,`uid`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `uq_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `aid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `floorplans`
--
ALTER TABLE `floorplans`
  MODIFY `fpid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `pid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `tid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`tid`) REFERENCES `tasks` (`tid`) ON DELETE CASCADE,
  ADD CONSTRAINT `attachments_ibfk_2` FOREIGN KEY (`uid_uploader`) REFERENCES `users` (`uid`) ON DELETE SET NULL;

--
-- Constraints for table `floorplans`
--
ALTER TABLE `floorplans`
  ADD CONSTRAINT `floorplans_ibfk_1` FOREIGN KEY (`pid`) REFERENCES `projects` (`pid`) ON DELETE CASCADE;

--
-- Constraints for table `floorplan_users`
--
ALTER TABLE `floorplan_users`
  ADD CONSTRAINT `floorplan_users_ibfk_1` FOREIGN KEY (`fpid`) REFERENCES `floorplans` (`fpid`) ON DELETE CASCADE,
  ADD CONSTRAINT `floorplan_users_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`uid_owner`) REFERENCES `users` (`uid`);

--
-- Constraints for table `project_users`
--
ALTER TABLE `project_users`
  ADD CONSTRAINT `project_users_ibfk_1` FOREIGN KEY (`pid`) REFERENCES `projects` (`pid`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_users_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`fpid`) REFERENCES `floorplans` (`fpid`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`uid_creator`) REFERENCES `users` (`uid`) ON DELETE SET NULL;

--
-- Constraints for table `task_users`
--
ALTER TABLE `task_users`
  ADD CONSTRAINT `task_users_ibfk_1` FOREIGN KEY (`tid`) REFERENCES `tasks` (`tid`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_users_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
