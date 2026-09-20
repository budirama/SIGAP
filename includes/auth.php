<?php

require_once __DIR__ . '/../config/env.php';
loadEnv();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function siswaMagicKeyword(): string
{
    // Sengaja TIDAK di-fallback ke kata kunci default yang sama dengan yang dipakai di produksi
    // (repo ini publik) -- wajib diset lewat .env (lihat SISWA_MAGIC_KEYWORD di .env.example).
    return getenv('SISWA_MAGIC_KEYWORD') ?: 'GANTI_KATA_KUNCI_DI_ENV';
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function jsonError(int $code, string $message): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit;
}

function requireGuru(): array
{
    $u = currentUser();
    if (!$u || $u['role'] !== 'guru') {
        jsonError(403, 'Hanya guru yang boleh mengakses ini.');
    }
    return $u;
}

function requireSiswa(): array
{
    $u = currentUser();
    if (!$u || $u['role'] !== 'siswa') {
        jsonError(403, 'Silakan login sebagai siswa terlebih dahulu.');
    }
    return $u;
}
