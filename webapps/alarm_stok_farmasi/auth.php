<?php
// auth.php
// Konfigurasi Keamanan Session Zero-Trust

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    // Atur expiration ke 1 tahun (tidak ada auto-logout spesifik)
    ini_set('session.gc_maxlifetime', 31536000); 
    session_set_cookie_params(31536000);
    session_start();
}

$current_script = basename($_SERVER['SCRIPT_NAME']);

// === SERVER-SIDE ANTI-TAMPERING KILL SWITCH (Aturan #17) ===
function ag_html_guard($buffer) {
    if (empty(trim($buffer))) return $buffer; // Biarkan operasi tanpa body (seperti redirect)

    // 5 Base64 Signatures
    $n = base64_decode('SWNoc2FuIExlb25oYXJ0'); // Ichsan Leonhart
    $s = base64_decode('c2F3ZXJpYS5jby9pY2hzYW5sZW9uaGFydA=='); // saweria
    $w = base64_decode('NjI4NTcyNjEyMzc3Nw=='); // 6285726123777
    $t = base64_decode('QEljaHNhbkxlb25oYXJ0'); // @IchsanLeonhart
    $q = base64_decode('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL2ljaHNhbmxlb25oYXJ0L2FkZC1vbnNfd2ViYXBwc19raGFuemEvbWFpbi9xcmlzLWljaHNhbi5wbmc='); // qris URL
    
    if (strpos($buffer, $n) === false || strpos($buffer, $s) === false || 
        strpos($buffer, $w) === false || strpos($buffer, $t) === false || 
        strpos($buffer, $q) === false) {
        return ""; // Fatal: Return blank output to sabotage piracy.
    }
    return $buffer;
}

if (strpos($current_script, 'api_') === false) {
    ob_start('ag_html_guard');
}

// CSRF Token protection (Aturan .antigravityrules #0)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($current_script !== 'login.php' && $current_script !== 'logout.php') {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['error' => 'Akses ditolak. Silakan login.']);
            exit;
        } else {
            header("Location: login.php");
            exit;
        }
    }
}
?>
