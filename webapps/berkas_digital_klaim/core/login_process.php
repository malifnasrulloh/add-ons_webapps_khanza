<?php
/*
 * File: core/login_process.php (V2 - Casemix Auth)
 */
session_start();
require_once(dirname(__DIR__) . '/config/koneksi.php'); // Pastikan path ini benar

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password_input = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($username) || empty($password_input)) {
    header('Location: ../index.php?error=empty');
    exit;
}

// 1. CEK SUPER ADMIN (Bypass Segala Aturan)
$sql_admin = "SELECT AES_DECRYPT(usere, 'nur') as usere, AES_DECRYPT(passworde, 'windi') as passworde FROM admin WHERE AES_DECRYPT(usere, 'nur') = ?";
$stmt_admin = $koneksi->prepare($sql_admin);
$stmt_admin->bind_param("s", $username);
$stmt_admin->execute();
$res_admin = $stmt_admin->get_result();

if ($res_admin->num_rows === 1) {
    $row = $res_admin->fetch_assoc();
    if ($row['passworde'] === $password_input) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $username;
        $_SESSION['role'] = 'Super Admin';
        $_SESSION['is_login'] = true;
        header("Location: ../dashboard.php");
        exit;
    }
}
$stmt_admin->close();

// 2. CEK USER PETUGAS (Validasi Hak Akses Casemix)
$sql_user = "SELECT 
                AES_DECRYPT(id_user, 'nur') as id_user, 
                AES_DECRYPT(password, 'windi') as password,
                inacbg_klaim_baru_manual,
                inacbg_klaim_baru_manual2,
                inacbg_klaim_baru_otomatis
            FROM user 
            WHERE AES_DECRYPT(id_user, 'nur') = ?";

$stmt_user = $koneksi->prepare($sql_user);
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$res_user = $stmt_user->get_result();

if ($res_user->num_rows === 1) {
    $row = $res_user->fetch_assoc();
    
    if ($row['password'] === $password_input) {
        // Cek Authorization (Salah satu hak akses INACBG harus 'true')
        if ($row['inacbg_klaim_baru_manual'] == 'true' || 
            $row['inacbg_klaim_baru_manual2'] == 'true' || 
            $row['inacbg_klaim_baru_otomatis'] == 'true') {
            
            session_regenerate_id(true);
            $_SESSION['user_id'] = $username;
            $_SESSION['role'] = 'Casemix';
            $_SESSION['is_login'] = true;
            
            header("Location: ../dashboard.php");
            exit;
        } else {
            // Login benar, tapi tidak punya hak akses
            header("Location: ../index.php?error=no_access");
            exit;
        }
    }
}
$stmt_user->close();

// Gagal Login
header("Location: ../index.php?error=invalid");
exit;
?>