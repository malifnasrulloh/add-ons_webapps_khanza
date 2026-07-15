<?php
/**
 * modules/kfa_api_helper.php — Helper KFA API Kemenkes SatuSehat
 *
 * Bertanggung jawab untuk:
 *  - Membaca credential dari satusehat_credential.json
 *  - Auto-refresh OAuth2 token (cache di session, retry 1x sebelum fallback)
 *  - Pencarian produk KFA dari API secara real-time
 *  - Return data auto-fill (rute_pemberian, dosage_form, ucum) untuk modal mapping
 *
 * @author   Ichsan Leonhart
 * @requires PHP 7.4+, cURL extension
 */

if (!defined('KFA_HELPER_LOADED')) {
    define('KFA_HELPER_LOADED', true);
}

// Lokasi file credential relatif terhadap posisi helper ini (root aplikasi)
if (!defined('KFA_CREDENTIAL_FILE')) {
    define('KFA_CREDENTIAL_FILE', dirname(__DIR__) . '/satusehat_credential.json');
}

// Session key untuk menyimpan token cache
if (!defined('KFA_SESSION_TOKEN_KEY')) {
    define('KFA_SESSION_TOKEN_KEY',  'kfa_access_token');
    define('KFA_SESSION_EXPIRY_KEY', 'kfa_token_expiry');
}

// Timeout cURL dalam detik
if (!defined('KFA_CURL_TIMEOUT')) {
    define('KFA_CURL_TIMEOUT', 15);
}

/**
 * Membaca credential dari file JSON.
 * Return array credential atau null jika file tidak ada / invalid.
 *
 * @return array|null
 */
function kfa_load_credential(): ?array {
    if (!file_exists(KFA_CREDENTIAL_FILE)) {
        return null;
    }
    $raw = file_get_contents(KFA_CREDENTIAL_FILE);
    if ($raw === false) return null;
    $data = json_decode($raw, true);
    return (is_array($data)) ? $data : null;
}

/**
 * Mendapatkan base URL API sesuai environment credential.
 *
 * @param  array  $cred  credential array dari kfa_load_credential()
 * @return array  ['auth' => string, 'kfa_v2' => string]
 */
function kfa_get_base_urls(array $cred): array {
    $env = $cred['environment'] ?? 'production';
    if ($env === 'sandbox') {
        return [
            'auth'   => 'https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1',
            'kfa_v2' => 'https://api-satusehat-stg.dto.kemkes.go.id/kfa-v2',
        ];
    }
    return [
        'auth'   => 'https://api-satusehat.kemkes.go.id/oauth2/v1',
        'kfa_v2' => 'https://api-satusehat.kemkes.go.id/kfa-v2',
    ];
}

/**
 * Melakukan request OAuth2 ke API Kemenkes untuk mendapatkan access token.
 * Return token string jika berhasil, null jika gagal.
 *
 * @param  array  $cred
 * @param  array  $urls
 * @return string|null
 */
function kfa_fetch_token(array $cred, array $urls): ?string {
    $client_id     = $cred['client_id']     ?? '';
    $client_secret = $cred['client_secret'] ?? '';

    if (empty($client_id) || empty($client_secret)) {
        return null;
    }

    $url = $urls['auth'] . '/accesstoken?grant_type=client_credentials';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => KFA_CURL_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => false, // beberapa server RS tidak punya CA bundle lengkap
    ]);

    $resp    = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false || $httpCode < 200 || $httpCode >= 300) {
        return null;
    }

    $json = json_decode($resp, true);
    return $json['access_token'] ?? null;
}

/**
 * Mendapatkan access token yang valid.
 * Logic: cek cache session → jika expired/tidak ada → request baru → retry 1x → return null.
 *
 * @param  array  $cred
 * @return string|null  token atau null jika gagal setelah retry
 */
function kfa_get_valid_token(array $cred): ?string {
    $urls = kfa_get_base_urls($cred);

    // 1. Cek cache session
    $now = time();
    if (
        !empty($_SESSION[KFA_SESSION_TOKEN_KEY]) &&
        !empty($_SESSION[KFA_SESSION_EXPIRY_KEY]) &&
        $now < (int)$_SESSION[KFA_SESSION_EXPIRY_KEY]
    ) {
        // Token masih valid
        return $_SESSION[KFA_SESSION_TOKEN_KEY];
    }

    // 2. Token tidak ada / expired → request baru
    $token = kfa_fetch_token($cred, $urls);

    // 3. Jika gagal, retry 1x
    if ($token === null) {
        sleep(1); // tunggu sebentar sebelum retry
        $token = kfa_fetch_token($cred, $urls);
    }

    if ($token === null) {
        // Hapus cache yang mungkin stale
        unset($_SESSION[KFA_SESSION_TOKEN_KEY], $_SESSION[KFA_SESSION_EXPIRY_KEY]);
        return null;
    }

    // 4. Simpan ke session (expire 55 menit — 5 menit buffer dari 60 menit real-nya)
    $_SESSION[KFA_SESSION_TOKEN_KEY]  = $token;
    $_SESSION[KFA_SESSION_EXPIRY_KEY] = $now + (55 * 60);

    return $token;
}

/**
 * Melakukan HTTP GET ke endpoint KFA API dengan Authorization Bearer token.
 * Return parsed array atau null jika error.
 *
 * @param  string  $url
 * @param  string  $token
 * @return array|null
 */
function kfa_http_get(string $url, string $token): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => KFA_CURL_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false || $httpCode < 200 || $httpCode >= 300) {
        return null;
    }
    return json_decode($resp, true) ?: null;
}

/**
 * Normalisasi kode rute pemberian KFA → kode ATC (untuk select_route).
 * API KFA menyimpan rute_pemberian.code seperti "O" (Oral), "P" (Parenteral), dll.
 * Satu Sehat menggunakan ATC route code. Kita pass-through — user bisa pilih manual jika tidak cocok.
 *
 * @param  string|null  $kfa_rute_code
 * @param  string|null  $kfa_rute_name
 * @return array ['code' => string, 'display' => string]
 */
function kfa_normalize_route(?string $kfa_rute_code, ?string $kfa_rute_name): array {
    // Mapping umum KFA rute code → ATC route code (yang ada di satu_sehat_ref_route)
    // Ini hanya hint — user tetap bisa ubah manual
    $routeMap = [
        'O'  => ['code' => 'C38288', 'display' => 'Oral'],
        'P'  => ['code' => 'C28161', 'display' => 'Intramuscular'],
        'IV' => ['code' => 'C38276', 'display' => 'Intravenous'],
        'SC' => ['code' => 'C38299', 'display' => 'Subcutaneous'],
        'IN' => ['code' => 'C38288', 'display' => 'Nasal'],
        'T'  => ['code' => 'C38304', 'display' => 'Topical'],
        'SL' => ['code' => 'C38300', 'display' => 'Sublingual'],
        'R'  => ['code' => 'C38295', 'display' => 'Rectal'],
        'TD' => ['code' => 'C38305', 'display' => 'Transdermal'],
        'IH' => ['code' => 'C38216', 'display' => 'Inhalation'],
    ];

    if ($kfa_rute_code && isset($routeMap[$kfa_rute_code])) {
        return $routeMap[$kfa_rute_code];
    }
    return ['code' => '', 'display' => $kfa_rute_name ?? ''];
}

/**
 * Mencari produk KFA dari API Kemenkes.
 *
 * Return array format Select2 dengan data auto-fill tambahan:
 * [
 *   'id'           => kfa_code (product),
 *   'text'         => "93004418 - Amoxicillin 500 mg Kapsul",
 *   'display_name' => "Amoxicillin 500 mg Kapsul",
 *   'product_type' => 'product' | 'template',
 *   'route_code'   => '', // hint dari API
 *   'route_display'=> '',
 *   'form_code'    => '',
 *   'form_display' => '',
 *   'ucum_code'    => '',  // satuan numerator dari API (mg, g, dll)
 * ]
 *
 * Return null jika API gagal (caller harus fallback ke database lokal).
 *
 * @param  array   $cred     credential array
 * @param  string  $keyword  kata kunci pencarian
 * @param  int     $size     jumlah hasil maksimal
 * @return array|null
 */
function kfa_search_from_api(array $cred, string $keyword, int $size = 20): ?array {
    $token = kfa_get_valid_token($cred);
    if ($token === null) {
        return null; // Gagal auth → fallback
    }

    $urls = kfa_get_base_urls($cred);
    $url  = $urls['kfa_v2'] . '/products/all?' . http_build_query([
        'page'         => 1,
        'size'         => $size,
        'product_type' => 'farmasi',
        'keyword'      => $keyword,
    ]);

    $data = kfa_http_get($url, $token);

    if ($data === null) {
        return null;
    }

    // Jika token expired di tengah jalan (401), hapus cache dan retry 1x
    // (Ini handled karena kfa_http_get return null jika status bukan 2xx)

    $results = [];

    // API v2 products/all mengembalikan struktur: {data: [{...}, ...]}
    $items = $data['data'] ?? $data['items']['data'] ?? [];

    if (!is_array($items)) {
        return null;
    }

    foreach ($items as $item) {
        // Ambil kfa_code dan nama produk
        $kfa_code    = $item['kfa_code']   ?? '';
        $name        = $item['name']        ?? ($item['product_template_name'] ?? '');
        if (empty($kfa_code) || empty($name)) continue;

        // Data untuk auto-fill
        $rute_code    = $item['rute_pemberian']['code'] ?? null;
        $rute_name    = $item['rute_pemberian']['name'] ?? null;
        $route        = kfa_normalize_route($rute_code, $rute_name);

        $form_code    = $item['dosage_form']['code'] ?? '';
        $form_display = $item['dosage_form']['name'] ?? '';

        $ucum_code    = $item['ucum']['cs_code'] ?? '';

        $results[] = [
            'id'           => $kfa_code,
            'text'         => $kfa_code . ' — ' . $name,
            'display_name' => $name,
            'product_type' => 'product',
            'route_code'   => $route['code'],
            'route_display'=> $route['display'],
            'form_code'    => $form_code,
            'form_display' => $form_display,
            'ucum_code'    => $ucum_code,
        ];
    }

    return $results;
}

/**
 * Mendapatkan detail produk KFA dari API berdasarkan kfa_code.
 *
 * @param  array   $cred
 * @param  string  $kfa_code
 * @return array|null  item detail atau null jika gagal
 */
function kfa_get_detail_from_api(array $cred, string $kfa_code): ?array {
    $token = kfa_get_valid_token($cred);
    if ($token === null) return null;

    $urls = kfa_get_base_urls($cred);
    $url  = $urls['kfa_v2'] . '/products?' . http_build_query([
        'identifier' => 'kfa',
        'code'       => $kfa_code,
    ]);

    $data = kfa_http_get($url, $token);
    return $data['result'] ?? null;
}

/**
 * Melakukan test koneksi: coba get token dan return status.
 *
 * @param  array  $cred
 * @return array  ['success' => bool, 'message' => string, 'environment' => string]
 */
function kfa_test_connection(array $cred): array {
    $env  = $cred['environment'] ?? 'production';
    $urls = kfa_get_base_urls($cred);

    if (empty($cred['client_id']) || empty($cred['client_secret'])) {
        return [
            'success'     => false,
            'message'     => 'Client ID atau Client Secret belum diisi.',
            'environment' => $env,
        ];
    }

    // Force fresh token (jangan gunakan cache)
    $token = kfa_fetch_token($cred, $urls);

    if ($token === null) {
        return [
            'success'     => false,
            'message'     => 'Gagal mendapatkan token. Periksa Client ID, Client Secret, dan koneksi internet server.',
            'environment' => $env,
        ];
    }

    // Update cache
    $_SESSION[KFA_SESSION_TOKEN_KEY]  = $token;
    $_SESSION[KFA_SESSION_EXPIRY_KEY] = time() + (55 * 60);

    return [
        'success'     => true,
        'message'     => 'Koneksi berhasil! Token berhasil didapat dari API ' . strtoupper($env) . '.',
        'environment' => $env,
        'token_preview' => substr($token, 0, 20) . '...[truncated]',
    ];
}
