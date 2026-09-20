CREATE DATABASE IF NOT EXISTS kuis_sd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kuis_sd;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE,
  password_hash VARCHAR(255) NULL,
  role ENUM('guru','siswa') NOT NULL DEFAULT 'siswa',
  jenjang ENUM('SD1','SD6','SMP9') NULL,
  dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE kisi_kisi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  jenjang ENUM('SD1','SD6','SMP9') NOT NULL,
  mapel VARCHAR(100) NOT NULL,
  materi VARCHAR(150) NOT NULL,
  indikator VARCHAR(255) NOT NULL,
  modul_konten LONGTEXT NULL,
  level_kognitif VARCHAR(20) NOT NULL,
  kesulitan ENUM('Mudah','Sedang','Sukar') NOT NULL DEFAULT 'Sedang',
  jumlah_soal INT NOT NULL DEFAULT 3,
  dibuat_oleh INT,
  dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (dibuat_oleh) REFERENCES users(id)
);

CREATE TABLE soal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kisi_id INT NOT NULL,
  jenjang ENUM('SD1','SD6','SMP9') NOT NULL,
  mapel VARCHAR(100) NOT NULL,
  materi VARCHAR(150) NOT NULL,
  kesulitan ENUM('Mudah','Sedang','Sukar') NOT NULL,
  pertanyaan TEXT NOT NULL,
  opsi_a VARCHAR(500) NOT NULL,
  opsi_b VARCHAR(500) NOT NULL,
  opsi_c VARCHAR(500) NOT NULL,
  opsi_d VARCHAR(500) NOT NULL,
  kunci ENUM('a','b','c','d') NOT NULL,
  pembahasan TEXT,
  visual_tipe ENUM('bangun_datar','bangun_ruang','hitung_benda','bandingkan_benda') NULL,
  visual_data TEXT NULL,
  sumber ENUM('manual','ai') DEFAULT 'manual',
  dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (kisi_id) REFERENCES kisi_kisi(id) ON DELETE CASCADE
);

CREATE TABLE hasil_sesi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  jenjang ENUM('SD1','SD6','SMP9') NOT NULL,
  materi VARCHAR(150),
  jumlah_soal INT NOT NULL,
  jumlah_benar INT NOT NULL,
  skor INT NOT NULL,
  waktu DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE hasil_detail (
  id INT AUTO_INCREMENT PRIMARY KEY,
  hasil_id INT NOT NULL,
  soal_id INT NOT NULL,
  kisi_id INT NOT NULL,
  jawaban_dipilih ENUM('a','b','c','d'),
  benar TINYINT(1) NOT NULL,
  FOREIGN KEY (hasil_id) REFERENCES hasil_sesi(id) ON DELETE CASCADE,
  FOREIGN KEY (soal_id) REFERENCES soal(id) ON DELETE CASCADE
);

CREATE INDEX idx_soal_kisi ON soal(kisi_id);
CREATE INDEX idx_soal_jenjang ON soal(jenjang);
CREATE INDEX idx_soal_mapel ON soal(mapel);
CREATE INDEX idx_kisi_jenjang ON kisi_kisi(jenjang);
CREATE INDEX idx_kisi_mapel ON kisi_kisi(mapel);
CREATE INDEX idx_detail_soal ON hasil_detail(soal_id);
CREATE INDEX idx_hasil_user ON hasil_sesi(user_id);
