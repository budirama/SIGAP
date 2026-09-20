<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
requireGuru();

$db = getDb();
$userId = (int)($_GET['user_id'] ?? 0);

$siswaStmt = $db->prepare("SELECT id, nama, jenjang FROM users WHERE id = ? AND role = 'siswa'");
$siswaStmt->execute([$userId]);
$siswa = $siswaStmt->fetch();
if (!$siswa) {
    jsonError(404, 'Siswa tidak ditemukan.');
}

// mapel per sesi diturunkan dari kisi-kisi soal yang benar-benar dikerjakan (hasil_detail), bukan
// dari kolom materi di hasil_sesi — supaya akurat juga untuk sesi "Campur Semua" yang bisa
// mencakup lebih dari satu mata pelajaran sekaligus.
$riwayatStmt = $db->prepare(
    "SELECT h.*, GROUP_CONCAT(DISTINCT k.mapel ORDER BY k.mapel SEPARATOR ', ') AS mapel
     FROM hasil_sesi h
     LEFT JOIN hasil_detail d ON d.hasil_id = h.id
     LEFT JOIN kisi_kisi k ON k.id = d.kisi_id
     WHERE h.user_id = ?
     GROUP BY h.id
     ORDER BY h.waktu ASC"
);
$riwayatStmt->execute([$userId]);
$riwayat = $riwayatStmt->fetchAll();

$skorArr = array_column($riwayat, 'skor');
$n = count($skorArr);
$rataRata = $n ? array_sum($skorArr) / $n : 0;
$tertinggi = $n ? max($skorArr) : 0;
$terendah = $n ? min($skorArr) : 0;

// Nilai per mata pelajaran: dihitung dari persentase jawaban benar per mapel di seluruh riwayat
// jawaban siswa (join hasil_detail -> kisi_kisi), bukan dari rata-rata skor per sesi — supaya
// akurat walau sesi campur beberapa materi/mapel sekaligus ("Campur Semua").
$mapelStmt = $db->prepare(
    "SELECT k.mapel, COUNT(*) AS total_soal, SUM(d.benar) AS total_benar
     FROM hasil_detail d
     JOIN hasil_sesi h ON h.id = d.hasil_id
     JOIN kisi_kisi k ON k.id = d.kisi_id
     WHERE h.user_id = ?
     GROUP BY k.mapel
     ORDER BY k.mapel ASC"
);
$mapelStmt->execute([$userId]);
$perMapelRaw = $mapelStmt->fetchAll();

function predikat(float $nilai): array
{
    if ($nilai >= 86) return ['A', 'Sangat Baik'];
    if ($nilai >= 71) return ['B', 'Baik'];
    if ($nilai >= 56) return ['C', 'Cukup'];
    return ['D', 'Perlu Bimbingan'];
}

$perMapel = array_map(function ($m) {
    $nilai = $m['total_soal'] ? round(($m['total_benar'] / $m['total_soal']) * 100, 1) : 0;
    [$huruf, $keterangan] = predikat($nilai);
    return [
        'mapel' => $m['mapel'],
        'jumlah_soal_dikerjakan' => (int)$m['total_soal'],
        'nilai' => $nilai,
        'predikat' => $huruf,
        'keterangan' => $keterangan,
    ];
}, $perMapelRaw);

echo json_encode([
    'siswa' => ['nama' => $siswa['nama'], 'jenjang' => $siswa['jenjang'], 'jenjang_label' => labelJenjang($siswa['jenjang'])],
    'ringkasan' => [
        'total_sesi' => $n,
        'rata_rata' => round($rataRata, 1),
        'skor_tertinggi' => $tertinggi,
        'skor_terendah' => $terendah,
    ],
    'per_mapel' => $perMapel,
    'riwayat' => $riwayat,
]);
