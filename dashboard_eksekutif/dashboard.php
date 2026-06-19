<?php
/*
 * File: dashboard.php (UPDATE V6 - HYPERLINKS)
 * - Fix: Link Pasien Masuk -> laporan_kunjungan.php
 * - Fix: Widget Kunjungan Aktif -> Tombol Ralan & Ranap terpisah.
 * - Fix: Link Tren -> laporan_kunjungan.php
 * - Fix: Link Top Poli -> laporan_kinerja_dokter.php
 */
$page_title = "Executive Dashboard";
require_once('includes/header.php');
?>

<style>
    .card-metric { transition: transform .2s; cursor: pointer; }
    .card-metric:hover { transform: scale(1.03); }
    .icon-circle {
        height: 3rem; width: 3rem; border-radius: 50%; display: flex; 
        align-items: center; justify-content: center; font-size: 1.5rem; color: white;
    }
    .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
    .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); }
    .bg-gradient-info { background: linear-gradient(45deg, #36b9cc, #258391); }
    .bg-gradient-warning { background: linear-gradient(45deg, #f6c23e, #dda20a); }
    .bg-gradient-danger { background: linear-gradient(45deg, #e74a3b, #be2617); }
    .text-xs-bold { font-size: 0.75rem; font-weight: 700; }
    
    .bed-row:hover { background-color: #f8f9fa; cursor: pointer; }
    
    /* Style untuk Link Header Chart */
    .chart-header-link { cursor: pointer; transition: color 0.2s; }
    .chart-header-link:hover h6 { color: #2e59d9 !important; text-decoration: underline; }
</style>

<div class="container-fluid">

    <div class="row mb-4">
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-4 border-primary shadow h-100 py-2 card-metric" onclick="window.location.href='laporan_billing_global.php'">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Omzet Hari Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="val-omzet-total">...</div>
                            <div class="mt-2">
                                <span class="badge bg-success me-1" title="Tunai"><i class="fas fa-money-bill me-1"></i><span id="val-omzet-tunai">0</span></span>
                                <span class="badge bg-warning text-dark" title="Piutang"><i class="fas fa-file-invoice me-1"></i><span id="val-omzet-piutang">0</span></span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="icon-circle bg-gradient-primary"><i class="fas fa-cash-register"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-4 border-success shadow h-100 py-2 card-metric" onclick="window.location.href='laporan_kunjungan.php'">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Pasien Registrasi Masuk</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="val-visit-total">...</div>
                            <div class="row mt-2 text-xs-bold text-muted">
                                <div class="col-6 border-end">Ralan: <span id="val-visit-ralan" class="text-success">0</span></div>
                                <div class="col-6">Ranap: <span id="val-visit-ranap" class="text-warning">0</span></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="icon-circle bg-gradient-success"><i class="fas fa-user-plus"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-4 border-info shadow h-100 py-2 card-metric" onclick="window.location.href='laporan_indikator_ranap.php'">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">BOR Bulan Ini (Global)</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800" id="val-bor">...%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar" id="bar-bor" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="icon-circle bg-gradient-info"><i class="fas fa-procedures"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-4 border-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Kunjungan Aktif (Blm Bayar)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="val-aktif">...</div>
                            <div class="mt-2">
                                <a href="kunjungan_ralan.php" class="btn btn-sm btn-outline-danger py-0" style="font-size: 0.7rem;">Ralan</a>
                                <a href="kunjungan_ranap.php" class="btn btn-sm btn-outline-warning text-dark py-0" style="font-size: 0.7rem;">Ranap</a>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="icon-circle bg-gradient-danger"><i class="fas fa-file-invoice-dollar"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- COLLAPSIBLE METRICS & WIDGETS SECTION (ON-DEMAND) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <a href="#collapseLegacyWidgets" class="d-block card-header py-3 d-flex flex-row align-items-center justify-content-between text-decoration-none" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="collapseLegacyWidgets" id="triggerCollapseWidgets">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-cubes me-2"></i>Rincian Indikator & Metrik Tambahan (On-Demand)</h6>
                    <span class="badge bg-secondary" id="widget-load-badge">Klik untuk Memuat</span>
                </a>
                <div class="collapse" id="collapseLegacyWidgets">
                    <div class="card-body">
                        <!-- Loading Spinner -->
                        <div id="widgets-loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Mengambil metrik & melakukan kalkulasi...</p>
                        </div>
                        
                        <!-- Main Grid (initially hidden) -->
                        <div id="widgets-container" class="d-none">
                            
                            <!-- 1. Executive Premium KPIs -->
                            <div class="row mb-4 g-3">
                                <div class="col-md-4">
                                    <div class="card border-start border-4 border-info shadow-sm py-2">
                                        <div class="card-body py-1">
                                            <div class="row align-items-center">
                                                <div class="col">
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Kepatuhan Bridging SatuSehat (Hari Ini)</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="val-prem-satusehat">0%</div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-satellite-dish fa-2x text-info"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-start border-4 border-warning shadow-sm py-2">
                                        <div class="card-body py-1">
                                            <div class="row align-items-center">
                                                <div class="col">
                                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Aset Dead Stock Farmasi (>3 Bulan)</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="val-prem-deadstock">Rp 0</div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-boxes fa-2x text-warning"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-start border-4 border-success shadow-sm py-2">
                                        <div class="card-body py-1">
                                            <div class="row align-items-center">
                                                <div class="col">
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Rata Waktu Tunggu Poli (Hari Ini)</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="val-prem-waittime">0 Menit</div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-clock fa-2x text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="mb-4">

                            <!-- Tabbed Subgroups for the 31 Widgets -->
                            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active py-1 px-3 me-2" id="pills-general-tab" data-bs-toggle="pill" data-bs-target="#pills-general" type="button" role="tab" aria-controls="pills-general" aria-selected="true">Umum & Kamar</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-1 px-3 me-2" id="pills-igd-tab" data-bs-toggle="pill" data-bs-target="#pills-igd" type="button" role="tab" aria-controls="pills-igd" aria-selected="false">IGD (Gawat Darurat)</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-1 px-3 me-2" id="pills-ralan-tab" data-bs-toggle="pill" data-bs-target="#pills-ralan" type="button" role="tab" aria-controls="pills-ralan" aria-selected="false">Rawat Jalan (Kunjungan)</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-1 px-3 me-2" id="pills-ranap-tab" data-bs-toggle="pill" data-bs-target="#pills-ranap" type="button" role="tab" aria-controls="pills-ranap" aria-selected="false">Rawat Inap (Kunjungan)</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-1 px-3 me-2" id="pills-ralan-keu-tab" data-bs-toggle="pill" data-bs-target="#pills-ralan-keu" type="button" role="tab" aria-controls="pills-ralan-keu" aria-selected="false">Keuangan Ralan</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-1 px-3" id="pills-ranap-keu-tab" data-bs-toggle="pill" data-bs-target="#pills-ranap-keu" type="button" role="tab" aria-controls="pills-ranap-keu" aria-selected="false">Keuangan Ranap</button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="pills-tabContent">
                                <!-- A. UMUM & KAMAR -->
                                <div class="tab-pane fade show active" id="pills-general" role="tabpanel" aria-labelledby="pills-general-tab">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Total Rekam Medis Pasien</div>
                                                <div class="h4 mb-0 font-weight-bold text-primary" id="val-gen-totalrm">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Reg. Ralan Hari Ini</div>
                                                <div class="h4 mb-0 font-weight-bold text-success" id="val-gen-ralantoday">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Pasien Ranap Aktif</div>
                                                <div class="h4 mb-0 font-weight-bold text-warning" id="val-gen-ranaptoday">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Registrasi Bulan Ini</div>
                                                <div class="h4 mb-0 font-weight-bold text-info" id="val-gen-totalmonth">0</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- B. IGD -->
                                <div class="tab-pane fade" id="pills-igd" role="tabpanel" aria-labelledby="pills-igd-tab">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Daftar IGD</div>
                                                <div class="h4 mb-0 font-weight-bold text-primary" id="val-igd-daftar">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">IGD Lanjut Rawat</div>
                                                <div class="h4 mb-0 font-weight-bold text-warning" id="val-igd-dirawat">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">IGD Belum Diperiksa</div>
                                                <div class="h4 mb-0 font-weight-bold text-danger" id="val-igd-belum">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">IGD Dirujuk</div>
                                                <div class="h4 mb-0 font-weight-bold text-info" id="val-igd-dirujuk">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">IGD Selesai Pelayanan</div>
                                                <div class="h4 mb-0 font-weight-bold text-success" id="val-igd-selesai">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">IGD Batal/Cancel</div>
                                                <div class="h4 mb-0 font-weight-bold text-secondary" id="val-igd-batal">0</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- C. RAWAT JALAN KUNJUNGAN -->
                                <div class="tab-pane fade" id="pills-ralan" role="tabpanel" aria-labelledby="pills-ralan-tab">
                                    <div class="row g-3">
                                        <div class="col-md-2">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ralan Batal</div>
                                                <div class="h5 mb-0 font-weight-bold text-danger" id="val-ralan-batal">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2.4 col-sm-6">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ralan BPJS</div>
                                                <div class="h5 mb-0 font-weight-bold text-primary" id="val-ralan-bpjs">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2.4 col-sm-6">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ralan Tunai</div>
                                                <div class="h5 mb-0 font-weight-bold text-success" id="val-ralan-tunai">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2.4 col-sm-6">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ralan Asuransi</div>
                                                <div class="h5 mb-0 font-weight-bold text-warning" id="val-ralan-asuransi">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2.4 col-sm-6">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ralan PT/Perusahaan</div>
                                                <div class="h5 mb-0 font-weight-bold text-info" id="val-ralan-perusahaan">0</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- D. RAWAT INAP KUNJUNGAN -->
                                <div class="tab-pane fade" id="pills-ranap" role="tabpanel" aria-labelledby="pills-ranap-tab">
                                    <div class="row g-3">
                                        <div class="col-md-2">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ranap Masuk</div>
                                                <div class="h5 mb-0 font-weight-bold text-primary" id="val-ranap-masuk">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ranap BPJS</div>
                                                <div class="h5 mb-0 font-weight-bold text-info" id="val-ranap-bpjs">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ranap Tunai</div>
                                                <div class="h5 mb-0 font-weight-bold text-success" id="val-ranap-tunai">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ranap Asuransi</div>
                                                <div class="h5 mb-0 font-weight-bold text-warning" id="val-ranap-asuransi">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ranap PT</div>
                                                <div class="h5 mb-0 font-weight-bold text-secondary" id="val-ranap-perusahaan">0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ranap Pulang</div>
                                                <div class="h5 mb-0 font-weight-bold text-danger" id="val-ranap-pulang">0</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- E. KEUANGAN RALAN -->
                                <div class="tab-pane fade" id="pills-ralan-keu" role="tabpanel" aria-labelledby="pills-ralan-keu-tab">
                                    <div class="row g-3">
                                        <div class="col-md-2.4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Total Pendapatan</div>
                                                <div class="h5 mb-0 font-weight-bold text-primary" id="val-keu-ralan-total">Rp 0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2.4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ralan Tunai</div>
                                                <div class="h5 mb-0 font-weight-bold text-success" id="val-keu-ralan-tunai">Rp 0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2.4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ralan BPJS</div>
                                                <div class="h5 mb-0 font-weight-bold text-info" id="val-keu-ralan-bpjs">Rp 0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2.4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ralan Asuransi</div>
                                                <div class="h5 mb-0 font-weight-bold text-warning" id="val-keu-ralan-asuransi">Rp 0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2.4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ralan Perusahaan</div>
                                                <div class="h5 mb-0 font-weight-bold text-secondary" id="val-keu-ralan-perusahaan">Rp 0</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- F. KEUANGAN RANAP -->
                                <div class="tab-pane fade" id="pills-ranap-keu" role="tabpanel" aria-labelledby="pills-ranap-keu-tab">
                                    <div class="row g-3">
                                        <div class="col-md-2.4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Total Pendapatan</div>
                                                <div class="h5 mb-0 font-weight-bold text-primary" id="val-keu-ranap-total">Rp 0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2.4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ranap Tunai</div>
                                                <div class="h5 mb-0 font-weight-bold text-success" id="val-keu-ranap-tunai">Rp 0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2.4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ranap BPJS</div>
                                                <div class="h5 mb-0 font-weight-bold text-info" id="val-keu-ranap-bpjs">Rp 0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2.4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ranap Asuransi</div>
                                                <div class="h5 mb-0 font-weight-bold text-warning" id="val-keu-ranap-asuransi">Rp 0</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2.4">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Ranap Perusahaan</div>
                                                <div class="h5 mb-0 font-weight-bold text-secondary" id="val-keu-ranap-perusahaan">Rp 0</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between chart-header-link" 
                     onclick="window.location.href='laporan_kunjungan.php'" title="Klik untuk lihat detail kunjungan">
                    <h6 class="m-0 font-weight-bold text-primary">Tren Kunjungan Tahun Ini <i class="fas fa-external-link-alt ms-2 small text-gray-400"></i></h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="chartTren"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Sumber Omzet Hari Ini</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2" style="height: 250px; cursor: pointer;" onclick="window.location.href='laporan_billing_global.php'">
                        <canvas id="chartOmzet"></canvas>
                    </div>
                    <div class="mt-4 text-center small text-muted">
                        *Klik chart untuk laporan detail
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3 chart-header-link" onclick="window.location.href='laporan_kinerja_dokter.php'" title="Klik untuk lihat kinerja dokter">
                    <h6 class="m-0 font-weight-bold text-primary">Top 5 Poliklinik Hari Ini (Live Queue) <i class="fas fa-external-link-alt ms-2 small text-gray-400"></i></h6>
                </div>
                <div class="card-body" id="container-top-poli">
                    <div class="text-center p-3">Loading...</div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ketersediaan Bed per Kelas (Realtime)</h6>
                </div>
                <div class="card-body" id="container-bed">
                    <div class="text-center p-3">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top 10 Penggunaan Farmasi Hari Ini</h6>
                </div>
                <div class="card-body">
                    <div style="height: 250px;">
                        <canvas id="chartFarmasi"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top 10 Booking Pendaftaran Online Hari Ini</h6>
                </div>
                <div class="card-body">
                    <div style="height: 250px;">
                        <canvas id="chartBooking"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="modalDetailBed" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-procedures me-2"></i>Pasien Rawat Inap: <span id="modalTitleKelas" class="fw-bold"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="tableDetailBed" class="table table-bordered table-striped table-sm w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Masuk</th>
                                <th>No. RM</th>
                                <th>Nama Pasien</th>
                                <th>Bangsal / Kamar</th>
                                <th>Kelas</th>
                                <th>Penjamin</th>
                                <th>Lama (Hari)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
    var chartTren, chartOmzet, chartFarmasi, chartBooking, tableDetailBed;
    var widgetsLoaded = false;

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    }

    $(document).ready(function() {
        // Init DataTables Modal
        tableDetailBed = $('#tableDetailBed').DataTable({
            "responsive": true, "pageLength": 10,
            "dom": 'Bfrtip', "buttons": ['excel'],
            "columns": [
                { "data": "waktu_masuk" },
                { "data": "no_rkm_medis" },
                { "data": "nm_pasien" },
                { "data": "nm_bangsal", render: function(d,t,r){ return d + ' (' + r.kd_kamar + ')'; } },
                { "data": "kelas" },
                { "data": "png_jawab" },
                { "data": "lama_hari", className: "text-center fw-bold" }
            ]
        });

        loadDashboardData();

        $('#collapseLegacyWidgets').on('show.bs.collapse', function () {
            if (!widgetsLoaded) {
                loadAdditionalWidgets();
            }
        });
    });

    function loadDashboardData() {
        $.ajax({
            url: 'api/data_dashboard.php', type: 'GET', dataType: 'json',
            success: function(res) {
                // 1. Omzet
                $('#val-omzet-total').text(formatRupiah(res.omzet.total));
                $('#val-omzet-tunai').text(formatRupiah(res.omzet.tunai));
                $('#val-omzet-piutang').text(formatRupiah(res.omzet.piutang));

                // 2. Kunjungan
                $('#val-visit-total').text(res.kunjungan.Total);
                $('#val-visit-ralan').text(res.kunjungan.Ralan);
                $('#val-visit-ranap').text(res.kunjungan.Ranap);
                
                // 3. BOR
                $('#val-bor').text(res.bed.bor_global + '%');
                $('#bar-bor').css('width', res.bed.bor_global + '%');
                
                // 4. Kunjungan Aktif
                $('#val-aktif').text(res.kunjungan_aktif.toLocaleString());

                // 5. Charts & Widgets
                renderChartTren(res.tren);
                renderChartOmzet(res.omzet);
                renderTopPoli(res.top_poli, res.kunjungan.Total);
                renderBedMonitor(res.bed.per_kelas);
                renderChartFarmasi(res.top_farmasi);
                renderChartBooking(res.top_booking);
            },
            error: function() { console.error("Gagal memuat data dashboard"); }
        });
    }

    function renderTopPoli(data, totalKunjungan) {
        var html = '';
        if(data.length > 0) {
            data.forEach(function(item) {
                var pct = (totalKunjungan > 0) ? (item.jumlah / totalKunjungan) * 100 : 0;
                html += `
                    <h4 class="small font-weight-bold">${item.nm_poli} <span class="float-end">${item.jumlah}</span></h4>
                    <div class="progress mb-4">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: ${pct}%"></div>
                    </div>
                `;
            });
        } else { html = '<div class="text-center text-muted">Belum ada kunjungan hari ini</div>'; }
        $('#container-top-poli').html(html);
    }

    function renderBedMonitor(data) {
        var html = '';
        if(data.length > 0) {
            data.forEach(function(item) {
                var bor = (item.total > 0) ? (item.terisi / item.total) * 100 : 0;
                var kosong = item.total - item.terisi;
                var color = bor > 85 ? 'bg-danger' : (bor > 60 ? 'bg-warning' : 'bg-success');
                
                html += `
                    <div class="mb-3 p-2 rounded bed-row" onclick="showBedDetail('${item.kelas}')">
                        <div class="d-flex justify-content-between small font-weight-bold">
                            <span class="text-primary">${item.kelas}</span>
                            <span>Terisi ${item.terisi} dari ${item.total} (${Math.round(bor)}%)</span>
                        </div>
                        <div class="progress mt-1" style="height: 10px;">
                            <div class="progress-bar ${color}" role="progressbar" style="width: ${bor}%"></div>
                        </div>
                        <div class="text-end mt-1" style="font-size: 0.75rem;">
                            <span class="text-success fw-bold">Kosong: ${kosong} Bed</span>
                        </div>
                    </div>
                `;
            });
        } else { html = '<div class="text-center text-muted">Data bed tidak tersedia</div>'; }
        $('#container-bed').html(html);
    }

    function showBedDetail(kelas) {
        $('#modalTitleKelas').text(kelas);
        $('#modalDetailBed').modal('show');
        
        $.ajax({
            url: 'api/data_detail_bed.php', type: 'GET', data: {kelas: kelas}, dataType: 'json',
            success: function(res) {
                tableDetailBed.clear().rows.add(res.data).draw();
            },
            error: function() { console.error("Gagal memuat detail bed"); }
        });
    }

    function renderChartTren(data) {
        var ctx = document.getElementById("chartTren").getContext('2d');
        if(chartTren) chartTren.destroy();
        
        chartTren = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"],
                datasets: [
                    {
                        label: "Total",
                        data: data.total,
                        borderColor: "#4e73df",
                        borderWidth: 4,
                        tension: 0.3,
                        pointRadius: 0
                    },
                    {
                        label: "Rawat Jalan",
                        data: data.ralan,
                        borderColor: "rgba(246, 194, 62, 0.5)",
                        borderWidth: 2,
                        borderDash: [5, 5],
                        tension: 0.3,
                        pointRadius: 0
                    },
                    {
                        label: "Rawat Inap",
                        data: data.ranap,
                        borderColor: "rgba(28, 200, 138, 0.5)",
                        borderWidth: 2,
                        borderDash: [5, 5],
                        tension: 0.3,
                        pointRadius: 0
                    }
                ],
            },
            options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: {display: true} } }
        });
    }

    function renderChartOmzet(dataObj) {
        var ctx = document.getElementById("chartOmzet").getContext('2d');
        if(chartOmzet) chartOmzet.destroy();
        chartOmzet = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: dataObj.labels,
                datasets: [{
                    data: dataObj.data,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '70%' }
        });
    }

    function renderChartFarmasi(dataObj) {
        var ctx = document.getElementById("chartFarmasi").getContext('2d');
        if(chartFarmasi) chartFarmasi.destroy();
        chartFarmasi = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dataObj.labels,
                datasets: [{
                    label: 'Jumlah Penggunaan',
                    data: dataObj.data,
                    backgroundColor: '#1cc88a',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    function renderChartBooking(dataObj) {
        var ctx = document.getElementById("chartBooking").getContext('2d');
        if(chartBooking) chartBooking.destroy();
        chartBooking = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dataObj.labels,
                datasets: [{
                    label: 'Jumlah Booking',
                    data: dataObj.data,
                    backgroundColor: '#4e73df',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    function loadAdditionalWidgets() {
        $.ajax({
            url: 'api/data_additional_widgets.php',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                // 1. Executive Premium KPIs
                $('#val-prem-satusehat').text(res.premium.satusehat_rate + '%');
                $('#val-prem-deadstock').text(formatRupiah(res.premium.dead_stock_value));
                $('#val-prem-waittime').text(res.premium.avg_wait_time + ' Menit');

                // 2. Umum & Kamar
                $('#val-gen-totalrm').text(res.general.total_rm.toLocaleString());
                $('#val-gen-ralantoday').text(res.general.ralan_today.toLocaleString());
                $('#val-gen-ranaptoday').text(res.general.ranap_today.toLocaleString());
                $('#val-gen-totalmonth').text(res.general.total_month.toLocaleString());

                // 3. IGD
                $('#val-igd-daftar').text(res.igd.daftar.toLocaleString());
                $('#val-igd-dirawat').text(res.igd.dirawat.toLocaleString());
                $('#val-igd-belum').text(res.igd.belum.toLocaleString());
                $('#val-igd-dirujuk').text(res.igd.dirujuk.toLocaleString());
                $('#val-igd-selesai').text(res.igd.selesai.toLocaleString());
                $('#val-igd-batal').text(res.igd.batal.toLocaleString());

                // 4. Ralan Kunjungan
                $('#val-ralan-batal').text(res.ralan_kunjungan.batal.toLocaleString());
                $('#val-ralan-bpjs').text(res.ralan_kunjungan.bpjs.toLocaleString());
                $('#val-ralan-tunai').text(res.ralan_kunjungan.tunai.toLocaleString());
                $('#val-ralan-asuransi').text(res.ralan_kunjungan.asuransi.toLocaleString());
                $('#val-ralan-perusahaan').text(res.ralan_kunjungan.perusahaan.toLocaleString());

                // 5. Ranap Kunjungan
                $('#val-ranap-masuk').text(res.ranap_kunjungan.masuk.toLocaleString());
                $('#val-ranap-bpjs').text(res.ranap_kunjungan.bpjs.toLocaleString());
                $('#val-ranap-tunai').text(res.ranap_kunjungan.tunai.toLocaleString());
                $('#val-ranap-asuransi').text(res.ranap_kunjungan.asuransi.toLocaleString());
                $('#val-ranap-perusahaan').text(res.ranap_kunjungan.perusahaan.toLocaleString());
                $('#val-ranap-pulang').text(res.ranap_kunjungan.pulang.toLocaleString());

                // 6. Keuangan Ralan
                $('#val-keu-ralan-total').text(formatRupiah(res.ralan_keuangan.total));
                $('#val-keu-ralan-tunai').text(formatRupiah(res.ralan_keuangan.tunai));
                $('#val-keu-ralan-bpjs').text(formatRupiah(res.ralan_keuangan.bpjs));
                $('#val-keu-ralan-asuransi').text(formatRupiah(res.ralan_keuangan.asuransi));
                $('#val-keu-ralan-perusahaan').text(formatRupiah(res.ralan_keuangan.perusahaan));

                // 7. Keuangan Ranap
                $('#val-keu-ranap-total').text(formatRupiah(res.ranap_keuangan.total));
                $('#val-keu-ranap-tunai').text(formatRupiah(res.ranap_keuangan.tunai));
                $('#val-keu-ranap-bpjs').text(formatRupiah(res.ranap_keuangan.bpjs));
                $('#val-keu-ranap-asuransi').text(formatRupiah(res.ranap_keuangan.asuransi));
                $('#val-keu-ranap-perusahaan').text(formatRupiah(res.ranap_keuangan.perusahaan));

                // UI adjustments
                $('#widgets-loading').addClass('d-none');
                $('#widgets-container').removeClass('d-none');
                $('#widget-load-badge').text('Data Dimuat').removeClass('bg-secondary').addClass('bg-success');
                widgetsLoaded = true;
            },
            error: function() {
                $('#widgets-loading').html('<div class="text-danger py-3"><i class="fas fa-exclamation-triangle fa-2x"></i><p class="mt-2">Gagal memuat data metrik tambahan.</p></div>');
            }
        });
    }
</script>
<?php $page_js = ob_get_clean(); ?>
<?php require_once('includes/footer.php'); ?>