-- Create and use the database
CREATE DATABASE IF NOT EXISTS nurseeuip_track;
USE nurseeuip_track;

-- Users table with password hashing
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    borrow_chance INT DEFAULT 5,
    last_chance_reset DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admins table with password hashing
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    admin_id VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Equipment table
CREATE TABLE equipment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    total_quantity INT NOT NULL,
    available_quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Borrowing records table
CREATE TABLE borrowing_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    equipment_id INT NOT NULL,
    borrowed_date DATETIME NOT NULL,
    due_date DATETIME NOT NULL,
    return_date DATETIME NULL,
    status ENUM('borrowed', 'returned', 'overdue') DEFAULT 'borrowed',
    notification_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    admin_id INT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- Insert sample equipment
INSERT INTO equipment (name, description, total_quantity, available_quantity) VALUES
('BP Apparatus', 'Digital blood pressure monitor', 5, 5),
('Stethoscope', 'Dual head stethoscope', 8, 8),
('Thermometer', 'Digital infrared thermometer', 10, 10),
('Pulse Oximeter', 'Fingertip pulse oximeter', 6, 6),
('Nebulizer', 'Compressor nebulizer machine', 3, 3),
('Wheelchair', 'Standard wheelchair', 2, 2),
('Crutches', 'Adjustable aluminum crutches', 4, 4),
('Glucose Meter', 'Blood glucose monitoring kit', 7, 7);

-- Insert sample admin with hashed password (password: ADMIN001)
INSERT INTO admins (full_name, email, admin_id, password_hash, contact_number) VALUES
('Admin User', 'admin@test.com', 'ADMIN001', '$2y$10$YourHashedPasswordHere', '09123456789');

-- Insert sample user with hashed password (password: 2024001)
INSERT INTO users (full_name, email, student_id, password_hash, contact_number, borrow_chance, last_chance_reset) VALUES
('Test Student', 'student@test.com', '2024001', '$2y$10$YourHashedPasswordHere', '09876543210', 5, CURDATE());