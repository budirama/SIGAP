USE kuis_sd;

ALTER TABLE users MODIFY jenjang ENUM('SD1','SD6','SMP9') NULL;
ALTER TABLE kisi_kisi MODIFY jenjang ENUM('SD1','SD6','SMP9') NOT NULL;
ALTER TABLE soal MODIFY jenjang ENUM('SD1','SD6','SMP9') NOT NULL;
ALTER TABLE hasil_sesi MODIFY jenjang ENUM('SD1','SD6','SMP9') NOT NULL;

INSERT INTO users (nama, email, password_hash, role, jenjang) VALUES
  ('Siswa Contoh SD1', NULL, NULL, 'siswa', 'SD1');
