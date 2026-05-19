-- UPCart - Database Builder & Seeder
-- CMSC 127 Final Project
-- 
-- Members:
-- Banas, Frederick Renz
-- Blancaflor, Leona
-- Gapulan, Ethan
-- Lauricio, Justin

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `upcart_db`
--

CREATE DATABASE IF NOT EXISTS `upcart_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `upcart_db`;

-- --------------------------------------------------------
--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id`                INT(11)       NOT NULL,
  `email`             VARCHAR(255)  NOT NULL,
  `password_hash`     VARCHAR(255)  NOT NULL,
  `first_name`        VARCHAR(100)  NOT NULL,
  `last_name`         VARCHAR(100)  NOT NULL,
  `program`           VARCHAR(100)  DEFAULT NULL,
  `year_level`        INT(1)        DEFAULT NULL,
  `role`              ENUM('buyer','seller','moderator') NOT NULL DEFAULT 'buyer',
  `is_verified`       TINYINT(1)    NOT NULL DEFAULT 0,
  `is_active`         TINYINT(1)    NOT NULL DEFAULT 1,
  `profile_photo_url` VARCHAR(500)  DEFAULT NULL,
  `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user`
-- Passwords are bcrypt hashes of "password123"
--

INSERT INTO `user` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `program`, `year_level`, `role`, `is_verified`, `is_active`, `profile_photo_url`, `created_at`) VALUES
(1,  'jvsumbong@up.edu.ph',   '$2b$10$abcdefghijklmnopqrstuuVwXyZ0123456789abcdefghijk', 'Joseph',   'Sumbong',   'BS Computer Science',  4, 'moderator', 1, 1, NULL, '2024-06-01 08:00:00'),
(2,  'ksmanejo@up.edu.ph',    '$2b$10$abcdefghijklmnopqrstuuVwXyZ0123456789abcdefghijk', 'Kzlyr',    'Manejo',    'BS Computer Science',  3, 'moderator', 1, 1, NULL, '2024-06-01 08:05:00'),
(3,  'jjsanz@up.edu.ph',      '$2b$10$abcdefghijklmnopqrstuuVwXyZ0123456789abcdefghijk', 'Jhoanna',  'Sanz',      'BS Biology',           2, 'seller',    1, 1, NULL, '2024-06-10 09:00:00'),
(4,  'bgcruz@up.edu.ph',      '$2b$10$abcdefghijklmnopqrstuuVwXyZ0123456789abcdefghijk', 'Benjamin', 'Cruz',      'BS Chemistry',         3, 'seller',    1, 1, NULL, '2024-06-11 10:00:00'),
(5,  'cltan@up.edu.ph',       '$2b$10$abcdefghijklmnopqrstuuVwXyZ0123456789abcdefghijk', 'Claire',   'Tan',       'BA Political Science', 1, 'seller',    1, 1, NULL, '2024-06-12 11:00:00'),
(6,  'lgrobredo@up.edu.ph',   '$2b$10$abcdefghijklmnopqrstuuVwXyZ0123456789abcdefghijk', 'Leni',     'Robredo',   'BS Mathematics',       4, 'buyer',     1, 1, NULL, '2024-06-13 12:00:00'),
(7,  'pbparker@up.edu.ph',    '$2b$10$abcdefghijklmnopqrstuuVwXyZ0123456789abcdefghijk', 'Peter',    'Parker',    'BS Physics',           2, 'buyer',     1, 1, NULL, '2024-06-14 09:30:00'),
(8,  'jdcruz@up.edu.ph',      '$2b$10$abcdefghijklmnopqrstuuVwXyZ0123456789abcdefghijk', 'Juan',     'Cruz',      'BS Computer Science',  3, 'buyer',     1, 1, NULL, '2024-06-15 10:15:00'),
(9,  'nnuzumaki@up.edu.ph',   '$2b$10$abcdefghijklmnopqrstuuVwXyZ0123456789abcdefghijk', 'Naruto',   'Uzumaki',   'BA English',           1, 'buyer',     1, 1, NULL, '2024-06-16 11:45:00'),
(10, 'yjitadori@up.edu.ph',   '$2b$10$abcdefghijklmnopqrstuuVwXyZ0123456789abcdefghijk', 'Yuji',     'Itadori',   'BS Nursing',           2, 'buyer',     0, 1, NULL, '2024-06-17 14:00:00'),
(11, 'mzfushiguro@up.edu.ph', '$2b$10$abcdefghijklmnopqrstuuVwXyZ0123456789abcdefghijk', 'Megumi',   'Fushiguro', 'BS Accountancy',       4, 'seller',    1, 0, NULL, '2024-06-18 15:00:00'),
(12, 'rysukuna@up.edu.ph',    '$2b$10$abcdefghijklmnopqrstuuVwXyZ0123456789abcdefghijk', 'Ryomen',   'Sukuna',    'BA Sociology',         3, 'seller',    1, 1, NULL, '2024-06-19 16:00:00');

-- --------------------------------------------------------
--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id`   INT(11)      NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `name`, `slug`) VALUES
(1, 'Skincare',        'skincare'),
(2, 'Haircare',        'haircare'),
(3, 'Food & Drinks',   'food-drinks'),
(4, 'Clothing',        'clothing'),
(5, 'Books',           'books'),
(6, 'Electronics',     'electronics'),
(7, 'School Supplies', 'school-supplies'),
(8, 'Appliances',      'appliances');

-- --------------------------------------------------------
--
-- Table structure for table `listing`
--

CREATE TABLE `listing` (
  `id`                  INT(11)       NOT NULL,
  `seller_id`           INT(11)       NOT NULL,
  `category_id`         INT(11)       NOT NULL,
  `moderated_by`        INT(11)       DEFAULT NULL,
  `title`               VARCHAR(255)  NOT NULL,
  `description`         TEXT          DEFAULT NULL,
  `price`               DECIMAL(10,2) NOT NULL,
  `item_type`           ENUM('consumable','non_consumable') NOT NULL,
  `condition`           VARCHAR(100)  NOT NULL,
  `moderation_status`   ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `listing_status`      ENUM('active','sold','removed') NOT NULL DEFAULT 'active',
  `moderation_feedback` TEXT          DEFAULT NULL,
  `moderated_at`        TIMESTAMP     NULL DEFAULT NULL,
  `meetup_location`     VARCHAR(255)  DEFAULT NULL,
  `color_texture_notes` VARCHAR(255)  DEFAULT NULL,
  `created_at`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `listing`
--

INSERT INTO `listing` (`id`, `seller_id`, `category_id`, `moderated_by`, `title`, `description`, `price`, `item_type`, `condition`, `moderation_status`, `listing_status`, `moderation_feedback`, `moderated_at`, `meetup_location`, `color_texture_notes`, `created_at`, `updated_at`) VALUES
(1,  3,  1, 1,    'Biore UV Aqua Rich Sunscreen SPF50',    'Used about 3 times. Bought the wrong shade.',          350.00, 'consumable',     'Good',      'approved', 'active',  NULL,                              '2024-11-01 09:00:00', 'AS Building Lobby',  'White, lightweight gel',  '2024-10-31 20:00:00', '2024-11-01 09:00:00'),
(2,  3,  2, 1,    'Pantene Shampoo 400ml',                 'Half-used. Switching brands.',                          80.00, 'consumable',     'Fair',      'approved', 'active',  NULL,                              '2024-11-01 09:05:00', 'AS Building Lobby',  NULL,                      '2024-10-31 20:10:00', '2024-11-01 09:05:00'),
(3,  4,  4, 2,    'Oversized White Shirt (Medium)',         'Worn twice. No damages.',                             150.00, 'non_consumable', 'Good',      'approved', 'active',  NULL,                              '2024-11-01 10:00:00', 'CAS Covered Court',  'White, cotton',           '2024-10-31 21:00:00', '2024-11-01 10:00:00'),
(4,  4,  5, 2,    'Calculus Early Transcendentals 8th Ed', 'Some pencil annotations. Spine intact.',              400.00, 'non_consumable', 'Good',      'approved', 'sold',    NULL,                              '2024-11-01 10:05:00', 'CAS Covered Court',  NULL,                      '2024-10-31 21:10:00', '2024-11-02 15:00:00'),
(5,  5,  1, 1,    'Cetaphil Gentle Skin Cleanser 250ml',   'About 60% remaining.',                                200.00, 'consumable',     'Good',      'approved', 'active',  NULL,                              '2024-11-01 11:00:00', 'Library Steps',      'White cream, mild scent', '2024-10-31 22:00:00', '2024-11-01 11:00:00'),
(6,  5,  6, 2,    'Xiaomi Powerbank 10000mAh',             'Minor scratch on back. Still holds full charge.',     600.00, 'non_consumable', 'Good',      'approved', 'active',  NULL,                              '2024-11-01 11:05:00', 'Library Steps',      'Black, matte finish',     '2024-10-31 22:10:00', '2024-11-01 11:05:00'),
(7,  12, 3, 1,    'Nescafe 3-in-1 (10 sachets remaining)', 'Leftover from a 20-pack box.',                         40.00, 'consumable',     'Good',      'approved', 'active',  NULL,                              '2024-11-02 08:00:00', 'Oblation Plaza',     NULL,                      '2024-11-01 19:00:00', '2024-11-02 08:00:00'),
(8,  12, 7, 2,    'Staedtler Mars Plastic Eraser (x5)',    'Unused, still in packaging.',                          50.00, 'non_consumable', 'Brand New', 'approved', 'active',  NULL,                              '2024-11-02 08:05:00', 'Oblation Plaza',     'White',                   '2024-11-01 19:10:00', '2024-11-02 08:05:00'),
(9,  3,  8, 1,    'Electric Fan (Small Desk Fan)',          'Works perfectly. 1 year old.',                       350.00, 'non_consumable', 'Good',      'approved', 'active',  NULL,                              '2024-11-02 09:00:00', 'AS Building Lobby',  'White',                   '2024-11-01 20:00:00', '2024-11-02 09:00:00'),
(10, 4,  4, NULL, 'Vintage Denim Jacket (Small)',          'Awaiting moderator review.',                          800.00, 'non_consumable', 'Good',      'pending',  'active',  NULL,                              NULL,                  'CAS Covered Court',  'Blue denim',              '2024-11-03 10:00:00', '2024-11-03 10:00:00'),
(11, 5,  1, 2,    'Sunplay Skin Aqua SPF50',               'Listing rejected due to missing expiry date.',        120.00, 'consumable',     'Fair',      'rejected', 'active',  'Please include the expiry date.', '2024-11-03 11:00:00', 'Library Steps',      NULL,                      '2024-11-03 09:00:00', '2024-11-03 11:00:00'),
(12, 11, 5, 1,    'Organic Chemistry Textbook 7th Ed',     'Seller account suspended.',                           350.00, 'non_consumable', 'Fair',      'approved', 'removed', NULL,                              '2024-11-01 12:00:00', 'Suspended',          NULL,                      '2024-10-31 23:00:00', '2024-11-04 08:00:00');

-- --------------------------------------------------------
--
-- Table structure for table `consumable_detail`
--

CREATE TABLE `consumable_detail` (
  `id`                  INT(11)      NOT NULL,
  `listing_id`          INT(11)      NOT NULL,
  `estimated_remaining` DECIMAL(5,2) DEFAULT NULL,
  `date_opened`         DATE         DEFAULT NULL,
  `expiry_date`         DATE         DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `consumable_detail`
--

INSERT INTO `consumable_detail` (`id`, `listing_id`, `estimated_remaining`, `date_opened`, `expiry_date`) VALUES
(1, 1,  85.00, '2024-09-15', '2026-03-01'),
(2, 2,  50.00, '2024-08-01', '2026-01-01'),
(3, 5,  60.00, '2024-07-20', '2025-12-01'),
(4, 7,  NULL,  NULL,         '2025-06-01'),
(5, 11, 70.00, '2024-10-01', NULL);

-- --------------------------------------------------------
--
-- Table structure for table `non_consumable_detail`
--

CREATE TABLE `non_consumable_detail` (
  `id`                 INT(11)      NOT NULL,
  `listing_id`         INT(11)      NOT NULL,
  `size_or_dimensions` VARCHAR(255) DEFAULT NULL,
  `material`           VARCHAR(255) DEFAULT NULL,
  `duration_of_use`    VARCHAR(100) DEFAULT NULL,
  `known_damages`      TEXT         DEFAULT NULL,
  `quantity`           INT(11)      NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `non_consumable_detail`
--

INSERT INTO `non_consumable_detail` (`id`, `listing_id`, `size_or_dimensions`, `material`, `duration_of_use`, `known_damages`, `quantity`) VALUES
(1, 3,  'Medium',              'Cotton',           '2 weeks',    NULL,                     1),
(2, 4,  '28 x 21 x 3 cm',     'Paperback',        '1 semester', 'Pencil annotations',     1),
(3, 6,  '14.5 x 7.2 x 1.5 cm','Polycarbonate',    '1 year',     'Minor scratch on back',  1),
(4, 8,  '6.5 x 2.5 cm',       'Synthetic rubber', 'Unused',     NULL,                     5),
(5, 9,  '20 x 15 x 25 cm',    'Plastic/Metal',    '1 year',     NULL,                     1),
(6, 10, 'Small',               'Denim',            '3 months',   NULL,                     1),
(7, 12, '28 x 21 x 4 cm',     'Paperback',        '2 semesters','Highlighted sections',   1);

-- --------------------------------------------------------
--
-- Table structure for table `listing_image`
--

CREATE TABLE `listing_image` (
  `id`         INT(11)      NOT NULL,
  `listing_id` INT(11)      NOT NULL,
  `image_url`  VARCHAR(500) NOT NULL,
  `sort_order` INT(11)      NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `listing_image`
--

INSERT INTO `listing_image` (`id`, `listing_id`, `image_url`, `sort_order`) VALUES
(1,  1, 'https://storage.upcart.upv.ph/listings/1/cover.jpg', 0),
(2,  1, 'https://storage.upcart.upv.ph/listings/1/side.jpg',  1),
(3,  2, 'https://storage.upcart.upv.ph/listings/2/cover.jpg', 0),
(4,  3, 'https://storage.upcart.upv.ph/listings/3/cover.jpg', 0),
(5,  3, 'https://storage.upcart.upv.ph/listings/3/back.jpg',  1),
(6,  4, 'https://storage.upcart.upv.ph/listings/4/cover.jpg', 0),
(7,  5, 'https://storage.upcart.upv.ph/listings/5/cover.jpg', 0),
(8,  6, 'https://storage.upcart.upv.ph/listings/6/cover.jpg', 0),
(9,  7, 'https://storage.upcart.upv.ph/listings/7/cover.jpg', 0),
(10, 8, 'https://storage.upcart.upv.ph/listings/8/cover.jpg', 0),
(11, 9, 'https://storage.upcart.upv.ph/listings/9/cover.jpg', 0);

-- --------------------------------------------------------
--
-- Table structure for table `saved_listing`
--

CREATE TABLE `saved_listing` (
  `id`         INT(11)   NOT NULL,
  `user_id`    INT(11)   NOT NULL,
  `listing_id` INT(11)   NOT NULL,
  `saved_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `saved_listing`
--

INSERT INTO `saved_listing` (`id`, `user_id`, `listing_id`, `saved_at`) VALUES
(1, 6, 1, '2024-11-01 14:00:00'),
(2, 6, 5, '2024-11-01 14:05:00'),
(3, 7, 3, '2024-11-01 15:00:00'),
(4, 7, 6, '2024-11-01 15:10:00'),
(5, 8, 4, '2024-11-01 16:00:00'),
(6, 9, 7, '2024-11-02 10:00:00'),
(7, 9, 8, '2024-11-02 10:05:00'),
(8, 6, 9, '2024-11-02 11:00:00');

-- --------------------------------------------------------
--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `id`           INT(11)       NOT NULL,
  `listing_id`   INT(11)       NOT NULL,
  `buyer_id`     INT(11)       NOT NULL,
  `seller_id`    INT(11)       NOT NULL,
  `agreed_price` DECIMAL(10,2) NOT NULL,
  `status`       ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `initiated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP     NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`id`, `listing_id`, `buyer_id`, `seller_id`, `agreed_price`, `status`, `initiated_at`, `completed_at`) VALUES
(1, 4, 8, 4, 400.00, 'completed', '2024-11-02 09:00:00', '2024-11-02 15:00:00'),
(2, 1, 6, 3, 350.00, 'confirmed', '2024-11-02 10:00:00', NULL),
(3, 3, 7, 4, 150.00, 'pending',   '2024-11-03 08:00:00', NULL),
(4, 6, 7, 5, 600.00, 'completed', '2024-11-02 13:00:00', '2024-11-03 10:00:00'),
(5, 5, 9, 3, 200.00, 'cancelled', '2024-11-02 14:00:00', NULL),
(6, 7, 9, 12, 40.00, 'completed', '2024-11-02 16:00:00', '2024-11-02 17:30:00');

-- --------------------------------------------------------
--
-- Table structure for table `review`
--

CREATE TABLE `review` (
  `id`             INT(11)   NOT NULL,
  `transaction_id` INT(11)   NOT NULL,
  `reviewer_id`    INT(11)   NOT NULL,
  `reviewee_id`    INT(11)   NOT NULL,
  `rating`         INT(1)    NOT NULL,
  `comment`        TEXT      DEFAULT NULL,
  `role`           ENUM('buyer','seller') NOT NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `review`
--

INSERT INTO `review` (`id`, `transaction_id`, `reviewer_id`, `reviewee_id`, `rating`, `comment`, `role`, `created_at`) VALUES
(1, 1, 8, 4, 5, 'Textbook was exactly as described. Fast transaction!',            'buyer',  '2024-11-02 16:00:00'),
(2, 1, 4, 8, 5, 'Very polite buyer. Arrived on time for the meetup.',              'seller', '2024-11-02 16:30:00'),
(3, 4, 7, 5, 4, 'Powerbank works great. Minor scratch was honestly unnoticeable.', 'buyer',  '2024-11-03 11:00:00'),
(4, 4, 5, 7, 5, 'Smooth transaction. Would sell again.',                           'seller', '2024-11-03 11:30:00'),
(5, 6, 9, 12, 5,'Sachets were fresh and properly packed.',                         'buyer',  '2024-11-02 18:00:00');

-- --------------------------------------------------------
--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `id`               INT(11)      NOT NULL,
  `reporter_id`      INT(11)      NOT NULL,
  `reported_user_id` INT(11)      NOT NULL,
  `listing_id`       INT(11)      DEFAULT NULL,
  `transaction_id`   INT(11)      DEFAULT NULL,
  `reason`           VARCHAR(255) NOT NULL,
  `description`      TEXT         DEFAULT NULL,
  `status`           ENUM('open','reviewing','resolved','closed') NOT NULL DEFAULT 'open',
  `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at`      TIMESTAMP    NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `report`
--

INSERT INTO `report` (`id`, `reporter_id`, `reported_user_id`, `listing_id`, `transaction_id`, `reason`, `description`, `status`, `created_at`, `resolved_at`) VALUES
(1, 8, 11, 12,   NULL, 'Misleading listing',       'The textbook had more damage than described in the listing.',    'resolved', '2024-11-03 09:00:00', '2024-11-04 08:00:00'),
(2, 9, 3,  NULL, 5,    'Cancelled without reason', 'Seller cancelled the transaction without any explanation.',      'open',     '2024-11-03 15:00:00', NULL),
(3, 6, 4,  10,   NULL, 'Incomplete listing',       'Pending listing has no images attached.',                        'reviewing','2024-11-03 17:00:00', NULL);

-- --------------------------------------------------------
--
-- Table structure for table `user_warning`
--

CREATE TABLE `user_warning` (
  `id`        INT(11)   NOT NULL,
  `user_id`   INT(11)   NOT NULL,
  `issued_by` INT(11)   NOT NULL,
  `reason`    TEXT      NOT NULL,
  `issued_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_warning`
--

INSERT INTO `user_warning` (`id`, `user_id`, `issued_by`, `reason`, `issued_at`) VALUES
(1, 11, 1, 'Listing contained inaccurate damage descriptions. Seller account has been suspended pending review.', '2024-11-04 08:05:00'),
(2, 3,  2, 'Transaction cancelled without notifying the buyer. Please follow proper cancellation protocol.',      '2024-11-04 09:00:00');

-- --------------------------------------------------------
--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id`             INT(11)     NOT NULL,
  `user_id`        INT(11)     NOT NULL,
  `type`           ENUM('listing_approved','listing_rejected','new_order','order_confirmed','completed','dispute_update') NOT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL,
  `reference_id`   INT(11)     DEFAULT NULL,
  `is_read`        TINYINT(1)  NOT NULL DEFAULT 0,
  `created_at`     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`id`, `user_id`, `type`, `reference_type`, `reference_id`, `is_read`, `created_at`) VALUES
(1,  3,  'listing_approved', 'listing',     1,  1, '2024-11-01 09:00:00'),
(2,  3,  'listing_approved', 'listing',     2,  1, '2024-11-01 09:05:00'),
(3,  4,  'listing_approved', 'listing',     3,  1, '2024-11-01 10:00:00'),
(4,  4,  'listing_approved', 'listing',     4,  1, '2024-11-01 10:05:00'),
(5,  5,  'listing_approved', 'listing',     5,  1, '2024-11-01 11:00:00'),
(6,  5,  'listing_approved', 'listing',     6,  1, '2024-11-01 11:05:00'),
(7,  5,  'listing_rejected', 'listing',     11, 0, '2024-11-03 11:00:00'),
(8,  4,  'new_order',        'transaction', 1,  1, '2024-11-02 09:00:00'),
(9,  3,  'new_order',        'transaction', 2,  1, '2024-11-02 10:00:00'),
(10, 4,  'new_order',        'transaction', 3,  0, '2024-11-03 08:00:00'),
(11, 5,  'new_order',        'transaction', 4,  1, '2024-11-02 13:00:00'),
(12, 8,  'order_confirmed',  'transaction', 1,  1, '2024-11-02 09:30:00'),
(13, 8,  'completed',        'transaction', 1,  1, '2024-11-02 15:00:00'),
(14, 4,  'completed',        'transaction', 1,  1, '2024-11-02 15:00:00'),
(15, 7,  'completed',        'transaction', 4,  1, '2024-11-03 10:00:00'),
(16, 5,  'completed',        'transaction', 4,  1, '2024-11-03 10:00:00'),
(17, 9,  'completed',        'transaction', 6,  0, '2024-11-02 17:30:00'),
(18, 9,  'dispute_update',   'report',      2,  0, '2024-11-04 09:00:00'),
(19, 3,  'dispute_update',   'report',      2,  0, '2024-11-04 09:00:00');

-- ========================================================
--
-- Indexes for all tables
--

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `listing`
--
ALTER TABLE `listing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `moderated_by` (`moderated_by`);

--
-- Indexes for table `consumable_detail`
--
ALTER TABLE `consumable_detail`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `listing_id` (`listing_id`);

--
-- Indexes for table `non_consumable_detail`
--
ALTER TABLE `non_consumable_detail`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `listing_id` (`listing_id`);

--
-- Indexes for table `listing_image`
--
ALTER TABLE `listing_image`
  ADD PRIMARY KEY (`id`),
  ADD KEY `listing_id` (`listing_id`);

--
-- Indexes for table `saved_listing`
--
ALTER TABLE `saved_listing`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_listing` (`user_id`, `listing_id`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`id`),
  ADD KEY `listing_id` (`listing_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_reviewer` (`transaction_id`, `reviewer_id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `reviewee_id` (`reviewee_id`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `reported_user_id` (`reported_user_id`),
  ADD KEY `listing_id` (`listing_id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `user_warning`
--
ALTER TABLE `user_warning`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `issued_by` (`issued_by`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

-- ========================================================
--
-- AUTO_INCREMENT for all tables
--

ALTER TABLE `user`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

ALTER TABLE `category`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `listing`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

ALTER TABLE `consumable_detail`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `non_consumable_detail`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `listing_image`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

ALTER TABLE `saved_listing`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `transaction`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `review`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `report`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `user_warning`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `notification`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

-- ========================================================
--
-- Foreign key constraints
--

ALTER TABLE `listing`
  ADD CONSTRAINT `fk_listing_seller`    FOREIGN KEY (`seller_id`)    REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_listing_category`  FOREIGN KEY (`category_id`)  REFERENCES `category` (`id`),
  ADD CONSTRAINT `fk_listing_moderator` FOREIGN KEY (`moderated_by`) REFERENCES `user` (`id`);

ALTER TABLE `consumable_detail`
  ADD CONSTRAINT `fk_consumable_listing` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`);

ALTER TABLE `non_consumable_detail`
  ADD CONSTRAINT `fk_nonconsumable_listing` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`);

ALTER TABLE `listing_image`
  ADD CONSTRAINT `fk_image_listing` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`);

ALTER TABLE `saved_listing`
  ADD CONSTRAINT `fk_saved_user`    FOREIGN KEY (`user_id`)    REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_saved_listing` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`);

ALTER TABLE `transaction`
  ADD CONSTRAINT `fk_txn_listing` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`),
  ADD CONSTRAINT `fk_txn_buyer`   FOREIGN KEY (`buyer_id`)   REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_txn_seller`  FOREIGN KEY (`seller_id`)  REFERENCES `user` (`id`);

ALTER TABLE `review`
  ADD CONSTRAINT `fk_review_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transaction` (`id`),
  ADD CONSTRAINT `fk_review_reviewer`    FOREIGN KEY (`reviewer_id`)    REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_review_reviewee`    FOREIGN KEY (`reviewee_id`)    REFERENCES `user` (`id`);

ALTER TABLE `report`
  ADD CONSTRAINT `fk_report_reporter`      FOREIGN KEY (`reporter_id`)      REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_report_reported_user` FOREIGN KEY (`reported_user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_report_listing`       FOREIGN KEY (`listing_id`)       REFERENCES `listing` (`id`),
  ADD CONSTRAINT `fk_report_transaction`   FOREIGN KEY (`transaction_id`)   REFERENCES `transaction` (`id`);

ALTER TABLE `user_warning`
  ADD CONSTRAINT `fk_warning_user`      FOREIGN KEY (`user_id`)   REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_warning_moderator` FOREIGN KEY (`issued_by`) REFERENCES `user` (`id`);

ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
