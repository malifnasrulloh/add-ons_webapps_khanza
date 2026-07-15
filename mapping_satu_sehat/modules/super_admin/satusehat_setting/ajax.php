<?php
/**
 * modules/super_admin/satusehat_setting/ajax.php
 * Backend handler untuk halaman Setting Credential SatuSehat.
 * Hanya Super Admin yang boleh akses.
 */
error_reporting(0);
ini_set('display_errors', 0);

require_once '../../../conf.php';
require_once '../../../auth_check.php';

header('Content-Type: application/json');

// Guard: hanya super admin
if (empty($_SESSION['is_admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Hanya Super Admin.']);
    exit;
}

require_once dirname(__DIR__) . '/../kfa_api_helper.php';

$action = $_GET['action'] ?? '';

// ============================================================
// 1. LOAD — Baca credential (client_secret di-mask)
// ============================================================
if ($action === 'load') {
    $cred = kfa_load_credential();
    if ($cred === null) {
        // Buat file default jika belum ada
        $cred = [
            'organization_id' => '',
            'client_id'       => '',
            'client_secret'   => '',
            'loinc_username'  => '',
            'loinc_password'  => '',
            'environment'     => 'production',
            'kfa_search_mode' => 'database',
            'updated_at'      => '',
            'updated_by'      => '',
        ];
    }

    // Mask client_secret: tampilkan hanya 6 karakter pertama + ***
    $masked = $cred;
    if (!empty($cred['client_secret'])) {
        $len = strlen($cred['client_secret']);
        $masked['client_secret_masked'] = substr($cred['client_secret'], 0, 6) . str_repeat('*', min($len - 6, 30));
        $masked['client_secret_set']    = true;
    } else {
        $masked['client_secret_masked'] = '';
        $masked['client_secret_set']    = false;
    }
    unset($masked['client_secret']); // jangan kirim secret ke frontend

    // Mask LOINC password
    $masked['loinc_username'] = $cred['loinc_username'] ?? '';
    if (!empty($cred['loinc_password'])) {
        $masked['loinc_password_masked'] = str_repeat('*', 8);
        $masked['loinc_password_set']    = true;
    } else {
        $masked['loinc_password_masked'] = '';
        $masked['loinc_password_set']    = false;
    }
    unset($masked['loinc_password']);

    echo json_encode(['status' => 'success', 'data' => $masked]);
    exit;
}

// ============================================================
// 2. SAVE — Simpan credential ke JSON
// ============================================================
if ($action === 'save') {
    validate_csrf();

    $org_id        = trim($_POST['organization_id'] ?? '');
    $client_id     = trim($_POST['client_id']       ?? '');
    $client_secret = trim($_POST['client_secret']   ?? ''); // kosong = tidak mengganti
    $environment   = trim($_POST['environment']     ?? 'production');
    $search_mode   = trim($_POST['kfa_search_mode'] ?? 'database');

    $loinc_username = trim($_POST['loinc_username'] ?? '');
    $loinc_password = trim($_POST['loinc_password'] ?? '');

    if (!in_array($environment, ['production', 'sandbox'])) $environment = 'production';
    if (!in_array($search_mode, ['api', 'database']))       $search_mode = 'database';

    // Baca existing untuk mempertahankan client_secret lama jika tidak diisi
    $existing = kfa_load_credential() ?? [];

    $newCred = [
        'organization_id' => $org_id,
        'client_id'       => $client_id,
        'client_secret'   => !empty($client_secret) ? $client_secret : ($existing['client_secret'] ?? ''),
        'loinc_username'  => $loinc_username,
        'loinc_password'  => !empty($loinc_password) ? $loinc_password : ($existing['loinc_password'] ?? ''),
        'environment'     => $environment,
        'kfa_search_mode' => $search_mode,
        'updated_at'      => date('Y-m-d H:i:s'),
        'updated_by'      => $_SESSION['user_name'] ?? 'admin',
    ];

    $written = file_put_contents(
        KFA_CREDENTIAL_FILE,
        json_encode($newCred, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    if ($written === false) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menulis file kredensial. Pastikan folder dapat ditulis (writable).']);
        exit;
    }

    // Hapus cache token di session agar token baru di-fetch dengan credential baru
    unset($_SESSION[KFA_SESSION_TOKEN_KEY], $_SESSION[KFA_SESSION_EXPIRY_KEY]);

    echo json_encode(['status' => 'success', 'message' => 'Credential berhasil disimpan.']);
    exit;
}

// ============================================================
// 3. TEST CONNECTION — Coba get token dan return status
// ============================================================
if ($action === 'test_connection') {
    validate_csrf();

    // Ambil credential dari POST (belum disimpan) agar bisa test sebelum save
    $org_id        = trim($_POST['organization_id'] ?? '');
    $client_id     = trim($_POST['client_id']       ?? '');
    $client_secret = trim($_POST['client_secret']   ?? '');
    $environment   = trim($_POST['environment']     ?? 'production');

    // Jika client_secret kosong, ambil dari file yang tersimpan
    if (empty($client_secret)) {
        $existing = kfa_load_credential();
        $client_secret = $existing['client_secret'] ?? '';
    }

    $testCred = [
        'organization_id' => $org_id,
        'client_id'       => $client_id,
        'client_secret'   => $client_secret,
        'environment'     => $environment,
    ];

    $result = kfa_test_connection($testCred);

    // Jika berhasil, update cache token
    echo json_encode([
        'status'  => $result['success'] ? 'success' : 'error',
        'message' => $result['message'],
        'environment' => $result['environment'],
        'token_preview' => $result['token_preview'] ?? null,
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenal.']);
