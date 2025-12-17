-- 3WAY Car Service Database Schema
-- Run this file to create all required tables

CREATE DATABASE IF NOT EXISTS `3way_car_service` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `3way_car_service`;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'manager', 'reception') DEFAULT 'reception',
    `branch` ENUM('thumama', 'rawdah') DEFAULT 'thumama',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customers Table
CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `visit_source` VARCHAR(50) DEFAULT NULL,
    `total_orders` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job Orders Table
CREATE TABLE IF NOT EXISTS `job_orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(20) NOT NULL UNIQUE,
    `customer_id` INT NOT NULL,
    `branch` ENUM('thumama', 'rawdah') DEFAULT 'thumama',
    `visit_source` VARCHAR(50) DEFAULT NULL,
    
    -- Car Information
    `car_type` VARCHAR(100) NOT NULL,
    `car_model` VARCHAR(50) DEFAULT NULL,
    `car_color` VARCHAR(50) DEFAULT NULL,
    `plate_number` VARCHAR(20) DEFAULT NULL,
    
    -- Pre-condition
    `has_dents` TINYINT(1) DEFAULT 0,
    `has_paint_erosion` TINYINT(1) DEFAULT 0,
    `has_scratches` TINYINT(1) DEFAULT 0,
    `has_previous_polish` TINYINT(1) DEFAULT 0,
    `has_exterior_mods` TINYINT(1) DEFAULT 0,
    `condition_details` TEXT DEFAULT NULL,
    
    -- Body Work Services
    `service_body_repair` TINYINT(1) DEFAULT 0,
    `service_parts_install` TINYINT(1) DEFAULT 0,
    `service_collision_repair` TINYINT(1) DEFAULT 0,
    
    -- Paint Services
    `service_single_paint` TINYINT(1) DEFAULT 0,
    `service_multi_paint` TINYINT(1) DEFAULT 0,
    `service_full_spray` TINYINT(1) DEFAULT 0,
    
    -- PDR Services
    `service_single_dent` TINYINT(1) DEFAULT 0,
    `service_multi_dents` TINYINT(1) DEFAULT 0,
    `pdr_notes` TEXT DEFAULT NULL,
    
    -- Polish & Protection Services
    `service_exterior_polish` TINYINT(1) DEFAULT 0,
    `service_interior_polish` TINYINT(1) DEFAULT 0,
    `service_lights_polish` TINYINT(1) DEFAULT 0,
    `service_scratch_treatment` TINYINT(1) DEFAULT 0,
    `service_nano_ceramic` TINYINT(1) DEFAULT 0,
    `service_ppf` TINYINT(1) DEFAULT 0,
    
    -- Additional Services
    `service_wash` TINYINT(1) DEFAULT 0,
    `service_deep_cleaning` TINYINT(1) DEFAULT 0,
    `service_other` TINYINT(1) DEFAULT 0,
    `other_service_notes` TEXT DEFAULT NULL,
    
    -- Cost & Time
    `estimated_cost` DECIMAL(10,2) DEFAULT 0,
    `expected_completion_time` VARCHAR(100) DEFAULT NULL,
    `delivery_date` DATETIME DEFAULT NULL,
    
    -- Technicians
    `body_technician` VARCHAR(100) DEFAULT NULL,
    `paint_technician` VARCHAR(100) DEFAULT NULL,
    `pdr_technician` VARCHAR(100) DEFAULT NULL,
    `polish_technician` VARCHAR(100) DEFAULT NULL,
    `branch_manager` VARCHAR(100) DEFAULT NULL,
    
    -- Delivery Status
    `services_completed` TINYINT(1) DEFAULT 0,
    `quality_checked` TINYINT(1) DEFAULT 0,
    `post_photos_taken` TINYINT(1) DEFAULT 0,
    `customer_received` TINYINT(1) DEFAULT 0,
    `post_delivery_notes` TEXT DEFAULT NULL,
    
    -- Order Status
    `status` ENUM('pending', 'in_progress', 'completed', 'delivered', 'cancelled') DEFAULT 'pending',
    `created_by` INT DEFAULT NULL,
    `completed_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`completed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_order_number` (`order_number`),
    INDEX `idx_status` (`status`),
    INDEX `idx_branch` (`branch`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Media Table (Photos and Videos)
CREATE TABLE IF NOT EXISTS `order_photos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `photo_type` ENUM('before', 'after', 'damage') DEFAULT 'before',
    `media_type` ENUM('image', 'video') DEFAULT 'image',
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` INT DEFAULT 0,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `job_orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity Log Table
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) DEFAULT NULL,
    `entity_id` INT DEFAULT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `branch`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير النظام', 'admin', 'thumama');

-- Insert sample reception user (password: reception123)
INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `branch`) VALUES
('reception', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'موظف الاستقبال', 'reception', 'thumama');
