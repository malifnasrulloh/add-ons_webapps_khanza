<div id="sidebar-wrapper">
    <div class="sidebar-heading text-center fw-bold">
        <img src="<?= isset($logo_b64) ? $logo_b64 : 'logo.php' ?>" width="30" class="bg-white rounded p-1 me-2"> 
        SIMRS Casemix
    </div>
    
    <div class="list-group list-group-flush mt-3" style="flex-grow: 1; overflow-y: auto; padding-bottom: 170px;">
        <?php 
            $page = basename($_SERVER['PHP_SELF']); 
            
            // Definisi Grup untuk Auto-Expand
            $group_dashboard = ['dashboard.php', 'plafon_ranap.php', 'laporan_semongko.php'];
            $group_rm        = ['laporan_indikator_ranap.php', 'laporan_penyakit.php', 'laporan_ppra.php'];
            $group_rl3       = [
                'laporan_rl_3.1.php', 'laporan_rl_3.2.php', 'laporan_rl_3.3.php', 'laporan_rl_3.4.php', 
                'laporan_rl_3.5.php', 'laporan_rl_3.6.php', 'laporan_rl_3.7.php', 'laporan_rl_3.8.php', 
                'laporan_rl_3.9.php', 'laporan_rl_3.10.php', 'laporan_rl_3.11.php', 'laporan_rl_3.12.php', 
                'laporan_rl_3.13.php', 'laporan_rl_3.14.php', 'laporan_rl_3.15.php', 'laporan_rl_3.16.php', 
                'laporan_rl_3.17.php', 'laporan_rl_3.18.php', 'laporan_rl_3.19.php'
            ];
            $group_morbiditas = ['laporan_rl_4.1.php', 'laporan_rl_4.2.php', 'laporan_rl_4.3.php', 'laporan_rl_5.1.php', 'laporan_rl_5.2.php', 'laporan_rl_5.3.php'];
            
            $show_dashboard  = in_array($page, $group_dashboard) ? 'show' : '';
            $active_dash     = in_array($page, $group_dashboard) ? 'active' : '';
            $show_rm         = in_array($page, $group_rm) ? 'show' : '';
            $active_rm       = in_array($page, $group_rm) ? 'active' : '';
            $show_rl3        = in_array($page, $group_rl3) ? 'show' : '';
            $active_rl3      = in_array($page, $group_rl3) ? 'active' : '';
            $show_morbiditas = in_array($page, $group_morbiditas) ? 'show' : '';
            $active_morbiditas = in_array($page, $group_morbiditas) ? 'active' : '';
        ?>

        <small class="text-uppercase text-white-50 px-3 mb-1" style="font-size:0.65rem; letter-spacing: 1px;">Utama</small>
        
        <a class="list-group-item list-group-item-action dropdown-toggle <?= $active_dash ?>" href="#menuDashboard" data-bs-toggle="collapse" aria-expanded="<?= !empty($show_dashboard) ? 'true' : 'false' ?>">
            <i class="fas fa-tachometer-alt w-25 text-center"></i> Dashboard
        </a>
        <div class="collapse <?= $show_dashboard ?>" id="menuDashboard">
            <div class="bg-dark bg-opacity-25 py-1">
                <a href="dashboard.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='dashboard.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;">
                    <i class="fas fa-file-invoice me-2"></i> Monitoring Berkas
                </a>
                <a href="plafon_ranap.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='plafon_ranap.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;">
                    <i class="fas fa-hand-holding-usd me-2"></i> Input Plafon BPJS
                </a>
                <a href="laporan_semongko.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_semongko.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;">
                    <i class="fas fa-bed me-2"></i> Laporan Semongko
                </a>
            </div>
        </div>

        <a class="list-group-item list-group-item-action dropdown-toggle mt-1 <?= $active_rm ?>" href="#menuRM" data-bs-toggle="collapse" aria-expanded="<?= !empty($show_rm) ? 'true' : 'false' ?>">
            <i class="fas fa-book-medical w-25 text-center"></i> Rekam Medis
        </a>
        <div class="collapse <?= $show_rm ?>" id="menuRM">
            <div class="bg-dark bg-opacity-25 py-1">
                <a href="laporan_indikator_ranap.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_indikator_ranap.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;">
                    <i class="fas fa-chart-line me-2"></i> Indikator (BOR/LOS)
                </a>
                <a href="laporan_penyakit.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_penyakit.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;">
                    <i class="fas fa-disease me-2"></i> Data Penyakit
                </a>
                <a href="laporan_ppra.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_ppra.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;">
                    <i class="fas fa-pills me-2"></i> Data PPRA
                </a>
            </div>
        </div>

        <div class="mt-3"></div>
        <small class="text-uppercase text-white-50 px-3 mb-1" style="font-size:0.65rem; letter-spacing: 1px;">Pelaporan SIRS 6.3</small>

        <a class="list-group-item list-group-item-action dropdown-toggle mt-1 <?= $active_rl3 ?>" href="#menuRL3" data-bs-toggle="collapse" aria-expanded="<?= !empty($show_rl3) ? 'true' : 'false' ?>">
            <i class="fas fa-hospital w-25 text-center"></i> RL 3 (Pelayanan)
        </a>
        <div class="collapse <?= $show_rl3 ?>" id="menuRL3">
            <div class="bg-dark bg-opacity-25 py-1 scrollable-menu" style="max-height: 400px; overflow-y: auto;">
                <div class="ps-4 py-1 text-white-50 small border-bottom border-secondary mx-2 mb-1" style="font-size: 0.7rem;">DASAR & KUNJUNGAN</div>
                <a href="laporan_rl_3.1.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.1.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-chart-bar me-2"></i> RL 3.1</a>
                <a href="laporan_rl_3.2.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.2.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-bed me-2"></i> RL 3.2</a>
                <a href="laporan_rl_3.3.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.3.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-ambulance me-2"></i> RL 3.3</a>
                <a href="laporan_rl_3.4.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.4.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-users me-2"></i> RL 3.4</a>
                <a href="laporan_rl_3.5.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.5.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-walking me-2"></i> RL 3.5</a>

                <div class="ps-4 py-1 text-white-50 small border-bottom border-secondary mx-2 mb-1 mt-2" style="font-size: 0.7rem;">KIA & ANAK</div>
                <a href="laporan_rl_3.6.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.6.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-baby me-2"></i> RL 3.6</a>
                <a href="laporan_rl_3.7.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.7.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-child me-2"></i> RL 3.7</a>
                <a href="laporan_rl_3.16.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.16.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-female me-2"></i> RL 3.16</a>

                <div class="ps-4 py-1 text-white-50 small border-bottom border-secondary mx-2 mb-1 mt-2" style="font-size: 0.7rem;">PENUNJANG & RUJUKAN</div>
                <a href="laporan_rl_3.8.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.8.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-flask me-2"></i> RL 3.8</a>
                <a href="laporan_rl_3.9.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.9.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-x-ray me-2"></i> RL 3.9</a>
                <a href="laporan_rl_3.10.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.10.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-exchange-alt me-2"></i> RL 3.10</a>

                <div class="ps-4 py-1 text-white-50 small border-bottom border-secondary mx-2 mb-1 mt-2" style="font-size: 0.7rem;">TINDAKAN & SPESIALIS</div>
                <a href="laporan_rl_3.11.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.11.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-tooth me-2"></i> RL 3.11</a>
                <a href="laporan_rl_3.12.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.12.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-scissors me-2"></i> RL 3.12</a>
                <a href="laporan_rl_3.13.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.13.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-wheelchair me-2"></i> RL 3.13</a>
                <a href="laporan_rl_3.14.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.14.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-microscope me-2"></i> RL 3.14</a>
                <a href="laporan_rl_3.15.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.15.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-brain me-2"></i> RL 3.15</a>

                <div class="ps-4 py-1 text-white-50 small border-bottom border-secondary mx-2 mb-1 mt-2" style="font-size: 0.7rem;">FARMASI & KEUANGAN</div>
                <a href="laporan_rl_3.17.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.17.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-boxes me-2"></i> RL 3.17</a>
                <a href="laporan_rl_3.18.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.18.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-prescription-bottle-alt me-2"></i> RL 3.18</a>
                <a href="laporan_rl_3.19.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_3.19.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-wallet me-2"></i> RL 3.19</a>
            </div>
        </div>

        <a class="list-group-item list-group-item-action dropdown-toggle mt-1 <?= $active_morbiditas ?>" href="#menuMorbiditas" data-bs-toggle="collapse" aria-expanded="<?= !empty($show_morbiditas) ? 'true' : 'false' ?>">
            <i class="fas fa-virus w-25 text-center"></i> RL 4 & 5 (Morbiditas)
        </a>
        <div class="collapse <?= $show_morbiditas ?>" id="menuMorbiditas">
            <div class="bg-dark bg-opacity-25 py-1">
                <a href="laporan_rl_4.1.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_4.1.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-file-medical me-2"></i> Morbiditas Ranap (4.1)</a>
                <a href="laporan_rl_4.2.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_4.2.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-list-ol me-2"></i> 10 Besar Ranap (4.2)</a>
                <a href="laporan_rl_4.3.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_4.3.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-skull me-2"></i> 10 Besar Mati (4.3)</a>
                <div class="border-top border-secondary mx-3 my-1 opacity-25"></div>
                <a href="laporan_rl_5.1.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_5.1.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-walking me-2"></i> Morbiditas Ralan (5.1)</a>
                <a href="laporan_rl_5.2.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_5.2.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-chart-bar me-2"></i> 10 Besar Baru (5.2)</a>
                <a href="laporan_rl_5.3.php" class="list-group-item list-group-item-action border-0 ps-5 bg-transparent text-white <?= ($page=='laporan_rl_5.3.php')?'fw-bold text-warning':'' ?>" style="font-size: 0.8rem;"><i class="fas fa-users-viewfinder me-2"></i> 10 Besar Kunjungan (5.3)</a>
            </div>
        </div>
        
        <div class="mt-auto">
            <small class="text-uppercase text-white-50 px-3 mt-4 mb-1" style="font-size:0.65rem; letter-spacing: 1px;">Akun</small>
            <?php if(isset($_SESSION['casemix_role']) && $_SESSION['casemix_role'] === 'Super Admin'): ?>
            <a href="user_management.php" class="list-group-item list-group-item-action <?= ($page=='user_management.php')?'fw-bold text-warning':'' ?>">
                <i class="fas fa-users-cog w-25 text-center"></i> Manajemen User
            </a>
            <?php endif; ?>
            <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                <i class="fas fa-sign-out-alt w-25 text-center"></i> Keluar Sistem
            </a>
        </div>
    </div>
    
    <div class="text-center text-white-50 p-2 w-100 position-absolute bottom-0" style="background: rgba(0,0,0,0.15); z-index: 10; border-top: 1px solid rgba(255,255,255,0.08); font-size: 0.7rem;">
        <div>&copy; <?= date('Y') ?> <?= isset($nama_instansi) ? $nama_instansi : 'RS' ?></div>
        <div class="border-top border-secondary border-opacity-25 my-1"></div>
        <div style="font-size: 0.68rem; line-height: 1.3;">
            <span class="text-white opacity-75">Ichsan Leonhart</span>
            <br>
            <a href="https://saweria.co/ichsanleonhart" target="_blank" class="text-warning text-decoration-none fw-bold" style="font-size: 0.65rem;">
                <i class="fas fa-donate me-1"></i>saweria.co/ichsanleonhart
            </a>
            <br>
            <a href="https://wa.me/6285726123777" target="_blank" class="text-white-50 text-decoration-none opacity-75"><i class="fab fa-whatsapp text-success me-1"></i>6285726123777</a>
            <span class="mx-1 opacity-25">|</span>
            <a href="https://t.me/IchsanLeonhart" target="_blank" class="text-white-50 text-decoration-none opacity-75"><i class="fab fa-telegram text-info me-1"></i>@IchsanLeonhart</a>
        </div>

        <a href="#" class="text-white-50 text-decoration-none mt-1 d-inline-block border-top border-secondary border-opacity-25 pt-1 w-100" data-bs-toggle="modal" data-bs-target="#changelogModal" style="opacity: 0.8; font-size: 0.65rem;">
            <i class="fas fa-code-branch me-1"></i> Log Pembaruan v1.5.0
        </a>
    </div>
</div>

<style>
    /* GLOBAL LAYOUT FIX */
    #sidebar-wrapper { 
        min-height: 100vh; width: 250px; margin-left: -250px; 
        position: fixed; top: 0; left: 0; bottom: 0; 
        z-index: 1050; transition: margin .25s ease-out; 
        background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%); 
        color: #fff; box-shadow: 4px 0 15px rgba(0,0,0,0.2);
        display: flex;
        flex-direction: column;
    }
    
    /* Custom Scrollbar for Sidebar List */
    #sidebar-wrapper .list-group::-webkit-scrollbar { width: 4px; }
    #sidebar-wrapper .list-group::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
    #sidebar-wrapper .list-group::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
    #page-content-wrapper { width: 100%; transition: margin .25s ease-out; }
    
    /* DESKTOP STATE */
    @media (min-width: 768px) { 
        #sidebar-wrapper { margin-left: 0; } 
        #page-content-wrapper { margin-left: 250px; width: calc(100% - 250px); } 
        body.sb-sidenav-toggled #sidebar-wrapper { margin-left: -250px; } 
        body.sb-sidenav-toggled #page-content-wrapper { margin-left: 0; width: 100%; } 
    }

    /* MOBILE OVERLAY */
    #overlay { display: none; position: fixed; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1040; top:0; left:0; }
    body.sb-sidenav-toggled #overlay { display: block; }
    @media (min-width: 768px) { body.sb-sidenav-toggled #overlay { display: none; } }

    /* Custom Scrollbar */
    .scrollable-menu::-webkit-scrollbar { width: 4px; }
    .scrollable-menu::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
    .scrollable-menu::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }

    /* Rotasi Icon Chevron */
    .list-group-item.dropdown-toggle::after {
        display: inline-block; margin-left: auto; vertical-align: middle;
        content: "\f078"; font-family: "Font Awesome 5 Free"; font-weight: 900;
        font-size: 0.7rem; float: right; transition: transform 0.3s ease;
    }
    .list-group-item.dropdown-toggle[aria-expanded="true"]::after { transform: rotate(180deg); }
    #sidebar-wrapper .list-group-item:hover { background: rgba(255,255,255,0.1); color: #fff !important; }
</style>
