-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 07, 2026 at 03:58 PM
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
-- Database: `axel25550005`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `kd_kat` varchar(6) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `kd_kat`, `category_name`) VALUES
(2, 'K002', 'Underwear'),
(3, 'K003', 'Sneakers'),
(4, 'K004', 'Jacket'),
(5, 'K005', 'Longpants'),
(8, 'K008', 'Shoes');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `product_code` varchar(50) DEFAULT NULL,
  `product_name` varchar(150) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `min_stock` int(11) DEFAULT 5,
  `price` int(11) DEFAULT NULL,
  `gambar` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `product_code`, `product_name`, `stock`, `min_stock`, `price`, `gambar`, `created_at`, `updated_at`) VALUES
(2, 2, 'P001', 'Supreme Boxer', 2, 1, 750000, 'f7a1b02459a5c166725d0d89617261f5.jpeg', '2026-05-23 13:49:35', NULL),
(3, 3, 'P002', 'Nike Airmax 95 Black', 1, 1, 2050000, '4a6e1383069706d9740aeece70b501bd.jpeg', '2026-05-23 14:53:19', NULL),
(4, 4, 'P003', 'Supreme x Margiela MM6', 2, 1, 3150000, '5778a73dd8750accb20cf404cfe59cde.jpeg', '2026-05-23 15:02:01', NULL),
(5, 5, 'P004', 'Supreme®/Martine Rose® Leather Pant', 1, 1, 2175000, '5141f958afa95f16effc507881bf9e0e.jpeg', '2026-05-23 15:04:07', NULL),
(8, 4, 'P007', 'Nike x Stussy Hoodie SS25', 5, 1, 2150000, 'b836a33c2e56faabd32852e3917eeba3.jpeg', '2026-05-25 07:00:05', NULL),
(9, 5, 'P008', 'Nike x Stussy Sweatpants SS25', 5, 1, 1850000, '040dffd7ed46ce5773de1e7b1d2f5be9.jpeg', '2026-05-25 07:00:51', NULL),
(10, 4, 'P009', 'Supreme x Margiela MM6 Box Logo SS26', 5, 1, 3250000, 'e0917bae5b9833d8c5057b86b06dc1db.jpeg', '2026-05-25 07:06:29', NULL),
(11, 8, 'P010', 'Clarks Supreme x Wallabee \'Patent Leather Pack - Black', 4, 1, 2755000, '86525a3f320ffee6737f22e0151df9df.jpeg', '2026-05-25 07:08:10', NULL),
(12, 3, 'P011', 'Nike Air Force 1 x Supreme Black', 4, 1, 2875000, '44cc3af30f6354b3f758e81c2f96262b.jpeg', '2026-05-25 07:09:00', NULL),
(13, 4, 'P012', 'Palace GORE-TEX Cargo Jacket \'Black\'', 5, 1, 2750000, '09b485697d2cb67dafc006a1ef49770b.jpeg', '2026-05-26 08:45:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `stock_logs`
--

CREATE TABLE `stock_logs` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `change_type` enum('ADD','EDIT','REDUCE') DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `stock_before` int(11) DEFAULT NULL,
  `stock_after` int(11) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_logs`
--

INSERT INTO `stock_logs` (`id`, `product_id`, `change_type`, `qty`, `stock_before`, `stock_after`, `note`, `created_at`, `created_by`) VALUES
(1, 2, 'REDUCE', 1, 2, 1, '', '2026-05-23 13:49:52', 1),
(2, 2, 'REDUCE', 2, 4, 2, '', '2026-05-23 14:50:50', 1),
(3, 3, 'REDUCE', 2, 5, 3, '', '2026-05-23 15:08:16', 1),
(4, 4, 'REDUCE', 3, 5, 2, '', '2026-05-23 15:08:33', 1),
(5, 5, 'REDUCE', 2, 3, 1, '', '2026-05-23 15:08:44', 1),
(6, 3, 'ADD', 1, 0, 1, '', '2026-05-25 06:41:49', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `is_active`, `created_at`) VALUES
(1, 'Axel Indra Yudha', 'axelstaley414@example.com', '$2y$10$2X7VBYbBIMce8ByNchQituv9IUZRsL/38Zl42y0WIcKS3V9PoP8L2', 'admin', 1, '2026-05-13 07:26:16'),
(3, 'Putrafxn', 'putrafelixon@gmail.com', '$2y$10$PQA4gDQXcMKK7BRUEpSVJumsDLLBpXKkYvJhtJDw6IIrJx9ZlFtn.', 'staff', 1, '2026-05-23 15:10:37'),
(4, 'James Jebbia', 'jamesjebbia@gmail.com', '$2y$10$YZlObRNrUyFOUM86lr9kP.WSsI1G6HS5HGfGdsKrV8jffou8Nhrky', 'staff', 1, '2026-05-25 07:24:46'),
(5, 'Ken Carson', 'kencarson007@gmail.com', '$2y$10$z3sRZl/0kqwFHBA/OZGAiOIGsR/BxJ/w2yqILhDHnII/zUspgZE0m', 'staff', 1, '2026-05-26 18:50:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kd_kat` (`kd_kat`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_logs`
--
ALTER TABLE `stock_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `stock_logs`
--
ALTER TABLE `stock_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
