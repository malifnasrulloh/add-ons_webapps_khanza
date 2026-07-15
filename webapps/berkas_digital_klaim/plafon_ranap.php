<?php
/*
 * File: plafon_ranap.php
 * Fungsi: Input Plafon BPJS & Monitoring Profit/Loss
 */
require_once('csrf.php');
if (!isset($_SESSION['casemix_login']) || $_SESSION['casemix_login'] !== true) { header("Location: index.php"); exit; }
require_once('../conf/conf.php');
$koneksi = bukakoneksi();

// Data Instansi & User (Sama)
$q_set = mysqli_query($koneksi, "SELECT nama_instansi, logo FROM setting LIMIT 1");
$r_set = mysqli_fetch_assoc($q_set);
$nama_instansi = htmlspecialchars($r_set['nama_instansi'], ENT_QUOTES, 'UTF-8');
$logo_b64 = isset($r_set['logo']) ? 'data:image/jpeg;base64,' . base64_encode($r_set['logo']) : 'logo.php';

$user_id = $_SESSION['casemix_user'];
$nama_user_login = htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8'); 
$q_pegawai = mysqli_query($koneksi, "SELECT nama FROM pegawai WHERE nik = '$user_id'");
if(mysqli_num_rows($q_pegawai) > 0){ $nama_user_login = htmlspecialchars(mysqli_fetch_assoc($q_pegawai)['nama'], ENT_QUOTES, 'UTF-8'); } 
else { $q_dok = mysqli_query($koneksi, "SELECT nm_dokter FROM dokter WHERE kd_dokter = '$user_id'"); if(mysqli_num_rows($q_dok) > 0) $nama_user_login = htmlspecialchars(mysqli_fetch_assoc($q_dok)['nm_dokter'], ENT_QUOTES, 'UTF-8'); }

// Filter Tanggal
$tgl_awal  = isset($_GET['tgl_awal']) ? validTeks4($_GET['tgl_awal'], 10) : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? validTeks4($_GET['tgl_akhir'], 10) : date('Y-m-d');

function safeFloat($val) {
    if (is_null($val) || $val === '') return 0.0;
    return (float)$val;
}

function safe_query($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if ($res === false) { return false; }
    return $res;
}

// 2. LOAD GLOBAL SETTINGS
$setting_kamar = ['hariawal' => 'no', 'lamajam' => 0]; 
$q_jam = safe_query($koneksi, "SELECT hariawal, lamajam FROM set_jam_minimal LIMIT 1");
if($q_jam && $r_jam = mysqli_fetch_assoc($q_jam)) $setting_kamar = $r_jam;

$tampilkan_ppn_ranap = false;
$q_set = mysqli_query($koneksi, "SELECT tampilkan_ppnobat_ranap FROM set_nota LIMIT 1");
if($q_set && $r_set = mysqli_fetch_assoc($q_set)){
    if($r_set['tampilkan_ppnobat_ranap'] == 'Yes') $tampilkan_ppn_ranap = true;
}

$service_umum = null; $service_piutang = null;
$q_su = safe_query($koneksi, "SELECT * FROM set_service_ranap LIMIT 1");
if($q_su) $service_umum = mysqli_fetch_assoc($q_su);
$q_sp = safe_query($koneksi, "SELECT * FROM set_service_ranap_piutang LIMIT 1");
if($q_sp) $service_piutang = mysqli_fetch_assoc($q_sp);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= csrf_meta() ?>
    <title>Input Plafon - <?= $nama_instansi ?></title>
    <link rel="icon" href="logo.php" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        body { overflow-x: hidden; background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; }
        
        /* Copy Style Sidebar dari Dashboard */
        #sidebar-wrapper { min-height: 100vh; width: 250px; margin-left: -250px; position: fixed; top: 0; left: 0; bottom: 0; z-index: 1000; transition: margin .25s ease-out; background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%); color: #fff; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
        #sidebar-wrapper .sidebar-heading { padding: 1.2rem 1rem; font-size: 1.1rem; border-bottom: 1px solid rgba(255,255,255,0.15); }
        #sidebar-wrapper .list-group { width: 250px; }
        #sidebar-wrapper .list-group-item { background: transparent; color: rgba(255,255,255,0.85); border: none; padding: 12px 20px; }
        #sidebar-wrapper .list-group-item:hover { background: rgba(255,255,255,0.15); color: #fff; border-left: 4px solid #fff; }
        #sidebar-wrapper .list-group-item.active { background: rgba(255,255,255,0.2); color: #fff; font-weight: bold; border-left: 4px solid #4cd137; }
        
        #page-content-wrapper { width: 100%; transition: margin .25s ease-out; }
        body.sb-sidenav-toggled #sidebar-wrapper { margin-left: 0; }
        @media (min-width: 768px) {
            #sidebar-wrapper { margin-left: 0; }
            #page-content-wrapper { margin-left: 250px; }
            body.sb-sidenav-toggled #sidebar-wrapper { margin-left: -250px; }
            body.sb-sidenav-toggled #page-content-wrapper { margin-left: 0; }
        }
        #overlay { display: none; position: fixed; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 900; }
        body.sb-sidenav-toggled #overlay { display: block; }
        @media (min-width: 768px) { body.sb-sidenav-toggled #overlay { display: none; } }
        
        .top-navbar { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 10px 20px; }
        .table th { white-space: nowrap; text-align: center; vertical-align: middle; background-color: #f8f9fa; font-size: 0.85rem; }
        .table td { vertical-align: middle; font-size: 0.85rem; }
        
        /* Select2 Adjustment */
        .select2-container { width: 100% !important; }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">
    <div id="overlay" onclick="toggleMenu()"></div>
    <?php include 'sidebar.php'; ?>

    <div id="page-content-wrapper">
        <nav class="top-navbar d-flex justify-content-between align-items-center sticky-top">
            <button class="btn btn-outline-secondary border-0" id="menu-toggle"><i class="fas fa-bars fa-lg"></i></button>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-md-block line-height-sm">
                    <div class="fw-bold text-dark small"><?= $nama_user_login ?></div>
                    <small class="text-muted" style="font-size:0.75rem">Petugas Casemix</small>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user_login) ?>&background=random" class="rounded-circle border" width="35">
            </div>
        </nav>

        <div class="container-fluid px-4 py-4">
            
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-hand-holding-usd me-2"></i>Monitoring Profit/Loss (BPJS Ranap)</h5>
                    <?php if (isset($_SESSION['casemix_role']) && $_SESSION['casemix_role'] === 'Super Admin'): ?>
                        <button type="button" class="btn btn-warning btn-sm fw-bold" onclick="fixSchema()">
                            <i class="fas fa-tools me-1"></i> Fix Table Constraint
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablePlafon" class="table table-striped table-hover w-100">
                            <thead class="bg-light">
                                <tr>
                                    <th>No. Rawat</th>
                                    <th>No. RM</th>
                                    <th>Nama Pasien</th>
                                    <th width="30%">Kode ICD / Grouper / Plafon</th>
                                    <th class="text-end">Billing Real</th>
                                    <th class="text-end">Plafon</th>
                                    <th class="text-end">Selisih</th>
                                    <th>DPJP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_plafon = "SELECT 
                                    reg_periksa.no_rawat, 
                                    reg_periksa.no_rkm_medis, 
                                    pasien.nm_pasien, 
                                    reg_periksa.biaya_reg,
                                    reg_periksa.kd_pj,
                                    perkiraan_biaya_ranap.kd_penyakit, 
                                    perkiraan_biaya_ranap.tarif AS tarif_INACBG,
                                    pegawai.nama as dpjp_nama
                                FROM reg_periksa
                                LEFT JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                                LEFT JOIN perkiraan_biaya_ranap ON reg_periksa.no_rawat = perkiraan_biaya_ranap.no_rawat
                                LEFT JOIN dpjp_ranap ON reg_periksa.no_rawat = dpjp_ranap.no_rawat
                                LEFT JOIN pegawai ON dpjp_ranap.kd_dokter = pegawai.nik
                                INNER JOIN kamar_inap ON reg_periksa.no_rawat = kamar_inap.no_rawat
                                WHERE reg_periksa.status_bayar = 'Belum Bayar' 
                                    AND reg_periksa.status_lanjut = 'Ranap'
                                    AND kamar_inap.stts_pulang = '-'
                                    AND reg_periksa.kd_pj = 'BPJ'
                                GROUP BY reg_periksa.no_rawat";

                                $hasil_data = array();
                                $hasil = mysqli_query($koneksi, $sql_plafon);
                                while ($row = mysqli_fetch_assoc($hasil)) {
                                    // ... [LOGIKA PERHITUNGAN BILLING] ...
                                    $no_rawat = $row['no_rawat'];
                                    $grand_total = 0.0;
                                    
                                    $sum_kamar = 0; $sum_reg = 0; 
                                    $sum_dr_ralan = 0; $sum_pr_ralan = 0; 
                                    $sum_dr_ranap = 0; $sum_pr_ranap = 0; 
                                    $sum_lab = 0; $sum_rad = 0; $sum_op = 0; $sum_obat = 0; 
                                    $sum_retur = 0; $sum_tambah = 0; $sum_potong = 0; $sum_harian = 0;

                                    if(safeFloat($row['biaya_reg']) > 0) {
                                        $val = safeFloat($row['biaya_reg']); $sum_reg += $val; $grand_total += $val;
                                    }

                                    $q_hist_kamar = safe_query($koneksi, "SELECT k.kd_kamar, k.trf_kamar, ki.tgl_masuk, ki.tgl_keluar, ki.lama, ki.ttl_biaya FROM kamar_inap ki JOIN kamar k ON ki.kd_kamar = k.kd_kamar WHERE ki.no_rawat='$no_rawat'");
                                    if($q_hist_kamar) {
                                        while($rhk = mysqli_fetch_assoc($q_hist_kamar)) {
                                            $tgl_masuk = $rhk['tgl_masuk'];
                                            $tgl_keluar = ($rhk['tgl_keluar'] != '0000-00-00') ? $rhk['tgl_keluar'] : date('Y-m-d');
                                            $d1 = new DateTime($tgl_masuk); $d2 = new DateTime($tgl_keluar);
                                            $diff = $d2->diff($d1);
                                            $hari_raw = $diff->days;

                                            if ($setting_kamar['hariawal'] == 'yes') $hari = $hari_raw + 1;
                                            else $hari = $hari_raw;

                                            if (safeFloat($rhk['ttl_biaya']) > 0 && safeFloat($rhk['lama']) > 0) $hari = safeFloat($rhk['lama']);

                                            $biaya_satu_kamar = $hari * safeFloat($rhk['trf_kamar']);
                                            if($biaya_satu_kamar > 0) { $sum_kamar += $biaya_satu_kamar; $grand_total += $biaya_satu_kamar; }

                                            $kd_k = $rhk['kd_kamar'];
                                            $q_bs = safe_query($koneksi, "SELECT SUM(besar_biaya) as tot FROM biaya_sekali WHERE kd_kamar='$kd_k'");
                                            if($q_bs && $row_bs = mysqli_fetch_assoc($q_bs)) { $val = safeFloat($row_bs['tot']); $sum_harian += $val; $grand_total += $val; }

                                            $q_bh = safe_query($koneksi, "SELECT SUM(besar_biaya) as tot FROM biaya_harian WHERE kd_kamar='$kd_k'");
                                            if($q_bh && $row_bh = mysqli_fetch_assoc($q_bh)) { $val = ($hari * safeFloat($row_bh['tot'])); $sum_harian += $val; $grand_total += $val; }
                                        }
                                    }

                                    $q_op = safe_query($koneksi, "SELECT * FROM operasi WHERE no_rawat='$no_rawat'");
                                    if($q_op) {
                                        while($r_op = mysqli_fetch_assoc($q_op)) {
                                            $komponen = ['biayaoperator1','biayaoperator2','biayaoperator3','biayaasisten_operator1','biayaasisten_operator2','biayadokter_anestesi','biayaasisten_anestesi','biayasewaok','biayaalat','akomodasi','bagian_rs','biaya_omloop','biayasarpras','biaya_dokter_anak','biayaperawaat_resusitas','biayabidan'];
                                            foreach($komponen as $k) { 
                                                if(isset($r_op[$k])) {
                                                    $val = safeFloat($r_op[$k]);
                                                    $sum_op += $val;
                                                }
                                            }
                                        }
                                    }
                                    $grand_total += $sum_op;

                                    $sql_tind = "SELECT 'lab' as grp, SUM(biaya) as tot FROM periksa_lab WHERE no_rawat='$no_rawat'
                                                 UNION ALL SELECT 'rad', SUM(biaya) FROM periksa_radiologi WHERE no_rawat='$no_rawat'
                                                 UNION ALL SELECT 'dr_ralan', SUM(biaya_rawat) FROM rawat_jl_dr WHERE no_rawat='$no_rawat'
                                                 UNION ALL SELECT 'pr_ralan', SUM(biaya_rawat) FROM rawat_jl_pr WHERE no_rawat='$no_rawat'
                                                 UNION ALL SELECT 'dr_ralan', SUM(biaya_rawat) FROM rawat_jl_drpr WHERE no_rawat='$no_rawat'
                                                 UNION ALL SELECT 'dr_ranap', SUM(biaya_rawat) FROM rawat_inap_dr WHERE no_rawat='$no_rawat'
                                                 UNION ALL SELECT 'pr_ranap', SUM(biaya_rawat) FROM rawat_inap_pr WHERE no_rawat='$no_rawat'
                                                 UNION ALL SELECT 'dr_ranap', SUM(biaya_rawat) FROM rawat_inap_drpr WHERE no_rawat='$no_rawat'
                                                 UNION ALL SELECT 'tambah', SUM(besar_biaya) FROM tambahan_biaya WHERE no_rawat='$no_rawat'
                                                 UNION ALL SELECT 'potong', SUM(besar_pengurangan) FROM pengurangan_biaya WHERE no_rawat='$no_rawat'";
                                    
                                    $q_tind = safe_query($koneksi, $sql_tind);
                                    if($q_tind) {
                                        while($rt = mysqli_fetch_assoc($q_tind)){
                                            $val = safeFloat($rt['tot']);
                                            $grp = $rt['grp'];
                                            if($val != 0) {
                                                if($grp == 'lab') $sum_lab += $val;
                                                else if($grp == 'rad') $sum_rad += $val;
                                                else if($grp == 'dr_ralan') $sum_dr_ralan += $val;
                                                else if($grp == 'pr_ralan') $sum_pr_ralan += $val;
                                                else if($grp == 'dr_ranap') $sum_dr_ranap += $val;
                                                else if($grp == 'pr_ranap') $sum_pr_ranap += $val;
                                                else if($grp == 'tambah') $sum_tambah += $val;
                                                else if($grp == 'potong') { $sum_potong += (-1 * abs($val)); $grand_total += (-1 * abs($val)); continue; } 
                                                $grand_total += $val;
                                            }
                                        }
                                    }

                                    $sql_obat = "SELECT SUM(total) as tot FROM detail_pemberian_obat WHERE no_rawat='$no_rawat'
                                                 UNION ALL SELECT SUM(besar_tagihan) FROM tagihan_obat_langsung WHERE no_rawat='$no_rawat'
                                                 UNION ALL SELECT SUM(hargasatuan * jumlah) FROM beri_obat_operasi WHERE no_rawat='$no_rawat'";
                                    $q_obat = safe_query($koneksi, $sql_obat);
                                    if($q_obat) while($ro = mysqli_fetch_assoc($q_obat)) $sum_obat += safeFloat($ro['tot']);
                                    $grand_total += $sum_obat;

                                    $q_ret_fix = safe_query($koneksi, "SELECT SUM(r.jml * d.ralan) as tot FROM returpasien r JOIN databarang d ON r.kode_brng = d.kode_brng WHERE r.no_rawat='$no_rawat'");
                                    if($q_ret_fix && $rr = mysqli_fetch_assoc($q_ret_fix)) $sum_retur += abs(safeFloat($rr['tot']));
                                    $grand_total -= $sum_retur;

                                    if($tampilkan_ppn_ranap) {
                                        $obat_bersih = $sum_obat - $sum_retur;
                                        if($obat_bersih > 0) $grand_total += round($obat_bersih * 0.11);
                                    }

                                    $s = null;
                                    $kd_pj = $row['kd_pj'];
                                    if($kd_pj != '-' && $kd_pj != 'UMUM' && $kd_pj != 'A01') $s = $service_piutang;
                                    else $s = $service_umum;

                                    if($s) {
                                        $basis = 0;
                                        if($s['laborat'] == 'Yes') $basis += $sum_lab;
                                        if($s['radiologi'] == 'Yes') $basis += $sum_rad;
                                        if($s['operasi'] == 'Yes') $basis += $sum_op;
                                        if($s['obat'] == 'Yes') $basis += ($sum_obat - $sum_retur);
                                        if($s['ranap_dokter'] == 'Yes') $basis += $sum_dr_ranap;
                                        if($s['ranap_paramedis'] == 'Yes') $basis += $sum_pr_ranap;
                                        if($s['ralan_dokter'] == 'Yes') $basis += $sum_dr_ralan;
                                        if($s['ralan_paramedis'] == 'Yes') $basis += $sum_pr_ralan;
                                        if($s['tambahan'] == 'Yes') $basis += $sum_tambah;
                                        if($s['potongan'] == 'Yes') $basis += $sum_potong;
                                        if($s['kamar'] == 'Yes') $basis += $sum_kamar;
                                        if($s['registrasi'] == 'Yes') $basis += $sum_reg;
                                        if($s['harian'] == 'Yes') $basis += $sum_harian;

                                        $persen = safeFloat($s['besar']);
                                        if($basis > 0 && $persen > 0) {
                                            $jasa_admin = round($basis * ($persen / 100));
                                            
                                            $cek = safe_query($koneksi, "SELECT totalbiaya FROM billing WHERE no_rawat='$no_rawat' AND (nm_perawatan LIKE '%Administrasi%' OR nm_perawatan LIKE '%Service%')");
                                            if(!$cek || mysqli_num_rows($cek) == 0) {
                                                $grand_total += $jasa_admin;
                                            } else {
                                                 while($row_bill = mysqli_fetch_assoc($cek)) $grand_total += safeFloat($row_bill['totalbiaya']);
                                            }
                                        }
                                    }

                                    $row['billing_sementara'] = $grand_total;
                                    $row['tarif_INACBG'] = safeFloat($row['tarif_INACBG']);
                                    $row['selisih_raw'] = $row['tarif_INACBG'] - $grand_total;
                                    
                                    $hasil_data[] = $row;
                                }

                                usort($hasil_data, function($a, $b) {
                                    if ($a['selisih_raw'] == $b['selisih_raw']) return 0;
                                    return ($a['selisih_raw'] < $b['selisih_raw']) ? -1 : 1;
                                });

                                foreach ($hasil_data as $row) {
                                    $selisih = $row['selisih_raw'];
                                    $warna_selisih = ($selisih < 0) ? 'text-danger fw-bold' : 'text-success fw-bold';
                                    $id_select = str_replace(['/','\\'], '-', $row['no_rawat']); // ID aman untuk JS
                                ?>
                                <tr>
                                    <td><?= $row['no_rawat'] ?></td>
                                    <td><?= $row['no_rkm_medis'] ?></td>
                                    <td><?= $row['nm_pasien'] ?></td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control form-control-sm" placeholder="Kode ICD/Grouper" 
                                                   id="kode_<?= $id_select ?>" value="<?= htmlspecialchars($row['kd_penyakit']) ?>">
                                            <input type="number" class="form-control form-control-sm" placeholder="Nominal" 
                                                   id="tarif_<?= $id_select ?>" value="<?= $row['tarif_INACBG'] ?>">
                                            <button class="btn btn-primary btn-sm btn-save" data-id="<?= $id_select ?>" data-rawat="<?= $row['no_rawat'] ?>">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-end"><?= number_format($row['billing_sementara'], 0, ',', '.') ?></td>
                                    <td class="text-end"><?= number_format($row['tarif_INACBG'], 0, ',', '.') ?></td>
                                    <td class="text-end <?= $warna_selisih ?>"><?= number_format($selisih, 0, ',', '.') ?></td>
                                    <td><small><?= $row['dpjp_nama'] ?></small></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Sidebar Toggle
    document.getElementById("menu-toggle").onclick = function () { document.body.classList.toggle("sb-sidenav-toggled"); };

    function fixSchema() {
        Swal.fire({
            title: 'Fix Table Schema?',
            text: "Ini akan melepas constraint kd_penyakit agar Anda bisa input bebas.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            confirmButtonText: 'Ya, Jalankan!'
        }).then((result) => {
            if (result.isConfirmed) {
                var csrfToken = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: 'api/fix_schema.php',
                    method: 'POST',
                    data: { csrf_token: csrfToken },
                    dataType: 'json',
                    success: function(resp) {
                        Swal.fire(resp.status === 'error' ? 'Gagal' : 'Berhasil', resp.message, resp.status);
                    },
                    error: function(xhr, status, error) { 
                        var msg = 'Gagal menghubungi server.';
                        if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        Swal.fire('Error', msg, 'error'); 
                    }
                });
            }
        });
    }

    $(document).ready(function() {
        // Init Datatable
        var tablePlafon = $('#tablePlafon').DataTable({ 
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            pageLength: 10,
            lengthMenu: [ [10, 50, 100, -1], ['10', '50', '100', 'Semua'] ]
        });

        // Handle Save Button
        $(document).on('click', '.btn-save', function() {
            var rawat = $(this).data('rawat');
            var id_el = $(this).data('id');
            var kode = $('#kode_' + id_el).val();
            var tarif = $('#tarif_' + id_el).val();

            if(!tarif || tarif <= 0) { Swal.fire('Error', 'Input nominal tarif dulu!', 'warning'); return; }

            Swal.fire({
                title: 'Simpan Tarif?',
                text: "Data perkiraan biaya akan diperbarui.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Simpan'
            }).then((result) => {
                if (result.isConfirmed) {
                    var csrfToken = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: 'api/save_grouper.php',
                        method: 'POST',
                        data: { case: rawat, kode: kode, tarif: tarif, csrf_token: csrfToken },
                        dataType: 'json',
                        success: function(resp) {
                            if(resp.status === 'success') {
                                Swal.fire('Berhasil!', resp.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Gagal!', resp.message, 'error');
                            }
                        },
                        error: function(xhr, status, error) { 
                        var msg = 'Gagal menghubungi server.';
                        if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        Swal.fire('Error', msg, 'error'); 
                    }
                    });
                }
            });
        });
    });
</script>

</body>
</html>
<?php mysqli_close($koneksi); ?>