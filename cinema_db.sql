-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2026 at 09:27 PM
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
-- Database: `cinema_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'AFSALPG', '560396');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `seats` text NOT NULL,
  `movie` varchar(100) NOT NULL,
  `theater` varchar(20) NOT NULL DEFAULT 'T1 - 4K',
  `totalAmount` decimal(10,2) NOT NULL,
  `time` datetime DEFAULT current_timestamp(),
  `booking_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `name`, `phone`, `seats`, `movie`, `theater`, `totalAmount`, `time`, `booking_expiry`) VALUES
(35, 'AFSAL', '9947952013', '7,8,9', 'Sarvam Maya', 'T1 - 4K', 300.00, '2026-05-11 10:00:00', NULL),
(36, 'AFSALPG', '9947952013', '45,46,47,44', 'Patriot', 'T1 - 4K', 600.00, '2026-05-11 16:00:00', NULL),
(37, 'Abhi', '9947952013', '5,6', 'athiradi', 'T1 - 4K', 300.00, '2026-05-16 10:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `married_status` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `place` varchar(100) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `username`, `email`, `password`, `age`, `married_status`, `phone`, `address`, `place`, `pincode`, `gender`) VALUES
(12, 'Muhammed Nabhan', 'sw249076@gmail.com', '$2y$10$3oEqO6vqpgsTkY/deghHGO3t7MLkhAav4Hrd6e/xajrXRepIk5W3u', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'Adhil', 'adhilmp395@gmail.com', '$2y$10$BiDNl9dLYZ9ZdBeprXRtv.CW7LiE.sZLWEboLVS92wMeHkb.UJPta', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'Afsal', 'finuafsal7@gmail.com', '$2y$10$gdlMOgRQM5kvWKwWkPfCb.isYS7pvO5E97IsXJe2/cPOiuTUsc26e', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `email_otps`
--

CREATE TABLE `email_otps` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `purpose` varchar(20) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `name`, `email`, `message`, `submitted_at`) VALUES
(1, 'Afsal', 'finuafsal7@gmail.com', 'This is good movie ', '2026-04-14 17:44:30');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `movie` varchar(100) NOT NULL,
  `theater` varchar(20) NOT NULL DEFAULT 'T1 - 4K',
  `seats` text NOT NULL,
  `totalAmount` decimal(10,2) NOT NULL,
  `cash_given` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  `payment_method` varchar(20) DEFAULT NULL,
  `payment_status` varchar(20) DEFAULT NULL,
  `gateway` varchar(30) DEFAULT NULL,
  `gateway_order_id` varchar(100) DEFAULT NULL,
  `gateway_payment_id` varchar(100) DEFAULT NULL,
  `gateway_signature` varchar(255) DEFAULT NULL,
  `reference_note` varchar(255) DEFAULT NULL,
  `time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `name`, `phone`, `movie`, `theater`, `seats`, `totalAmount`, `cash_given`, `balance`, `payment_method`, `payment_status`, `gateway`, `gateway_order_id`, `gateway_payment_id`, `gateway_signature`, `reference_note`, `time`) VALUES
(1, NULL, 'AFSAL PG', '9947952013', 'FALCONS Theater', 'T1 - 4K', '1', 100.00, 100.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-29 07:56:09'),
(2, NULL, 'Ansara', '7306481035', 'Avengers', 'T1 - 4K', '2', 100.00, 100.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-29 08:13:12'),
(3, NULL, 'AFSAL PG', '9947952013', 'AVANGERS', 'T1 - 4K', '27', 100.00, 100.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-29 12:50:02'),
(4, NULL, 'AFSAL PG', '9947952013', 'Dune: Part One', 'T1 - 4K', '29', 100.00, 100.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-29 12:54:00'),
(5, NULL, 'Anaive', '9947952013', 'Dune: Part One', 'T1 - 4K', '34', 100.00, 100.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-29 12:54:50'),
(6, NULL, 'Anaive', '9947952013', 'AVANGERS', 'T1 - 4K', '63', 100.00, 100.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-29 12:58:17'),
(7, NULL, 'AFSAL PG', '9947952013', 'Sarvam Maya', 'T1 - 4K', '10', 100.00, 500.00, 400.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 19:54:45'),
(8, NULL, 'Anaive', '9947952013', 'Dune: Part One', 'T1 - 4K', '21', 100.00, 2500.00, 2400.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 19:55:26'),
(9, NULL, 'AFSAL PG', '9947952013', 'Sarvam Maya', 'T1 - 4K', '38', 100.00, 560.00, 460.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 20:13:25'),
(10, NULL, 'AFSAL PG', '9947952013', 'AVANGERS', 'T1 - 4K', '28', 100.00, 120.00, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 20:17:21'),
(11, NULL, 'AFSAL PG', '9947952013', 'Dune: Part One', 'T1 - 4K', '29', 100.00, 480.00, 380.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 20:18:17'),
(12, NULL, 'AFSAL PG', '9947952013', 'Sarvam Maya', 'T1 - 4K', '2', 100.00, 250.00, 150.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-20 16:36:33'),
(13, NULL, 'AFSAL PG', '9947952013', 'Sarvam Maya', 'T1 - 4K', '2', 100.00, 250.00, 150.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-20 16:37:47'),
(14, NULL, 'sinu', '9947952013', 'AVANGERS', 'T1 - 4K', '34', 100.00, 250.00, 150.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-20 16:38:48'),
(15, NULL, 'sinu', '9947952013', 'AVANGERS', 'T1 - 4K', '34', 100.00, 250.00, 150.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-20 17:06:25'),
(16, NULL, 'Afsal', '9947952013', 'Dune: Part One', 'T1 - 4K', '79', 100.00, 120.00, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-20 17:14:22'),
(17, NULL, 'Afsalpg', '9947952013', 'Dune: Part One', 'T1 - 4K', '79,89', 200.00, 250.00, 50.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-20 17:18:52'),
(18, NULL, 'Anu', '9947952013', 'Dune: Part One', 'T1 - 4K', '4', 100.00, 120.00, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-20 17:35:23'),
(19, NULL, 'aysu', '9947952013', 'Sarvam Maya', 'T1 - 4K', '5', 100.00, 100.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-20 17:36:12'),
(20, NULL, 'Asnan', '9656047648', 'AVANGERS', 'T1 - 4K', '21', 100.00, 150.00, 50.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 13:41:58'),
(21, NULL, 'Asnan', '9656047648', 'AVANGERS', 'T1 - 4K', '21', 100.00, 150.00, 50.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 13:42:04'),
(22, NULL, 'AFSAL', '9947952013', 'Sarvam Maya', 'T1 - 4K', '7', 100.00, 120.00, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-10 20:15:00'),
(23, NULL, 'AFSAL', '9947952013', 'Sarvam Maya', 'T1 - 4K', '18', 100.00, 120.00, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-11 13:25:56'),
(24, NULL, 'AFSAL', '9947952013', 'Sarvam Maya', 'T1 - 4K', '18', 100.00, 120.00, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-11 13:26:08'),
(25, NULL, 'Anaive', '9947952013', 'Sarvam Maya', 'T1 - 4K', '11', 100.00, 150.00, 50.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-11 13:31:03'),
(26, NULL, 'Abhi', '9995628480', 'Sarvam Maya', 'T1 - 4K', '12', 100.00, 120.00, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-11 13:32:07'),
(27, NULL, 'AFSAL', '9947952013', 'Sarvam Maya', 'T1 - 4K', '7,8,9', 300.00, 330.00, 30.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-11 15:32:45'),
(28, NULL, 'AFSALPG', '9947952013', 'Patriot', 'T1 - 4K', '45,46,47,44', 600.00, 1000.00, 400.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-11 16:04:39'),
(29, NULL, 'Abhi', '9947952013', 'athiradi', 'T1 - 4K', '5,6', 300.00, 350.00, 50.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-16 20:56:14');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `stars` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `stars`, `comment`, `submitted_at`) VALUES
(1, 3, 'Hshh', '2026-03-29 07:41:26'),
(4, 5, 'Good website', '2026-04-14 17:43:01'),
(5, 5, 'Good website', '2026-05-10 18:16:44');

-- --------------------------------------------------------

--
-- Table structure for table `upcoming_movies`
--

CREATE TABLE `upcoming_movies` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `language` varchar(50) NOT NULL,
  `image` text NOT NULL,
  `trailer_url` text DEFAULT NULL,
  `rating` varchar(20) DEFAULT NULL,
  `votes` varchar(50) DEFAULT NULL,
  `description` text NOT NULL,
  `release_date` date NOT NULL,
  `ticket_price` decimal(10,2) NOT NULL DEFAULT 100.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `upcoming_movies`
--

INSERT INTO `upcoming_movies` (`id`, `title`, `language`, `image`, `trailer_url`, `rating`, `votes`, `description`, `release_date`, `ticket_price`) VALUES
(7, 'Avengers Doomsday', 'English', 'https://encrypted-tbn1.gstatic.com/images?q=tbn:ANd9GcQhaopGyJLmwBcPoAU6hpM0mChrLhqZa-QVg-VwBjMFNSTryzBa', NULL, '9.8', '20.5k', 'Doomsday is an upcoming American superhero film based on the Marvel Comics superhero team the Avengers.', '2026-12-18', 250.00),
(9, 'Drishyam 3', 'Malayalam', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTspNHph8I7A8UNz9FoOQJ8HeiThrzlX3Es_9DQZJ0NB-e-5fsdXCuohL-_Cx0rWga2AKVY2dmslsvF4n2prmMivktqA0vXtP-SQwbBBqo&s=10', NULL, '12', '11', 'a 2026 Malayalam-language crime thriller', '2026-05-21', 150.00),
(12, 'athiradi', 'Malayalam', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQAaxAPeySP3U0MFGHRveVX24wsRDJHLFnUhGS5aS_jrb8f0IJNK94Cy6Mz9jRJZ7ltJsVrfovkZTvh8FflsYO0lsT8q7HxZ-g8GrBNf84X7fwRRw&s=10&ec=121691707', 'https://youtu.be/syBtRf69q6s?si=7XzJWEPgWqcYidaf', '7', '7.3', 'good', '2026-05-17', 150.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `email_otps`
--
ALTER TABLE `email_otps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `upcoming_movies`
--
ALTER TABLE `upcoming_movies`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `email_otps`
--
ALTER TABLE `email_otps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `upcoming_movies`
--
ALTER TABLE `upcoming_movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `delete_old_upcoming_movies` ON SCHEDULE EVERY 1 DAY STARTS '2026-03-29 10:25:49' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM upcoming_movies
  WHERE release_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)$$

CREATE DEFINER=`root`@`localhost` EVENT `delete_expired_bookings` ON SCHEDULE EVERY 5 MINUTE STARTS '2026-03-29 10:25:49' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM bookings
  WHERE booking_expiry IS NOT NULL
    AND booking_expiry < NOW()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
