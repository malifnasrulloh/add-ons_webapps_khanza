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
    // Use SNOMED Browser Public FHIR R4 API Endpoint
    $vs_url = 'http://snomed.info/sct?fhir_vs';
    if ($ecl !== null) {
        $vs_url .= '=ecl/' . urlencode($ecl);
    }

    $url = 'https://snomedbrowser.org/fhir/ValueSet/$expand'
         . '?filter=' . urlencode($keyword)
         . '&count=50'
         . '&url=' . urlencode($vs_url)
         . '&_format=json';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
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
            $results = fhir_sort_results($results, $keyword);
        }
    }

    return [
        'status'  => ($http_code === 200 && count($results) > 0) ? 'success' : 'error',
        'source'  => 'api',
        'results' => $results,
        'debug'   => [
            'http_code' => $http_code,
            'curl_error' => $curl_err ?: null,
            'url' => $url,
            'response_empty' => empty($response),
            'response_preview' => $response ? substr($response, 0, 300) : null
        ]
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
            'results' => [],
            'debug' => ['message' => 'LOINC credentials empty']
        ];
    }

    $url = "https://fhir.loinc.org/ValueSet/\$expand?url=http://loinc.org/vs&count=30&filter=" . urlencode($keyword);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    // Basic Auth
    curl_setopt($ch, CURLOPT_USERPWD, $loincUser . ":" . $loincPass);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Cache-Control: no-cache'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
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
                    'system_type' => '',
                    'method_typ' => '',
                    'property' => '',
                    'class' => '',
                    'shortname' => '',
                    'system' => $item['system'] ?? 'http://loinc.org'
                ];
            }
            $results = fhir_sort_results($results, $keyword);
        }
    }
    
    return [
        'status' => ($http_code === 200 && count($results) > 0) ? 'success' : 'error',
        'source' => 'api',
        'results' => $results,
        'debug'   => [
            'http_code' => $http_code,
            'curl_error' => $curl_err ?: null,
            'url' => $url,
            'response_empty' => empty($response),
            'response_preview' => $response ? substr($response, 0, 300) : null
        ]
    ];
}

/**
 * Mengurutkan hasil pencarian berdasarkan relevansi kata kunci.
 * 1. Cocok kode persis (exact code match)
 * 2. Teks diawali oleh query lengkap
 * 3. Teks diawali oleh kata pertama query
 * 4. Alfabetis
 */
function fhir_sort_results($results, $query) {
    $q = strtolower(trim($query));
    if ($q === '') return $results;
    
    $words = array_filter(explode(' ', $q));
    $firstWord = reset($words) ?: '';
    
    usort($results, function($a, $b) use ($q, $firstWord) {
        $aId = strtolower($a['id']);
        $bId = strtolower($b['id']);
        if ($aId === $q && $bId !== $q) return -1;
        if ($bId === $q && $aId !== $q) return 1;
        
        $aDisplay = strtolower($a['display']);
        $bDisplay = strtolower($b['display']);
        
        $aStartsWithQuery = (strpos($aDisplay, $q) === 0);
        $bStartsWithQuery = (strpos($bDisplay, $q) === 0);
        
        if ($aStartsWithQuery && !$bStartsWithQuery) return -1;
        if ($bStartsWithQuery && !$aStartsWithQuery) return 1;
        
        if ($firstWord !== '') {
            $aStartsWithFirstWord = (strpos($aDisplay, $firstWord) === 0);
            $bStartsWithFirstWord = (strpos($bDisplay, $firstWord) === 0);
            
            if ($aStartsWithFirstWord && !$bStartsWithFirstWord) return -1;
            if ($bStartsWithFirstWord && !$aStartsWithFirstWord) return 1;
        }
        
        return strcasecmp($a['display'], $b['display']);
    });
    
    return $results;
}
