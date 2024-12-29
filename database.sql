CREATE DATABASE db_peminjaman_aula;
USE db_peminjaman_aula;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') NOT NULL
);

CREATE TABLE ruangan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_ruangan VARCHAR(100) NOT NULL,
    kapasitas INT NOT NULL,
    fasilitas TEXT,
    status ENUM('tersedia', 'dipinjam') DEFAULT 'tersedia'
);

CREATE TABLE peminjaman (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    ruangan_id INT,
    tanggal_mulai DATETIME NOT NULL,
    tanggal_selesai DATETIME NOT NULL,
    keperluan TEXT NOT NULL,
    status ENUM('pending', 'disetujui', 'ditolak') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (ruangan_id) REFERENCES ruangan(id)
);

-- Menambahkan user default (password: admin123)
INSERT INTO users (username, password, nama_lengkap, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'),
('user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User Biasa', 'user'); 

-- Tambahkan data ruangan
INSERT INTO ruangan (nama_ruangan, kapasitas, fasilitas, status) VALUES
('Aula 1', 300, 'AC, Sound System, Proyektor, Kursi 300 buah, Panggung, Ruang Ganti', 'tersedia'),
('Aula 2', 200, 'AC, Sound System, Proyektor, Kursi 200 buah, Panggung', 'tersedia'),
('Mini GOR', 500, 'Kipas Angin Industrial, Sound System, Tribun Penonton, Lapangan Olahraga Indoor', 'tersedia'),
('Aula 4', 100, 'AC, Sound System, Proyektor, Kursi 100 buah, Ruang Rapat', 'tersedia'); 