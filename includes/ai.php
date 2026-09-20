<?php

require_once __DIR__ . '/../config/env.php';
loadEnv();

class AiError extends Exception
{
}

/**
 * Panggil OpenAI Chat Completions API dan kembalikan hasil parse JSON dari jawabannya.
 * $prompt harus secara eksplisit meminta model membalas HANYA dengan JSON.
 */
function callOpenAiJson(string $systemPrompt, string $userPrompt, int $maxTokens = 2000): array
{
    $apiKey = getenv('OPENAI_API_KEY');
    if (!$apiKey) {
        throw new AiError('OPENAI_API_KEY belum diisi di .env — fitur AI tidak aktif.');
    }
    $model = getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => [
            'content-type: application/json',
            'authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => $model,
            'max_tokens' => $maxTokens,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]),
    ]);
    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        throw new AiError('Gagal memanggil API AI: ' . $curlError);
    }

    $resp = json_decode($raw, true);
    if ($httpCode >= 400) {
        $msg = $resp['error']['message'] ?? ('HTTP ' . $httpCode);
        throw new AiError('API AI menolak permintaan: ' . $msg);
    }

    $text = $resp['choices'][0]['message']['content'] ?? '';
    $text = preg_replace('/```json|```/', '', $text);
    $data = json_decode(trim($text), true);
    if (!is_array($data)) {
        throw new AiError('Jawaban AI tidak berupa JSON yang valid.');
    }

    return $data;
}

const VISUAL_BENTUK_DATAR_VALID = ['lingkaran', 'persegi', 'persegi_panjang', 'segitiga', 'segilima', 'segienam'];
const VISUAL_BENTUK_RUANG_VALID = ['kubus', 'balok', 'tabung', 'kerucut', 'bola'];

/**
 * Validasi & bersihkan spesifikasi visual dari AI. Mengembalikan [tipe, dataJson] atau [null, null]
 * kalau tidak valid/tidak ada — supaya visual yang rusak tidak pernah tersimpan ke database.
 */
function sanitizeVisual(?array $visual): array
{
    if (!$visual || empty($visual['tipe'])) {
        return [null, null];
    }
    $tipe = $visual['tipe'];
    $d = $visual['data'] ?? [];

    if ($tipe === 'bangun_datar' || $tipe === 'bangun_ruang') {
        $bentuk = $d['bentuk'] ?? '';
        $daftarValid = $tipe === 'bangun_datar' ? VISUAL_BENTUK_DATAR_VALID : VISUAL_BENTUK_RUANG_VALID;
        if (!in_array($bentuk, $daftarValid, true)) return [null, null];
        $warna = preg_match('/^#[0-9a-fA-F]{6}$/', $d['warna'] ?? '') ? $d['warna'] : '#4f46e5';
        return [$tipe, json_encode(['bentuk' => $bentuk, 'warna' => $warna])];
    }

    if ($tipe === 'hitung_benda') {
        $emoji = trim((string)($d['emoji'] ?? ''));
        $jumlah = (int)($d['jumlah'] ?? 0);
        if ($emoji === '' || $jumlah < 1 || $jumlah > 20) return [null, null];
        return [$tipe, json_encode(['emoji' => $emoji, 'jumlah' => $jumlah])];
    }

    if ($tipe === 'bandingkan_benda') {
        $kiri = $d['kiri'] ?? [];
        $kanan = $d['kanan'] ?? [];
        $ke = trim((string)($kiri['emoji'] ?? ''));
        $kj = (int)($kiri['jumlah'] ?? 0);
        $ne = trim((string)($kanan['emoji'] ?? ''));
        $nj = (int)($kanan['jumlah'] ?? 0);
        if ($ke === '' || $ne === '' || $kj < 1 || $kj > 15 || $nj < 1 || $nj > 15) return [null, null];
        return [$tipe, json_encode([
            'kiri' => ['emoji' => $ke, 'jumlah' => $kj, 'label' => (string)($kiri['label'] ?? '')],
            'kanan' => ['emoji' => $ne, 'jumlah' => $nj, 'label' => (string)($kanan['label'] ?? '')],
        ])];
    }

    return [null, null];
}

/**
 * Susun array visual (bentuk yang dipahami sanitizeVisual()) dari field form manual tambah soal
 * (dikirim guru lewat api/soal_add.php). Mengembalikan null kalau guru tidak memilih visual apa pun.
 */
function buildVisualInputFromForm(array $data): ?array
{
    $tipe = $data['visual_tipe'] ?? '';
    if ($tipe === 'bangun_datar' || $tipe === 'bangun_ruang') {
        return ['tipe' => $tipe, 'data' => [
            'bentuk' => $data['visual_bentuk'] ?? '',
            'warna' => $data['visual_warna'] ?? '',
        ]];
    }
    if ($tipe === 'hitung_benda') {
        return ['tipe' => $tipe, 'data' => [
            'emoji' => $data['visual_emoji'] ?? '',
            'jumlah' => $data['visual_jumlah'] ?? 0,
        ]];
    }
    if ($tipe === 'bandingkan_benda') {
        return ['tipe' => $tipe, 'data' => [
            'kiri' => [
                'emoji' => $data['visual_kiri_emoji'] ?? '',
                'jumlah' => $data['visual_kiri_jumlah'] ?? 0,
                'label' => $data['visual_kiri_label'] ?? '',
            ],
            'kanan' => [
                'emoji' => $data['visual_kanan_emoji'] ?? '',
                'jumlah' => $data['visual_kanan_jumlah'] ?? 0,
                'label' => $data['visual_kanan_label'] ?? '',
            ],
        ]];
    }
    return null;
}

/**
 * Generate soal pilihan ganda untuk satu baris kisi_kisi lewat AI, lalu simpan ke tabel soal.
 * Mengembalikan jumlah soal yang berhasil ditambahkan.
 *
 * $sertakanVisual: kalau true, AI boleh menyertakan visual sederhana (bangun datar / hitung benda /
 * bandingkan benda) yang dirender sistem sendiri (SVG/emoji) — bukan gambar asli — untuk soal yang
 * memang cocok divisualisasikan. Soal lain tetap teks biasa.
 */
function generateSoalForKisi(PDO $db, array $kisi, int $jumlah = 3, bool $sertakanVisual = false): int
{
    $labelJenjangMap = ['SD1' => 'kelas 1 SD (usia 6-7 tahun)', 'SD6' => 'kelas 6 SD', 'SMP9' => 'kelas 9 SMP/MTs'];
    $labelJenjang = $labelJenjangMap[$kisi['jenjang']] ?? $kisi['jenjang'];

    $existingStmt = $db->prepare("SELECT pertanyaan FROM soal WHERE kisi_id = ?");
    $existingStmt->execute([$kisi['id']]);
    $soalLama = array_column($existingStmt->fetchAll(), 'pertanyaan');

    $system = 'Kamu adalah pembuat soal ujian untuk siswa sekolah di Indonesia. Sistem ini menampilkan teks biasa '
        . 'dan TIDAK BISA menampilkan foto/gambar asli/ilustrasi bebas, jadi JANGAN PERNAH membuat soal yang '
        . 'merujuk ke foto/gambar asli (mis. "lihat foto berikut") - soal harus bisa dijawab hanya dari teks '
        . 'pertanyaan itu sendiri' . ($sertakanVisual ? ', KECUALI kamu memakai field "visual" sesuai skema yang diberikan (itu dirender otomatis oleh sistem, bukan gambar asli).' : '.')
        . ' Selalu balas HANYA dengan JSON object valid, tanpa penjelasan tambahan.';

    $visualInstruksi = '';
    if ($sertakanVisual) {
        $visualInstruksi = "\nUntuk soal yang cocok (mis. mengenal bentuk bangun datar, atau menghitung/membandingkan "
            . "jumlah benda), tambahkan field opsional \"visual\" pada soal tsb dengan salah satu skema berikut "
            . "(kosongkan/hilangkan field ini kalau soal tidak butuh visual):\n"
            . "ATURAN PALING PENTING: visual HARUS relevan langsung dengan indikator/materi yang diberikan. JANGAN "
            . "memaksakan visual (apalagi bentuk/hitung benda yang tidak nyambung) hanya supaya ada variasi — kalau "
            . "materinya tidak benar-benar cocok divisualisasikan dengan salah satu dari 4 skema di bawah, JANGAN "
            . "sertakan field \"visual\" sama sekali, biarkan soal jadi teks biasa. Perkiraan wajar: hanya sebagian "
            . "kecil dari {$jumlah} soal (bisa juga 0) yang benar-benar butuh visual.\n"
            . "JANGAN PERNAH mendeskripsikan visual di dalam teks pertanyaan itu sendiri (mis. JANGAN menulis kalimat "
            . "seperti \"(gambar lingkaran merah)\" atau \"(bentuknya silinder)\" di teks soal) — itu membocorkan "
            . "jawaban. Visual sudah otomatis ditampilkan terpisah oleh sistem, teks soal cukup bertanya normal "
            . "seperti \"Bangun datar apakah ini?\" tanpa embel-embel keterangan tambahan.\n"
            . '1) Bangun datar (2D): {"tipe":"bangun_datar","data":{"bentuk":"lingkaran|persegi|persegi_panjang|segitiga|segilima|segienam","warna":"#RRGGBB"}}' . "\n"
            . '2) Bangun ruang (3D): {"tipe":"bangun_ruang","data":{"bentuk":"kubus|balok|tabung|kerucut|bola","warna":"#RRGGBB"}}' . "\n"
            . '3) Menghitung benda: {"tipe":"hitung_benda","data":{"emoji":"🍎","jumlah":5}}' . "\n"
            . '4) Membandingkan 2 kelompok benda: {"tipe":"bandingkan_benda","data":{"kiri":{"emoji":"🍎","jumlah":3,"label":"Apel"},"kanan":{"emoji":"🍊","jumlah":5,"label":"Jeruk"}}}' . "\n"
            . "Catatan bangun_ruang: bentuk ini HANYA untuk bangun ruang sederhana tanpa ukuran spesifik (mis. \"apa "
            . "nama bangun ruang ini?\"). Kalau soal butuh menghitung volume/luas permukaan dengan angka ukuran "
            . "tertentu (panjang rusuk, jari-jari, dsb), JANGAN pakai visual karena ukurannya tidak bisa ditampilkan "
            . "di gambar — cukup sebutkan ukurannya di teks soal seperti biasa.\n"
            . "Kalau pakai visual \"bangun_datar\"/\"bangun_ruang\" untuk pertanyaan \"bangun apakah ini?\", pastikan "
            . "kunci jawaban benar-benar sesuai bentuk yang dirender, dan ke-4 opsi jawaban HARUS berupa 4 nama bentuk "
            . "yang berbeda (tidak boleh ada nama bentuk yang sama muncul di lebih dari satu opsi).\n"
            . "PENTING untuk \"hitung_benda\": ini HANYA untuk soal yang menampilkan SATU kelompok benda sejenis secara "
            . "LENGKAP, dan field \"jumlah\" harus SAMA PERSIS dengan jawaban yang benar (siswa menghitung langsung dari "
            . "gambar). JANGAN pakai \"hitung_benda\" kalau soal melibatkan DUA kelompok benda berbeda yang harus "
            . "dijumlahkan/dikurangkan/dibandingkan (mis. \"7 apel dan 6 jeruk, berapa total buahnya?\" atau \"3 apel + "
            . "2 apel = ?\") — dalam kasus begini JANGAN sertakan visual sama sekali (biarkan soal berupa teks biasa), "
            . "walaupun ada visual \"bandingkan_benda\", visual itu HANYA untuk soal yang benar-benar MEMBANDINGKAN "
            . "\"mana yang lebih banyak/sedikit\" (bukan untuk soal yang meminta MENJUMLAHKAN kedua kelompok, karena "
            . "hasil penjumlahannya akan mudah dihitung langsung dari gambar sehingga soal jadi terlalu mudah/bocor).\n"
            . "Variasikan bentuk dan warna antar-soal — JANGAN membuat 2 soal berbeda dengan visual yang persis sama "
            . "(bentuk & warna sama) dalam satu batch ini.\n";
    }

    $user = "Buatkan {$jumlah} soal pilihan ganda (4 opsi) untuk siswa {$labelJenjang} di Indonesia.\n"
        . "Materi: {$kisi['materi']}\nIndikator: {$kisi['indikator']}\n"
        . "Level kognitif: {$kisi['level_kognitif']}\nTingkat kesukaran: {$kisi['kesulitan']}\n"
        . (count($soalLama) ? "Jangan mengulang soal berikut:\n- " . implode("\n- ", $soalLama) . "\n" : "")
        . $visualInstruksi
        . 'Balas dengan JSON object berbentuk: {"soal":[{"pertanyaan":"...","a":"...","b":"...","c":"...","d":"...","kunci":"a","pembahasan":"..."'
        . ($sertakanVisual ? ',"visual":{"tipe":"...","data":{...}}' : '') . '}]}';

    $data = callOpenAiJson($system, $user, $sertakanVisual ? 5000 : 4000);
    $daftarSoal = $data['soal'] ?? [];

    $stmt = $db->prepare(
        "INSERT INTO soal (kisi_id, jenjang, mapel, materi, kesulitan, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, kunci, pembahasan, visual_tipe, visual_data, sumber)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ai')"
    );

    $ditambahkan = 0;
    $visualSudahDipakai = [];
    foreach ($daftarSoal as $item) {
        if (empty($item['pertanyaan']) || empty($item['kunci']))
            continue;
        $kunci = strtolower($item['kunci']);
        if (!in_array($kunci, ['a', 'b', 'c', 'd'], true))
            continue;
        $opsiTeks = [$item['a'] ?? '', $item['b'] ?? '', $item['c'] ?? '', $item['d'] ?? ''];
        $opsiNormal = array_map(fn($v) => mb_strtolower(trim((string)$v)), $opsiTeks);
        if (count(array_unique($opsiNormal)) < 4)
            continue; // ada opsi jawaban yang duplikat, buang soal ini

        // Acak posisi opsi jawaban di server — AI cenderung menaruh jawaban benar di A,
        // jadi jangan percaya urutan dari AI supaya siswa tidak bisa menebak dari pola posisi.
        $indeksAsli = [0, 1, 2, 3];
        shuffle($indeksAsli);
        $opsiTerurut = array_map(fn($i) => $opsiTeks[$i], $indeksAsli);
        $indeksBenarAsli = ['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3][$kunci];
        $kunci = ['a', 'b', 'c', 'd'][array_search($indeksBenarAsli, $indeksAsli, true)];

        [$visualTipe, $visualData] = $sertakanVisual ? sanitizeVisual($item['visual'] ?? null) : [null, null];
        if ($visualTipe !== null) {
            $sig = $visualTipe . '|' . $visualData;
            if (isset($visualSudahDipakai[$sig])) {
                [$visualTipe, $visualData] = [null, null]; // visual sama sudah dipakai soal lain di batch ini
            } else {
                $visualSudahDipakai[$sig] = true;
            }
        }
        $stmt->execute([
            $kisi['id'],
            $kisi['jenjang'],
            $kisi['mapel'],
            $kisi['materi'],
            $kisi['kesulitan'],
            $item['pertanyaan'],
            $opsiTerurut[0],
            $opsiTerurut[1],
            $opsiTerurut[2],
            $opsiTerurut[3],
            $kunci,
            $item['pembahasan'] ?? '',
            $visualTipe,
            $visualData,
        ]);
        $ditambahkan++;
    }

    return $ditambahkan;
}

/**
 * Bersihkan & batasi struktur modul pembelajaran dari AI supaya selalu aman dirender
 * (batasi panjang array, buang field yang bukan string/array sesuai skema).
 */
function sanitizeModul($data): ?array
{
    if (!is_array($data)) return null;

    $strArr = function ($v, int $max) {
        if (!is_array($v)) return [];
        $out = [];
        foreach ($v as $item) {
            if (is_string($item) && trim($item) !== '') $out[] = trim($item);
            if (count($out) >= $max) break;
        }
        return $out;
    };
    $sectionArr = function ($v, int $max) {
        if (!is_array($v)) return [];
        $out = [];
        foreach ($v as $item) {
            if (!is_array($item)) continue;
            $judul = trim((string)($item['judul'] ?? ''));
            $isi = trim((string)($item['isi'] ?? ''));
            if ($judul === '' || $isi === '') continue;
            $out[] = ['judul' => $judul, 'isi' => $isi];
            if (count($out) >= $max) break;
        }
        return $out;
    };
    $istilahArr = function ($v, int $max) {
        if (!is_array($v)) return [];
        $out = [];
        foreach ($v as $item) {
            if (!is_array($item)) continue;
            $istilah = trim((string)($item['istilah'] ?? ''));
            $arti = trim((string)($item['arti'] ?? ''));
            if ($istilah === '' || $arti === '') continue;
            $out[] = ['istilah' => $istilah, 'arti' => $arti];
            if (count($out) >= $max) break;
        }
        return $out;
    };

    $modul = [
        'tujuan_pembelajaran' => $strArr($data['tujuan_pembelajaran'] ?? null, 6),
        'penjelasan' => $sectionArr($data['penjelasan'] ?? null, 6),
        'contoh' => $sectionArr($data['contoh'] ?? null, 5),
        'istilah_kunci' => $istilahArr($data['istilah_kunci'] ?? null, 8),
        'poin_penting' => $strArr($data['poin_penting'] ?? null, 6),
    ];

    if (!$modul['tujuan_pembelajaran'] || !$modul['penjelasan']) return null; // minimal harus ada ini

    return $modul;
}

/**
 * Generate modul pembelajaran (bukan soal ujian) untuk satu baris kisi_kisi lewat AI, lalu
 * simpan ke kolom kisi_kisi.modul_konten. Modul ini yang dibaca siswa sebelum/di luar latihan
 * soal, supaya siswa juga dapat pendalaman materi, bukan cuma dites.
 */
function generateModulForKisi(PDO $db, array $kisi, string $instruksiTambahan = ''): bool
{
    $labelJenjangMap = ['SD1' => 'kelas 1 SD (usia 6-7 tahun)', 'SD6' => 'kelas 6 SD', 'SMP9' => 'kelas 9 SMP/MTs'];
    $labelJenjang = $labelJenjangMap[$kisi['jenjang']] ?? $kisi['jenjang'];

    $system = 'Kamu adalah guru berpengalaman yang menulis modul belajar (bahan bacaan/penjelasan materi) untuk '
        . 'siswa di Indonesia — BUKAN membuat soal ujian. Tulisan harus jelas, terstruktur, sesuai usia/jenjang '
        . 'siswa, dan berdasarkan indikator/tujuan pembelajaran yang diberikan (bukan materi generik di luar itu). '
        . 'Sistem ini hanya menampilkan teks (tidak ada gambar), jadi jangan merujuk ke gambar/ilustrasi yang tidak '
        . 'ada. Selalu balas HANYA dengan JSON object valid, tanpa penjelasan tambahan.';

    $user = "Buatkan modul pembelajaran (bahan bacaan penjelasan materi, bukan soal) untuk siswa {$labelJenjang} di Indonesia.\n"
        . "Mata Pelajaran: {$kisi['mapel']}\nMateri: {$kisi['materi']}\nIndikator/tujuan: {$kisi['indikator']}\n"
        . "Level kognitif: {$kisi['level_kognitif']}\n\n"
        . "Struktur yang diminta (JSON):\n"
        . '{"tujuan_pembelajaran":["poin tujuan belajar 1","poin 2", ...(3-5 poin, turunan dari indikator di atas, ditulis dari sudut pandang \"Setelah belajar ini, kamu dapat ...\")],' . "\n"
        . '"penjelasan":[{"judul":"judul sub-bagian","isi":"penjelasan lengkap sub-bagian ini, 2-4 kalimat atau lebih kalau perlu"}, ...(2-4 sub-bagian yang menjelaskan materi secara berurutan dan mudah dipahami)],' . "\n"
        . '"contoh":[{"judul":"judul contoh (mis. \"Contoh 1\")","isi":"contoh soal/kasus konkret beserta cara penyelesaian/penjelasannya langkah demi langkah"}, ...(1-3 contoh, WAJIB diisi kalau materinya matematika/sains yang ada perhitungan atau prosedur; boleh kosong array [] kalau materi tidak butuh contoh soal, mis. materi hafalan/sikap)],' . "\n"
        . '"istilah_kunci":[{"istilah":"kata/istilah penting","arti":"penjelasan sederhana artinya"}, ...(isi kalau materi punya istilah/kosakata khusus yang perlu diketahui siswa, mis. bahasa/sains/agama; boleh kosong array [] kalau tidak relevan)],' . "\n"
        . '"poin_penting":["rangkuman poin penting 1","poin 2", ...(3-5 poin ringkasan paling penting untuk diingat siswa di akhir modul)]}' . "\n\n"
        . "Gunakan bahasa yang sesuai usia {$labelJenjang} (semakin muda jenjangnya, semakin sederhana kalimatnya), "
        . "dan pastikan isi modul benar-benar relevan dengan materi \"{$kisi['materi']}\" serta indikator yang diberikan."
        . ($instruksiTambahan !== '' ? "\n\nPERMINTAAN KHUSUS dari guru untuk modul ini (WAJIB dipenuhi, sertakan secara eksplisit "
            . "di bagian \"penjelasan\" dan/atau \"contoh\" yang relevan, jangan diabaikan): {$instruksiTambahan}" : '');

    $data = callOpenAiJson($system, $user, 3500);
    $modul = sanitizeModul($data);
    if (!$modul) {
        throw new AiError('AI tidak mengembalikan modul yang valid. Coba lagi.');
    }

    $db->prepare("UPDATE kisi_kisi SET modul_konten = ? WHERE id = ?")
        ->execute([json_encode($modul), $kisi['id']]);

    return true;
}
