<?php
/**
 * modules/referensi/index.php — Manajemen Referensi Data Satu Sehat
 * CRUD untuk: Bentuk Sediaan, Rute Pemberian, Satuan Numerator, Satuan Denominator
 * Akses: Super Admin atau user dengan hak akses obat/vaksin
 */
require_once '../../conf.php';
require_once '../../auth_check.php';

require_login();
$is_admin = !empty($_SESSION['is_admin']);
if (!$is_admin) {
    $ha = $_SESSION['hak_akses'] ?? [];
    if (($ha['satu_sehat_mapping_obat'] ?? '') !== 'true' &&
        ($ha['satu_sehat_mapping_vaksin'] ?? '') !== 'true') {
        $_SESSION['flash_error'] = 'Anda tidak memiliki hak akses ke modul ini.';
        header('Location: ../../index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Referensi Data — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="../../logo.php">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }
        .page-header { background: linear-gradient(135deg, #0891b2, #06b6d4); color: white; border-radius: 16px; padding: 1.5rem 2rem; margin-bottom: 1.5rem; }
        .page-header h4 { font-weight: 700; margin: 0; }
        .page-header p { margin: 0.25rem 0 0; opacity: 0.8; font-size: .875rem; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .nav-tabs .nav-link { font-weight: 500; }
        .nav-tabs .nav-link.active { font-weight: 700; }
        .footer-credit { text-align: center; padding: 1.5rem; font-size: .72rem; color: #94a3b8; cursor: pointer; transition: all 0.2s; }
        .footer-credit:hover { color: #6366f1; background: rgba(99, 102, 241, 0.05); }
        .footer-credit a { color: #6d28d9; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white border-bottom px-3 py-2 mb-4">
    <a class="text-decoration-none" href="../../index.php" style="color:#0891b2; font-weight:500; font-size:.875rem;">
        <i class="fa fa-arrow-left me-2"></i>Dashboard
    </a>
    <span class="navbar-brand mb-0" style="font-size:1rem; color:#0891b2">
        <i class="fa-solid fa-database me-2"></i> Referensi Data — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?>
    </span>
    <div>
        <span class="text-muted small me-3"><i class="fa fa-user me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
        <a href="../../logout.php" class="text-danger small text-decoration-none"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="page-header">
        <h4><i class="fa-solid fa-database me-2"></i> Manajemen Referensi Data Satu Sehat</h4>
        <p>CRUD untuk Bentuk Sediaan, Rute Pemberian, Satuan Numerator (UCUM), dan Satuan Denominator (HL7 DrugForm).</p>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3" id="refTabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#" data-tbl="form" data-label="Bentuk Sediaan" data-system="Sistem Satu Sehat Kemenkes">
                        <i class="fa fa-capsules me-1"></i> Bentuk Sediaan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-tbl="route" data-label="Rute Pemberian" data-system="http://www.whocc.no/atc">
                        <i class="fa fa-route me-1"></i> Rute Pemberian
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-tbl="numerator" data-label="Satuan Numerator" data-system="http://unitsofmeasure.org (UCUM)">
                        <i class="fa fa-ruler me-1"></i> Satuan Numerator
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-tbl="denominator" data-label="Satuan Denominator" data-system="HL7 v3-orderableDrugForm">
                        <i class="fa fa-pills me-1"></i> Satuan Denominator
                    </a>
                </li>
            </ul>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <small class="text-muted">System: <span id="systemLabel" class="fw-semibold text-primary">Sistem Satu Sehat Kemenkes</span></small>
                </div>
                <button class="btn btn-primary btn-sm" id="btnTambah">
                    <i class="fa fa-plus me-1"></i> Tambah Data Baru
                </button>
            </div>

            <div class="table-responsive">
                <table id="tabelRef" class="table table-striped table-hover table-bordered w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="30%">Kode</th>
                            <th width="55%">Display / Nama</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="modalForm" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle"><i class="fa fa-edit me-2"></i>Form Data</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="f_tbl">
                <input type="hidden" id="f_old_code">
                <div class="mb-3">
                    <label class="form-label fw-bold">Kode <span class="text-danger">*</span></label>
                    <input type="text" class="form-control font-monospace" id="f_code" placeholder="Contoh: TAB, mg, BS001...">
                    <div class="form-text">Kode ini dipakai sebagai identifier unik (case-sensitive).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Display / Nama</label>
                    <input type="text" class="form-control" id="f_display" placeholder="Nama lengkap / deskripsi satuan...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSimpan">
                    <i class="fa fa-save me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Footer copyright (Anti-Tampering — JANGAN DIHAPUS) -->
<div class="footer-credit" id="footer-credit-block" onclick="new bootstrap.Modal(document.getElementById('modalSaweria')).show();">
    &copy; <a href="https://saweria.co/ichsanleonhart" target="_blank" onclick="event.stopPropagation();">Ichsan Leonhart</a> &nbsp;·&nbsp;
    <a href="https://wa.me/6285726123777" target="_blank" onclick="event.stopPropagation();">6285726123777</a> &nbsp;·&nbsp;
    <a href="https://t.me/IchsanLeonhart" target="_blank" onclick="event.stopPropagation();">@IchsanLeonhart</a> &nbsp;·&nbsp;
    <a href="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" target="_blank" onclick="event.stopPropagation();">QRIS Donasi</a>
    — <a href="https://saweria.co/ichsanleonhart" target="_blank" onclick="event.stopPropagation();">saweria.co/ichsanleonhart</a>
</div>

<!-- Modal Saweria -->
<div class="modal fade" id="modalSaweria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center pt-0 pb-4 px-4">
                <div class="mb-3">
                    <img src="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" class="img-fluid rounded-3 shadow-sm" style="max-width:280px;" alt="QRIS Donasi">
                </div>
                <h5 class="fw-bold text-primary mb-3">Apresiasi &amp; Dukungan Donasi</h5>
                <p class="text-muted small px-2 mb-4" style="line-height:1.6;">
                    Terima kasih telah menggunakan aplikasi pemetaan Satu Sehat ini.<br><br>
                    Jika aplikasi ini membantu pekerjaan Anda, mohon bantuannya untuk memberikan apresiasi/dukungan agar pengembangan terus berlanjut. 🙏
                </p>
                <div class="d-grid gap-2">
                    <a href="https://saweria.co/ichsanleonhart" target="_blank" class="btn btn-primary py-2 fw-bold" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border:none;">
                        <i class="fa-solid fa-heart me-2"></i> Dukung via Saweria.co
                    </a>
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
const CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>';
let currentTbl  = 'form';
let dtTable     = null;

$(function() {
    // Init DataTable
    dtTable = $('#tabelRef').DataTable({
        processing   : true,
        serverSide   : true,
        ajax: {
            url : 'ajax.php?action=load',
            data: function(d) { d.tbl = currentTbl; }
        },
        columns: [
            { data: 'code' },
            { data: 'display' },
            {
                data: null, orderable: false, className: 'text-center',
                render: function(data) {
                    return `<button class='btn btn-sm btn-outline-primary btn-edit me-1'
                                data-code='${escHtml(data.code)}' data-display='${escHtml(data.display)}'>
                                <i class='fa fa-edit'></i>
                            </button>
                            <button class='btn btn-sm btn-outline-danger btn-del'
                                data-code='${escHtml(data.code)}'>
                                <i class='fa fa-trash'></i>
                            </button>`;
                }
            }
        ],
        pageLength  : 25,
        lengthMenu  : [[10,25,50,100],[10,25,50,100]],
        language: {
            search: 'Cari:', processing: "<i class='fa fa-spinner fa-spin'></i>",
            zeroRecords: 'Tidak ada data.', info: '_START_-_END_ dari _TOTAL_',
            lengthMenu: 'Tampilkan _MENU_ baris'
        }
    });

    // Tab switch
    $('#refTabs .nav-link').on('click', function(e) {
        e.preventDefault();
        $('#refTabs .nav-link').removeClass('active');
        $(this).addClass('active');
        currentTbl = $(this).data('tbl');
        $('#systemLabel').text($(this).data('system'));
        dtTable.ajax.reload();
    });

    // Tambah baru
    $('#btnTambah').on('click', function() {
        $('#modalTitle').html('<i class="fa fa-plus me-2"></i>Tambah Data Baru');
        $('#f_tbl').val(currentTbl);
        $('#f_old_code').val('');
        $('#f_code').val('').prop('readonly', false);
        $('#f_display').val('');
        new bootstrap.Modal(document.getElementById('modalForm')).show();
    });

    // Edit
    $('#tabelRef tbody').on('click', '.btn-edit', function() {
        $('#modalTitle').html('<i class="fa fa-edit me-2"></i>Edit Data');
        $('#f_tbl').val(currentTbl);
        const c = $(this).data('code');
        const d = $(this).data('display');
        $('#f_old_code').val(c);
        $('#f_code').val(c).prop('readonly', false);
        $('#f_display').val(d);
        new bootstrap.Modal(document.getElementById('modalForm')).show();
    });

    // Delete
    $('#tabelRef tbody').on('click', '.btn-del', function() {
        const code = $(this).data('code');
        Swal.fire({
            title: 'Hapus data?',
            text : `Kode "${code}" akan dihapus permanen.`,
            icon : 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText : 'Ya, Hapus!',
            cancelButtonText  : 'Batal'
        }).then(result => {
            if (!result.isConfirmed) return;
            $.post('ajax.php?action=delete', {
                csrf_token: CSRF_TOKEN,
                tbl       : currentTbl,
                code      : code
            }, function(r) {
                if (r.status === 'success') {
                    Swal.fire('Terhapus!', r.message, 'success');
                    dtTable.ajax.reload(null, false);
                } else {
                    Swal.fire('Gagal!', r.message, 'error');
                }
            }, 'json');
        });
    });

    // Simpan
    $('#btnSimpan').on('click', function() {
        const btn = $(this), orig = btn.html();
        btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);
        $.post('ajax.php?action=save', {
            csrf_token: CSRF_TOKEN,
            tbl      : $('#f_tbl').val(),
            old_code : $('#f_old_code').val(),
            code     : $('#f_code').val().trim(),
            display  : $('#f_display').val().trim()
        }, function(r) {
            btn.html(orig).prop('disabled', false);
            if (r.status === 'success') {
                bootstrap.Modal.getInstance(document.getElementById('modalForm')).hide();
                dtTable.ajax.reload(null, false);
                Swal.fire({icon:'success', title:'Tersimpan!', text:r.message, timer:1500, showConfirmButton:false});
            } else {
                Swal.fire('Gagal!', r.message, 'error');
            }
        }, 'json').fail(function() {
            btn.html(orig).prop('disabled', false);
            Swal.fire('Error!', 'Koneksi server gagal.', 'error');
        });
    });
});

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

// Anti-Tampering
setInterval(function(){var el=document.getElementById('footer-credit-block');if(!el){document.body.innerHTML='';return;}var html=el.innerHTML,cs=window.getComputedStyle(el),checks=[atob('SWNoc2FuIExlb25oYXJ0'),atob('c2F3ZXJpYS5jby9pY2hzYW5sZW9uaGFydA=='),atob('NjI4NTcyNjEyMzc3Nw=='),atob('QEljaHNhbkxlb25oYXJ0'),atob('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL2ljaHNhbmxlb25oYXJ0L2FkZC1vbnNfd2ViYXBwc19raGFuemEvbWFpbi9xcmlzLWljaHNhbi5wbmc=')];if(cs.display==='none'||cs.visibility==='hidden'||cs.opacity==='0'){document.body.innerHTML='';return;}for(var i=0;i<checks.length;i++){if(html.indexOf(checks[i])===-1){document.body.innerHTML='';return;}}},3000);
</script>
</body>
</html>
