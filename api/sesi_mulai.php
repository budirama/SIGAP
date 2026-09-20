<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
$u = requireSiswa();

$materi = $_GET['materi'] ?? '';
$mapel = $_GET['mapel'] ?? '';
$db = getDb();

if ($materi !== '' && $materi !== '__semua__') {
    $sql = "SELECT * FROM kisi_kisi WHERE jenjang = ? AND materi = ?";
    $params = [$u['jenjang'], $materi];
    $filterLabel = $materi;
} elseif ($mapel !== '') {
    $sql = "SELECT * FROM kisi_kisi WHERE jenjang = ? AND mapel = ?";
    $params = [$u['jenjang'], $mapel];
    $filterLabel = $mapel;
} else {
    $sql = "SELECT * FROM kisi_kisi WHERE jenjang = ?";
    $params = [$u['jenjang']];
    $filterLabel = '__semua__';
}
$stmt = $db->prepare($sql);
$stmt->execute($params);
$barisKisi = $stmt->fetchAll();

$terpilih = [];
foreach ($barisKisi as $k) {
    $s = $db->prepare("SELECT * FROM soal WHERE kisi_id = ? ORDER BY RAND() LIMIT ?");
    $s->bindValue(1, $k['id'], PDO::PARAM_INT);
    $s->bindValue(2, (int)$k['jumlah_soal'], PDO::PARAM_INT);
    $s->execute();
    foreach ($s->fetchAll() as $row) {
        $terpilih[] = $row;
    }
}
shuffle($terpilih);

if (!$terpilih) {
    jsonError(404, 'Belum ada soal untuk materi ini. Minta guru menambahkan soal dulu.');
}

$hasilAcak = array_map(function ($s) {
    $opsi = [
        ['id' => 'a', 'text' => $s['opsi_a']],
        ['id' => 'b', 'text' => $s['opsi_b']],
        ['id' => 'c', 'text' => $s['opsi_c']],
        ['id' => 'd', 'text' => $s['opsi_d']],
    ];
    shuffle($opsi);
    return [
        'soal_id' => $s['id'],
        'kisi_id' => $s['kisi_id'],
        'pertanyaan' => $s['pertanyaan'],
        'opsi' => $opsi,
        'visual_tipe' => $s['visual_tipe'],
        'visual_data' => $s['visual_data'] ? json_decode($s['visual_data'], true) : null,
    ];
}, $terpilih);

$_SESSION['sesi_kunci'] = array_column($terpilih, 'kunci', 'id');
$_SESSION['sesi_kisi_map'] = array_column($terpilih, 'kisi_id', 'id');
$_SESSION['sesi_materi'] = $filterLabel;

echo json_encode($hasilAcak);
