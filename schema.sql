-- Gaming Store Inventory System - Database Schema

CREATE DATABASE IF NOT EXISTS gaming_store;
USE gaming_store;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_quantity INT NOT NULL DEFAULT 0,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sales table
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sale items table
CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default categories
INSERT INTO categories (name) VALUES
('Graphics Cards'),
('Processors'),
('RAM'),
('Storage'),
('Monitors'),
('Peripherals');

-- Insert sample products
INSERT INTO products (category_id, name, description, price, stock_quantity, image_url) VALUES
(1, 'NVIDIA RTX 4070', 'GeForce RTX 4070 12GB GDDR6X', 19900.00, 15, ''),
(1, 'AMD RX 7800 XT', 'Radeon RX 7800 XT 16GB GDDR6', 17500.00, 8, ''),
(2, 'Intel Core i7-14700K', '14th Gen Intel Core i7, 20 cores', 14900.00, 12, ''),
(2, 'AMD Ryzen 7 7800X3D', '8-Core, 16-Thread Desktop Processor', 13500.00, 3, ''),
(3, 'Corsair Vengeance DDR5 32GB', 'DDR5-6000 CL36 Dual Channel Kit', 4290.00, 20, ''),
(3, 'G.Skill Trident Z5 RGB 32GB', 'DDR5-6400 CL32 RGB Kit', 5490.00, 2, ''),
(4, 'Samsung 990 Pro 2TB', 'NVMe M.2 SSD, 7450MB/s Read', 5990.00, 10, ''),
(4, 'WD Black SN850X 1TB', 'NVMe M.2 SSD, 7300MB/s Read', 3290.00, 18, ''),
(5, 'LG 27GP850-B', '27" QHD Nano IPS, 180Hz, 1ms', 12900.00, 6, ''),
(5, 'ASUS ROG Swift PG279QM', '27" QHD IPS, 240Hz, G-Sync', 22900.00, 4, ''),
(6, 'Logitech G Pro X Superlight 2', 'Wireless Gaming Mouse, 63g', 4590.00, 25, ''),
(6, 'Razer DeathAdder V3', 'Ergonomic Esports Mouse, 63g', 2990.00, 30, '');

-- Insert sample sales
INSERT INTO sales (sale_date, total_amount) VALUES
(NOW() - INTERVAL 5 DAY, 24490.00),
(NOW() - INTERVAL 3 DAY, 19190.00),
(NOW() - INTERVAL 2 DAY, 34400.00),
(NOW() - INTERVAL 1 DAY, 8880.00),
(NOW(), 17490.00);

-- Insert sample sale items
INSERT INTO sale_items (sale_id, product_id, quantity, unit_price) VALUES
(1, 1, 1, 19900.00),  -- RTX 4070
(1, 11, 1, 4590.00),   -- Logitech Mouse
(2, 3, 1, 14900.00),   -- i7-14700K
(2, 5, 1, 4290.00),    -- Corsair RAM
(3, 2, 1, 17500.00),   -- RX 7800 XT
(3, 9, 1, 12900.00),   -- LG Monitor
(3, 12, 1, 2990.00),   -- Razer Mouse (added extra for total)
(4, 5, 1, 4290.00),    -- Corsair RAM
(4, 11, 1, 4590.00),   -- Logitech Mouse
(5, 2, 1, 17500.00);   -- RX 7800 XT (another sale)
