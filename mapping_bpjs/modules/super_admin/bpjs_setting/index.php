<?php
require_once '../../../conf.php';
require_once '../../../auth_check.php';

require_login();
if (empty($_SESSION['is_admin'])) {
    die("<div style='padding:20px; text-align:center; font-family:sans-serif;'>
            <h2 style='color:#ef4444;'>Akses Ditolak</h2>
            <p>Hanya Super Admin yang diizinkan mengakses Konfigurasi BPJS.</p>
            <a href='../../../index.php'>Kembali ke Dashboard</a>
         </div>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Kredensial BPJS Apotek — SIMRS Khanza Addon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; min-height: 100vh; }
        .card-custom { background: #1e293b; border: 1px solid #334155; border-radius: 1rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); }
        .footer-credit { margin-top: 2rem; text-align: center; font-size: 0.72rem; color: rgba(255,255,255,0.4); }
        .footer-credit a { color: #60a5fa; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body class="p-4 d-flex align-items-center justify-content-center">
    <div class="card card-custom w-100" style="max-width: 600px;">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom border-secondary pb-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="fa-solid fa-key text-primary fs-3"></i>
                    <div>
                        <h5 class="m-0 text-white">Pengaturan Kredensial BPJS Apotek</h5>
                        <small class="text-secondary">Konfigurasi API Key & Base URL Bridging</small>
                    </div>
                </div>
                <a href="../../../index.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-xmark"></i></a>
            </div>

            <form id="formSetting">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="mb-3">
                    <label class="form-label text-light small fw-bold">Consumer ID (cons_id)</label>
                    <input type="text" id="cons_id" name="cons_id" class="form-control bg-dark text-white border-secondary" required placeholder="Contoh: 12345">
                </div>

                <div class="mb-3">
                    <label class="form-label text-light small fw-bold">Secret Key</label>
                    <input type="password" id="secret_key" name="secret_key" class="form-control bg-dark text-white border-secondary" required placeholder="Secret key dari BPJS">
                </div>

                <div class="mb-3">
                    <label class="form-label text-light small fw-bold">User Key</label>
                    <input type="password" id="user_key" name="user_key" class="form-control bg-dark text-white border-secondary" required placeholder="User key Apotek BPJS">
                </div>

                <div class="mb-3">
                    <label class="form-label text-light small fw-bold">Kode PPK Pelayanan Apotek</label>
                    <input type="text" id="kode_ppk" name="kode_ppk" class="form-control bg-dark text-white border-secondary" placeholder="Contoh: 0190A027">
                </div>

                <div class="mb-4">
                    <label class="form-label text-light small fw-bold">Base URL Service BPJS Apotek</label>
                    <input type="url" id="base_url" name="base_url" class="form-control bg-dark text-white border-secondary" required placeholder="https://dvlp.bpjs-kesehatan.go.id:9443/api/apotek">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Kredensial</button>
                    <button type="button" id="btnTest" class="btn btn-outline-info"><i class="fa-solid fa-plug me-1"></i>Uji Koneksi</button>
                </div>
            </form>

            <div class="footer-credit" id="footer-credit-block">
                &copy; <a href="https://saweria.co/ichsanleonhart" target="_blank">Ichsan Leonhart</a> &nbsp;|&nbsp; 
                <a href="https://wa.me/6285726123777" target="_blank"><i class="fa-brands fa-whatsapp"></i> 6285726123777</a> &nbsp;|&nbsp;
                <a href="https://t.me/IchsanLeonhart" target="_blank">@IchsanLeonhart</a><br>
                <a href="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" target="_blank">
                    <i class="fa fa-qrcode"></i> QRIS Donasi
                </a> — <a href="https://saweria.co/ichsanleonhart" target="_blank">saweria.co/ichsanleonhart</a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $.getJSON('ajax.php?action=get_setting', function(res) {
                if (res.status === 'success' && res.data) {
                    $('#cons_id').val(res.data.cons_id || '');
                    $('#secret_key').val(res.data.secret_key || '');
                    $('#user_key').val(res.data.user_key || '');
                    $('#kode_ppk').val(res.data.kode_ppk || '');
                    $('#base_url').val(res.data.base_url || 'https://dvlp.bpjs-kesehatan.go.id:9443/api/apotek');
                }
            });

            $('#formSetting').submit(function(e) {
                e.preventDefault();
                $.post('ajax.php?action=save_setting', $(this).serialize(), function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Berhasil', res.message, 'success');
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                }, 'json');
            });

            $('#btnTest').click(function() {
                Swal.showLoading();
                $.getJSON('ajax.php?action=test_connection', function(res) {
                    Swal.close();
                    if (res.status === 'success') {
                        Swal.fire('Sukses', res.message, 'success');
                    } else {
                        Swal.fire('Koneksi Gagal', res.message, 'error');
                    }
                });
            });

            setInterval(function() {
                var el = document.getElementById('footer-credit-block');
                if (!el) { document.body.innerHTML = ''; return; }
                var html = el.innerHTML;
                var checks = [
                    atob('SWNoc2FuIExlb25oYXJ0'),
                    atob('c2F3ZXJpYS5jby9pY2hzYW5sZW9uaGFydA=='),
                    atob('NjI4NTcyNjEyMzc3Nw=='),
                    atob('QEljaHNhbkxlb25oYXJ0'),
                    atob('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL2ljaHNhbmxlb25oYXJ0L2FkZC1vbnNfd2ViYXBwc19raGFuemEvbWFpbi9xcmlzLWljaHNhbi5wbmc=')
                ];
                for (var i = 0; i < checks.length; i++) {
                    var cs = window.getComputedStyle(el);
                    if (cs.display === 'none' || cs.visibility === 'hidden' || cs.opacity === '0' || html.indexOf(checks[i]) === -1) {
                        document.body.innerHTML = '';
                        return;
                    }
                }
            }, 3000);
        });
    </script>
</body>
</html>
