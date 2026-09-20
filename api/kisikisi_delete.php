<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
requireGuru();

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$id = (int)($data['id'] ?? 0);
if ($id <= 0) {
    jsonError(400, 'ID tidak valid.');
}

$stmt = getDb()->prepare("DELETE FROM kisi_kisi WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(['ok' => true]);
