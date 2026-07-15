<?php
/*
 * File: api/ai_analyzer.php
 * Deskripsi: API Gateway Universal untuk Analisis Data & Chat Assistant.
 *            Mendukung Auto-Switching Fallback Model, SSE Streaming,
 *            dan SSE Keepalive Heartbeat untuk mencegah timeout (HTTP 524)
 *            saat memproses konteks data berukuran besar.
 */

// Set batas waktu eksekusi skrip dan limit memori untuk pemrosesan batch besar & streaming
@set_time_limit(300);
@ini_set('memory_limit', '256M');

// Session diinisialisasi oleh api/auth_guard.php secara otomatis (auto_prepend_file)
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$config_file = dirname(__DIR__) . '/config/llm_config.json';
if (!file_exists($config_file)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Konfigurasi LLM belum diset. Silakan hubungi Super Admin.'
    ]);
    exit;
}

$config = json_decode(file_get_contents($config_file), true);
if (isset($config['ai_status']) && $config['ai_status'] === 'off') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Fitur AI saat ini dinonaktifkan oleh sistem.'
    ]);
    exit;
}

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

$action    = isset($_POST['action']) ? $_POST['action'] : 'batch_summary';
$is_stream = isset($_POST['stream']) && $_POST['stream'] == '1';

if (!$is_stream) {
    header('Content-Type: application/json; charset=utf-8');
}

// ─────────────────────────────────────────────────
// Helper: Kirim SSE Headers + Event "thinking" sesegera mungkin.
// Ini adalah inti dari solusi Anti-Timeout (HTTP 524):
// Cloudflare / proxy hanya akan timeout jika tidak ada SATU BYTE PUN
// yang dikirim dari server dalam 100 detik pertama.
// Dengan mengirimkan header dan event thinking sebelum memanggil LLM,
// koneksi SSE sudah "hidup" di mata Cloudflare.
// ─────────────────────────────────────────────────
function sse_start_and_send_thinking($row_count, $action_label = 'analisis') {
    if (headers_sent()) return;

    // Bersihkan output buffer agar header bisa dikirim
    if (ob_get_level() > 0) ob_end_clean();

    // Kirim header SSE
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');

    // Estimasi ukuran konteks untuk info user
    $size_label = '';
    if ($row_count >= 1000) {
        $size_label = number_format($row_count) . ' baris — konteks sangat besar';
    } elseif ($row_count >= 100) {
        $size_label = $row_count . ' baris — konteks besar';
    } else {
        $size_label = $row_count . ' baris';
    }

    // Kirim event "thinking" agar UI dapat menampilkan progress kepada user
    echo "event: thinking\ndata: " . json_encode([
        'row_count'   => $row_count,
        'size_label'  => $size_label,
        'action'      => $action_label,
        'message'     => "AI sedang membaca & memproses {$size_label} data. Mohon tunggu, proses ini bisa memakan waktu beberapa menit untuk dataset besar.",
    ]) . "\n\n";
    flush();
}

if ($action === 'batch_summary') {
    $raw_data_json = isset($_POST['raw_data']) ? $_POST['raw_data'] : '';
    if (empty($raw_data_json)) {
        if ($is_stream) { send_sse_error('Data untuk analisis tidak dikirim.'); }
        else { echo json_encode(['status' => 'error', 'message' => 'Data untuk analisis tidak dikirim.']); }
        exit;
    }

    $raw_data = json_decode($raw_data_json, true);
    if (!is_array($raw_data)) {
        if ($is_stream) { send_sse_error('Format data tidak valid.'); }
        else { echo json_encode(['status' => 'error', 'message' => 'Format data tidak valid.']); }
        exit;
    }

    // Batasi maks 10000 baris terbaru
    $raw_data = array_slice($raw_data, 0, 10000);

    // ── ANTI-TIMEOUT: Kirim SSE headers + event thinking segera ──
    if ($is_stream) {
        sse_start_and_send_thinking(count($raw_data), 'ringkasan laporan');
    }

    // Serialisasi dinamis data tabel/array
    $formatted_data = serialize_raw_data($raw_data);

    // Gunakan custom prompt spesifik halaman
    $system_prompt  = isset($_POST['custom_prompt']) ? trim($_POST['custom_prompt']) : "Anda adalah Asisten Analis AI profesional. Analisis data berikut dan sajikan dalam Bahasa Indonesia yang ringkas, mudah dipahami, serta ramah eksekutif.";
    $system_prompt .= "\n\nPENTING: Sajikan seluruh analisis dan rekomendasi secara langsung menggunakan narasi Bahasa Indonesia berformat Markdown (Gunakan Judul ##, Bold, dan Poin Bullet). DILARANG KERAS merespons dalam bentuk skrip atau kode pemrograman (seperti Python, JavaScript, JSON, dll).";
    
    $user_message = "Berikut data mentah (" . count($raw_data) . " baris) untuk dianalisis:\n\n" . $formatted_data;

    $response = call_llm_api_with_fallback($config['api_endpoint'], $config['api_key'], $models_to_try, $system_prompt, [['role' => 'user', 'content' => $user_message]], 4096, $is_stream);

    if ($is_stream) {
        if ($response['status'] !== 'success') {
            send_sse_error($response['message']);
        }
        exit;
    }

    if ($response['status'] === 'success') {
        echo json_encode([
            'status'          => 'success',
            'summary'         => $response['content'],
            'model_used'      => $response['model_used'],
            'total_processed' => count($raw_data)
        ]);
    } else {
        echo json_encode([
            'status'  => 'error',
            'message' => $response['message']
        ]);
    }
    exit;

} elseif ($action === 'chat_discuss') {
    $user_msg      = isset($_POST['message'])        ? trim($_POST['message'])  : '';
    $history_json  = isset($_POST['history'])        ? $_POST['history']        : '[]';
    $report_context= isset($_POST['report_context']) ? trim($_POST['report_context']) : '';
    $raw_data_json = isset($_POST['raw_data'])       ? $_POST['raw_data']       : '';

    if (empty($user_msg)) {
        if ($is_stream) { send_sse_error('Pesan tidak boleh kosong.'); }
        else { echo json_encode(['status' => 'error', 'message' => 'Pesan tidak boleh kosong.']); }
        exit;
    }

    $history = json_decode($history_json, true);
    if (!is_array($history)) {
        $history = [];
    }

    $raw_data = json_decode($raw_data_json, true);
    if (is_array($raw_data)) {
        $raw_data = array_slice($raw_data, 0, 10000);
    }

    $row_count_chat = is_array($raw_data) ? count($raw_data) : 0;

    // ── ANTI-TIMEOUT: Kirim SSE headers + event thinking segera ──
    if ($is_stream) {
        sse_start_and_send_thinking($row_count_chat, 'diskusi chat');
    }

    $formatted_raw_data = "";
    if (is_array($raw_data) && count($raw_data) > 0) {
        $formatted_raw_data .= "\n\nSebagai tambahan referensi, berikut adalah data mentah (" . count($raw_data) . " baris) yang sedang dibahas:\n";
        $formatted_raw_data .= serialize_raw_data($raw_data);
    }

    // Gunakan custom prompt spesifik halaman
    $system_prompt  = isset($_POST['custom_prompt']) ? trim($_POST['custom_prompt']) : "Anda adalah Asisten Analis AI profesional.";
    $system_prompt .= "\n\nKonteks analisis awal:\n" . $report_context . $formatted_raw_data;
    $system_prompt .= "\n\nJawab pertanyaan user mengenai data di atas secara objektif dan jelas dalam Bahasa Indonesia.";
    $system_prompt .= "\n\nPENTING: Selalu jawab langsung dalam bentuk teks naratif/diskusi eksekutif berformat Markdown. DILARANG KERAS menuliskan kode pemrograman (seperti skrip Python atau JavaScript) dalam jawaban.";

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
            'status'     => 'success',
            'reply'      => $response['content'],
            'model_used' => $response['model_used']
        ]);
    } else {
        echo json_encode([
            'status'  => 'error',
            'message' => $response['message']
        ]);
    }
    exit;
}

// ─────────────────────────────────────────────────
// Wrapper Fallback: mencoba model utama, lalu cadangan jika gagal.
// ─────────────────────────────────────────────────
function call_llm_api_with_fallback($endpoint, $api_key, $models_array, $system_prompt, $messages_array, $max_tokens = 4096, $stream = false) {
    $last_result = ['status' => 'error', 'message' => 'Tidak ada model yang dikonfigurasi.'];
    foreach ($models_array as $model) {
        $result = call_llm_api($endpoint, $api_key, $model, $system_prompt, $messages_array, $max_tokens, $stream);
        if ($result['status'] === 'success') {
            $result['model_used'] = $model;
            return $result;
        }
        $last_result = $result;
        $is_quota_error = (
            strpos($result['message'], '429') !== false ||
            strpos(strtolower($result['message']), 'quota') !== false ||
            strpos(strtolower($result['message']), 'limit') !== false ||
            strpos(strtolower($result['message']), 'token') !== false ||
            strpos(strtolower($result['message']), 'credentials') !== false
        );
        if (!$is_quota_error) break;
        error_log("[LLM Fallback Universal] Model $model gagal. Mencoba cadangan...");
    }
    return $last_result;
}

// ─────────────────────────────────────────────────
// Core LLM API call menggunakan curl_multi.
//
// Untuk mode streaming: menggunakan curl_multi_exec() dalam sebuah loop
// sehingga PHP dapat mengirimkan komentar heartbeat (": ping") ke browser
// setiap 20 detik SELAMA fase prefill LLM (saat LLM sedang membaca/memproses
// konteks, sebelum token pertama dikirimkan).
//
// Ini mencegah proxy/CDN (Cloudflare, dll.) menutup koneksi dengan HTTP 524
// karena koneksi SSE terlihat aktif terus-menerus dari sisi proxy.
// ─────────────────────────────────────────────────
function call_llm_api($endpoint, $api_key, $model, $system_prompt, $messages_array, $max_tokens = 4096, $stream = false) {
    $url = rtrim($endpoint, '/');
    if (strpos($url, '/chat/completions') === false) {
        $url .= '/chat/completions';
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];

    $post_data = [
        'model'       => $model,
        'messages'    => array_merge([['role' => 'system', 'content' => $system_prompt]], $messages_array),
        'temperature' => 0.2,
        'stream'      => $stream
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER,    $headers);
    curl_setopt($ch, CURLOPT_POST,          true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,    json_encode($post_data));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT,       300);
    curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    if ($stream) {
        /*
         * MODE STREAMING + KEEPALIVE HEARTBEAT
         *
         * SSE headers sudah dikirim oleh sse_start_and_send_thinking() sebelumnya.
         * Di sini kita menggunakan curl_multi agar loop PHP tetap berjalan
         * dan bisa mengirimkan "ping" heartbeat setiap 20 detik sambil
         * menunggu LLM menyelesaikan fase prefill-nya.
         */
        $error_buffer  = '';
        $http_code     = 0;
        $first_chunk   = true; // flag untuk emit event "metadata" saat chunk pertama tiba

        // Buffer respons streaming dari LLM
        $response_body = '';
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch_inner, $chunk) use (&$error_buffer, &$http_code, &$first_chunk, $model) {
            $http_code = curl_getinfo($ch_inner, CURLINFO_HTTP_CODE);
            if ($http_code === 200) {
                // Saat chunk pertama tiba, emit event metadata (nama model)
                if ($first_chunk) {
                    echo "event: metadata\ndata: " . json_encode(['model_used' => $model]) . "\n\n";
                    if (ob_get_level() > 0) ob_flush(); flush();
                    $first_chunk = false;
                }
                echo $chunk;
                if (ob_get_level() > 0) ob_flush(); flush();
            } else {
                $error_buffer .= $chunk;
            }
            return strlen($chunk);
        });

        // Gunakan curl_multi untuk non-blocking loop dengan heartbeat
        $mh          = curl_multi_init();
        curl_multi_add_handle($mh, $ch);

        $last_ping_time = time(); // catat waktu ping terakhir

        do {
            // Jalankan curl (non-blocking)
            $status = curl_multi_exec($mh, $still_running);

            // Kirim heartbeat komentar SSE setiap 20 detik
            // Browser dan Cloudflare akan mengabaikan komentar ini,
            // tapi kehadirannya membuktikan koneksi masih hidup.
            if (time() - $last_ping_time >= 20) {
                echo ": ping\n\n";
                if (ob_get_level() > 0) ob_flush(); flush();
                $last_ping_time = time();
            }

            // Hindari CPU spinning — beri jeda 50ms antar iterasi
            if ($still_running) {
                curl_multi_select($mh, 0.05);
            }

        } while ($still_running && $status == CURLM_OK);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($mh, $ch);
        curl_multi_close($mh);
        curl_close($ch);

        if ($http_code !== 200) {
            return ['status' => 'error', 'message' => 'API mengembalikan HTTP Code ' . $http_code . '. Detail: ' . $error_buffer];
        }
        return ['status' => 'success', 'streamed' => true, 'content' => ''];
    }

    // MODE NON-STREAMING (standar)
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        return ['status' => 'error', 'message' => 'API HTTP Code ' . $http_code . '. Detail: ' . $response];
    }

    $res_data = json_decode($response, true);
    if (isset($res_data['choices'][0]['message']['content'])) {
        return ['status' => 'success', 'content' => trim($res_data['choices'][0]['message']['content'])];
    }
    
    // Fallback SSE Stream parsing (untuk gateway yang memaksa mode streaming)
    if (strpos($response, 'data:') !== false) {
        $lines     = explode("\n", $response);
        $full_text = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'data:') === 0) {
                $json_str   = trim(substr($line, 5));
                if ($json_str === '[DONE]') continue;
                $chunk_data = json_decode($json_str, true);
                if (isset($chunk_data['choices'][0]['delta']['content'])) {
                    $full_text .= $chunk_data['choices'][0]['delta']['content'];
                }
            }
        }
        if (!empty($full_text)) return ['status' => 'success', 'content' => trim($full_text)];
    }

    return ['status' => 'error', 'message' => 'Format respons dari LLM tidak dikenal.'];
}

// ─────────────────────────────────────────────────
// Helper: Kirim SSE error jika terjadi kegagalan.
// ─────────────────────────────────────────────────
function send_sse_error($msg) {
    if (!headers_sent()) {
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
    }
    echo "event: error\ndata: " . json_encode(['message' => $msg]) . "\n\n";
    if (ob_get_level() > 0) ob_flush(); flush();
}

// ─────────────────────────────────────────────────
// Helper: Serialisasi data dinamis (flat/nested) ke format teks untuk LLM.
// ─────────────────────────────────────────────────
function serialize_raw_data($raw_data) {
    $has_nested = false;
    foreach ($raw_data as $row) {
        if (is_array($row)) {
            foreach ($row as $v) {
                if (is_array($v) || is_object($v)) {
                    $has_nested = true;
                    break 2;
                }
            }
        }
    }

    if ($has_nested) {
        return json_encode($raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $formatted = "";
    foreach ($raw_data as $idx => $row) {
        $num = $idx + 1;
        if (!is_array($row)) {
            $formatted .= "[$num] $row\n";
            continue;
        }
        $line_parts = [];
        foreach ($row as $k => $v) {
            if (is_array($v) || is_object($v)) continue;
            $line_parts[] = "$k: $v";
        }
        $formatted .= "[$num] " . implode(" | ", $line_parts) . "\n";
    }
    return $formatted;
}
?>
