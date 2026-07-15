<?php
/**
 * modules/radiologi/index.php — Halaman Mapping Radiologi Satu Sehat
 */
require_once '../../conf.php';
require_once '../../auth_check.php';
check_module_access('satu_sehat_mapping_radiologi');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mapping Radiologi — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="../../logo.php">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family:'Inter',sans-serif; background:#f1f5f9; }
        .select2-container { z-index:9999; }
        .nav-back { color:#be185d; font-weight:500; text-decoration:none; font-size:.875rem; }
        .page-header { background:linear-gradient(135deg,#be185d,#ec4899); color:white; border-radius:16px; padding:1.5rem 2rem; margin-bottom:1.5rem; }
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
    <span class="navbar-brand mb-0" style="font-size:1rem; color:#be185d">
        <i class="fa-solid fa-x-ray me-2"></i> Mapping Radiologi — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?>
    </span>
    <div>
        <span class="text-muted small me-3"><i class="fa fa-user me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
        <a href="../../logout.php" class="text-danger small text-decoration-none"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="page-header">
        <h4><i class="fa-solid fa-x-ray me-2"></i> Mapping Tindakan Radiologi ke LOINC &amp; SNOMED</h4>
        <p>Klik Mapping pada baris tindakan untuk menetapkan kode LOINC procedure dan spesimen SNOMED-CT.</p>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <label class="form-label fw-semibold small" style="color:#be185d">Cari Tindakan Radiologi (Server)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" id="keyword_rad" class="form-control" placeholder="Ketik nama tindakan radiologi lalu Enter...">
                        <button class="btn px-4 text-white" id="btnCariServer" style="background:#be185d">
                            <i class="fa fa-filter me-1"></i> Tampilkan
                        </button>
                    </div>
                    <div class="form-text">Kosongkan untuk menampilkan semua data dengan server-side paging.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableRad" class="table table-hover table-bordered w-100 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="10%">Kode RS</th>
                            <th width="30%">Nama Tindakan (RS)</th>
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

<!-- Modal Mapping Radiologi -->
<div class="modal fade" id="modalMappingRad" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:#be185d">
                <h5 class="modal-title"><i class="fa fa-x-ray me-2"></i> Mapping Tindakan Radiologi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="m_kd_jenis_prw">
                <div class="p-3 mb-3 bg-light rounded border">
                    <small class="text-muted">Tindakan Radiologi:</small><br>
                    <strong id="m_nama_rad" class="fs-4 text-dark"></strong>
                    <br><small class="text-muted" id="m_kd_rad"></small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-primary">1. Kode LOINC (Procedure)</label>
                    <select class="form-select" id="sel_loinc_rad" style="width:100%"></select>
                    <div class="mt-1"><span id="loinc_source_badge_rad" class="badge bg-secondary" style="font-size:.7rem;"><i class="fa fa-database me-1"></i>Sumber: Database Lokal</span></div>
                    <input type="hidden" id="m_loinc_display_rad">
                    <div class="form-text">Cari kode LOINC untuk prosedur radiologi (Bahasa Inggris). System: <i>http://loinc.org</i></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-success">2. Kode Spesimen/Lokasi (SNOMED-CT) <span class="text-muted fw-normal small">— opsional</span></label>
                    <select class="form-select" id="sel_snomed_rad" style="width:100%"></select>
                    <div class="mt-1"><span id="snomed_source_badge_rad" class="badge bg-secondary" style="font-size:.7rem;"><i class="fa fa-database me-1"></i>Sumber: Database Lokal</span></div>
                    <input type="hidden" id="m_snomed_display_rad">
                    <div class="form-text">Contoh: tubuh yang difoto. System: <i>http://snomed.info/sct</i></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn px-4 text-white" id="btnSimpanRad" style="background:#be185d; border-color:#be185d">
                    <i class="fa fa-save me-1"></i> Simpan Mapping
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
    var table = $('#tableRad').DataTable({
        "processing":true,"serverSide":true,
        "ajax":{"url":"ajax.php?action=load_table","data":function(d){d.keyword=$('#keyword_rad').val();}},
        "dom": "<'row mb-3'<'col-md-2'l><'col-md-6 text-center'B><'col-md-4'f>>" +
               "<'row'<'col-md-12'tr>>" +
               "<'row'<'col-md-5'i><'col-md-7'p>>",
        "buttons": [
            { extend: 'excelHtml5', text: '<i class="fa fa-file-excel"></i> Export Excel', className: 'btn btn-success btn-sm' }
        ],
        "columns":[{data:0},{data:1},{data:2},{data:3,className:"text-center",orderable:true},{data:4,className:"text-center",orderable:false}],
        "pageLength":25,"lengthMenu":[[10,25,50,100,-1],[10,25,50,100,"Semua"]],
        "language":{"search":"Filter:","processing":"<i class='fa fa-spinner fa-spin'></i>","zeroRecords":"Tidak ada data","info":"_START_-_END_ dari _TOTAL_","lengthMenu":"Tampilkan _MENU_ baris"}
    });
    $('#btnCariServer').click(function(){table.ajax.reload();});
    $('#keyword_rad').on('keyup',function(e){if(e.key==='Enter')table.ajax.reload();});

    // === FHIR Badge helper (4 state) ===
    function fhirSetBadge(id, state) {
        var b = $('#' + id);
        b.removeClass('bg-secondary bg-success bg-warning bg-info text-dark');
        if (state === 'loading')       b.addClass('bg-info').html('<i class="fa fa-spinner fa-spin me-1"></i>Menghubungi API FHIR...');
        else if (state === 'api')      b.addClass('bg-success').html('<i class="fa fa-cloud me-1"></i>Sumber: API FHIR Terminology (Online)');
        else if (state === 'fallback') b.addClass('bg-warning text-dark').html('<i class="fa fa-triangle-exclamation me-1"></i>API gagal &mdash; fallback Database Lokal');
        else                           b.addClass('bg-secondary').html('<i class="fa fa-database me-1"></i>Sumber: Database Lokal');
    }

    $('#sel_loinc_rad').select2({
        theme: 'bootstrap-5', dropdownParent: $('#modalMappingRad'),
        placeholder: 'Cari kode LOINC...', minimumInputLength: 2,
        ajax: {
            url: 'ajax.php?action=search_loinc', dataType: 'json', delay: 300,
            data: function(p) { return { term: p.term }; },
            beforeSend: function() { fhirSetBadge('loinc_source_badge_rad', 'loading'); },
            processResults: function(d) {
                fhirSetBadge('loinc_source_badge_rad', d.source || 'database');
                return { results: d.results };
            },
            error: function() { fhirSetBadge('loinc_source_badge_rad', 'fallback'); }
        }
    }).on('select2:select', function(e) { $('#m_loinc_display_rad').val(e.params.data.display); });

    $('#sel_snomed_rad').select2({
        theme: 'bootstrap-5', dropdownParent: $('#modalMappingRad'),
        placeholder: 'Cari Spesimen/Lokasi...', minimumInputLength: 2, allowClear: true,
        ajax: {
            url: 'ajax.php?action=search_snomed', dataType: 'json', delay: 300,
            data: function(p) { return { term: p.term }; },
            beforeSend: function() { fhirSetBadge('snomed_source_badge_rad', 'loading'); },
            processResults: function(d) {
                fhirSetBadge('snomed_source_badge_rad', d.source || 'database');
                return { results: d.results };
            },
            error: function() { fhirSetBadge('snomed_source_badge_rad', 'fallback'); }
        }
    }).on('select2:select', function(e) { $('#m_snomed_display_rad').val(e.params.data.display); });

    $('#tableRad tbody').on('click','.btn-map',function(){
        var kd=$(this).data('kd'),nama=$(this).data('nama'),code=$(this).data('code'),disp=$(this).data('display'),sc=$(this).data('sampel-code'),sd=$(this).data('sampel-display');
        $('#m_kd_jenis_prw').val(kd); $('#m_nama_rad').text(nama); $('#m_kd_rad').text(kd);
        fhirSetBadge('loinc_source_badge_rad', 'database');
        fhirSetBadge('snomed_source_badge_rad', 'database');
        $('#sel_loinc_rad').val(null).trigger('change');
        if(code){var o=new Option(code+' - '+disp,code,true,true);$('#sel_loinc_rad').append(o).trigger('change');$('#m_loinc_display_rad').val(disp);}else $('#m_loinc_display_rad').val('');
        $('#sel_snomed_rad').val(null).trigger('change');
        if(sc){var o2=new Option(sc+' - '+sd,sc,true,true);$('#sel_snomed_rad').append(o2).trigger('change');$('#m_snomed_display_rad').val(sd);}else $('#m_snomed_display_rad').val('');
        new bootstrap.Modal(document.getElementById('modalMappingRad')).show();
    });

    $('#btnSimpanRad').click(function(){
        var btn=$(this),orig=btn.html();
        btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled',true);
        $.post('ajax.php?action=save_mapping',{
            csrf_token:CSRF_TOKEN,
            kd_jenis_prw:$('#m_kd_jenis_prw').val(),
            loinc_code:$('#sel_loinc_rad').val()||'',
            loinc_display:$('#m_loinc_display_rad').val(),
            snomed_code:$('#sel_snomed_rad').val()||'',
            snomed_display:$('#m_snomed_display_rad').val()
        },function(r){
            btn.html(orig).prop('disabled',false);
            if(r.status==='success'){
                btn.html('<i class="fa fa-check"></i> Tersimpan!').css({'background':'#16a34a','border-color':'#16a34a'});
                setTimeout(function(){btn.html(orig).css({'background':'#be185d','border-color':'#be185d'});bootstrap.Modal.getInstance(document.getElementById('modalMappingRad')).hide();table.ajax.reload(null,false);},1500);
            } else Swal.fire('Gagal!',r.message,'error');
        },'json').fail(function(){btn.html(orig).prop('disabled',false);Swal.fire('Error!','Koneksi gagal.','error');});
    });

    setInterval(function(){var el=document.getElementById('footer-credit-block');if(!el){document.body.innerHTML='';return;}var html=el.innerHTML,cs=window.getComputedStyle(el),checks=[atob('SWNoc2FuIExlb25oYXJ0'),atob('c2F3ZXJpYS5jby9pY2hzYW5sZW9uaGFydA=='),atob('NjI4NTcyNjEyMzc3Nw=='),atob('QEljaHNhbkxlb25oYXJ0'),atob('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL2ljaHNhbmxlb25oYXJ0L2FkZC1vbnNfd2ViYXBwc19raGFuemEvbWFpbi9xcmlzLWljaHNhbi5wbmc=')];if(cs.display==='none'||cs.visibility==='hidden'||cs.opacity==='0'){document.body.innerHTML='';return;}for(var i=0;i<checks.length;i++){if(html.indexOf(checks[i])===-1){document.body.innerHTML='';return;}}},3000);
});
</script>
</body>
</html>
