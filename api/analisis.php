<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
requireGuru();
$db = getDb();

$jenjang = $_GET['jenjang'] ?? null;
$sql = "SELECT h.*, u.nama AS nama_siswa FROM hasil_sesi h JOIN users u ON u.id = h.user_id"
    . ($jenjang ? " WHERE h.jenjang = ?" : "") . " ORDER BY h.waktu DESC";
$stmt = $db->prepare($sql);
$stmt->execute($jenjang ? [$jenjang] : []);
$hasil = $stmt->fetchAll();

$skorArr = array_column($hasil, 'skor');
$n = count($skorArr);
$mean = $n ? array_sum($skorArr) / $n : 0;
$sd = $n ? sqrt(array_sum(array_map(fn($s) => ($s - $mean) ** 2, $skorArr)) / $n) : 0;

$siswa = array_map(fn($h) => [
    'nama' => $h['nama_siswa'],
    'jenjang' => $h['jenjang'],
    'materi' => $h['materi'],
    'skor' => $h['skor'],
    'waktu' => $h['waktu'],
    'kategori' => kategoriSigma((float)$h['skor'], $mean, $sd),
], $hasil);

sort($skorArr);
$mid = intdiv($n, 2);
$median = $n === 0 ? 0 : ($n % 2 ? $skorArr[$mid] : ($skorArr[$mid - 1] + $skorArr[$mid]) / 2);

$idAtas = array_column(array_filter($hasil, fn($h) => $h['skor'] >= $median), 'id');
$idBawah = array_column(array_filter($hasil, fn($h) => $h['skor'] < $median), 'id');

$rows = $db->query(
    "SELECT d.soal_id, d.hasil_id, d.benar, s.pertanyaan, s.mapel, s.materi, s.jenjang
     FROM hasil_detail d JOIN soal s ON s.id = d.soal_id"
)->fetchAll();

$perSoal = [];
foreach ($rows as $r) {
    $id = $r['soal_id'];
    $perSoal[$id] ??= [
        'pertanyaan' => $r['pertanyaan'], 'mapel' => $r['mapel'], 'materi' => $r['materi'], 'jenjang' => $r['jenjang'],
        'benar' => 0, 'total' => 0, 'benarAtas' => 0, 'totalAtas' => 0, 'benarBawah' => 0, 'totalBawah' => 0,
    ];
    $perSoal[$id]['total']++;
    if ($r['benar']) $perSoal[$id]['benar']++;
    if (in_array($r['hasil_id'], $idAtas)) {
        $perSoal[$id]['totalAtas']++;
        if ($r['benar']) $perSoal[$id]['benarAtas']++;
    } else {
        $perSoal[$id]['totalBawah']++;
        if ($r['benar']) $perSoal[$id]['benarBawah']++;
    }
}

$itemAnalysis = [];
foreach ($perSoal as $id => $p) {
    $P = $p['total'] ? $p['benar'] / $p['total'] : 0;
    $pAtas = $p['totalAtas'] ? $p['benarAtas'] / $p['totalAtas'] : 0;
    $pBawah = $p['totalBawah'] ? $p['benarBawah'] / $p['totalBawah'] : 0;
    $itemAnalysis[] = [
        'soal_id' => $id, 'pertanyaan' => $p['pertanyaan'], 'mapel' => $p['mapel'], 'materi' => $p['materi'], 'jenjang' => $p['jenjang'],
        'P' => round($P, 2), 'D' => round($pAtas - $pBawah, 2),
    ];
}

echo json_encode([
    'ringkasan' => ['total_sesi' => $n, 'rata_rata' => round($mean, 1), 'simpangan_baku' => round($sd, 1)],
    'siswa' => $siswa,
    'butir_soal' => $itemAnalysis,
]);
