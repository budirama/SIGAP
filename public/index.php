<?php

require_once __DIR__ . '/../includes/auth.php';

$u = currentUser();
if (!$u || $u['role'] !== 'guru') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Guru - SIGAP - SIstem Generate &amp; Analisis Pembelajaran</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app-shell d-flex">

    <!-- Sidebar (desktop) -->
    <aside class="sidebar d-none d-md-flex flex-column">
        <div class="brand d-flex align-items-center gap-2">
            <div class="brand-mark">🧠</div>
            <div>
                <div class="name">SIGAP</div>
                <div class="sub">SIstem Generate &amp; Analisis Pembelajaran</div>
            </div>
        </div>
        <nav class="nav flex-column mt-2" id="sidebar-nav">
            <a href="#" class="nav-link active" data-tab="ringkasan"><i class="bi bi-grid-1x2-fill"></i>Ringkasan</a>
            <a href="#" class="nav-link" data-tab="kisikisi"><i class="bi bi-clipboard-data-fill"></i>Kisi-kisi</a>
            <a href="#" class="nav-link" data-tab="banksoal"><i class="bi bi-journal-richtext"></i>Bank Soal</a>
            <a href="#" class="nav-link" data-tab="analisis"><i class="bi bi-bar-chart-fill"></i>Analisis</a>
            <a href="#" class="nav-link" data-tab="raport"><i class="bi bi-file-earmark-text-fill"></i>Raport</a>
        </nav>
        <div class="mt-auto p-3">
            <a href="logout.php" class="nav-link"><i class="bi bi-box-arrow-right"></i>Keluar</a>
        </div>
    </aside>

    <!-- Sidebar (mobile offcanvas) -->
    <div class="offcanvas offcanvas-start sidebar d-md-none" tabindex="-1" id="mobileSidebar">
        <div class="brand d-flex align-items-center gap-2">
            <div class="brand-mark">🧠</div>
            <div><div class="name">SIGAP</div><div class="sub">SIstem Generate &amp; Analisis Pembelajaran</div></div>
        </div>
        <nav class="nav flex-column mt-2" id="sidebar-nav-mobile">
            <a href="#" class="nav-link active" data-tab="ringkasan"><i class="bi bi-grid-1x2-fill"></i>Ringkasan</a>
            <a href="#" class="nav-link" data-tab="kisikisi"><i class="bi bi-clipboard-data-fill"></i>Kisi-kisi</a>
            <a href="#" class="nav-link" data-tab="banksoal"><i class="bi bi-journal-richtext"></i>Bank Soal</a>
            <a href="#" class="nav-link" data-tab="analisis"><i class="bi bi-bar-chart-fill"></i>Analisis</a>
            <a href="#" class="nav-link" data-tab="raport"><i class="bi bi-file-earmark-text-fill"></i>Raport</a>
            <a href="logout.php" class="nav-link mt-3"><i class="bi bi-box-arrow-right"></i>Keluar</a>
        </nav>
    </div>

    <div class="main-content d-flex flex-column min-vh-100">
        <header class="topbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-light d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <h5 class="mb-0 fw-bold" id="page-title">Ringkasan</h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="d-none d-sm-inline text-muted small">Halo, <?= htmlspecialchars($u['nama']) ?></span>
                <span class="brand-mark" style="width:36px;height:36px;font-size:16px;">👤</span>
            </div>
        </header>

        <main class="p-3 p-md-4 flex-grow-1">

            <!-- ===== RINGKASAN ===== -->
            <section id="tab-ringkasan" class="section-panel active">
                <div class="row g-3 mb-4" id="kpi-ringkasan"></div>
                <div class="row g-3">
                    <div class="col-lg-7">
                        <div class="soft-card p-3 p-md-4 h-100">
                            <h6 class="fw-bold mb-3">Perbandingan Rata-rata Skor</h6>
                            <div class="chart-box"><canvas id="chart-perbandingan"></canvas></div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="soft-card p-3 p-md-4 h-100">
                            <h6 class="fw-bold mb-3">Materi per Mata Pelajaran</h6>
                            <div class="chart-box"><canvas id="chart-mapel"></canvas></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===== KISI-KISI ===== -->
            <section id="tab-kisikisi" class="section-panel">
                <div class="soft-card ai-generate-card p-3 p-md-4 mb-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="fs-4">✨</span>
                        <h6 class="fw-bold mb-0">Buat Otomatis via AI</h6>
                    </div>
                    <p class="text-muted small mb-3">Guru cukup masukkan mata pelajaran — materi, indikator, dan soalnya dibuatkan otomatis oleh AI, siswa bisa langsung mengerjakan.</p>
                    <form id="form-ai-generate" class="row g-2 align-items-end">
                        <div class="col-6 col-md-3">
                            <label class="form-label small mb-1">Jenjang</label>
                            <select name="jenjang" class="form-select form-select-sm" required>
                                <option value="">Pilih</option>
                                <option value="SD1">SD Kelas 1</option>
                                <option value="SD6">SD Kelas 6</option>
                                <option value="SMP9">SMP/MTs Kelas 9</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small mb-1">Mata Pelajaran</label>
                            <input type="text" name="mapel" class="form-control form-control-sm" placeholder="mis. Matematika" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-1">Jml materi</label>
                            <input type="number" name="jumlah_materi" class="form-control form-control-sm" min="1" max="10" value="5" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-1">Soal/materi</label>
                            <input type="number" name="jumlah_soal" class="form-control form-control-sm" min="1" max="10" value="3" required>
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="submit" id="btn-ai-generate" class="btn btn-primary btn-sm w-100">Generate</button>
                        </div>
                        <div class="col-12">
                            <label class="form-check-label small text-muted d-block">
                                <input type="checkbox" name="buatkan_soal" class="form-check-input" checked>
                                Sekalian buatkan soalnya juga (siswa bisa langsung latihan)
                            </label>
                            <label class="form-check-label small text-muted d-block">
                                <input type="checkbox" name="buatkan_modul" class="form-check-input" checked>
                                Sekalian buatkan modul pembelajarannya juga (siswa bisa belajar dulu sebelum latihan)
                            </label>
                        </div>
                        <div class="col-12"><p id="ai-generate-status" class="small text-primary mb-0"></p></div>
                    </form>
                </div>

                <div class="accordion mb-3" id="accordion-manual">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-manual">
                                <i class="bi bi-pencil-square me-2"></i> Tambah Kisi-kisi Manual
                            </button>
                        </h2>
                        <div id="collapse-manual" class="accordion-collapse collapse" data-bs-parent="#accordion-manual">
                            <div class="accordion-body">
                                <form id="form-kisikisi" class="row g-2">
                                    <div class="col-6 col-md-2">
                                        <select name="jenjang" class="form-select form-select-sm" required>
                                            <option value="">Jenjang</option>
                                            <option value="SD1">SD Kelas 1</option>
                                            <option value="SD6">SD Kelas 6</option>
                                            <option value="SMP9">SMP/MTs Kelas 9</option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <input type="text" name="mapel" class="form-control form-control-sm" placeholder="Mata Pelajaran" required>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <input type="text" name="materi" class="form-control form-control-sm" placeholder="Materi" required>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <input type="text" name="indikator" class="form-control form-control-sm" placeholder="Indikator" required>
                                    </div>
                                    <div class="col-6 col-md-1">
                                        <select name="level" class="form-select form-select-sm" required>
                                            <option value="">Lvl</option>
                                            <option value="C1">C1</option>
                                            <option value="C2">C2</option>
                                            <option value="C3">C3</option>
                                            <option value="C4">C4</option>
                                            <option value="C5">C5</option>
                                            <option value="C6">C6</option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-1">
                                        <select name="kesulitan" class="form-select form-select-sm" required>
                                            <option value="Mudah">Mudah</option>
                                            <option value="Sedang" selected>Sedang</option>
                                            <option value="Sukar">Sukar</option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-1">
                                        <input type="number" name="jumlah" class="form-control form-control-sm" min="1" value="3" required>
                                    </div>
                                    <div class="col-6 col-md-1">
                                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">Tambah</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Filter jenjang</span>
                    <select id="filter-jenjang-kisikisi" class="form-select form-select-sm w-auto">
                        <option value="">Semua</option>
                        <option value="SD1">SD Kelas 1</option>
                        <option value="SD6">SD Kelas 6</option>
                        <option value="SMP9">SMP/MTs Kelas 9</option>
                    </select>
                </div>
                <div class="soft-card p-0">
                    <div class="table-responsive">
                        <table class="table tbl-modern mb-0" id="table-kisikisi">
                            <thead>
                                <tr><th>Jenjang</th><th>Mata Pelajaran</th><th>Materi</th><th>Indikator</th><th>Level</th><th>Kesulitan</th><th>Jml/sesi</th><th></th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ===== BANK SOAL ===== -->
            <section id="tab-banksoal" class="section-panel">
                <div class="accordion mb-3" id="accordion-soal">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-soal">
                                <i class="bi bi-plus-circle me-2"></i> Tambah Soal Manual
                            </button>
                        </h2>
                        <div id="collapse-soal" class="accordion-collapse collapse show" data-bs-parent="#accordion-soal">
                            <div class="accordion-body">
                                <form id="form-soal" class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Kisi-kisi</label>
                                        <select name="kisi_id" id="soal-kisi-id" class="form-select form-select-sm" required></select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Pertanyaan</label>
                                        <textarea name="pertanyaan" class="form-control form-control-sm" required></textarea>
                                    </div>
                                    <div class="col-6">
                                        <input type="text" name="a" class="form-control form-control-sm" placeholder="Opsi A" required>
                                    </div>
                                    <div class="col-6">
                                        <input type="text" name="b" class="form-control form-control-sm" placeholder="Opsi B" required>
                                    </div>
                                    <div class="col-6">
                                        <input type="text" name="c" class="form-control form-control-sm" placeholder="Opsi C" required>
                                    </div>
                                    <div class="col-6">
                                        <input type="text" name="d" class="form-control form-control-sm" placeholder="Opsi D" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Visual (opsional — dirender sistem, bukan upload gambar)</label>
                                        <select name="visual_tipe" id="soal-visual-tipe" class="form-select form-select-sm">
                                            <option value="">Tidak ada</option>
                                            <option value="bangun_datar">Bangun Datar (2D)</option>
                                            <option value="bangun_ruang">Bangun Ruang (3D)</option>
                                            <option value="hitung_benda">Hitung Benda</option>
                                            <option value="bandingkan_benda">Bandingkan 2 Kelompok Benda</option>
                                        </select>
                                    </div>
                                    <div id="visual-fields-bangun_datar" class="col-12 row g-2 hidden">
                                        <div class="col-6">
                                            <select name="visual_bentuk" class="form-select form-select-sm">
                                                <option value="lingkaran">Lingkaran</option>
                                                <option value="persegi">Persegi</option>
                                                <option value="persegi_panjang">Persegi Panjang</option>
                                                <option value="segitiga">Segitiga</option>
                                                <option value="segilima">Segilima</option>
                                                <option value="segienam">Segienam</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <input type="color" name="visual_warna" class="form-control form-control-sm form-control-color" value="#4f46e5">
                                        </div>
                                    </div>
                                    <div id="visual-fields-bangun_ruang" class="col-12 row g-2 hidden">
                                        <div class="col-6">
                                            <select name="visual_bentuk" class="form-select form-select-sm">
                                                <option value="kubus">Kubus</option>
                                                <option value="balok">Balok</option>
                                                <option value="tabung">Tabung</option>
                                                <option value="kerucut">Kerucut</option>
                                                <option value="bola">Bola</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <input type="color" name="visual_warna" class="form-control form-control-sm form-control-color" value="#4f46e5">
                                        </div>
                                    </div>
                                    <div id="visual-fields-hitung_benda" class="col-12 row g-2 hidden">
                                        <div class="col-6">
                                            <input type="text" name="visual_emoji" class="form-control form-control-sm" placeholder="Emoji (mis. 🍎)" maxlength="4">
                                        </div>
                                        <div class="col-6">
                                            <input type="number" name="visual_jumlah" class="form-control form-control-sm" placeholder="Jumlah" min="1" max="20">
                                        </div>
                                    </div>
                                    <div id="visual-fields-bandingkan_benda" class="col-12 row g-2 hidden">
                                        <div class="col-4">
                                            <input type="text" name="visual_kiri_emoji" class="form-control form-control-sm" placeholder="Emoji kiri" maxlength="4">
                                        </div>
                                        <div class="col-4">
                                            <input type="number" name="visual_kiri_jumlah" class="form-control form-control-sm" placeholder="Jml kiri" min="1" max="15">
                                        </div>
                                        <div class="col-4">
                                            <input type="text" name="visual_kiri_label" class="form-control form-control-sm" placeholder="Label kiri">
                                        </div>
                                        <div class="col-4">
                                            <input type="text" name="visual_kanan_emoji" class="form-control form-control-sm" placeholder="Emoji kanan" maxlength="4">
                                        </div>
                                        <div class="col-4">
                                            <input type="number" name="visual_kanan_jumlah" class="form-control form-control-sm" placeholder="Jml kanan" min="1" max="15">
                                        </div>
                                        <div class="col-4">
                                            <input type="text" name="visual_kanan_label" class="form-control form-control-sm" placeholder="Label kanan">
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small mb-1">Kunci jawaban</label>
                                        <select name="kunci" class="form-select form-select-sm" required>
                                            <option value="a">A</option>
                                            <option value="b">B</option>
                                            <option value="c">C</option>
                                            <option value="d">D</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-9">
                                        <label class="form-label small mb-1">Pembahasan (opsional)</label>
                                        <textarea name="pembahasan" class="form-control form-control-sm"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-sm">Tambah Soal</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Filter jenjang</span>
                    <select id="filter-jenjang-soal" class="form-select form-select-sm w-auto">
                        <option value="">Semua</option>
                        <option value="SD1">SD Kelas 1</option>
                        <option value="SD6">SD Kelas 6</option>
                        <option value="SMP9">SMP/MTs Kelas 9</option>
                    </select>
                </div>
                <div class="soft-card p-0">
                    <div class="table-responsive">
                        <table class="table tbl-modern mb-0" id="table-soal">
                            <thead>
                                <tr><th>Jenjang</th><th>Mata Pelajaran</th><th>Materi</th><th>Pertanyaan</th><th>Kunci</th><th>Sumber</th><th></th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ===== ANALISIS ===== -->
            <section id="tab-analisis" class="section-panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small">Filter jenjang</span>
                    <select id="filter-jenjang-analisis" class="form-select form-select-sm w-auto">
                        <option value="">Semua Jenjang</option>
                        <option value="SD1">SD Kelas 1</option>
                        <option value="SD6">SD Kelas 6</option>
                        <option value="SMP9">SMP/MTs Kelas 9</option>
                    </select>
                </div>

                <div class="row g-3 mb-3" id="analisis-ringkasan"></div>

                <div class="row g-3 mb-4">
                    <div class="col-lg-7">
                        <div class="soft-card p-3 p-md-4 h-100">
                            <h6 class="fw-bold mb-3">Tren Skor per Sesi</h6>
                            <div class="chart-box"><canvas id="chart-tren"></canvas></div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="soft-card p-3 p-md-4 h-100">
                            <h6 class="fw-bold mb-3">Distribusi Kategori (Kurva Sigma)</h6>
                            <div class="chart-box"><canvas id="chart-kategori"></canvas></div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-2">Riwayat Sesi</h6>
                <div class="soft-card p-0 mb-4">
                    <div class="table-responsive">
                        <table class="table tbl-modern mb-0" id="table-siswa">
                            <thead><tr><th>Nama</th><th>Jenjang</th><th>Materi</th><th>Skor</th><th>Kategori</th><th>Waktu</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <h6 class="fw-bold mb-2">Analisis Butir Soal</h6>
                <div class="soft-card p-0">
                    <div class="table-responsive">
                        <table class="table tbl-modern mb-0" id="table-butir">
                            <thead><tr><th>Pertanyaan</th><th>Jenjang</th><th>Mata Pelajaran</th><th>Materi</th><th>Tingkat kesukaran (P)</th><th>Daya beda (D)</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ===== RAPORT PER ANAK ===== -->
            <section id="tab-raport" class="section-panel">
                <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                    <div class="btn-group" id="raport-siswa-picker" role="group"></div>
                    <button type="button" id="btn-cetak-raport" class="btn btn-outline-primary btn-sm hidden">
                        <i class="bi bi-printer me-1"></i>Cetak / Simpan PDF
                    </button>
                </div>

                <p id="raport-placeholder" class="text-muted">Pilih nama anak di atas untuk melihat raportnya.</p>

                <div id="raport-print-area" class="hidden">
                    <div class="soft-card p-4 mb-3" id="raport-kop">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <h4 class="fw-bold mb-1">Raport Siswa — SIGAP</h4>
                                <div class="text-muted small">SIstem Generate &amp; Analisis Pembelajaran</div>
                                <div class="text-muted small" id="raport-tanggal-cetak"></div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold fs-5" id="raport-nama-siswa"></div>
                                <div class="text-muted" id="raport-jenjang-siswa"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3" id="raport-kpi"></div>

                    <div class="soft-card p-3 p-md-4 mb-3">
                        <h6 class="fw-bold mb-3">Perkembangan Skor</h6>
                        <div class="chart-box chart-box-raport"><canvas id="chart-raport-anak"></canvas></div>
                    </div>

                    <h6 class="fw-bold mb-2">Nilai per Mata Pelajaran</h6>
                    <div class="soft-card p-0 mb-4">
                        <div class="table-responsive">
                            <table class="table tbl-modern mb-0" id="table-raport-mapel">
                                <thead><tr><th>Mata Pelajaran</th><th>Soal Dikerjakan</th><th>Nilai</th><th>Predikat</th><th>Keterangan</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Analisis Perkembangan</h6>
                        <button type="button" id="btn-buat-narasi" class="btn btn-outline-primary btn-sm no-print">
                            <i class="bi bi-stars me-1"></i>Buat Analisis (AI)
                        </button>
                    </div>
                    <p id="narasi-placeholder" class="text-muted small">Klik "Buat Analisis (AI)" untuk membuat insight naratif berdasarkan data nilai anak ini (minimal 3 sesi latihan).</p>
                    <div id="narasi-status" class="small text-primary mb-2"></div>
                    <div id="narasi-hasil" class="row g-3 mb-4 hidden">
                        <div class="col-md-6">
                            <div class="soft-card p-3 h-100">
                                <h6 class="fw-bold mb-2"><i class="bi bi-graph-up-arrow text-primary me-1"></i>Ringkasan Umum</h6>
                                <p class="small mb-0" id="narasi-ringkasan-umum"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="soft-card p-3 h-100">
                                <h6 class="fw-bold mb-2"><i class="bi bi-trophy-fill text-success me-1"></i>Kekuatan</h6>
                                <p class="small mb-0" id="narasi-kekuatan"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="soft-card p-3 h-100">
                                <h6 class="fw-bold mb-2"><i class="bi bi-arrow-up-circle-fill text-warning me-1"></i>Perlu Diperkuat</h6>
                                <p class="small mb-0" id="narasi-perlu-diperkuat"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="soft-card p-3 h-100">
                                <h6 class="fw-bold mb-2"><i class="bi bi-lightbulb-fill text-info me-1"></i>Rekomendasi</h6>
                                <p class="small mb-0" id="narasi-rekomendasi"></p>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-2">Riwayat Sesi Latihan</h6>
                    <div class="soft-card p-0">
                        <div class="table-responsive">
                            <table class="table tbl-modern mb-0" id="table-raport-riwayat">
                                <thead><tr><th>Waktu</th><th>Mata Pelajaran</th><th>Materi</th><th>Jumlah Soal</th><th>Benar</th><th>Skor</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

        </main>

        <footer class="py-3 px-4 text-center text-muted small border-top bg-white no-print mt-auto">
            Develop by PAPA B03d1
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="assets/app.js"></script>
<script>
initGuruDashboard();
</script>
</body>
</html>
