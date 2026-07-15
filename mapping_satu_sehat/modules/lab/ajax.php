<?php
/**
 * modules/lab/ajax.php — Backend AJAX Modul Mapping Lab
 * FIX: Hapus LIMIT 100 → server-side pagination DataTables.
 */
error_reporting(0); ini_set('display_errors', 0);
require_once '../../conf.php';
require_once '../../auth_check.php';
check_module_access('satu_sehat_mapping_lab');
header('Content-Type: application/json');
$action = isset($_GET['action']) ? $_GET['action'] : '';
try {
    // ========================================================
    // 1. LOAD TABLE — Server-Side Pagination
    // ========================================================
    if ($action === 'load_table') {
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

        // Cek tabel
        $cek = $pdo->query("SHOW TABLES LIKE 'satu_sehat_mapping_lab'");
        if ($cek->rowCount() == 0) throw new Exception("Tabel 'satu_sehat_mapping_lab' belum dibuat! Jalankan installation.bat.");

        // Base WHERE (selalu aktif)
        $baseWhere = "jp.status = '1' AND jp.nm_perawatan IS NOT NULL
                      AND COALESCE(NULLIF(TRIM(tl.Pemeriksaan), ''), NULL) IS NOT NULL";
        $params = [];

        // Filter keyword
        if (!empty($keyword)) {
            $baseWhere .= " AND tl.Pemeriksaan LIKE :keyword";
            $params[':keyword'] = "%$keyword%";
        }

        // Filter DataTables search box
        $dt_search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
        if (!empty($dt_search)) {
            $baseWhere .= " AND (tl.Pemeriksaan LIKE :sdt OR jp.nm_perawatan LIKE :sdt OR ml.code LIKE :sdt)";
            $params[':sdt'] = "%$dt_search%";
        }

        // Total records (tanpa filter user)
        $sqlTotal = "SELECT COUNT(*) FROM template_laboratorium tl
                     JOIN jns_perawatan_lab jp ON tl.kd_jenis_prw = jp.kd_jenis_prw
                     JOIN penjab pj ON jp.kd_pj = pj.kd_pj
                     LEFT JOIN satu_sehat_mapping_lab ml ON tl.id_template = ml.id_template
                     WHERE jp.status = '1' AND jp.nm_perawatan IS NOT NULL
                     AND COALESCE(NULLIF(TRIM(tl.Pemeriksaan), ''), NULL) IS NOT NULL";
        $recordsTotal = (int)$pdo->query($sqlTotal)->fetchColumn();

        // Filtered count
        $sqlCount = "SELECT COUNT(*) FROM template_laboratorium tl
                     JOIN jns_perawatan_lab jp ON tl.kd_jenis_prw = jp.kd_jenis_prw
                     JOIN penjab pj ON jp.kd_pj = pj.kd_pj
                     LEFT JOIN satu_sehat_mapping_lab ml ON tl.id_template = ml.id_template
                     WHERE $baseWhere";
        $stmtC = $pdo->prepare($sqlCount); $stmtC->execute($params);
        $recordsFiltered = (int)$stmtC->fetchColumn();

        // Data query
        $sql = "SELECT pj.png_jawab, jp.nm_perawatan AS Nama_Paket_Lab,
                       tl.Pemeriksaan, tl.satuan, tl.id_template AS id_template_lab,
                       ml.code AS loinc_code, ml.display AS loinc_display,
                       ml.sampel_code, ml.sampel_display
                FROM template_laboratorium tl
                JOIN jns_perawatan_lab jp ON tl.kd_jenis_prw = jp.kd_jenis_prw
                JOIN penjab pj ON jp.kd_pj = pj.kd_pj
                LEFT JOIN satu_sehat_mapping_lab ml ON tl.id_template = ml.id_template
                WHERE $baseWhere";

        // ORDER BY
        $orderColIndex = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 1;
        $orderDir      = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
        $colMap = [
            0 => 'tl.id_template',
            1 => 'tl.Pemeriksaan',
            2 => 'ml.code',
            3 => 'ml.code'
        ];
        $orderBy = isset($colMap[$orderColIndex]) ? $colMap[$orderColIndex] : 'tl.Pemeriksaan';
        $sql .= " ORDER BY $orderBy $orderDir";

        // Paging
        $start  = isset($_GET['start'])  ? (int)$_GET['start']  : 0;
        $length = isset($_GET['length']) ? (int)$_GET['length'] : 25;
        if ($length < 1 || $length > 500) $length = 25;
        $sql .= " LIMIT :lmt OFFSET :ofs";
        $params[':lmt'] = $length; $params[':ofs'] = $start;

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            if ($k === ':lmt' || $k === ':ofs') $stmt->bindValue($k, $v, PDO::PARAM_INT);
            else $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = (!empty($row['loinc_code']) && !empty($row['sampel_code']))
                ? '<span class="badge bg-success"><i class="fa fa-check"></i> Mapped</span>'
                : '<span class="badge bg-danger">Belum</span>';

            $info = "";
            if ($row['loinc_code']) $info .= "<span class='badge bg-primary'>LOINC</span> <b>" . htmlspecialchars($row['loinc_code'], ENT_QUOTES, 'UTF-8') . "</b><br><small class='text-muted'>" . htmlspecialchars(substr($row['loinc_display'],0,50), ENT_QUOTES, 'UTF-8') . "</small><br>";
            if ($row['sampel_code']) $info .= "<span class='badge bg-info text-dark'>SNOMED</span> <b>" . htmlspecialchars($row['sampel_code'], ENT_QUOTES, 'UTF-8') . "</b><br><small class='text-muted'>" . htmlspecialchars(substr($row['sampel_display'],0,50), ENT_QUOTES, 'UTF-8') . "</small>";

            $pemeriksaan = htmlspecialchars($row['Pemeriksaan'], ENT_QUOTES, 'UTF-8');
            $paket       = htmlspecialchars($row['Nama_Paket_Lab'], ENT_QUOTES, 'UTF-8');
            $penjab      = htmlspecialchars($row['png_jawab'], ENT_QUOTES, 'UTF-8');

            $btn = "<button class='btn btn-sm btn-outline-primary btn-map'
                    data-id='{$row['id_template_lab']}'
                    data-nama='$pemeriksaan'
                    data-loinc='" . htmlspecialchars($row['loinc_code'] ?? '', ENT_QUOTES, 'UTF-8') . "'
                    data-loinc-display='" . htmlspecialchars($row['loinc_display'] ?? '', ENT_QUOTES, 'UTF-8') . "'
                    data-snomed='" . htmlspecialchars($row['sampel_code'] ?? '', ENT_QUOTES, 'UTF-8') . "'
                    data-snomed-display='" . htmlspecialchars($row['sampel_display'] ?? '', ENT_QUOTES, 'UTF-8') . "'>
                    <i class='fa fa-edit'></i> Mapping</button>";

            $data[] = [
                $row['id_template_lab'],
                "<b>$pemeriksaan</b><br><small class='text-muted'>$paket</small><br><span class='badge bg-secondary' style='font-size:.6rem'>$penjab</span>",
                $info, $status, $btn
            ];
        }

        echo json_encode([
            "draw" => isset($_GET['draw']) ? (int)$_GET['draw'] : 1,
            "recordsTotal"    => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data"            => $data
        ]);
        exit;
    }

    // ========================================================
    // 2. SEARCH LOINC
    // ========================================================
    if ($action === 'search_loinc') {
        $q = isset($_GET['term']) ? trim($_GET['term']) : '';
        
        require_once dirname(__DIR__) . '/kfa_api_helper.php';
        require_once dirname(__DIR__) . '/fhir_terminology_helper.php';
        $cred       = kfa_load_credential();
        $searchMode = ($cred && !empty($cred['kfa_search_mode'])) ? $cred['kfa_search_mode'] : 'database';
        
        $isFallback = false;
        if ($searchMode === 'api') {
            $apiData = fhir_search_loinc($q);
            if ($apiData['status'] === 'success') {
                echo json_encode(['results' => $apiData['results'], 'source' => 'api']);
                exit;
            }
            $isFallback = true;
        }

        $stmt = $pdo->prepare("SELECT loinc_num as id, CONCAT(loinc_num,' - ',long_common_name) as text, long_common_name as display FROM satu_sehat_ref_loinc WHERE long_common_name LIKE :q OR loinc_num LIKE :q LIMIT 20");
        $stmt->execute([':q' => "%$q%"]);
        echo json_encode(['results' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'source' => $isFallback ? 'fallback' : 'database']);
        exit;
    }

    // ========================================================
    // 3. SEARCH SNOMED
    // ========================================================
    if ($action === 'search_snomed') {
        $q = isset($_GET['term']) ? trim($_GET['term']) : '';
        
        require_once dirname(__DIR__) . '/kfa_api_helper.php';
        require_once dirname(__DIR__) . '/fhir_terminology_helper.php';
        $cred       = kfa_load_credential();
        $searchMode = ($cred && !empty($cred['kfa_search_mode'])) ? $cred['kfa_search_mode'] : 'database';
        
        $isFallback = false;
        if ($searchMode === 'api') {
            $apiData = fhir_search_snomed($q);
            if ($apiData['status'] === 'success') {
                echo json_encode(['results' => $apiData['results'], 'source' => 'api']);
                exit;
            }
            $isFallback = true;
        }

        $stmt = $pdo->prepare("SELECT conceptId as id, CONCAT(conceptId,' - ',term) as text, term as display FROM satu_sehat_ref_snomed WHERE term LIKE :q OR conceptId LIKE :q LIMIT 20");
        $stmt->execute([':q' => "%$q%"]);
        echo json_encode(['results' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'source' => $isFallback ? 'fallback' : 'database']);
        exit;
    }

    // ========================================================
    // 4. SAVE MAPPING
    // ========================================================
    if ($action === 'save_mapping') {
        validate_csrf();
        $id_template   = (int)($_POST['id_template']    ?? 0);
        $loinc_code    = trim($_POST['loinc_code']   ?? '');
        $loinc_display = trim($_POST['loinc_display'] ?? '');
        $snomed_code   = trim($_POST['snomed_code']  ?? '');
        $snomed_display= trim($_POST['snomed_display'] ?? '');

        if (!$id_template || empty($loinc_code)) throw new Exception("Data tidak lengkap.");

        // Simpan item utama
        $stmt = $pdo->prepare("INSERT INTO satu_sehat_mapping_lab (id_template, code, system, display, sampel_code, sampel_system, sampel_display)
                VALUES (:id, :lc, 'http://loinc.org', :ld, :sc, 'http://snomed.info/sct', :sd)
                ON DUPLICATE KEY UPDATE code=:lc2, display=:ld2, sampel_code=:sc2, sampel_display=:sd2");
        $stmt->execute([':id'=>$id_template,':lc'=>$loinc_code,':ld'=>$loinc_display,':sc'=>$snomed_code,':sd'=>$snomed_display,':lc2'=>$loinc_code,':ld2'=>$loinc_display,':sc2'=>$snomed_code,':sd2'=>$snomed_display]);

        $pesan = "Mapping berhasil disimpan.";

        // Smart Copy: terapkan ke pemeriksaan dengan nama sama
        if (isset($_POST['apply_all']) && $_POST['apply_all'] === 'true') {
            $cek = $pdo->prepare("SELECT Pemeriksaan FROM template_laboratorium WHERE id_template = ?");
            $cek->execute([$id_template]);
            $raw_name = $cek->fetchColumn();
            if ($raw_name) {
                $bulk = $pdo->prepare("INSERT INTO satu_sehat_mapping_lab (id_template, code, system, display, sampel_code, sampel_system, sampel_display)
                              SELECT t.id_template,:lc,'http://loinc.org',:ld,:sc,'http://snomed.info/sct',:sd
                              FROM template_laboratorium t WHERE TRIM(LOWER(t.Pemeriksaan))=TRIM(LOWER(:nm)) AND t.id_template!=:id_asal
                              ON DUPLICATE KEY UPDATE code=:lc2,display=:ld2,sampel_code=:sc2,sampel_display=:sd2");
                $bulk->execute([':lc'=>$loinc_code,':ld'=>$loinc_display,':sc'=>$snomed_code,':sd'=>$snomed_display,':nm'=>trim($raw_name),':id_asal'=>$id_template,':lc2'=>$loinc_code,':ld2'=>$loinc_display,':sc2'=>$snomed_code,':sd2'=>$snomed_display]);
                $jml = $bulk->rowCount();
                if ($jml > 0) $pesan .= " Disalin ke <b>$jml</b> pemeriksaan bernama sama.";
            }
        }

        echo json_encode(['status' => 'success', 'message' => $pesan]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(["draw"=>1,"recordsTotal"=>0,"recordsFiltered"=>0,"data"=>[],"error"=>$e->getMessage()]);
}
?>
