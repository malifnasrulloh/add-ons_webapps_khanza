<?php
/*
 * File: api/data_kunjungan_ranap.php (SECURITY HARDENED - PDO)
 * Deskripsi: Menampilkan list pasien Ranap dengan kalkulasi biaya realtime.
 * Fix: Menggunakan logika PHP loop untuk Operasi (bukan SQL SUM) untuk mencegah error typo kolom.
 * Fix: Menjamin sinkronisasi 100% dengan data_rincian_billing.php
 */

ob_start();
ini_set('display_errors', 0);
error_reporting(0);
set_time_limit(0);

header('Content-Type: application/json; charset=utf-8');
require_once(dirname(__DIR__) . '/config/koneksi.php'); 

// 1. HELPER FUNCTIONS
function safeFloat($val) {
    if (is_null($val) || $val === '') return 0.0;
    return (float)$val;
}

try {
    // 3. PARAMETER DATATABLES
    $draw   = isset($_GET['draw']) ? intval($_GET['draw']) : 0;
    $start  = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
    $mode   = isset($_GET['mode']) ? $_GET['mode'] : 'active';
    $tgl1   = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
    $tgl2   = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

    // 4. QUERY UTAMA
    $where = " WHERE 1=1 ";
    $params = [];

    if ($mode == 'active') {
        $where .= " AND ki.stts_pulang = '-' ";
    } else {
        $where .= " AND ki.tgl_masuk BETWEEN :tgl1 AND :tgl2 ";
        $params[':tgl1'] = $tgl1;
        $params[':tgl2'] = $tgl2;
    }

    if (!empty($search)) {
        $where .= " AND (ki.no_rawat LIKE :search OR p.nm_pasien LIKE :search OR d.nm_dokter LIKE :search OR b.nm_bangsal LIKE :search) ";
        $params[':search'] = "%$search%";
    }

    $sql_count = "SELECT COUNT(DISTINCT ki.no_rawat) as total 
                  FROM kamar_inap ki 
                  JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
                  JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                  LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
                  LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
                  LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
                  $where";
    $stmt_count = $koneksi_pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_records = (int)$stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

    $sql_limit = ($length != -1) ? " LIMIT " . (int)$start . ", " . (int)$length : "";

    $sql_data = "SELECT ki.no_rawat, ki.tgl_masuk, ki.jam_masuk, ki.tgl_keluar, ki.jam_keluar, ki.stts_pulang,
                 ki.diagnosa_awal, ki.diagnosa_akhir,
                 p.nm_pasien, p.no_rkm_medis, b.nm_bangsal, k.kd_kamar,
                 pj.png_jawab, pj.kd_pj, rp.biaya_reg,
                 -- Fetch DPJP from dpjp_ranap, fallback to reg_periksa doctor
                 COALESCE(
                     (SELECT d2.nm_dokter FROM dpjp_ranap dr JOIN dokter d2 ON dr.kd_dokter = d2.kd_dokter WHERE dr.no_rawat = ki.no_rawat ORDER BY dr.kd_dokter DESC LIMIT 1),
                     d.nm_dokter
                 ) AS nm_dokter,
                 -- Flag to check if it is fallback (1 = fallback, 0 = has DPJP)
                 CASE WHEN (SELECT COUNT(*) FROM dpjp_ranap dr WHERE dr.no_rawat = ki.no_rawat) > 0 THEN 0 ELSE 1 END AS is_dpjp_fallback,
                 (SELECT COUNT(*) FROM periksa_lab WHERE no_rawat = ki.no_rawat) as total_lab,
                 (SELECT COUNT(*) FROM periksa_radiologi WHERE no_rawat = ki.no_rawat) as total_rad,
                 (SELECT COUNT(no_rawat) FROM resume_pasien_ranap WHERE no_rawat = ki.no_rawat) as ada_resume,
                 (SELECT COUNT(no_rawat) FROM data_triase_igd WHERE no_rawat = ki.no_rawat) as ada_triase,
                 (SELECT COUNT(no_rawat) FROM penilaian_medis_igd WHERE no_rawat = ki.no_rawat) as ada_asesmen,
                 (SELECT COUNT(no_rawat) FROM operasi WHERE no_rawat = ki.no_rawat) as ada_operasi
                 FROM kamar_inap ki 
                 JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
                 JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                 JOIN penjab pj ON rp.kd_pj = pj.kd_pj
                 LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
                 LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
                 LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
                 $where
                 GROUP BY ki.no_rawat
                 ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC
                 $sql_limit";

    $stmt_data = $koneksi_pdo->prepare($sql_data);
    $stmt_data->execute($params);
    $raw_data = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

    // 5. FORMAT OUTPUT (TANPA KALKULASI — Lazy Loading v2)
    $data = [];
    foreach ($raw_data as $r) {
        // Hitung Hari Rawat (UI Only)
        $tgl_keluar = ($r['tgl_keluar'] != '0000-00-00') ? $r['tgl_keluar'] : date('Y-m-d');
        $jam_keluar = ($r['jam_keluar'] == '00:00:00') ? date('H:i:s') : $r['jam_keluar'];
        try {
            $d1 = new DateTime($r['tgl_masuk'].' '.$r['jam_masuk']);
            $d2 = new DateTime($tgl_keluar.' '.$jam_keluar);
            $hari_ui = $d2->diff($d1)->days;
            if($hari_ui < 1) $hari_ui = 0;
        } catch(Exception $e) { $hari_ui = 0; }

        $data[] = [
            "waktu"          => $r['tgl_masuk'],
            "no_rawat"       => $r['no_rawat'],
            "pasien"         => $r['nm_pasien'],
            "rm"             => $r['no_rkm_medis'],
            "dpjp"           => $r['nm_dokter'],
            "is_dpjp_fallback" => ((int)$r['is_dpjp_fallback'] === 1),
            "kamar"          => $r['nm_bangsal'],
            "penjamin"       => $r['png_jawab'],
            "kd_pj"          => $r['kd_pj'],
            
            "diagnosa_awal"  => $r['diagnosa_awal'],
            "diagnosa_akhir" => $r['diagnosa_akhir'],
            "lama_rawat"     => $hari_ui . " Hari",
            "count_lab"      => $r['total_lab'],
            "count_rad"      => $r['total_rad'],
            "klaim_resume"   => $r['ada_resume'],
            "klaim_triase"   => $r['ada_triase'],
            "klaim_asesmen"  => $r['ada_asesmen'],
            "klaim_operasi"  => $r['ada_operasi'],
            
            // Placeholder — akan diisi async oleh frontend
            "estimasi"       => null,
            "plafon"         => null,
            "selisih"        => null,
            "is_over"        => false,
            "status_pulang"  => ($r['stts_pulang'] != '-') ? $r['stts_pulang'] : 'Masih Dirawat'
        ];
    }

    $output = [
        "draw" => $draw,
        "recordsTotal" => $total_records,
        "recordsFiltered" => $total_records,
        "data" => $data
    ];

    ob_end_clean();
    echo json_encode($output);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        "draw" => $draw, "recordsTotal" => 0, "recordsFiltered" => 0, "data" => [],
        "error" => "Server Error: " . $e->getMessage()
    ]);
}
?>