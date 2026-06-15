<?php
/**
 * FHIR Terminology API Helper
 * Menangani pencarian SNOMED-CT dan LOINC ke public terminology servers.
 */

// Membaca credential KFA / FHIR dari file JSON
function fhir_get_credential() {
    $file = dirname(__DIR__) . '/satusehat_credential.json';
    if (!file_exists($file)) {
        return null; // File belum ada
    }
    $json = file_get_contents($file);
    return json_decode($json, true);
}

/**
 * Mencari kode SNOMED CT menggunakan SNOMED International Public Training API.
 * Server ini sangat cepat, stabil, dan tidak memerlukan autentikasi.
 *
 * @param string $keyword
 * @param string|null $ecl Constraint SNOMED Expression Constraint Language (opsional)
 * @return array Array results untuk select2
 */
function fhir_search_snomed($keyword, $ecl = null) {
    // Gunakan Snowstorm Training API (Public, No Auth)
    $url = 'https://snowstorm-training.snomedtools.org/fhir/ValueSet/$expand';
    
    // Jika ada ECL, gabungkan ke parameter url. Jika tidak, gunakan base fhir_vs
    $vs_url = 'http://snomed.info/sct?fhir_vs';
    if ($ecl !== null) {
        $vs_url .= '=ecl/' . urlencode($ecl);
    }
    
    $url .= '?url=' . $vs_url
         . '&filter=' . urlencode($keyword)
         . '&count=50'; // Ambil hingga 50 untuk sinkronisasi dengan pagination lokal

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Cache-Control: no-cache'
    ]);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    $results = [];
    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['expansion']['contains'])) {
            foreach ($data['expansion']['contains'] as $item) {
                $results[] = [
                    'id'      => $item['code'],
                    'text'    => $item['code'] . ' - ' . $item['display'],
                    'display' => $item['display'],
                    'system'  => $item['system'] ?? 'http://snomed.info/sct'
                ];
            }
        }
    }

    return [
        'status'  => ($http_code === 200 && count($results) > 0) ? 'success' : 'error',
        'source'  => 'api',
        'results' => $results,
        'debug'   => $curl_err ?: null
    ];
}

/**
 * Mencari kode LOINC di server fhir.loinc.org.
 * Membutuhkan username dan password akun gratis loinc.org.
 * @param string $keyword
 * @return array Array results untuk select2
 */
function fhir_search_loinc($keyword) {
    $cred = fhir_get_credential();
    $loincUser = isset($cred['loinc_username']) ? $cred['loinc_username'] : '';
    $loincPass = isset($cred['loinc_password']) ? $cred['loinc_password'] : '';
    
    // Jika tidak ada credential di-set, langsung kembalikan error (supaya lari ke fallback DB lokal)
    if (empty($loincUser) || empty($loincPass)) {
        return [
            'status' => 'error',
            'message' => 'Credential LOINC belum diatur di Super Admin.',
            'source' => 'api',
            'results' => []
        ];
    }

    $url = "https://fhir.loinc.org/ValueSet/\$expand?url=http://loinc.org/vs&count=30&filter=" . urlencode($keyword);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // Basic Auth
    curl_setopt($ch, CURLOPT_USERPWD, $loincUser . ":" . $loincPass);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $results = [];
    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['expansion']['contains'])) {
            foreach ($data['expansion']['contains'] as $item) {
                $results[] = [
                    'id' => $item['code'],
                    'text' => $item['code'] . ' - ' . $item['display'],
                    'display' => $item['display'],
                    'system' => $item['system']
                ];
            }
        }
    }
    
    return [
        'status' => $http_code === 200 ? 'success' : 'error',
        'source' => 'api',
        'results' => $results
    ];
}
