<?php
if (session_status() == PHP_SESSION_NONE) {

    // Deteksi Otomatis koneksi aman (HTTPS)
    $is_https = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    );

    // Hardening: Paksa session hanya via cookie (bukan URL), strict mode
    ini_set('session.use_only_cookies', 1);   // Session ID tidak boleh lewat URL
    ini_set('session.use_strict_mode', 1);     // Tolak session ID ilegal
    ini_set('session.cookie_httponly', 1);     // Anti XSS cookie theft
    ini_set('session.cookie_secure', $is_https ? '1' : '0'); // Otomatis True jika HTTPS

    // Set parameter cookie (SameSite=Strict tersedia native di PHP 7.3+)
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $is_https, 
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    session_start();
}

// (Header dasar, tidak memblokir CDN eksternal)
if (!headers_sent()) {
    // Mencegah halaman di-embed dalam iframe di domain lain (anti-Clickjacking)
    header('X-Frame-Options: SAMEORIGIN');

    // Mencegah browser menebak MIME type (anti MIME-Sniffing)
    header('X-Content-Type-Options: nosniff');

    // Kontrol informasi Referer yang dikirim saat navigasi
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Filter XSS bawaan browser (legacy, tetapi masih berguna)
    header('X-XSS-Protection: 1; mode=block');

    // Batasi akses ke fitur browser sensitif
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

error_reporting(0);
ini_set('display_errors', 0);


define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sik');


$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);


if ($koneksi->connect_error) {
    
    error_log('[Dashboard Eksekutif] Koneksi DB gagal: ' . $koneksi->connect_error);
    die('Layanan sementara tidak tersedia. Silakan hubungi administrator.');
}


$koneksi->set_charset("utf8mb4");


try {
    $koneksi_pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $koneksi_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $koneksi_pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[Dashboard Eksekutif] Koneksi PDO DB gagal: ' . $e->getMessage());
    die('Layanan sementara tidak tersedia. Silakan hubungi administrator.');
}


date_default_timezone_set('Asia/Jakarta');
?>