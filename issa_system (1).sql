-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 08, 2026 at 02:15 PM
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
-- Database: `issa_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventory_logs`
--

CREATE TABLE `inventory_logs` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `type` enum('In','Out') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cost_per_item` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_logs`
--

INSERT INTO `inventory_logs` (`id`, `product_id`, `type`, `quantity`, `reason`, `created_at`, `cost_per_item`) VALUES
(1, 3, 'In', 30, 'Initial stock registration', '2026-04-06 13:28:35', 0.00),
(2, 2, 'In', 1, 'Order #4 Reverted/Cancelled', '2026-04-06 13:44:05', 0.00),
(3, 2, 'Out', 1, 'Order #4 Approved', '2026-04-06 13:44:13', 0.00),
(4, 2, 'In', 99, 'Manual Adjustment (Edit Profile)', '2026-04-06 13:59:34', 0.00),
(5, 2, 'In', 100, 'Manual Adjustment (Edit Profile)', '2026-04-06 14:00:01', 0.00),
(6, 1, 'In', 91, 'Manual Adjustment', '2026-04-06 14:07:12', 0.00),
(7, 4, 'In', 23, 'Initial stock registration', '2026-04-08 11:37:56', 0.00),
(8, 5, 'In', 20, 'Initial stock registration', '2026-04-08 12:14:18', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `variation` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('Pending','Approved','Delivered','Cancelled','Declined') DEFAULT 'Pending',
  `decline_reason` text DEFAULT NULL,
  `estimated_delivery` date DEFAULT NULL,
  `encoded_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `product_id`, `customer_name`, `product_name`, `variation`, `quantity`, `total_price`, `status`, `decline_reason`, `estimated_delivery`, `encoded_by`, `created_at`) VALUES
(1, 1, 'Birondo', '', NULL, 1, 0.00, 'Approved', NULL, '2026-04-01', NULL, '2026-04-01 11:04:13'),
(2, 2, 'Birondo', '', NULL, 10, 0.00, 'Declined', 'out of stock', '2026-04-30', NULL, '2026-04-01 11:09:54'),
(3, 3, 'Birondo', '', NULL, 4, 0.00, 'Approved', '', '2026-04-06', NULL, '2026-04-06 13:29:48'),
(4, 2, 'Birondo', '', NULL, 1, 0.00, 'Approved', '', '2026-04-06', NULL, '2026-04-06 13:37:06');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `variation` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `max_quantity` int(11) DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category`, `product_name`, `variation`, `description`, `price`, `quantity`, `max_quantity`, `created_at`) VALUES
(1, 'Brooms', 'Stiff Broom', 'Standard', 'Hard bristles for outdoor sweeping and rough surfaces.', 1000.00, 100, 100, '2026-04-01 10:45:49'),
(2, 'Dustpan', 'Dustpan (Medium)', 'Red', 'Standard household dustpan with high-walled sides.', 100.00, 200, 200, '2026-04-01 10:46:33'),
(3, 'Bucket', 'Plastic Buckets', 'Black', 'Multipurpose heavy-duty bucket with liter markings.', 100.00, 26, 100, '2026-04-06 13:28:35'),
(4, 'Dustpan', 'Dustpan (Medium)', 'Green', 'Standard household dustpan with high-walled sides.', 120.00, 23, 100, '2026-04-08 11:37:56'),
(5, 'Bucket', 'Plastic Buckets', 'Red', 'Multipurpose heavy-duty bucket with liter markings.', 120.00, 20, 100, '2026-04-08 12:14:18');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `contact_person` varchar(150) NOT NULL,
  `office_address` text NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `quantity` int(12) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$vpaXJhBcdeUdkIK19xIO.uIW8fPb3.nnY4ZChx.MKuFybdOpMaxvC', 'admin', '2026-03-23 06:47:45'),
(2, 'birondo', 'birondo@gmail.com', '$2y$10$qvaL4LDGY1zuBvr.C1T7seyFKtL5Dm6B.gjQS4W0VWoYMw.rIddYC', 'admin', '2026-03-23 06:50:08'),
(9, 'Ian Keneth', 'ian@gmail.com', '$2y$10$lMTKONefHoGgF63H/pwKiuqIj5FeBDOy3U2gXNEuauoFlnanz93La', 'staff', '2026-03-29 02:48:09'),
(10, 'Renz Percy', 'renz.ui@phinamed.com', '$2y$10$JgV4DI4GEPsyrXKdbHGVG.RNofhmJbKiU3LE7YT8QqX6.wo9St6ru', 'staff', '2026-03-29 13:57:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
