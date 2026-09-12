<?php
/**
 * modules/obat/ajax.php — Backend AJAX Modul Mapping Obat & Alkes (KFA Satu Sehat)
 * Mendukung KFA v2 (Farmasi) dan KFA v3 (Alat Kesehatan / Alkes)
 * Keamanan: Validasi CSRF untuk semua write actions, RBAC guard.
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
        $keyword         = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $filter_kategori = isset($_GET['filter_kategori']) ? trim($_GET['filter_kategori']) : 'semua';

        // Count total records aktif
        $stmtTotal = $pdo->query("SELECT COUNT(*) FROM databarang WHERE status = '1'");
        $recordsTotal = (int)$stmtTotal->fetchColumn();

        // Base query
        $sql = "SELECT d.kode_brng, d.nama_brng, d.kdjns, d.kode_golongan,
                       m.obat_code, m.obat_display,
                       m.form_code, m.form_display,
                       m.route_code, m.route_display,
                       m.numerator_code, m.numerator_system,
                       m.denominator_code, m.denominator_system,
                       a.nie, a.manufacturer, a.nama_dagang, a.product_template_code, a.product_template_name,
                       CASE
                           WHEN a.kfa_code IS NOT NULL
                                OR (m.obat_code IS NOT NULL
                                    AND (m.form_code IS NULL OR m.form_code = '')
                                    AND (m.route_code IS NULL OR m.route_code = ''))
                           THEN 1 ELSE 0
                       END AS is_alkes
                FROM databarang d
                LEFT JOIN satu_sehat_mapping_obat m ON d.kode_brng = m.kode_brng
                LEFT JOIN satu_sehat_ref_alkes a ON m.obat_code = a.kfa_code";

        $params = [];
        $where  = ["d.status = '1'"];

        // Filter Kategori (Semua, Obat, Alkes)
        if ($filter_kategori === 'alkes') {
            $where[] = "(d.kdjns IN ('JB03', 'JB04') OR d.kode_golongan IN ('G08', 'G10') OR (m.form_code IS NULL AND m.obat_code IS NOT NULL AND (m.route_code IS NULL OR m.route_code = '')))";
        } elseif ($filter_kategori === 'obat') {
            $where[] = "((d.kdjns NOT IN ('JB03', 'JB04') OR d.kdjns IS NULL) AND (d.kode_golongan NOT IN ('G08', 'G10') OR d.kode_golongan IS NULL) AND NOT (m.form_code IS NULL AND m.obat_code IS NOT NULL AND (m.route_code IS NULL OR m.route_code = '')))";
        }

        // Filter keyword dari tombol 'Tampilkan' (Kode RS, Nama RS, Kode KFA, Nama KFA)
        if (!empty($keyword)) {
            $where[] = "(d.nama_brng LIKE :k OR d.kode_brng LIKE :k OR m.obat_code LIKE :k OR m.obat_display LIKE :k)";
            $params[':k'] = "%$keyword%";
        }

        // Filter dari search box DataTables
        $dt_search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
        if (!empty($dt_search)) {
            $where[] = "(d.nama_brng LIKE :s OR d.kode_brng LIKE :s OR m.obat_code LIKE :s OR m.obat_display LIKE :s)";
            $params[':s'] = "%$dt_search%";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        // Count filtered records
        $sqlCount = "SELECT COUNT(*) FROM databarang d
                     LEFT JOIN satu_sehat_mapping_obat m ON d.kode_brng = m.kode_brng
                     LEFT JOIN satu_sehat_ref_alkes a ON m.obat_code = a.kfa_code";
        if (!empty($where)) {
            $sqlCount .= " WHERE " . implode(' AND ', $where);
        }
        $stmtFiltered = $pdo->prepare($sqlCount);
        $stmtFiltered->execute($params);
        $recordsFiltered = (int)$stmtFiltered->fetchColumn();

        // Sorting kolom dinamis
        $orderColIndex = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 1;
        $orderDir      = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
        $colMap = [
            0 => 'd.kode_brng',
            1 => 'd.nama_brng',
            2 => 'm.obat_code',
            3 => 'm.obat_code'
        ];
        $orderBy = isset($colMap[$orderColIndex]) ? $colMap[$orderColIndex] : 'd.nama_brng';
        $sql .= " ORDER BY $orderBy $orderDir";

        // Paging DataTables
        $start  = isset($_GET['start'])  ? (int)$_GET['start']  : 0;
        $length = isset($_GET['length']) ? (int)$_GET['length'] : 25;
        if ($length < 1 || $length > 500) $length = 25;
        $sql .= " LIMIT :lmt OFFSET :ofs";
        $params[':lmt'] = $length;
        $params[':ofs'] = $start;

        $stmt = $pdo->prepare($sql);
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
            $status = !empty($row['obat_code'])
                ? '<span class="badge bg-success"><i class="fa fa-check me-1"></i>Mapped</span>'
                : '<span class="badge bg-danger">Belum</span>';

            $is_alkes = !empty($row['is_alkes']);
            $info = "";

            if (!empty($row['obat_code'])) {
                if ($is_alkes) {
                    $info = "<span class='badge bg-warning text-dark me-1'><i class='fa fa-stethoscope me-1'></i>ALKES (v3)</span> " .
                            "<b>KFA:</b> " . htmlspecialchars($row['obat_code'], ENT_QUOTES, 'UTF-8') . "<br>" .
                            "<small class='text-muted'>" . htmlspecialchars($row['obat_display'] ?? '', ENT_QUOTES, 'UTF-8') . "</small>";
                    if (!empty($row['nie'])) {
                        $info .= "<br><span class='badge bg-light text-dark border me-1'><i class='fa fa-id-card me-1'></i>NIE: " . htmlspecialchars($row['nie'], ENT_QUOTES, 'UTF-8') . "</span>";
                    }
                    if (!empty($row['manufacturer'])) {
                        $info .= "<span class='badge bg-light text-dark border'><i class='fa fa-industry me-1'></i>" . htmlspecialchars($row['manufacturer'], ENT_QUOTES, 'UTF-8') . "</span>";
                    }
                } else {
                    $info = "<span class='badge bg-primary me-1'><i class='fa fa-pills me-1'></i>OBAT (v2)</span> " .
                            "<b>KFA:</b> " . htmlspecialchars($row['obat_code'], ENT_QUOTES, 'UTF-8') . "<br>" .
                            "<small class='text-muted'>" . htmlspecialchars($row['obat_display'] ?? '', ENT_QUOTES, 'UTF-8') . "</small><br>" .
                            "<b>Route:</b> " . htmlspecialchars($row['route_display'] ?: '-', ENT_QUOTES, 'UTF-8') .
                            " (" . htmlspecialchars($row['route_code'] ?: '-', ENT_QUOTES, 'UTF-8') . ") | " .
                            "<b>Unit:</b> " .
                            (!empty($row['numerator_code']) ? htmlspecialchars($row['numerator_code'], ENT_QUOTES, 'UTF-8') . " / " : "") .
                            htmlspecialchars($row['denominator_code'] ?: '-', ENT_QUOTES, 'UTF-8');
                }
            } else {
                $info = "<small class='text-muted'>- Belum dimapping -</small>";
            }

            $nama_safe = htmlspecialchars($row['nama_brng'], ENT_QUOTES, 'UTF-8');
            $row['nama_brng'] = $nama_safe;

            $btn = "<button class='btn btn-sm btn-primary btn-map'
                    data-json='" . json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) . "'>
                    <i class='fa fa-edit me-1'></i>Mapping</button>";

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
    // 2. SEARCH KFA (Select2 AJAX)
    //    Mendukung product_type: 'farmasi' (KFA v2) dan 'alkes' (KFA v3)
    //    Mode: 'api' = API Kemenkes (real-time)
    //          'database' = database lokal
    // ========================================================
    if ($action === 'search_kfa') {
        $q            = isset($_GET['term']) ? trim($_GET['term']) : '';
        $product_type = isset($_GET['product_type']) ? strtolower(trim($_GET['product_type'])) : 'farmasi';
        $templateOnly = !empty($_GET['template_only']) && ($_GET['template_only'] === '1' || $_GET['template_only'] === 'true');

        require_once dirname(__DIR__) . '/kfa_api_helper.php';
        $cred       = kfa_load_credential();
        $searchMode = ($cred && !empty($cred['kfa_search_mode'])) ? $cred['kfa_search_mode'] : 'database';

        $isFallback = false;

        // ----------------------------------------------------
        // A. ALKES SEARCH (KFA v3)
        // ----------------------------------------------------
        if ($product_type === 'alkes') {
            if ($searchMode === 'api' && $cred && !empty($cred['client_id'])) {
                $apiResults = kfa_v3_search_alkes($cred, $q, 20, $templateOnly);
                if ($apiResults !== null) {
                    // Auto-cache ke satu_sehat_ref_alkes (prepare sekali di luar loop)
                    try {
                        $stmtCache = $pdo->prepare("
                            INSERT INTO satu_sehat_ref_alkes
                            (kfa_code, display_name, nama_dagang, nie, manufacturer, product_template_code, product_template_name, farmalkes_type)
                            VALUES (:kfa, :disp, :dag, :nie, :mfg, :ptc, :ptn, :ft)
                            ON DUPLICATE KEY UPDATE display_name=:disp2, nama_dagang=:dag2, nie=:nie2, manufacturer=:mfg2
                        ");
                        foreach ($apiResults as $item) {
                            if (!empty($item['id']) && !empty($item['display_name'])) {
                                $stmtCache->execute([
                                    ':kfa'  => $item['id'],
                                    ':disp' => $item['display_name'],
                                    ':dag'  => $item['nama_dagang'] ?? '',
                                    ':nie'  => $item['nie'] ?? '',
                                    ':mfg'  => $item['manufacturer'] ?? '',
                                    ':ptc'  => $item['product_template_code'] ?? '',
                                    ':ptn'  => $item['product_template_name'] ?? '',
                                    ':ft'   => $item['farmalkes_type'] ?? 'device',
                                    ':disp2'=> $item['display_name'],
                                    ':dag2' => $item['nama_dagang'] ?? '',
                                    ':nie2' => $item['nie'] ?? '',
                                    ':mfg2' => $item['manufacturer'] ?? '',
                                ]);
                            }
                        }
                    } catch (Exception $e) {}

                    echo json_encode([
                        'results'      => $apiResults,
                        'source'       => 'api',
                        'product_type' => 'alkes',
                        'pagination'   => ['more' => false]
                    ]);
                    exit;
                }
                $isFallback = true;
            }

            // Local Database fallback untuk Alkes
            $page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit  = 50;
            $offset = ($page - 1) * $limit;

            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM satu_sehat_ref_alkes WHERE display_name LIKE :q OR kfa_code LIKE :q OR nama_dagang LIKE :q OR nie LIKE :q");
            $stmtCount->execute([':q' => "%$q%"]);
            $total = (int)$stmtCount->fetchColumn();
            $more  = ($offset + $limit) < $total;

            $stmt = $pdo->prepare("
                SELECT kfa_code as id,
                       CONCAT(kfa_code, ' — ', display_name, IF(nie != '' AND nie IS NOT NULL, CONCAT(' [NIE: ', nie, ']'), ''), IF(manufacturer != '' AND manufacturer IS NOT NULL, CONCAT(' (', manufacturer, ')'), '')) as text,
                       display_name,
                       nama_dagang,
                       nie,
                       manufacturer,
                       product_template_code,
                       product_template_name,
                       farmalkes_type,
                       'alkes' as product_type,
                       '' as route_code,
                       '' as route_display,
                       '' as form_code,
                       '' as form_display,
                       '' as ucum_code
                FROM satu_sehat_ref_alkes
                WHERE display_name LIKE :q OR kfa_code LIKE :q OR nama_dagang LIKE :q OR nie LIKE :q
                ORDER BY CASE WHEN kfa_code = :exact THEN 0 ELSE 1 END, display_name ASC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':exact', $q);
            $stmt->bindValue(':q', "%$q%");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $parsedRows = [];
            foreach ($rawRows as $rw) {
                $p = kfa_parse_alkes_details($rw['display_name'], $rw['product_template_code'] ?? '', $rw['product_template_name'] ?? '');
                $rw['route_code']        = $p['route_code'];
                $rw['route_display']     = $p['route_display'];
                $rw['form_code']         = $p['form_code'];
                $rw['form_display']      = $p['form_display'];
                $rw['form_system']       = $p['form_system'];
                $rw['numerator_code']    = $p['numerator_code'];
                $rw['denominator_code']  = $p['denominator_code'];
                $rw['ucum_code']         = $p['numerator_code'];
                $parsedRows[] = $rw;
            }

            echo json_encode([
                'results'      => $parsedRows,
                'source'       => $isFallback ? 'fallback' : 'database',
                'product_type' => 'alkes',
                'pagination'   => ['more' => $more]
            ]);
            exit;
        }

        // ----------------------------------------------------
        // B. OBAT / FARMASI SEARCH (KFA v2)
        // ----------------------------------------------------
        if ($searchMode === 'api' && $cred && !empty($cred['client_id'])) {
            $apiResults = kfa_search_from_api($cred, $q, 20);

            if ($apiResults !== null) {
                echo json_encode([
                    'results'      => $apiResults,
                    'source'       => 'api',
                    'product_type' => 'farmasi',
                    'pagination'   => ['more' => false]
                ]);
                exit;
            }
            $isFallback = true;
        }

        $page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit  = 50;
        $offset = ($page - 1) * $limit;

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM satu_sehat_ref_kfa WHERE display_name LIKE :q OR kfa_code LIKE :q");
        $stmtCount->execute([':q' => "%$q%"]);
        $total = (int)$stmtCount->fetchColumn();
        $more  = ($offset + $limit) < $total;

        $stmt = $pdo->prepare("
            SELECT kfa_code as id,
                   CONCAT(kfa_code, ' - ', display_name) as text,
                   display_name,
                   'farmasi' as product_type,
                   '' as route_code,
                   '' as route_display,
                   '' as form_code,
                   '' as form_display,
                   '' as ucum_code
            FROM satu_sehat_ref_kfa
            WHERE display_name LIKE :q OR kfa_code LIKE :q
            ORDER BY
                CASE WHEN kfa_code = :exact THEN 0 ELSE 1 END,
                display_name ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':exact', $q);
        $stmt->bindValue(':q', "%$q%");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode([
            'results'      => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'source'       => $isFallback ? 'fallback' : 'database',
            'product_type' => 'farmasi',
            'pagination'   => ['more' => $more]
        ]);
        exit;
    }

    // ========================================================
    // 3. REFRESH KFA TOKEN (resusitasi cache token)
    // ========================================================
    if ($action === 'refresh_kfa_token') {
        validate_csrf();
        require_once dirname(__DIR__) . '/kfa_api_helper.php';
        $cred = kfa_load_credential();
        if (!$cred || empty($cred['client_id']) || empty($cred['client_secret'])) {
            echo json_encode(['status' => 'error', 'message' => 'Credential belum dikonfigurasi oleh Super Admin.']);
            exit;
        }
        unset($_SESSION[KFA_SESSION_TOKEN_KEY], $_SESSION[KFA_SESSION_EXPIRY_KEY]);
        $urls  = kfa_get_base_urls($cred);
        $token = kfa_fetch_token($cred, $urls);
        if ($token === null) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mendapatkan token baru dari API Kemenkes. Periksa koneksi internet server dan credential di Setting SatuSehat.']);
            exit;
        }
        $_SESSION[KFA_SESSION_TOKEN_KEY]  = $token;
        $_SESSION[KFA_SESSION_EXPIRY_KEY] = time() + (55 * 60);
        echo json_encode(['status' => 'success', 'message' => 'Token KFA berhasil diperbarui! Silakan cari produk kembali.']);
        exit;
    }

    // ========================================================
    // 3.1. GET DRUG DETAIL (Auto-fill data dari API KFA Detail)
    // ========================================================
    if ($action === 'get_drug_detail') {
        $kfa_code = trim($_GET['kfa_code'] ?? '');
        if (empty($kfa_code)) {
            echo json_encode(['status' => 'error', 'message' => 'Kode KFA kosong.']);
            exit;
        }
        require_once dirname(__DIR__) . '/kfa_api_helper.php';
        $cred = kfa_load_credential();
        $detail = kfa_get_detail_from_api($cred, $kfa_code);
        if ($detail) {
            $parsed = kfa_parse_drug_details($detail);
            echo json_encode(['status' => 'success', 'data' => $parsed]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Detail produk KFA tidak ditemukan.']);
        }
        exit;
    }

    // ========================================================
    // 4. SAVE MAPPING (POST) — Validasi CSRF wajib
    // ========================================================
    if ($action === 'save_mapping') {
        validate_csrf();

        $kode_brng    = trim($_POST['kode_brng']    ?? '');
        $mapping_type = trim($_POST['mapping_type'] ?? 'obat'); // 'obat' | 'alkes'
        $sync_vaksin  = !empty($_POST['sync_vaksin']) && ($_POST['sync_vaksin'] == '1' || $_POST['sync_vaksin'] === true || $_POST['sync_vaksin'] === 'true');

        $kfa_code    = trim($_POST['kfa_code']    ?? '');
        $kfa_display = trim($_POST['kfa_display_hidden'] ?? '');
        if (empty($kfa_display)) $kfa_display = trim($_POST['kfa_display_manual'] ?? '');

        if (empty($kode_brng) || empty($kfa_code)) {
            echo json_encode(['status' => 'error', 'message' => 'Kode barang dan Kode KFA wajib diisi.']);
            exit;
        }

        // ----------------------------------------------------
        // A. ALKES SAVE
        // ----------------------------------------------------
        if ($mapping_type === 'alkes') {
            $nie  = trim($_POST['alkes_nie'] ?? '');
            $mfg  = trim($_POST['alkes_manufacturer'] ?? '');
            $ptc  = trim($_POST['alkes_template_code'] ?? '');
            $ptn  = trim($_POST['alkes_template_name'] ?? '');
            $farm = trim($_POST['alkes_farmalkes_type'] ?? 'device');

            // 1. Bentuk Sediaan (Form) untuk Alkes
            $form_raw     = explode('|', $_POST['form_code'] ?? '|');
            $form_code    = !empty($form_raw[0]) ? $form_raw[0] : (!empty($ptc) ? $ptc : 'ALKES');
            $form_display = !empty($form_raw[1]) ? $form_raw[1] : (!empty($ptn) ? $ptn : 'Alat Kesehatan');
            $form_system  = 'http://sys-ids.kemkes.go.id/kfa';
            if (strpos($form_code, 'BS') === 0) {
                $form_system = 'http://terminology.kemkes.go.id/CodeSystem/medication-form';
            }

            // 2. Rute Pemberian (Route) untuk Alkes
            $route_raw     = explode('|', $_POST['route_code'] ?? '|');
            $route_code    = !empty($route_raw[0]) ? $route_raw[0] : null;
            $route_display = !empty($route_raw[1]) ? $route_raw[1] : null;
            $route_system  = $route_code ? 'http://www.whocc.no/atc' : null;

            // 3. Numerator & Denominator untuk Alkes
            $num_code   = !empty(trim($_POST['numerator_code'] ?? '')) ? trim($_POST['numerator_code']) : '1';
            $num_system = 'http://unitsofmeasure.org';

            $den_code   = !empty(trim($_POST['denominator_code'] ?? '')) ? trim($_POST['denominator_code']) : 'PCS';
            $den_system = in_array(strtolower($den_code), ['ml', 'l', 'mg', 'g'])
                ? 'http://unitsofmeasure.org'
                : 'http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm';

            // Simpan ke satu_sehat_mapping_obat dengan kolom terisi
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
                    form_code=?, form_system=?, form_display=?,
                    route_code=?, route_system=?, route_display=?,
                    numerator_code=?, numerator_system=?,
                    denominator_code=?, denominator_system=?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $kode_brng, $kfa_code, $kfa_display,
                $form_code, $form_system, $form_display,
                $route_code, $route_system, $route_display,
                $num_code, $num_system,
                $den_code, $den_system,
                // ON DUPLICATE KEY UPDATE
                $kfa_code, $kfa_display,
                $form_code, $form_system, $form_display,
                $route_code, $route_system, $route_display,
                $num_code, $num_system,
                $den_code, $den_system
            ]);

            // Cache juga ke satu_sehat_ref_alkes
            try {
                $stmtCache = $pdo->prepare("
                    INSERT INTO satu_sehat_ref_alkes
                    (kfa_code, display_name, nama_dagang, nie, manufacturer, product_template_code, product_template_name, farmalkes_type)
                    VALUES (:kfa, :disp, :dag, :nie, :mfg, :ptc, :ptn, :ft)
                    ON DUPLICATE KEY UPDATE display_name=:disp2, nie=:nie2, manufacturer=:mfg2
                ");
                $stmtCache->execute([
                    ':kfa'  => $kfa_code,
                    ':disp' => $kfa_display,
                    ':dag'  => $kfa_display,
                    ':nie'  => $nie,
                    ':mfg'  => $mfg,
                    ':ptc'  => $ptc,
                    ':ptn'  => $ptn,
                    ':ft'   => $farm ?: 'device',
                    ':disp2'=> $kfa_display,
                    ':nie2' => $nie,
                    ':mfg2' => $mfg
                ]);
            } catch (Exception $e) {}

            echo json_encode(['status' => 'success', 'message' => 'Mapping Alat Kesehatan (Alkes) berhasil disimpan!']);
            exit;
        }

        // ----------------------------------------------------
        // B. OBAT / FARMASI SAVE
        // ----------------------------------------------------
        $form_raw    = explode('|', $_POST['form_code']  ?? '|');
        $form_code   = $form_raw[0];
        $form_display= $form_raw[1] ?? '';

        $route_raw   = explode('|', $_POST['route_code'] ?? '|');
        $route_code  = $route_raw[0];
        $route_display = $route_raw[1] ?? '';

        $num_code   = trim($_POST['numerator_code'] ?? '');
        $den_code   = trim($_POST['denominator_code'] ?? '');

        $num_system = 'http://unitsofmeasure.org';
        $den_system = 'http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm';

        $c_num = $pdo->prepare("SELECT code FROM satu_sehat_ref_numerator WHERE code = ?");
        $c_num->execute([$den_code]);
        if ($c_num->fetch()) {
            $den_system = 'http://unitsofmeasure.org';
        } else {
            $c_den = $pdo->prepare("SELECT code FROM satu_sehat_ref_denominator WHERE code = ?");
            $c_den->execute([$den_code]);
            if ($c_den->fetch()) {
                $den_system = 'http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm';
            }
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
                form_code=?, form_system='http://terminology.kemkes.go.id/CodeSystem/medication-form', form_display=?,
                route_code=?, route_system='http://www.whocc.no/atc', route_display=?,
                numerator_code=?, numerator_system=?,
                denominator_code=?, denominator_system=?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $kode_brng, $kfa_code, $kfa_display,
            $form_code, "http://terminology.kemkes.go.id/CodeSystem/medication-form", $form_display,
            $route_code, "http://www.whocc.no/atc", $route_display,
            $num_code, $num_system,
            $den_code, $den_system,
            $kfa_code, $kfa_display,
            $form_code, $form_display,
            $route_code, $route_display,
            $num_code, $num_system,
            $den_code, $den_system
        ]);

        // Auto-cache KFA ke tabel referensi obat
        if (!empty($kfa_code)) {
            $stmtCache = $pdo->prepare("INSERT IGNORE INTO satu_sehat_ref_kfa (kfa_code, display_name) VALUES (:id, :term)");
            $stmtCache->execute([':id' => $kfa_code, ':term' => $kfa_display]);
        }

        // Dual-Save: sinkronisasi otomatis ke satu_sehat_mapping_vaksin (1A + 1B)
        if ($sync_vaksin) {
            try {
                $vaksin_display_80 = function_exists('mb_substr') ? mb_substr($kfa_display, 0, 80) : substr($kfa_display, 0, 80);

                // Rekomendasi 1A + 1B untuk dose_quantity
                $check_lower = strtolower($kfa_display);
                $dose_code   = 'mL';
                $dose_unit   = 'mL';
                if (strpos($check_lower, 'tetes') !== false || strpos($check_lower, 'drop') !== false || strpos($check_lower, 'polio oral') !== false || strpos($check_lower, 'bopv') !== false) {
                    $dose_code = '[drp]';
                    $dose_unit = 'Tetes';
                } elseif (!empty($den_code) && in_array(strtolower($den_code), ['ml', 'l', 'mg'])) {
                    $dose_code = $den_code;
                    $dose_unit = $den_code;
                }

                $sqlVaksin = "INSERT INTO satu_sehat_mapping_vaksin
                              (kode_brng, vaksin_code, vaksin_system, vaksin_display, route_code, route_system, route_display, dose_quantity_code, dose_quantity_system, dose_quantity_unit)
                              VALUES (:kb, :vc, 'http://sys-ids.kemkes.go.id/kfa', :vd, :rc, 'http://www.whocc.no/atc', :rd, :dqc, 'http://unitsofmeasure.org', :dqu)
                              ON DUPLICATE KEY UPDATE vaksin_code=:vc2, vaksin_display=:vd2, route_code=:rc2, route_display=:rd2, dose_quantity_code=:dqc2, dose_quantity_unit=:dqu2";
                $stmtVaksin = $pdo->prepare($sqlVaksin);
                $stmtVaksin->execute([
                    ':kb'   => $kode_brng,
                    ':vc'   => $kfa_code,
                    ':vd'   => $vaksin_display_80,
                    ':rc'   => $route_code,
                    ':rd'   => $route_display,
                    ':dqc'  => $dose_code,
                    ':dqu'  => $dose_unit,
                    ':vc2'  => $kfa_code,
                    ':vd2'  => $vaksin_display_80,
                    ':rc2'  => $route_code,
                    ':rd2'  => $route_display,
                    ':dqc2' => $dose_code,
                    ':dqu2' => $dose_unit,
                ]);
            } catch (Exception $e) {}
        }

        $msg = 'Mapping Obat berhasil disimpan!';
        if ($sync_vaksin) {
            $msg .= ' (Tersinkronkan ke Mapping Vaksin)';
        }

        echo json_encode(['status' => 'success', 'message' => $msg]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["draw" => 1, "recordsTotal" => 0, "recordsFiltered" => 0, "data" => [], "error" => $e->getMessage()]);
}
