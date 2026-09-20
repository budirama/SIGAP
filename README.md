# SIGAP — SIstem Generate & Analisis Pembelajaran

Aplikasi web kuis & modul belajar berbasis AI untuk siswa SD dan SMP/MTs, dengan login
terpisah per siswa, materi & bank soal per jenjang/mata pelajaran, modul pembelajaran
mendalam (bukan cuma latihan soal), serta raport perkembangan per anak.

Awalnya dibangun untuk keperluan belajar mandiri 3 orang anak (SD Kelas 1, SD Kelas 6,
dan SMP/MTs Kelas 9), dengan konten materi yang mengacu ke Kurikulum Merdeka & referensi
TKA (Tes Kemampuan Akademik).

## Fitur Utama

- **Multi-jenjang & multi-siswa**: setiap siswa punya akun, jenjang, dan progres belajar
  sendiri-sendiri (saat ini mendukung SD Kelas 1, SD Kelas 6, dan SMP/MTs Kelas 9).
- **Login sederhana untuk siswa**: cukup pilih nama + jenjang + kata kunci ajaib (tanpa
  password rumit), supaya anak-anak bisa login sendiri.
- **Dashboard guru**: kelola kisi-kisi, bank soal, dan pantau progres semua siswa dari satu
  tempat.
- **Generate soal otomatis via AI** (OpenAI): guru cukup isi kisi-kisi (mata pelajaran,
  materi, indikator, level kognitif), soal pilihan ganda dibuat otomatis lengkap dengan
  pembahasan.
- **Modul pembelajaran berbasis AI**: selain dites, siswa juga bisa membaca modul
  penjelasan materi (tujuan belajar, penjelasan, contoh soal terjawab, istilah kunci,
  poin penting) sebelum berlatih soal — termasuk modul khusus untuk melatih kemampuan
  literasi/menerjemahkan soal cerita matematika.
- **Visual/ilustrasi otomatis**: sebagian soal (terutama SD) bisa menyertakan ilustrasi
  bangun datar/ruang atau visual hitung benda yang digambar langsung oleh sistem (SVG),
  tanpa perlu upload gambar.
- **Raport per anak**: rekap nilai per mata pelajaran, predikat, grafik perkembangan skor,
  dan analisis naratif (AI) tentang kekuatan & area yang perlu diperkuat — bisa
  diprint/PDF.
- **Anti-tebak jawaban**: posisi opsi jawaban diacak otomatis di server supaya tidak ada
  bias posisi (mis. jawaban selalu di opsi A).

## Teknologi

- **Backend**: PHP 8.1 + PDO (MySQL), tanpa framework — struktur MVC ringan sendiri.
- **Database**: MySQL/MariaDB.
- **Frontend**: Bootstrap 5, Bootstrap Icons, Chart.js — vanilla JS (tanpa build step).
- **AI**: OpenAI Chat Completions API (model default `gpt-4o-mini`), dipakai untuk generate
  soal, modul pembelajaran, dan analisis naratif raport.

## Struktur Project

```
config/     Koneksi database & loader .env
includes/   Helper inti: auth/session, integrasi AI (generate soal/modul), helper umum
api/        Endpoint JSON yang dipanggil dari halaman guru/siswa (AJAX)
public/     Halaman yang diakses browser (login, dashboard guru, halaman siswa) + assets
sql/        Schema database & file migrasi
```

## Menjalankan di Lokal (XAMPP)

1. Clone repo ini ke `htdocs` (mis. `htdocs/RAMAHOME`).
2. Buat database MySQL, lalu import `sql/schema.sql` (dan `sql/seed.sql` untuk data awal
   kalau diperlukan).
3. Salin `.env.example` menjadi `.env`, lalu isi kredensial database & (opsional)
   `OPENAI_API_KEY` kalau ingin memakai fitur generate soal/modul otomatis.
4. Buka `http://localhost/RAMAHOME/` — otomatis diarahkan ke halaman login.

## Dedikasi

Untuk para orang tua — teknologi dan AI bisa membantu membuatkan soal, modul, dan raport,
tapi tidak bisa menggantikan kehadiran Anda. Aplikasi ini dibuat bukan supaya anak belajar
sendirian di depan layar, melainkan sebagai alat bantu agar waktu mendampingi anak belajar
jadi lebih terarah dan bermakna. Tetap luangkan waktu, dampingi, dan rayakan setiap
kemajuan kecil mereka — dukungan dan kehadiran orang tua adalah faktor terpenting dalam
tumbuh kembang dan semangat belajar anak.
