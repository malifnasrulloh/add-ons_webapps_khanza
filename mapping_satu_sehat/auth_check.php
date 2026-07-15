<?php
/**
 * auth_check.php — Guard Otentikasi & Hak Akses (RBAC)
 *
 * Cara pakai di setiap halaman modul:
 *   require_once '../../auth_check.php';
 *   check_module_access('satu_sehat_mapping_obat'); // nama kolom di tabel user
 *
 * Super admin (dari tabel `admin`) selalu lolos semua modul.
 */

// conf.php sudah di-include oleh pemanggil (index.php modul),
// jadi session sudah aktif. Tapi kita pastikan lagi.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login.
 * Jika belum, redirect ke halaman login.
 */
function require_login() {
    if (empty($_SESSION['user_id'])) {
        $depth = substr_count($_SERVER['SCRIPT_NAME'], '/') - 1;
        $up    = max(0, $depth - 1);
        header('Location: ' . str_repeat('../', $up) . 'login.php');
        exit;
    }
}

/**
 * Cek hak akses modul.
 * @param string $modul — Nama kolom di tabel `user` (misal: 'satu_sehat_mapping_obat')
 */
function check_module_access($modul) {
    require_login();

    // Super admin selalu punya akses penuh ke semua modul
    if (!empty($_SESSION['is_admin'])) {
        return; // Lolos
    }

    // Cek kolom hak akses di data user yang sudah disimpan saat login
    $hak_akses = isset($_SESSION['hak_akses'][$modul]) ? $_SESSION['hak_akses'][$modul] : 'false';

    if ($hak_akses !== 'true') {
        // Jika request AJAX, kirim JSON error
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses ke modul ini.']);
            exit;
        }
        // Untuk halaman biasa, redirect ke dashboard dengan pesan error
        $_SESSION['flash_error'] = 'Anda tidak memiliki hak akses ke modul tersebut.';
        header('Location: ../../index.php');
        exit;
    }
}
?>
