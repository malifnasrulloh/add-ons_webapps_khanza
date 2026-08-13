<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../../../conf.php';
require_once '../../../auth_check.php';
require_once '../../bpjs_api_helper.php';

require_login();

if (empty($_SESSION['is_admin'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Hanya Super Admin yang diizinkan mengakses halaman ini.']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$credFile = __DIR__ . '/../../../bpjs_credential.json';

try {
    if ($action === 'get_setting') {
        if (file_exists($credFile)) {
            $data = json_decode(file_get_contents($credFile), true);
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'File bpjs_credential.json tidak ditemukan.']);
        }
        exit;
    }

    if ($action === 'save_setting') {
        validate_csrf();

        $cons_id = trim($_POST['cons_id'] ?? '');
        $secret_key = trim($_POST['secret_key'] ?? '');
        $user_key = trim($_POST['user_key'] ?? '');
        $kode_ppk = trim($_POST['kode_ppk'] ?? '');
        $base_url = trim($_POST['base_url'] ?? 'https://dvlp.bpjs-kesehatan.go.id:9443/api/apotek');

        $data = [
            'cons_id' => $cons_id,
            'secret_key' => $secret_key,
            'user_key' => $user_key,
            'kode_ppk' => $kode_ppk,
            'base_url' => $base_url
        ];

        file_put_contents($credFile, json_encode($data, JSON_PRETTY_PRINT));
        echo json_encode(['status' => 'success', 'message' => 'Pengaturan kredensial BPJS berhasil disimpan.']);
        exit;
    }

    if ($action === 'test_connection') {
        $api = new BpjsApiHelper();
        $res = $api->request('/referensi/dpho', 'GET');

        if ($res['code'] == '200') {
            echo json_encode(['status' => 'success', 'message' => 'Koneksi ke API BPJS Apotek Berhasil!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Koneksi Gagal: ' . ($res['message'] ?? 'Respon tidak valid.')]);
        }
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
