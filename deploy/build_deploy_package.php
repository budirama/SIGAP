<?php
/**
 * Script migrasi/deploy SIGAP (Kuis Cerdas) ke cPanel.
 *
 * Jalankan dari lokal (XAMPP) setiap kali mau deploy/update ke cPanel:
 *   D:\xampp\php\php.exe deploy\build_deploy_package.php
 *
 * Hasilnya (di folder deploy/output/):
 *   1. sigap-db-<timestamp>.sql   -> dump database lokal (schema + data), import via phpMyAdmin di cPanel
 *   2. sigap-deploy-<timestamp>.zip -> paket file project siap upload & extract via File Manager cPanel
 *
 * Karena hosting hanya punya akses File Manager/FTP + phpMyAdmin (tanpa SSH), proses upload &
 * import tetap dilakukan manual di cPanel — lihat deploy/PANDUAN_DEPLOY_CPANEL.md untuk langkahnya.
 */

require_once __DIR__ . '/../config/env.php';
loadEnv();

$projectRoot = dirname(__DIR__);
$outDir = __DIR__ . '/output';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$timestamp = date('Ymd-His');

echo "=== SIGAP - Build Paket Deploy cPanel ===\n\n";

// ------------------------------------------------------------------
// 1) Dump database lokal
// ------------------------------------------------------------------
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'kuis_sd';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

$mysqldumpCandidates = [
    'D:/xampp/mysql/bin/mysqldump.exe',
    'mysqldump',
];
$mysqldumpBin = null;
foreach ($mysqldumpCandidates as $cand) {
    if ($cand === 'mysqldump' || is_file($cand)) {
        $mysqldumpBin = $cand;
        break;
    }
}
if (!$mysqldumpBin) {
    fwrite(STDERR, "GAGAL: mysqldump tidak ditemukan.\n");
    exit(1);
}

$sqlOut = $outDir . "/sigap-db-{$timestamp}.sql";
$passPart = $dbPass !== '' ? '-p' . escapeshellarg($dbPass) : '';
$cmd = sprintf(
    '%s --host=%s --user=%s %s --default-character-set=utf8mb4 --single-transaction --routines --triggers %s > %s',
    escapeshellarg($mysqldumpBin),
    escapeshellarg($dbHost),
    escapeshellarg($dbUser),
    $passPart,
    escapeshellarg($dbName),
    escapeshellarg($sqlOut)
);
echo "Dumping database '{$dbName}'...\n";
exec($cmd . ' 2>&1', $out, $code);
if ($code !== 0 || !is_file($sqlOut) || filesize($sqlOut) === 0) {
    fwrite(STDERR, "GAGAL dump database:\n" . implode("\n", $out) . "\n");
    exit(1);
}
echo "  -> OK: " . basename($sqlOut) . " (" . round(filesize($sqlOut) / 1024, 1) . " KB)\n\n";

// ------------------------------------------------------------------
// 2) Susun file project yang akan diupload (exclude file/folder khusus dev)
// ------------------------------------------------------------------
$excludeTopLevel = [
    '.git', '.vscode', '.claude', 'deploy',
    'KELAS_9',                          // dokumen sumber kurikulum mentah, tidak dipakai runtime
    'spesifikasi-kuis-php-mysql.md',    // dokumen internal dev
    '.env',                             // jangan ikut kebawa punya lokal, kita generate versi produksi terpisah
    '.env.example',
    '.htaccess',                        // versi lokal (RewriteBase /RAMAHOME/), kita ganti versi produksi
];

$stageDir = $outDir . "/sigap-deploy-{$timestamp}";
if (is_dir($stageDir)) {
    removeDirRecursive($stageDir);
}
mkdir($stageDir, 0777, true);

echo "Menyalin file project ke staging folder...\n";
foreach (scandir($projectRoot) as $entry) {
    if ($entry === '.' || $entry === '..') continue;
    if (in_array($entry, $excludeTopLevel, true)) continue;
    copyRecursive($projectRoot . '/' . $entry, $stageDir . '/' . $entry);
}
echo "  -> OK\n\n";

// ------------------------------------------------------------------
// 3) Tulis .env produksi (kredensial diisi manual nanti setelah DB dibuat di cPanel)
// ------------------------------------------------------------------
$envProd = <<<ENV
DB_HOST=localhost
DB_NAME=GANTI_dengan_nama_database_cpanel
DB_USER=GANTI_dengan_user_database_cpanel
DB_PASS=GANTI_dengan_password_database_cpanel

SISWA_MAGIC_KEYWORD=GANTI_KATA_KUNCI_DI_ENV

# WAJIB diisi manual lewat File Manager (Code Editor) setelah upload -- JANGAN pernah taruh
# API key asli di dalam paket zip yang bisa saja lewat email/channel lain yang kurang aman.
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
ENV;
file_put_contents($stageDir . '/.env', $envProd);

// ------------------------------------------------------------------
// 4) Tulis .htaccess produksi (RewriteBase "/" karena document root subdomain = root project ini,
//    ditambah blokir akses langsung ke folder non-publik & file .sql/.env)
// ------------------------------------------------------------------
$htaccessProd = <<<HT
# Buka root project -> langsung diarahkan ke form login (public/login.php)
RewriteEngine On
RewriteBase /

# Blokir akses langsung ke folder non-publik (kode PHP internal, config, SQL, dsb) lewat browser
RewriteRule ^(config|includes|sql)/ - [F,L]

# Root project (https://sigap.jejakinovasi.com/) -> form login
RewriteRule ^\$ public/login.php [R=302,L]

# Jangan izinkan .env (berisi kredensial/API key) diakses langsung lewat browser
<Files ".env">
    Require all denied
</Files>

# Jangan izinkan file .sql diakses langsung lewat browser
<FilesMatch "\\.sql\$">
    Require all denied
</FilesMatch>
HT;
file_put_contents($stageDir . '/.htaccess', $htaccessProd);

echo "Menulis .env & .htaccess versi produksi ke staging folder...\n  -> OK\n\n";

// ------------------------------------------------------------------
// 5) Zip staging folder
// ------------------------------------------------------------------
$zipPath = $outDir . "/sigap-deploy-{$timestamp}.zip";
echo "Membuat zip...\n";
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "GAGAL membuat zip.\n");
    exit(1);
}
addDirToZip($zip, $stageDir, '');
$zip->close();
echo "  -> OK: " . basename($zipPath) . " (" . round(filesize($zipPath) / 1024, 1) . " KB)\n\n";

removeDirRecursive($stageDir);

echo "=== SELESAI ===\n";
echo "1. Database dump : " . $sqlOut . "\n";
echo "2. Paket file zip: " . $zipPath . "\n\n";
echo "Langkah selanjutnya ada di deploy/PANDUAN_DEPLOY_CPANEL.md\n";

// ------------------------------------------------------------------
// Helper functions
// ------------------------------------------------------------------
function copyRecursive(string $src, string $dst): void
{
    if (is_dir($src)) {
        mkdir($dst, 0777, true);
        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') continue;
            copyRecursive($src . '/' . $item, $dst . '/' . $item);
        }
    } elseif (is_file($src)) {
        copy($src, $dst);
    }
}

function removeDirRecursive(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        is_dir($path) ? removeDirRecursive($path) : unlink($path);
    }
    rmdir($dir);
}

function addDirToZip(ZipArchive $zip, string $dir, string $zipPathPrefix): void
{
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . '/' . $item;
        $zipPath = $zipPathPrefix === '' ? $item : $zipPathPrefix . '/' . $item;
        if (is_dir($full)) {
            $zip->addEmptyDir($zipPath);
            addDirToZip($zip, $full, $zipPath);
        } else {
            $zip->addFile($full, $zipPath);
        }
    }
}
