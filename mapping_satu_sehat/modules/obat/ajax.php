<?php
/**
 * modules/obat/ajax.php — Backend AJAX Modul Mapping Obat
 * FIX: Hapus LIMIT 100. Gunakan server-side pagination DataTables yang benar.
 * SECURITY: Validasi CSRF untuk semua action write.
 */
error_reporting(0);
ini_set('display_errors', 0);

require_once '../../conf.php';
require_once '../../auth_check.php';
check_module_access('satu_sehat_mapping_obat'); // RBAC Guard

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {

    // ========================================================
    // 1. LOAD TABLE — Server-Side Pagination DataTables
    // ========================================================
    if ($action === 'load_table') {
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

        // Count total records (tanpa filter keyword)
        $stmtTotal = $pdo->query("SELECT COUNT(*) FROM databarang");
        $recordsTotal = (int)$stmtTotal->fetchColumn();

        // Base query
        $sql = "SELECT d.kode_brng, d.nama_brng,
                       m.obat_code, m.obat_display,
                       m.form_code, m.form_display,
                       m.route_code, m.route_display,
                       m.numerator_code, m.numerator_system,
                       m.denominator_code, m.denominator_system
                FROM databarang d
                LEFT JOIN satu_sehat_mapping_obat m ON d.kode_brng = m.kode_brng";

        $params = [];

        // Filter keyword dari tombol 'Tampilkan'
        $where = [];
        if (!empty($keyword)) {
            $where[] = "(d.nama_brng LIKE :k OR d.kode_brng LIKE :k)";
            $params[':k'] = "%$keyword%";
        }

        // Filter dari search box bawaan DataTables
        $dt_search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
        if (!empty($dt_search)) {
            $where[] = "(d.nama_brng LIKE :s OR d.kode_brng LIKE :s OR m.obat_code LIKE :s)";
            $params[':s'] = "%$dt_search%";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        // Count filtered records
        $sqlCount = "SELECT COUNT(*) FROM databarang d
                     LEFT JOIN satu_sehat_mapping_obat m ON d.kode_brng = m.kode_brng";
        if (!empty($where)) {
            $sqlCount .= " WHERE " . implode(' AND ', $where);
        }
        $stmtFiltered = $pdo->prepare($sqlCount);
        $stmtFiltered->execute($params);
        $recordsFiltered = (int)$stmtFiltered->fetchColumn();

        // ORDER BY
        $orderColIndex = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 1;
        $orderDir      = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
        $colMap = [
            0 => 'd.kode_brng',
            1 => 'd.nama_brng',
            2 => 'm.obat_code', // detail
            3 => 'm.obat_code'  // status mapped/belum
        ];
        $orderBy = isset($colMap[$orderColIndex]) ? $colMap[$orderColIndex] : 'd.nama_brng';
        $sql .= " ORDER BY $orderBy $orderDir";

        // Paging — DataTables mengirim 'start' dan 'length'
        $start  = isset($_GET['start'])  ? (int)$_GET['start']  : 0;
        $length = isset($_GET['length']) ? (int)$_GET['length'] : 25;
        if ($length < 1 || $length > 500) $length = 25; // Batas keamanan
        $sql .= " LIMIT :lmt OFFSET :ofs";
        $params[':lmt'] = $length;
        $params[':ofs'] = $start;

        $stmt = $pdo->prepare($sql);
        // Bind integer secara eksplisit agar PDO tidak quote-kan angka
        foreach ($params as $key => $val) {
            if ($key === ':lmt' || $key === ':ofs') {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $val);
            }
        }
        $stmt->execute();

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Status badge
            $status = !empty($row['obat_code'])
                ? '<span class="badge bg-success"><i class="fa fa-check"></i> Mapped</span>'
                : '<span class="badge bg-danger">Belum</span>';

            // Detail mapping
            $info = "";
            if (!empty($row['obat_code'])) {
                $info = "<b>KFA:</b> " . htmlspecialchars($row['obat_code'], ENT_QUOTES, 'UTF-8') . "<br>
                         <small class='text-muted'>" . htmlspecialchars($row['obat_display'], ENT_QUOTES, 'UTF-8') . "</small><br>
                         <b>Route:</b> " . htmlspecialchars($row['route_display'], ENT_QUOTES, 'UTF-8') .
                          " (" . htmlspecialchars($row['route_code'] ?? '', ENT_QUOTES, 'UTF-8') . ") | " .
                         "<b>Unit:</b> " . 
                         (!empty($row['numerator_code']) ? htmlspecialchars($row['numerator_code'], ENT_QUOTES, 'UTF-8') . " / " : "") . 
                         htmlspecialchars($row['denominator_code'] ?? '', ENT_QUOTES, 'UTF-8');
            } else {
                $info = "<small class='text-muted'>- Belum dimapping -</small>";
            }

            $nama_safe = htmlspecialchars($row['nama_brng'], ENT_QUOTES, 'UTF-8');
            $row['nama_brng'] = $nama_safe;

            $btn = "<button class='btn btn-sm btn-primary btn-map'
                    data-json='" . json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) . "'>
                    <i class='fa fa-edit'></i> Mapping</button>";

            $data[] = [
                htmlspecialchars($row['kode_brng'], ENT_QUOTES, 'UTF-8'),
                $nama_safe,
                $info,
                $status,
                $btn
            ];
        }

        echo json_encode([
            "draw"            => isset($_GET['draw']) ? (int)$_GET['draw'] : 1,
            "recordsTotal"    => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data"            => $data
        ]);
        exit;
    }

    // ========================================================
    // 2. SEARCH KFA (untuk Select2 AJAX)
    //    Mode: 'api' = API Kemenkes (real-time, dengan auto-fill)
    //          'database' = database lokal (default lama)
    // ========================================================
    if ($action === 'search_kfa') {
        $q = isset($_GET['term']) ? trim($_GET['term']) : '';

        // Baca mode dari credential JSON
        require_once dirname(__DIR__) . '/kfa_api_helper.php';
        $cred       = kfa_load_credential();
        $searchMode = ($cred && !empty($cred['kfa_search_mode'])) ? $cred['kfa_search_mode'] : 'database';

        // ---- Mode API ----
        $isFallback = false;
        if ($searchMode === 'api' && $cred && !empty($cred['client_id'])) {
            $apiResults = kfa_search_from_api($cred, $q, 20);

            if ($apiResults !== null) {
                // Berhasil dari API — return dengan flag source=api untuk frontend auto-fill
                echo json_encode([
                    'results' => $apiResults,
                    'source'  => 'api'
                ]);
                exit;
            }
            // API gagal setelah retry → catat sebagai fallback
            $isFallback = true;
        }

        // ---- Mode Database (atau fallback dari API) ----
        $stmt = $pdo->prepare(
            "SELECT kfa_code as id,
                    CONCAT(kfa_code, ' - ', display_name) as text,
                    display_name,
                    '' as route_code,
                    '' as route_display,
                    '' as form_code,
                    '' as form_display,
                    '' as ucum_code
             FROM satu_sehat_ref_kfa
             WHERE display_name LIKE :q OR kfa_code LIKE :q
             LIMIT 20"
        );
        $stmt->execute([':q' => "%$q%"]);
        echo json_encode([
            'results' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'source'  => $isFallback ? 'fallback' : 'database'
        ]);
        exit;
    }

    // ========================================================
    // 3. REFRESH KFA TOKEN (resusitasi — force clear cache + request fresh token)
    // ========================================================
    if ($action === 'refresh_kfa_token') {
        validate_csrf();
        require_once dirname(__DIR__) . '/kfa_api_helper.php';
        $cred = kfa_load_credential();
        if (!$cred || empty($cred['client_id']) || empty($cred['client_secret'])) {
            echo json_encode(['status' => 'error', 'message' => 'Credential belum dikonfigurasi oleh Super Admin.']);
            exit;
        }
        // Hapus token cache lama
        unset($_SESSION[KFA_SESSION_TOKEN_KEY], $_SESSION[KFA_SESSION_EXPIRY_KEY]);
        // Request token baru (bukan via cache)
        $urls  = kfa_get_base_urls($cred);
        $token = kfa_fetch_token($cred, $urls);
        if ($token === null) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mendapatkan token baru dari API Kemenkes. Periksa koneksi internet server dan credential di Setting SatuSehat.']);
            exit;
        }
        $_SESSION[KFA_SESSION_TOKEN_KEY]  = $token;
        $_SESSION[KFA_SESSION_EXPIRY_KEY] = time() + (55 * 60);
        echo json_encode(['status' => 'success', 'message' => 'Token KFA berhasil diperbarui! Silakan cari obat kembali.']);
        exit;
    }

    // ========================================================
    // 4. SAVE MAPPING (POST) — Validasi CSRF wajib
    // ========================================================
    if ($action === 'save_mapping') {
        validate_csrf(); // Terminate 403 jika CSRF tidak cocok

        $kode_brng   = trim($_POST['kode_brng']   ?? '');
        $kfa_code    = trim($_POST['kfa_code']    ?? '');
        $kfa_display = trim($_POST['kfa_display_hidden'] ?? '');
        if (empty($kfa_display)) $kfa_display = trim($_POST['kfa_display_manual'] ?? '');

        $form_raw    = explode('|', $_POST['form_code']  ?? '|');
        $form_code   = $form_raw[0];
        $form_display= $form_raw[1] ?? '';

        $route_raw   = explode('|', $_POST['route_code'] ?? '|');
        $route_code  = $route_raw[0];
        $route_display = $route_raw[1] ?? '';

        // --- Logic Satuan (Numerator & Denominator) ---
        $num_code   = trim($_POST['numerator_code'] ?? '');
        $den_code   = trim($_POST['denominator_code'] ?? '');
        
        // Numerator selalu UCUM
        $num_system = 'http://unitsofmeasure.org';
        
        // Denominator: Bisa UCUM (cair) atau HL7 DrugForm (padat)
        $den_system = 'http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm';
        
        // Cek DB untuk kepastian (apakah UCUM?)
        $c_num = $pdo->prepare("SELECT code FROM satu_sehat_ref_numerator WHERE code = ?");
        $c_num->execute([$den_code]);
        if ($c_num->fetch()) {
            $den_system = 'http://unitsofmeasure.org';
        } else {
            // Cek apakah HL7 DrugForm
            $c_den = $pdo->prepare("SELECT code FROM satu_sehat_ref_denominator WHERE code = ?");
            $c_den->execute([$den_code]);
            if ($c_den->fetch()) {
                $den_system = 'http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm';
            }
        }

        if (empty($kode_brng) || empty($kfa_code)) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
            exit;
        }

        $sql = "INSERT INTO satu_sehat_mapping_obat
                (kode_brng, obat_code, obat_system, obat_display,
                 form_code, form_system, form_display,
                 route_code, route_system, route_display,
                 numerator_code, numerator_system,
                 denominator_code, denominator_system)
                VALUES (?, ?, 'http://sys-ids.kemkes.go.id/kfa', ?,
                        ?, ?, ?,
                        ?, ?, ?,
                        ?, ?,
                        ?, ?)
                ON DUPLICATE KEY UPDATE
                obat_code=?, obat_display=?,
                form_code=?, form_display=?,
                route_code=?, route_display=?,
                numerator_code=?, numerator_system=?,
                denominator_code=?, denominator_system=?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $kode_brng, $kfa_code, $kfa_display,
            $form_code, "http://terminology.kemkes.go.id/CodeSystem/medication-form", $form_display,
            $route_code, "http://www.whocc.no/atc", $route_display,
            $num_code, $num_system,
            $den_code, $den_system,
            // ON DUPLICATE KEY UPDATE
            $kfa_code, $kfa_display,
            $form_code, $form_display,
            $route_code, $route_display,
            $num_code, $num_system,
            $den_code, $den_system
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Mapping berhasil disimpan!']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["draw" => 1, "recordsTotal" => 0, "recordsFiltered" => 0, "data" => [], "error" => $e->getMessage()]);
}
?>
