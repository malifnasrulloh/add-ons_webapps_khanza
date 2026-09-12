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
            'kfa_v3' => 'https://api-satusehat-stg.dto.kemkes.go.id/kfa-v3',
        ];
    }
    return [
        'auth'   => 'https://api-satusehat.kemkes.go.id/oauth2/v1',
        'kfa_v2' => 'https://api-satusehat.kemkes.go.id/kfa-v2',
        'kfa_v3' => 'https://api-satusehat.kemkes.go.id/kfa-v3',
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
    $postData = http_build_query([
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
    ]);

    if (extension_loaded('curl') && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
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
        $json = json_decode($resp, true);
        return $json['access_token'] ?? null;
    } else {
        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $postData,
                'timeout' => KFA_CURL_TIMEOUT,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false
            ]
        ];
        $context = stream_context_create($opts);
        $resp = @file_get_contents($url, false, $context);
        if ($resp === false) return null;

        if (isset($http_response_header[0])) {
            preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $m);
            $code = isset($m[1]) ? (int)$m[1] : 500;
            if ($code < 200 || $code >= 300) return null;
        }

        $json = json_decode($resp, true);
        return $json['access_token'] ?? null;
    }
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
    if (extension_loaded('curl') && function_exists('curl_init')) {
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
    } else {
        $opts = [
            'http' => [
                'method'  => 'GET',
                'header'  => "Authorization: Bearer {$token}\r\nAccept: application/json\r\n",
                'timeout' => KFA_CURL_TIMEOUT,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false
            ]
        ];
        $context = stream_context_create($opts);
        $resp = @file_get_contents($url, false, $context);
        if ($resp === false) return null;

        if (isset($http_response_header[0])) {
            preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $m);
            $code = isset($m[1]) ? (int)$m[1] : 500;
            if ($code < 200 || $code >= 300) return null;
        }

        return json_decode($resp, true) ?: null;
    }
}

/**
 * Melakukan HTTP POST dengan JSON payload ke KFA API (terutama endpoint KFA v3).
 *
 * @param  string  $url
 * @param  string  $token
 * @param  array   $payload
 * @return array|null
 */
function kfa_http_post_json(string $url, string $token, array $payload): ?array {
    $jsonBody = json_encode($payload);

    if (extension_loaded('curl') && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonBody,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
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
    } else {
        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Authorization: Bearer {$token}\r\nContent-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $jsonBody,
                'timeout' => KFA_CURL_TIMEOUT,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false
            ]
        ];
        $context = stream_context_create($opts);
        $resp = @file_get_contents($url, false, $context);
        if ($resp === false) return null;

        if (isset($http_response_header[0])) {
            preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $m);
            $code = isset($m[1]) ? (int)$m[1] : 500;
            if ($code < 200 || $code >= 300) return null;
        }

        return json_decode($resp, true) ?: null;
    }
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
    // Mapping kode rute KFA → kode ATC yang tersimpan di satu_sehat_ref_route
    $routeMap = [
        'O'  => ['code' => 'O', 'display' => 'Oral'],
        'P'  => ['code' => 'P', 'display' => 'Parenteral'],
        'IV' => ['code' => 'inj.intravenous', 'display' => 'Injection Intravenous'],
        'IM' => ['code' => 'inj.intramuscular', 'display' => 'Injection Intramuscular'],
        'SC' => ['code' => 'inj.subcutaneous', 'display' => 'Injection Subcutaneous'],
        'IN' => ['code' => 'N', 'display' => 'Nasal'],
        'T'  => ['code' => 'Topical', 'display' => 'Topikal (Oles/Kulit/Mata/Telinga)'],
        'SL' => ['code' => 'SL', 'display' => 'Sublingual/Buccal/Oro mucosal'],
        'R'  => ['code' => 'R', 'display' => 'Rectal'],
        'TD' => ['code' => 'TD', 'display' => 'Transdermal'],
        'IH' => ['code' => 'Inhal', 'display' => 'Inhalation'],
        'V'  => ['code' => 'V', 'display' => 'Vaginal'],
    ];

    if ($kfa_rute_code && isset($routeMap[$kfa_rute_code])) {
        return $routeMap[$kfa_rute_code];
    }
    return ['code' => $kfa_rute_code ?? '', 'display' => $kfa_rute_name ?? ''];
}

/**
 * Melakukan parsing cerdas dari payload detail KFA API untuk auto-fill:
 * - Route (kode & display yang cocok dengan satu_sehat_ref_route)
 * - Dosage Form (form_code & form_display)
 * - Numerator code (satuan UCUM kekuatan, e.g. mg, g, ug, [IU])
 * - Denominator code (satuan penyajian: mL, TAB, CAP, g, dll)
 * - Vaccine info (is_vaccine, dose_quantity_code, dose_quantity_unit)
 *
 * @param  array $item
 * @return array
 */
function kfa_parse_drug_details(array $item): array {
    $rute_code    = $item['rute_pemberian']['code'] ?? null;
    $rute_name    = $item['rute_pemberian']['name'] ?? null;
    $route        = kfa_normalize_route($rute_code, $rute_name);

    $form_code    = $item['dosage_form']['code'] ?? '';
    $form_display = $item['dosage_form']['name'] ?? '';

    // Active ingredient strength parsing
    $strength_str = '';
    if (!empty($item['active_ingredients'][0]['kekuatan_zat_aktif'])) {
        $strength_str = trim($item['active_ingredients'][0]['kekuatan_zat_aktif']);
    }
    $product_name = $item['name'] ?? ($item['nama_dagang'] ?? '');

    $num_code = '';
    $den_code = '';

    // Regex parse strength string, e.g. "100 mg/1 mL", "500 mg", "10 ug/0.5 mL", "20 mcg/mL"
    if (!empty($strength_str)) {
        if (preg_match('/^[\d\.,\s]*([a-zA-Z%\[\]]+)(?:\s*\/\s*[\d\.,\s]*([a-zA-Z%\[\]]+))?/i', $strength_str, $matches)) {
            $raw_num = strtolower(trim($matches[1] ?? ''));
            $raw_den = strtolower(trim($matches[2] ?? ''));

            // Normalize numerator
            if ($raw_num === 'mcg') $raw_num = 'ug';
            if ($raw_num === 'iu')  $raw_num = '[IU]';
            if ($raw_num === 'mg' || $raw_num === 'g' || $raw_num === 'ug' || $raw_num === '[iu]' || $raw_num === 'ml') {
                $num_code = ($raw_num === '[iu]') ? '[IU]' : (($raw_num === 'ml') ? 'mL' : $raw_num);
            }

            // Normalize denominator
            if (!empty($raw_den)) {
                if ($raw_den === 'ml') $den_code = 'mL';
                elseif ($raw_den === 'l') $den_code = 'L';
                elseif ($raw_den === 'g') $den_code = 'g';
                else $den_code = $raw_den;
            }
        }
    }

    // If denominator is still empty, infer from dosage form and name
    if (empty($den_code)) {
        $check_text = strtolower($form_display . ' ' . $product_name);
        if (strpos($check_text, 'tablet') !== false || strpos($check_text, 'kaplet') !== false) {
            $den_code = 'TAB';
        } elseif (strpos($check_text, 'kapsul') !== false || strpos($check_text, 'capsule') !== false) {
            $den_code = 'CAP';
        } elseif (strpos($check_text, 'tetes') !== false || strpos($check_text, 'drop') !== false) {
            $den_code = 'mL';
        } elseif (strpos($check_text, 'sirup') !== false || strpos($check_text, 'syrup') !== false || strpos($check_text, 'suspensi') !== false || strpos($check_text, 'injeksi') !== false || strpos($check_text, 'larutan') !== false || strpos($check_text, 'cair') !== false) {
            $den_code = 'mL';
        } elseif (strpos($check_text, 'salep') !== false || strpos($check_text, 'krim') !== false || strpos($check_text, 'cream') !== false || strpos($check_text, 'gel') !== false || strpos($check_text, 'ointment') !== false) {
            $den_code = 'g';
        } elseif (strpos($check_text, 'supp') !== false) {
            $den_code = 'SUPP';
        } else {
            $den_code = 'TAB'; // Default solid
        }
    }

    // Fallback numerator from ucum if still empty
    if (empty($num_code) && !empty($item['ucum']['cs_code'])) {
        $num_code = $item['ucum']['cs_code'];
    }

    // Fallback route if route is empty
    if (empty($route['code'])) {
        $check_text = strtolower($form_display . ' ' . $product_name);
        if (strpos($check_text, 'oral') !== false || strpos($check_text, 'tablet') !== false || strpos($check_text, 'kapsul') !== false || strpos($check_text, 'sirup') !== false) {
            $route = ['code' => 'O', 'display' => 'Oral'];
        } elseif (strpos($check_text, 'injeksi') !== false || strpos($check_text, 'infus') !== false || strpos($check_text, 'injection') !== false) {
            $route = ['code' => 'P', 'display' => 'Parenteral'];
        } elseif (strpos($check_text, 'salep') !== false || strpos($check_text, 'krim') !== false || strpos($check_text, 'tetes mata') !== false || strpos($check_text, 'tetes telinga') !== false) {
            $route = ['code' => 'Topical', 'display' => 'Topikal (Oles/Kulit/Mata/Telinga)'];
        } elseif (strpos($check_text, 'tetes hidung') !== false || strpos($check_text, 'nasal') !== false) {
            $route = ['code' => 'N', 'display' => 'Nasal'];
        } elseif (strpos($check_text, 'inhal') !== false) {
            $route = ['code' => 'Inhal', 'display' => 'Inhalation'];
        } elseif (strpos($check_text, 'supp') !== false || strpos($check_text, 'rectal') !== false) {
            $route = ['code' => 'R', 'display' => 'Rectal'];
        }
    }

    // Vaccine detection & dose quantity recommendation (1A + 1B)
    $farmalkes_code = $item['farmalkes_type']['code'] ?? ($item['farmalkes_type'] ?? '');
    $is_vaccine = ($farmalkes_code === 'vaccine') || (stripos($product_name, 'vaksin') !== false) || (stripos($product_name, 'vaccine') !== false);

    $dose_qty_code = 'mL';
    $dose_qty_unit = 'mL';
    if ($is_vaccine) {
        $check_name = strtolower($product_name);
        if (strpos($check_name, 'drop') !== false || strpos($check_name, 'tetes') !== false || strpos($check_name, 'polio oral') !== false || strpos($check_name, 'bopv') !== false) {
            $dose_qty_code = '[drp]';
            $dose_qty_unit = 'Tetes';
        } elseif (!empty($den_code) && in_array(strtolower($den_code), ['ml', 'l', 'mg'])) {
            $dose_qty_code = $den_code;
            $dose_qty_unit = $den_code;
        } elseif (!empty($item['volume_uom_name']) && in_array(strtolower($item['volume_uom_name']), ['ml', 'l', 'mg'])) {
            $dose_qty_code = $item['volume_uom_name'];
            $dose_qty_unit = $item['volume_uom_name'];
        }
    }

    return [
        'form_code'          => $form_code,
        'form_display'       => $form_display,
        'route_code'         => $route['code'],
        'route_display'      => $route['display'],
        'numerator_code'     => $num_code,
        'denominator_code'   => $den_code,
        'is_vaccine'         => $is_vaccine,
        'dose_quantity_code' => $dose_qty_code,
        'dose_quantity_unit' => $dose_qty_unit,
    ];
}

/**
 * Melakukan parsing cerdas data Alkes (KFA v3) untuk mengisi:
 * - Bentuk sediaan (form_code, form_display, form_system): Kemenkes Form untuk cairan/gel/plester, atau KFA Template
 * - Rute pemberian (route_code & route_display): Parenteral, Topikal, Urethral, Inhalasi
 * - Satuan Numerator & Denominator: PCS, SET, ROL, mL, dll.
 *
 * @param  string $display_name
 * @param  string $tmpl_code
 * @param  string $tmpl_name
 * @param  string $uom_name
 * @return array
 */
function kfa_parse_alkes_details(string $display_name, string $tmpl_code = '', string $tmpl_name = '', string $uom_name = 'Pcs'): array {
    $text = strtolower($display_name . ' ' . $tmpl_name);

    // 1. Bentuk Sediaan (Form) — 1C: Context-sensitive
    $form_code    = !empty($tmpl_code) ? $tmpl_code : 'ALKES';
    $form_display = !empty($tmpl_name) ? $tmpl_name : $display_name;
    $form_system  = 'http://sys-ids.kemkes.go.id/kfa';

    if (strpos($text, 'cairan steril') !== false || strpos($text, 'water for injection') !== false || strpos($text, 'aquades') !== false || strpos($text, 'wfi') !== false) {
        $form_code = 'BS063'; $form_display = 'Cairan Steril'; $form_system = 'http://terminology.kemkes.go.id/CodeSystem/medication-form';
    } elseif (strpos($text, 'larutan') !== false || strpos($text, 'alkohol') !== false || strpos($text, 'alcohol') !== false || strpos($text, 'antiseptik') !== false || strpos($text, 'povidone') !== false || strpos($text, 'betadine') !== false || strpos($text, 'rivanol') !== false) {
        $form_code = 'BS032'; $form_display = 'Larutan'; $form_system = 'http://terminology.kemkes.go.id/CodeSystem/medication-form';
    } elseif (strpos($text, 'gel') !== false || strpos($text, 'lubricant') !== false || strpos($text, 'jelly') !== false) {
        $form_code = 'BS013'; $form_display = 'Gel'; $form_system = 'http://terminology.kemkes.go.id/CodeSystem/medication-form';
    } elseif (strpos($text, 'plester') !== false || strpos($text, 'tulle') !== false || strpos($text, 'dressing') !== false) {
        $form_code = 'BS091'; $form_display = 'Tulle/Plester Obat'; $form_system = 'http://terminology.kemkes.go.id/CodeSystem/medication-form';
    } elseif (strpos($text, 'implant') !== false) {
        $form_code = 'BS018'; $form_display = 'Implant'; $form_system = 'http://terminology.kemkes.go.id/CodeSystem/medication-form';
    } elseif (strpos($text, 'iud') !== false || strpos($text, 'intra uterine') !== false) {
        $form_code = 'BS017'; $form_display = 'Intra Uterine Device (IUD)'; $form_system = 'http://terminology.kemkes.go.id/CodeSystem/medication-form';
    } elseif (strpos($text, 'gas') !== false || strpos($text, 'oksigen') !== false || strpos($text, 'oxygen') !== false) {
        $form_code = 'BS012'; $form_display = 'Gas'; $form_system = 'http://terminology.kemkes.go.id/CodeSystem/medication-form';
    }

    // 2. Denominator (Satuan Penyajian) — 2A
    $den_code = 'PCS';
    $uom_lower = strtolower($uom_name . ' ' . $text);
    if (strpos($uom_lower, 'set') !== false) $den_code = 'SET';
    elseif (strpos($uom_lower, 'roll') !== false || strpos($uom_lower, 'rol') !== false) $den_code = 'ROL';
    elseif (strpos($uom_lower, 'botol') !== false || strpos($uom_lower, 'fles') !== false || strpos($uom_lower, 'vial') !== false) $den_code = 'mL';
    elseif (strpos($uom_lower, 'tube') !== false || strpos($uom_lower, 'tub') !== false) $den_code = 'g';
    elseif (strpos($uom_lower, 'lembar') !== false || strpos($uom_lower, 'lbr') !== false || strpos($uom_lower, 'sheet') !== false) $den_code = 'LBR';
    elseif (strpos($uom_lower, 'box') !== false || strpos($uom_lower, 'dus') !== false) $den_code = 'BOX';
    elseif (strpos($uom_lower, 'pad') !== false || strpos($uom_lower, 'kasa') !== false || strpos($uom_lower, 'kassa') !== false) $den_code = 'PAD';

    // 3. Rute Pemberian (Route) — 3A
    $route_code    = 'Topical';
    $route_display = 'Topikal (Oles/Kulit/Mata/Telinga)';
    if (strpos($text, 'infus') !== false || strpos($text, 'infusion') !== false || strpos($text, 'syringe') !== false || strpos($text, 'spuit') !== false || strpos($text, 'jarum') !== false || strpos($text, 'needle') !== false || strpos($text, 'cannula') !== false || strpos($text, 'kanul') !== false || strpos($text, 'catheter iv') !== false || strpos($text, 'iv catheter') !== false || strpos($text, 'abocath') !== false || strpos($text, 'blood set') !== false) {
        $route_code = 'P';
        $route_display = 'Parenteral';
    } elseif (strpos($text, 'foley') !== false || strpos($text, 'catheter urine') !== false || strpos($text, 'urine catheter') !== false || strpos($text, 'kateter') !== false || strpos($text, 'urine bag') !== false) {
        $route_code = 'urethral';
        $route_display = 'Urethral';
    } elseif (strpos($text, 'endotracheal') !== false || strpos($text, 'ett') !== false || strpos($text, 'suction') !== false || strpos($text, 'nasal oxygen') !== false || strpos($text, 'masker oksigen') !== false) {
        $route_code = 'Inhal';
        $route_display = 'Inhalation';
    } elseif (strpos($text, 'ngt') !== false || strpos($text, 'feeding tube') !== false || strpos($text, 'stomach tube') !== false) {
        $route_code = 'O';
        $route_display = 'Oral';
    }

    // 4. Numerator
    $num_code = '1';
    if (preg_match('/\b(\d+(?:\.\d+)?)\s*(ml|mL|l|L|g|mg)\b/', $display_name, $m)) {
        $num_code = strtolower($m[2]) === 'ml' ? 'mL' : (strtolower($m[2]) === 'l' ? 'L' : $m[2]);
    }

    return [
        'form_code'          => $form_code,
        'form_display'       => $form_display,
        'form_system'        => $form_system,
        'route_code'         => $route_code,
        'route_display'      => $route_display,
        'numerator_code'     => $num_code,
        'denominator_code'   => $den_code,
    ];
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

    // API v2 products/all mengembalikan struktur: {data: [{...}, ...]} atau {items: {data: [...]}}
    $items = $data['data'] ?? $data['items']['data'] ?? [];

    if (!is_array($items)) {
        if (isset($data['page']) || isset($data['total']) || isset($data['items'])) {
            return []; // Valid empty result from API
        }
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

        $farm_type    = $item['farmalkes_type']['code'] ?? ($item['farmalkes_type'] ?? 'medicine');

        $results[] = [
            'id'            => $kfa_code,
            'text'          => $kfa_code . ' — ' . $name,
            'display_name'  => $name,
            'farmalkes_type'=> $farm_type,
            'product_type'  => 'product',
            'route_code'    => $route['code'],
            'route_display' => $route['display'],
            'form_code'     => $form_code,
            'form_display'  => $form_display,
            'ucum_code'     => $ucum_code,
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
 * Mencari produk Alat Kesehatan (ALKES) menggunakan KFA v3 REST API.
 *
 * Mendukung:
 *  - Variant products: POST /kfa-v3/alkes/products
 *  - Template products: POST /kfa-v3/alkes/template
 *
 * Return array format Select2 dengan metadata Alkes:
 * [
 *   'id'                    => kfa_code,
 *   'text'                  => "33002982 — Infusion set disposable... [NIE: ...]",
 *   'display_name'          => "Infusion set disposable...",
 *   'nama_dagang'           => "STARDEC Infusion Set...",
 *   'nie'                   => "AKD 20902810476",
 *   'manufacturer'          => "PT. SAFELOCK MEDICAL",
 *   'product_template_code' => "32009840",
 *   'product_template_name' => "Infusion set disposable",
 *   'farmalkes_type'        => "device",
 *   'product_type'          => 'alkes',
 *   'route_code'            => '',
 *   'route_display'         => '',
 *   'form_code'             => '',
 *   'form_display'          => '',
 *   'ucum_code'             => '',
 * ]
 *
 * @param  array   $cred
 * @param  string  $keyword
 * @param  int     $size
 * @param  bool    $templateOnly
 * @return array|null
 */
function kfa_v3_search_alkes(array $cred, string $keyword, int $size = 20, bool $templateOnly = false): ?array {
    $token = kfa_get_valid_token($cred);
    if ($token === null) {
        return null;
    }

    $urls = kfa_get_base_urls($cred);
    $endpoint = $templateOnly ? '/alkes/template' : '/alkes/products';
    $url = $urls['kfa_v3'] . $endpoint;

    $payload = [
        'page'   => 1,
        'size'   => $size,
        'state'  => 'valid',
        'active' => true,
        'search' => $keyword,
    ];

    $response = kfa_http_post_json($url, $token, $payload);
    if ($response === null || !isset($response['status'])) {
        return null;
    }

    // SatuSehat KFA v3 returns {"status": 200, "error": false, "message": "no data", "data": null} when no matches found
    if (empty($response['data']) || !is_array($response['data'])) {
        if (!empty($response['status']) && (int)$response['status'] === 200) {
            return []; // Valid empty result from API
        }
        return null;
    }

    $results = [];
    foreach ($response['data'] as $item) {
        $kfa_code = $item['kfa_code'] ?? '';
        if (empty($kfa_code)) continue;

        if ($templateOnly) {
            $name         = $item['name'] ?? ($item['display_name'] ?? '');
            $display_name = $name;
            $nama_dagang  = $name;
            $nie          = '';
            $mfg          = '';
            $tmpl_code    = $kfa_code;
            $tmpl_name    = $name;
            $farm_type    = $item['farmalkes_type']['code'] ?? 'device';

            $text = $kfa_code . ' — [Template] ' . $display_name;
        } else {
            $display_name = $item['display_name'] ?? ($item['nama_dagang'] ?? ($item['name'] ?? ''));
            $nama_dagang  = $item['nama_dagang'] ?? '';
            $nie          = $item['nie'] ?? '';
            $mfg          = $item['manufacturer'] ?? ($item['registrar'] ?? '');
            $tmpl_code    = $item['product_template']['kfa_code'] ?? '';
            $tmpl_name    = $item['product_template']['name'] ?? '';
            $farm_type    = $item['farmalkes_type']['code'] ?? 'device';

            $text = $kfa_code . ' — ' . $display_name;
            if (!empty($nie)) {
                $text .= ' [NIE: ' . $nie . ']';
            }
            if (!empty($mfg)) {
                $text .= ' (' . $mfg . ')';
            }
        }

        $parsed = kfa_parse_alkes_details($display_name, $tmpl_code, $tmpl_name, $item['uom_name'] ?? 'Pcs');

        $results[] = [
            'id'                    => $kfa_code,
            'text'                  => $text,
            'display_name'          => $display_name,
            'nama_dagang'           => $nama_dagang,
            'nie'                   => $nie,
            'manufacturer'          => $mfg,
            'product_template_code' => $tmpl_code,
            'product_template_name' => $tmpl_name,
            'farmalkes_type'        => $farm_type,
            'product_type'          => 'alkes',
            'route_code'            => $parsed['route_code'],
            'route_display'         => $parsed['route_display'],
            'form_code'             => $parsed['form_code'],
            'form_display'          => $parsed['form_display'],
            'form_system'           => $parsed['form_system'],
            'numerator_code'        => $parsed['numerator_code'],
            'denominator_code'      => $parsed['denominator_code'],
            'ucum_code'             => $parsed['numerator_code'],
        ];
    }

    return $results;
}

/**
 * Mendapatkan detail produk Alkes KFA v3 berdasarkan kfa_code.
 *
 * @param  array   $cred
 * @param  string  $kfa_code
 * @return array|null
 */
function kfa_v3_get_alkes_detail(array $cred, string $kfa_code): ?array {
    $token = kfa_get_valid_token($cred);
    if ($token === null) return null;

    $urls = kfa_get_base_urls($cred);
    $url  = $urls['kfa_v3'] . '/alkes/products';
    $payload = [
        'page'     => 1,
        'size'     => 1,
        'kfa_code' => $kfa_code,
        'search'   => $kfa_code,
    ];

    $data = kfa_http_post_json($url, $token, $payload);
    if ($data && !empty($data['data'][0])) {
        return $data['data'][0];
    }

    // Fallback: search in template
    $urlTmpl = $urls['kfa_v3'] . '/alkes/template';
    $dataTmpl = kfa_http_post_json($urlTmpl, $token, $payload);
    return $dataTmpl['data'][0] ?? null;
}

/**
 * Unified Search Helper: mencari produk KFA berdasarkan tipe (farmasi / alkes).
 *
 * @param  array   $cred
 * @param  string  $keyword
 * @param  string  $product_type 'farmasi' | 'alkes'
 * @param  int     $size
 * @param  bool    $templateOnly
 * @return array|null
 */
function kfa_search_product(array $cred, string $keyword, string $product_type = 'farmasi', int $size = 20, bool $templateOnly = false): ?array {
    if (strtolower($product_type) === 'alkes') {
        return kfa_v3_search_alkes($cred, $keyword, $size, $templateOnly);
    }
    return kfa_search_from_api($cred, $keyword, $size);
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
