-- SQL Dump to create database and tables for BudgetKu
CREATE DATABASE IF NOT EXISTS budgetku CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE budgetku;

-- users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- kategori table
CREATE TABLE IF NOT EXISTS kategori (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(100) NOT NULL,
  batas INT DEFAULT 0,
  periode ENUM('Harian','Mingguan','Bulanan') DEFAULT 'Bulanan',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- transaksi table
CREATE TABLE IF NOT EXISTS transaksi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  tanggal DATE NOT NULL,
  deskripsi VARCHAR(255),
  jumlah INT NOT NULL,
  tipe ENUM('Pemasukan','Pengeluaran') NOT NULL,
  kategori_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- target table
CREATE TABLE IF NOT EXISTS target (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  nama_target VARCHAR(150) NOT NULL,
  jumlah_target INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sample admin user (username: admin password: 12345)
INSERT INTO users (username, password) VALUES
('admin', '$2y$10$L8a2PhtQYbRWh1FoUyi96OwZ/6ly7K5S2V7FttLdDlmAK83DJqJtG');
