<?php
/**
 * modules/vaksin/ajax.php — Backend AJAX Modul Mapping Vaksin
 * Sumber data : tabel databarang (filter by nama/kategori vaksin)
 * Target      : tabel satu_sehat_mapping_vaksin (PK: kode_brng)
 * Kolom target: vaksin_code, vaksin_system, vaksin_display,
 *               route_code, route_system, route_display,
 *               dose_quantity_code, dose_quantity_system, dose_quantity_unit
 */
error_reporting(0); ini_set('display_errors', 0);
require_once '../../conf.php';
require_once '../../auth_check.php';
check_module_access('satu_sehat_mapping_vaksin');
header('Content-Type: application/json');
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    // ========================================================
    // 1. LOAD TABLE
    // ========================================================
    if ($action === 'load_table') {
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

        $baseWhere = "1=1";
        $params = [];

        if (!empty($keyword)) {
            $baseWhere .= " AND (d.nama_brng LIKE :keyword OR d.kode_brng LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }
        $dt_search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
        if (!empty($dt_search)) {
            $baseWhere .= " AND (d.nama_brng LIKE :sdt OR d.kode_brng LIKE :sdt OR mv.vaksin_code LIKE :sdt)";
            $params[':sdt'] = "%$dt_search%";
        }

        // Total
        $sqlTotal = "SELECT COUNT(*) FROM databarang";
        $recordsTotal = (int)$pdo->query($sqlTotal)->fetchColumn();

        $sqlCount = "SELECT COUNT(*) FROM databarang d LEFT JOIN satu_sehat_mapping_vaksin mv ON d.kode_brng=mv.kode_brng WHERE $baseWhere";
        $stmtC = $pdo->prepare($sqlCount); $stmtC->execute($params);
        $recordsFiltered = (int)$stmtC->fetchColumn();

        $sql = "SELECT d.kode_brng, d.nama_brng,
                       mv.vaksin_code, mv.vaksin_display, mv.vaksin_system,
                       mv.route_code, mv.route_display,
                       mv.dose_quantity_code, mv.dose_quantity_unit
                FROM databarang d
                LEFT JOIN satu_sehat_mapping_vaksin mv ON d.kode_brng = mv.kode_brng
                WHERE $baseWhere";

        // ORDER BY
        $orderColIndex = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 1;
        $orderDir      = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
        $colMap = [
            0 => 'd.kode_brng',
            1 => 'd.nama_brng',
            2 => 'mv.vaksin_code',
            3 => 'mv.vaksin_code'
        ];
        $orderBy = isset($colMap[$orderColIndex]) ? $colMap[$orderColIndex] : 'd.nama_brng';
        $sql .= " ORDER BY $orderBy $orderDir";

        $start  = isset($_GET['start'])  ? (int)$_GET['start']  : 0;
        $length = isset($_GET['length']) ? (int)$_GET['length'] : 25;
        if ($length < 1 || $length > 500) $length = 25;
        $sql .= " LIMIT :lmt OFFSET :ofs";
        $params[':lmt'] = $length; $params[':ofs'] = $start;

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            if ($k===':lmt'||$k===':ofs') $stmt->bindValue($k,$v,PDO::PARAM_INT);
            else $stmt->bindValue($k,$v);
        }
        $stmt->execute();

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = !empty($row['vaksin_code'])
                ? '<span class="badge bg-success"><i class="fa fa-check"></i> Mapped</span>'
                : '<span class="badge bg-danger">Belum</span>';

            $info = "";
            if (!empty($row['vaksin_code'])) {
                $info .= "<span class='badge bg-success'>CVX/KFA</span> <b>" . htmlspecialchars($row['vaksin_code'], ENT_QUOTES, 'UTF-8') . "</b><br>";
                $info .= "<small class='text-muted'>" . htmlspecialchars(substr($row['vaksin_display'],0,50), ENT_QUOTES, 'UTF-8') . "</small><br>";
                if ($row['route_code']) $info .= "<b>Rute:</b> " . htmlspecialchars($row['route_display'], ENT_QUOTES, 'UTF-8') . "<br>";
                if ($row['dose_quantity_code']) $info .= "<b>Satuan:</b> " . htmlspecialchars($row['dose_quantity_unit'] ?: $row['dose_quantity_code'], ENT_QUOTES, 'UTF-8') . " (" . htmlspecialchars($row['dose_quantity_code'], ENT_QUOTES, 'UTF-8') . ")";
            } else {
                $info = "<small class='text-muted'>- Belum dimapping -</small>";
            }

            $nama_safe = htmlspecialchars($row['nama_brng'], ENT_QUOTES, 'UTF-8');
            $kode_safe = htmlspecialchars($row['kode_brng'], ENT_QUOTES, 'UTF-8');

            $row_json = json_encode([
                'kode_brng'       => $row['kode_brng'],
                'nama_brng'       => $row['nama_brng'],
                'vaksin_code'     => $row['vaksin_code'] ?? '',
                'vaksin_display'  => $row['vaksin_display'] ?? '',
                'route_code'      => $row['route_code'] ?? '',
                'route_display'   => $row['route_display'] ?? '',
                'dose_qty_code'   => $row['dose_quantity_code'] ?? '',
                'dose_qty_unit'   => $row['dose_quantity_unit'] ?? '',
            ], JSON_HEX_APOS | JSON_HEX_QUOT);

            $btn = "<button class='btn btn-sm btn-outline-success btn-map' data-json='$row_json'>
                    <i class='fa fa-edit'></i> Mapping</button>";

            $data[] = [$kode_safe, $nama_safe, $info, $status, $btn];
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
    // 2. SEARCH KFA (vaksin) — Dual Mode: API / Database
    // ========================================================
    if ($action === 'search_kfa') {
        $q = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['term']) ? trim($_GET['term']) : '');

        // Baca mode dari credential JSON
        require_once dirname(__DIR__) . '/kfa_api_helper.php';
        $cred       = kfa_load_credential();
        $searchMode = ($cred && !empty($cred['kfa_search_mode'])) ? $cred['kfa_search_mode'] : 'database';

        // ---- Mode API ----
        $isFallback = false;
        if ($searchMode === 'api' && $cred && !empty($cred['client_id'])) {
            $apiResults = kfa_search_from_api($cred, $q, 20);

            if ($apiResults !== null) {
                echo json_encode([
                    'results' => $apiResults,
                    'source'  => 'api'
                ]);
                exit;
            }
            // API gagal → catat sebagai fallback
            $isFallback = true;
        }

        // ---- Mode Database (atau fallback) ----
        $stmt = $pdo->prepare(
            "SELECT kfa_code AS id,
                    CONCAT(kfa_code, ' - ', display_name) AS text,
                    display_name,
                    '' as route_code,
                    '' as route_display,
                    '' as ucum_code
             FROM satu_sehat_ref_kfa
             WHERE kfa_code LIKE :q OR display_name LIKE :q
             LIMIT 50"
        );
        $stmt->execute([':q' => "%$q%"]);
        echo json_encode(['results' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'source' => $isFallback ? 'fallback' : 'database']);
        exit;
    }

    // ========================================================
    // 2b. REFRESH KFA TOKEN (resusitasi)
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
            echo json_encode(['status' => 'error', 'message' => 'Gagal mendapatkan token baru dari API Kemenkes. Periksa koneksi server dan credential.']);
            exit;
        }
        $_SESSION[KFA_SESSION_TOKEN_KEY]  = $token;
        $_SESSION[KFA_SESSION_EXPIRY_KEY] = time() + (55 * 60);
        echo json_encode(['status' => 'success', 'message' => 'Token KFA berhasil diperbarui! Silakan cari vaksin kembali.']);
        exit;
    }

    // ========================================================
    // 3. SAVE MAPPING
    // ========================================================
    if ($action === 'save_mapping') {
        validate_csrf();

        $kode_brng       = trim($_POST['kode_brng']         ?? '');
        $vaksin_code     = trim($_POST['vaksin_code']        ?? '');
        $vaksin_display  = trim($_POST['vaksin_display']     ?? '');
        $vaksin_system   = trim($_POST['vaksin_system']      ?? 'http://sys-ids.kemkes.go.id/kfa');
        $route_code      = trim($_POST['route_code']         ?? '');
        $route_display   = trim($_POST['route_display']      ?? '');
        $dose_qty_code   = trim($_POST['dose_quantity_code'] ?? '');
        $dose_qty_system = trim($_POST['dose_quantity_system'] ?? 'http://unitsofmeasure.org');
        $dose_qty_unit   = trim($_POST['dose_quantity_unit'] ?? '');

        if (empty($kode_brng) || empty($vaksin_code)) {
            echo json_encode(['status' => 'error', 'message' => 'Kode barang dan kode vaksin wajib diisi.']);
            exit;
        }

        $sql = "INSERT INTO satu_sehat_mapping_vaksin
                (kode_brng, vaksin_code, vaksin_system, vaksin_display,
                 route_code, route_system, route_display,
                 dose_quantity_code, dose_quantity_system, dose_quantity_unit)
                VALUES (:kb, :vc, :vs, :vd, :rc, :rs, :rd, :dqc, :dqs, :dqu)
                ON DUPLICATE KEY UPDATE
                vaksin_code=:vc2, vaksin_system=:vs2, vaksin_display=:vd2,
                route_code=:rc2, route_display=:rd2,
                dose_quantity_code=:dqc2, dose_quantity_system=:dqs2, dose_quantity_unit=:dqu2";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':kb'=>$kode_brng, ':vc'=>$vaksin_code, ':vs'=>$vaksin_system, ':vd'=>$vaksin_display,
            ':rc'=>$route_code, ':rs'=>"http://www.whocc.no/atc", ':rd'=>$route_display,
            ':dqc'=>$dose_qty_code, ':dqs'=>$dose_qty_system, ':dqu'=>$dose_qty_unit,
            ':vc2'=>$vaksin_code, ':vs2'=>$vaksin_system, ':vd2'=>$vaksin_display,
            ':rc2'=>$route_code, ':rd2'=>$route_display,
            ':dqc2'=>$dose_qty_code, ':dqs2'=>$dose_qty_system, ':dqu2'=>$dose_qty_unit
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Mapping vaksin berhasil disimpan.']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["draw"=>1,"recordsTotal"=>0,"recordsFiltered"=>0,"data"=>[],"error"=>$e->getMessage()]);
}
?>
