CREATE DATABASE fleet_management;
USE fleet_management;
drop database fleet_management;

-- Users Table
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'driver', 'mechanic') NOT NULL,
    phone VARCHAR(20),
    dob DATE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    first_login TINYINT(1) DEFAULT 0
);

-- Vehicles Table
CREATE TABLE Vehicles (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,  
    registration_no VARCHAR(50) NOT NULL,       
    make VARCHAR(100) NOT NULL,                
    model VARCHAR(100) NOT NULL,   
    year INT NOT NULL,  
    vin VARCHAR(100) NOT NULL UNIQUE,   
    mileage INT NOT NULL,           
    fuel_type VARCHAR(50) NOT NULL,   
    status ENUM('active', 'inactive', 'retired') NOT NULL DEFAULT 'active',             
    assigned_driver INT,                        
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
    FOREIGN KEY (assigned_driver) REFERENCES Users(user_id) ON DELETE SET NULL
);

-- Maintenance Tasks Table
CREATE TABLE Maintenance_Tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    task_name VARCHAR(255) NOT NULL,
    estimated_time INT NOT NULL,
    additional_details TEXT
);

-- Service Centers Table (Fixed ID name)
CREATE TABLE Service_Centers (
    service_center_id INT AUTO_INCREMENT PRIMARY KEY,
    service_center_name VARCHAR(255) NOT NULL,
    task_id INT,
    FOREIGN KEY (task_id) REFERENCES Maintenance_Tasks(task_id)
    
);

-- Maintenance Schedule Table (Fixed foreign key references)
CREATE TABLE Maintenance_Schedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    task_id INT NOT NULL,
    service_center_id INT NOT NULL,
    schedule_date DATE NOT NULL,
    schedule_start_time TIME NOT NULL,
    schedule_end_time TIME NOT NULL,
    status ENUM('Scheduled', 'Admitted', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    additional_info TEXT NULL,
    FOREIGN KEY (vehicle_id) REFERENCES Vehicles(vehicle_id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES Maintenance_Tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (service_center_id) REFERENCES Service_Centers(service_center_id) ON DELETE CASCADE
);

ALTER TABLE maintenance_schedule ADD COLUMN mileage_at_service INT DEFAULT NULL;

-- Service History Table (Fixed foreign key references)
CREATE TABLE Service_History (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    task_id INT NOT NULL,
    service_center_id INT NOT NULL,
    date_of_service DATE NOT NULL,
    mileage_at_service INT NOT NULL,
    service_notes TEXT NOT NULL,
    checkout_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES Vehicles(vehicle_id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES Maintenance_Tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (service_center_id) REFERENCES Service_Centers(service_center_id) ON DELETE CASCADE
);

CREATE TABLE notification_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    schedule_id INT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (schedule_id) REFERENCES maintenance_schedule(schedule_id)
);
-- Service Center Mechanics Table
CREATE TABLE service_center_mechanics (
    service_center_mechanic_id INT AUTO_INCREMENT PRIMARY KEY,
    service_center_id INT NOT NULL,
    mechanic_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_center_id) REFERENCES Service_Centers(service_center_id) ON DELETE CASCADE,
    FOREIGN KEY (mechanic_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Messages Table
CREATE TABLE Messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,    
    message_body TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    deleted_by_sender TINYINT(1) DEFAULT 0,
    deleted_by_receiver TINYINT(1) DEFAULT 0,
    FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Simple Notifications Table
CREATE TABLE Notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('maintenance', 'message', 'system') DEFAULT 'maintenance',
    related_id INT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

