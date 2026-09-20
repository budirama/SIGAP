<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

if (currentUser()) {
    $role = currentUser()['role'];
    header('Location: ' . ($role === 'guru' ? 'index.php' : 'siswa.php'));
    exit;
}

$error = '';
$db = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis = $_POST['jenis'] ?? '';

    if ($jenis === 'guru') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'guru'");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if (!$u || !$u['password_hash'] || !password_verify($password, $u['password_hash'])) {
            $error = 'Email atau password guru salah.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $u['id'], 'nama' => $u['nama'], 'role' => 'guru', 'jenjang' => null];
            header('Location: index.php');
            exit;
        }
    } elseif ($jenis === 'siswa') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $jenjangDipilih = $_POST['jenjang'] ?? '';
        $kataKunci = trim($_POST['kata_kunci'] ?? '');

        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'siswa'");
        $stmt->execute([$userId]);
        $u = $stmt->fetch();

        if (!$u) {
            $error = 'Nama tidak ditemukan.';
        } elseif ($u['jenjang'] !== $jenjangDipilih) {
            $error = 'Jenjang yang dipilih tidak cocok dengan nama tersebut.';
        } elseif (!hash_equals(siswaMagicKeyword(), $kataKunci)) {
            $error = 'Kata kunci ajaib salah.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $u['id'], 'nama' => $u['nama'], 'role' => 'siswa', 'jenjang' => $u['jenjang']];
            header('Location: siswa.php');
            exit;
        }
    }
}

$daftarSiswa = $db->query("SELECT id, nama, jenjang FROM users WHERE role = 'siswa' ORDER BY nama ASC")->fetchAll();
$tabAktif = ($_POST['jenis'] ?? 'siswa') === 'guru' ? 'guru' : 'siswa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - SIGAP - SIstem Generate &amp; Analisis Pembelajaran</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="login-shell d-flex flex-wrap">
    <div class="login-hero d-flex align-items-center justify-content-center flex-fill p-5" style="flex-basis: 40%;">
        <div class="content text-center">
            <div class="emoji-row mb-3">
                <span>🧠</span><span>📚</span><span>🏆</span>
            </div>
            <h1 class="font-baloo fw-bold mb-1">SIGAP</h1>
            <div class="fw-semibold mb-3 opacity-90" style="font-size: 1.05rem; letter-spacing: 0.3px;">SIstem Generate &amp; Analisis Pembelajaran</div>
            <p class="opacity-75 mb-0">Belajar seru, latihan soal otomatis dibuat AI,<br>raport pribadi untuk setiap anak.</p>
        </div>
    </div>

    <div class="d-flex flex-column align-items-center justify-content-center flex-fill p-4" style="flex-basis: 60%;">
        <div class="soft-card login-card p-4 p-md-5 w-100" style="max-width: 440px;">
            <h4 class="fw-bold mb-1">Selamat datang 👋</h4>
            <p class="text-muted small mb-4">Masuk untuk mulai belajar atau kelola kuis.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="role-toggle d-flex mb-4">
                <input type="radio" class="btn-check" name="role-switch" id="role-siswa" autocomplete="off" <?= $tabAktif === 'siswa' ? 'checked' : '' ?>>
                <label class="btn flex-fill py-2" for="role-siswa" data-tab="siswa"><i class="bi bi-emoji-smile me-1"></i>Siswa</label>

                <input type="radio" class="btn-check" name="role-switch" id="role-guru" autocomplete="off" <?= $tabAktif === 'guru' ? 'checked' : '' ?>>
                <label class="btn flex-fill py-2" for="role-guru" data-tab="guru"><i class="bi bi-person-badge me-1"></i>Guru</label>
            </div>

            <form method="post" id="form-siswa" class="tab-panel <?= $tabAktif === 'siswa' ? 'active' : '' ?>">
                <input type="hidden" name="jenis" value="siswa">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama</label>
                    <select name="user_id" id="siswa-user-id" class="form-select" required>
                        <option value="">-- pilih nama --</option>
                        <?php foreach ($daftarSiswa as $s): ?>
                            <option value="<?= (int)$s['id'] ?>" data-jenjang="<?= htmlspecialchars($s['jenjang']) ?>">
                                <?= htmlspecialchars($s['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Jenjang</label>
                    <select name="jenjang" id="siswa-jenjang" class="form-select" required>
                        <option value="">-- pilih jenjang --</option>
                        <option value="SD1">SD Kelas 1</option>
                        <option value="SD6">SD Kelas 6</option>
                        <option value="SMP9">SMP/MTs Kelas 9</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-semibold">Kata kunci ajaib 🔑</label>
                    <input type="text" name="kata_kunci" class="form-control" placeholder="Tanya guru/orang tua" required autocomplete="off">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Masuk & Mulai Belajar</button>
            </form>

            <form method="post" id="form-guru" class="tab-panel <?= $tabAktif === 'guru' ? 'active' : '' ?>">
                <input type="hidden" name="jenis" value="guru">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-dark w-100 py-2 fw-semibold">Masuk sebagai Guru</button>
            </form>
        </div>
        <footer class="text-center text-muted small mt-4">
            Develop by PAPA B03d1
        </footer>
    </div>
</div>
<script src="assets/app.js"></script>
<script>
initLoginPage();
</script>
</body>
</html>
