<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai.php';

header('Content-Type: application/json');
$u = requireGuru();

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$jenjang = $data['jenjang'] ?? '';
$mapel = trim($data['mapel'] ?? '');
$jumlahMateri = max(1, min(10, (int)($data['jumlah_materi'] ?? 5)));
$jumlahSoalPerMateri = max(1, min(10, (int)($data['jumlah_soal'] ?? 3)));
$buatkanSoal = !empty($data['buatkan_soal']);
$buatkanModul = !empty($data['buatkan_modul']);

if (!in_array($jenjang, ['SD1', 'SD6', 'SMP9'], true)) {
    jsonError(400, 'Jenjang tidak valid.');
}
if ($mapel === '') {
    jsonError(400, 'Mata pelajaran wajib diisi.');
}

$db = getDb();
$labelJenjangMap = ['SD1' => 'kelas 1 SD (usia 6-7 tahun)', 'SD6' => 'kelas 6 SD', 'SMP9' => 'kelas 9 SMP/MTs'];
$labelJenjang = $labelJenjangMap[$jenjang] ?? $jenjang;

$existingStmt = $db->prepare("SELECT materi FROM kisi_kisi WHERE jenjang = ? AND mapel = ?");
$existingStmt->execute([$jenjang, $mapel]);
$materiLama = array_column($existingStmt->fetchAll(), 'materi');

$system = 'Kamu adalah penyusun kisi-kisi soal ujian mengikuti kurikulum sekolah di Indonesia. '
    . 'Selalu balas HANYA dengan JSON object valid, tanpa penjelasan tambahan.';
$user = "Buatkan kisi-kisi soal untuk mata pelajaran \"{$mapel}\" siswa {$labelJenjang} di Indonesia, "
    . "sebanyak {$jumlahMateri} baris materi/indikator yang berbeda-beda dan representatif untuk mata pelajaran tsb.\n"
    . (count($materiLama) ? "Jangan mengulang materi berikut yang sudah ada:\n- " . implode("\n- ", $materiLama) . "\n" : "")
    . "Setiap baris berisi: materi (topik spesifik), indikator (kalimat 'siswa dapat ...'), "
    . "level_kognitif (salah satu dari C1 C2 C3 C4 C5 C6), kesulitan (salah satu dari Mudah Sedang Sukar).\n"
    . 'Balas dengan JSON object berbentuk: {"kisi_kisi":[{"materi":"...","indikator":"...","level_kognitif":"C2","kesulitan":"Sedang"}]}';

try {
    $result = callOpenAiJson($system, $user, 2500);
} catch (AiError $e) {
    jsonError(502, $e->getMessage());
}

$daftarKisi = $result['kisi_kisi'] ?? [];
if (!$daftarKisi) {
    jsonError(502, 'AI tidak mengembalikan kisi-kisi apa pun. Coba lagi.');
}

$insertStmt = $db->prepare(
    "INSERT INTO kisi_kisi (jenjang, mapel, materi, indikator, level_kognitif, kesulitan, jumlah_soal, dibuat_oleh)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

$kisiDitambahkan = [];
foreach ($daftarKisi as $item) {
    $materi = trim($item['materi'] ?? '');
    $indikator = trim($item['indikator'] ?? '');
    $level = strtoupper(trim($item['level_kognitif'] ?? 'C2'));
    $kesulitan = $item['kesulitan'] ?? 'Sedang';
    if ($materi === '' || $indikator === '') continue;
    if (!in_array($level, ['C1', 'C2', 'C3', 'C4', 'C5', 'C6'], true)) $level = 'C2';
    if (!in_array($kesulitan, ['Mudah', 'Sedang', 'Sukar'], true)) $kesulitan = 'Sedang';

    $insertStmt->execute([$jenjang, $mapel, $materi, $indikator, $level, $kesulitan, $jumlahSoalPerMateri, $u['id']]);
    $kisiDitambahkan[] = [
        'id' => $db->lastInsertId(), 'jenjang' => $jenjang, 'mapel' => $mapel, 'materi' => $materi,
        'indikator' => $indikator, 'level_kognitif' => $level, 'kesulitan' => $kesulitan,
    ];
}

$totalSoal = 0;
$totalModul = 0;
$gagalSoal = [];
$gagalModul = [];
$sertakanVisual = in_array($jenjang, ['SD1', 'SD6', 'SMP9'], true); // diaktifkan utk kelas 1 SD, kelas 6 SD, dan SMP kelas 9

foreach ($kisiDitambahkan as $k) {
    if ($buatkanModul) {
        try {
            generateModulForKisi($db, $k);
            $totalModul++;
        } catch (AiError $e) {
            $gagalModul[] = $k['materi'] . ': ' . $e->getMessage();
        }
    }
    if ($buatkanSoal) {
        try {
            $totalSoal += generateSoalForKisi($db, $k, $jumlahSoalPerMateri, $sertakanVisual);
        } catch (AiError $e) {
            $gagalSoal[] = $k['materi'] . ': ' . $e->getMessage();
        }
    }
}

echo json_encode([
    'kisi_ditambahkan' => count($kisiDitambahkan),
    'soal_ditambahkan' => $totalSoal,
    'modul_ditambahkan' => $totalModul,
    'gagal_soal' => $gagalSoal,
    'gagal_modul' => $gagalModul,
]);
