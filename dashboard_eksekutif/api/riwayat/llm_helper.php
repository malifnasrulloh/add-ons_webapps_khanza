<?php
// File: helpers/llm_helper.php
// Helper untuk membaca dan menyimpan pengaturan LLM (AI Assistance)

function get_llm_config() {
    $config_file = dirname(__DIR__, 2) . '/config/llm_config.json';
    
    // Default values
    $defaults = [
        'ai_status' => 'off',
        'api_endpoint' => 'https://rzwe56j.abc-tunnel.us/v1',
        'api_key' => 'sk-2b1cde0813c4b032-mla04u-d5d60bab',
        'model' => 'Flash',
        'fallback_models' => ['Flash', 'ag/gemini-3.5-flash-low', 'ag/gemini-pro-agent', 'Claude'],
        'prompt' => ''
    ];

    if (!file_exists($config_file)) {
        return $defaults;
    }

    $json_data = file_get_contents($config_file);
    $config = json_decode($json_data, true);

    if (!is_array($config)) {
        return $defaults;
    }

    // Merge with defaults to ensure all keys are present
    return array_merge($defaults, $config);
}

function save_llm_config($new_config) {
    $config_file = dirname(__DIR__, 2) . '/config/llm_config.json';
    
    $current_config = get_llm_config();
    $updated_config = array_merge($current_config, $new_config);

    // Validate ai_status
    if (isset($updated_config['ai_status'])) {
        $updated_config['ai_status'] = $updated_config['ai_status'] === 'on' ? 'on' : 'off';
    }

    // Clean fallback models
    if (isset($updated_config['fallback_models'])) {
        if (is_array($updated_config['fallback_models'])) {
            $updated_config['fallback_models'] = array_unique(array_filter(array_map('trim', $updated_config['fallback_models'])));
        } else {
            $updated_config['fallback_models'] = array_unique(array_filter(array_map('trim', explode(',', $updated_config['fallback_models']))));
        }
    }

    // Ensure config directory exists
    $config_dir = dirname($config_file);
    if (!is_dir($config_dir)) {
        mkdir($config_dir, 0755, true);
    }

    $json_data = json_encode($updated_config, JSON_PRETTY_PRINT);
    if (file_put_contents($config_file, $json_data) !== false) {
        return true;
    }
    return false;
}

function is_ai_enabled() {
    $config = get_llm_config();
    return isset($config['ai_status']) && $config['ai_status'] === 'on';
}

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
        error_log("[LLM Fallback E-Dokter] Model $model gagal. Mencoba cadangan...");
    }
    return $last_result;
}

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
        'model' => $model,
        'messages' => array_merge([['role' => 'system', 'content' => $system_prompt]], $messages_array),
        'temperature' => 0.2,
        'stream' => $stream
    ];

    $ch = curl_init($url);
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
            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
                if (!$headers_sent) {
                    header('Content-Type: text/event-stream; charset=utf-8');
                    header('Cache-Control: no-cache');
                    header('Connection: keep-alive');
                    header('X-Accel-Buffering: no');
                    echo "event: metadata\ndata: " . json_encode(['model_used' => $model]) . "\n\n";
                    if (ob_get_level() > 0) ob_flush(); flush();
                    $headers_sent = true;
                }
                echo $chunk;
                if (ob_get_level() > 0) ob_flush(); flush();
            } else {
                $error_buffer .= $chunk;
            }
            return strlen($chunk);
        });

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            return ['status' => 'error', 'message' => 'API mengembalikan HTTP Code ' . $http_code . '. Detail: ' . $error_buffer];
        }
        return ['status' => 'success', 'streamed' => true, 'content' => ''];
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        return ['status' => 'error', 'message' => 'API HTTP Code ' . $http_code . '. Detail: ' . $response];
    }

    $res_data = json_decode($response, true);
    if (isset($res_data['choices'][0]['message']['content'])) {
        return ['status' => 'success', 'content' => trim($res_data['choices'][0]['message']['content'])];
    }

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
                }
            }
        }
        if (!empty($full_text)) return ['status' => 'success', 'content' => trim($full_text)];
    }

    return ['status' => 'error', 'message' => 'Format respons dari LLM tidak dikenal.'];
}

function send_sse_error($msg) {
    if (!headers_sent()) {
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
    }
    echo "event: error\ndata: " . json_encode(['message' => $msg]) . "\n\n";
    if (ob_get_level() > 0) ob_flush(); flush();
}
?>
