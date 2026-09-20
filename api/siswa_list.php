<?php

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$rows = getDb()->query("SELECT id, nama, jenjang FROM users WHERE role = 'siswa' ORDER BY nama ASC")->fetchAll();

echo json_encode($rows);
