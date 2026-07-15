<?php
/**
 * modules/vaksin/index.php — Halaman Mapping Vaksin Satu Sehat
 */
require_once '../../conf.php';
require_once '../../auth_check.php';
check_module_access('satu_sehat_mapping_vaksin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mapping Vaksin — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></title>
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
        .nav-back { color:#059669; font-weight:500; text-decoration:none; font-size:.875rem; }
        .page-header { background:linear-gradient(135deg,#059669,#10b981); color:white; border-radius:16px; padding:1.5rem 2rem; margin-bottom:1.5rem; }
        .page-header h4 { font-weight:700; margin:0; }
        .page-header p { margin:.25rem 0 0; opacity:.8; font-size:.875rem; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .footer-credit { text-align: center; padding: 1.5rem; font-size: .72rem; color: #94a3b8; cursor: pointer; transition: all 0.2s; }
        .footer-credit:hover { color: #6366f1; background: rgba(99, 102, 241, 0.05); }
        .footer-credit a { color: #6d28d9; text-decoration: none; font-weight: 600; }
        .footer-credit a:hover { text-decoration: underline; }
        .select2-container { z-index: 9999; }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white border-bottom px-3 py-2 mb-4">
    <a class="nav-back" href="../../index.php"><i class="fa fa-arrow-left me-2"></i>Dashboard</a>
    <span class="navbar-brand mb-0" style="font-size:1rem; color:#059669">
        <i class="fa-solid fa-syringe me-2"></i> Mapping Vaksin — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?>
    </span>
    <div>
        <span class="text-muted small me-3"><i class="fa fa-user me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
        <a href="../../logout.php" class="text-danger small text-decoration-none"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4><i class="fa-solid fa-syringe me-2"></i> Mapping Vaksin ke Referensi Satu Sehat</h4>
            <p>Petakan kode vaksin RS ke kode CVX/KFA, rute pemberian, dan dosis baku Satu Sehat.</p>
        </div>
        <a href="../referensi/index.php" target="_blank" class="btn btn-light" style="color: #059669; font-weight: bold; padding: 0.5rem 1.5rem; border-radius: 50px; font-size: 0.9rem;">
            <i class="fa fa-database me-2"></i> Master Referensi Data
        </a>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <label class="form-label fw-semibold small" style="color:#059669">Cari Nama Vaksin (Server)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" id="keyword_vaksin" class="form-control" placeholder="Ketik nama vaksin lalu Enter...">
                        <button class="btn px-4 text-white" id="btnCariServer" style="background:#059669">
                            <i class="fa fa-filter me-1"></i> Tampilkan
                        </button>
                    </div>
                    <div class="form-text">Kosongkan untuk menampilkan semua data obat/vaksin aktif dengan server-side paging.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableVaksin" class="table table-hover table-bordered w-100 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="10%">Kode RS</th>
                            <th width="25%">Nama Vaksin (RS)</th>
                            <th width="45%">Detail Mapping (CVX/KFA, Rute, Dosis)</th>
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

<!-- Modal Mapping Vaksin -->
<div class="modal fade" id="modalMappingVaksin" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:#059669">
                <h5 class="modal-title"><i class="fa fa-syringe me-2"></i> Form Mapping Vaksin</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="m_kode_brng_vaksin">
                <div class="alert alert-light border d-flex align-items-center mb-3">
                    <i class="fa fa-syringe fa-2x me-3 text-success"></i>
                    <div>
                        <div class="fw-bold fs-5" id="m_nama_vaksin">Nama Vaksin</div>
                        <small class="text-muted" id="m_kode_vaksin_label">Kode RS</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-success">1. Kode Vaksin (KFA / CVX)</label>
                    <div class="input-group">
                        <select class="form-select" id="vaksin_code_input" style="width:85%"></select>
                        <a href="https://satusehat.kemkes.go.id/kfa-browser/" target="_blank" class="btn btn-outline-secondary" title="Buka KFA Browser">
                            <i class="fa fa-external-link-alt"></i>
                        </a>
                    </div>
                    <!-- Badge sumber data, loading state, dan tombol resusitasi -->
                    <div class="mt-1 d-flex align-items-center gap-2 flex-wrap" id="kfa_vaksin_badge_row">
                        <span id="kfa_vaksin_source_badge" class="badge bg-secondary" style="font-size:.7rem;">
                            <i class="fa fa-database me-1"></i>Sumber: Database Lokal
                        </span>
                        <span id="kfa_vaksin_autofill_notice" class="text-success small" style="display:none;font-size:.72rem;">
                            <i class="fa fa-magic me-1"></i>Rute &amp; Satuan Dosis otomatis terisi dari API KFA
                        </span>
                        <button type="button" id="btnRetryKfaVaksin"
                            style="display:none;font-size:.72rem;padding:2px 10px;border-radius:20px;"
                            class="btn btn-sm btn-warning fw-semibold">
                            <i class="fa fa-rotate-right me-1"></i>Coba Ulang API
                        </button>
                    </div>
                    <div class="form-text">Masukkan kode KFA atau CVX. Otomatis menarik dari referensi KFA Satu Sehat Kemenkes.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-success">2. Nama / Display Vaksin</label>
                    <input type="text" class="form-control" id="vaksin_display_input" placeholder="Nama resmi vaksin sesuai standar referensi...">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary">3. System Kode <span class="fw-normal text-muted small">— pilih sesuai jenis kode</span></label>
                    <select class="form-select" id="vaksin_system_select">
                        <option value="http://sys-ids.kemkes.go.id/kfa">KFA Kemenkes (http://sys-ids.kemkes.go.id/kfa)</option>
                        <option value="http://hl7.org/fhir/sid/cvx">CVX (http://hl7.org/fhir/sid/cvx)</option>
                    </select>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">4. Rute Pemberian</label>
                        <select class="form-select select2-static" id="vaksin_route_select" style="width:100%;">
                            <option value="">-- Pilih Rute --</option>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM satu_sehat_ref_route");
                            while ($r = $stmt->fetch()) {
                                echo "<option value='" . htmlspecialchars($r['code'], ENT_QUOTES, 'UTF-8') . "' data-display='" . htmlspecialchars($r['display'], ENT_QUOTES, 'UTF-8') . "'>"
                                   . htmlspecialchars($r['display'], ENT_QUOTES, 'UTF-8') . " (ATC: " . htmlspecialchars($r['code'], ENT_QUOTES, 'UTF-8') . ")</option>";
                            }
                            ?>
                        </select>
                        <div class="form-text">System rute: http://www.whocc.no/atc</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">5. Satuan Dosis <span class="text-danger">*</span> <span class="fw-normal text-muted small">— pilih satuan baku vaksin</span></label>
                        <select class="form-select select2-static" id="dose_qty_code_select" style="width:100%;">
                            <option value="">-- Pilih Satuan --</option>
                            <?php
                            $stmtN = $pdo->query("SELECT * FROM satu_sehat_ref_numerator ORDER BY code ASC");
                            while ($nu = $stmtN->fetch()) {
                                echo "<option value='" . htmlspecialchars($nu['code'], ENT_QUOTES, 'UTF-8') . "' data-display='" . htmlspecialchars($nu['display'], ENT_QUOTES, 'UTF-8') . "'>"
                                   . htmlspecialchars($nu['code'], ENT_QUOTES, 'UTF-8') . " \u2014 " . htmlspecialchars($nu['display'], ENT_QUOTES, 'UTF-8') . "</option>";
                            }
                            ?>
                        </select>
                        <div class="form-text">System dosis: http://unitsofmeasure.org</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn px-4 text-white" id="btnSimpanVaksin" style="background:#059669; border-color:#059669">
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
    var table = $('#tableVaksin').DataTable({
        "processing":true,"serverSide":true,
        "ajax": { "url": "ajax.php?action=load_table", "data": function(d) { d.keyword = $('#keyword_vaksin').val(); } },
        "dom": "<'row mb-3'<'col-md-2'l><'col-md-6 text-center'B><'col-md-4'f>>" +
               "<'row'<'col-md-12'tr>>" +
               "<'row'<'col-md-5'i><'col-md-7'p>>",
        "buttons": [
            { extend: 'excelHtml5', text: '<i class="fa fa-file-excel"></i> Export Excel', className: 'btn btn-success btn-sm' }
        ],
        "columns": [
            {data:0},{data:1},{data:2},{data:3,className:"text-center",orderable:true},{data:4,className:"text-center",orderable:false}
        ],
        "pageLength": 25, "lengthMenu": [[10,25,50,100,-1],[10,25,50,100,"Semua"]],
        "language":{"search":"Filter:","processing":"<i class='fa fa-spinner fa-spin'></i>","zeroRecords":"Tidak ada data","info":"_START_-_END_ dari _TOTAL_","lengthMenu":"Tampilkan _MENU_ baris"}
    });
    $('#btnCariServer').click(function(){table.ajax.reload();});
    $('#keyword_vaksin').on('keyup',function(e){if(e.key==='Enter')table.ajax.reload();});

    // Select2 untuk form static
    $('.select2-static').select2({ theme: 'bootstrap-5', dropdownParent: $('#modalMappingVaksin') });

    // Select2 config for Vaksin Code — dengan loading indicator, fallback notice, retry
    var kfaVaksinLastSource = 'database';
    var kfaVaksinLastTerm   = '';

    function kfaSetBadgeVaksin(state) {
        var badge = $('#kfa_vaksin_source_badge');
        var retry = $('#btnRetryKfaVaksin');
        badge.removeClass('bg-secondary bg-success bg-warning bg-info text-dark');
        switch (state) {
            case 'loading':
                badge.addClass('bg-info')
                    .html('<i class="fa fa-spinner fa-spin me-1"></i>Menghubungi API KFA Kemenkes...');
                retry.hide();
                break;
            case 'api':
                badge.addClass('bg-success')
                    .html('<i class="fa fa-cloud me-1"></i>Sumber: API KFA Kemenkes');
                retry.hide();
                break;
            case 'fallback':
                badge.addClass('bg-warning text-dark')
                    .html('<i class="fa fa-triangle-exclamation me-1"></i>API KFA gagal &mdash; menggunakan Database Lokal');
                retry.show();
                break;
            default:
                badge.addClass('bg-secondary')
                    .html('<i class="fa fa-database me-1"></i>Sumber: Database Lokal');
                retry.hide();
        }
    }

    $('#vaksin_code_input').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalMappingVaksin'),
        placeholder: 'Ketik Kode atau Nama KFA...',
        minimumInputLength: 2,
        ajax: {
            url: 'ajax.php?action=search_kfa',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                kfaVaksinLastTerm = params.term;
                return { term: params.term };
            },
            beforeSend: function() {
                kfaSetBadgeVaksin('loading');
                $('#kfa_vaksin_autofill_notice').hide();
            },
            processResults: function(data) {
                kfaVaksinLastSource = data.source || 'database';
                if (kfaVaksinLastSource === 'api') {
                    kfaSetBadgeVaksin('api');
                } else if (kfaVaksinLastSource === 'fallback') {
                    kfaSetBadgeVaksin('fallback');
                } else {
                    kfaSetBadgeVaksin('database');
                }
                return { results: data.results };
            },
            error: function() { kfaSetBadgeVaksin('fallback'); },
            cache: false
        }
    }).on('select2:select', function(e) {
        var d = e.params.data;
        $('#vaksin_display_input').val(d.display_name);

        // AUTO-FILL dari API
        if (kfaVaksinLastSource === 'api') {
            var didAutofill = false;
            if (d.route_code) {
                if ($('#vaksin_route_select option[value="' + d.route_code + '"]').length > 0) {
                    $('#vaksin_route_select').val(d.route_code);
                    didAutofill = true;
                }
            }
            if (d.ucum_code) {
                if ($('#dose_qty_code_select option[value="' + d.ucum_code + '"]').length > 0) {
                    $('#dose_qty_code_select').val(d.ucum_code);
                    didAutofill = true;
                }
            }
            if (didAutofill) {
                $('#kfa_vaksin_autofill_notice').show();
            } else {
                $('#kfa_vaksin_autofill_notice').hide();
            }
        } else {
            $('#kfa_vaksin_autofill_notice').hide();
        }
    });

    // Tombol Coba Ulang (Resusitasi) KFA API — vaksin
    $('#btnRetryKfaVaksin').on('click', function() {
        var btn = $(this);
        btn.html('<i class="fa fa-spinner fa-spin me-1"></i>Memperbarui token...').prop('disabled', true);
        $.post('ajax.php?action=refresh_kfa_token', { csrf_token: CSRF_TOKEN }, function(r) {
            btn.prop('disabled', false);
            if (r.status === 'success') {
                kfaSetBadgeVaksin('database');
                btn.hide();
                var toast = $('<span>').addClass('badge bg-success ms-1').css('font-size', '.72rem')
                    .html('<i class="fa fa-check me-1"></i>Token diperbarui! Cari vaksin kembali.')
                    .appendTo('#kfa_vaksin_badge_row');
                setTimeout(function() { toast.fadeOut(400, function() { $(this).remove(); }); }, 3000);
            } else {
                btn.html('<i class="fa fa-rotate-right me-1"></i>Coba Ulang API');
                Swal.fire({ icon: 'error', title: 'Gagal Refresh Token', text: r.message });
            }
        }, 'json').fail(function() {
            btn.html('<i class="fa fa-rotate-right me-1"></i>Coba Ulang API').prop('disabled', false);
            Swal.fire('Error', 'Koneksi server gagal saat refresh token.', 'error');
        });
    });

    // Buka modal & isi data existing
    $('#tableVaksin tbody').on('click','.btn-map',function(){
        var d=$(this).data('json');
        $('#m_kode_brng_vaksin').val(d.kode_brng);
        $('#m_nama_vaksin').text(d.nama_brng);
        $('#m_kode_vaksin_label').text(d.kode_brng);

        // Reset badge sumber & notif auto-fill
        kfaVaksinLastSource = 'database';
        kfaSetBadgeVaksin('database');
        $('#kfa_vaksin_autofill_notice').hide();
        $('#btnRetryKfaVaksin').hide();

        $('#vaksin_code_input').val(null).trigger('change');
        if (d.vaksin_code) {
            var opt = new Option(d.vaksin_code + ' - ' + d.vaksin_display, d.vaksin_code, true, true);
            $('#vaksin_code_input').append(opt).trigger('change');
        }
        $('#vaksin_display_input').val(d.vaksin_display||'');
        
        if(d.route_code) $('#vaksin_route_select').val(d.route_code).trigger('change');
        else $('#vaksin_route_select').val('').trigger('change');
        if(d.dose_qty_code) $('#dose_qty_code_select').val(d.dose_qty_code).trigger('change');
        else $('#dose_qty_code_select').val('').trigger('change');
        new bootstrap.Modal(document.getElementById('modalMappingVaksin')).show();
    });

    // Simpan
    $('#btnSimpanVaksin').click(function(){
        // === VALIDASI CLIENT-SIDE ===
        var vaksinKode = ($('#vaksin_code_input').val() || '').trim();
        var doseCode   = $('#dose_qty_code_select').val();
        var doseUnit   = $('#dose_qty_code_select option:selected').data('display') || doseCode;

        if (!vaksinKode) {
            Swal.fire('Peringatan', 'Kode Vaksin KFA wajib dipilih.', 'warning');
            return;
        }
        if (!doseCode) {
            Swal.fire('Peringatan', 'Satuan dosis wajib dipilih (misal: mL).', 'warning');
            $('#dose_qty_code_select').addClass('is-invalid').focus();
            return;
        }
        $('#dose_qty_code_select').removeClass('is-invalid');
        // ===========================

        var btn=$(this),orig=btn.html();
        var routeOpt = $('#vaksin_route_select option:selected');
        btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled',true);
        $.post('ajax.php?action=save_mapping',{
            csrf_token:CSRF_TOKEN,
            kode_brng:$('#m_kode_brng_vaksin').val(),
            vaksin_code:($('#vaksin_code_input').val() || '').trim(),
            vaksin_display:($('#vaksin_display_input').val() || '').trim(),
            vaksin_system:$('#vaksin_system_select').val(),
            route_code:$('#vaksin_route_select').val(),
            route_display:routeOpt.data('display')||routeOpt.text().split('(')[0].trim(),
            dose_quantity_code: doseCode,
            dose_quantity_system:'http://unitsofmeasure.org',
            dose_quantity_unit: doseUnit
        },function(r){
            btn.html(orig).prop('disabled',false);
            if(r.status==='success'){
                btn.html('<i class="fa fa-check"></i> Tersimpan!').css({'background':'#16a34a','border-color':'#16a34a'});
                setTimeout(function(){btn.html(orig).css({'background':'#059669','border-color':'#059669'});bootstrap.Modal.getInstance(document.getElementById('modalMappingVaksin')).hide();table.ajax.reload(null,false);},1500);
            } else Swal.fire('Gagal!',r.message,'error');
        },'json').fail(function(){btn.html(orig).prop('disabled',false);Swal.fire('Error!','Koneksi gagal.','error');});
    });

    setInterval(function(){var el=document.getElementById('footer-credit-block');if(!el){document.body.innerHTML='';return;}var html=el.innerHTML,cs=window.getComputedStyle(el),checks=[atob('SWNoc2FuIExlb25oYXJ0'),atob('c2F3ZXJpYS5jby9pY2hzYW5sZW9uaGFydA=='),atob('NjI4NTcyNjEyMzc3Nw=='),atob('QEljaHNhbkxlb25oYXJ0'),atob('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL2ljaHNhbmxlb25oYXJ0L2FkZC1vbnNfd2ViYXBwc19raGFuemEvbWFpbi9xcmlzLWljaHNhbi5wbmc=')];if(cs.display==='none'||cs.visibility==='hidden'||cs.opacity==='0'){document.body.innerHTML='';return;}for(var i=0;i<checks.length;i++){if(html.indexOf(checks[i])===-1){document.body.innerHTML='';return;}}},3000);
});
</script>
</body>
</html>
