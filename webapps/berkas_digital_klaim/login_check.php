<?php
/*
 * File: /webapps/berkas_digital_perawatan/login_check.php
 * Fungsi: Memvalidasi kredensial user ke database Khanza (SECURED Version)
 */
require_once('csrf.php'); // Melakukan session_start dan otomatis validasi POST CSRF
require_once('../conf/conf.php');

$koneksi = bukakoneksi();

// 1. Ambil Input
// Username tidak perlu disanitasi dengan validTeks4 lagi karena kita pakai Prepared Statements
$username = isset($_POST['username']) ? $_POST['username'] : '';

// Password kita ambil RAW (mentah) untuk pencocokan PHP (agar karakter unik tidak hilang)
$password_raw = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($username) || empty($password_raw)) {
    header("Location: index.php?pesan=gagal");
    exit;
}

// ============================================================================
// 1. CEK SUPER ADMIN (Tabel: admin)
// ============================================================================
// Gunakan Prepared Statement untuk mencegah SQL Injection
$q_admin = "SELECT 
                CAST(AES_DECRYPT(passworde, 'windi') AS CHAR) as passworde 
            FROM admin 
            WHERE CAST(AES_DECRYPT(usere, 'nur') AS CHAR) = ? LIMIT 1";

$stmt_admin = mysqli_prepare($koneksi, $q_admin);
if ($stmt_admin) {
    mysqli_stmt_bind_param($stmt_admin, "s", $username);
    mysqli_stmt_execute($stmt_admin);
    $r_admin = mysqli_stmt_get_result($stmt_admin);

    if ($r_admin && mysqli_num_rows($r_admin) > 0) {
        $row = mysqli_fetch_assoc($r_admin);
        
        // Bandingkan password dari DB dengan Input Mentah User
        if ($row['passworde'] === $password_raw) {
            // Login Berhasil Sebagai Super Admin
            session_regenerate_id(true); // Security: Cegah Session Fixation
            $_SESSION['casemix_login'] = true;
            $_SESSION['casemix_user']  = $username;
            $_SESSION['casemix_role']  = 'Super Admin';
            
            header("Location: dashboard.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt_admin);
}

// ============================================================================
// 2. CEK USER PEGAWAI (Tabel: user)
// ============================================================================
$q_user = "SELECT 
                CAST(AES_DECRYPT(password, 'windi') AS CHAR) as password,
                inacbg_klaim_baru_manual, 
                inacbg_klaim_baru_manual2, 
                inacbg_klaim_baru_otomatis
            FROM user 
            WHERE CAST(AES_DECRYPT(id_user, 'nur') AS CHAR) = ? LIMIT 1";

$stmt_user = mysqli_prepare($koneksi, $q_user);
if ($stmt_user) {
    mysqli_stmt_bind_param($stmt_user, "s", $username);
    mysqli_stmt_execute($stmt_user);
    $r_user = mysqli_stmt_get_result($stmt_user);

    if ($r_user && mysqli_num_rows($r_user) > 0) {
        $row = mysqli_fetch_assoc($r_user);
        
        // Bandingkan password
        if ($row['password'] === $password_raw) {
            // Cek Hak Akses Casemix (Salah satu harus true)
            if ($row['inacbg_klaim_baru_manual'] == 'true' || 
                $row['inacbg_klaim_baru_manual2'] == 'true' || 
                $row['inacbg_klaim_baru_otomatis'] == 'true') {
                
                // Login Berhasil Sebagai Petugas
                session_regenerate_id(true);
                $_SESSION['casemix_login'] = true;
                $_SESSION['casemix_user']  = $username;
                $_SESSION['casemix_role']  = 'Petugas Casemix';
                
                header("Location: dashboard.php");
                exit;
            } else {
                // Password benar, tapi tidak punya hak akses
                header("Location: index.php?pesan=noaccess");
                exit;
            }
        }
    }
    mysqli_stmt_close($stmt_user);
}

// Jika sampai sini, berarti Gagal (Username tidak ada atau Password salah)
header("Location: index.php?pesan=gagal");
mysqli_close($koneksi);
?>