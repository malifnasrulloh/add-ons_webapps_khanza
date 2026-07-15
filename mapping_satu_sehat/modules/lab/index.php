<?php
/**
 * modules/lab/index.php — Halaman Mapping Laboratorium
 */
require_once '../../conf.php';
require_once '../../auth_check.php';
check_module_access('satu_sehat_mapping_lab');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mapping Lab — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="../../logo.php">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }
        .select2-container { z-index: 9999; }
        .nav-back { color: #0891b2; font-weight: 500; text-decoration: none; font-size: .875rem; }
        .page-header { background: linear-gradient(135deg,#0891b2,#06b6d4); color:white; border-radius:16px; padding:1.5rem 2rem; margin-bottom:1.5rem; }
        .page-header h4 { font-weight:700; margin:0; }
        .page-header p { margin:.25rem 0 0; opacity:.8; font-size:.875rem; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .footer-credit { text-align: center; padding: 1.5rem; font-size: .72rem; color: #94a3b8; cursor: pointer; transition: all 0.2s; }
        .footer-credit:hover { color: #6366f1; background: rgba(99, 102, 241, 0.05); }
        .footer-credit a { color: #6d28d9; text-decoration: none; font-weight: 600; }
        .footer-credit a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white border-bottom px-3 py-2 mb-4">
    <a class="nav-back" href="../../index.php"><i class="fa fa-arrow-left me-2"></i>Dashboard</a>
    <span class="navbar-brand text-primary mb-0" style="font-size:1rem; color:#0891b2 !important">
        <i class="fa-solid fa-flask me-2"></i> Mapping Lab — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?>
    </span>
    <div>
        <span class="text-muted small me-3"><i class="fa fa-user me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
        <a href="../../logout.php" class="text-danger small text-decoration-none"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="page-header">
        <h4><i class="fa-solid fa-flask me-2"></i> Mapping Laboratorium ke LOINC &amp; SNOMED</h4>
        <p>Klik Mapping pada baris pemeriksaan untuk menetapkan kode LOINC dan spesimen SNOMED-CT.</p>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <label class="form-label fw-semibold small" style="color:#0891b2">Cari Nama Pemeriksaan (Server)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" id="keyword_pemeriksaan" class="form-control" placeholder="Ketik nama pemeriksaan lalu tekan Enter...">
                        <button class="btn px-4 text-white" id="btnCariServer" style="background:#0891b2">
                            <i class="fa fa-filter me-1"></i> Tampilkan
                        </button>
                    </div>
                    <div class="form-text">Kosongkan untuk menampilkan <strong>semua data</strong> dengan server-side paging.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableLab" class="table table-hover table-bordered w-100 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">ID</th>
                            <th width="35%">Detail Pemeriksaan (RS)</th>
                            <th width="40%">Mapping (Satu Sehat)</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Mapping Lab -->
<div class="modal fade" id="modalMapping" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:#0891b2">
                <h5 class="modal-title">Mapping Pemeriksaan Lab</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formMapping">
                    <input type="hidden" name="id_template" id="m_id_template">
                    <div class="p-3 mb-3 bg-light rounded border">
                        <small class="text-muted">Pemeriksaan:</small><br>
                        <strong id="m_nama_pemeriksaan" class="fs-4 text-dark"></strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary">1. Kode Pemeriksaan (LOINC)</label>
                        <select class="form-select" id="sel_loinc" name="loinc_code" style="width:100%" required></select>
                        <div class="mt-1"><span id="loinc_source_badge" class="badge bg-secondary" style="font-size:.7rem;"><i class="fa fa-database me-1"></i>Sumber: Database Lokal</span></div>
                        <input type="hidden" name="loinc_display" id="m_loinc_display">
                        <div class="form-text">Cari dalam Bahasa Inggris. System: <i>http://loinc.org</i></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">2. Spesimen/Sampel (SNOMED-CT)</label>
                        <select class="form-select" id="sel_snomed" name="snomed_code" style="width:100%" required></select>
                        <div class="mt-1"><span id="snomed_source_badge" class="badge bg-secondary" style="font-size:.7rem;"><i class="fa fa-database me-1"></i>Sumber: Database Lokal</span></div>
                        <input type="hidden" name="snomed_display" id="m_snomed_display">
                        <div class="form-text">Contoh: Serum, Plasma, Urine. System: <i>http://snomed.info/sct</i></div>
                    </div>
                    <hr>
                    <div class="form-check form-switch p-3 bg-warning bg-opacity-10 rounded border border-warning">
                        <input class="form-check-input" type="checkbox" id="apply_all" name="apply_all" value="true" checked>
                        <label class="form-check-label fw-bold" for="apply_all">Terapkan ke semua pemeriksaan bernama sama?</label>
                        <div class="small text-muted mt-1">Pemeriksaan lain bernama "<b id="m_nama_copy"></b>" akan otomatis ter-update.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary px-4" id="btnSimpan" style="background:#0891b2; border-color:#0891b2">Simpan Mapping</button>
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

<!-- Modal Saweria (Uneg-uneg Mengemis) -->
<div class="modal fade" id="modalSaweria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center pt-0 pb-4 px-4">
                <div class="mb-3">
                    <img src="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" class="img-fluid rounded-3 shadow-sm" style="max-width: 280px;" alt="QRIS Donasi">
                </div>
                <h5 class="fw-bold text-primary mb-3">Apresiasi & Dukungan Donasi</h5>
                <p class="text-muted small px-2 mb-4" style="line-height: 1.6;">
                    Halo rekan-rekan IT dan Super Admin. Terima kasih telah menggunakan aplikasi pemetaan Satu Sehat ini.<br><br>
                    Jika aplikasi ini membantu mempermudah pekerjaan Anda, mohon bantuannya untuk sedikit memberikan apresiasi / "traktiran kopi" agar saya tetap semangat melakukan maintenance dan update fitur lainnya. Berapapun dukungan Anda sangat berarti bagi kelangsungan pengembangan aplikasi ini.<br><br>
                    <strong>Terima kasih banyak atas dukungannya! 🙏</strong>
                </p>
                <div class="d-grid gap-2">
                    <a href="https://saweria.co/ichsanleonhart" target="_blank" class="btn btn-primary py-2 fw-bold" style="background:linear-gradient(135deg, #4f46e5, #7c3aed); border:none;">
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
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>';
$(function() {
    var table = $('#tableLab').DataTable({
        "processing": true, "serverSide": true,
        "ajax": { "url": "ajax.php?action=load_table", "data": function(d) { d.keyword = $('#keyword_pemeriksaan').val(); } },
        "dom": "<'row mb-3'<'col-md-2'l><'col-md-6 text-center'B><'col-md-4'f>>" +
               "<'row'<'col-md-12'tr>>" +
               "<'row'<'col-md-5'i><'col-md-7'p>>",
        "buttons": [
            { extend: 'excelHtml5', text: '<i class="fa fa-file-excel"></i> Export Excel', className: 'btn btn-success btn-sm' }
        ],
        "columns": [
            {data:0,className:"text-center"},{data:1},{data:2},
            {data:3,className:"text-center",orderable:true},{data:4,className:"text-center",orderable:false}
        ],
        "pageLength": 25, "lengthMenu": [[10,25,50,100,-1],[10,25,50,100,"Semua"]],
        "language": {"search":"Filter:","processing":"<i class='fa fa-spinner fa-spin'></i>","zeroRecords":"Tidak ada data","info":"_START_-_END_ dari _TOTAL_","lengthMenu":"Tampilkan _MENU_ baris"}
    });
    $('#btnCariServer').click(function() { table.ajax.reload(); });
    $('#keyword_pemeriksaan').on('keyup', function(e) { if(e.key==='Enter') table.ajax.reload(); });

    // === FHIR Badge helper (4 state) ===
    function fhirSetBadge(id, state) {
        var b = $('#' + id);
        b.removeClass('bg-secondary bg-success bg-warning bg-info text-dark');
        if (state === 'loading')       b.addClass('bg-info').html('<i class="fa fa-spinner fa-spin me-1"></i>Menghubungi API FHIR...');
        else if (state === 'api')      b.addClass('bg-success').html('<i class="fa fa-cloud me-1"></i>Sumber: API FHIR Terminology (Online)');
        else if (state === 'fallback') b.addClass('bg-warning text-dark').html('<i class="fa fa-triangle-exclamation me-1"></i>API gagal &mdash; fallback Database Lokal');
        else                           b.addClass('bg-secondary').html('<i class="fa fa-database me-1"></i>Sumber: Database Lokal');
    }

    $('#sel_loinc').select2({
        theme: 'bootstrap-5', dropdownParent: $('#modalMapping'),
        placeholder: 'Cari Kode LOINC...', minimumInputLength: 2,
        ajax: {
            url: 'ajax.php?action=search_loinc', dataType: 'json', delay: 300,
            data: function(p) { return { term: p.term }; },
            beforeSend: function() { fhirSetBadge('loinc_source_badge', 'loading'); },
            processResults: function(d) {
                fhirSetBadge('loinc_source_badge', d.source || 'database');
                return { results: d.results };
            },
            error: function() { fhirSetBadge('loinc_source_badge', 'fallback'); }
        }
    }).on('select2:select', function(e) { $('#m_loinc_display').val(e.params.data.display); });

    $('#sel_snomed').select2({
        theme: 'bootstrap-5', dropdownParent: $('#modalMapping'),
        placeholder: 'Cari Spesimen/Sampel (SNOMED-CT)...', minimumInputLength: 2,
        ajax: {
            url: 'ajax.php?action=search_snomed', dataType: 'json', delay: 300,
            data: function(p) { return { term: p.term }; },
            beforeSend: function() { fhirSetBadge('snomed_source_badge', 'loading'); },
            processResults: function(d) {
                fhirSetBadge('snomed_source_badge', d.source || 'database');
                return { results: d.results };
            },
            error: function() { fhirSetBadge('snomed_source_badge', 'fallback'); }
        }
    }).on('select2:select', function(e) { $('#m_snomed_display').val(e.params.data.display); });

    $('#tableLab tbody').on('click', '.btn-map', function() {
        var id=$(this).data('id'), nama=$(this).data('nama'), loinc=$(this).data('loinc'), ld=$(this).data('loinc-display'), snomed=$(this).data('snomed'), sd=$(this).data('snomed-display');
        $('#m_id_template').val(id); $('#m_nama_pemeriksaan').text(nama); $('#m_nama_copy').text(nama);
        fhirSetBadge('loinc_source_badge', 'database');
        fhirSetBadge('snomed_source_badge', 'database');
        $('#sel_loinc').val(null).trigger('change');
        if (loinc) { var o=new Option(loinc+' - '+ld,loinc,true,true); $('#sel_loinc').append(o).trigger('change'); $('#m_loinc_display').val(ld); } else $('#m_loinc_display').val('');
        $('#sel_snomed').val(null).trigger('change');
        if (snomed) { var o2=new Option(snomed+' - '+sd,snomed,true,true); $('#sel_snomed').append(o2).trigger('change'); $('#m_snomed_display').val(sd); } else $('#m_snomed_display').val('');
        new bootstrap.Modal(document.getElementById('modalMapping')).show();
    });

    $('#btnSimpan').click(function() {
        var btn=$(this), orig=btn.html();
        btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled',true);
        var fd = $('#formMapping').serialize() + '&csrf_token=' + encodeURIComponent(CSRF_TOKEN);
        $.post('ajax.php?action=save_mapping', fd, function(r) {
            btn.html(orig).prop('disabled',false);
            if(r.status==='success') {
                bootstrap.Modal.getInstance(document.getElementById('modalMapping')).hide();
                Swal.fire({icon:'success',title:'Berhasil!',html:r.message,timer:2000,showConfirmButton:false});
                table.ajax.reload(null,false);
            } else Swal.fire('Gagal!',r.message,'error');
        },'json').fail(function(){ btn.html(orig).prop('disabled',false); Swal.fire('Error!','Koneksi gagal.','error'); });
    });

    setInterval(function(){var el=document.getElementById('footer-credit-block');if(!el){document.body.innerHTML='';return;}var html=el.innerHTML,cs=window.getComputedStyle(el),checks=[atob('SWNoc2FuIExlb25oYXJ0'),atob('c2F3ZXJpYS5jby9pY2hzYW5sZW9uaGFydA=='),atob('NjI4NTcyNjEyMzc3Nw=='),atob('QEljaHNhbkxlb25oYXJ0'),atob('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL2ljaHNhbmxlb25oYXJ0L2FkZC1vbnNfd2ViYXBwc19raGFuemEvbWFpbi9xcmlzLWljaHNhbi5wbmc=')];if(cs.display==='none'||cs.visibility==='hidden'||cs.opacity==='0'){document.body.innerHTML='';return;}for(var i=0;i<checks.length;i++){if(html.indexOf(checks[i])===-1){document.body.innerHTML='';return;}}},3000);
});
</script>
</body>
</html>
