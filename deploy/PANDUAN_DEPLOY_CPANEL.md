# Panduan Deploy SIGAP (Kuis Cerdas) ke cPanel — sigap.jejakinovasi.com

Hosting hanya punya akses **File Manager/FTP + phpMyAdmin** (tanpa SSH), jadi upload &
import database dilakukan manual lewat cPanel. Script `build_deploy_package.php` di folder ini
menyiapkan semua file yang dibutuhkan supaya proses manual itu tinggal upload-import saja.

## 1. Generate paket deploy (di komputer lokal)

```
D:\xampp\php\php.exe deploy\build_deploy_package.php
```

Hasilnya ada di `deploy/output/`:
- `sigap-db-<timestamp>.sql` — dump database lokal (schema + semua data: kisi-kisi, soal, modul,
  hasil latihan siswa yang sudah ada)
- `sigap-deploy-<timestamp>.zip` — semua file project (tanpa folder dev seperti `KELAS_9/`,
  `.vscode/`, `.claude/`, dokumen spesifikasi), sudah termasuk `.htaccess` & `.env` versi produksi

Jalankan ulang script ini setiap kali mau update/redeploy ke server (misal setelah nambah fitur baru).

## 2. Buat subdomain di cPanel

1. cPanel → **Domains** (atau **Subdomains**) → buat subdomain `sigap` untuk domain `jejakinovasi.com`
   sehingga jadi `sigap.jejakinovasi.com`.
2. **Document Root**: arahkan ke folder baru khusus, misal `sigap.jejakinovasi.com` atau
   `sigap_app` (folder ini nanti isinya = isi zip yang diextract, bukan isi folder `public/`
   saja — karena `api/`, `config/`, `includes/` harus jadi folder sejajar dengan `public/`
   supaya path relatif `../api/...` yang dipanggil dari halaman siswa/guru tetap benar).

## 3. Buat database MySQL di cPanel

1. cPanel → **MySQL® Databases**.
2. Buat database baru (cPanel otomatis kasih prefix, misal `namauser_sigap`).
3. Buat user MySQL baru + password, lalu **Add User to Database** dengan privilege **All Privileges**.
4. Catat 3 hal ini (dibutuhkan di langkah 5): nama database, nama user, password.

## 4. Import database

1. cPanel → **phpMyAdmin** → pilih database yang baru dibuat di langkah 3.
2. Tab **Import** → pilih file `sigap-db-<timestamp>.sql` dari langkah 1 → **Go**.
3. Pastikan semua tabel (`users`, `kisi_kisi`, `soal`, `hasil_sesi`, `hasil_detail`) muncul dan
   terisi data setelah import selesai.

## 5. Upload & extract file project

1. cPanel → **File Manager** → masuk ke folder document root yang dibuat di langkah 2.
2. **Upload** file `sigap-deploy-<timestamp>.zip`.
3. Klik kanan file zip di File Manager → **Extract** (langsung ke folder tsb, jangan bikin
   subfolder tambahan — hasil extract harus taruh `public/`, `api/`, `config/`, dst langsung
   di document root, bukan di dalam `document_root/sigap-deploy-xxx/`).
4. Setelah yakin isinya benar, hapus file zip-nya dari File Manager (tidak perlu disimpan di server).

## 6. Isi kredensial produksi di `.env`

1. Di File Manager, klik kanan `.env` (folder ini `.htaccess`-nya sudah otomatis menyembunyikan
   dotfile dari akses browser, tapi tetap kelihatan & bisa diedit di File Manager) → **Edit**.
2. Isi:
   ```
   DB_HOST=localhost
   DB_NAME=<nama database dari langkah 3>
   DB_USER=<user database dari langkah 3>
   DB_PASS=<password database dari langkah 3>
   OPENAI_API_KEY=<API key OpenAI asli>
   ```
3. **Save Changes**.

> `OPENAI_API_KEY` sengaja dikosongkan di paket zip (tidak ikut ter-upload lewat channel apa pun)
> — harus diisi manual di sini supaya key asli tidak pernah tersebar di file yang bisa dikirim/dibagikan.

## 7. Pastikan versi PHP kompatibel

cPanel → **MultiPHP Manager** → pastikan subdomain ini pakai **PHP 8.1 atau lebih baru**, dengan
extension `pdo_mysql` aktif (biasanya sudah default aktif di cPanel modern).

## 8. Tes

1. Buka `https://sigap.jejakinovasi.com/` → harus langsung redirect ke form login (bukan
   direktori listing atau 403/404).
2. Coba login sebagai guru (pakai email guru yang terdaftar) dan sebagai salah satu siswa untuk
   pastikan koneksi database & sesi berjalan normal.
3. Coba buka salah satu materi yang sudah ada modulnya, dan coba fitur generate AI (butuh
   `OPENAI_API_KEY` yang sudah diisi di langkah 6) untuk pastikan koneksi ke OpenAI juga jalan.
4. Cek folder yang tidak boleh diakses publik tidak bisa dibuka langsung dari browser (harus
   403 Forbidden):
   - `https://sigap.jejakinovasi.com/.env`
   - `https://sigap.jejakinovasi.com/config/db.php`
   - `https://sigap.jejakinovasi.com/includes/ai.php`
   - `https://sigap.jejakinovasi.com/sql/schema.sql`

## Update/redeploy di kemudian hari

Untuk update kode setelah ada perubahan baru:
1. Jalankan ulang `deploy\build_deploy_package.php` di lokal.
2. Upload zip baru, extract **menimpa** file lama di document root (biarkan `.env` yang sudah
   berisi kredensial produksi tetap ada — jangan extract-overwrite kalau khawatir `.env` ikut
   tertimpa; kalau ragu, backup `.env` di server dulu sebelum extract, lalu restore isinya lagi
   setelah extract selesai).
3. Kalau ada perubahan struktur database (tabel/kolom baru), jalankan file SQL migrasi terkait
   (ada di folder `sql/migrate_*.sql`) manual lewat phpMyAdmin — dump penuh dari langkah 1 hanya
   perlu diimport ulang kalau setup dari awal (fresh install), bukan untuk update rutin (supaya
   data yang sudah ada di server produksi tidak tertimpa/hilang).
