<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai.php';

header('Content-Type: application/json');
requireGuru();

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$kisiId = (int)($data['kisi_id'] ?? 0);
$instruksi = trim($data['instruksi'] ?? '');

$db = getDb();
$kisiStmt = $db->prepare("SELECT * FROM kisi_kisi WHERE id = ?");
$kisiStmt->execute([$kisiId]);
$k = $kisiStmt->fetch();
if (!$k) {
    jsonError(404, 'Kisi-kisi tidak ditemukan.');
}

try {
    generateModulForKisi($db, $k, $instruksi);
} catch (AiError $e) {
    jsonError(502, $e->getMessage());
}

echo json_encode(['ok' => true]);
