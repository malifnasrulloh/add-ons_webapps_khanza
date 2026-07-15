<?php
// FILE: index.php
// Pastikan kompatibel PHP 7.3+
require_once 'auth.php'; // Pastikan login via auth.php
require_once 'koneksi.php';

// 1. Ambil Data Instansi & Logo (Blob)
// Kita ambil limit 1 saja
$q_instansi = $pdo->query("SELECT nama_instansi, logo FROM setting LIMIT 1");
$instansi   = $q_instansi->fetch();
$nama_rs    = htmlspecialchars($instansi['nama_instansi'] ?? '', ENT_QUOTES, 'UTF-8');

// Konversi BLOB ke Base64 untuk ditampilkan
// Cek jika logo ada isinya
if ($instansi['logo']) {
    $logo_b64 = base64_encode($instansi['logo']);
    $logo_src = 'data:image/jpeg;base64,' . $logo_b64;
} else {
    $logo_src = 'https://via.placeholder.com/50'; // Placeholder jika kosong
}

// 2. Ambil Data Bangsal untuk Dropdown
$sql_depo = "SELECT kd_bangsal, nm_bangsal FROM bangsal WHERE status='1' ORDER BY nm_bangsal ASC";
$res_depo = $pdo->query($sql_depo);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Command Center - <?= $nama_rs ?></title>
    
    <link rel="icon" type="image/png" href="<?= $logo_src ?>">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        :root {
            --primary-bg: #f3f4f6;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --danger-color: #dc2626;
            --success-color: #059669;
            --accent-color: #2563eb;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--primary-bg);
            color: var(--text-main);
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Navbar */
        .navbar-custom {
            background: var(--card-bg);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            padding: 0.8rem 2rem;
            border-bottom: 3px solid var(--accent-color);
        }

        .brand-logo {
            height: 45px;
            width: 45px;
            object-fit: cover;
            margin-right: 15px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

        /* Dashboard Cards */
        .dashboard-card {
            background: var(--card-bg);
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        /* Status Indicator Large */
        .status-indicator {
            padding: 2.5rem;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 1.5rem;
            transition: all 0.5s ease;
            position: relative;
            overflow: hidden;
        }
        
        .status-safe {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .status-danger {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #991b1b;
            border: 1px solid #fecaca;
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        /* Table Styling */
        .table-custom thead th {
            background-color: #f9fafb;
            color: #6b7280;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            font-weight: 700;
            border-bottom: 2px solid #e5e7eb;
        }
        .table-custom td {
            vertical-align: middle;
            font-size: 0.9rem;
            border-bottom: 1px solid #f3f4f6;
        }
        .stok-badge {
            font-size: 1rem;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 6px;
            min-width: 50px;
            display: inline-block;
        }

        /* Overlay Welcome Screen */
        #interaction-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.98);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        .btn-start {
            padding: 12px 35px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            transition: transform 0.2s;
        }
        .btn-start:hover { transform: scale(1.05); box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4); }

        /* Blink Animation */
        .blink-text { animation: blinker-text 1s linear infinite; }
        @keyframes blinker-text { 50% { opacity: 0; } }

        /* Countdown Badge */
        .countdown-badge {
            font-family: 'Courier New', monospace;
            font-weight: 800;
            letter-spacing: 1px;
        }

    </style>
</head>
<body>

<div id="interaction-overlay">
    <img src="<?= $logo_src ?>" width="90" height="90" class="mb-4 rounded-circle shadow-sm" style="object-fit:cover;">
    <h3 class="mb-2 fw-bold text-dark">Sistem Monitoring Stok</h3>
    <p class="text-secondary mb-4">Klik tombol di bawah untuk mengaktifkan Dashboard & Alarm</p>
    <button class="btn btn-primary btn-start" onclick="startSystem()">
        <i class="bi bi-power me-2"></i> AKTIFKAN MONITORING
    </button>
</div>

<nav class="navbar navbar-custom d-flex justify-content-between align-items-center sticky-top">
    <div class="d-flex align-items-center">
        <img src="<?= $logo_src ?>" alt="Logo" class="brand-logo rounded-circle">
        <div>
            <h6 class="mb-0 fw-bold text-dark"><?= $nama_rs ?></h6>
            <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Alarm Monitoring Stok Menipis</span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="d-none d-md-flex align-items-center">
            <a href="index.php" class="btn btn-primary btn-sm me-2"><i class="bi bi-bell"></i> Alarm Stok</a>
            <a href="stok_terakhir.php" class="btn btn-outline-primary btn-sm me-2"><i class="bi bi-box-seam"></i> Cek Stok</a>
            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-box-arrow-right"></i> Logout</button>
        </div>
        <div class="text-end d-none d-md-block border-start ps-3">
            <div id="jam-digital" class="fw-bold fs-5 font-monospace text-dark">00:00:00</div>
            <div id="tanggal-digital" class="small text-muted" style="font-size: 0.75rem;">...</div>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="dashboard-card p-4 h-100">
                <h6 class="text-uppercase text-secondary fw-bold mb-4" style="font-size: 0.8rem; letter-spacing: 1px;">
                    <i class="bi bi-sliders me-2"></i>Konfigurasi Depo
                </h6>
                
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">Lokasi Pantau</label>
                    <select id="pilihDepo" class="form-select">
                        <?php while($row = $res_depo->fetch()): ?>
                            <option value="<?= htmlspecialchars($row['kd_bangsal'], ENT_QUOTES, 'UTF-8') ?>" <?= $row['kd_bangsal'] == 'AP' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['nm_bangsal'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded mb-4 border">
                    <div>
                        <span class="fw-bold d-block text-dark" style="font-size: 0.9rem;">
                            <i class="bi bi-volume-up-fill me-1 text-primary"></i> Alarm Suara
                        </span>
                        <small class="text-muted" style="font-size: 0.75rem;">Bunyi otomatis saat kritis</small>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="toggleSuara" style="transform: scale(1.3);" checked>
                    </div>
                </div>

                <div class="mt-auto pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted fw-bold">Status Sistem</small>
                        <span id="countdown-wrapper" class="badge bg-light text-dark border countdown-badge">
                            <i class="bi bi-arrow-repeat me-1"></i> <span id="countdown-timer">--</span>s
                        </span>
                    </div>
                    <div id="status-badge" class="alert alert-secondary py-2 px-3 mb-0 small text-center fw-bold">
                        <i class="bi bi-hourglass-split me-1"></i> Standby...
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="dashboard-card p-4 h-100">
                <div id="status-panel" class="status-indicator status-safe">
                    <div class="d-flex justify-content-center align-items-center mb-2">
                        <i class="bi bi-shield-check display-3 me-3"></i>
                        <div class="text-start">
                            <h2 class="fw-bold mb-0">STOK AMAN</h2>
                            <small class="opacity-75">Tidak ada obat di bawah stok minimal</small>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-uppercase text-secondary fw-bold mb-0" style="font-size: 0.8rem; letter-spacing: 1px;">
                        Live Data Feed
                    </h6>
                    <span class="badge bg-primary rounded-pill" id="total-items">0 Item</span>
                </div>

                <div class="table-responsive flex-grow-1">
                    <table class="table table-custom table-hover w-100" id="tabel-stok">
                        <thead>
                            <tr>
                                <th width="15%">Kode</th>
                                <th width="40%">Nama Obat</th>
                                <th width="15%">Satuan</th>
                                <th width="15%" class="text-center">Min</th>
                                <th width="15%" class="text-center">Sisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center text-muted small py-3 mt-auto">
    <div class="container d-flex justify-content-center align-items-center gap-3 flex-wrap">
        <span>SIMRS Monitoring &copy; <?= date('Y') ?> <?= isset($nama_rs) ? $nama_rs : 'Farmasi' ?></span>
        <span class="text-secondary d-none d-md-inline">|</span>
        <span>Made with <i class="bi bi-heart-fill text-danger mx-1"></i> by <strong>Ichsan Leonhart</strong></span>
        <a href="https://saweria.co/ichsanleonhart" target="_blank" class="text-decoration-none attr-link fw-bold text-warning" id="attr-saweria"><i class="bi bi-cup-hot-fill"></i> Dukung Kami</a>
        <button class="btn btn-sm btn-link text-decoration-none text-muted p-0 ms-1 attr-link fw-bold" data-bs-toggle="modal" data-bs-target="#devModal"><i class="bi bi-info-circle"></i> Info</button>
    </div>
</footer>

<!-- Modal Developer Info -->
<div class="modal fade" id="devModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-code-slash me-2"></i>Tentang Pengembang</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-4">
        <img src="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" alt="QRIS Ichsan" class="img-fluid rounded shadow-sm mb-3" style="max-width:200px" id="attr-qris">
        <h5 class="fw-bold mb-1">Ichsan Leonhart</h5>
        <div class="d-flex justify-content-center gap-3 mb-3 mt-2">
            <a href="https://wa.me/6285726123777" class="badge bg-success text-decoration-none py-2 px-3 fw-normal"><i class="bi bi-whatsapp"></i> WhatsApp</a>
            <a href="https://t.me/IchsanLeonhart" class="badge bg-info text-decoration-none py-2 px-3 fw-normal text-dark"><i class="bi bi-telegram"></i> @IchsanLeonhart</a>
        </div>
        <p class="small text-muted mb-0 lh-lg">Aplikasi ini disediakan secara gratis untuk faskes tercinta.<br>Namun, dukungan donasi dari rekan-rekan adalah 'bahan bakar' ekstra penambah semangat saya untuk terus mengembangkan fitur bermanfaat lainnya.</p>
      </div>
      <div class="modal-footer border-0 justify-content-center bg-light">
        <a href="https://saweria.co/ichsanleonhart" target="_blank" class="btn btn-warning px-4 fw-bold text-dark"><i class="bi bi-cup-hot-fill"></i> Donasi via Saweria</a>
      </div>
    </div>
  </div>
</div>

<audio id="audioAlarm" loop preload="auto">
    <source src="audio/alarm.mp3" type="audio/mpeg">
</audio>

<!-- Modal Logout Confirmation -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Logout</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-4">
        <p class="fs-5 mb-0">Apakah Anda yakin ingin keluar dari sistem keamanan stok?</p>
      </div>
      <div class="modal-footer border-0 justify-content-center bg-light">
        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
        <a href="logout.php" class="btn btn-danger px-4">Ya, Logout</a>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    // --- KONFIGURASI ---
    const REFRESH_INTERVAL = 10; // Detik (Waktu hitung mundur)

    // --- GLOBAL VARIABLES ---
    const alarm = document.getElementById('audioAlarm');
    let isSoundOn = true;
    let isInitialized = false;
    let timerValue = REFRESH_INTERVAL;
    let timerInterval;
    let dataTableStok;

    // Utility script sanitasi input untuk display DOM
    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    // 1. Inisialisasi Select2 & DataTables
    $('#pilihDepo').select2({
        theme: "bootstrap-5",
        width: '100%'
    });

    dataTableStok = $('#tabel-stok').DataTable({
        paging: false,
        ordering: true,
        info: true,
        searching: true,
        scrollY: "35vh",
        scrollCollapse: true,
        language: { 
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
            emptyTable: 'Tidak ada item yang perlu perhatian saat ini.',
            info: 'Menampilkan _TOTAL_ item kritis',
            infoEmpty: 'Menampilkan 0 item',
            search: '_INPUT_',
            searchPlaceholder: 'Cari obat...'
        },
        dom: '<"d-flex justify-content-between align-items-center"<"d-none">f>rt<"mt-2"i>'
    });

    // 2. JAM DIGITAL
    setInterval(() => {
        const now = new Date();
        document.getElementById('jam-digital').innerText = now.toLocaleTimeString('id-ID');
        document.getElementById('tanggal-digital').innerText = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }, 1000);

    // 3. START SYSTEM (Unlock Audio & Start Timer)
    function startSystem() {
        // Unlock Audio Context
        alarm.play().then(() => {
            alarm.pause();
            alarm.currentTime = 0;
        }).catch(e => console.log("Audio unlock status:", e));

        $('#interaction-overlay').fadeOut(400);
        isInitialized = true;
        
        // Cek data pertama kali
        cekStok();
        // Mulai hitung mundur
        startCountdown();
    }

    // 4. COUNTDOWN TIMER LOGIC
    function startCountdown() {
        if(timerInterval) clearInterval(timerInterval); // Reset jika ada duplikat
        
        timerValue = REFRESH_INTERVAL;
        $('#countdown-timer').text(timerValue);

        timerInterval = setInterval(() => {
            if(!isInitialized) return;

            timerValue--;
            $('#countdown-timer').text(timerValue);

            if (timerValue <= 0) {
                cekStok();           // Trigger cek stok
                timerValue = REFRESH_INTERVAL; // Reset nilai visual segera
            }
        }, 1000);
    }

    // 5. CEK STOK (AJAX)
    function cekStok() {
        let depo = $('#pilihDepo').val();
        
        // Update UI status sedang loading (opsional, biar user tau sedang fetch)
        $('#status-badge').html('<i class="bi bi-cloud-arrow-down-fill animate-bounce"></i> Updating...').removeClass('alert-danger alert-success').addClass('alert-primary');

        $.ajax({
            url: 'api_cek_stok.php',
            type: 'GET',
            data: { depo: depo },
            dataType: 'json',
            success: function(response) {
                // Update Badge Status Koneksi
                let waktu = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit', second:'2-digit'});
                $('#status-badge').html('<i class="bi bi-wifi"></i> Terhubung • ' + waktu)
                                  .removeClass('alert-primary alert-danger').addClass('alert-success');

                $('#total-items').text(response.jumlah_warning + ' Item Warning');

                let rows = [];

                if (response.jumlah_warning > 0) {
                    // --- KONDISI KRITIS ---
                    $('#status-panel').removeClass('status-safe').addClass('status-danger');
                    $('#status-panel').html(`
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <i class="bi bi-exclamation-triangle-fill display-3 me-3 blink-text"></i>
                            <div class="text-start">
                                <h2 class="fw-bold mb-0 text-danger">PERHATIAN!</h2>
                                <p class="mb-0 fw-bold">${response.jumlah_warning} Obat Menipis</p>
                            </div>
                        </div>
                    `);

                    // Konstruksi Array untuk DataTables
                    $.each(response.data, function(i, item) {
                        rows.push([
                            `<span class="font-monospace text-muted small">${escapeHtml(item.kode_brng)}</span>`,
                            `<span class="fw-bold text-dark">${escapeHtml(item.nama_brng)}</span>`,
                            `<span class="text-muted small">${escapeHtml(item.satuan)}</span>`,
                            `<div class="text-center fw-bold text-secondary">${escapeHtml(item.stokminimal)}</div>`,
                            `<div class="text-center"><span class="stok-badge bg-danger text-white shadow-sm blink-text">${escapeHtml(item.stok)}</span></div>`
                        ]);
                    });

                    // Bunyikan Alarm (Hanya jika belum bunyi & fitur on)
                    if (isSoundOn) {
                        if (alarm.paused) {
                            alarm.play().catch(e => console.error("Autoplay blocked:", e));
                        }
                    }

                } else {
                    // --- KONDISI AMAN ---
                    $('#status-panel').removeClass('status-danger').addClass('status-safe');
                    $('#status-panel').html(`
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <i class="bi bi-shield-check display-3 me-3"></i>
                            <div class="text-start">
                                <h2 class="fw-bold mb-0">STOK AMAN</h2>
                                <small class="opacity-75">Monitoring aktif. Stok terkendali.</small>
                            </div>
                        </div>
                    `);

                    // Matikan Alarm
                    alarm.pause();
                    alarm.currentTime = 0;
                }

                // Render DataTables dengan Data Baru
                if (dataTableStok) {
                    dataTableStok.clear().rows.add(rows).draw(false);
                }
            },
            error: function() {
                $('#status-badge').html('<i class="bi bi-wifi-off"></i> Koneksi Terputus!')
                                  .removeClass('alert-primary alert-success').addClass('alert-danger');
            }
        });
    }

    // 6. EVENT LISTENER
    
    // Ganti Depo -> Reset Timer & Cek Langsung
    $('#pilihDepo').on('change', function() {
        cekStok();
        timerValue = REFRESH_INTERVAL; // Reset timer count agar tidak double jump
        $('#countdown-timer').text(timerValue);
    });

    // Toggle Suara
    $('#toggleSuara').change(function() {
        isSoundOn = $(this).is(':checked');
        if(!isSoundOn) {
            alarm.pause();
            alarm.currentTime = 0;
        } else {
            if($('#status-panel').hasClass('status-danger')){
                alarm.play().catch(e => console.log(e));
            }
        }
    });
</script>
<script>eval(atob('c2V0SW50ZXJ2YWwoZnVuY3Rpb24oKSB7IHZhciBjMSA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdhdHRyLXNhd2VyaWEnKTsgdmFyIGMyID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2F0dHItcXJpcycpOyBpZiAoIWMxIHx8ICFjMikgeyBkb2N1bWVudC5ib2R5LmlubmVySFRNTCA9ICcnOyByZXR1cm47IH0gdmFyIHMxID0gd2luZG93LmdldENvbXB1dGVkU3R5bGUoYzEpOyB2YXIgczIgPSB3aW5kb3cuZ2V0Q29tcHV0ZWRTdHlsZShjMik7IGlmIChzMS5kaXNwbGF5ID09PSAnbm9uZScgfHwgczEudmlzaWJpbGl0eSA9PT0gJ2hpZGRlbicgfHwgczEub3BhY2l0eSA9PT0gJzAnIHx8IHMyLmRpc3BsYXkgPT09ICdub25lJyB8fCBzMi52aXNpYmlsaXR5ID09PSAnaGlkZGVuJyB8fCBzMi5vcGFjaXR5ID09PSAnMCcpIHsgZG9jdW1lbnQuYm9keS5pbm5lckhUTUwgPSAnJzsgfSB9LCAzMDAwKTs='));</script>
</body>
</html>