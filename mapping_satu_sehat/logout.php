<?php
/**
 * logout.php — Handler Logout
 * Menghapus seluruh session dan redirect ke halaman login.
 */
session_start();
$_SESSION = [];                             // Kosongkan semua data session

// Hapus cookie session jika ada
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();                          // Hancurkan session di server

header('Location: login.php');
exit;
?>
