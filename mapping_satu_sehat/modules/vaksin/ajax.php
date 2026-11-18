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
        $isFallback = false;
        if ($searchMode === 'api' && $cred && !empty($cred['client_id'])) {
            $apiResults = kfa_search_from_api($cred, $q, 20);

            if ($apiResults !== null) {
                echo json_encode([
                    'results' => $apiResults,
                    'source'  => 'api',
                    'pagination' => ['more' => false]
                ]);
                exit;
            }
            // API gagal → catat sebagai fallback
            $isFallback = true;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM satu_sehat_ref_kfa WHERE kfa_code LIKE :q OR display_name LIKE :q");
        $stmtCount->execute([':q' => "%$q%"]);
        $total = $stmtCount->fetchColumn();
        $more = ($offset + $limit) < $total;

        // ---- Mode Database (atau fallback) ----
        $stmt = $pdo->prepare("
            SELECT kfa_code AS id,
                CONCAT(kfa_code, ' - ', display_name) AS text,
                display_name,
                '' as route_code,
                '' as route_display,
                '' as ucum_code
            FROM satu_sehat_ref_kfa
            WHERE kfa_code LIKE :q OR display_name LIKE :q
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
            'results' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'source'  => $isFallback ? 'fallback' : 'database',
            'pagination' => ['more' => $more]
        ]);
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
        $sync_obat       = !empty($_POST['sync_obat']) && ($_POST['sync_obat'] == '1' || $_POST['sync_obat'] === true || $_POST['sync_obat'] === 'true');

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

        // Auto-cache KFA to local database for future searches
        if (!empty($vaksin_code)) {
            $stmtCache = $pdo->prepare("INSERT IGNORE INTO satu_sehat_ref_kfa (kfa_code, display_name) VALUES (:id, :term)");
            $stmtCache->execute([':id' => $vaksin_code, ':term' => $vaksin_display]);
        }

        // Dual-Save: sinkronkan juga ke satu_sehat_mapping_obat
        if ($sync_obat) {
            try {
                $sqlObat = "INSERT INTO satu_sehat_mapping_obat
                            (kode_brng, obat_code, obat_system, obat_display, route_code, route_system, route_display)
                            VALUES (:kb, :oc, :os, :od, :rc, 'http://www.whocc.no/atc', :rd)
                            ON DUPLICATE KEY UPDATE obat_code=:oc2, obat_display=:od2, route_code=:rc2, route_display=:rd2";
                $stmtObat = $pdo->prepare($sqlObat);
                $stmtObat->execute([
                    ':kb'  => $kode_brng,
                    ':oc'  => $vaksin_code,
                    ':os'  => $vaksin_system,
                    ':od'  => $vaksin_display,
                    ':rc'  => $route_code,
                    ':rd'  => $route_display,
                    ':oc2' => $vaksin_code,
                    ':od2' => $vaksin_display,
                    ':rc2' => $route_code,
                    ':rd2' => $route_display,
                ]);
            } catch (Exception $e) {}
        }

        $msg = 'Mapping vaksin berhasil disimpan.';
        if ($sync_obat) {
            $msg .= ' (Tersinkronkan ke Mapping Obat)';
        }

        echo json_encode(['status' => 'success', 'message' => $msg]);
        exit;
    }

    // ========================================================
    // 4. LOAD TABLE PAKET TINDAKAN VAKSIN
    // ========================================================
    if ($action === 'load_table_paket') {
        $sql = "SELECT vt.kd_jenis_prw, jp.nm_perawatan, vt.urutan, vt.kode_brng, d.nama_brng, 
                       mv.vaksin_code, mv.vaksin_display, vt.posisi_batch, vt.reason_code
                FROM satu_sehat_mapping_vaksin_tindakan vt
                LEFT JOIN jns_perawatan jp ON vt.kd_jenis_prw = jp.kd_jenis_prw
                LEFT JOIN databarang d ON vt.kode_brng = d.kode_brng
                LEFT JOIN satu_sehat_mapping_vaksin mv ON vt.kode_brng = mv.kode_brng
                ORDER BY vt.kd_jenis_prw ASC, vt.urutan ASC";

        $stmt = $pdo->query($sql);
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tindakan_info = "<b>" . htmlspecialchars($row['kd_jenis_prw'], ENT_QUOTES, 'UTF-8') . "</b><br>"
                           . "<span class='text-dark'>" . htmlspecialchars($row['nm_perawatan'] ?? '-', ENT_QUOTES, 'UTF-8') . "</span>";

            $urutan_badge = ($row['urutan'] == 1)
                ? "<span class='badge bg-primary'>Vaksin Ke-1</span>"
                : "<span class='badge bg-info text-dark'>Vaksin Ke-" . (int)$row['urutan'] . "</span>";

            $barang_info = "<b>" . htmlspecialchars($row['nama_brng'] ?? '-', ENT_QUOTES, 'UTF-8') . "</b> (" . htmlspecialchars($row['kode_brng'], ENT_QUOTES, 'UTF-8') . ")<br>";
            if (!empty($row['vaksin_code'])) {
                $barang_info .= "<span class='badge bg-success'>KFA " . htmlspecialchars($row['vaksin_code'], ENT_QUOTES, 'UTF-8') . "</span> ";
                $barang_info .= "<small class='text-muted'>" . htmlspecialchars(substr($row['vaksin_display'] ?? '', 0, 40), ENT_QUOTES, 'UTF-8') . "</small>";
            } else {
                $barang_info .= "<span class='badge bg-danger'>Barang belum dipetakan ke KFA</span>";
            }

            $posisi_badge = ($row['posisi_batch'] == 2)
                ? "<span class='badge bg-warning text-dark'>Batch SOAP Ke-2 (batch2)</span>"
                : "<span class='badge bg-secondary'>Batch SOAP Utama (batch)</span>";

            $reason_text = "Imunisasi Program";
            $reason_cls  = "bg-secondary";
            if ($row['reason_code'] === 'IM-Khusus') {
                $reason_text = "Program Khusus (Umroh/Haji)";
                $reason_cls  = "bg-primary";
            } elseif ($row['reason_code'] === 'IM-Pilihan') {
                $reason_text = "Imunisasi Pilihan";
                $reason_cls  = "bg-success";
            }
            $reason_badge = "<span class='badge $reason_cls'>$reason_text</span>";

            $row_json = json_encode([
                'kd_jenis_prw'  => $row['kd_jenis_prw'],
                'nm_perawatan'  => $row['nm_perawatan'] ?? '',
                'urutan'        => $row['urutan'],
                'kode_brng'     => $row['kode_brng'],
                'nama_brng'     => $row['nama_brng'] ?? '',
                'posisi_batch'  => $row['posisi_batch'],
                'reason_code'   => $row['reason_code'],
            ], JSON_HEX_APOS | JSON_HEX_QUOT);

            $btn = "<button class='btn btn-sm btn-outline-primary btn-edit-paket me-1' data-json='$row_json' title='Edit Mapping'>
                        <i class='fa fa-edit'></i>
                    </button>
                    <button class='btn btn-sm btn-outline-danger btn-delete-paket' data-kd='" . htmlspecialchars($row['kd_jenis_prw'], ENT_QUOTES, 'UTF-8') . "' data-urutan='" . (int)$row['urutan'] . "' title='Hapus'>
                        <i class='fa fa-trash'></i>
                    </button>";

            $data[] = [
                $tindakan_info,
                $urutan_badge,
                $barang_info,
                $posisi_badge,
                $reason_badge,
                $btn
            ];
        }

        echo json_encode([
            "draw"            => 1,
            "recordsTotal"    => count($data),
            "recordsFiltered" => count($data),
            "data"            => $data
        ]);
        exit;
    }

    // ========================================================
    // 5. SEARCH TINDAKAN (jns_perawatan)
    // ========================================================
    if ($action === 'search_tindakan') {
        $q = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['term']) ? trim($_GET['term']) : '');
        $stmt = $pdo->prepare("
            SELECT kd_jenis_prw AS id,
                   CONCAT(kd_jenis_prw, ' - ', nm_perawatan) AS text,
                   nm_perawatan
            FROM jns_perawatan
            WHERE status = '1' AND (kd_jenis_prw LIKE :q OR nm_perawatan LIKE :q)
            ORDER BY nm_perawatan ASC
            LIMIT 40
        ");
        $stmt->execute([':q' => "%$q%"]);
        echo json_encode(['results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    // ========================================================
    // 6. SEARCH BARANG VAKSIN TERPETAKAN
    // ========================================================
    if ($action === 'search_barang_vaksin') {
        $q = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['term']) ? trim($_GET['term']) : '');
        $stmt = $pdo->prepare("
            SELECT d.kode_brng AS id,
                   CONCAT(d.kode_brng, ' - ', d.nama_brng, ' [KFA: ', IFNULL(mv.vaksin_code, '-'), ']') AS text,
                   d.nama_brng,
                   mv.vaksin_code,
                   mv.vaksin_display
            FROM databarang d
            INNER JOIN satu_sehat_mapping_vaksin mv ON d.kode_brng = mv.kode_brng
            WHERE d.status = '1' AND (d.kode_brng LIKE :q OR d.nama_brng LIKE :q OR mv.vaksin_code LIKE :q OR mv.vaksin_display LIKE :q)
            ORDER BY d.nama_brng ASC
            LIMIT 40
        ");
        $stmt->execute([':q' => "%$q%"]);
        echo json_encode(['results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    // ========================================================
    // 7. SAVE MAPPING PAKET
    // ========================================================
    if ($action === 'save_mapping_paket') {
        validate_csrf();

        $kd_jenis_prw = trim($_POST['kd_jenis_prw'] ?? '');
        $urutan       = (int)($_POST['urutan'] ?? 1);
        $kode_brng    = trim($_POST['kode_brng'] ?? '');
        $posisi_batch = (int)($_POST['posisi_batch'] ?? 1);
        $reason_code  = trim($_POST['reason_code'] ?? 'IM-Khusus');

        if (empty($kd_jenis_prw) || empty($kode_brng)) {
            echo json_encode(['status' => 'error', 'message' => 'Pilih tindakan perawatan dan barang vaksin terlebih dahulu.']);
            exit;
        }

        if ($urutan < 1) $urutan = 1;
        if (!in_array($posisi_batch, [1, 2], true)) $posisi_batch = 1;
        if (!in_array($reason_code, ['IM-Khusus', 'IM-Pilihan', 'IM-Program'], true)) $reason_code = 'IM-Khusus';

        $sql = "INSERT INTO satu_sehat_mapping_vaksin_tindakan (kd_jenis_prw, urutan, kode_brng, posisi_batch, reason_code)
                VALUES (:kd, :urutan, :kb, :pb, :rc)
                ON DUPLICATE KEY UPDATE kode_brng = :kb2, posisi_batch = :pb2, reason_code = :rc2";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':kd'      => $kd_jenis_prw,
            ':urutan'  => $urutan,
            ':kb'      => $kode_brng,
            ':pb'      => $posisi_batch,
            ':rc'      => $reason_code,
            ':kb2'     => $kode_brng,
            ':pb2'     => $posisi_batch,
            ':rc2'     => $reason_code
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Mapping paket tindakan vaksin berhasil disimpan.']);
        exit;
    }

    // ========================================================
    // 8. DELETE MAPPING PAKET
    // ========================================================
    if ($action === 'delete_mapping_paket') {
        validate_csrf();

        $kd_jenis_prw = trim($_POST['kd_jenis_prw'] ?? '');
        $urutan       = (int)($_POST['urutan'] ?? 1);

        if (empty($kd_jenis_prw)) {
            echo json_encode(['status' => 'error', 'message' => 'Kode jenis perawatan tidak valid.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM satu_sehat_mapping_vaksin_tindakan WHERE kd_jenis_prw = :kd AND urutan = :urutan");
        $stmt->execute([':kd' => $kd_jenis_prw, ':urutan' => $urutan]);

        echo json_encode(['status' => 'success', 'message' => 'Mapping paket tindakan berhasil dihapus.']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["draw"=>1,"recordsTotal"=>0,"recordsFiltered"=>0,"data"=>[],"error"=>$e->getMessage()]);
}
?>
