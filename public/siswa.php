<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$u = currentUser();
if (!$u || $u['role'] !== 'siswa') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Halo <?= htmlspecialchars($u['nama']) ?> - SIGAP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="d-flex flex-column min-vh-100">

<header class="siswa-hero px-3 px-md-5 py-4 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="siswa-avatar">🧒</div>
            <div>
                <div class="fw-bold font-baloo fs-4">Halo, <?= htmlspecialchars($u['nama']) ?>!</div>
                <div class="opacity-90 small d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-light text-primary fw-bold">SIGAP</span>
                    <span><?= htmlspecialchars(labelJenjang($u['jenjang'])) ?></span>
                    <span class="opacity-75 d-none d-sm-inline">&bull; SIstem Generate &amp; Analisis Pembelajaran</span>
                </div>
            </div>
        </div>
        <a href="logout.php" class="btn btn-light btn-sm fw-semibold"><i class="bi bi-box-arrow-right me-1"></i>Keluar</a>
    </div>
</header>

<main class="px-3 px-md-5 pb-5 flex-grow-1" style="max-width: 980px; width: 100%; margin: 0 auto;">

    <ul class="nav kid-tabs mb-4 gap-2">
        <li class="nav-item"><a href="#" class="nav-link active" data-tab="latihan">📝 Latihan</a></li>
        <li class="nav-item"><a href="#" class="nav-link" data-tab="raport">📊 Raport Saya</a></li>
    </ul>

    <!-- ===== TAB LATIHAN ===== -->
    <section id="tab-latihan" class="section-panel active">

        <!-- pilih mata pelajaran -->
        <div id="view-subjects">
            <h5 class="font-baloo fw-bold mb-3">Pilih Mata Pelajaran</h5>
            <div class="row g-3" id="subject-cards"></div>
        </div>

        <!-- pilih materi dalam 1 mapel -->
        <div id="view-materi" class="hidden">
            <button type="button" class="btn btn-light btn-sm mb-3" id="btn-back-subjects"><i class="bi bi-arrow-left me-1"></i>Kembali</button>
            <h5 class="font-baloo fw-bold mb-3" id="materi-mapel-title"></h5>
            <button type="button" class="materi-chip mb-2" id="btn-mapel-semua" style="border-color:var(--brand);color:var(--brand);">
                🎯 Latihan semua topik di mapel ini
            </button>
            <div class="d-flex flex-wrap gap-2" id="materi-chip-list"></div>
        </div>

        <!-- modul pembelajaran (pendalaman materi sebelum latihan) -->
        <div id="view-modul" class="hidden">
            <button type="button" class="btn btn-light btn-sm mb-3" id="btn-back-materi"><i class="bi bi-arrow-left me-1"></i>Kembali</button>
            <div class="card quiz-card p-4 modul-card">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <div class="text-muted small" id="modul-mapel-label"></div>
                        <h5 class="font-baloo fw-bold mb-0" id="modul-materi-title"></h5>
                    </div>
                    <span class="badge" style="background:var(--kid-purple);">📖 Modul Belajar</span>
                </div>

                <div class="modul-section modul-tujuan">
                    <h6>🎯 Tujuan Pembelajaran</h6>
                    <ul id="modul-tujuan"></ul>
                </div>

                <div id="modul-penjelasan"></div>

                <div id="modul-contoh-wrap" class="modul-section hidden">
                    <h6>💡 Contoh</h6>
                    <div id="modul-contoh"></div>
                </div>

                <div id="modul-istilah-wrap" class="modul-section hidden">
                    <h6>🔤 Istilah Kunci</h6>
                    <div id="modul-istilah" class="row g-2"></div>
                </div>

                <div class="modul-section modul-poin-penting">
                    <h6>⭐ Poin Penting</h6>
                    <ul id="modul-poin-penting"></ul>
                </div>
            </div>
            <div class="d-flex justify-content-center mt-3">
                <button type="button" class="btn btn-primary btn-lg" id="btn-modul-ke-latihan">Mulai Latihan Soal &raquo;</button>
            </div>
        </div>

        <!-- sesi latihan berjalan -->
        <div id="latihan-area" class="hidden">
            <div class="quiz-progress-wrap mb-3">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span id="latihan-progress"></span>
                    <span id="latihan-mapel-label"></span>
                </div>
                <div class="progress"><div class="progress-bar bg-primary" id="progress-bar" style="width:0%"></div></div>
            </div>
            <div class="card quiz-card p-4" id="latihan-soal"></div>
            <div class="d-flex justify-content-between mt-3">
                <button id="btn-prev" type="button" class="btn btn-outline-secondary">&laquo; Sebelumnya</button>
                <button id="btn-next" type="button" class="btn btn-primary">Selanjutnya &raquo;</button>
                <button id="btn-selesai" type="button" class="btn btn-success hidden">Selesai &amp; Kumpulkan ✅</button>
            </div>
        </div>

        <!-- hasil -->
        <div id="latihan-hasil" class="hidden text-center">
            <div class="result-emoji mb-2" id="hasil-emoji">🎉</div>
            <div class="result-score" id="hasil-score-num"></div>
            <p class="text-muted" id="latihan-skor"></p>
            <div class="d-flex gap-2 justify-content-center mt-3">
                <button id="btn-latihan-lagi" type="button" class="btn btn-primary">Latihan Lagi</button>
                <button id="btn-lihat-raport" type="button" class="btn btn-outline-secondary">Lihat Raport</button>
            </div>
        </div>
    </section>

    <!-- ===== TAB RAPORT ===== -->
    <section id="tab-raport" class="section-panel">
        <h5 class="font-baloo fw-bold mb-3">Raport Saya</h5>
        <div class="row g-3 mb-4" id="raport-ringkasan"></div>
        <div class="soft-card p-3 p-md-4 mb-4">
            <h6 class="fw-bold mb-3">Perkembangan Skor</h6>
            <div class="chart-box"><canvas id="chart-raport"></canvas></div>
        </div>
        <div class="soft-card p-0">
            <div class="table-responsive">
                <table class="table tbl-modern mb-0" id="table-raport">
                    <thead><tr><th>Waktu</th><th>Mata Pelajaran</th><th>Materi</th><th>Jumlah Soal</th><th>Benar</th><th>Skor</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

</main>

<footer class="py-3 px-4 text-center text-muted small border-top bg-white no-print mt-auto">
    Develop by PAPA B03d1
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="assets/app.js"></script>
<script>
initSiswaDashboard();
</script>
</body>
</html>
