// ===== Util =====

async function apiGet(url) {
    const res = await fetch(url);
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || 'Terjadi kesalahan.');
    return data;
}

async function apiPost(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || 'Terjadi kesalahan.');
    return data;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

// ===== Visual soal (bangun datar / hitung benda) — dirender sistem sendiri, bukan gambar asli =====

function regularPolygonPoints(sides, cx, cy, r) {
    const pts = [];
    const start = -Math.PI / 2; // mulai dari atas
    for (let i = 0; i < sides; i++) {
        const angle = start + (i * 2 * Math.PI) / sides;
        pts.push(`${(cx + r * Math.cos(angle)).toFixed(1)},${(cy + r * Math.sin(angle)).toFixed(1)}`);
    }
    return pts.join(' ');
}

function renderBangunDatarSvg(bentuk, warna) {
    const c = escapeHtml(warna || '#4f46e5');
    let shape = '';
    if (bentuk === 'lingkaran') shape = `<circle cx="60" cy="60" r="50" fill="${c}"/>`;
    else if (bentuk === 'persegi') shape = `<rect x="15" y="15" width="90" height="90" fill="${c}"/>`;
    else if (bentuk === 'persegi_panjang') shape = `<rect x="5" y="30" width="110" height="60" fill="${c}"/>`;
    else if (bentuk === 'segitiga') shape = `<polygon points="${regularPolygonPoints(3, 60, 65, 55)}" fill="${c}"/>`;
    else if (bentuk === 'segilima') shape = `<polygon points="${regularPolygonPoints(5, 60, 60, 52)}" fill="${c}"/>`;
    else if (bentuk === 'segienam') shape = `<polygon points="${regularPolygonPoints(6, 60, 60, 52)}" fill="${c}"/>`;
    else return '';
    return `<div class="soal-visual"><svg viewBox="0 0 120 120" width="140" height="140">${shape}</svg></div>`;
}

function shadeColor(hex, percent) {
    // percent negatif = lebih gelap, positif = lebih terang
    const n = parseInt((hex || '#4f46e5').replace('#', ''), 16);
    let r = (n >> 16) & 0xff, g = (n >> 8) & 0xff, b = n & 0xff;
    const adjust = (v) => Math.max(0, Math.min(255, Math.round(v + (percent < 0 ? v * percent : (255 - v) * percent))));
    r = adjust(r); g = adjust(g); b = adjust(b);
    return `rgb(${r},${g},${b})`;
}

function renderBangunRuangSvg(bentuk, warna) {
    const c = warna || '#4f46e5';
    const depan = c, atas = shadeColor(c, 0.35), samping = shadeColor(c, -0.25);
    let shape = '';
    if (bentuk === 'kubus') {
        shape = `
            <polygon points="30,50 70,50 90,30 50,30" fill="${atas}"/>
            <polygon points="70,50 90,30 90,70 70,90" fill="${samping}"/>
            <rect x="30" y="50" width="40" height="40" fill="${depan}"/>`;
    } else if (bentuk === 'balok') {
        shape = `
            <polygon points="15,45 85,45 100,28 30,28" fill="${atas}"/>
            <polygon points="85,45 100,28 100,60 85,95" fill="${samping}"/>
            <rect x="15" y="45" width="70" height="50" fill="${depan}"/>`;
    } else if (bentuk === 'tabung') {
        shape = `
            <ellipse cx="60" cy="90" rx="35" ry="13" fill="${samping}"/>
            <rect x="25" y="35" width="70" height="55" fill="${depan}"/>
            <ellipse cx="60" cy="35" rx="35" ry="13" fill="${atas}"/>`;
    } else if (bentuk === 'kerucut') {
        shape = `
            <ellipse cx="60" cy="95" rx="35" ry="12" fill="${samping}"/>
            <polygon points="60,15 26,95 94,95" fill="${depan}"/>`;
    } else if (bentuk === 'bola') {
        shape = `
            <circle cx="60" cy="60" r="45" fill="${depan}"/>
            <ellipse cx="45" cy="40" rx="16" ry="10" fill="#ffffff" opacity="0.35"/>`;
    } else {
        return '';
    }
    return `<div class="soal-visual"><svg viewBox="0 0 120 120" width="140" height="140">${shape}</svg></div>`;
}

function renderHitungBendaVisual(emoji, jumlah) {
    const items = Array.from({ length: jumlah }, () => `<span>${escapeHtml(emoji)}</span>`).join('');
    return `<div class="soal-visual"><div class="benda-grid">${items}</div></div>`;
}

function renderBandingkanBendaVisual(kiri, kanan) {
    const kolom = (grp) => `
        <div class="benda-kolom">
            <div class="benda-grid">${Array.from({ length: grp.jumlah }, () => `<span>${escapeHtml(grp.emoji)}</span>`).join('')}</div>
            ${grp.label ? `<div class="benda-label">${escapeHtml(grp.label)}</div>` : ''}
        </div>`;
    return `<div class="soal-visual"><div class="benda-bandingkan">${kolom(kiri)}${kolom(kanan)}</div></div>`;
}

function renderVisual(tipe, data) {
    if (!tipe || !data) return '';
    try {
        if (tipe === 'bangun_datar') return renderBangunDatarSvg(data.bentuk, data.warna);
        if (tipe === 'bangun_ruang') return renderBangunRuangSvg(data.bentuk, data.warna);
        if (tipe === 'hitung_benda') return renderHitungBendaVisual(data.emoji, data.jumlah);
        if (tipe === 'bandingkan_benda') return renderBandingkanBendaVisual(data.kiri, data.kanan);
    } catch (e) { /* abaikan visual yang datanya rusak, soal tetap tampil sebagai teks */ }
    return '';
}

function labelMateriSesi(materi) {
    if (!materi || materi === '__semua__') return 'Campur Semua';
    return materi;
}

function labelJenjang(j) {
    if (j === 'SD1') return 'SD Kelas 1';
    if (j === 'SD6') return 'SD Kelas 6';
    if (j === 'SMP9') return 'SMP/MTs Kelas 9';
    return '-';
}

function mapelIcon(mapel) {
    const m = (mapel || '').toLowerCase();
    if (m.includes('matematika') || m.includes('math')) return '🔢';
    if (m.includes('ipa') || m.includes('sains') || m.includes('science')) return '🔬';
    if (m.includes('bahasa indonesia') || m.includes('literasi')) return '📖';
    if (m.includes('inggris') || m.includes('english')) return '🗣️';
    if (m.includes('arab')) return '🌙';
    if (m.includes('hadits') || m.includes('hadis')) return '📿';
    if (m.includes('tarjamah') || m.includes('terjemah') || m.includes('quran') || m.includes('qur\'an')) return '📜';
    if (m.includes('ips') || m.includes('sosial')) return '🌍';
    if (m.includes('agama')) return '🕌';
    if (m.includes('ppkn') || m.includes('pkn') || m.includes('kewarganegaraan')) return '🏛️';
    if (m.includes('seni')) return '🎨';
    if (m.includes('olahraga') || m.includes('pjok')) return '⚽';
    if (m.includes('komputer') || m.includes('informatika') || m.includes('tik')) return '💻';
    return '📚';
}

function badgeJenjang(j) {
    return `<span class="badge badge-jenjang-${j}">${labelJenjang(j)}</span>`;
}
function badgeKesulitan(k) {
    return `<span class="badge badge-kesulitan-${escapeHtml(k)}">${escapeHtml(k)}</span>`;
}
function badgeSumber(s) {
    return `<span class="badge badge-sumber-${s === 'ai' ? 'ai' : 'manual'}">${s === 'ai' ? '✨ AI' : 'Manual'}</span>`;
}
function badgeSkor(skor) {
    const cls = skor >= 80 ? 'tinggi' : skor >= 60 ? 'sedang' : 'rendah';
    return `<span class="badge badge-skor-${cls}">${skor}</span>`;
}

function setupTabs(navSelector) {
    document.querySelectorAll(navSelector).forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const target = link.dataset.tab;
            document.querySelectorAll(navSelector).forEach((l) => l.classList.toggle('active', l.dataset.tab === target));
            document.querySelectorAll('.section-panel').forEach((p) => p.classList.toggle('active', p.id === 'tab-' + target));

            const titleEl = document.getElementById('page-title');
            if (titleEl) titleEl.textContent = link.textContent.trim();

            const offcanvasEl = document.getElementById('mobileSidebar');
            if (offcanvasEl && window.bootstrap) {
                const oc = bootstrap.Offcanvas.getInstance(offcanvasEl);
                if (oc) oc.hide();
            }

            document.dispatchEvent(new CustomEvent('tabchange', { detail: { tab: target } }));
        });
    });
}

const chartInstances = {};
function renderChart(canvasId, config) {
    const ctx = document.getElementById(canvasId);
    if (!ctx || typeof Chart === 'undefined') return;
    if (chartInstances[canvasId]) chartInstances[canvasId].destroy();
    chartInstances[canvasId] = new Chart(ctx, config);
}

function kpiCard(icon, color, value, label) {
    return `
        <div class="col-6 col-md-3">
            <div class="soft-card p-3 d-flex align-items-center gap-3 h-100">
                <div class="kpi-icon ${color}"><i class="bi ${icon}"></i></div>
                <div>
                    <div class="fw-bold fs-4">${value}</div>
                    <div class="text-muted small">${label}</div>
                </div>
            </div>
        </div>`;
}

// ===== Login page =====

function initLoginPage() {
    document.querySelectorAll('input[name=role-switch]').forEach((radio) => {
        radio.addEventListener('change', () => {
            const label = document.querySelector(`label[for="${radio.id}"]`);
            const target = label ? label.dataset.tab : null;
            document.querySelectorAll('.tab-panel').forEach((p) => p.classList.toggle('active', p.id === 'form-' + target));
        });
    });

    const selNama = document.getElementById('siswa-user-id');
    const selJenjang = document.getElementById('siswa-jenjang');
    if (selNama && selJenjang) {
        selNama.addEventListener('change', () => {
            const opt = selNama.selectedOptions[0];
            const jenjang = opt ? opt.dataset.jenjang : '';
            if (jenjang) selJenjang.value = jenjang;
        });
    }
}

// ===== Guru dashboard =====

function initGuruDashboard() {
    setupTabs('#sidebar-nav .nav-link[data-tab], #sidebar-nav-mobile .nav-link[data-tab]');

    loadRingkasan();
    loadKisiKisi();
    loadSoal();

    document.getElementById('filter-jenjang-kisikisi').addEventListener('change', (e) => loadKisiKisi(e.target.value));
    document.getElementById('filter-jenjang-soal').addEventListener('change', (e) => loadSoal(e.target.value));
    document.getElementById('filter-jenjang-analisis').addEventListener('change', (e) => loadAnalisis(e.target.value));

    document.getElementById('form-kisikisi').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const payload = Object.fromEntries(new FormData(form).entries());
        try {
            await apiPost('../api/kisikisi_add.php', payload);
            const jenjang = payload.jenjang;
            const mapel = payload.mapel;
            form.reset();
            form.querySelector('[name=jenjang]').value = jenjang;
            form.querySelector('[name=mapel]').value = mapel;
            form.querySelector('[name=kesulitan]').value = 'Sedang';
            form.querySelector('[name=jumlah]').value = 3;
            await loadKisiKisi(document.getElementById('filter-jenjang-kisikisi').value);
            await loadRingkasan();
        } catch (err) {
            alert(err.message);
        }
    });

    document.getElementById('soal-visual-tipe').addEventListener('change', (e) => {
        ['bangun_datar', 'bangun_ruang', 'hitung_benda', 'bandingkan_benda'].forEach((tipe) => {
            const aktif = e.target.value === tipe;
            const container = document.getElementById('visual-fields-' + tipe);
            container.classList.toggle('hidden', !aktif);
            // field non-aktif dinonaktifkan supaya tidak ikut terkirim (nama field visual_bentuk/visual_warna dipakai bersama)
            container.querySelectorAll('input, select').forEach((el) => { el.disabled = !aktif; });
        });
    });

    document.getElementById('form-soal').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const payload = Object.fromEntries(new FormData(form).entries());
        try {
            const kisiId = payload.kisi_id;
            await apiPost('../api/soal_add.php', payload);
            form.reset();
            form.querySelector('#soal-kisi-id').value = kisiId;
            ['bangun_datar', 'bangun_ruang', 'hitung_benda', 'bandingkan_benda'].forEach((tipe) => {
                document.getElementById('visual-fields-' + tipe).classList.add('hidden');
            });
            await loadSoal(document.getElementById('filter-jenjang-soal').value);
            await loadRingkasan();
        } catch (err) {
            alert(err.message);
        }
    });

    document.getElementById('form-ai-generate').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('btn-ai-generate');
        const status = document.getElementById('ai-generate-status');
        const payload = Object.fromEntries(new FormData(form).entries());
        payload.buatkan_soal = form.querySelector('[name=buatkan_soal]').checked;
        payload.buatkan_modul = form.querySelector('[name=buatkan_modul]').checked;

        const bagian = ['kisi-kisi'];
        if (payload.buatkan_modul) bagian.push('modul');
        if (payload.buatkan_soal) bagian.push('soal');
        btn.disabled = true;
        status.textContent = `Sedang membuat ${bagian.join(', ')} via AI, mohon tunggu...`;
        try {
            const hasil = await apiPost('../api/kisikisi_generate_ai.php', payload);
            let pesan = `Berhasil: ${hasil.kisi_ditambahkan} materi ditambahkan`;
            if (payload.buatkan_modul) pesan += `, ${hasil.modul_ditambahkan} modul dibuat`;
            if (payload.buatkan_soal) pesan += `, ${hasil.soal_ditambahkan} soal dibuat`;
            if (hasil.gagal_modul && hasil.gagal_modul.length) pesan += `. Sebagian modul gagal dibuat: ${hasil.gagal_modul.join('; ')}`;
            if (hasil.gagal_soal && hasil.gagal_soal.length) pesan += `. Sebagian soal gagal dibuat: ${hasil.gagal_soal.join('; ')}`;
            status.textContent = pesan;
            await loadKisiKisi(document.getElementById('filter-jenjang-kisikisi').value);
            await loadSoal(document.getElementById('filter-jenjang-soal').value);
            await loadRingkasan();
        } catch (err) {
            status.textContent = '';
            alert(err.message);
        } finally {
            btn.disabled = false;
        }
    });

    document.getElementById('btn-cetak-raport').addEventListener('click', () => window.print());

    document.getElementById('btn-buat-narasi').addEventListener('click', async () => {
        if (!raportSiswaTerpilih) return;
        const btn = document.getElementById('btn-buat-narasi');
        const status = document.getElementById('narasi-status');
        btn.disabled = true;
        status.textContent = 'Menganalisis data nilai... mohon tunggu.';
        try {
            const data = await apiPost('../api/raport_narasi.php', { user_id: raportSiswaTerpilih });
            document.getElementById('narasi-ringkasan-umum').textContent = data.narasi.ringkasan_umum;
            document.getElementById('narasi-kekuatan').textContent = data.narasi.kekuatan;
            document.getElementById('narasi-perlu-diperkuat').textContent = data.narasi.perlu_diperkuat;
            document.getElementById('narasi-rekomendasi').textContent = data.narasi.rekomendasi;
            document.getElementById('narasi-placeholder').classList.add('hidden');
            document.getElementById('narasi-hasil').classList.remove('hidden');
            status.textContent = '';
        } catch (err) {
            status.textContent = '';
            alert(err.message);
        } finally {
            btn.disabled = false;
        }
    });

    document.addEventListener('tabchange', (e) => {
        if (e.detail.tab === 'analisis') loadAnalisis(document.getElementById('filter-jenjang-analisis').value);
        if (e.detail.tab === 'ringkasan') loadRingkasan();
        if (e.detail.tab === 'raport') loadRaportSiswaPicker();
    });
}

let raportSiswaTerpilih = null;

async function loadRaportSiswaPicker() {
    const picker = document.getElementById('raport-siswa-picker');
    if (picker.dataset.loaded) return;
    picker.dataset.loaded = '1';

    const daftar = await apiGet('../api/siswa_list.php');
    picker.innerHTML = daftar.map((s) => `
        <button type="button" class="btn btn-outline-primary btn-sm raport-pilih-siswa" data-id="${s.id}" data-nama="${escapeHtml(s.nama)}">
            ${escapeHtml(s.nama)}
        </button>
    `).join('');

    picker.querySelectorAll('.raport-pilih-siswa').forEach((btn) => {
        btn.addEventListener('click', () => {
            picker.querySelectorAll('.raport-pilih-siswa').forEach((b) => b.classList.toggle('active', b === btn));
            loadRaportAnak(Number(btn.dataset.id));
        });
    });
}

async function loadRaportAnak(userId) {
    raportSiswaTerpilih = userId;

    // reset analisis naratif tiap ganti anak — narasi lama tidak relevan lagi
    document.getElementById('narasi-hasil').classList.add('hidden');
    document.getElementById('narasi-placeholder').classList.remove('hidden');
    document.getElementById('narasi-status').textContent = '';

    const data = await apiGet('../api/raport_anak.php?user_id=' + userId);

    document.getElementById('raport-placeholder').classList.add('hidden');
    document.getElementById('raport-print-area').classList.remove('hidden');
    document.getElementById('btn-cetak-raport').classList.remove('hidden');

    document.getElementById('raport-nama-siswa').textContent = data.siswa.nama;
    document.getElementById('raport-jenjang-siswa').textContent = data.siswa.jenjang_label;
    document.getElementById('raport-tanggal-cetak').textContent = 'Dicetak: ' + new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

    document.getElementById('raport-kpi').innerHTML =
        kpiCard('bi-pencil-square', 'bg-primary', data.ringkasan.total_sesi, 'Total Sesi Latihan') +
        kpiCard('bi-graph-up-arrow', 'bg-success', data.ringkasan.rata_rata, 'Rata-rata Skor') +
        kpiCard('bi-trophy-fill', 'bg-warning', data.ringkasan.skor_tertinggi, 'Skor Tertinggi') +
        kpiCard('bi-arrow-down-circle', 'bg-info', data.ringkasan.skor_terendah, 'Skor Terendah');

    const kronologis = [...data.riwayat];
    renderChart('chart-raport-anak', {
        type: 'line',
        data: {
            labels: kronologis.map((_, i) => 'Sesi ' + (i + 1)),
            datasets: [{
                label: 'Skor', data: kronologis.map((r) => r.skor),
                borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,.15)', tension: .3, fill: true,
            }],
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } },
    });

    document.querySelector('#table-raport-mapel tbody').innerHTML = data.per_mapel.map((m) => `
        <tr>
            <td>${escapeHtml(m.mapel)}</td>
            <td>${m.jumlah_soal_dikerjakan}</td>
            <td>${badgeSkor(m.nilai)}</td>
            <td><span class="badge badge-predikat-${m.predikat}">${m.predikat}</span></td>
            <td>${escapeHtml(m.keterangan)}</td>
        </tr>
    `).join('') || '<tr><td colspan="5" class="text-center text-muted py-4">Belum ada data latihan.</td></tr>';

    document.querySelector('#table-raport-riwayat tbody').innerHTML = [...data.riwayat].reverse().map((r) => `
        <tr>
            <td>${escapeHtml(r.waktu)}</td>
            <td>${escapeHtml(r.mapel || '-')}</td>
            <td>${escapeHtml(labelMateriSesi(r.materi))}</td>
            <td>${r.jumlah_soal}</td>
            <td>${r.jumlah_benar}</td>
            <td>${badgeSkor(r.skor)}</td>
        </tr>
    `).join('') || '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat latihan.</td></tr>';
}

const SISWA_JENJANG = [
    { jenjang: 'SD1', label: 'SD Kelas 1', color: '#20c997' },
    { jenjang: 'SD6', label: 'SD Kelas 6', color: '#3ea6ff' },
    { jenjang: 'SMP9', label: 'SMP/MTs Kelas 9', color: '#ff6b9d' },
];

async function loadRingkasan() {
    const [kisiRows, soalRows, ringkasanAll, ...ringkasanPerJenjang] = await Promise.all([
        apiGet('../api/kisikisi_list.php'),
        apiGet('../api/soal_list.php'),
        apiGet('../api/analisis.php'),
        ...SISWA_JENJANG.map((s) => apiGet('../api/analisis.php?jenjang=' + encodeURIComponent(s.jenjang))),
    ]);

    document.getElementById('kpi-ringkasan').innerHTML =
        kpiCard('bi-clipboard-data-fill', 'bg-primary', kisiRows.length, 'Total Kisi-kisi') +
        kpiCard('bi-journal-richtext', 'bg-success', soalRows.length, 'Total Bank Soal') +
        kpiCard('bi-pencil-square', 'bg-warning', ringkasanAll.ringkasan.total_sesi, 'Total Sesi Latihan') +
        kpiCard('bi-graph-up-arrow', 'bg-info', ringkasanAll.ringkasan.rata_rata, 'Rata-rata Skor Gabungan');

    renderChart('chart-perbandingan', {
        type: 'bar',
        data: {
            labels: SISWA_JENJANG.map((s) => s.label),
            datasets: [{
                label: 'Rata-rata Skor',
                data: ringkasanPerJenjang.map((r) => r.ringkasan.rata_rata),
                backgroundColor: SISWA_JENJANG.map((s) => s.color),
                borderRadius: 8,
            }],
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } },
    });

    const counts = {};
    kisiRows.forEach((r) => { counts[r.mapel] = (counts[r.mapel] || 0) + 1; });
    const labels = Object.keys(counts);
    const palette = ['#4f46e5', '#ff6b9d', '#ffa62b', '#20c997', '#8b5cf6', '#ffd23f', '#3ea6ff'];
    renderChart('chart-mapel', {
        type: 'doughnut',
        data: { labels, datasets: [{ data: Object.values(counts), backgroundColor: labels.map((_, i) => palette[i % palette.length]) }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
    });
}

async function loadKisiKisi(jenjang) {
    const url = '../api/kisikisi_list.php' + (jenjang ? '?jenjang=' + encodeURIComponent(jenjang) : '');
    const rows = await apiGet(url);
    const tbody = document.querySelector('#table-kisikisi tbody');
    tbody.innerHTML = rows.map((r) => `
        <tr>
            <td>${badgeJenjang(r.jenjang)}</td>
            <td>${escapeHtml(r.mapel)}</td>
            <td>${escapeHtml(r.materi)} ${r.modul_konten ? '<span class="badge bg-light text-dark border">📖 Modul</span>' : ''}</td>
            <td>${escapeHtml(r.indikator)}</td>
            <td>${escapeHtml(r.level_kognitif)}</td>
            <td>${badgeKesulitan(r.kesulitan)}</td>
            <td>${escapeHtml(String(r.jumlah_soal))}</td>
            <td class="text-nowrap">
                <button type="button" class="btn btn-sm btn-outline-secondary btn-ai-modul" data-id="${r.id}">${r.modul_konten ? 'Buat Ulang Modul' : '+Modul AI'}</button>
                <button type="button" class="btn btn-sm btn-outline-primary btn-ai-soal" data-id="${r.id}" data-jumlah="${r.jumlah_soal}">+Soal AI</button>
                <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="${r.id}">Hapus</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="8" class="text-center text-muted py-4">Belum ada kisi-kisi.</td></tr>';

    tbody.querySelectorAll('.btn-delete').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Hapus kisi-kisi ini? Semua soal terkait ikut terhapus.')) return;
            await apiPost('../api/kisikisi_delete.php', { id: Number(btn.dataset.id) });
            await loadKisiKisi(document.getElementById('filter-jenjang-kisikisi').value);
            await loadSoal(document.getElementById('filter-jenjang-soal').value);
            await loadRingkasan();
        });
    });

    tbody.querySelectorAll('.btn-ai-modul').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const instruksi = prompt(
                'Ada topik/penekanan khusus yang ingin ditambahkan ke modul ini? (opsional, kosongkan kalau tidak ada)\n' +
                'Contoh: "tambahkan penjelasan & contoh soal cerita tentang diskon"',
                ''
            );
            if (instruksi === null) return; // guru klik Cancel
            btn.disabled = true;
            const teksAsli = btn.textContent;
            btn.textContent = 'Membuat...';
            try {
                await apiPost('../api/modul_generate_ai.php', { kisi_id: Number(btn.dataset.id), instruksi });
                await loadKisiKisi(document.getElementById('filter-jenjang-kisikisi').value);
            } catch (err) {
                alert(err.message);
                btn.disabled = false;
                btn.textContent = teksAsli;
            }
        });
    });

    tbody.querySelectorAll('.btn-ai-soal').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const jumlah = prompt('Buat berapa soal baru via AI untuk materi ini?', btn.dataset.jumlah || '3');
            if (!jumlah) return;
            btn.disabled = true;
            btn.textContent = 'Membuat...';
            try {
                const hasil = await apiPost('../api/soal_generate_ai.php', { kisi_id: Number(btn.dataset.id), jumlah: Number(jumlah) });
                alert(`${hasil.ditambahkan} soal berhasil ditambahkan.`);
                await loadSoal(document.getElementById('filter-jenjang-soal').value);
                await loadRingkasan();
            } catch (err) {
                alert(err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = '+Soal AI';
            }
        });
    });

    // isi dropdown kisi-kisi di form tambah soal
    const selKisi = document.getElementById('soal-kisi-id');
    const allRows = jenjang ? await apiGet('../api/kisikisi_list.php') : rows;
    selKisi.innerHTML = allRows.map((r) =>
        `<option value="${r.id}">[${labelJenjang(r.jenjang)}] ${escapeHtml(r.mapel)}: ${escapeHtml(r.materi)} - ${escapeHtml(r.indikator)}</option>`
    ).join('') || '<option value="">Belum ada kisi-kisi</option>';
}

async function loadSoal(jenjang) {
    const url = '../api/soal_list.php' + (jenjang ? '?jenjang=' + encodeURIComponent(jenjang) : '');
    const rows = await apiGet(url);
    const tbody = document.querySelector('#table-soal tbody');
    tbody.innerHTML = rows.map((r) => `
        <tr>
            <td>${badgeJenjang(r.jenjang)}</td>
            <td>${escapeHtml(r.mapel)}</td>
            <td>${escapeHtml(r.materi)}</td>
            <td>${escapeHtml(r.pertanyaan)} ${r.visual_tipe ? '<span class="badge bg-light text-dark border">🖼️ Visual</span>' : ''}</td>
            <td>${r.kunci.toUpperCase()}</td>
            <td>${badgeSumber(r.sumber)}</td>
            <td><button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="${r.id}">Hapus</button></td>
        </tr>
    `).join('') || '<tr><td colspan="7" class="text-center text-muted py-4">Belum ada soal.</td></tr>';

    tbody.querySelectorAll('.btn-delete').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Hapus soal ini?')) return;
            await apiPost('../api/soal_delete.php', { id: Number(btn.dataset.id) });
            await loadSoal(document.getElementById('filter-jenjang-soal').value);
            await loadRingkasan();
        });
    });
}

async function loadAnalisis(jenjang) {
    const url = '../api/analisis.php' + (jenjang ? '?jenjang=' + encodeURIComponent(jenjang) : '');
    const data = await apiGet(url);

    const skorTertinggi = data.siswa.length ? Math.max(...data.siswa.map((s) => s.skor)) : 0;
    document.getElementById('analisis-ringkasan').innerHTML =
        kpiCard('bi-pencil-square', 'bg-primary', data.ringkasan.total_sesi, 'Total Sesi') +
        kpiCard('bi-graph-up-arrow', 'bg-success', data.ringkasan.rata_rata, 'Rata-rata Skor') +
        kpiCard('bi-rulers', 'bg-warning', data.ringkasan.simpangan_baku, 'Simpangan Baku') +
        kpiCard('bi-trophy-fill', 'bg-info', skorTertinggi, 'Skor Tertinggi');

    const byNama = {};
    [...data.siswa].sort((a, b) => new Date(a.waktu) - new Date(b.waktu)).forEach((s) => {
        byNama[s.nama] = byNama[s.nama] || [];
        byNama[s.nama].push(s.skor);
    });
    const tren = Object.entries(byNama);
    const maxLen = tren.length ? Math.max(...tren.map(([, arr]) => arr.length)) : 0;
    const palette = ['#3ea6ff', '#ff6b9d', '#20c997', '#ffa62b'];
    renderChart('chart-tren', {
        type: 'line',
        data: {
            labels: Array.from({ length: maxLen }, (_, i) => 'Sesi ' + (i + 1)),
            datasets: tren.map(([nama, arr], i) => ({
                label: nama, data: arr, borderColor: palette[i % palette.length],
                backgroundColor: palette[i % palette.length], tension: .3, spanGaps: true,
            })),
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } } },
    });

    const katCounts = {};
    data.siswa.forEach((s) => { katCounts[s.kategori] = (katCounts[s.kategori] || 0) + 1; });
    renderChart('chart-kategori', {
        type: 'doughnut',
        data: {
            labels: Object.keys(katCounts),
            datasets: [{ data: Object.values(katCounts), backgroundColor: ['#20c997', '#3ea6ff', '#ffd23f', '#ffa62b', '#ff6b9d'] }],
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
    });

    document.querySelector('#table-siswa tbody').innerHTML = data.siswa.map((s) => `
        <tr>
            <td>${escapeHtml(s.nama)}</td>
            <td>${badgeJenjang(s.jenjang)}</td>
            <td>${escapeHtml(s.materi || '-')}</td>
            <td>${badgeSkor(s.skor)}</td>
            <td>${escapeHtml(s.kategori)}</td>
            <td>${escapeHtml(s.waktu)}</td>
        </tr>
    `).join('') || '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada data sesi.</td></tr>';

    document.querySelector('#table-butir tbody').innerHTML = data.butir_soal.map((b) => `
        <tr>
            <td>${escapeHtml(b.pertanyaan)}</td>
            <td>${badgeJenjang(b.jenjang)}</td>
            <td>${escapeHtml(b.mapel)}</td>
            <td>${escapeHtml(b.materi)}</td>
            <td>${b.P}</td>
            <td>${b.D}</td>
        </tr>
    `).join('') || '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada data butir soal.</td></tr>';
}

// ===== Siswa dashboard =====

const latihanState = {
    soal: [],
    index: 0,
    jawaban: {},
    kisiMap: {},
    currentMapel: null,
    subjectsByMapel: {},
};

function initSiswaDashboard() {
    setupTabs('.kid-tabs .nav-link[data-tab]');

    loadSubjectCards();

    document.getElementById('btn-back-subjects').addEventListener('click', showSubjectsView);
    document.getElementById('btn-back-materi').addEventListener('click', () => showMateriView(latihanState.currentMapel));
    document.getElementById('btn-mapel-semua').addEventListener('click', () => mulaiLatihan({ mapel: latihanState.currentMapel }));
    document.getElementById('btn-prev').addEventListener('click', () => geserSoal(-1));
    document.getElementById('btn-next').addEventListener('click', () => geserSoal(1));
    document.getElementById('btn-selesai').addEventListener('click', kumpulkanLatihan);
    document.getElementById('btn-latihan-lagi').addEventListener('click', () => {
        document.getElementById('latihan-hasil').classList.add('hidden');
        showSubjectsView();
    });
    document.getElementById('btn-lihat-raport').addEventListener('click', () => {
        document.getElementById('latihan-hasil').classList.add('hidden');
        document.querySelector('.kid-tabs .nav-link[data-tab="raport"]').click();
    });

    document.addEventListener('tabchange', (e) => {
        if (e.detail.tab === 'raport') loadRaport();
    });
}

async function loadSubjectCards() {
    const rows = await apiGet('../api/kisikisi_list.php');
    const byMapel = {};
    rows.forEach((r) => {
        byMapel[r.mapel] = byMapel[r.mapel] || [];
        byMapel[r.mapel].push(r);
    });
    latihanState.subjectsByMapel = byMapel;

    const palette = ['var(--kid-purple)', 'var(--kid-pink)', 'var(--kid-orange)', 'var(--kid-teal)', 'var(--kid-blue)'];
    const mapels = Object.keys(byMapel);

    let html = mapels.map((mapel, i) => {
        const list = byMapel[mapel];
        const totalSoal = list.reduce((sum, r) => sum + Number(r.jumlah_soal || 0), 0);
        return `
            <div class="col-6 col-md-4">
                <div class="subject-card" style="background:${palette[i % palette.length]}" data-mapel="${escapeHtml(mapel)}">
                    <div class="emoji">${mapelIcon(mapel)}</div>
                    <div class="title mt-2">${escapeHtml(mapel)}</div>
                    <div class="meta">${list.length} materi &bull; ~${totalSoal} soal</div>
                </div>
            </div>`;
    }).join('');

    if (mapels.length > 1) {
        html += `
            <div class="col-6 col-md-4">
                <div class="subject-card" style="background:linear-gradient(135deg, var(--kid-yellow), var(--kid-orange));" data-mapel="__semua__">
                    <div class="emoji">🎲</div>
                    <div class="title mt-2">Campur Semua</div>
                    <div class="meta">Semua mata pelajaran diacak</div>
                </div>
            </div>`;
    }

    document.getElementById('subject-cards').innerHTML = html || '<p class="text-muted">Belum ada materi. Minta guru menambahkan dulu ya!</p>';

    document.querySelectorAll('.subject-card').forEach((card) => {
        card.addEventListener('click', () => {
            const mapel = card.dataset.mapel;
            if (mapel === '__semua__') {
                mulaiLatihan({ materi: '__semua__' });
            } else {
                showMateriView(mapel);
            }
        });
    });
}

function showMateriView(mapel) {
    latihanState.currentMapel = mapel;
    document.getElementById('materi-mapel-title').textContent = mapelIcon(mapel) + ' ' + mapel;
    const list = latihanState.subjectsByMapel[mapel] || [];
    document.getElementById('materi-chip-list').innerHTML = list.map((r) => `
        <button type="button" class="materi-chip" data-materi="${escapeHtml(r.materi)}">
            ${r.modul_konten ? '📖 ' : ''}${escapeHtml(r.materi)}
        </button>
    `).join('');
    document.querySelectorAll('#materi-chip-list .materi-chip').forEach((chip) => {
        chip.addEventListener('click', () => {
            const row = list.find((r) => r.materi === chip.dataset.materi);
            if (row && row.modul_konten) {
                showModulView(row);
            } else {
                mulaiLatihan({ materi: chip.dataset.materi });
            }
        });
    });
    document.getElementById('view-subjects').classList.add('hidden');
    document.getElementById('view-modul').classList.add('hidden');
    document.getElementById('view-materi').classList.remove('hidden');
}

/**
 * Modul AI sering menulis daftar tahapan sebagai satu paragraf panjang berisi penanda
 * "1) ... 2) ... 3) ..." atau "Langkah 1: ... Langkah 2: ...". Fungsi ini mendeteksi pola
 * tsb dan mengubahnya jadi daftar bullet HTML asli supaya lebih mudah dibaca siswa,
 * daripada satu blok teks panjang. Kalau tidak ada pola list, tampilkan sebagai paragraf biasa.
 */
function formatModulIsi(text) {
    if (!text) return '';
    const raw = String(text).trim();
    const renderList = (items) =>
        `<ul class="modul-isi-list">${items.map((p) => `<li>${escapeHtml(p.trim())}</li>`).join('')}</ul>`;
    const renderParas = (lines) => lines.map((p) => `<p>${escapeHtml(p.trim())}</p>`).join('');

    // Cari rangkaian "chunks" (potongan teks) yang berurutan 1,2,3,... sesuai markerRe (harus punya
    // capture group 1 = angka urutan) di dalamnya, lalu render bagian itu sebagai <ul> dan sisa
    // teks sebelum/sesudahnya (kalimat pembuka/penutup) sebagai paragraf biasa.
    const cobaListBernomor = (chunks, markerRe, stripRe) => {
        const startIdx = chunks.findIndex((c) => markerRe.test(c) && Number(c.match(markerRe)[1]) === 1);
        if (startIdx === -1) return null;
        let endIdx = startIdx;
        while (
            endIdx + 1 < chunks.length
            && markerRe.test(chunks[endIdx + 1])
            && Number(chunks[endIdx + 1].match(markerRe)[1]) === (endIdx - startIdx) + 2
        ) {
            endIdx++;
        }
        if (endIdx - startIdx < 1) return null; // minimal 2 item berurutan
        const before = chunks.slice(0, startIdx);
        const items = chunks.slice(startIdx, endIdx + 1).map((c) => c.replace(stripRe, ''));
        const after = chunks.slice(endIdx + 1);
        return renderParas(before) + renderList(items) + renderParas(after);
    };

    // Pola "Langkah 1: ... Langkah 2: ..." dalam satu paragraf (boleh didahului kalimat pembuka/soal)
    let hasil = cobaListBernomor(
        raw.split(/\s*(?=Langkah\s+\d+\s*:)/).map((s) => s.trim()).filter(Boolean),
        /^Langkah\s+(\d+)\s*:/, /^Langkah\s+\d+\s*:\s*/
    );
    if (hasil) return hasil;

    // Pola "1) ... 2) ... 3) ..." dalam satu paragraf
    hasil = cobaListBernomor(
        raw.split(/\s+(?=\d+\)\s)/).map((s) => s.trim()).filter(Boolean),
        /^(\d+)\)/, /^\d+\)\s*/
    );
    if (hasil) return hasil;

    if (raw.includes('\n')) {
        const baris = raw.split(/\n+/).map((s) => s.trim()).filter(Boolean);

        // Pola baris bernomor dengan newline asli: "1. ...\n2. ...\n3. ..."
        hasil = cobaListBernomor(baris, /^(\d+)\.\s/, /^\d+\.\s*/);
        if (hasil) return hasil;

        // Bukan daftar bernomor tapi ada beberapa baris -> tetap tampilkan sebagai paragraf terpisah
        // (bukan digabung jadi satu blok teks panjang) supaya enak dibaca.
        if (baris.length > 1) return renderParas(baris);
    }

    return `<p>${escapeHtml(raw)}</p>`;
}

function showModulView(row) {
    let modul;
    try {
        modul = JSON.parse(row.modul_konten);
    } catch (e) {
        mulaiLatihan({ materi: row.materi }); // modul rusak, langsung ke latihan saja
        return;
    }

    document.getElementById('modul-mapel-label').textContent = mapelIcon(row.mapel) + ' ' + row.mapel;
    document.getElementById('modul-materi-title').textContent = row.materi;

    document.getElementById('modul-tujuan').innerHTML =
        (modul.tujuan_pembelajaran || []).map((t) => `<li>${escapeHtml(t)}</li>`).join('');

    document.getElementById('modul-penjelasan').innerHTML =
        (modul.penjelasan || []).map((p) => `
            <div class="modul-penjelasan-bagian">
                <div class="judul">${escapeHtml(p.judul)}</div>
                <div class="isi">${formatModulIsi(p.isi)}</div>
            </div>
        `).join('');

    const contohWrap = document.getElementById('modul-contoh-wrap');
    if (modul.contoh && modul.contoh.length) {
        document.getElementById('modul-contoh').innerHTML = modul.contoh.map((c) => `
            <div class="modul-contoh-item">
                <div class="judul">${escapeHtml(c.judul)}</div>
                <div class="isi">${formatModulIsi(c.isi)}</div>
            </div>
        `).join('');
        contohWrap.classList.remove('hidden');
    } else {
        contohWrap.classList.add('hidden');
    }

    const istilahWrap = document.getElementById('modul-istilah-wrap');
    if (modul.istilah_kunci && modul.istilah_kunci.length) {
        document.getElementById('modul-istilah').innerHTML = modul.istilah_kunci.map((it) => `
            <div class="col-6 col-md-4">
                <div class="modul-istilah-item">
                    <div class="istilah">${escapeHtml(it.istilah)}</div>
                    <div class="arti">${escapeHtml(it.arti)}</div>
                </div>
            </div>
        `).join('');
        istilahWrap.classList.remove('hidden');
    } else {
        istilahWrap.classList.add('hidden');
    }

    document.getElementById('modul-poin-penting').innerHTML =
        (modul.poin_penting || []).map((p) => `<li>${escapeHtml(p)}</li>`).join('');

    document.getElementById('btn-modul-ke-latihan').onclick = () => mulaiLatihan({ materi: row.materi });

    document.getElementById('view-materi').classList.add('hidden');
    document.getElementById('view-modul').classList.remove('hidden');
}

function showSubjectsView() {
    document.getElementById('view-materi').classList.add('hidden');
    document.getElementById('view-modul').classList.add('hidden');
    document.getElementById('latihan-area').classList.add('hidden');
    document.getElementById('latihan-hasil').classList.add('hidden');
    document.getElementById('view-subjects').classList.remove('hidden');
}

async function mulaiLatihan(opts) {
    const params = new URLSearchParams();
    if (opts.materi) params.set('materi', opts.materi);
    else if (opts.mapel) params.set('mapel', opts.mapel);

    try {
        const soal = await apiGet('../api/sesi_mulai.php?' + params.toString());
        latihanState.soal = soal;
        latihanState.index = 0;
        latihanState.jawaban = {};
        latihanState.kisiMap = {};
        soal.forEach((s) => { latihanState.kisiMap[s.soal_id] = s.kisi_id; });

        document.getElementById('view-subjects').classList.add('hidden');
        document.getElementById('view-materi').classList.add('hidden');
        document.getElementById('view-modul').classList.add('hidden');
        document.getElementById('latihan-area').classList.remove('hidden');
        document.getElementById('latihan-mapel-label').textContent =
            opts.materi && opts.materi !== '__semua__' ? opts.materi : (opts.mapel || 'Semua Materi');
        renderSoalAktif();
    } catch (err) {
        alert(err.message);
    }
}

function renderSoalAktif() {
    const s = latihanState.soal[latihanState.index];
    const total = latihanState.soal.length;
    document.getElementById('latihan-progress').textContent = `Soal ${latihanState.index + 1} dari ${total}`;
    document.getElementById('progress-bar').style.width = Math.round(((latihanState.index + 1) / total) * 100) + '%';

    const dipilih = latihanState.jawaban[s.soal_id];
    const letters = { a: 'A', b: 'B', c: 'C', d: 'D' };
    document.getElementById('latihan-soal').innerHTML = `
        <div class="pertanyaan mb-3">${escapeHtml(s.pertanyaan)}</div>
        ${renderVisual(s.visual_tipe, s.visual_data)}
        <div class="opsi-list">
            ${s.opsi.map((o) => `
                <button type="button" class="opsi-btn ${dipilih === o.id ? 'selected' : ''}" data-opsi="${o.id}">
                    <span class="letter">${letters[o.id]}</span>${escapeHtml(o.text)}
                </button>
            `).join('')}
        </div>
    `;

    document.querySelectorAll('.opsi-btn').forEach((el) => {
        el.addEventListener('click', () => {
            latihanState.jawaban[s.soal_id] = el.dataset.opsi;
            renderSoalAktif();
        });
    });

    document.getElementById('btn-prev').disabled = latihanState.index === 0;
    const isLast = latihanState.index === total - 1;
    document.getElementById('btn-next').classList.toggle('hidden', isLast);
    document.getElementById('btn-selesai').classList.toggle('hidden', !isLast);
}

function geserSoal(delta) {
    const total = latihanState.soal.length;
    const next = latihanState.index + delta;
    if (next < 0 || next >= total) return;
    latihanState.index = next;
    renderSoalAktif();
}

async function kumpulkanLatihan() {
    const totalSoal = latihanState.soal.length;
    const totalDijawab = Object.keys(latihanState.jawaban).length;
    if (totalDijawab < totalSoal) {
        if (!confirm(`Baru ${totalDijawab} dari ${totalSoal} soal dijawab. Tetap kumpulkan?`)) return;
    }
    try {
        const hasil = await apiPost('../api/sesi_submit.php', {
            jawaban: latihanState.jawaban,
            kisi_map: latihanState.kisiMap,
        });
        document.getElementById('latihan-area').classList.add('hidden');
        document.getElementById('latihan-hasil').classList.remove('hidden');

        const skor = hasil.skor;
        const emoji = skor >= 90 ? '🏆' : skor >= 75 ? '🌟' : skor >= 60 ? '👍' : '💪';
        document.getElementById('hasil-emoji').textContent = emoji;
        document.getElementById('hasil-score-num').textContent = skor;
        document.getElementById('latihan-skor').textContent = `${hasil.jumlah_benar} benar dari ${hasil.total} soal`;
    } catch (err) {
        alert(err.message);
    }
}

async function loadRaport() {
    const data = await apiGet('../api/raport_siswa.php');
    document.getElementById('raport-ringkasan').innerHTML = `
        <div class="col-4"><div class="stat-pill bg-primary"><div class="value">${data.ringkasan.total_sesi}</div><div class="label">Total Sesi</div></div></div>
        <div class="col-4"><div class="stat-pill bg-success"><div class="value">${data.ringkasan.rata_rata}</div><div class="label">Rata-rata Skor</div></div></div>
        <div class="col-4"><div class="stat-pill" style="background:var(--kid-orange)"><div class="value">${data.ringkasan.skor_terbaik}</div><div class="label">Skor Terbaik</div></div></div>
    `;

    const kronologis = [...data.riwayat].reverse();
    renderChart('chart-raport', {
        type: 'line',
        data: {
            labels: kronologis.map((_, i) => 'Sesi ' + (i + 1)),
            datasets: [{
                label: 'Skor', data: kronologis.map((r) => r.skor),
                borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,.15)', tension: .3, fill: true,
            }],
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } },
    });

    document.querySelector('#table-raport tbody').innerHTML = data.riwayat.map((r) => `
        <tr>
            <td>${escapeHtml(r.waktu)}</td>
            <td>${escapeHtml(r.mapel || '-')}</td>
            <td>${escapeHtml(labelMateriSesi(r.materi))}</td>
            <td>${r.jumlah_soal}</td>
            <td>${r.jumlah_benar}</td>
            <td>${badgeSkor(r.skor)}</td>
        </tr>
    `).join('') || '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat latihan.</td></tr>';
}
