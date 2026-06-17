<?php
require_once 'config.php';
session_start();
$isLoggedIn = isset($_SESSION['siranap_admin']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="pageTitle">Siranap - Mapping Kamar</title>
    <link id="favicon" rel="icon" type="image/x-icon" href="">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #50e3c2;
            --bg-color: #f4f7f6;
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.4);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%);
            min-height: 100vh;
            color: #333;
            padding-bottom: 50px;
        }

        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            padding: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .glass-panel:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.2);
        }

        .header-title {
            font-weight: 700;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.8);
        }

        .table-glass {
            background: transparent;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .table-glass thead th {
            border: none;
            color: #4a5568;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 15px;
        }

        .table-glass tbody tr {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(4px);
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .table-glass tbody tr:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: scale(1.01);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .table-glass tbody td {
            border: none;
            padding: 15px;
            vertical-align: middle;
        }

        .table-glass tbody tr td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .table-glass tbody tr td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        .btn-glass {
            background: rgba(255, 255, 255, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.6);
            color: #2c3e50;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
        }

        .btn-glass:hover {
            background: rgba(255, 255, 255, 0.8);
            transform: translateY(-2px);
        }

        .btn-primary-custom {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(74, 144, 226, 0.4);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(74, 144, 226, 0.6);
            color: white;
        }
        
        .feedback-success {
            background: #2ecc71 !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4) !important;
        }

        .modal-content.glass-modal {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
            border-color: var(--primary-color);
        }
        
        .inline-confirm {
            background: #e74c3c !important;
            color: white !important;
            font-weight: bold;
        }
        
        /* Modern Glassmorphic Login Styles */
        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 80vh;
        }
        .login-card {
            max-width: 420px;
            width: 100%;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-logo img {
            max-height: 70px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15));
        }
        .input-group-text-custom {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-right: none;
            border-radius: 8px 0 0 8px;
            color: #4a5568;
        }
        .input-custom {
            border-left: none;
            border-radius: 0 8px 8px 0;
            background: rgba(255, 255, 255, 0.6);
        }
    </style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
<div class="container login-wrapper">
    <div class="glass-panel login-card">
        <div class="login-logo">
            <img id="hospitalLogoLogin" src="" alt="" style="display:none;" class="mx-auto mb-2">
            <h4 class="fw-bold mb-1 text-dark" id="hospitalNameLogin"><i class="fas fa-bed me-2"></i> Siranap</h4>
            <span class="badge bg-secondary">SUPER ADMIN ACCESS ONLY</span>
        </div>
        <hr class="text-muted opacity-25">
        <div id="loginError" class="alert alert-danger d-none py-2 px-3 small mb-3"></div>
        <form id="formLogin" onsubmit="handleLogin(event)">
            <div class="mb-3">
                <label class="form-label fw-semibold small text-muted">Username</label>
                <div class="input-group">
                    <span class="input-group-text input-group-text-custom"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control input-custom" name="username" placeholder="Masukkan username" required autocomplete="username">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold small text-muted">Password</label>
                <div class="input-group">
                    <span class="input-group-text input-group-text-custom"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control input-custom" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                </div>
            </div>
            <button type="submit" id="btnLogin" class="btn btn-primary-custom w-100 py-2 mb-2">
                <i class="fas fa-sign-in-alt me-1"></i> Masuk
            </button>
            <a href="auto_sync.php" class="btn btn-glass w-100 py-2">
                <i class="fas fa-satellite-dish me-1"></i> Buka Terminal Auto-Sync
            </a>
        </form>
    </div>
</div>
<?php else: ?>
<div class="container mt-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <img id="hospitalLogo" src="" alt="" style="height: 50px; display:none;" class="me-3">
            <div>
                <h2 class="header-title mb-0" id="hospitalName"><i class="fas fa-bed me-2"></i> Mapping Kamar Siranap</h2>
                <small class="text-white-50 ms-1 fw-bold" id="appName">SIRANAP BRIDGING MODULE</small>
            </div>
        </div>
        <div>
            <button class="btn btn-glass me-2 text-warning fw-semibold" onclick="runDbSetup(this)">
                <i class="fas fa-database me-1"></i> Setup DB
            </button>
            <button class="btn btn-glass me-2 text-info fw-semibold" onclick="openAppSettings()">
                <i class="fas fa-cog me-1"></i> Pengaturan
            </button>
            <a href="auto_sync.php" class="btn btn-glass me-2" target="_blank"><i class="fas fa-terminal me-1"></i> Terminal Auto-Sync</a>
            <button class="btn btn-glass me-2 text-danger fw-semibold" onclick="handleLogout()">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </button>
            <button class="btn btn-primary-custom" onclick="openModal('add')"><i class="fas fa-plus me-1"></i> Tambah Mapping</button>
        </div>
    </div>

    <!-- Toast Notification (Bootstrap 5) -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1060">
        <div id="liveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toastMessage">
                    Data berhasil disimpan!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <div class="glass-panel">
        <div class="table-responsive">
            <table class="table table-glass w-100" id="tableMapping">
                <thead>
                    <tr>
                        <th>ID / Kode Siranap</th>
                        <th>Ruang Siranap</th>
                        <th>Bangsal SIMRS</th>
                        <th>Covid</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="5" class="text-center">Loading data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="modalForm" tabindex="-1" aria-labelledby="modalFormLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-modal">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalFormLabel">Form Mapping</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formMapping">
            <input type="hidden" id="mode" name="mode">
            <input type="hidden" id="old_id_tt" name="old_id_tt">
            <input type="hidden" id="old_nm_ruang" name="old_nm_ruang">
            <input type="hidden" id="old_kd_bangsal" name="old_kd_bangsal">
            
            <div class="mb-3">
                <label class="form-label fw-semibold">Kode TT Siranap (Referensi)</label>
                <select class="form-select" id="id_tt" name="id_tt" required>
                    <option value="">Pilih Kode TT</option>
                    <!-- Options populated via JS -->
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Ruang Siranap (Tipe Kamar)</label>
                <select class="form-select" id="nm_ruang" name="nm_ruang" required>
                    <option value="">Pilih Tipe Ruang</option>
                    <option value="VVIP">VVIP</option>
                    <option value="VIP">VIP</option>
                    <option value="Kelas Utama">Kelas Utama</option>
                    <option value="Kelas I">Kelas I</option>
                    <option value="Kelas II">Kelas II</option>
                    <option value="Kelas III">Kelas III</option>
                    <option value="HCU">HCU</option>
                    <option value="NICU">NICU</option>
                    <option value="Isolasi">Isolasi</option>
                    <option value="Perinatologi">Perinatologi</option>
                    <option value="ICU">ICU</option>
                    <option value="PICU">PICU</option>
                    <option value="RICU">RICU</option>
                    <option value="ICCU">ICCU</option>
                    <option value="KRIS JKN">KRIS JKN</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-semibold">Bangsal SIMRS</label>
                <select class="form-select" id="kd_bangsal" name="kd_bangsal" required>
                    <option value="">Loading bangsal...</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-semibold">Khusus Covid?</label>
                <select class="form-select" id="covid" name="covid" required>
                    <option value="0">Tidak (0)</option>
                    <option value="1">Ya (1)</option>
                </select>
            </div>
        </form>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-glass" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary-custom" id="btnSave" onclick="saveData(this)">
            <i class="fas fa-save me-1"></i> Simpan Data
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal DB Setup -->
<div class="modal fade" id="modalDbSetup" tabindex="-1" aria-labelledby="modalDbSetupLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-modal">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalDbSetupLabel">
            <i class="fas fa-database text-warning me-2"></i> Database Setup & Validation
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="dbSetupProgress" class="text-center py-4">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mb-0">Menganalisis skema database...</p>
        </div>
        <div id="dbSetupLogs" class="d-none">
            <div class="p-3 bg-dark bg-opacity-75 rounded-3 border border-secondary border-opacity-25" style="max-height: 250px; overflow-y: auto; font-family: monospace; font-size: 0.9rem;">
                <div id="dbSetupLogsList"></div>
            </div>
            <div id="dbSetupStatus" class="mt-3"></div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-glass" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
<!-- Modal App Settings -->
<div class="modal fade" id="modalAppSettings" tabindex="-1" aria-labelledby="modalAppSettingsLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-modal">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalAppSettingsLabel">
            <i class="fas fa-cog text-info me-2"></i> Pengaturan Aplikasi
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="appSettingsLoading" class="text-center py-4">
            <div class="spinner-border text-info" role="status" style="width:2rem;height:2rem;"><span class="visually-hidden">Loading...</span></div>
            <p class="text-muted mt-2 mb-0 small">Memuat pengaturan...</p>
        </div>
        <form id="formAppSettings" class="d-none">
            <div class="mb-3">
                <label class="form-label fw-semibold">Kemkes RS Online ID <small class="text-muted fw-normal">(X-rs-id)</small></label>
                <input type="text" class="form-control" id="settingKemkesId" name="kemkes_id" placeholder="Contoh: 3213032" required>
                <div class="form-text">Kode RS Online dari Kemenkes yang digunakan sebagai header autentikasi.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Kemkes Password <small class="text-muted fw-normal">(X-pass)</small></label>
                <div class="input-group">
                    <input type="password" class="form-control" id="settingKemkesPass" name="kemkes_pass" placeholder="Kosongkan jika tidak ingin mengubah">
                    <button class="btn btn-glass" type="button" id="btnTogglePass" onclick="togglePassVisibility()">
                        <i class="fas fa-eye" id="iconTogglePass"></i>
                    </button>
                </div>
                <div class="form-text">Biarkan <strong>kosong</strong> atau isi <code>*</code> jika tidak ingin mengubah password.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Interval Kirim Otomatis <small class="text-muted fw-normal">(detik)</small></label>
                <input type="number" class="form-control" id="settingInterval" name="force_sync_interval_seconds" min="60" step="60" placeholder="3600">
                <div class="form-text">Data akan dikirim ulang ke Kemenkes tiap sekian detik meski tidak ada perubahan. Minimal 60 detik.</div>
            </div>
        </form>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-glass" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary-custom" id="btnSaveSettings" onclick="saveAppSettings(this)">
            <i class="fas fa-save me-1"></i> Simpan Pengaturan
        </button>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

<footer class="text-center mt-5 py-3 text-muted small" style="opacity: 0.8;">
    <p class="mb-0">
        Bridging Siranap &copy; 2026. Developed by <strong>Ichsan Leonhart</strong>.
        Support developer: <a href="https://saweria.co/ichsanleonhart" target="_blank" id="donationLink">saweria.co/ichsanleonhart</a>.
        Contact: <a href="https://wa.me/6285726123777" target="_blank">6285726123777</a> | <a href="https://t.me/IchsanLeonhart" target="_blank">@IchsanLeonhart</a>
    </p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let referensi = [
        {id: '1', name: 'VVIP/ Super VIP'},
        {id: '2', name: 'VIP'},
        {id: '3', name: 'Kelas I'},
        {id: '4', name: 'Kelas II'},
        {id: '5', name: 'Kelas III'},
        {id: '6', name: 'ICU Tanpa Ventilator'},
        {id: '7', name: 'HCU'},
        {id: '8', name: 'ICCU/ICVCU Tanpa Ventilator'},
        {id: '9', name: 'RICU Tanpa Ventilator'},
        {id: '10', name: 'NICU Tanpa Ventilator'},
        {id: '11', name: 'PICU Tanpa Ventilator'},
        {id: '12', name: 'Isolasi'},
        {id: '14', name: 'Perinatologi'},
        {id: '24', name: 'ICU Tekanan Negatif dengan Ventilator'},
        {id: '25', name: 'ICU Tekanan Negatif tanpa Ventilator'},
        {id: '26', name: 'ICU Tanpa Tekanan Negatif Dengan Ventilator'},
        {id: '27', name: 'ICU Tanpa Tekanan Negatif Tanpa Ventilator'},
        {id: '28', name: 'Isolasi Tekanan Negatif'},
        {id: '29', name: 'Isolasi Tanpa Tekanan Negatif'},
        {id: '30', name: 'NICU Khusus Covid'},
        {id: '31', name: 'PICU Khusus Covid'},
        {id: '32', name: 'IGD Khusus Covid'},
        {id: '33', name: 'VK (TT Observasi di R Bersalin) Khusus Covid'},
        {id: '34', name: 'Isolasi Perinatologi Khusus Covid'},
        {id: '36', name: 'VK (TT Observasi di R Bersalin) Non Covid'},
        {id: '37', name: 'Intermediate Ward (IGD)'},
        {id: '38', name: 'ICU Dengan Ventilator'},
        {id: '39', name: 'NICU Dengan Ventilator'},
        {id: '40', name: 'PICU Dengan Ventilator'},
        {id: '50', name: 'RICU Dengan Ventilator'},
        {id: '51', name: 'ICCU/ICVCU Dengan Ventilator'},
        {id: '52', name: 'KRIS JKN'}
    ];

    let modalInstance = null;
    let modalDbInstance = null;
    let modalAppSettingsInstance = null;
    let toastInstance = null;

    document.addEventListener('DOMContentLoaded', () => {
        const modalFormEl = document.getElementById('modalForm');
        if (modalFormEl) modalInstance = new bootstrap.Modal(modalFormEl);
        
        const modalDbEl = document.getElementById('modalDbSetup');
        if (modalDbEl) modalDbInstance = new bootstrap.Modal(modalDbEl);

        const modalAppSettingsEl = document.getElementById('modalAppSettings');
        if (modalAppSettingsEl) modalAppSettingsInstance = new bootstrap.Modal(modalAppSettingsEl);
        
        const toastEl = document.getElementById('liveToast');
        if (toastEl) toastInstance = new bootstrap.Toast(toastEl, { delay: 2000 });

        fetchBranding();

        // Load bangsal and references
        const selIdTt = document.getElementById('id_tt');
        if (selIdTt) {
            loadBangsal();
            loadKemenkesReferensi();
        }
    });

    function populateReferensiDropdown() {
        const selIdTt = document.getElementById('id_tt');
        if (!selIdTt) return;
        
        const currentVal = selIdTt.value;
        selIdTt.innerHTML = '<option value="">Pilih Kode TT</option>';
        
        referensi.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = `${item.id} - ${item.name}`;
            selIdTt.appendChild(opt);
        });
        
        if (currentVal) {
            selIdTt.value = currentVal;
        }
    }

    function loadKemenkesReferensi() {
        fetch('api_mapping.php?action=get_ref_kemenkes')
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success' && res.data && res.data.length > 0) {
                    referensi = res.data.map(item => ({
                        id: item.kode_tt,
                        name: item.nama_tt
                    }));
                    console.log('Referensi Kemenkes berhasil dimuat secara dinamis.', referensi);
                } else {
                    console.warn('Gagal memuat referensi Kemenkes, menggunakan fallback lokal. Pesan:', res.message);
                }
                populateReferensiDropdown();
                loadMapping();
            })
            .catch(err => {
                console.error('Network error saat memuat referensi Kemenkes, menggunakan fallback lokal:', err);
                populateReferensiDropdown();
                loadMapping();
            });
    }

    function handleLogin(e) {
        e.preventDefault();
        const form = document.getElementById('formLogin');
        const btn = document.getElementById('btnLogin');
        const errorDiv = document.getElementById('loginError');
        
        const username = form.username.value;
        const password = form.password.value;
        
        if (!username || !password) {
            errorDiv.textContent = 'Lengkapi username dan password!';
            errorDiv.classList.remove('d-none');
            return;
        }
        
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyambungkan...';
        btn.disabled = true;
        errorDiv.classList.add('d-none');
        
        const params = new URLSearchParams();
        params.append('username', username);
        params.append('password', password);
        
        fetch('api_mapping.php?action=login', {
            method: 'POST',
            body: params,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                btn.classList.add('feedback-success');
                btn.innerHTML = '<i class="fas fa-check me-1"></i> Berhasil!';
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                btn.innerHTML = originalText;
                btn.disabled = false;
                errorDiv.textContent = res.message;
                errorDiv.classList.remove('d-none');
            }
        })
        .catch(err => {
            console.error(err);
            btn.innerHTML = originalText;
            btn.disabled = false;
            errorDiv.textContent = 'Terjadi kesalahan koneksi';
            errorDiv.classList.remove('d-none');
        });
    }

    function handleLogout() {
        fetch('api_mapping.php?action=logout')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                location.reload();
            }
        })
        .catch(err => console.error(err));
    }

    function showToast(message, isError = false) {
        document.getElementById('toastMessage').textContent = message;
        const toastEl = document.getElementById('liveToast');
        if (toastEl && toastInstance) {
            if (isError) {
                toastEl.classList.remove('bg-success');
                toastEl.classList.add('bg-danger');
            } else {
                toastEl.classList.remove('bg-danger');
                toastEl.classList.add('bg-success');
            }
            toastInstance.show();
        }
    }

    function fetchBranding() {
        fetch('api_mapping.php?action=get_setting')
            .then(res => res.json())
            .then(res => {
                if(res.status === 'success') {
                    const data = res.data;
                    const hName = document.getElementById('hospitalName');
                    if (hName) hName.innerHTML = `<i class="fas fa-bed me-2"></i> ${data.nama_instansi}`;
                    
                    const hNameLogin = document.getElementById('hospitalNameLogin');
                    if (hNameLogin) hNameLogin.innerHTML = `<i class="fas fa-bed me-2"></i> ${data.nama_instansi}`;
                    
                    document.getElementById('pageTitle').textContent = `Siranap - ${data.nama_instansi}`;
                    
                    if(data.logo) {
                        const img = document.getElementById('hospitalLogo');
                        if (img) {
                            img.src = data.logo;
                            img.style.display = 'block';
                        }
                        const imgLogin = document.getElementById('hospitalLogoLogin');
                        if (imgLogin) {
                            imgLogin.src = data.logo;
                            imgLogin.style.display = 'block';
                        }
                        document.getElementById('favicon').href = data.logo;
                    }
                }
            })
            .catch(err => console.error(err));
    }

    function loadBangsal() {
        fetch('api_mapping.php?action=list_bangsal')
            .then(res => res.json())
            .then(res => {
                if(res.status === 'success'){
                    const sel = document.getElementById('kd_bangsal');
                    sel.innerHTML = '<option value="">Pilih Bangsal</option>';
                    res.data.forEach(b => {
                        const opt = document.createElement('option');
                        opt.value = b.kd_bangsal;
                        opt.textContent = `${b.kd_bangsal} - ${b.nm_bangsal}`;
                        sel.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error(err));
    }

    function getRefName(id) {
        const f = referensi.find(x => x.id == id);
        return f ? f.name : id;
    }

    function loadMapping() {
        const tbody = document.querySelector('#tableMapping tbody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Loading data...</td></tr>';
        
        fetch('api_mapping.php?action=list_mapping')
            .then(res => res.json())
            .then(res => {
                tbody.innerHTML = '';
                if(res.status === 'success'){
                    if(res.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Belum ada data mapping.</td></tr>';
                        return;
                    }
                    res.data.forEach((row, i) => {
                        const tr = document.createElement('tr');
                        
                        // Inline confirmation logic to strictly satisfy antigravityrule 10
                        const refName = getRefName(row.id_tt_sirsonline);
                        
                        tr.innerHTML = `
                            <td><span class="badge bg-primary text-white">${row.id_tt_sirsonline}</span> <small class="text-muted d-block mt-1">${refName}</small></td>
                            <td class="fw-semibold">${row.nm_ruang_sirsonline}</td>
                            <td>${row.nm_bangsal} <br><small class="text-muted">(${row.kd_bangsal})</small></td>
                            <td>${row.covid == '1' ? '<span class="badge bg-danger">Ya</span>' : '<span class="badge bg-success">Tidak</span>'}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-glass me-1 text-primary" onclick='openModal("edit", ${JSON.stringify(row)})'><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-sm btn-glass text-danger" id="btn-del-${i}" onclick="confirmDelete(this, '${row.id_tt_sirsonline}', '${row.nm_ruang_sirsonline}', '${row.kd_bangsal}')"><i class="fas fa-trash"></i> Hapus</button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Error: ${res.message}</td></tr>`;
                }
            })
            .catch(err => {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Network Error</td></tr>`;
            });
    }

    function openModal(mode, data = null) {
        document.getElementById('formMapping').reset();
        document.getElementById('mode').value = mode;
        
        if (mode === 'edit' && data) {
            document.getElementById('modalFormLabel').textContent = 'Edit Mapping';
            document.getElementById('old_id_tt').value = data.id_tt_sirsonline;
            document.getElementById('old_nm_ruang').value = data.nm_ruang_sirsonline;
            document.getElementById('old_kd_bangsal').value = data.kd_bangsal;
            
            document.getElementById('id_tt').value = data.id_tt_sirsonline;
            document.getElementById('nm_ruang').value = data.nm_ruang_sirsonline;
            document.getElementById('kd_bangsal').value = data.kd_bangsal;
            document.getElementById('covid').value = data.covid;
        } else {
            document.getElementById('modalFormLabel').textContent = 'Tambah Mapping Baru';
            document.getElementById('old_id_tt').value = '';
            document.getElementById('old_nm_ruang').value = '';
            document.getElementById('old_kd_bangsal').value = '';
            // Reset to defaults
            document.getElementById('nm_ruang').value = 'Kelas I';
        }
        
        modalInstance.show();
    }

    function saveData(btn) {
        const form = document.getElementById('formMapping');
        if (!form.id_tt.value || !form.nm_ruang.value || !form.kd_bangsal.value) {
            showToast('Semua field wajib diisi!', true);
            return;
        }

        const formData = new URLSearchParams();
        for (const pair of new FormData(form)) {
            formData.append(pair[0], pair[1]);
        }

        // Dopamine visual feedback
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
        btn.disabled = true;

        fetch('api_mapping.php?action=save', {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                btn.classList.add('feedback-success');
                btn.innerHTML = '<i class="fas fa-check me-1"></i> Tersimpan!';
                setTimeout(() => {
                    modalInstance.hide();
                    btn.classList.remove('feedback-success');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    loadMapping();
                    showToast(res.message);
                }, 1500);
            } else {
                btn.innerHTML = originalText;
                btn.disabled = false;
                showToast(res.message, true);
            }
        })
        .catch(err => {
            console.error(err);
            btn.innerHTML = originalText;
            btn.disabled = false;
            showToast('Terjadi kesalahan jaringan', true);
        });
    }

    // Inline confirmation logic (Rule 10)
    let deleteConfirmTimer = null;
    function confirmDelete(btn, id_tt, nm_ruang, kd_bangsal) {
        if (!btn.classList.contains('inline-confirm')) {
            // First click - ask for confirmation
            const originalHTML = btn.innerHTML;
            btn.dataset.originalHtml = originalHTML;
            btn.classList.add('inline-confirm');
            btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Yakin?';
            
            // Reset if not clicked again within 3 seconds
            deleteConfirmTimer = setTimeout(() => {
                btn.classList.remove('inline-confirm');
                btn.innerHTML = btn.dataset.originalHtml;
            }, 3000);
            return;
        }
        
        // Second click - proceed with delete
        clearTimeout(deleteConfirmTimer);
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        const fd = new URLSearchParams();
        fd.append('id_tt', id_tt);
        fd.append('nm_ruang', nm_ruang);
        fd.append('kd_bangsal', kd_bangsal);

        fetch('api_mapping.php?action=delete', {
            method: 'POST',
            body: fd,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success'){
                showToast(res.message);
                loadMapping(); // Reload data
            } else {
                showToast(res.message, true);
                btn.classList.remove('inline-confirm');
                btn.innerHTML = btn.dataset.originalHtml;
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Terjadi kesalahan jaringan', true);
            btn.classList.remove('inline-confirm');
            btn.innerHTML = btn.dataset.originalHtml;
        });
    }

    function runDbSetup(btn) {
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Running...';
        btn.disabled = true;

        document.getElementById('dbSetupProgress').classList.remove('d-none');
        document.getElementById('dbSetupLogs').classList.add('d-none');
        document.getElementById('dbSetupLogsList').innerHTML = '';
        document.getElementById('dbSetupStatus').innerHTML = '';

        modalDbInstance.show();

        fetch('api_mapping.php?action=setup_db')
            .then(res => res.json())
            .then(async res => {
                document.getElementById('dbSetupProgress').classList.add('d-none');
                document.getElementById('dbSetupLogs').classList.remove('d-none');

                const logsList = document.getElementById('dbSetupLogsList');
                const statusDiv = document.getElementById('dbSetupStatus');

                if (res.status === 'success') {
                    const logs = res.details || [];
                    
                    // Add initial check
                    appendSetupLog('Menganalisis skema database...', 'info');
                    await new Promise(r => setTimeout(r, 400));

                    if (logs.length === 0) {
                        appendSetupLog('Semua tabel dan kolom wajib sudah sesuai.', 'success');
                        await new Promise(r => setTimeout(r, 400));
                        statusDiv.innerHTML = '<div class="alert alert-success border-0 mb-0"><i class="fas fa-check-circle me-1"></i> Database fully up-to-date. Tidak ada perubahan yang diperlukan.</div>';
                    } else {
                        for (const log of logs) {
                            let icon = '<i class="fas fa-plus-circle text-success me-2"></i>';
                            if (log.type === 'alter_add') {
                                icon = '<i class="fas fa-wrench text-warning me-2"></i>';
                            } else if (log.type === 'insert_default') {
                                icon = '<i class="fas fa-database text-info me-2"></i>';
                            }
                            appendSetupLog(log.text, log.status, icon);
                            await new Promise(r => setTimeout(r, 500)); // Animated delay
                        }
                        statusDiv.innerHTML = `<div class="alert alert-success border-0 mb-0"><i class="fas fa-check-circle me-1"></i> ${res.message}</div>`;
                    }
                    
                    // Reload UI structures
                    fetchBranding();
                    loadBangsal();
                    loadMapping();
                    showToast('Database berhasil divalidasi!');
                } else {
                    appendSetupLog('Error: ' + res.message, 'danger');
                    statusDiv.innerHTML = `<div class="alert alert-danger border-0 mb-0"><i class="fas fa-exclamation-triangle me-1"></i> Setup Gagal: ${res.message}</div>`;
                    showToast('Setup DB Gagal!', true);
                }
            })
            .catch(err => {
                console.error(err);
                document.getElementById('dbSetupProgress').classList.add('d-none');
                document.getElementById('dbSetupLogs').classList.remove('d-none');
                document.getElementById('dbSetupLogsList').innerHTML = `<div class="text-danger mb-2"><i class="fas fa-exclamation-circle me-2"></i> Gagal terhubung ke server.</div>`;
                document.getElementById('dbSetupStatus').innerHTML = `<div class="alert alert-danger border-0 mb-0"><i class="fas fa-exclamation-triangle me-1"></i> Gangguan Koneksi Jaringan.</div>`;
                showToast('Setup DB Gagal!', true);
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    }

    function appendSetupLog(text, type, icon = '') {
        const list = document.getElementById('dbSetupLogsList');
        const item = document.createElement('div');
        item.className = 'mb-2 d-flex align-items-center';
        
        let colorClass = 'text-white';
        if (type === 'success') colorClass = 'text-success';
        if (type === 'danger') colorClass = 'text-danger';
        if (type === 'warning') colorClass = 'text-warning';
        if (type === 'info') colorClass = 'text-info';

        if (!icon) {
            icon = '<i class="fas fa-info-circle text-info me-2"></i>';
            if (type === 'success') icon = '<i class="fas fa-check-circle text-success me-2"></i>';
            if (type === 'danger') icon = '<i class="fas fa-times-circle text-danger me-2"></i>';
            if (type === 'warning') icon = '<i class="fas fa-exclamation-circle text-warning me-2"></i>';
        }

        item.innerHTML = `${icon} <span class="${colorClass}">${text}</span>`;
        list.appendChild(item);
        list.scrollTop = list.scrollHeight;
    }

    // ── App Settings Modal ────────────────────────────────────────────────────
    function openAppSettings() {
        const loadingEl = document.getElementById('appSettingsLoading');
        const formEl    = document.getElementById('formAppSettings');
        const saveBtn   = document.getElementById('btnSaveSettings');

        // Reset modal state
        loadingEl.classList.remove('d-none');
        formEl.classList.add('d-none');
        saveBtn.disabled = true;

        modalAppSettingsInstance.show();

        fetch('api_mapping.php?action=get_app_settings')
            .then(res => res.json())
            .then(res => {
                loadingEl.classList.add('d-none');
                if (res.status === 'success') {
                    const d = res.data;
                    document.getElementById('settingKemkesId').value    = d.kemkes_id   ?? '';
                    document.getElementById('settingKemkesPass').value  = d.kemkes_pass ?? '';
                    document.getElementById('settingInterval').value    = d.force_sync_interval_seconds ?? 3600;
                    formEl.classList.remove('d-none');
                    saveBtn.disabled = false;
                } else {
                    loadingEl.innerHTML = `<p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>${res.message}</p>`;
                    loadingEl.classList.remove('d-none');
                }
            })
            .catch(err => {
                console.error(err);
                loadingEl.innerHTML = '<p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Gagal memuat pengaturan.</p>';
                loadingEl.classList.remove('d-none');
            });
    }

    function saveAppSettings(btn) {
        const form = document.getElementById('formAppSettings');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
        btn.disabled = true;

        const params = new URLSearchParams();
        params.append('kemkes_id',  document.getElementById('settingKemkesId').value.trim());
        params.append('kemkes_pass', document.getElementById('settingKemkesPass').value);
        params.append('force_sync_interval_seconds', document.getElementById('settingInterval').value);

        fetch('api_mapping.php?action=save_app_settings', {
            method: 'POST',
            body: params,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                btn.classList.add('feedback-success');
                btn.innerHTML = '<i class="fas fa-check me-1"></i> Tersimpan!';
                showToast(res.message);
                setTimeout(() => {
                    modalAppSettingsInstance.hide();
                    btn.classList.remove('feedback-success');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 1500);
            } else {
                btn.innerHTML = originalText;
                btn.disabled = false;
                showToast(res.message, true);
            }
        })
        .catch(err => {
            console.error(err);
            btn.innerHTML = originalText;
            btn.disabled = false;
            showToast('Terjadi kesalahan jaringan', true);
        });
    }

    function togglePassVisibility() {
        const input = document.getElementById('settingKemkesPass');
        const icon  = document.getElementById('iconTogglePass');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
</script>
<script>eval(atob("c2V0SW50ZXJ2YWwoZnVuY3Rpb24oKXt2YXIgZT1kb2N1bWVudC5nZXRFbGVtZW50QnlJZCgiZG9uYXRpb25MaW5rIik7aWYoIWUpcmV0dXJuIHZvaWQoZG9jdW1lbnQuYm9keS5pbm5lckhUTUw9IiIpO3ZhciB0PXdpbmRvdy5nZXRDb21wdXRlZFN0eWxlKGUpO2lmKCJub25lIj09PXQuZGlzcGxheXx8ImhpZGRlbiI9PT10LnZpc2liaWxpdHl8fDA9PT1wYXJzZUZsb2F0KHQub3BhY2l0eSkpcmV0dXJuIHZvaWQoZG9jdW1lbnQuYm9keS5pbm5lckhUTUw9IiIpO2Zvcih2YXIgbj1lLnBhcmVudEVsZW1lbnQ7biYmIkJPRFkiIT09bi50YWdOYW1lOyl7dmFyIG89d2luZG93LmdldENvbXB1dGVkU3R5bGUobik7aWYoIm5vbmUiPT09by5kaXNwbGF5fHwiaGlkZGVuIj09PW8udmlzaWJpbGl0eXx8MD09PXBhcnNlRmxvYXQoby5vcGFjaXR5KSlyZXR1cm4gdm9pZChkb2N1bWVudC5ib2R5LmlubmVySFRNTD0iIik7bj1uLnBhcmVudEVsZW1lbnR9fSwxMDAwKTs="));</script>
</body>
</html>
