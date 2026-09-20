<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai.php';

header('Content-Type: application/json');
requireGuru();

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$kisiId = (int)($data['kisi_id'] ?? 0);
$jumlah = max(1, min(10, (int)($data['jumlah'] ?? 5)));

$db = getDb();
$kisiStmt = $db->prepare("SELECT * FROM kisi_kisi WHERE id = ?");
$kisiStmt->execute([$kisiId]);
$k = $kisiStmt->fetch();
if (!$k) {
    jsonError(404, 'Kisi-kisi tidak ditemukan.');
}

$sertakanVisual = in_array($k['jenjang'], ['SD1', 'SD6', 'SMP9'], true); // diaktifkan utk kelas 1 SD, kelas 6 SD, dan SMP kelas 9
try {
    $ditambahkan = generateSoalForKisi($db, $k, $jumlah, $sertakanVisual);
} catch (AiError $e) {
    jsonError(502, $e->getMessage());
}

echo json_encode(['ditambahkan' => $ditambahkan]);
