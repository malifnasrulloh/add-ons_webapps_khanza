<?php
// helpers/debug_helper.php
// Helper untuk debug logging — hanya aktif jika DEBUG_MODE = true di config.php

if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);

// Global log storage (per-request, tidak persisten)
if (!isset($GLOBALS['_debug_logs'])) {
    $GLOBALS['_debug_logs'] = [];
}

/**
 * Tulis satu log entry.
 * @param string $tag     Label singkat, misal: "rawat_inap_dr"
 * @param string $level   'info' | 'success' | 'warning' | 'error'
 * @param string $message Pesan deskriptif
 * @param mixed  $extra   Data tambahan (array, Exception, dll.) — opsional
 */
function dbg_log(string $tag, string $level, string $message, $extra = null): void {
    if (!DEBUG_MODE) return;
    $entry = [
        'time'    => date('H:i:s'),
        'tag'     => $tag,
        'level'   => $level, // 'info' | 'success' | 'warning' | 'error'
        'message' => $message,
    ];
    if ($extra !== null) {
        if ($extra instanceof Exception) {
            $entry['extra'] = $extra->getMessage();
        } elseif (is_array($extra) || is_scalar($extra)) {
            $entry['extra'] = $extra;
        }
    }
    $GLOBALS['_debug_logs'][] = $entry;
}

/**
 * Ambil semua log yang sudah dikumpulkan.
 */
function dbg_get_logs(): array {
    return $GLOBALS['_debug_logs'] ?? [];
}

/**
 * Eksekusi query PDO dengan debug logging terintegrasi.
 * Return: array of rows, atau [] jika error.
 */
function dbg_query(PDO $pdo, string $tag, string $sql, array $params, string &$errorOut = ''): array {
    $errorOut = ''; // Reset error sebelum eksekusi baru
    if (DEBUG_MODE) {
        // Tampilkan sebagian SQL (hapus whitespace berlebih)
        $sqlShort = preg_replace('/\s+/', ' ', trim($sql));
        $sqlPreview = substr($sqlShort, 0, 120) . (strlen($sqlShort) > 120 ? '...' : '');
        dbg_log($tag, 'info', "Query: $sqlPreview");
    }
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (DEBUG_MODE) {
            dbg_log($tag, 'success', count($rows) . ' baris ditemukan');
        }
        return $rows;
    } catch (Exception $e) {
        $errorOut = $e->getMessage();
        if (DEBUG_MODE) {
            dbg_log($tag, 'error', 'SQL Error: ' . $e->getMessage());
        }
        return [];
    }
}
