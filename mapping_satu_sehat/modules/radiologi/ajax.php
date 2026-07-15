<?php
/**
 * modules/radiologi/ajax.php — Backend AJAX Modul Mapping Radiologi
 * Tabel sumber : jns_perawatan_radiologi JOIN penjab
 * Tabel target  : satu_sehat_mapping_radiologi (PK: kd_jenis_prw)
 * Referensi     : satu_sehat_ref_loinc & satu_sehat_ref_snomed (shared dengan lab)
 */
error_reporting(0); ini_set('display_errors', 0);
require_once '../../conf.php';
require_once '../../auth_check.php';
check_module_access('satu_sehat_mapping_radiologi');
header('Content-Type: application/json');
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    // ========================================================
    // 1. LOAD TABLE
    // ========================================================
    if ($action === 'load_table') {
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

        $baseWhere = "jp.status = '1' AND jp.nm_perawatan IS NOT NULL";
        $params = [];

        if (!empty($keyword)) {
            $baseWhere .= " AND (jp.nm_perawatan LIKE :keyword OR jp.kd_jenis_prw LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }
        $dt_search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
        if (!empty($dt_search)) {
            $baseWhere .= " AND (jp.nm_perawatan LIKE :sdt OR mr.code LIKE :sdt OR pj.png_jawab LIKE :sdt)";
            $params[':sdt'] = "%$dt_search%";
        }

        // Total count
        $sqlTotal = "SELECT COUNT(*) FROM jns_perawatan_radiologi jp JOIN penjab pj ON jp.kd_pj = pj.kd_pj LEFT JOIN satu_sehat_mapping_radiologi mr ON jp.kd_jenis_prw = mr.kd_jenis_prw WHERE jp.status='1' AND jp.nm_perawatan IS NOT NULL";
        $recordsTotal = (int)$pdo->query($sqlTotal)->fetchColumn();

        $sqlCount = "SELECT COUNT(*) FROM jns_perawatan_radiologi jp JOIN penjab pj ON jp.kd_pj = pj.kd_pj LEFT JOIN satu_sehat_mapping_radiologi mr ON jp.kd_jenis_prw = mr.kd_jenis_prw WHERE $baseWhere";
        $stmtC = $pdo->prepare($sqlCount); $stmtC->execute($params);
        $recordsFiltered = (int)$stmtC->fetchColumn();

        $sql = "SELECT jp.kd_jenis_prw, jp.nm_perawatan, pj.png_jawab,
                       mr.code, mr.system, mr.display,
                       mr.sampel_code, mr.sampel_system, mr.sampel_display
                FROM jns_perawatan_radiologi jp
                JOIN penjab pj ON jp.kd_pj = pj.kd_pj
                LEFT JOIN satu_sehat_mapping_radiologi mr ON jp.kd_jenis_prw = mr.kd_jenis_prw
                WHERE $baseWhere";

        // ORDER BY
        $orderColIndex = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 1;
        $orderDir      = isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
        $colMap = [
            0 => 'jp.kd_jenis_prw',
            1 => 'jp.nm_perawatan',
            2 => 'mr.code',
            3 => 'mr.code'
        ];
        $orderBy = isset($colMap[$orderColIndex]) ? $colMap[$orderColIndex] : 'jp.nm_perawatan';
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
            $status = (!empty($row['code']))
                ? '<span class="badge bg-success"><i class="fa fa-check"></i> Mapped</span>'
                : '<span class="badge bg-danger">Belum</span>';

            $info = "";
            if ($row['code']) {
                $info .= "<span class='badge bg-primary'>LOINC</span> <b>" . htmlspecialchars($row['code'], ENT_QUOTES, 'UTF-8') . "</b><br>";
                $info .= "<small class='text-muted'>" . htmlspecialchars(substr($row['display'],0,50), ENT_QUOTES, 'UTF-8') . "</small><br>";
                if ($row['sampel_code']) {
                    $info .= "<span class='badge bg-info text-dark'>SNOMED</span> <b>" . htmlspecialchars($row['sampel_code'], ENT_QUOTES, 'UTF-8') . "</b><br>";
                    $info .= "<small class='text-muted'>" . htmlspecialchars(substr($row['sampel_display'],0,50), ENT_QUOTES, 'UTF-8') . "</small>";
                }
            }

            $nama_safe  = htmlspecialchars($row['nm_perawatan'], ENT_QUOTES, 'UTF-8');
            $kode_safe  = htmlspecialchars($row['kd_jenis_prw'], ENT_QUOTES, 'UTF-8');
            $penjab     = htmlspecialchars($row['png_jawab'], ENT_QUOTES, 'UTF-8');

            $btn = "<button class='btn btn-sm btn-outline-danger btn-map'
                    data-kd='$kode_safe'
                    data-nama='$nama_safe'
                    data-code='" . htmlspecialchars($row['code'] ?? '', ENT_QUOTES, 'UTF-8') . "'
                    data-display='" . htmlspecialchars($row['display'] ?? '', ENT_QUOTES, 'UTF-8') . "'
                    data-sampel-code='" . htmlspecialchars($row['sampel_code'] ?? '', ENT_QUOTES, 'UTF-8') . "'
                    data-sampel-display='" . htmlspecialchars($row['sampel_display'] ?? '', ENT_QUOTES, 'UTF-8') . "'>
                    <i class='fa fa-edit'></i> Mapping</button>";

            $data[] = [
                $kode_safe,
                "<b>$nama_safe</b><br><span class='badge bg-secondary' style='font-size:.6rem'>$penjab</span>",
                $info, $status, $btn
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
    // 2. SEARCH LOINC (shared dengan lab)
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

        $kd_jenis_prw  = trim($_POST['kd_jenis_prw']   ?? '');
        $loinc_code    = trim($_POST['loinc_code']      ?? '');
        $loinc_display = trim($_POST['loinc_display']   ?? '');
        $snomed_code   = trim($_POST['snomed_code']     ?? '');
        $snomed_display= trim($_POST['snomed_display']  ?? '');

        if (empty($kd_jenis_prw) || empty($loinc_code)) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
            exit;
        }

        $sql = "INSERT INTO satu_sehat_mapping_radiologi
                (kd_jenis_prw, code, system, display, sampel_code, sampel_system, sampel_display)
                VALUES (:kd, :lc, 'http://loinc.org', :ld, :sc, 'http://snomed.info/sct', :sd)
                ON DUPLICATE KEY UPDATE
                code=:lc2, display=:ld2, sampel_code=:sc2, sampel_display=:sd2";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':kd'=>$kd_jenis_prw, ':lc'=>$loinc_code, ':ld'=>$loinc_display, ':sc'=>$snomed_code, ':sd'=>$snomed_display,
            ':lc2'=>$loinc_code, ':ld2'=>$loinc_display, ':sc2'=>$snomed_code, ':sd2'=>$snomed_display
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Mapping radiologi berhasil disimpan.']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["draw"=>1,"recordsTotal"=>0,"recordsFiltered"=>0,"data"=>[],"error"=>$e->getMessage()]);
}
?>
