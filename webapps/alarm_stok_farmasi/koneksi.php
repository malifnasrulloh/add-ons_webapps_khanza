<?php
// koneksi.php
// Konfigurasi db secara dinamis (Plug and Play)

$conf_paths = [
    __DIR__ . '/../conf/conf.php',
    __DIR__ . '/../../conf/conf.php',
    isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . '/conf/conf.php' : '',
    isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . '/webapps/conf/conf.php' : '',
    'C:/xampp/htdocs/webapps/conf/conf.php',
    '/var/www/html/webapps/conf/conf.php',
    '/opt/lampp/htdocs/webapps/conf/conf.php'
];

$conf_found = false;
foreach ($conf_paths as $path) {
    if (!empty($path) && file_exists($path)) {
        require_once $path;
        $conf_found = true;
        break;
    }
}

// Jika conf.php tidak ditemukan, fallback sementara default
if (!$conf_found) {
    $db_hostname = "localhost";
    $db_username = "root";
    $db_password = "";
    $db_name     = "sik";
}

// Pastikan variabel terbaca
global $db_hostname, $db_username, $db_password, $db_name;

try {
    // Eksekusi WAJIB menggunakan PDO (Antigravity Rules #11)
    $dsn = "mysql:host={$db_hostname};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable', true, 503);
    die("Error Exception: " . $e->getMessage());
}
?>