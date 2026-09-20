<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/ai.php';

header('Content-Type: application/json');
requireGuru();

$db = getDb();
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$userId = (int)($_GET['user_id'] ?? ($body['user_id'] ?? 0));

$siswaStmt = $db->prepare("SELECT id, nama, jenjang FROM users WHERE id = ? AND role = 'siswa'");
$siswaStmt->execute([$userId]);
$siswa = $siswaStmt->fetch();
if (!$siswa) {
    jsonError(404, 'Siswa tidak ditemukan.');
}

// Ringkasan umum
$riwayatStmt = $db->prepare("SELECT skor, waktu FROM hasil_sesi WHERE user_id = ? ORDER BY waktu ASC");
$riwayatStmt->execute([$userId]);
$riwayat = $riwayatStmt->fetchAll();
$n = count($riwayat);

if ($n < 3) {
    jsonError(400, 'Riwayat latihan siswa ini masih terlalu sedikit (minimal 3 sesi) untuk dibuatkan analisis yang bermakna.');
}

$skorArr = array_column($riwayat, 'skor');
$rataRata = round(array_sum($skorArr) / $n, 1);

// Tren: bandingkan rata-rata paruh pertama vs paruh kedua riwayat (kronologis)
$tengah = intdiv($n, 2);
$paruhAwal = array_slice($skorArr, 0, max(1, $tengah));
$paruhAkhir = array_slice($skorArr, $tengah);
$rataAwal = round(array_sum($paruhAwal) / count($paruhAwal), 1);
$rataAkhir = round(array_sum($paruhAkhir) / count($paruhAkhir), 1);
$tren = $rataAkhir - $rataAwal >= 5 ? 'meningkat' : ($rataAwal - $rataAkhir >= 5 ? 'menurun' : 'stabil');

// Nilai per mata pelajaran
$mapelStmt = $db->prepare(
    "SELECT k.mapel, COUNT(*) AS total, SUM(d.benar) AS benar
     FROM hasil_detail d JOIN hasil_sesi h ON h.id = d.hasil_id JOIN kisi_kisi k ON k.id = d.kisi_id
     WHERE h.user_id = ? GROUP BY k.mapel ORDER BY k.mapel"
);
$mapelStmt->execute([$userId]);
$perMapel = array_map(function ($r) {
    return ['mapel' => $r['mapel'], 'nilai' => round(($r['benar'] / $r['total']) * 100, 1)];
}, $mapelStmt->fetchAll());

// Nilai per materi (butuh minimal 2 soal dikerjakan biar tidak berisik) — untuk cari yang terlemah & terkuat
$materiStmt = $db->prepare(
    "SELECT k.mapel, k.materi, COUNT(*) AS total, SUM(d.benar) AS benar
     FROM hasil_detail d JOIN hasil_sesi h ON h.id = d.hasil_id JOIN kisi_kisi k ON k.id = d.kisi_id
     WHERE h.user_id = ? GROUP BY k.mapel, k.materi HAVING COUNT(*) >= 2"
);
$materiStmt->execute([$userId]);
$perMateri = array_map(function ($r) {
    return ['mapel' => $r['mapel'], 'materi' => $r['materi'], 'nilai' => round(($r['benar'] / $r['total']) * 100, 1)];
}, $materiStmt->fetchAll());

usort($perMateri, fn($a, $b) => $a['nilai'] <=> $b['nilai']);
$materiTerlemah = array_slice($perMateri, 0, 5);
$materiTerkuat = array_slice(array_reverse($perMateri), 0, 5);

$labelJenjang = labelJenjang($siswa['jenjang']);
$listMapel = implode('; ', array_map(fn($m) => "{$m['mapel']}: {$m['nilai']}", $perMapel));
$listLemah = implode('; ', array_map(fn($m) => "{$m['materi']} ({$m['mapel']}): {$m['nilai']}", $materiTerlemah));
$listKuat = implode('; ', array_map(fn($m) => "{$m['materi']} ({$m['mapel']}): {$m['nilai']}", $materiTerkuat));

$system = 'Kamu adalah pendidik berpengalaman yang menulis catatan naratif rapor untuk orang tua siswa di Indonesia. '
    . 'Gaya bahasa hangat, membangun, spesifik berdasarkan data (bukan template generik), dan tidak menghakimi. '
    . 'Selalu balas HANYA dengan JSON object valid, tanpa penjelasan tambahan.';

$user = "Buatkan analisis naratif perkembangan belajar untuk siswa berikut, dalam Bahasa Indonesia.\n\n"
    . "Nama: {$siswa['nama']}\nJenjang: {$labelJenjang}\n"
    . "Total sesi latihan: {$n}\nRata-rata skor keseluruhan: {$rataRata}\n"
    . "Tren (paruh awal {$rataAwal} vs paruh akhir {$rataAkhir} riwayat): {$tren}\n"
    . "Nilai per mata pelajaran (skala 0-100): {$listMapel}\n"
    . ($listLemah ? "Materi dengan nilai terendah (kandidat area yang perlu diperkuat): {$listLemah}\n" : "")
    . ($listKuat ? "Materi dengan nilai tertinggi (kandidat kekuatan): {$listKuat}\n" : "")
    . "\nTulis 4 bagian pendek (masing-masing 2-3 kalimat, bahasa Indonesia yang mudah dipahami orang tua):\n"
    . "1. ringkasan_umum: gambaran umum performa dan tren belajar siswa.\n"
    . "2. kekuatan: kekuatan siswa, sebutkan mata pelajaran/materi spesifik dari data di atas (bukan generik).\n"
    . "3. perlu_diperkuat: area spesifik yang perlu diperkuat, sebutkan materi/mapel spesifik dari data di atas, dengan nada membangun bukan menghakimi.\n"
    . "4. rekomendasi: saran konkret dan praktis untuk orang tua/guru dalam mendampingi anak berlatih materi yang lemah tsb.\n"
    . 'Balas dengan JSON: {"ringkasan_umum":"...","kekuatan":"...","perlu_diperkuat":"...","rekomendasi":"..."}';

try {
    $hasil = callOpenAiJson($system, $user, 1200);
} catch (AiError $e) {
    jsonError(502, $e->getMessage());
}

echo json_encode([
    'siswa' => ['nama' => $siswa['nama'], 'jenjang_label' => $labelJenjang],
    'narasi' => [
        'ringkasan_umum' => $hasil['ringkasan_umum'] ?? '',
        'kekuatan' => $hasil['kekuatan'] ?? '',
        'perlu_diperkuat' => $hasil['perlu_diperkuat'] ?? '',
        'rekomendasi' => $hasil['rekomendasi'] ?? '',
    ],
]);
