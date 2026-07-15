<?php
// helpers/ajax/ajax_riwayat_farmasi.php
require_once dirname(__DIR__, 2) . '/config/koneksi.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

$no_rkm_medis = $_POST['no_rkm_medis'] ?? $_GET['no_rkm_medis'] ?? '';
$filter_mode  = $_POST['filter_mode'] ?? $_GET['filter_mode'] ?? '5_terakhir';
$tgl_awal     = $_POST['tgl_awal'] ?? $_GET['tgl_awal'] ?? '';
$tgl_akhir    = $_POST['tgl_akhir'] ?? $_GET['tgl_akhir'] ?? '';

if (empty($no_rkm_medis)) {
    echo json_encode(['status' => 'error', 'message' => 'no_rkm_medis tidak valid']);
    exit;
}

$timeline = [];

try {
    $sql_reg = "SELECT no_rawat, tgl_registrasi, jam_reg FROM reg_periksa WHERE no_rkm_medis = ?";
    $params_reg = [$no_rkm_medis];

    if ($filter_mode === 'tanggal' && !empty($tgl_awal) && !empty($tgl_akhir)) {
        $sql_reg .= " AND tgl_registrasi BETWEEN ? AND ?";
        $params_reg[] = $tgl_awal;
        $params_reg[] = $tgl_akhir;
    }
    
    $sql_reg .= " ORDER BY tgl_registrasi DESC, jam_reg DESC";
    
    if ($filter_mode === '5_terakhir') {
        $sql_reg .= " LIMIT 5";
    }

    $stmt_reg = $koneksi_pdo->prepare($sql_reg);
    $stmt_reg->execute($params_reg);
    $kunjungan = $stmt_reg->fetchAll(PDO::FETCH_ASSOC);
    
    $list_norawat = array_column($kunjungan, 'no_rawat');
    $placeholders = empty($list_norawat) ? "''" : implode(',', array_fill(0, count($list_norawat), '?'));

    if (!empty($list_norawat)) {
        
        // 1. Resep Obat
        $stmt_resep = $koneksi_pdo->prepare("SELECT * FROM resep_obat WHERE no_rawat IN ($placeholders)");
        $stmt_resep->execute($list_norawat);
        $resep_headers = $stmt_resep->fetchAll(PDO::FETCH_ASSOC);
        
        $umum_grouped = [];
        $racik_grouped = [];
        
        if (!empty($resep_headers)) {
            $list_noresep = array_column($resep_headers, 'no_resep');
            $resep_placeholders = implode(',', array_fill(0, count($list_noresep), '?'));
            
            // Ambil obat umum dalam 1 query
            $stmt_umum = $koneksi_pdo->prepare("SELECT r.*, d.nama_brng FROM resep_dokter r JOIN databarang d ON r.kode_brng = d.kode_brng WHERE r.no_resep IN ($resep_placeholders)");
            $stmt_umum->execute($list_noresep);
            foreach ($stmt_umum->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $umum_grouped[$row['no_resep']][] = $row;
            }
            
            // Ambil racikan dalam 1 query
            $stmt_racik = $koneksi_pdo->prepare("SELECT * FROM resep_dokter_racikan WHERE no_resep IN ($resep_placeholders)");
            $stmt_racik->execute($list_noresep);
            foreach ($stmt_racik->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $racik_grouped[$row['no_resep']][] = $row;
            }
        }
        
        foreach ($resep_headers as $row) {
            $no_resep = $row['no_resep'];
            $umum = $umum_grouped[$no_resep] ?? [];
            $racikan = $racik_grouped[$no_resep] ?? [];
            
            if(!empty($umum) || !empty($racikan)) {
                $timeline[] = [
                    'tanggal' => $row['tgl_peresepan'],
                    'jam' => $row['jam_peresepan'],
                    'jenis' => 'Resep',
                    'no_rawat' => $row['no_rawat'],
                    'data' => [
                        'no_resep' => $no_resep,
                        'umum' => $umum,
                        'racikan' => $racikan
                    ]
                ];
            }
        }

        // 2. Resep Pulang
        $stmt_resep_pulang = $koneksi_pdo->prepare("SELECT * FROM resep_pulang WHERE no_rawat IN ($placeholders)");
        $stmt_resep_pulang->execute($list_norawat);
        foreach ($stmt_resep_pulang->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $timeline[] = [
                'tanggal' => $row['tanggal'],
                'jam' => $row['jam'],
                'jenis' => 'Resep',
                'no_rawat' => $row['no_rawat'],
                'data' => [
                    'No Resep/Nota' => $row['no_keluar'] ?? '-',
                    'Keterangan' => 'Resep Pulang',
                    'Kode Barang' => $row['kode_brng'],
                    'Jumlah' => $row['jml_barang'],
                    'Aturan Pakai' => $row['aturan_pakai'] ?? '-',
                    'Harga' => $row['harga']
                ]
            ];
        }

        // 3. Rekonsiliasi Obat
        $stmt_rekonsil = $koneksi_pdo->prepare("SELECT * FROM rekonsiliasi_obat WHERE no_rawat IN ($placeholders)");
        $stmt_rekonsil->execute($list_norawat);
        foreach ($stmt_rekonsil->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $dt = date('Y-m-d', strtotime($row['tanggal']));
            $tm = date('H:i:s', strtotime($row['tanggal']));
            $timeline[] = [
                'tanggal' => $dt,
                'jam' => $tm,
                'jenis' => 'Rekonsiliasi Obat',
                'no_rawat' => $row['no_rawat'],
                'data' => [
                    'Alergi' => $row['alergi'],
                    'Riwayat Obat' => $row['riwayat_penggunaan_obat'],
                    'Kondisi Khusus' => $row['kondisi_khusus'],
                    'Tindak Lanjut' => $row['tindak_lanjut']
                ]
            ];
        }

        // 4. Pelayanan Informasi Obat / Edukasi / Asuhan Lainnya
        $tables_farmasi = [
            'pelayanan_informasi_obat' => 'Pelayanan Informasi Obat (PIO)',
            'konseling_farmasi' => 'Konseling Farmasi'
        ];

        foreach($tables_farmasi as $table => $label) {
            $stmt = $koneksi_pdo->prepare("SELECT t.* FROM $table t WHERE t.no_rawat IN ($placeholders)");
            $stmt->execute($list_norawat);
            foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $tanggal = $row['tanggal'] ?? '';
                if(empty($tanggal)) continue;
                $dt = date('Y-m-d', strtotime($tanggal));
                $tm = date('H:i:s', strtotime($tanggal));
                
                $info = [];
                if(isset($row['materi'])) $info['Materi'] = $row['materi'];
                if(isset($row['pertanyaan'])) $info['Pertanyaan'] = $row['pertanyaan'];
                if(isset($row['jawaban'])) $info['Jawaban'] = $row['jawaban'];
                if(isset($row['keterangan'])) $info['Keterangan'] = $row['keterangan'];
                if(isset($row['kesimpulan'])) $info['Kesimpulan'] = $row['kesimpulan'];
                
                $info['Tipe'] = $label;

                $timeline[] = [
                    'tanggal' => $dt,
                    'jam' => $tm,
                    'jenis' => 'Edukasi & Farmasi',
                    'no_rawat' => $row['no_rawat'],
                    'data' => $info
                ];
            }
        }
    }

    echo json_encode(['status' => 'success', 'data' => $timeline]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
