<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai.php';

header('Content-Type: application/json');
requireGuru();

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$db = getDb();

$kisiId = (int)($data['kisi_id'] ?? 0);
$pertanyaan = trim($data['pertanyaan'] ?? '');
$a = trim($data['a'] ?? '');
$b = trim($data['b'] ?? '');
$c = trim($data['c'] ?? '');
$d = trim($data['d'] ?? '');
$kunci = strtolower(trim($data['kunci'] ?? ''));
$pembahasan = trim($data['pembahasan'] ?? '');

if ($pertanyaan === '' || $a === '' || $b === '' || $c === '' || $d === '') {
    jsonError(400, 'Pertanyaan dan 4 opsi jawaban wajib diisi.');
}
if (!in_array($kunci, ['a', 'b', 'c', 'd'], true)) {
    jsonError(400, 'Kunci jawaban harus salah satu dari a/b/c/d.');
}

$kisiStmt = $db->prepare("SELECT jenjang, mapel, materi, kesulitan FROM kisi_kisi WHERE id = ?");
$kisiStmt->execute([$kisiId]);
$k = $kisiStmt->fetch();
if (!$k) {
    jsonError(404, 'Kisi-kisi tidak ditemukan.');
}

[$visualTipe, $visualData] = sanitizeVisual(buildVisualInputFromForm($data));

$stmt = $db->prepare(
    "INSERT INTO soal (kisi_id, jenjang, mapel, materi, kesulitan, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, kunci, pembahasan, visual_tipe, visual_data, sumber)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'manual')"
);
$stmt->execute([
    $kisiId, $k['jenjang'], $k['mapel'], $k['materi'], $k['kesulitan'], $pertanyaan,
    $a, $b, $c, $d, $kunci, $pembahasan, $visualTipe, $visualData,
]);

echo json_encode(['id' => $db->lastInsertId()]);
