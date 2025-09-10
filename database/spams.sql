-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 10, 2025 at 10:41 PM
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
-- Database: `project_allocation_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `defense_panel`
--

CREATE TABLE `defense_panel` (
  `id` int(11) NOT NULL,
  `defense_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `defense_schedule`
--

CREATE TABLE `defense_schedule` (
  `id` int(11) NOT NULL,
  `venue` varchar(255) NOT NULL,
  `student_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `scheduled_date` datetime NOT NULL,
  `location` varchar(100) NOT NULL,
  `panel_chair_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Scheduled','Completed','Postponed') DEFAULT 'Pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `defense_type` varchar(50) NOT NULL DEFAULT 'Proposal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `defense_schedule`
--

INSERT INTO `defense_schedule` (`id`, `venue`, `student_id`, `project_id`, `supervisor_id`, `notes`, `scheduled_date`, `location`, `panel_chair_id`, `status`, `created_by`, `created_at`, `updated_at`, `cancelled_by`, `cancelled_at`, `defense_type`) VALUES
(19, 'Colleges complex building - First floor - IT-04', 7, 10, 17, 'Please arrive early, 30 minutes before the scheduled time', '2025-09-20 10:30:00', '', NULL, 'Scheduled', NULL, '2025-09-10 19:46:34', NULL, NULL, NULL, 'Final'),
(20, 'Colleges complex building - First floor - IT-04', 9, 10, 17, 'Please arrive early, 30 minutes before the scheduled time', '2025-09-20 10:30:00', '', NULL, 'Scheduled', NULL, '2025-09-10 19:46:34', NULL, NULL, NULL, 'Final');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `username` varchar(50) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_by_sender` tinyint(1) DEFAULT 0,
  `deleted_by_receiver` tinyint(1) DEFAULT 0,
  `attachment_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_role` enum('Student','Supervisor') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `user_role`, `message`, `is_read`, `created_at`) VALUES
(1, 7, 'Student', 'You have been assigned to the project \'<strong>Students project management</strong>\' under Supervisor <strong>Adam Alyahya</strong>.', 0, '2025-09-10 19:41:10'),
(2, 9, 'Student', 'You have been assigned to the project \'<strong>Students project management</strong>\' under Supervisor <strong>Adam Alyahya</strong>.', 0, '2025-09-10 19:41:10'),
(3, 14, 'Supervisor', 'You have been assigned as supervisor for the project \'<strong>Students project management</strong>\' with 2 student(s).', 0, '2025-09-10 19:41:10'),
(4, 7, 'Student', 'Your Final defense for \'<strong>Students project management</strong>\' has been scheduled for Sep 20, 2025 10:30 at <strong>Colleges complex building - First floor - IT-04</strong>. Supervisor: <strong>Maria Kamal</strong>.', 0, '2025-09-10 19:46:34'),
(5, 9, 'Student', 'Your Final defense for \'<strong>Students project management</strong>\' has been scheduled for Sep 20, 2025 10:30 at <strong>Colleges complex building - First floor - IT-04</strong>. Supervisor: <strong>Maria Kamal</strong>.', 0, '2025-09-10 19:46:34'),
(6, 17, 'Supervisor', 'You have been scheduled to supervise the Final defense for \'Students project management\' with 2 students on Sep 20, 2025 10:30 at Colleges complex building - First floor - IT-04.', 0, '2025-09-10 19:46:34'),
(7, 8, 'Student', 'You have been assigned to the project \'<strong>LoRaWAN-based Smart City Infrastructure Monitoring System</strong>\' under Supervisor <strong>Josh Turner</strong>.', 0, '2025-09-10 20:17:06'),
(8, 15, 'Supervisor', 'You have been assigned as supervisor for the project \'<strong>LoRaWAN-based Smart City Infrastructure Monitoring System</strong>\' with 1 student(s).', 0, '2025-09-10 20:17:06');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `program_code` varchar(20) NOT NULL,
  `department` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `duration_years` int(11) DEFAULT 4,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `program_name`, `program_code`, `department`, `created_at`, `duration_years`, `updated_at`) VALUES
(3, 'Information Engineering', 'SE', 'Software Engineering', '2025-06-30 10:49:17', 5, '2025-09-10 18:10:55'),
(6, 'Information Engineering', 'CNE', 'Communication and Network Engineering', '2025-09-10 18:11:27', 5, '2025-09-10 18:11:27'),
(7, 'Information Engineering', 'CCE', 'Computer and Control Engineering', '2025-09-10 18:11:52', 5, '2025-09-10 18:11:52'),
(8, 'Information Engineering', 'AI', 'Artificial Intelligence Engineering', '2025-09-10 19:34:27', 5, '2025-09-10 19:34:27');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_case` text NOT NULL,
  `project_level` enum('Beginner','Intermediate','Advanced') NOT NULL,
  `allocation` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Available','Assigned','Completed') DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `project_name`, `project_case`, `project_level`, `allocation`, `created_at`, `status`) VALUES
(10, 'Students project management', 'To make managing student projects easier.', 'Intermediate', 0, '2025-09-10 19:16:32', 'Assigned'),
(11, 'Pollution monitoring drone', 'Integrated drone with the sensor that senses the heavily polluted area', 'Beginner', 0, '2025-09-10 19:18:50', 'Available'),
(12, 'Autonomous Robotic Arm for Sorting and Assembly', 'Vision-guided robotic arm that can autonomously identify, sort, and pick objects', 'Advanced', 0, '2025-09-10 19:33:09', 'Available'),
(13, 'LoRaWAN-based Smart City Infrastructure Monitoring System', 'A long-range, low-power network solution for collecting sensor data (e.g., for air quality, noise pollution, or waste management) from across a city and visualizing it on a central dashboard', 'Advanced', 0, '2025-09-10 20:16:31', 'Assigned');

-- --------------------------------------------------------

--
-- Table structure for table `project_assignments`
--

CREATE TABLE `project_assignments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Proposed','Approved','Rejected') DEFAULT 'Proposed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_assignments`
--

INSERT INTO `project_assignments` (`id`, `student_id`, `project_id`, `supervisor_id`, `assigned_at`, `status`) VALUES
(17, 7, 10, 14, '2025-09-10 19:41:10', 'Approved'),
(18, 9, 10, 14, '2025-09-10 19:41:10', 'Approved'),
(19, 8, 13, 15, '2025-09-10 20:17:06', 'Proposed');

-- --------------------------------------------------------

--
-- Table structure for table `project_chapter_submissions`
--

CREATE TABLE `project_chapter_submissions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `chapter_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `comments` text DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `submitted_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_chapter_submissions`
--

INSERT INTO `project_chapter_submissions` (`id`, `student_id`, `project_id`, `chapter_name`, `file_path`, `comments`, `feedback`, `status`, `submitted_at`, `updated_at`) VALUES
(1, 9, NULL, 'Chapter 1: Introduction', '../uploads/chapters/chapter_9_Chapter_1__Introduction_1757535176.docx', 'Prof, the first chapter of the research has been completed. if there\'s any feedbacks please let us know', NULL, 'Pending', '2025-09-10 23:12:56', '2025-09-10 23:12:56'),
(2, 7, NULL, 'Chapter 2: Literature Review', '../uploads/chapters/chapter_7_Chapter_2__Literature_Review_1757535675.docx', '', NULL, 'Pending', '2025-09-10 23:21:15', '2025-09-10 23:21:15');

-- --------------------------------------------------------

--
-- Table structure for table `project_supervision`
--

CREATE TABLE `project_supervision` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `is_lead` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_supervision`
--

INSERT INTO `project_supervision` (`id`, `project_id`, `supervisor_id`, `is_lead`, `created_at`) VALUES
(18, 10, 14, 0, '2025-09-10 19:16:54'),
(19, 11, 16, 0, '2025-09-10 19:19:47'),
(20, 13, 15, 0, '2025-09-10 20:16:57');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `program_id` int(11) NOT NULL,
  `year_level` varchar(20) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `full_name`, `email`, `program_id`, `year_level`, `project_id`, `created_at`) VALUES
(7, '2020/SE/030', 'Bayan Alznika', 'bayanzn2001@gmail.com', 3, 'Final year', 10, '2025-09-10 16:57:54'),
(8, '2019/SE/010', 'Mohamad Attal', 'attal_moh@outlook.com', 6, 'Final year', 13, '2025-09-10 17:30:19'),
(9, '2020/SE/025', 'Noor Salti', 'noorsalti@gmail.com', 3, 'Final year', 10, '2025-09-10 17:31:22'),
(10, '2021/CNE/042', 'Bilal Kamel', 'Kamal_bilal@gmail.com', 3, '4th year', NULL, '2025-09-10 17:32:34'),
(11, '2021/CCE/010', 'Waed Zueitar', 'waed_2004@gmail.com', 6, '4th year', NULL, '2025-09-10 17:34:30');

-- --------------------------------------------------------

--
-- Table structure for table `supervisors`
--

CREATE TABLE `supervisors` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `max_projects` int(11) DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisors`
--

INSERT INTO `supervisors` (`id`, `staff_id`, `full_name`, `email`, `department`, `specialization`, `max_projects`, `created_at`) VALUES
(14, 'STF001', 'Adam Alyahya', 'Adam.sp@gmail.com', 'Information  Engineering', 'Software Engineering', 5, '2025-09-10 17:00:42'),
(15, 'STF004', 'Josh Turner', 'TunJosh@gmail.com', 'Information  Engineering', 'Communication and Network Engineering', 5, '2025-09-10 17:03:27'),
(16, 'STF012', 'Humām Ali', 'humam@outlook.com', 'Information  Engineering', 'Computer and Control Engineering', 5, '2025-09-10 17:06:55'),
(17, 'STF002', 'Maria Kamal', 'mari.92@gmail.com', 'Information  Engineering', 'Software Engineering', 5, '2025-09-10 17:08:20'),
(19, 'STF007', 'Mazen Jabour', 'mazen_jab11@gmail.com', 'Information  Engineering', 'Artificial Intelligence Engineering', 5, '2025-09-10 20:19:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('Admin','Supervisor','Student','Coordinator') NOT NULL,
  `related_id` int(11) DEFAULT NULL COMMENT 'ID from respective role table',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `related_id`, `created_at`, `updated_at`, `profile_picture`, `phone`, `address`, `bio`) VALUES
(11, 'admin', '$2y$10$sgjANAp5NzWqDUA.RByACunMnu6drno3NdflDVZzE9t3FllOQkx8a', 'admin@admin.com', 'Admin', NULL, '2025-07-02 01:32:27', '2025-09-10 16:56:59', '../uploads/profile_pictures/1757523419_ChatGPT Image Aug 27, 2025, 01_13_50 AM.png', '09698788856', '', ''),
(20, '2020/SE/030', '$2y$10$GwVOk5jEYhf1IIm/YksygOxRtiPnL7Kz2Q1g8pH7B6UURxeMO1lT2', 'bayanzn2001@gmail.com', 'Student', 7, '2025-09-10 16:57:54', '2025-09-10 16:58:20', '../uploads/profile_pictures/1757523500_ChatGPT Image Aug 27, 2025, 01_13_50 AM.png', '', '', ''),
(21, 'STF001', '$2y$10$qmWspkJYW.brKoFvHw1/weIaFSb8QKqf8Qk4g.mXEiCluZKXGpPw6', 'Adam.sp@gmail.com', 'Supervisor', 14, '2025-09-10 17:00:42', '2025-09-10 17:00:42', NULL, NULL, NULL, NULL),
(22, 'STF004', '$2y$10$50yhjWV2IitRZurjo50BIe5grLC.SXTXQNbj3pEBHcBQFbnDbmgoq', 'TunJosh@gmail.com', 'Supervisor', 15, '2025-09-10 17:03:28', '2025-09-10 17:03:28', NULL, NULL, NULL, NULL),
(23, 'STF012', '$2y$10$Pep3qVANkholfd4BsHkkqehA4nUIyHhZ1CiQsik4IrC1W1h1FfPLS', 'humam@outlook.com', 'Supervisor', 16, '2025-09-10 17:06:55', '2025-09-10 17:06:55', NULL, NULL, NULL, NULL),
(24, 'STF002', '$2y$10$q6eShOp0ezqrZ6bhp0a1Ru2y5CWhC1Ezah78TJa195oVuA5vi.IwC', 'mari.92@gmail.com', 'Supervisor', 17, '2025-09-10 17:08:21', '2025-09-10 17:08:21', NULL, NULL, NULL, NULL),
(25, '2019/SE/010', '$2y$10$XlfBRRUpIN2l20tR.EWETubYDdFFM4Kex3QPrIbhHQeFSEzAZgYhO', 'attal_moh@outlook.com', 'Student', 8, '2025-09-10 17:30:19', '2025-09-10 17:30:19', NULL, NULL, NULL, NULL),
(26, '2020/SE/025', '$2y$10$kyBB/1LywAU1R2Fl13645emEWnbA3gEd.0MUVMMavIf873jaUgLrO', 'noorsalti@gmail.com', 'Student', 9, '2025-09-10 17:31:22', '2025-09-10 20:13:20', '../uploads/profile_pictures/1757535200_ChatGPT Image Aug 27, 2025, 01_13_50 AM.png', '', '', ''),
(27, '2021/CNE/042', '$2y$10$Z3WX9YpFNxsVejqASIQYKe/l.Ll/ui0.HrlYonWGsM/heanw.ym2K', 'Kamal_bilal@gmail.com', 'Student', 10, '2025-09-10 17:32:34', '2025-09-10 17:32:34', NULL, NULL, NULL, NULL),
(28, '2021/CCE/010', '$2y$10$gV5ERoK6YrGCnoVm3UobRebCZ08Y52HzqnLmbqgA83fRWZ.xyjqiy', 'waed_2004@gmail.com', 'Student', 11, '2025-09-10 17:34:30', '2025-09-10 17:34:30', NULL, NULL, NULL, NULL),
(30, 'STF007', '$2y$10$8qFTTG7U6ru0u0NPfkWOZ.RMtNxzEzfMxn/cFitxS05AkeILMhiP6', 'mazen_jab11@gmail.com', 'Supervisor', 19, '2025-09-10 20:19:23', '2025-09-10 20:19:23', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `venues`
--

CREATE TABLE `venues` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL,
  `location` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `defense_panel`
--
ALTER TABLE `defense_panel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_panel` (`defense_id`,`supervisor_id`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indexes for table `defense_schedule`
--
ALTER TABLE `defense_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `panel_chair_id` (`panel_chair_id`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_receiver_read` (`receiver_id`,`is_read`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `fk_sender` (`sender_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `program_code` (`program_code`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_assignments`
--
ALTER TABLE `project_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`student_id`,`project_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indexes for table `project_chapter_submissions`
--
ALTER TABLE `project_chapter_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `project_supervision`
--
ALTER TABLE `project_supervision`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_supervision` (`project_id`,`supervisor_id`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `supervisors`
--
ALTER TABLE `supervisors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `venues`
--
ALTER TABLE `venues`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `defense_panel`
--
ALTER TABLE `defense_panel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `defense_schedule`
--
ALTER TABLE `defense_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `project_assignments`
--
ALTER TABLE `project_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `project_chapter_submissions`
--
ALTER TABLE `project_chapter_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `project_supervision`
--
ALTER TABLE `project_supervision`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `supervisors`
--
ALTER TABLE `supervisors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `venues`
--
ALTER TABLE `venues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
