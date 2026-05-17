-- Create database
CREATE DATABASE IF NOT EXISTS rental_db;
USE rental_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Equipment table
CREATE TABLE equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price_per_day DECIMAL(10,2) NOT NULL,
    category VARCHAR(50),
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rentals table
CREATE TABLE rentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    equipment_id INT NOT NULL,
    rental_date DATE NOT NULL,
    return_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'returned') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (equipment_id) REFERENCES equipment(id)
);

-- Insert sample data
INSERT INTO equipment (name, description, price_per_day, category) VALUES
('Snow Tent', 'Insulated tent for winter camping', 40.00, 'Winter'),
('Sleeping Bag (-30°C)', 'Extreme cold weather sleeping bag', 25.00, 'Winter'),
('Snow Shoes', 'Essential for walking on snow', 15.00, 'Winter'),
('Ice Axe', 'Climbing tool for ice and snow', 20.00, 'Winter'),
('Action Camera', 'Waterproof camera for adventures', 30.00, 'Water Sports'),
('Waterproof Bag', 'Dry bag for water activities', 10.00, 'Water Sports'),
('Swimsuit', 'Quick-dry swimsuit', 5.00, 'Water Sports'),
('Dry Case', 'Waterproof case for electronics', 8.00, 'Water Sports'),
('2-Person Tent', 'Lightweight camping tent', 35.00, 'Camping'),
('Sleeping Bag', 'Standard sleeping bag', 15.00, 'Camping'),
('Stove', 'Portable camping stove', 20.00, 'Camping'),
('Lantern', 'LED camping lantern', 10.00, 'Camping');

-- Insert admin user (password: admin123)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@rental.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
