<?php
/**
 * modules/admin_ref/index.php — Admin Referensi KFA / LOINC / SNOMED
 * KHUSUS Super Admin
 */
require_once '../../conf.php';
require_once '../../auth_check.php';
require_login();
if (empty($_SESSION['is_admin'])) {
    $_SESSION['flash_error'] = 'Halaman ini hanya dapat diakses oleh Super Admin.';
    header('Location: ../../index.php');
    exit;
}

// Konfigurasi tiap tab (nama tabel, kolom, label)
$tabs = [
    'kfa'    => [
        'label'   => 'KFA (Kamus Farmasi & Alkes)',
        'icon'    => 'fa-pills',
        'color'   => '#4f46e5',
        'cols'    => ['kfa_code' => 'Kode KFA', 'display_name' => 'Nama / Display'],
        'pk'      => 'kfa_code',
        'system'  => 'http://sys-ids.kemkes.go.id/kfa',
    ],
    'loinc'  => [
        'label'   => 'LOINC',
        'icon'    => 'fa-flask',
        'color'   => '#0891b2',
        'cols'    => ['loinc_num' => 'Kode LOINC', 'component' => 'Component', 'long_common_name' => 'Long Common Name'],
        'pk'      => 'loinc_num',
        'system'  => 'http://loinc.org',
    ],
    'snomed' => [
        'label'   => 'SNOMED-CT',
        'icon'    => 'fa-microscope',
        'color'   => '#be185d',
        'cols'    => ['conceptId' => 'Kode SNOMED', 'term' => 'Display'],
        'pk'      => 'conceptId',
        'system'  => 'http://snomed.info/sct',
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Referensi — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="../../logo.php">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }
        .page-header { background: linear-gradient(135deg, #1e1b4b, #4f46e5); color: white; border-radius: 16px; padding: 1.5rem 2rem; margin-bottom: 1.5rem; }
        .page-header h4 { font-weight: 700; margin: 0; }
        .page-header p  { margin: .25rem 0 0; opacity: .8; font-size: .875rem; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .lazy-notice { text-align: center; padding: 3rem 1rem; color: #94a3b8; }
        .lazy-notice i { font-size: 2.5rem; margin-bottom: .75rem; display: block; }
        .footer-credit { text-align: center; padding: 1.5rem; font-size: .72rem; color: #94a3b8; cursor: pointer; transition: all .2s; }
        .footer-credit:hover { color: #6366f1; }
        .footer-credit a { color: #6d28d9; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white border-bottom px-3 py-2 mb-4">
    <a class="text-decoration-none" href="../../index.php" style="color:#4f46e5;font-weight:500;font-size:.875rem;">
        <i class="fa fa-arrow-left me-2"></i>Dashboard
    </a>
    <span class="navbar-brand mb-0" style="font-size:1rem;color:#4f46e5">
        <i class="fa-solid fa-shield-halved me-2"></i>Admin Referensi — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?>
    </span>
    <div>
        <span class="text-muted small me-3"><i class="fa fa-user-shield me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?> (Super Admin)</span>
        <a href="../../logout.php" class="text-danger small text-decoration-none"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="page-header">
        <h4><i class="fa-solid fa-shield-halved me-2"></i> Admin Referensi: KFA / LOINC / SNOMED</h4>
        <p>Tambah, edit, atau hapus data referensi master. Data ini digunakan oleh seluruh modul mapping. <strong>Hati-hati: perubahan berdampak ke semua user.</strong></p>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-0" id="adminTabs">
                <?php $first = true; foreach ($tabs as $key => $cfg): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $first ? 'active' : '' ?>" href="#"
                       data-tbl="<?= $key ?>"
                       data-cols='<?= json_encode($cfg['cols']) ?>'
                       data-pk="<?= $cfg['pk'] ?>"
                       data-system="<?= htmlspecialchars($cfg['system']) ?>"
                       data-color="<?= $cfg['color'] ?>"
                       style="<?= $first ? 'color:'.$cfg['color'].';font-weight:700;' : '' ?>">
                        <i class="fa-solid <?= $cfg['icon'] ?> me-1"></i> <?= $cfg['label'] ?>
                    </a>
                </li>
                <?php $first = false; endforeach; ?>
            </ul>

            <div class="bg-light border border-top-0 rounded-bottom p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">System: <span id="systemLabel" class="fw-semibold" style="color:#4f46e5"><?= $tabs['kfa']['system'] ?></span></small>
                    </div>
                    <button class="btn btn-sm btn-primary" id="btnTambah">
                        <i class="fa fa-plus me-1"></i> Tambah Entri Baru
                    </button>
                </div>
                <div class="mt-2">
                    <div class="input-group input-group-sm" style="max-width:400px">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" class="form-control" id="lazySearch" placeholder="Ketik minimum 2 karakter untuk mencari...">
                        <button class="btn btn-outline-primary" id="btnCari"><i class="fa fa-filter"></i> Tampilkan</button>
                    </div>
                    <div class="form-text">Data besar — ketik keyword terlebih dahulu untuk performa optimal.</div>
                </div>
            </div>

            <!-- Lazy placeholder -->
            <div id="lazyPlaceholder" class="lazy-notice">
                <i class="fa fa-magnifying-glass text-muted"></i>
                <p class="mb-0 fw-semibold">Ketik keyword (min. 2 karakter) lalu klik <strong>Tampilkan</strong> untuk memuat data.</p>
                <p class="small text-muted mb-0">Tabel ini berisi ratusan ribu baris — lazy loading mencegah server overload.</p>
            </div>

            <div class="table-responsive" id="tableWrapper" style="display:none;">
                <table id="tabelAdminRef" class="table table-striped table-hover table-bordered w-100">
                    <thead class="table-light" id="theadDynamic"></thead>
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
            <div class="modal-header text-white" id="modalHeader" style="background:#4f46e5">
                <h5 class="modal-title" id="modalTitle"><i class="fa fa-edit me-2"></i>Form Data</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalFields">
                <!-- Diisi dynamic oleh JS -->
                <input type="hidden" id="f_tbl">
                <input type="hidden" id="f_old_pk">
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
<div class="modal fade" id="modalSaweria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
            <div class="modal-header border-0 pb-0"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center pt-0 pb-4 px-4">
                <div class="mb-3"><img src="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" class="img-fluid rounded-3 shadow-sm" style="max-width:280px;" alt="QRIS Donasi"></div>
                <h5 class="fw-bold text-primary mb-3">Apresiasi &amp; Dukungan</h5>
                <p class="text-muted small px-2 mb-4" style="line-height:1.6;">Terima kasih atas dukungannya! 🙏</p>
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
let currentTbl  = 'kfa';
let currentCols = <?= json_encode($tabs['kfa']['cols']) ?>;
let currentPk   = '<?= $tabs['kfa']['pk'] ?>';
let currentColor= '<?= $tabs['kfa']['color'] ?>';
let dtTable     = null;
let dtInit      = false;

function escH(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

function buildCols() {
    let arr = [];
    $.each(currentCols, function(col, lbl) {
        arr.push({ title: lbl, data: null, render: function(d, t, row, meta) { return escH(row[meta.col]); } });
    });
    arr.push({
        title: 'Aksi', data: null, orderable: false, className: 'text-center',
        render: function(d, t, row) {
            const pkVal = escH(row[0]);
            return `<button class="btn btn-sm btn-outline-primary btn-edit me-1" data-row='${escH(JSON.stringify(row))}'><i class="fa fa-edit"></i></button>
                    <button class="btn btn-sm btn-outline-danger btn-del" data-pk="${pkVal}"><i class="fa fa-trash"></i></button>`;
        }
    });
    return arr;
}

function buildTable() {
    if (dtInit) { dtTable.destroy(); dtInit = false; }
    $('#tabelAdminRef').find('thead, tbody').empty();

    // Build header
    let hdr = '<tr>';
    $.each(currentCols, (col, lbl) => { hdr += `<th>${escH(lbl)}</th>`; });
    hdr += '<th class="text-center">Aksi</th></tr>';
    $('#theadDynamic').html(hdr);

    dtTable = $('#tabelAdminRef').DataTable({
        processing : true,
        serverSide : true,
        searching  : false, // searchnya pakai custom input
        ajax: {
            url : 'ajax.php?action=load',
            data: function(d) {
                d.tbl = currentTbl;
                d.search = { value: $('#lazySearch').val().trim() };
            }
        },
        columns    : buildCols(),
        pageLength : 25,
        lengthMenu : [[10,25,50],[10,25,50]],
        language   : {
            processing: "<i class='fa fa-spinner fa-spin'></i>",
            zeroRecords: 'Tidak ada data yang cocok.',
            info: '_START_-_END_ dari _TOTAL_ hasil',
            lengthMenu: 'Tampilkan _MENU_ baris',
            infoEmpty: ''
        },
        drawCallback: function(settings) {
            const json = this.api().ajax.json();
            if (json && json.lazy_notice) {
                $('#tableWrapper').hide();
                $('#lazyPlaceholder').show();
            } else {
                $('#tableWrapper').show();
                $('#lazyPlaceholder').hide();
            }
        }
    });
    dtInit = true;
}

$(function() {
    // Tab switch
    $('#adminTabs .nav-link').on('click', function(e) {
        e.preventDefault();
        $('#adminTabs .nav-link').css({'color':'','font-weight':''}).removeClass('active');
        $(this).addClass('active').css({'color':$(this).data('color'),'font-weight':'700'});
        currentTbl   = $(this).data('tbl');
        currentCols  = $(this).data('cols');
        currentPk    = $(this).data('pk');
        currentColor = $(this).data('color');
        $('#systemLabel').text($(this).data('system')).css('color', currentColor);
        $('#modalHeader').css('background', currentColor);
        $('#lazySearch').val('');
        $('#tableWrapper').hide();
        $('#lazyPlaceholder').show();
        if (dtInit) { dtTable.destroy(); dtInit = false; }
    });

    // Tampilkan / search
    $('#btnCari').on('click', triggerLoad);
    $('#lazySearch').on('keyup', function(e) { if (e.key === 'Enter') triggerLoad(); });

    function triggerLoad() {
        const q = $('#lazySearch').val().trim();
        if (q.length < 2) {
            Swal.fire({icon:'info', title:'Keyword terlalu pendek', text:'Masukkan minimal 2 karakter untuk mencari.', timer:1500, showConfirmButton:false});
            return;
        }
        buildTable();
    }

    // Tambah baru
    $('#btnTambah').on('click', function() {
        $('#modalTitle').html('<i class="fa fa-plus me-2"></i>Tambah Entri Baru');
        $('#f_tbl').val(currentTbl); $('#f_old_pk').val('');
        let html = '<input type="hidden" id="f_tbl"><input type="hidden" id="f_old_pk">';
        $.each(currentCols, function(col, lbl) {
            html += `<div class="mb-3"><label class="form-label fw-bold">${escH(lbl)}</label>
                     <input type="text" class="form-control font-monospace" id="f_${escH(col)}" data-col="${escH(col)}" placeholder="${escH(lbl)}..."></div>`;
        });
        $('#modalFields').html(html);
        $('#f_tbl').val(currentTbl); $('#f_old_pk').val('');
        $('#modalHeader').css('background', currentColor);
        new bootstrap.Modal(document.getElementById('modalForm')).show();
    });

    // Edit
    $('#tabelAdminRef tbody').on('click', '.btn-edit', function() {
        const row  = JSON.parse($(this).attr('data-row'));
        const keys = Object.keys(currentCols);
        $('#modalTitle').html('<i class="fa fa-edit me-2"></i>Edit Data');
        let html = '<input type="hidden" id="f_tbl"><input type="hidden" id="f_old_pk">';
        keys.forEach((col, i) => {
            const lbl = currentCols[col];
            html += `<div class="mb-3"><label class="form-label fw-bold">${escH(lbl)}</label>
                     <input type="text" class="form-control font-monospace" id="f_${escH(col)}" data-col="${escH(col)}" value="${escH(row[i])}" placeholder="${escH(lbl)}..."></div>`;
        });
        $('#modalFields').html(html);
        $('#f_tbl').val(currentTbl);
        $('#f_old_pk').val(row[0]);
        $('#modalHeader').css('background', currentColor);
        new bootstrap.Modal(document.getElementById('modalForm')).show();
    });

    // Delete
    $('#tabelAdminRef tbody').on('click', '.btn-del', function() {
        const pkVal = $(this).data('pk');
        Swal.fire({
            title: 'Hapus entri?',
            html : `Kode <code>${escH(String(pkVal))}</code> akan dihapus permanen dari tabel referensi master.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then(res => {
            if (!res.isConfirmed) return;
            $.post('ajax.php?action=delete', { csrf_token: CSRF_TOKEN, tbl: currentTbl, pk_val: pkVal }, function(r) {
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
        let postData = {
            csrf_token: CSRF_TOKEN,
            tbl       : $('#f_tbl').val(),
            old_pk    : $('#f_old_pk').val()
        };
        // Ambil semua field dinamis
        $('#modalFields [data-col]').each(function() {
            postData[$(this).data('col')] = $(this).val().trim();
        });
        $.post('ajax.php?action=save', postData, function(r) {
            btn.html(orig).prop('disabled', false);
            if (r.status === 'success') {
                bootstrap.Modal.getInstance(document.getElementById('modalForm')).hide();
                if (dtInit) dtTable.ajax.reload(null, false);
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

// Anti-Tampering
setInterval(function(){var el=document.getElementById('footer-credit-block');if(!el){document.body.innerHTML='';return;}var html=el.innerHTML,cs=window.getComputedStyle(el),checks=[atob('SWNoc2FuIExlb25oYXJ0'),atob('c2F3ZXJpYS5jby9pY2hzYW5sZW9uaGFydA=='),atob('NjI4NTcyNjEyMzc3Nw=='),atob('QEljaHNhbkxlb25oYXJ0'),atob('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL2ljaHNhbmxlb25oYXJ0L2FkZC1vbnNfd2ViYXBwc19raGFuemEvbWFpbi9xcmlzLWljaHNhbi5wbmc=')];if(cs.display==='none'||cs.visibility==='hidden'||cs.opacity==='0'){document.body.innerHTML='';return;}for(var i=0;i<checks.length;i++){if(html.indexOf(checks[i])===-1){document.body.innerHTML='';return;}}},3000);
</script>
</body>
</html>
