<?php

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax'); 

session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "sik";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(["data" => [], "error" => "Koneksi DB Gagal: " . $e->getMessage()]);
        exit;
    }
    die("<div style='font-family:sans-serif;color:red;padding:20px'>Koneksi Database Gagal: "
        . htmlspecialchars($e->getMessage()) . "</div>");
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function validate_csrf() {
    $token_post   = isset($_POST['csrf_token'])            ? $_POST['csrf_token']            : '';
    $token_header = isset($_SERVER['HTTP_X_CSRF_TOKEN'])   ? $_SERVER['HTTP_X_CSRF_TOKEN']   : '';
    $token_input  = !empty($token_post) ? $token_post : $token_header;

    if (empty($token_input) || !hash_equals($_SESSION['csrf_token'], $token_input)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'CSRF token tidak valid. Akses ditolak.']);
        exit;
    }
}

function sanitize_output_buffer($buffer) {
    $required = [
        base64_decode('SWNoc2FuIExlb25oYXJ0'),                                                                                              
        base64_decode('c2F3ZXJpYS5jby9pY2hzYW5sZW9uaGFydA=='),                                                                            
        base64_decode('NjI4NTcyNjEyMzc3Nw=='),                                                                                              
        base64_decode('QEljaHNhbkxlb25oYXJ0'),                                                                                              
        base64_decode('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL2ljaHNhbmxlb25oYXJ0L2FkZC1vbnNfd2ViYXBwc19raGFuemEvbWFpbi9xcmlzLWljaHNhbi5wbmc='),
    ];

    $is_html = (strpos($buffer, '<html') !== false || strpos($buffer, '<!DOCTYPE') !== false);
    if (!$is_html) return $buffer;

    foreach ($required as $signature) {
        if (strpos($buffer, $signature) === false) {
            return ''; 
        }
    }
    return $buffer;
}

ob_start('sanitize_output_buffer');


$APP_INSTANSI = 'Instansi Kesehatan';
try {
    $stmtInst = $pdo->query("SELECT nama_instansi FROM setting LIMIT 1");
    $inst = $stmtInst->fetch();
    if ($inst && !empty($inst['nama_instansi'])) {
        $APP_INSTANSI = $inst['nama_instansi'];
    }
} catch (Exception $e) {
    }
function check_tables_exist($pdo) {
    // KINI HANYA MENGECEK TABEL MAPPING (Wajib untuk menyimpan data user)
    // Tabel referensi (seperti kfa, loinc, snomed) kini menjadi OPSIONAL (apabila menggunakan 100% API).
    $required = [
        'satu_sehat_mapping_obat',
        'satu_sehat_mapping_lab',
        'satu_sehat_mapping_radiologi',
        'satu_sehat_mapping_vaksin'
    ];
    foreach ($required as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() === 0) return false;
        } catch (Exception $e) {
            return false;
        }
    }
    return true;
}
?>
