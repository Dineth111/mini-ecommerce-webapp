CREATE DATABASE IF NOT EXISTS `luxe_commerce` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `luxe_commerce`;

-- 1. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Products Table
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `image` VARCHAR(255) NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Users Table (Customers)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Admins Table
CREATE TABLE IF NOT EXISTS `admin` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Cart Table
CREATE TABLE IF NOT EXISTS `cart` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_product` (`user_id`, `product_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'shipped', 'delivered') NOT NULL DEFAULT 'pending',
    `shipping_address` TEXT NOT NULL,
    `contact_number` VARCHAR(20) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NULL,
    `quantity` INT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEED DATA
-- Seed Categories
INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Electronics'),
(2, 'Fashion & Apparel'),
(3, 'Home & Living'),
(4, 'Books & Stationery');

-- Seed Products
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image`, `stock`) VALUES
(1, 1, 'Quantum Pro Wireless Headphones', 'Experience high-fidelity audio with active noise cancellation, a 40-hour battery life, and comfortable ergonomic memory foam earcups.', 149.99, 'headphones.jpg', 25),
(2, 1, 'Veloce Smartwatch Series X', 'A sleek smartwatch featuring an AMOLED display, real-time heart rate monitoring, fitness tracking, and up to 7 days of battery life.', 199.99, 'smartwatch.jpg', 15),
(3, 2, 'Classic Leather Moto Jacket', 'Handcrafted from premium full-grain leather, this timeless jacket features heavy-duty metal zippers and a quilted satin lining.', 299.99, 'jacket.jpg', 8),
(4, 2, 'Nomad Canvas Travel Backpack', 'A water-resistant canvas backpack with a padded laptop compartment, multiple storage pockets, and genuine leather accents.', 59.99, 'backpack.jpg', 40),
(5, 3, 'Minimalist Ceramic Coffee Dripper', 'A beautifully crafted ceramic dripper for the perfect pour-over coffee experience. Dishwasher safe and elegant on any countertop.', 24.99, 'dripper.jpg', 50),
(6, 3, 'Ergonomic Mesh Office Chair', 'Breathable mesh chair with adjustable lumbar support, 3D armrests, and a tilt tension mechanism for all-day comfort.', 249.99, 'chair.jpg', 12),
(7, 4, 'Chronicles of the Cosmos: A Sci-Fi Anthology', 'A collection of award-winning science fiction short stories exploring the outer limits of space exploration, AI, and futuristic worlds.', 14.99, 'book.jpg', 30);

-- Seed User (password: userpassword)
INSERT INTO `users` (`id`, `username`, `email`, `password`) VALUES
(1, 'john_doe', 'john@example.com', '$2y$10$.NigjBGUpeajTDBdb7ShW.73DS4hVgH/.U5HM9H6zORcdovVS.DuG');

-- Seed Admin (password: adminpassword)
INSERT INTO `admin` (`id`, `username`, `email`, `password`) VALUES
(1, 'admin', 'admin@luxecommerce.com', '$2y$10$JZvho2Qa/F1I99CdNyC.HO0PhNkG5mOLOWoJE5LaWp3WUvZq/uJMK');
