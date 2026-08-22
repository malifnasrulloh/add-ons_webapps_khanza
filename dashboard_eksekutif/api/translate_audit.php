<?php
/*
 * File: api/translate_audit.php
 * Deskripsi: API untuk Penerjemahan Query Tunggal, Analisis Batch Kolektif Audit Trail, dan AI Chat Assistant dengan Auto-Switching Fallback Model & Real-Time SSE Streaming
 */

// Set batas waktu eksekusi skrip dan limit memori untuk pemrosesan batch besar & streaming
@set_time_limit(300);
@ini_set('memory_limit', '256M');

session_start();

// Cek Otorisasi Super Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Akses ditolak. Fitur ini khusus untuk Super Admin.'
    ]);
    exit;
}
session_write_close();

$config_file = dirname(__DIR__) . '/config/llm_config.json';
if (!file_exists($config_file)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Konfigurasi LLM belum diset. Silakan buka menu Pengaturan LLM terlebih dahulu.'
    ]);
    exit;
}

$config = json_decode(file_get_contents($config_file), true);
if (empty($config['api_endpoint']) || empty($config['api_key']) || empty($config['model'])) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Konfigurasi LLM tidak lengkap. Silakan lengkapi di menu Pengaturan LLM.'
    ]);
    exit;
}

// Susun model prioritas untuk auto-switching
$fallback_models = isset($config['fallback_models']) ? $config['fallback_models'] : [];
if (!is_array($fallback_models)) {
    $fallback_models = array_map('trim', explode(',', $fallback_models));
}
$models_to_try = array_unique(array_filter(array_merge([$config['model']], $fallback_models)));

$action = isset($_POST['action']) ? $_POST['action'] : 'single';
$is_stream = isset($_POST['stream']) && $_POST['stream'] == '1';

if (!$is_stream) {
    header('Content-Type: application/json; charset=utf-8');
}

if ($action === 'batch_summary') {
    // Analisis Kolektif / Batch Audit Trail (Maksimal 500 data terbaru)
    $logs_json = isset($_POST['logs']) ? $_POST['logs'] : '';
    if (empty($logs_json)) {
        if ($is_stream) { send_sse_error('Data log audit tidak dikirim.'); }
        else { echo json_encode(['status' => 'error', 'message' => 'Data log audit tidak dikirim.']); }
        exit;
    }

    $logs = json_decode($logs_json, true);
    if (!is_array($logs) || empty($logs)) {
        if ($is_stream) { send_sse_error('Format data log audit tidak valid.'); }
        else { echo json_encode(['status' => 'error', 'message' => 'Format data log audit tidak valid.']); }
        exit;
    }

    // Batasi maksimal 500 baris query terbaru sesuai permintaan pengguna
    $logs = array_slice($logs, 0, 500);

    // Format sampel data log menjadi teks yang efisien untuk LLM
    $formatted_logs = "";
    foreach ($logs as $idx => $item) {
        $tgl = isset($item['tgl']) ? $item['tgl'] : '';
        $user = isset($item['user']) ? $item['user'] : '';
        $nama = isset($item['nama']) ? $item['nama'] : '';
        $sql = isset($item['sql']) ? $item['sql'] : '';
        $num = $idx + 1;
        $formatted_logs .= "[$num] ($tgl) User: $nama ($user) -> SQL: $sql\n";
    }

    $system_prompt = isset($_POST['custom_prompt']) ? trim($_POST['custom_prompt']) : "Anda adalah Analis Keamanan Siber & Audit SIMKES Rumah Sakit yang ahli. Tugas Anda adalah menerjemahkan dan mengelompokkan sekumpulan log audit SQL menjadi Laporan Naratif Eksekutif dalam Bahasa Indonesia yang profesional, ramah pengguna, dan mudah dipahami oleh Manajemen Eksekutif RS.";
    
    $user_message = "Berikut adalah daftar " . count($logs) . " log audit trail terbaru hasil filter:\n\n" . $formatted_logs;

    // Gunakan max_tokens 4096 agar tidak mudah terpotong
    $response = call_llm_api_with_fallback($config['api_endpoint'], $config['api_key'], $models_to_try, $system_prompt, [['role' => 'user', 'content' => $user_message]], 4096, $is_stream);

    if ($is_stream) {
        if ($response['status'] !== 'success') {
            send_sse_error($response['message']);
        }
        exit;
    }

    if ($response['status'] === 'success') {
        echo json_encode([
            'status' => 'success',
            'summary' => $response['content'],
            'model_used' => $response['model_used'],
            'total_processed' => count($logs)
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => $response['message']
        ]);
    }
    exit;

} elseif ($action === 'chat_discuss') {
    // Interactive Chat Assistant Diskusi Lanjutan
    $user_msg = isset($_POST['message']) ? trim($_POST['message']) : '';
    $history_json = isset($_POST['history']) ? $_POST['history'] : '[]';
    $report_context = isset($_POST['report_context']) ? trim($_POST['report_context']) : '';
    $raw_logs_json = isset($_POST['raw_logs']) ? $_POST['raw_logs'] : '';

    if (empty($user_msg)) {
        if ($is_stream) { send_sse_error('Pesan tidak boleh kosong.'); }
        else { echo json_encode(['status' => 'error', 'message' => 'Pesan tidak boleh kosong.']); }
        exit;
    }

    $history = json_decode($history_json, true);
    if (!is_array($history)) {
        $history = [];
    }

    $raw_logs = json_decode($raw_logs_json, true);
    $formatted_raw_logs = "";
    if (is_array($raw_logs) && count($raw_logs) > 0) {
        $formatted_raw_logs .= "\n\nSebagai tambahan referensi, berikut adalah data mentah (" . count($raw_logs) . " baris) yang sedang dibahas:\n";
        foreach ($raw_logs as $idx => $item) {
            $tgl = isset($item['tgl']) ? $item['tgl'] : '';
            $user = isset($item['user']) ? $item['user'] : '';
            $nama = isset($item['nama']) ? $item['nama'] : '';
            $sql = isset($item['sql']) ? $item['sql'] : '';
            $num = $idx + 1;
            $formatted_raw_logs .= "[$num] ($tgl) User: $nama ($user) -> SQL: $sql\n";
        }
    }

    $system_prompt = "Anda adalah Asisten AI Analis Audit Trail SIMKES Khanza. Anda sedang berdiskusi dengan Super Admin/Manajemen Eksekutif RS mengenai temuan laporan audit log berikut:\n\n" . $report_context . $formatted_raw_logs . "\n\nJawab pertanyaan pengguna dengan jelas, tepat, dan membantu dalam Bahasa Indonesia.";

    // Susun pesan untuk API
    $messages = [];
    foreach ($history as $h) {
        if (isset($h['role']) && isset($h['content'])) {
            $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }
    }
    $messages[] = ['role' => 'user', 'content' => $user_msg];

    $response = call_llm_api_with_fallback($config['api_endpoint'], $config['api_key'], $models_to_try, $system_prompt, $messages, 2048, $is_stream);

    if ($is_stream) {
        if ($response['status'] !== 'success') {
            send_sse_error($response['message']);
        }
        exit;
    }

    if ($response['status'] === 'success') {
        echo json_encode([
            'status' => 'success',
            'reply' => $response['content'],
            'model_used' => $response['model_used']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => $response['message']
        ]);
    }
    exit;

} else {
    // Penterjemahan Query Single Row (Tanpa Stream)
    $sql_base64 = isset($_POST['sql']) ? trim($_POST['sql']) : '';
    if (empty($sql_base64)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Data SQL tidak dikirim.'
        ]);
        exit;
    }

    $sql_query = base64_decode($sql_base64);
    if ($sql_query === false) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Dekode data SQL gagal.'
        ]);
        exit;
    }

    $messages = [
        ['role' => 'user', 'content' => "Terjemahkan query berikut:\n" . $sql_query]
    ];

    $single_prompt = isset($_POST['custom_prompt']) ? trim($_POST['custom_prompt']) : (!empty($config['prompt']) ? $config['prompt'] : "Terjemahkan SQL query pada datatable ini ke penjelasan bahasa Indonesia yang singkat, ramah pengguna, dan mudah dipahami. Jelaskan apa tindakan yang dilakukan (misal menambah pasien, mengupdate kamar, menghapus obat, dan lain-lain) dan sebutkan data kunci seperti nama atau nomor rekam medis jika ada. Jangan sertakan tag teknis atau kode, langsung hasil terjemahannya saja.");

    $response = call_llm_api_with_fallback($config['api_endpoint'], $config['api_key'], $models_to_try, $single_prompt, $messages, 1024, false);

    if ($response['status'] === 'success') {
        echo json_encode([
            'status' => 'success',
            'translation' => $response['content'],
            'model_used' => $response['model_used']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => $response['message']
        ]);
    }
    exit;
}

// Wrapper panggilan LLM dengan sistem Fallback / Auto-Switching
function call_llm_api_with_fallback($endpoint, $api_key, $models_array, $system_prompt, $messages_array, $max_tokens = 4096, $stream = false) {
    $last_result = ['status' => 'error', 'message' => 'Tidak ada model yang dikonfigurasi.'];
    
    foreach ($models_array as $model) {
        $result = call_llm_api($endpoint, $api_key, $model, $system_prompt, $messages_array, $max_tokens, $stream);
        
        if ($result['status'] === 'success') {
            $result['model_used'] = $model;
            return $result;
        }
        
        $last_result = $result;
        
        // Deteksi error kuota, token, rate limit (HTTP 429 atau text terkait quota/limit/token/credentials)
        $is_quota_error = (
            strpos($result['message'], '429') !== false ||
            strpos(strtolower($result['message']), 'quota') !== false ||
            strpos(strtolower($result['message']), 'limit') !== false ||
            strpos(strtolower($result['message']), 'token') !== false ||
            strpos(strtolower($result['message']), 'credentials') !== false
        );
        
        if (!$is_quota_error) {
            // Jika error tipe fatal lain, hentikan fallback loop langsung
            break;
        }
        
        error_log("[LLM Fallback Auto-Switch] Model " . $model . " gagal dengan error: " . $result['message'] . ". Mencoba model cadangan berikutnya...");
    }
    
    return $last_result;
}

// Fungsi Dasar Panggilan LLM HTTP API
function call_llm_api($endpoint, $api_key, $model, $system_prompt, $messages_array, $max_tokens = 4096, $stream = false) {
    $url = rtrim($endpoint, '/');
    if (strpos($url, '/chat/completions') === false) {
        $url .= '/chat/completions';
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];

    // Sisipkan system prompt di awal array pesan
    $full_messages = array_merge(
        [['role' => 'system', 'content' => $system_prompt]],
        $messages_array
    );

    $post_data = [
        'model' => $model,
        'messages' => $full_messages,
        'temperature' => 0.2,
        'max_tokens' => $max_tokens,
        'max_completion_tokens' => $max_tokens,
        'stream' => $stream
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    if ($stream) {
        $headers_sent = false;
        $error_buffer = '';
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) use (&$headers_sent, &$error_buffer, $model) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code === 200) {
                if (!$headers_sent) {
                    header('Content-Type: text/event-stream; charset=utf-8');
                    header('Cache-Control: no-cache');
                    header('Connection: keep-alive');
                    header('X-Accel-Buffering: no');
                    // Kirim event metadata awal
                    echo "event: metadata\ndata: " . json_encode(['model_used' => $model]) . "\n\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                    $headers_sent = true;
                }
                echo $chunk;
                if (ob_get_level() > 0) ob_flush();
                flush();
            } else {
                $error_buffer .= $chunk;
            }
            return strlen($chunk);
        });

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['status' => 'error', 'message' => 'Koneksi error: ' . $error];
        }
        if ($http_code !== 200) {
            return ['status' => 'error', 'message' => 'API mengembalikan HTTP Code ' . $http_code . '. Detail: ' . $error_buffer];
        }
        return ['status' => 'success', 'streamed' => true, 'content' => ''];
    }

    // Mode Non-Streaming
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['status' => 'error', 'message' => 'Koneksi error: ' . $error];
    }

    if ($http_code !== 200) {
        return ['status' => 'error', 'message' => 'API mengembalikan HTTP Code ' . $http_code . '. Detail: ' . $response];
    }

    $res_data = json_decode($response, true);
    if (isset($res_data['choices'][0]['message']['content'])) {
        return [
            'status' => 'success',
            'content' => trim($res_data['choices'][0]['message']['content'])
        ];
    }

    // Fallback: Jika response berupa SSE stream (data:), gabungkan isinya
    if (strpos($response, 'data:') !== false) {
        $lines = explode("\n", $response);
        $full_text = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'data:') === 0) {
                $json_str = trim(substr($line, 5));
                if ($json_str === '[DONE]') continue;
                $chunk_data = json_decode($json_str, true);
                if (isset($chunk_data['choices'][0]['delta']['content'])) {
                    $full_text .= $chunk_data['choices'][0]['delta']['content'];
                } elseif (isset($chunk_data['choices'][0]['text'])) {
                    $full_text .= $chunk_data['choices'][0]['text'];
                }
            }
        }
        if (!empty($full_text)) {
            return [
                'status' => 'success',
                'content' => trim($full_text)
            ];
        }
    }

    $raw_preview = substr(strip_tags($response), 0, 200);
    return [
        'status' => 'error',
        'message' => 'Format respons dari LLM tidak dikenal. Raw response: ' . $raw_preview
    ];
}

function send_sse_error($msg) {
    if (!headers_sent()) {
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
    }
    echo "event: error\ndata: " . json_encode(['message' => $msg]) . "\n\n";
    if (ob_get_level() > 0) ob_flush();
    flush();
}
?>
