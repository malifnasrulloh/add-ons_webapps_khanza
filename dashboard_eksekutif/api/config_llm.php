<?php
/*
 * File: api/config_llm.php
 * Deskripsi: API untuk menyimpan konfigurasi LLM dan menguji koneksi (Super Admin Only)
 */

// Memastikan content-type adalah JSON
header('Content-Type: application/json; charset=utf-8');

// Cek Otorisasi Super Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Akses ditolak. Halaman ini khusus untuk Super Admin.'
    ]);
    exit;
}

$config_file = dirname(__DIR__) . '/config/llm_config.json';

// Ambil input POST
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'save') {
    // CSRF token validation
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Token CSRF tidak valid.'
        ]);
        exit;
    }

    $ai_status = isset($_POST['ai_status']) && $_POST['ai_status'] === 'on' ? 'on' : 'off';
    $api_endpoint = isset($_POST['api_endpoint']) ? trim($_POST['api_endpoint']) : '';
    $api_key = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';
    $model = isset($_POST['model']) ? trim($_POST['model']) : '';
    $fallback_models_input = isset($_POST['fallback_models']) ? $_POST['fallback_models'] : '';
    $prompt = isset($_POST['prompt']) ? trim($_POST['prompt']) : '';

    if (empty($api_endpoint) || empty($api_key) || empty($model)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Endpoint, API Key, dan Model wajib diisi.'
        ]);
        exit;
    }

    // Ubah string/array fallback model menjadi array flat
    $fallback_models = [];
    if (is_array($fallback_models_input)) {
        $fallback_models = array_unique(array_filter(array_map('trim', $fallback_models_input)));
    } elseif (!empty($fallback_models_input)) {
        $fallback_models = array_unique(array_filter(array_map('trim', explode(',', $fallback_models_input))));
    }

    $new_config = [
        'ai_status' => $ai_status,
        'api_endpoint' => $api_endpoint,
        'api_key' => $api_key,
        'model' => $model,
        'fallback_models' => $fallback_models,
        'prompt' => $prompt
    ];

    if (file_put_contents($config_file, json_encode($new_config, JSON_PRETTY_PRINT))) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Konfigurasi LLM berhasil disimpan.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal menulis ke file konfigurasi. Pastikan izin akses folder sesuai.'
        ]);
    }
    exit;

} elseif ($action === 'test') {
    $api_endpoint = isset($_POST['api_endpoint']) ? trim($_POST['api_endpoint']) : '';
    $api_key = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';
    $model = isset($_POST['model']) ? trim($_POST['model']) : '';
    $prompt = isset($_POST['prompt']) ? trim($_POST['prompt']) : '';

    if (empty($api_endpoint) || empty($api_key) || empty($model)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Endpoint, API Key, dan Model wajib diisi untuk tes.'
        ]);
        exit;
    }

    // Panggil helper LLM dengan query test sederhana
    $test_query = "SELECT * FROM pasien LIMIT 1";
    $result = test_llm_call($api_endpoint, $api_key, $model, $prompt, $test_query);

    if ($result['status'] === 'success') {
        echo json_encode([
            'status' => 'success',
            'message' => 'Koneksi Sukses! Respons model: ' . $result['content']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Tes Koneksi Gagal: ' . $result['message']
        ]);
    }
    exit;
} elseif ($action === 'fetch_models') {
    $api_endpoint = isset($_POST['api_endpoint']) ? trim($_POST['api_endpoint']) : '';
    $api_key = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';

    if (empty($api_endpoint) || empty($api_key)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Endpoint dan API Key wajib diisi untuk mengambil daftar model.'
        ]);
        exit;
    }

    $url = rtrim($api_endpoint, '/') . '/models';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Koneksi error: ' . $error
        ]);
        exit;
    }

    if ($http_code !== 200) {
        echo json_encode([
            'status' => 'error',
            'message' => 'HTTP ' . $http_code . ' - ' . $response
        ]);
        exit;
    }

    $res_data = json_decode($response, true);
    $models = [];
    if (isset($res_data['data']) && is_array($res_data['data'])) {
        foreach ($res_data['data'] as $m) {
            if (isset($m['id'])) {
                $models[] = $m['id'];
            }
        }
    }

    echo json_encode([
        'status' => 'success',
        'models' => $models
    ]);
    exit;
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Aksi tidak dikenal.'
    ]);
    exit;
}

// Helper function untuk melakukan panggilan LLM
function test_llm_call($endpoint, $api_key, $model, $system_prompt, $user_message) {
    $url = rtrim($endpoint, '/');
    if (strpos($url, '/chat/completions') === false) {
        $url .= '/chat/completions';
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];

    $post_data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $user_message]
        ],
        'temperature' => 0.2,
        'max_tokens' => 150,
        'stream' => false
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['status' => 'error', 'message' => $error];
    }

    if ($http_code !== 200) {
        return ['status' => 'error', 'message' => 'HTTP ' . $http_code . ' - ' . $response];
    }

    $res_data = json_decode($response, true);
    if (isset($res_data['choices'][0]['message']['content'])) {
        return ['status' => 'success', 'content' => $res_data['choices'][0]['message']['content']];
    }
    
    // Fallback if the endpoint ignores 'stream' => false and returns SSE stream chunks
    if (strpos($response, 'data: ') !== false) {
        $content = '';
        $lines = explode("\n", $response);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'data: ') === 0) {
                $json_str = substr($line, 6);
                if ($json_str === '[DONE]') continue;
                $chunk = json_decode($json_str, true);
                if (isset($chunk['choices'][0]['delta']['content'])) {
                    $content .= $chunk['choices'][0]['delta']['content'];
                } elseif (isset($chunk['choices'][0]['message']['content'])) {
                    $content .= $chunk['choices'][0]['message']['content'];
                }
            }
        }
        if (!empty($content)) {
            return ['status' => 'success', 'content' => $content];
        }
    }
    
    // Fallback if the endpoint returns native Gemini format by accident
    if (isset($res_data['candidates'][0]['content']['parts'][0]['text'])) {
        return ['status' => 'success', 'content' => $res_data['candidates'][0]['content']['parts'][0]['text']];
    }

    // Check if there is an explicit error message in the JSON
    if (isset($res_data['error']['message'])) {
        return ['status' => 'error', 'message' => 'API Error: ' . (is_array($res_data['error']['message']) ? json_encode($res_data['error']['message']) : $res_data['error']['message'])];
    }
    if (isset($res_data['error']) && is_string($res_data['error'])) {
        return ['status' => 'error', 'message' => 'API Error: ' . $res_data['error']];
    }

    return ['status' => 'error', 'message' => 'Format respons JSON tidak dikenali. Respons: ' . mb_substr($response, 0, 300)];
}
?>
