<?php
require_once __DIR__ . '/lz_string.php';

/**
 * BPJS Apotek API Helper Class
 *
 * Mirrors the SIMRS Khanza Java implementation (ApiApotekBPJS.java):
 *  - Signature : Base64( HMAC-SHA256( ConsID . "&" . timestamp, SecretKey ) )
 *  - Decrypt   : AES-256-CBC with key=SHA256(ConsID.SecretKey.timestamp),
 *                IV = first 16 bytes of that hash, then LZ-String URI-safe decompress
 */
class BpjsApiHelper {
    private $consId;
    private $secretKey;
    private $userKey;
    private $baseUrl;

    public function __construct($creds = null) {
        if ($creds === null) {
            $credFile = __DIR__ . '/../bpjs_credential.json';
            if (file_exists($credFile)) {
                $creds = json_decode(file_get_contents($credFile), true) ?: [];
            } else {
                $creds = [];
            }
        }
        $this->consId    = trim($creds['cons_id'] ?? '');
        $this->secretKey = trim($creds['secret_key'] ?? '');
        $this->userKey   = trim($creds['user_key'] ?? '');
        $this->baseUrl   = rtrim(trim($creds['base_url'] ?? 'https://dvlp.bpjs-kesehatan.go.id:9443/api/apotek'), '/');
    }

    public function isConfigured() {
        return !empty($this->consId) && !empty($this->secretKey) && !empty($this->userKey) && !empty($this->baseUrl);
    }

    public function getConsId() { return $this->consId; }
    public function getBaseUrl() { return $this->baseUrl; }

    public function generateSignature($timestamp) {
        $data = $this->consId . "&" . $timestamp;
        return base64_encode(hash_hmac('sha256', $data, $this->secretKey, true));
    }

    /**
     * Decrypt + decompress BPJS `response` payload.
     */
    public function decryptResponse($encryptedData, $timestamp) {
        if (empty($encryptedData) || !is_string($encryptedData)) return null;

        $keyMaterial = $this->consId . $this->secretKey . $timestamp;
        $hashKey = hash('sha256', $keyMaterial, true);
        $iv = substr($hashKey, 0, 16);

        $decrypted = openssl_decrypt(
            base64_decode($encryptedData),
            'AES-256-CBC',
            $hashKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) return null;

        $decompressed = LZString::decompressFromEncodedURIComponent($decrypted);
        if ($decompressed === null || $decompressed === '') return null;

        $json = json_decode($decompressed, true);
        return $json !== null ? $json : $decompressed;
    }

    /**
     * Perform an HTTP request to the BPJS Apotek API.
     * Returns [code, message, data, raw].
     */
    public function request($endpoint, $method = 'GET', $body = null) {
        if (!$this->isConfigured()) {
            throw new Exception("Kredensial BPJS belum dikonfigurasi. Silakan isi di menu Pengaturan BPJS.");
        }

        $timestamp = (string) time();
        $signature = $this->generateSignature($timestamp);
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        $headers = [
            'x-cons-id: ' . $this->consId,
            'x-timestamp: ' . $timestamp,
            'x-signature: ' . $signature,
            'user_key: ' . $this->userKey,
            'Content-Type: ' . (strtoupper($method) === 'GET' ? 'application/json' : 'application/x-www-form-urlencoded'),
            'Accept: application/json'
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        if ($body !== null) {
            $jsonBody = is_array($body) ? json_encode($body, JSON_UNESCAPED_UNICODE) : (string) $body;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }

        $result = json_decode($response, true);
        if (!is_array($result)) {
            throw new Exception("Respon API tidak valid (HTTP $httpCode): " . substr($response, 0, 500));
        }

        $metaCode = isset($result['metaData']['code']) ? (string) $result['metaData']['code'] : '';
        $metaMessage = $result['metaData']['message'] ?? 'Unknown response';

        if (isset($result['response']) && is_string($result['response']) && $result['response'] !== '') {
            $result['response'] = $this->decryptResponse($result['response'], $timestamp);
        }

        return [
            'code'    => $metaCode,
            'message' => $metaMessage,
            'data'    => $result['response'] ?? null,
            'raw'     => $result
        ];
    }
}
?>
