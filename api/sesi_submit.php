<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
$u = requireSiswa();

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$kunci = $_SESSION['sesi_kunci'] ?? [];
$kisiMap = $_SESSION['sesi_kisi_map'] ?? [];
$materi = $_SESSION['sesi_materi'] ?? '__semua__';
$db = getDb();

if (!$kunci || !isset($data['jawaban']) || !is_array($data['jawaban'])) {
    jsonError(400, 'Tidak ada sesi latihan aktif. Mulai sesi dulu.');
}

// Nilai dihitung dari seluruh soal sesi yang disimpan server, bukan dari subset
// yang dikirim klien. Soal yang tidak dijawab (atau ID di luar sesi) tetap salah,
// supaya menghilangkan jawaban yang salah tidak menaikkan skor.
$jawabanMasuk = $data['jawaban'];
$jumlahBenar = 0;
$detail = [];
foreach ($kunci as $soalId => $jawabanBenar) {
    $dipilih = $jawabanMasuk[$soalId] ?? null;
    if (is_string($dipilih)) {
        $dipilih = strtolower(trim($dipilih));
    } else {
        $dipilih = null;
    }
    if (!in_array($dipilih, ['a', 'b', 'c', 'd'], true)) {
        $dipilih = null;
    }
    $benar = $dipilih !== null && $dipilih === $jawabanBenar;
    if ($benar) $jumlahBenar++;
    $detail[] = [
        'soal_id' => (int)$soalId,
        'kisi_id' => (int)($kisiMap[$soalId] ?? 0),
        'jawaban' => $dipilih,
        'benar' => $benar,
    ];
}
$total = count($kunci);
$skor = $total ? (int) round(($jumlahBenar / $total) * 100) : 0;

$db->beginTransaction();
try {
    $db->prepare("INSERT INTO hasil_sesi (user_id, jenjang, materi, jumlah_soal, jumlah_benar, skor) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$u['id'], $u['jenjang'], $materi, $total, $jumlahBenar, $skor]);
    $hasilId = $db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO hasil_detail (hasil_id, soal_id, kisi_id, jawaban_dipilih, benar) VALUES (?, ?, ?, ?, ?)");
    foreach ($detail as $d) {
        $stmt->execute([$hasilId, $d['soal_id'], $d['kisi_id'] ?: null, $d['jawaban'], $d['benar'] ? 1 : 0]);
    }
    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    jsonError(500, 'Gagal menyimpan hasil latihan.');
}

unset($_SESSION['sesi_kunci'], $_SESSION['sesi_kisi_map'], $_SESSION['sesi_materi']);

echo json_encode(['skor' => $skor, 'jumlah_benar' => $jumlahBenar, 'total' => $total]);
