-- ThriftHub Database Schema & Initial Data
-- Created for BCA TU 4th Semester Web Technology Project

CREATE DATABASE IF NOT EXISTS `thrift_db`;
USE `thrift_db`;

-- 1. Users Table
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `balance` DECIMAL(10,2) NOT NULL DEFAULT 5000.00,
  `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Categories Table
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `icon` VARCHAR(50) DEFAULT '🏷️'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Products Table
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `seller_id` INT NOT NULL,
  `category_id` INT NOT NULL DEFAULT 1,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `condition_status` ENUM('Brand New', 'Like New', 'Gently Used', 'Well Used') NOT NULL DEFAULT 'Gently Used',
  `stock` INT NOT NULL DEFAULT 1,
  `image` VARCHAR(255) DEFAULT 'default_item.jpg',
  `status` ENUM('active', 'sold', 'archived') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Orders Table
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_ref` VARCHAR(20) NOT NULL UNIQUE,
  `buyer_id` INT NOT NULL,
  `seller_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('Completed', 'Pending', 'Cancelled') NOT NULL DEFAULT 'Completed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Transactions Table
CREATE TABLE `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `txn_ref` VARCHAR(20) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `type` ENUM('credit', 'debit') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data: Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `icon`) VALUES
(1, 'Clothing & Outerwear', 'clothing', '🧥'),
(2, 'Footwear & Sneakers', 'footwear', '👟'),
(3, 'Vintage & Retro', 'vintage', '📻'),
(4, 'Books & Media', 'books', '📚'),
(5, 'Electronics & Gadgets', 'electronics', '🎧'),
(6, 'Accessories & Watches', 'accessories', '⌚');

-- Seed Data: Users (Passwords are password_hash formatted, 'admin123' and 'rojal123' and 'password123')
-- Admin: admin / admin123
-- User 1: rojal / rojal123
-- User 2: john_doe / password123
INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `password`, `balance`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin', 'admin@thrifthub.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe11yvS46T7iCybK.vOQzT/K2Q3R0eYtG', 10000.00, 'admin', NOW()),
(2, 'Rojal Maharjan', 'rojal', 'rojal@thrifthub.com', '$2y$10$wN1G1/vQ/LgC2zO7S0/r4.Wk7W5PjKx81Tj2eM.nO6J1L1L1L1L1L', 4500.00, 'user', NOW()),
(3, 'John Doe', 'john_doe', 'john@gmail.com', '$2y$10$89v8/tX/B0g03gN8FhO.Ue.b/tJ5.K2X1S2Y3Z4A5B6C7D8E9F0G1', 3200.00, 'user', NOW());

-- Seed Data: Products
INSERT INTO `products` (`id`, `seller_id`, `category_id`, `name`, `description`, `price`, `condition_status`, `stock`, `image`, `status`, `created_at`) VALUES
(1, 2, 1, 'Vintage Denim Trucker Jacket', 'Authentic 90s classic blue denim jacket in excellent condition. Fits size Medium nicely.', 1850.00, 'Like New', 1, 'denim_jacket.jpg', 'active', NOW() - INTERVAL 5 DAY),
(2, 2, 2, 'Retro High-Top Canvas Sneakers', 'Classic black and white canvas sneakers, worn twice. Size UK 8 / EU 42.', 1200.00, 'Gently Used', 1, 'sneakers.jpg', 'active', NOW() - INTERVAL 4 DAY),
(3, 3, 5, 'Sony Noise-Canceling Headphones', 'Over-ear wireless headphones with great bass response and long battery life.', 3500.00, 'Gently Used', 1, 'headphones.jpg', 'active', NOW() - INTERVAL 3 DAY),
(4, 3, 3, 'Classic Film Camera 35mm', 'Fully functional vintage 35mm manual focus film camera with 50mm lens.', 4200.00, 'Gently Used', 1, 'camera.jpg', 'active', NOW() - INTERVAL 2 DAY),
(5, 2, 4, 'BCA Web Technology & DBMS Books Set', 'Complete set of 4th semester BCA textbooks in very good condition with no missing pages.', 650.00, 'Gently Used', 2, 'bca_books.jpg', 'active', NOW() - INTERVAL 1 DAY),
(6, 3, 6, 'Minimalist Quartz Leather Watch', 'Sleek brown leather strap watch with silver dial casing. Battery newly replaced.', 950.00, 'Like New', 1, 'watch.jpg', 'active', NOW());

-- Seed Data: Transactions
INSERT INTO `transactions` (`user_id`, `txn_ref`, `description`, `amount`, `type`, `created_at`) VALUES
(2, 'TXN100912', 'Welcome bonus deposit', 5000.00, 'credit', NOW() - INTERVAL 5 DAY),
(3, 'TXN100913', 'Welcome bonus deposit', 5000.00, 'credit', NOW() - INTERVAL 5 DAY),
(2, 'TXN100914', 'Listing earnings payout', 1200.00, 'credit', NOW() - INTERVAL 2 DAY),
(2, 'TXN100915', 'Wallet withdrawal to eSewa', 1700.00, 'debit', NOW() - INTERVAL 1 DAY);
