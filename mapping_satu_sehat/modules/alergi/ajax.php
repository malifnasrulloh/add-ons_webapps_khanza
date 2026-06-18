<?php
/**
 * modules/alergi/ajax.php — Backend AJAX Modul Mapping Alergi (Stateless/Session-based)
 */
error_reporting(0); ini_set('display_errors', 0);
require_once __DIR__ . '/../../conf.php';
require_once __DIR__ . '/../../auth_check.php';
require_login(); // Menggunakan require_login karena modul ini tidak memiliki tabel tersendiri
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$session_key = 'alergi_mapping_data';

// Helper function to safely read JSON and init session
function process_json_input($json_string) {
    global $session_key;
    $data = json_decode($json_string, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Format JSON tidak valid: " . json_last_error_msg());
    }
    if (!isset($data['alergi']) || !is_array($data['alergi'])) {
        throw new Exception("JSON tidak memiliki root node 'alergi' berupa array sesuai format .iyem.");
    }
    $_SESSION[$session_key] = $data['alergi'];
    return count($data['alergi']);
}

try {
    // ========================================================
    // 1. IMPORT FILE (.iyem / .json)
    // ========================================================
    if ($action === 'import_file') {
        validate_csrf();
        if (!isset($_FILES['file_iyem']) || $_FILES['file_iyem']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Gagal mengunggah file.");
        }
        $content = file_get_contents($_FILES['file_iyem']['tmp_name']);
        $count = process_json_input($content);
        echo json_encode(['status' => 'success', 'message' => "Berhasil memuat $count data alergi."]);
        exit;
    }

    // ========================================================
    // 2. IMPORT URL
    // ========================================================
    if ($action === 'import_url') {
        validate_csrf();
        $url = filter_var($_POST['url_iyem'] ?? '', FILTER_VALIDATE_URL);
        if (!$url) throw new Exception("URL tidak valid.");
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $content = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($code !== 200 || !$content) throw new Exception("Gagal mengambil data dari URL.");
        $count = process_json_input($content);
        echo json_encode(['status' => 'success', 'message' => "Berhasil memuat $count data alergi dari URL."]);
        exit;
    }

    // ========================================================
    // 3. IMPORT TEXT (PASTE)
    // ========================================================
    if ($action === 'import_text') {
        validate_csrf();
        $text = trim($_POST['text_iyem'] ?? '');
        if (empty($text)) throw new Exception("Teks JSON kosong.");
        $count = process_json_input($text);
        echo json_encode(['status' => 'success', 'message' => "Berhasil memuat $count data alergi dari teks."]);
        exit;
    }

    // ========================================================
    // 4. GET DATA FOR DATATABLES
    // ========================================================
    if ($action === 'get_data') {
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        
        if (!isset($_SESSION[$session_key])) {
            echo json_encode(['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
            exit;
        }

        $all_data = $_SESSION[$session_key];
        
        // Simple search
        $search = $_POST['search']['value'] ?? '';
        $filtered_data = [];
        foreach ($all_data as $index => $row) {
            $row['__index'] = $index; // Inject index so UI knows which row to update
            if (empty($search) || 
                stripos($row['keyword'], $search) !== false || 
                stripos($row['text'], $search) !== false ||
                stripos($row['coding_display'], $search) !== false) {
                $filtered_data[] = $row;
            }
        }

        // Pagination
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        if ($length < 1) $length = 10;
        
        $paged_data = array_slice($filtered_data, $start, $length);

        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => count($all_data),
            'recordsFiltered' => count($filtered_data),
            'data' => $paged_data
        ]);
        exit;
    }

    // ========================================================
    // 5. SEARCH SNOMED (ALLERGY SPECIFIC)
    // ========================================================
    if ($action === 'search_snomed_allergy') {
        $q = isset($_GET['term']) ? trim($_GET['term']) : '';
        require_once dirname(__DIR__) . '/fhir_terminology_helper.php';
        
        // // ECL untuk Alergi: (Substance) ATAU (Pharmaceutical / biologic product) ATAU (Propensity to adverse reaction)
        // $ecl = '<<105590001 OR <<373873005 OR <<420134006';
        
        $apiData = fhir_search_snomed($q);
        
        if ($apiData['status'] === 'success') {
            echo json_encode(['results' => $apiData['results'], 'source' => 'api', 'pagination' => ['more' => false]]);
        } else {
            echo json_encode(['results' => [], 'source' => 'error', 'pagination' => ['more' => false]]);
        }
        exit;
    }

    // ========================================================
    // 6. SAVE MAPPING (UPDATE SESSION)
    // ========================================================
    if ($action === 'save_mapping') {
        validate_csrf();
        $index = isset($_POST['index']) && $_POST['index'] !== '' ? intval($_POST['index']) : null;
        if ($index === null || !isset($_SESSION[$session_key][$index])) {
            throw new Exception("Index data tidak valid atau sesi kadaluarsa.");
        }

        $_SESSION[$session_key][$index]['category'] = isset($_POST['category']) ? $_POST['category'] : '';
        $_SESSION[$session_key][$index]['coding_code'] = isset($_POST['snomed_code']) ? $_POST['snomed_code'] : '';
        $_SESSION[$session_key][$index]['coding_display'] = isset($_POST['snomed_display']) ? $_POST['snomed_display'] : '';

        echo json_encode(['status' => 'success', 'message' => 'Mapping berhasil diperbarui di memori (sesi).']);
        exit;
    }

    // ========================================================
    // 7. DOWNLOAD
    // ========================================================
    if ($action === 'download') {
        if (empty($_SESSION[$session_key])) {
            die("Data sesi kosong. Harap import data terlebih dahulu.");
        }
        
        $output = ['alergi' => $_SESSION[$session_key]];
        $json = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        header('Content-disposition: attachment; filename=alergisatusehat.iyem');
        header('Content-type: application/json');
        echo $json;
        exit;
    }

    // ========================================================
    // 8. CLEAR
    // ========================================================
    if ($action === 'clear') {
        validate_csrf();
        unset($_SESSION[$session_key]);
        echo json_encode(['status' => 'success', 'message' => 'Sesi berhasil dibersihkan.']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>