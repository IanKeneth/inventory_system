-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2026 at 01:31 PM
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
-- Table structure for table `basic_reports`
--

CREATE TABLE `basic_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity_on_hand` int(11) NOT NULL,
  `report_title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Approved','Declined') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `basic_reports`
--

INSERT INTO `basic_reports` (`id`, `user_id`, `product_name`, `quantity_on_hand`, `report_title`, `description`, `category`, `created_at`, `status`) VALUES
(1, 0, 'Stiff Broom', 15, 'out of stock', 'out of stock yesterday', 'Supply Request', '2026-04-18 11:37:18', 'Approved'),
(2, 10, 'Brooms', 20, 'out of stock', 'out of since 7days', 'Supply Request', '2026-04-23 11:40:58', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_logs`
--

CREATE TABLE `inventory_logs` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('In','Out') NOT NULL,
  `quantity` int(11) NOT NULL,
  `Q_qty` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT 'Manual Adjustment',
  `log_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_logs`
--

INSERT INTO `inventory_logs` (`id`, `product_id`, `user_id`, `type`, `quantity`, `Q_qty`, `reason`, `log_date`) VALUES
(2, 3, NULL, 'In', 100, 100, 'Initial Registration', '2026-04-25 07:49:37'),
(3, 3, NULL, 'Out', 10, 0, 'Order #6 Approved', '2026-04-25 07:59:19'),
(4, 3, NULL, 'In', 10, 0, 'Order #6 Status Changed from Approved', '2026-04-25 07:59:38'),
(5, 3, NULL, 'Out', 10, 0, 'Order #6 Approved', '2026-04-25 08:07:22'),
(6, 3, NULL, 'In', 10, 0, 'Order #6 Restored (Status changed from Approved)', '2026-04-25 08:07:38'),
(7, 3, NULL, 'Out', 10, 0, 'Order #6 Approved', '2026-04-25 08:09:35'),
(10, 3, NULL, 'Out', 20, 0, 'Order #7 Approved', '2026-04-25 09:06:47'),
(11, 3, NULL, 'In', 20, 0, 'Order #7 Restored/Changed', '2026-04-25 09:06:55'),
(12, 3, NULL, 'In', 10, 0, 'Order #6 Restored/Changed', '2026-04-25 09:14:34'),
(13, 3, NULL, 'Out', 10, 0, 'Order #8 Approved', '2026-04-25 09:15:13'),
(14, 3, NULL, 'Out', 10, 0, 'Order #9 Approved', '2026-04-25 09:19:16'),
(15, 3, NULL, 'Out', 20, 0, 'Order #11 Approved', '2026-04-25 09:21:37'),
(16, 3, NULL, 'Out', 50, 0, 'Order #13 Delivered', '2026-04-25 09:33:21'),
(17, 3, NULL, 'In', 90, 0, 'Manual Adjustment (Edit Profile)', '2026-04-25 09:33:34'),
(18, 3, NULL, 'Out', 15, 0, 'Manual Adjustment (Edit Profile)', '2026-04-26 13:38:03'),
(19, 3, NULL, 'Out', 5, 0, 'Manual Adjustment (Edit Profile)', '2026-04-26 13:56:38'),
(20, 3, NULL, 'In', 5, 0, 'Manual Adjustment (Edit Profile)', '2026-04-26 13:58:27'),
(21, 3, NULL, 'Out', 1, 0, 'Manual Adjustment (Edit Profile)', '2026-04-26 14:02:26'),
(22, 3, 1, 'In', 1, 0, 'Manual Adjustment (Edit Profile)', '2026-04-26 14:12:53'),
(23, 3, 1, 'Out', 99, 0, 'Manual Adjustment ', '2026-04-26 15:41:43'),
(24, 3, 11, 'In', 10, 0, 'Staff Adjustment', '2026-04-27 03:50:40'),
(25, 3, 11, 'Out', 10, 0, 'Staff Adjustment', '2026-04-27 04:18:10'),
(26, 3, 11, 'In', 10, 0, 'Staff Adjustment', '2026-04-28 13:05:45'),
(27, 3, 11, 'Out', 5, 0, 'Staff Adjustment', '2026-04-28 13:13:08'),
(28, 3, 11, 'Out', 5, 0, 'Staff Adjustment', '2026-04-28 13:22:18'),
(29, 4, NULL, 'In', 100, 100, 'Initial Registration', '2026-04-29 03:24:58'),
(30, 5, NULL, 'In', 100, 100, 'Initial Registration', '2026-04-29 03:42:06'),
(31, 6, NULL, 'In', 100, 100, 'Initial Registration', '2026-04-29 03:49:03'),
(32, 7, NULL, 'In', 98, 98, 'Initial Registration', '2026-04-29 04:09:23'),
(33, 7, 1, 'In', 2, 0, 'Manual Adjustment ', '2026-04-29 04:09:40');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `variation` varchar(100) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `fulfillment_method` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Approved','Declined','Delivered') DEFAULT 'Pending',
  `decline_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `product_id`, `user_id`, `customer_name`, `product_name`, `variation`, `unit_price`, `quantity`, `total_price`, `fulfillment_method`, `status`, `decline_reason`, `created_at`) VALUES
(1, 9, 0, 'Birondo', 'Soft Broom', 'Ordinary', 100.00, 10, 1000.00, NULL, 'Approved', '', '2026-04-23 12:51:50'),
(2, 10, 11, 'Birondo', 'Soft Broom', 'Premium', 120.00, 10, 1200.00, NULL, 'Approved', '', '2026-04-23 13:22:00'),
(5, 13, 11, 'percy', 'street Broom', 'Outdoor (Stiff bristles)', 90.00, 2, 180.00, 'Picked Up', 'Approved', '', '2026-04-24 07:05:31'),
(6, 3, 11, 'Birondo', 'Soft Broom', 'Ordinary', 100.00, 10, 1000.00, 'Delivery', 'Delivered', '', '2026-04-25 07:58:13'),
(7, 3, 11, 'mee', 'Soft Broom', 'Ordinary', 100.00, 20, 2000.00, 'Picked Up', 'Delivered', '', '2026-04-25 09:04:13'),
(8, 3, 11, 'ian', 'Soft Broom', 'Ordinary', 100.00, 10, 1000.00, 'Picked Up', 'Approved', '', '2026-04-25 09:15:00'),
(9, 3, 11, 'renz', 'Soft Broom', 'Ordinary', 100.00, 10, 1000.00, 'Picked Up', 'Approved', '', '2026-04-25 09:19:03'),
(10, 3, 11, 'percy', 'Soft Broom', 'Ordinary', 100.00, 100, 10000.00, 'Delivery', 'Declined', 'out of stock', '2026-04-25 09:20:12'),
(11, 3, 11, 'percy', 'Soft Broom', 'Ordinary', 100.00, 20, 2000.00, 'Delivery', 'Approved', '', '2026-04-25 09:21:13'),
(12, 3, 11, 'percy', 'Soft Broom', 'Ordinary', 100.00, 50, 5000.00, 'Delivery', 'Delivered', '', '2026-04-25 09:24:25'),
(13, 3, 11, 'renz', 'Soft Broom', 'Ordinary', 100.00, 50, 5000.00, 'Delivery', 'Delivered', '', '2026-04-25 09:33:04');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `variation` varchar(100) DEFAULT 'Standard',
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `max_quantity` int(11) NOT NULL DEFAULT 100,
  `image_path` varchar(255) DEFAULT 'default-product.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category`, `product_name`, `variation`, `description`, `price`, `quantity`, `max_quantity`, `image_path`, `created_at`) VALUES
(3, 'Brooms', 'Soft Broom', 'Ordinary', 'Ordinary broom for house cleanup', 100.00, 100, 100, '1777431757_Soft_Broom.jpeg', '2026-04-25 07:49:37'),
(4, 'Brooms', 'Soft Broom', 'Premium', 'Our Premium Soft Broom doesn\'t just push dirt around—it hugs your floors', 120.00, 100, 100, '1777433098_Soft_Broom.jpeg', '2026-04-29 03:24:58'),
(5, 'Brooms', 'Stiff Broom', 'Standard', 'A stiff broom is a heavy-duty tool with rigid bristles designed to sweep heavy debris, mud, and stubborn wet or dry deposits from tough surfaces.', 150.00, 100, 100, '1777434126_Stiff_Broom.jpg', '2026-04-29 03:42:06'),
(6, 'Brooms', 'Lobby Broom', 'medium-handled', 'A lobby broom is a compact, short-handled tool designed for quick, one-handed sweeping in high-traffic commercial spaces', 120.00, 100, 100, '1777434543_Lobby_Broom.jpg', '2026-04-29 03:49:03'),
(7, 'Brooms', 'Street Broom', 'Stiff Bassine (Natural):', 'A heavy-duty push broom features stiff bristles built to clear gravel, wet leaves, and debris from rough surfaces', 200.00, 100, 100, '1777435763_Street_Broom.jpg', '2026-04-29 04:09:23');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `products_supplied` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transfer_requests`
--

CREATE TABLE `transfer_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `qty` int(11) NOT NULL,
  `source_location` varchar(100) DEFAULT NULL,
  `destination` varchar(100) DEFAULT NULL,
  `request_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Pending','Approved','Declined') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transfer_requests`
--

INSERT INTO `transfer_requests` (`id`, `user_id`, `item_name`, `qty`, `source_location`, `destination`, `request_date`, `notes`, `status`, `created_at`) VALUES
(2, 0, 'brooms', 200, 'Main Warehouse', 'Distribution Center', '2026-04-19', 'out of stuck since monday april 13', 'Approved', '2026-04-18 12:00:32'),
(3, 11, 'brooms', 100, 'Main Warehouse', 'Distribution Center', '2026-04-30', 'low stack ', 'Approved', '2026-04-23 11:31:14');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `profile_pic`) VALUES
(1, 'Admin Panel', 'admin@gmail.com', '$2y$10$TBn1.J/2ysRt9RrlfsUPVeG8DDpjclwWtMCpuOLX0/oz97WTNXh9e', 'admin', '2026-03-23 06:47:45', 'admin_1_1776583822.png'),
(2, 'birondo', 'birondo@gmail.com', '$2y$10$qvaL4LDGY1zuBvr.C1T7seyFKtL5Dm6B.gjQS4W0VWoYMw.rIddYC', 'admin', '2026-03-23 06:50:08', NULL),
(9, 'Ian Keneth', 'ian@gmail.com', '$2y$10$lMTKONefHoGgF63H/pwKiuqIj5FeBDOy3U2gXNEuauoFlnanz93La', 'staff', '2026-03-29 02:48:09', NULL),
(10, 'Renz Percy', 'renz.ui@phinamed.com', '$2y$10$JgV4DI4GEPsyrXKdbHGVG.RNofhmJbKiU3LE7YT8QqX6.wo9St6ru', 'staff', '2026-03-29 13:57:52', NULL),
(11, 'Ian Keneth', 'iama.birondo.ui@phinmaed.com', '$2y$10$fsKVx/1hW3fMmRvPwOFu/e1cck58.xyFFNvaAddztsLdXG55Smbli', 'staff', '2026-04-14 11:19:17', 'admin_11_1776585631.avif');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `basic_reports`
--
ALTER TABLE `basic_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_logs` (`product_id`);

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
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transfer_requests`
--
ALTER TABLE `transfer_requests`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `basic_reports`
--
ALTER TABLE `basic_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transfer_requests`
--
ALTER TABLE `transfer_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD CONSTRAINT `fk_product_logs` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
