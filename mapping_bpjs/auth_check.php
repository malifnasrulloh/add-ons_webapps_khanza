<?php
/**
 * auth_check.php — Guard Otentikasi & Hak Akses (RBAC) untuk Mapping BPJS
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (empty($_SESSION['user_id'])) {
        $depth = substr_count($_SERVER['SCRIPT_NAME'], '/') - 1;
        $up    = max(0, $depth - 1);
        header('Location: ' . str_repeat('../', $up) . 'login.php');
        exit;
    }
}

function check_module_access($modul) {
    require_login();

    if (!empty($_SESSION['is_admin'])) {
        return;
    }

    $hak_akses = isset($_SESSION['hak_akses'][$modul]) ? $_SESSION['hak_akses'][$modul] : 'false';

    if ($hak_akses !== 'true') {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses ke modul ini.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Anda tidak memiliki hak akses ke modul tersebut.';
        header('Location: ../../index.php');
        exit;
    }
}
?>
