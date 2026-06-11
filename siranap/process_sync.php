<?php
require_once 'config.php';
header('Content-Type: application/json');

// $kemkes_id and $kemkes_pass are now from config.php
$url = "https://sirs.kemkes.go.id/fo/index.php/Fasyankes";

$force = isset($_GET['force']) && ($_GET['force'] === 'true' || $_GET['force'] === '1');

$logs = [];
$changesCount = 0;
$getDebug = null;

try {
    // 1. Fetch current status of beds from Kemenkes (source of truth)
    $dt = new DateTime(null, new DateTimeZone("UTC"));
    $timestamp = $dt->getTimestamp();

    $headers = [
        "X-rs-id: " . $kemkes_id,
        "X-Timestamp: " . $timestamp,
        "X-pass: " . $kemkes_pass,
        "Content-type: application/json"
    ];

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);

    $responseRaw = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);

    // Save debug info for the GET request
    $kemenkesResponse = json_decode($responseRaw, true);
    $kemenkesBeds = $kemenkesResponse['fasyankes'] ?? [];
    $getDebug = [
        'url' => $url,
        'headers' => $headers,
        'http_code' => $httpcode,
        'response' => $responseRaw,
        'curl_error' => $curl_error,
        'count' => is_array($kemenkesBeds) ? count($kemenkesBeds) : 0
    ];

    if ($httpcode < 200 || $httpcode >= 300) {
        throw new Exception("Gagal mengambil data dari Kemenkes. HTTP Code: $httpcode. Error: $curl_error.");
    }

    if (!is_array($kemenkesBeds)) {
        throw new Exception("Format data dari Kemenkes tidak valid (bukan JSON array).");
    }

    // Map existing Kemenkes entries: key (id_tt_ruang_covid) -> detail
    $kemenkesState = [];
    foreach ($kemenkesBeds as $bed) {
        if (!empty($bed['id_t_tt']) && $bed['ruang'] !== null) {
            $key = trim($bed['id_tt']) . '_' . trim($bed['ruang']) . '_' . trim($bed['covid']);
            $kemenkesState[$key] = [
                'id_t_tt' => $bed['id_t_tt'],
                'jumlah' => $bed['jumlah'],
                'terpakai' => $bed['terpakai']
            ];
        }
    }

    // 2. Fetch current status of beds from local DB (per bangsal, using bangsal name as 'ruang')
    $sql = "SELECT 
                A.id_tt_sirsonline as id_tt,
                A.nm_ruang_sirsonline as kelas,
                A.kd_bangsal,
                C.nm_bangsal as ruang,
                A.covid,
                COUNT(B.kd_kamar) AS jumlah,
                SUM(if (B.`status`='ISI',1,0)) as terpakai
            FROM sirsonline_ketersediaan_kamar A
            JOIN bangsal C ON A.kd_bangsal = C.kd_bangsal
            LEFT JOIN kamar B ON A.kd_bangsal = B.kd_bangsal AND B.statusdata = '1' AND (
                (A.nm_ruang_sirsonline = 'VVIP' AND B.kelas = 'Kelas VVIP') OR
                (A.nm_ruang_sirsonline = 'VIP' AND B.kelas = 'Kelas VIP') OR
                (A.nm_ruang_sirsonline = 'Kelas Utama' AND B.kelas = 'Kelas Utama') OR
                (A.nm_ruang_sirsonline = 'Kelas I' AND B.kelas = 'Kelas 1') OR
                (A.nm_ruang_sirsonline = 'Kelas II' AND B.kelas = 'Kelas 2') OR
                (A.nm_ruang_sirsonline = 'Kelas III' AND B.kelas = 'Kelas 3') OR
                (A.nm_ruang_sirsonline NOT IN ('VVIP', 'VIP', 'Kelas Utama', 'Kelas I', 'Kelas II', 'Kelas III'))
            )
            GROUP BY A.id_tt_sirsonline, A.nm_ruang_sirsonline, A.kd_bangsal, A.covid";
            
    $stmt = $pdo->query($sql);
    $currentData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $currentStateKeys = [];
    $toSync = [];

    // 3. Identify additions (POST) and updates (PUT)
    foreach ($currentData as $row) {
        $id_tt = trim($row['id_tt']);
        $ruang = trim($row['ruang']);
        $covid = trim($row['covid']);
        $key = $id_tt . '_' . $ruang . '_' . $covid;
        $currentStateKeys[] = $key;
        
        $jumlah = (int)$row['jumlah'];
        $terpakai = (int)($row['terpakai'] ?? 0);
        
        // Calculate covid split
        $terpakai_suspek = "0";
        $terpakai_konfirmasi = "0";
        if ($covid === '1') {
            $terpakai_konfirmasi = (string)$terpakai;
        }
        
        $data = [
            'ruang' => $ruang,
            'jumlah_ruang' => "1",
            'jumlah' => (string)$jumlah,
            'terpakai' => (string)$terpakai,
            'terpakai_suspek' => $terpakai_suspek,
            'terpakai_konfirmasi' => $terpakai_konfirmasi,
            'antrian' => "0",
            'prepare' => "0",
            'prepare_plan' => "0",
            'covid' => (int)$covid,
            'terpakai_dbd' => "0",
            'terpakai_dbd_anak' => "0",
            'jumlah_dbd' => "0"
        ];

        if (!isset($kemenkesState[$key])) {
            // New entry -> POST (needs id_tt)
            $data['id_tt'] = $id_tt;
            $toSync[] = [
                'method' => 'POST',
                'data' => $data,
                'key' => $key,
                'label' => "$id_tt ($ruang)"
            ];
        } else {
            // Existing entry -> PUT (needs id_t_tt instead of id_tt)
            $id_t_tt = $kemenkesState[$key]['id_t_tt'];
            $data['id_t_tt'] = $id_t_tt;
            
            $kemenkesJumlah = (int)$kemenkesState[$key]['jumlah'];
            $kemenkesTerpakai = (int)$kemenkesState[$key]['terpakai'];
            
            // Sync if counts changed OR if we are forcing sync (sapu bersih)
            if ($force || $kemenkesJumlah !== $jumlah || $kemenkesTerpakai !== $terpakai) {
                $toSync[] = [
                    'method' => 'PUT',
                    'data' => $data,
                    'key' => $key,
                    'label' => "$id_tt ($ruang) [ID: $id_t_tt]"
                ];
            }
        }
    }

    // 4. Identify Removals (DELETE)
    foreach ($kemenkesState as $key => $kState) {
        if (!in_array($key, $currentStateKeys)) {
            $id_t_tt = $kState['id_t_tt'];
            $parts = explode('_', $key);
            $id_tt = $parts[0] ?? '';
            $ruang = $parts[1] ?? '';
            
            $toSync[] = [
                'method' => 'DELETE',
                'data' => ['id_t_tt' => $id_t_tt],
                'key' => $key,
                'label' => "$id_tt ($ruang) [ID: $id_t_tt]"
            ];
        }
    }

    // 5. Execute requests
    foreach ($toSync as $item) {
        $method = $item['method'];
        $data = $item['data'];
        $key = $item['key'];

        $dt = new DateTime(null, new DateTimeZone("UTC"));
        $timestamp = $dt->getTimestamp();

        $postdata = json_encode($data);
        $headers = [
            "X-rs-id: ".$kemkes_id,
            "X-Timestamp: ".$timestamp,
            "X-pass: ".$kemkes_pass,
            "Content-type: application/json",
            "Content-length: ".strlen($postdata)
        ];

        $curl = curl_init();			
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);
        
        $isSuccess = ($httpcode >= 200 && $httpcode < 300);
        if ($isSuccess) {
            $changesCount++;
            
            // Log to trackersql (only successful operations)
            $payloadLog = "SUCCESS: " . $method . " " . $url . " Payload: " . $postdata . " Response: " . $response;
            $stmtLog = $pdo->prepare("INSERT INTO trackersql (tanggal, sqle, usere) VALUES (?, ?, ?)");
            $stmtLog->execute([date('Y-m-d H:i:s'), $payloadLog, 'auto_sync']);
        }
        
        $logs[] = [
            'method' => $method,
            'label' => $item['label'],
            'url' => $url,
            'headers' => $headers,
            'payload' => $postdata,
            'http_code' => $httpcode,
            'response' => $response,
            'curl_error' => $curl_error,
            'status' => $isSuccess ? 'SUCCESS' : 'FAILED'
        ];
    }

    echo json_encode([
        'status' => 'success',
        'changes' => $changesCount,
        'get_debug' => $getDebug,
        'logs' => $logs
    ]);

} catch (\Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage(),
        'get_debug' => $getDebug,
        'logs' => $logs
    ]);
}
?>
