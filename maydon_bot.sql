-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 28, 2019 at 12:35 AM
-- Server version: 5.7.24
-- PHP Version: 7.2.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `maydon_bot`
--

-- --------------------------------------------------------

--
-- Table structure for table `bot_cart`
--

CREATE TABLE `bot_cart` (
  `id` int(11) NOT NULL,
  `chat_id` int(11) DEFAULT NULL,
  `manufacturer_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bot_orders`
--

CREATE TABLE `bot_orders` (
  `chat_id` int(11) NOT NULL,
  `order_code` int(5) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `created_data` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bot_users`
--

CREATE TABLE `bot_users` (
  `chat_id` int(40) NOT NULL,
  `first_name` int(11) DEFAULT NULL,
  `last_name` int(11) DEFAULT NULL,
  `username` int(11) DEFAULT NULL,
  `language_code` int(11) DEFAULT NULL,
  `phone` int(11) DEFAULT NULL,
  `comment` text,
  `cart_count` int(3) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bot_users_caches`
--

CREATE TABLE `bot_users_caches` (
  `chat_id` int(11) NOT NULL,
  `latitude` varchar(30) DEFAULT NULL,
  `longitude` varchar(30) DEFAULT NULL,
  `json_code` text,
  `current_page` int(3) DEFAULT NULL,
  `last_message_id` int(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `telegramuser`
--

CREATE TABLE `telegramuser` (
  `id` int(40) NOT NULL,
  `is_bot` tinyint(1) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `language_code` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bot_cart`
--
ALTER TABLE `bot_cart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bot_users`
--
ALTER TABLE `bot_users`
  ADD UNIQUE KEY `chat_id` (`chat_id`),
  ADD KEY `chat_id_2` (`chat_id`);

--
-- Indexes for table `telegramuser`
--
ALTER TABLE `telegramuser`
  ADD UNIQUE KEY `id_2` (`id`),
  ADD KEY `id` (`id`),
  ADD KEY `id_3` (`id`),
  ADD KEY `id_4` (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bot_cart`
--
ALTER TABLE `bot_cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
