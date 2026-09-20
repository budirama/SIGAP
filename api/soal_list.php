<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$u = currentUser();
$db = getDb();

$where = [];
$params = [];

if ($u && $u['role'] === 'siswa') {
    $where[] = 'jenjang = ?';
    $params[] = $u['jenjang'];
} else {
    requireGuru();
    if (!empty($_GET['jenjang'])) {
        $where[] = 'jenjang = ?';
        $params[] = $_GET['jenjang'];
    }
}

if (!empty($_GET['kisi_id'])) {
    $where[] = 'kisi_id = ?';
    $params[] = (int)$_GET['kisi_id'];
}
if (!empty($_GET['materi'])) {
    $where[] = 'materi = ?';
    $params[] = $_GET['materi'];
}

$sql = 'SELECT * FROM soal' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY dibuat_pada DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);

echo json_encode($stmt->fetchAll());
