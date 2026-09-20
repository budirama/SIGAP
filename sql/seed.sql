USE kuis_sd;

-- Akun guru contoh. Password TIDAK diisi di sini (jangan taruh hash yang bisa dicocokkan ke
-- password tertentu di repo publik) -- generate hash sendiri lalu UPDATE manual, mis:
-- php -r "echo password_hash('password_pilihan_anda', PASSWORD_DEFAULT);"
INSERT INTO users (nama, email, password_hash, role, jenjang) VALUES
  ('Guru/Orang Tua', 'guru@contoh.com', NULL, 'guru', NULL);
-- UPDATE users SET password_hash = '<hasil hash di atas>' WHERE email = 'guru@contoh.com';

-- Akun siswa contoh: tanpa password, login pakai nama + jenjang + kata kunci ajaib
-- (lihat .env SISWA_MAGIC_KEYWORD). Ganti nama sesuai kebutuhan.
INSERT INTO users (nama, email, password_hash, role, jenjang) VALUES
  ('Siswa Contoh 1', NULL, NULL, 'siswa', 'SD1'),
  ('Siswa Contoh 2', NULL, NULL, 'siswa', 'SD6'),
  ('Siswa Contoh 3', NULL, NULL, 'siswa', 'SMP9');
