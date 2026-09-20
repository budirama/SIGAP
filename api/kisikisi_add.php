<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
$u = requireGuru();

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$jenjang = $data['jenjang'] ?? '';
$mapel = trim($data['mapel'] ?? '');
$materi = trim($data['materi'] ?? '');
$indikator = trim($data['indikator'] ?? '');
$level = trim($data['level'] ?? '');
$kesulitan = $data['kesulitan'] ?? 'Sedang';
$jumlah = max(1, (int)($data['jumlah'] ?? 1));

if (!in_array($jenjang, ['SD1', 'SD6', 'SMP9'], true)) {
    jsonError(400, 'Jenjang tidak valid.');
}
if ($mapel === '' || $materi === '' || $indikator === '' || $level === '') {
    jsonError(400, 'Mata pelajaran, materi, indikator, dan level kognitif wajib diisi.');
}
if (!in_array($kesulitan, ['Mudah', 'Sedang', 'Sukar'], true)) {
    jsonError(400, 'Kesulitan tidak valid.');
}

$stmt = getDb()->prepare(
    "INSERT INTO kisi_kisi (jenjang, mapel, materi, indikator, level_kognitif, kesulitan, jumlah_soal, dibuat_oleh)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->execute([$jenjang, $mapel, $materi, $indikator, $level, $kesulitan, $jumlah, $u['id']]);

echo json_encode(['id' => getDb()->lastInsertId()]);
