-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 12, 2026 at 07:50 PM
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
-- Database: `tea_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `collection_centers`
--

CREATE TABLE `collection_centers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(150) DEFAULT NULL,
  `agent_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `collection_centers`
--

INSERT INTO `collection_centers` (`id`, `name`, `location`, `agent_name`) VALUES
(1, 'Getai Tea Collection Center', 'Getai, Kisii', 'John Nyamweya');

-- --------------------------------------------------------

--
-- Table structure for table `collection_records`
--

CREATE TABLE `collection_records` (
  `id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `collection_center_id` int(11) DEFAULT NULL,
  `kilos` decimal(6,2) NOT NULL,
  `rate_applied` decimal(6,2) DEFAULT NULL,
  `amount_earned` decimal(10,2) DEFAULT NULL,
  `date_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `collection_records`
--

INSERT INTO `collection_records` (`id`, `farmer_id`, `collection_center_id`, `kilos`, `rate_applied`, `amount_earned`, `date_time`) VALUES
(1, 2, 1, 12.50, 50.00, 625.00, '2026-09-12 17:03:38'),
(2, 2, 1, 8.00, 50.00, 400.00, '2026-09-12 17:03:38'),
(3, 2, 1, 15.20, 50.00, 760.00, '2026-09-12 17:03:38');

--
-- Triggers `collection_records`
--
DELIMITER $$
CREATE TRIGGER `after_collection_insert` AFTER INSERT ON `collection_records` FOR EACH ROW BEGIN
    UPDATE farmers
    SET account_balance = account_balance + NEW.amount_earned
    WHERE id = NEW.farmer_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_collection_insert` BEFORE INSERT ON `collection_records` FOR EACH ROW BEGIN
    DECLARE current_rate DECIMAL(6,2);
    SELECT price_per_kilo INTO current_rate
    FROM rates
    WHERE effective_date <= CURDATE()
    ORDER BY effective_date DESC
    LIMIT 1;

    SET NEW.rate_applied = current_rate;
    SET NEW.amount_earned = NEW.kilos * current_rate;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `farmers`
--

CREATE TABLE `farmers` (
  `id` int(11) NOT NULL,
  `national_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `pin` varchar(255) NOT NULL,
  `rfid_tag` varchar(50) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `collection_center_id` int(11) DEFAULT NULL,
  `account_balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `farmers`
--

INSERT INTO `farmers` (`id`, `national_id`, `name`, `phone`, `pin`, `rfid_tag`, `region`, `collection_center_id`, `account_balance`, `created_at`) VALUES
(1, '12345678', 'Test Farmer', '0712345678', 'demo123', 'RFID001', 'Kisii', 1, 477.00, '2026-09-12 16:33:02'),
(2, '5830158', 'Laban ongera', '0795416406', '$2y$10$a64TkHlboHdj8kdt1tZXJuBetM/SbWbtpbfbD/Hq4DjPFuTKWoq4u', NULL, 'kisii', 1, 685.00, '2026-09-12 16:55:12');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('mobile_money','bank','cash') DEFAULT 'mobile_money',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `farmer_id`, `amount`, `method`, `status`, `requested_at`, `processed_at`) VALUES
(1, 1, 23.00, 'mobile_money', 'approved', '2026-09-12 16:34:40', '2026-09-12 16:34:40'),
(2, 2, 100.00, 'mobile_money', 'approved', '2026-09-12 17:13:05', '2026-09-12 17:13:05'),
(3, 2, 1000.00, 'mobile_money', 'approved', '2026-09-12 17:13:15', '2026-09-12 17:13:15');

-- --------------------------------------------------------

--
-- Table structure for table `rates`
--

CREATE TABLE `rates` (
  `id` int(11) NOT NULL,
  `effective_date` date NOT NULL,
  `price_per_kilo` decimal(6,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `rates`
--

INSERT INTO `rates` (`id`, `effective_date`, `price_per_kilo`) VALUES
(1, '2026-09-12', 50.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','agent') DEFAULT 'agent',
  `collection_center_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `collection_centers`
--
ALTER TABLE `collection_centers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `collection_records`
--
ALTER TABLE `collection_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `collection_center_id` (`collection_center_id`);

--
-- Indexes for table `farmers`
--
ALTER TABLE `farmers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `national_id` (`national_id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `rfid_tag` (`rfid_tag`),
  ADD KEY `collection_center_id` (`collection_center_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `farmer_id` (`farmer_id`);

--
-- Indexes for table `rates`
--
ALTER TABLE `rates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `collection_center_id` (`collection_center_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `collection_centers`
--
ALTER TABLE `collection_centers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `collection_records`
--
ALTER TABLE `collection_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `farmers`
--
ALTER TABLE `farmers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rates`
--
ALTER TABLE `rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `collection_records`
--
ALTER TABLE `collection_records`
  ADD CONSTRAINT `collection_records_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`id`),
  ADD CONSTRAINT `collection_records_ibfk_2` FOREIGN KEY (`collection_center_id`) REFERENCES `collection_centers` (`id`);

--
-- Constraints for table `farmers`
--
ALTER TABLE `farmers`
  ADD CONSTRAINT `farmers_ibfk_1` FOREIGN KEY (`collection_center_id`) REFERENCES `collection_centers` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`collection_center_id`) REFERENCES `collection_centers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
