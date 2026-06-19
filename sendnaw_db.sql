-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2026 at 12:43 PM
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
-- Database: `sendnaw_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(100) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','compliance_officer','support_agent','fraud_analyst') DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ajo_contributions`
--

CREATE TABLE `ajo_contributions` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cycle` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `paid_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ajo_groups`
--

CREATE TABLE `ajo_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_by` int(11) NOT NULL,
  `contribution_amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'NGN',
  `frequency` enum('daily','weekly','monthly') NOT NULL,
  `member_count` int(11) DEFAULT 0,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `current_cycle` int(11) DEFAULT 1,
  `next_payout_user` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ajo_members`
--

CREATE TABLE `ajo_members` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `beneficiaries`
--

CREATE TABLE `beneficiaries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `send_type` enum('tag','account','phone') NOT NULL,
  `avatar_url` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `billing_plans`
--

CREATE TABLE `billing_plans` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'NGN',
  `value` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crypto_transactions`
--

CREATE TABLE `crypto_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('buy','sell') NOT NULL,
  `currency` varchar(10) NOT NULL,
  `amount` decimal(18,8) NOT NULL,
  `price_usd` decimal(18,8) NOT NULL,
  `total_usd` decimal(18,8) NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crypto_wallets`
--

CREATE TABLE `crypto_wallets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `currency` varchar(10) NOT NULL,
  `balance` decimal(18,8) DEFAULT 0.00000000,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deposits`
--

CREATE TABLE `deposits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flagged_transactions`
--

CREATE TABLE `flagged_transactions` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fraud_alerts`
--

CREATE TABLE `fraud_alerts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `alert_type` enum('suspicious_login','large_transfer','unusual_location','multiple_failures') NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL,
  `description` text DEFAULT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `giftcard_products`
--

CREATE TABLE `giftcard_products` (
  `id` int(11) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `country` varchar(2) NOT NULL,
  `face_value` decimal(15,2) NOT NULL,
  `selling_price` decimal(15,2) NOT NULL,
  `buyback_price` decimal(15,2) NOT NULL,
  `stock` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invest_products`
--

CREATE TABLE `invest_products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `min_invest` decimal(15,2) NOT NULL,
  `expected_return_rate` decimal(5,2) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `risk_level` enum('low','medium','high') DEFAULT 'medium',
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kyc_documents`
--

CREATE TABLE `kyc_documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_type` varchar(50) DEFAULT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `kyc_submitted_at` timestamp NULL DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kyc_documents`
--

INSERT INTO `kyc_documents` (`id`, `user_id`, `document_type`, `document_number`, `full_name`, `date_of_birth`, `status`, `rejection_reason`, `submitted_at`, `reviewed_at`, `kyc_submitted_at`, `file_path`, `created_at`) VALUES
(1, 3, 'proof_of_address', NULL, NULL, NULL, 'approved', NULL, '2026-06-15 16:08:52', '2026-06-15 17:17:17', NULL, 'api/kyc/uploads/kyc_3_1781539732_2770.jpg', '2026-06-15 16:14:25'),
(2, 3, 'selfie', NULL, NULL, NULL, 'approved', NULL, '2026-06-15 22:01:39', '2026-06-15 22:14:30', NULL, 'api/kyc/uploads/kyc_3_1781560899_8890.jpg', '2026-06-15 22:01:39'),
(3, 3, 'selfie', NULL, NULL, NULL, 'approved', NULL, '2026-06-15 22:15:15', '2026-06-15 22:15:54', NULL, 'api/kyc/uploads/kyc_3_1781561715_9493.jpg', '2026-06-15 22:15:15'),
(4, 3, 'id_front', NULL, NULL, NULL, 'approved', NULL, '2026-06-16 21:58:08', '2026-06-16 22:02:19', NULL, 'api/kyc/uploads/kyc_3_1781647088_2373.jpg', '2026-06-16 21:58:08'),
(5, 3, 'id_front', NULL, NULL, NULL, 'approved', NULL, '2026-06-16 22:37:45', '2026-06-16 22:38:06', NULL, 'api/kyc/uploads/kyc_3_1781649465_2858.jpg', '2026-06-16 22:37:45'),
(6, 3, 'passport', NULL, NULL, NULL, 'approved', NULL, '2026-06-16 22:40:27', '2026-06-16 22:40:48', NULL, 'api/kyc/uploads/kyc_3_1781649627_3432.jpg', '2026-06-16 22:40:27'),
(7, 3, 'id_front', NULL, NULL, NULL, 'approved', NULL, '2026-06-16 22:53:44', '2026-06-16 22:58:45', NULL, 'api/kyc/uploads/kyc_3_1781650424_1540.jpg', '2026-06-16 22:53:44'),
(8, 3, 'id_front', NULL, NULL, NULL, 'approved', NULL, '2026-06-16 23:02:09', '2026-06-16 23:04:09', NULL, 'api/kyc/uploads/kyc_3_1781650929_2075.jpg', '2026-06-16 23:02:09'),
(9, 3, 'id_front', NULL, NULL, NULL, 'approved', NULL, '2026-06-16 23:04:17', '2026-06-17 16:06:21', NULL, 'api/kyc/uploads/kyc_3_1781651057_3168.jpg', '2026-06-16 23:04:17'),
(10, 4, 'proof_of_address', NULL, NULL, NULL, 'approved', NULL, '2026-06-17 15:39:50', '2026-06-17 15:51:16', NULL, 'api/kyc/uploads/kyc_4_1781710790_8222.jpg', '2026-06-17 15:39:50'),
(11, 4, 'id_front', NULL, NULL, NULL, 'approved', NULL, '2026-06-17 15:52:53', '2026-06-17 15:53:14', NULL, 'api/kyc/uploads/kyc_4_1781711573_4242.jpg', '2026-06-17 15:52:53'),
(12, 4, 'bank_statement', NULL, NULL, NULL, 'pending', NULL, '2026-06-18 00:41:53', NULL, NULL, 'api/kyc/uploads/kyc_4_1781743313_1532.jpg', '2026-06-18 00:41:53'),
(13, 5, 'id_front', NULL, NULL, NULL, 'approved', NULL, '2026-06-18 00:56:30', '2026-06-18 00:57:22', NULL, 'api/kyc/uploads/kyc_5_1781744190_7144.jpg', '2026-06-18 00:56:30');

-- --------------------------------------------------------

--
-- Table structure for table `kyc_upgrade_requests`
--

CREATE TABLE `kyc_upgrade_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `requested_tier` tinyint(1) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `kyc_submitted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kyc_upgrade_requests`
--

INSERT INTO `kyc_upgrade_requests` (`id`, `user_id`, `requested_tier`, `status`, `admin_notes`, `created_at`, `processed_at`, `kyc_submitted_at`) VALUES
(1, 3, 2, 'approved', NULL, '2026-06-15 22:01:39', '2026-06-16 22:58:45', NULL),
(2, 4, 2, 'approved', NULL, '2026-06-17 15:52:53', '2026-06-17 15:53:14', NULL),
(3, 4, 3, 'pending', NULL, '2026-06-18 00:41:53', NULL, NULL),
(4, 5, 2, 'approved', NULL, '2026-06-18 00:56:30', '2026-06-18 00:57:22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'NGN',
  `interest_rate` decimal(5,2) NOT NULL,
  `duration_months` int(11) NOT NULL,
  `monthly_installment` decimal(15,2) NOT NULL,
  `total_due` decimal(15,2) NOT NULL,
  `status` enum('pending','approved','disbursed','active','repaid','defaulted','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `disbursed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_products`
--

CREATE TABLE `loan_products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `min_amount` decimal(15,2) NOT NULL,
  `max_amount` decimal(15,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `duration_months` int(11) NOT NULL,
  `late_fee_rate` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_repayments`
--

CREATE TABLE `loan_repayments` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `amount_due` decimal(15,2) NOT NULL,
  `amount_paid` decimal(15,2) DEFAULT 0.00,
  `paid_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','paid','overdue') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_requests`
--

CREATE TABLE `loan_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected','disbursed') DEFAULT 'pending',
  `requested_at` timestamp NULL DEFAULT current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('transaction','kyc','promotion','alert') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `read`, `created_at`) VALUES
(1, 3, 'alert', 'Push Notifications Enabled', 'You will now receive important updates.', 0, '2026-06-17 13:38:42');

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pending_deposits`
--

CREATE TABLE `pending_deposits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reference` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'NGN',
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_deposits`
--

INSERT INTO `pending_deposits` (`id`, `user_id`, `reference`, `amount`, `currency`, `status`, `created_at`) VALUES
(1, 3, 'DEP_3_1781538767', 2000.00, 'NGN', 'pending', '2026-06-15 15:52:50');

-- --------------------------------------------------------

--
-- Table structure for table `prediction_bets`
--

CREATE TABLE `prediction_bets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `market_id` int(11) NOT NULL,
  `outcome` varchar(255) NOT NULL,
  `amount_usd` decimal(15,2) NOT NULL,
  `odds` decimal(10,2) NOT NULL,
  `potential_win` decimal(15,2) NOT NULL,
  `status` enum('active','won','lost','cancelled') DEFAULT 'active',
  `placed_at` timestamp NULL DEFAULT current_timestamp(),
  `settled_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prediction_markets`
--

CREATE TABLE `prediction_markets` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `outcome_yes` varchar(255) NOT NULL,
  `outcome_no` varchar(255) NOT NULL,
  `odds_yes` decimal(10,2) NOT NULL,
  `odds_no` decimal(10,2) NOT NULL,
  `end_date` datetime NOT NULL,
  `volume` decimal(15,2) DEFAULT 0.00,
  `participants` int(11) DEFAULT 0,
  `status` enum('active','settled','cancelled') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('airtime','data','electricity','tv','giftcard') NOT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `smartcard_number` varchar(50) DEFAULT NULL,
  `meter_number` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'NGN',
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `ref_code` varchar(100) DEFAULT NULL,
  `external_reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `endpoint` text NOT NULL,
  `public_key` text DEFAULT NULL,
  `auth_token` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `savings_liquidation_requests`
--

CREATE TABLE `savings_liquidation_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `penalty_percent` decimal(5,2) DEFAULT 0.00,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` timestamp NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `savings_plans`
--

CREATE TABLE `savings_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('fixed','flexible') NOT NULL,
  `min_amount` decimal(15,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_providers`
--

CREATE TABLE `service_providers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('airtime','data','electricity','tv','giftcard') NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stocks`
--

CREATE TABLE `stocks` (
  `id` int(11) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `current_price` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'NGN',
  `change_percent` decimal(10,2) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `logo_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_portfolio`
--

CREATE TABLE `stock_portfolio` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `shares` decimal(18,4) NOT NULL,
  `avg_price` decimal(15,2) NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

CREATE TABLE `stock_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('buy','sell') NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `shares` decimal(18,4) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `total` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tier_upgrade_requests`
--

CREATE TABLE `tier_upgrade_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `requested_tier` tinyint(1) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` timestamp NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `type` enum('transfer','deposit','withdrawal') NOT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transfers`
--

CREATE TABLE `transfers` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `sendnaw_tag` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `sendnaw_tag` varchar(100) NOT NULL,
  `account_number` varchar(20) DEFAULT NULL,
  `avatar_url` text DEFAULT NULL,
  `default_currency` varchar(3) DEFAULT 'NGN',
  `display_currency` varchar(3) DEFAULT 'NGN',
  `role` enum('admin','user') DEFAULT 'user',
  `kyc_tier` tinyint(1) DEFAULT 0,
  `kyc_status` enum('pending','approved','rejected','not_submitted') DEFAULT 'not_submitted',
  `dob` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `bvn` varchar(20) DEFAULT NULL,
  `nin` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) DEFAULT 0,
  `kyc_submitted_at` timestamp NULL DEFAULT NULL,
  `kyc_reviewed_at` timestamp NULL DEFAULT NULL,
  `id_verified` tinyint(1) DEFAULT 0,
  `address_verified` tinyint(1) DEFAULT 0,
  `selfie_verified` tinyint(1) DEFAULT 0,
  `gender` enum('male','female','other') DEFAULT NULL,
  `fcm_token` text DEFAULT NULL,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_backup_codes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `sendnaw_tag`, `account_number`, `avatar_url`, `default_currency`, `display_currency`, `role`, `kyc_tier`, `kyc_status`, `dob`, `address`, `bvn`, `nin`, `is_active`, `created_at`, `is_admin`, `kyc_submitted_at`, `kyc_reviewed_at`, `id_verified`, `address_verified`, `selfie_verified`, `gender`, `fcm_token`, `two_factor_secret`, `two_factor_enabled`, `two_factor_backup_codes`) VALUES
(1, 'Admin User', 'admin@sendnaw.com', '+1234567890', '$2y$10$V6LfOKMddgOyps9qkO0dZ.Bw75a4gDblaQU5J7.CmZWCWe/RuFzNS', 'admin', '1000000001', NULL, 'NGN', 'NGN', 'admin', 1, '', NULL, NULL, NULL, NULL, 1, '2026-06-14 21:39:00', 1, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, 0, NULL),
(3, 'mikec', 'mikec9613@gmail.com', '23409041728815', '$2y$10$r5ZuJZSfjRCWYsxqxv5d/u250H1ZqtXwzS9sA70fVU/7UytXSsD7q', 'mikec', '1000000003', 'https://api.dicebear.com/9.x/avataaars/svg?seed=mikec9613%40gmail.com&background=6f42c1', 'NGN', 'NGN', 'user', 1, '', NULL, NULL, NULL, NULL, 1, '2026-06-14 22:38:17', 0, '2026-06-16 23:04:17', '2026-06-17 16:06:21', 1, 1, 1, 'male', 'c8Sp2qE7NhsA1JWcr5F2hu:APA91bEXl1G9hmt58TGRuAdmQT0B0nSDwMjbRYcB_Rr97wJg0_ZA3C-WTPfX5vUNRKKlvWkRhYsrvbBbklwyZ8m-hSOahC3zca-rs-XrzPcShwryOg11CI4', 'F5UEBXGXKI4ZYLQQ', 0, NULL),
(4, 'thewalker', 'thewalker065@gmail.com', '23408061750882', '$2y$10$KWXRgO0H/YXG0vsDbqglyuC.34lAQ38bHXYkTMdjo1meYH62rSK/O', 'thewalker', '1000000004', 'https://api.dicebear.com/9.x/avataaars/svg?seed=thewalker065%40gmail.com&background=6f42c1', 'NGN', 'NGN', 'user', 1, '', '1986-02-18', 'Hilltown', '90834762753', '908347627532', 1, '2026-06-17 15:36:07', 0, '2026-06-18 00:41:53', '2026-06-17 15:53:14', 0, 0, 0, 'male', NULL, NULL, 0, NULL),
(5, 'sendnawt', 'sendnawt@gmail.com', '23409155958860', '$2y$10$1bLqVhmZ8DpIqy8uhPrrguYZah8wzFb3XtE.00Q0zCeqrF7ihJZvm', 'sendnawt', '1000000005', 'https://api.dicebear.com/9.x/avataaars/svg?seed=sendnawt%40gmail.com&background=6f42c1', 'NGN', 'NGN', 'user', 1, '', '2007-12-20', NULL, '60001394692', '60001394692', 1, '2026-06-18 00:48:26', 0, '2026-06-18 00:56:30', '2026-06-18 00:57:22', 0, 0, 0, 'male', NULL, 'BWZNHZ4NWIJ7OBEE', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_giftcards`
--

CREATE TABLE `user_giftcards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `card_code` varchar(100) NOT NULL,
  `face_value` decimal(15,2) NOT NULL,
  `purchased_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('active','used','expired') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_investments`
--

CREATE TABLE `user_investments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'NGN',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','matured','withdrawn') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_limits`
--

CREATE TABLE `user_limits` (
  `id` int(11) NOT NULL,
  `tier` tinyint(1) NOT NULL,
  `daily_deposit_limit` decimal(15,2) NOT NULL,
  `daily_withdraw_limit` decimal(15,2) NOT NULL,
  `single_transfer_limit` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_limits`
--

INSERT INTO `user_limits` (`id`, `tier`, `daily_deposit_limit`, `daily_withdraw_limit`, `single_transfer_limit`) VALUES
(1, 1, 50000.00, 50000.00, 50000.00),
(2, 2, 200000.00, 100000.00, 100000.00),
(3, 3, 500000.00, 300000.00, 300000.00);

-- --------------------------------------------------------

--
-- Table structure for table `user_portfolio`
--

CREATE TABLE `user_portfolio` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `quantity` decimal(18,4) NOT NULL,
  `average_buy_price` decimal(15,2) NOT NULL,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_savings`
--

CREATE TABLE `user_savings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'NGN',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','matured','withdrawn') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_name` varchar(255) DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_tokens`
--

INSERT INTO `user_tokens` (`id`, `user_id`, `token`, `user_agent`, `ip_address`, `device_name`, `last_activity`, `created_at`, `expires_at`) VALUES
(25, 3, 'a7d08d790f2c014ae100bdf12a3c719eb1d5cd97a8f5271efbf5666f3989705c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '::1', 'Windows NT 10.0; Win64; x64', '2026-06-16 22:08:51', '2026-06-16 22:08:51', NULL),
(29, 4, 'fee49d83850db7673f63c43a1ec664204225876a4d4784f35ddef3c15bf348bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '::1', 'Windows NT 10.0; Win64; x64', '2026-06-17 15:48:19', '2026-06-17 15:48:19', NULL),
(32, 1, '5b604b95a300f969cbc91d134fc75985da7c1071bd834b41913ee97b5a94fe7a', 'Unknown device', '::1', 'Unknown Device', '2026-06-18 23:03:48', '2026-06-18 23:03:48', NULL),
(33, 5, 'a0ddec87d175cf3125ec909d2ece6483e8ae3d531efefb4eb4cf868b2ceb1677', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '::1', 'Windows NT 10.0; Win64; x64', '2026-06-19 09:05:40', '2026-06-19 09:05:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `virtual_cards`
--

CREATE TABLE `virtual_cards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `card_number` varchar(20) NOT NULL,
  `expiry_month` int(2) NOT NULL,
  `expiry_year` int(4) NOT NULL,
  `cvv` varchar(5) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `balance` decimal(15,2) DEFAULT 0.00,
  `status` enum('active','frozen','closed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `virtual_cards`
--

INSERT INTO `virtual_cards` (`id`, `user_id`, `card_number`, `expiry_month`, `expiry_year`, `cvv`, `currency`, `balance`, `status`, `created_at`) VALUES
(1, 3, '48517740702', 6, 2029, '162', 'USD', 0.00, 'active', '2026-06-16 16:53:11');

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `currency_code` varchar(3) NOT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallets`
--

INSERT INTO `wallets` (`id`, `user_id`, `currency_code`, `balance`, `updated_at`) VALUES
(1, 1, 'NGN', 0.00, '2026-06-14 21:39:15'),
(2, 1, 'USD', 0.00, '2026-06-14 21:39:15'),
(3, 1, 'GBP', 0.00, '2026-06-14 21:39:15'),
(4, 3, 'NGN', 0.00, '2026-06-14 22:38:17'),
(5, 3, 'USD', 0.00, '2026-06-14 22:38:17'),
(6, 3, 'GBP', 0.00, '2026-06-14 22:38:17'),
(7, 4, 'NGN', 0.00, '2026-06-17 15:36:07'),
(8, 4, 'USD', 0.00, '2026-06-17 15:36:07'),
(9, 4, 'GBP', 0.00, '2026-06-17 15:36:07'),
(10, 5, 'NGN', 0.00, '2026-06-18 00:48:26'),
(11, 5, 'USD', 0.00, '2026-06-18 00:48:26'),
(12, 5, 'GBP', 0.00, '2026-06-18 00:48:26');

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` timestamp NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `ajo_contributions`
--
ALTER TABLE `ajo_contributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ajo_groups`
--
ALTER TABLE `ajo_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `ajo_members`
--
ALTER TABLE `ajo_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_user` (`group_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_ident` (`user_id`,`identifier`);

--
-- Indexes for table `billing_plans`
--
ALTER TABLE `billing_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `crypto_transactions`
--
ALTER TABLE `crypto_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `crypto_wallets`
--
ALTER TABLE `crypto_wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_currency` (`user_id`,`currency`);

--
-- Indexes for table `deposits`
--
ALTER TABLE `deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `flagged_transactions`
--
ALTER TABLE `flagged_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `fraud_alerts`
--
ALTER TABLE `fraud_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `resolved_by` (`resolved_by`);

--
-- Indexes for table `giftcard_products`
--
ALTER TABLE `giftcard_products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invest_products`
--
ALTER TABLE `invest_products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kyc_documents`
--
ALTER TABLE `kyc_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `kyc_upgrade_requests`
--
ALTER TABLE `kyc_upgrade_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `loan_products`
--
ALTER TABLE `loan_products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `loan_requests`
--
ALTER TABLE `loan_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone` (`phone`);

--
-- Indexes for table `pending_deposits`
--
ALTER TABLE `pending_deposits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `prediction_bets`
--
ALTER TABLE `prediction_bets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `market_id` (`market_id`);

--
-- Indexes for table `prediction_markets`
--
ALTER TABLE `prediction_markets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_code` (`ref_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `savings_liquidation_requests`
--
ALTER TABLE `savings_liquidation_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `savings_plans`
--
ALTER TABLE `savings_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_providers`
--
ALTER TABLE `service_providers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `stocks`
--
ALTER TABLE `stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `symbol` (`symbol`);

--
-- Indexes for table `stock_portfolio`
--
ALTER TABLE `stock_portfolio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_stock` (`user_id`,`symbol`);

--
-- Indexes for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tier_upgrade_requests`
--
ALTER TABLE `tier_upgrade_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `transfers`
--
ALTER TABLE `transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `sendnaw_tag` (`sendnaw_tag`),
  ADD UNIQUE KEY `account_number` (`account_number`);

--
-- Indexes for table `user_giftcards`
--
ALTER TABLE `user_giftcards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `user_investments`
--
ALTER TABLE `user_investments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `user_limits`
--
ALTER TABLE `user_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tier` (`tier`);

--
-- Indexes for table `user_portfolio`
--
ALTER TABLE `user_portfolio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_stock` (`user_id`,`stock_id`),
  ADD KEY `stock_id` (`stock_id`);

--
-- Indexes for table `user_savings`
--
ALTER TABLE `user_savings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `virtual_cards`
--
ALTER TABLE `virtual_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `card_number` (`card_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ajo_contributions`
--
ALTER TABLE `ajo_contributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ajo_groups`
--
ALTER TABLE `ajo_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ajo_members`
--
ALTER TABLE `ajo_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `billing_plans`
--
ALTER TABLE `billing_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crypto_transactions`
--
ALTER TABLE `crypto_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crypto_wallets`
--
ALTER TABLE `crypto_wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deposits`
--
ALTER TABLE `deposits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `flagged_transactions`
--
ALTER TABLE `flagged_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fraud_alerts`
--
ALTER TABLE `fraud_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `giftcard_products`
--
ALTER TABLE `giftcard_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invest_products`
--
ALTER TABLE `invest_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kyc_documents`
--
ALTER TABLE `kyc_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `kyc_upgrade_requests`
--
ALTER TABLE `kyc_upgrade_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_products`
--
ALTER TABLE `loan_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_requests`
--
ALTER TABLE `loan_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pending_deposits`
--
ALTER TABLE `pending_deposits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `prediction_bets`
--
ALTER TABLE `prediction_bets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prediction_markets`
--
ALTER TABLE `prediction_markets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `savings_liquidation_requests`
--
ALTER TABLE `savings_liquidation_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `savings_plans`
--
ALTER TABLE `savings_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_providers`
--
ALTER TABLE `service_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_portfolio`
--
ALTER TABLE `stock_portfolio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tier_upgrade_requests`
--
ALTER TABLE `tier_upgrade_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transfers`
--
ALTER TABLE `transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_giftcards`
--
ALTER TABLE `user_giftcards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_investments`
--
ALTER TABLE `user_investments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_limits`
--
ALTER TABLE `user_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_portfolio`
--
ALTER TABLE `user_portfolio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_savings`
--
ALTER TABLE `user_savings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `virtual_cards`
--
ALTER TABLE `virtual_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  ADD CONSTRAINT `beneficiaries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `crypto_transactions`
--
ALTER TABLE `crypto_transactions`
  ADD CONSTRAINT `crypto_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `crypto_wallets`
--
ALTER TABLE `crypto_wallets`
  ADD CONSTRAINT `crypto_wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `deposits`
--
ALTER TABLE `deposits`
  ADD CONSTRAINT `deposits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kyc_documents`
--
ALTER TABLE `kyc_documents`
  ADD CONSTRAINT `kyc_documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kyc_upgrade_requests`
--
ALTER TABLE `kyc_upgrade_requests`
  ADD CONSTRAINT `kyc_upgrade_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_requests`
--
ALTER TABLE `loan_requests`
  ADD CONSTRAINT `loan_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pending_deposits`
--
ALTER TABLE `pending_deposits`
  ADD CONSTRAINT `pending_deposits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD CONSTRAINT `push_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `savings_liquidation_requests`
--
ALTER TABLE `savings_liquidation_requests`
  ADD CONSTRAINT `savings_liquidation_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_portfolio`
--
ALTER TABLE `stock_portfolio`
  ADD CONSTRAINT `stock_portfolio_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tier_upgrade_requests`
--
ALTER TABLE `tier_upgrade_requests`
  ADD CONSTRAINT `tier_upgrade_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transfers`
--
ALTER TABLE `transfers`
  ADD CONSTRAINT `transfers_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transfers_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_portfolio`
--
ALTER TABLE `user_portfolio`
  ADD CONSTRAINT `user_portfolio_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_portfolio_ibfk_2` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `virtual_cards`
--
ALTER TABLE `virtual_cards`
  ADD CONSTRAINT `virtual_cards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallets`
--
ALTER TABLE `wallets`
  ADD CONSTRAINT `wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
