<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$u = currentUser();
$db = getDb();

if ($u && $u['role'] === 'siswa') {
    $stmt = $db->prepare("SELECT * FROM kisi_kisi WHERE jenjang = ? ORDER BY dibuat_pada ASC");
    $stmt->execute([$u['jenjang']]);
} else {
    requireGuru();
    $jenjang = $_GET['jenjang'] ?? null;
    $sql = "SELECT * FROM kisi_kisi" . ($jenjang ? " WHERE jenjang = ?" : "") . " ORDER BY dibuat_pada ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($jenjang ? [$jenjang] : []);
}

echo json_encode($stmt->fetchAll());
