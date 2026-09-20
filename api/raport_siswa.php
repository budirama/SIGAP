<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
$u = requireSiswa();
$db = getDb();

// mapel per sesi diturunkan dari kisi-kisi soal yang benar-benar dikerjakan (hasil_detail), bukan
// dari kolom materi di hasil_sesi — supaya akurat juga untuk sesi "Campur Semua".
$stmt = $db->prepare(
    "SELECT h.*, GROUP_CONCAT(DISTINCT k.mapel ORDER BY k.mapel SEPARATOR ', ') AS mapel
     FROM hasil_sesi h
     LEFT JOIN hasil_detail d ON d.hasil_id = h.id
     LEFT JOIN kisi_kisi k ON k.id = d.kisi_id
     WHERE h.user_id = ?
     GROUP BY h.id
     ORDER BY h.waktu DESC"
);
$stmt->execute([$u['id']]);
$riwayat = $stmt->fetchAll();

$skorArr = array_column($riwayat, 'skor');
$n = count($skorArr);
$mean = $n ? array_sum($skorArr) / $n : 0;
$terbaik = $n ? max($skorArr) : 0;

echo json_encode([
    'nama' => $u['nama'],
    'jenjang' => $u['jenjang'],
    'ringkasan' => ['total_sesi' => $n, 'rata_rata' => round($mean, 1), 'skor_terbaik' => $terbaik],
    'riwayat' => $riwayat,
]);
