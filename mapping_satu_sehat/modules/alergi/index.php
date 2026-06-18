<?php
/**
 * modules/alergi/index.php — UI Mapping Alergi (Session-based)
 */
require_once __DIR__ . '/../../conf.php';
require_once __DIR__ . '/../../auth_check.php';
require_login();

// Cek apakah ada data di session
$has_data = false;
if (isset($_SESSION['alergi_mapping_data']) && !empty($_SESSION['alergi_mapping_data'])) {
    $has_data = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapping Alergi - Satu Sehat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .nav-tabs .nav-link { font-weight: 600; color: #6c757d; }
        .nav-tabs .nav-link.active { color: #0d6efd; border-bottom: 3px solid #0d6efd; }
        .table th { background-color: #f8f9fa; font-size: 0.85rem; text-transform: uppercase; }
        .table td { vertical-align: middle; font-size: 0.9rem; }
    </style>
</head>
<body class="p-3">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold text-primary"><i class="fa fa-allergies me-2"></i>Mapping Alergi (Satu Sehat)</h4>
        <div>
            <button class="btn btn-outline-danger btn-sm" id="btnClear" <?= !$has_data ? 'style="display:none;"' : '' ?>><i class="fa fa-trash me-1"></i>Bersihkan Data</button>
            <a href="ajax.php?action=download" class="btn btn-success btn-sm ms-2" id="btnDownload" <?= !$has_data ? 'style="display:none;"' : '' ?>><i class="fa fa-download me-1"></i>Download .iyem</a>
        </div>
    </div>

    <!-- Panel Import -->
    <div class="card mb-4" id="importPanel" <?= $has_data ? 'style="display:none;"' : '' ?>>
        <div class="card-header bg-white pt-3 pb-0">
            <ul class="nav nav-tabs border-bottom-0" id="importTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="file-tab" data-bs-toggle="tab" data-bs-target="#file-pane" type="button" role="tab"><i class="fa fa-file-upload me-1"></i>Upload File</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="url-tab" data-bs-toggle="tab" data-bs-target="#url-pane" type="button" role="tab"><i class="fa fa-link me-1"></i>Fetch URL</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="paste-tab" data-bs-toggle="tab" data-bs-target="#paste-pane" type="button" role="tab"><i class="fa fa-paste me-1"></i>Paste JSON</button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="importTabsContent">
                <!-- Upload File -->
                <div class="tab-pane fade show active" id="file-pane" role="tabpanel">
                    <form id="formUpload" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Pilih file <b>alergisatusehat.iyem</b> atau .json</label>
                            <input class="form-control" type="file" id="file_iyem" name="file_iyem" accept=".iyem,.json" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-upload me-1"></i>Upload Data</button>
                    </form>
                </div>
                <!-- Fetch URL -->
                <div class="tab-pane fade" id="url-pane" role="tabpanel">
                    <form id="formUrl">
                        <div class="mb-3">
                            <label class="form-label">Masukkan URL file JSON</label>
                            <input type="url" class="form-control" id="url_iyem" name="url_iyem" placeholder="https://example.com/alergisatusehat.iyem" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-cloud-download-alt me-1"></i>Fetch Data</button>
                    </form>
                </div>
                <!-- Paste JSON -->
                <div class="tab-pane fade" id="paste-pane" role="tabpanel">
                    <form id="formPaste">
                        <div class="mb-3">
                            <label class="form-label">Paste isi file JSON di sini</label>
                            <textarea class="form-control text-monospace" id="text_iyem" name="text_iyem" rows="6" placeholder='{ "alergi": [ ... ] }' required style="font-family: monospace; font-size: 0.85rem;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-clipboard-check me-1"></i>Import Text</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card" id="dataPanel" <?= !$has_data ? 'style="display:none;"' : '' ?>>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableAlergi" class="table table-bordered table-hover w-100">
                    <thead class="bg-light">
                        <tr>
                            <th>Keyword</th>
                            <th>Kategori</th>
                            <th>Kode SNOMED</th>
                            <th>Display SNOMED</th>
                            <th>Teks (Keterangan)</th>
                            <th style="width: 80px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Mapping -->
<div class="modal fade" id="modalMap" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-edit me-2"></i>Edit Mapping Alergi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formMap">
                    <input type="hidden" name="index" id="m_index">
                    <div class="p-3 mb-3 bg-light rounded border">
                        <div class="row">
                            <div class="col-md-4"><small class="text-muted">Keyword:</small><br><strong id="m_keyword_lbl" class="fs-5 text-dark"></strong></div>
                            <div class="col-md-8"><small class="text-muted">Keterangan:</small><br><span id="m_text_lbl"></span></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kategori (AllergyCategory)</label>
                        <select class="form-select" name="category" id="m_category" required>
                            <option value="food">Food (Makanan)</option>
                            <option value="medication">Medication (Obat)</option>
                            <option value="environment">Environment (Lingkungan)</option>
                            <option value="biologic">Biologic (Biologis)</option>
                            <option value="other">Other (Lainnya)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Kode SNOMED-CT (Allergy Code)</label>
                        <select class="form-select" id="m_snomed_code" name="snomed_code" style="width:100%" required></select>
                        <div class="mt-1">
                            <span id="snomed_badge" class="badge bg-success" style="font-size:.7rem;"><i class="fa fa-cloud me-1"></i>SNOMED Training API</span>
                            <a id="snomed_ext_link" href="#" target="_blank" class="badge bg-info text-dark text-decoration-none ms-1" style="font-size:.7rem; display:none;"><i class="fa fa-external-link me-1"></i>SNOMED Browser</a>
                        </div>
                        <input type="hidden" name="snomed_display" id="m_snomed_display">
                        <div class="form-text">Mencari: <i>Substance, Product, atau Allergy Finding</i>. System: <i>http://snomed.info/sct</i></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSimpanMap">Simpan Perubahan</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF_TOKEN = '<?= isset($_SESSION["csrf_token"]) ? htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") : "" ?>';
let table;

function reloadTable() {
    if (table) {
        table.ajax.reload(null, false);
    } else {
        table = $('#tableAlergi').DataTable({
            processing: true, serverSide: true,
            ajax: { url: 'ajax.php?action=get_data', type: 'POST' },
            columns: [
                { data: 'keyword', render: function(data){ return '<strong>'+data+'</strong>'; } },
                { data: 'category', render: function(data){ 
                    const colors = {food:'success', medication:'danger', environment:'info', biologic:'warning', other:'secondary'};
                    return `<span class="badge bg-${colors[data]||'secondary'}">${data}</span>`; 
                }},
                { data: 'coding_code', render: function(data){ return `<code>${data}</code>`; } },
                { data: 'coding_display' },
                { data: 'text' },
                { data: '__index', orderable: false, searchable: false, className: 'text-center',
                  render: function(data, type, row) {
                      return `<button class="btn btn-sm btn-primary btn-edit" data-idx="${data}" data-keyword="${row.keyword}" data-txt="${row.text}" data-cat="${row.category}" data-sc="${row.coding_code}" data-sd="${row.coding_display}"><i class="fa fa-edit"></i></button>`;
                  }
                }
            ],
            language: { processing: "<i class='fa fa-spinner fa-spin'></i> Memuat..." }
        });
    }
}

function handleImportResponse(res) {
    if (res.status === 'success') {
        Swal.fire('Berhasil', res.message, 'success');
        $('#importPanel').slideUp();
        $('#dataPanel').slideDown();
        $('#btnClear, #btnDownload').show();
        reloadTable();
    } else {
        Swal.fire('Gagal', res.message || 'Terjadi kesalahan', 'error');
    }
}

$(document).ready(function() {
    if ($('#dataPanel').is(':visible')) reloadTable();

    // Import Handlers
    $('#formUpload').submit(function(e){
        e.preventDefault();
        var fd = new FormData(this); fd.append('csrf_token', CSRF_TOKEN);
        $.ajax({ url: 'ajax.php?action=import_file', type: 'POST', data: fd, processData: false, contentType: false, success: handleImportResponse });
    });
    $('#formUrl').submit(function(e){
        e.preventDefault();
        $.post('ajax.php?action=import_url', $(this).serialize() + '&csrf_token=' + CSRF_TOKEN, handleImportResponse, 'json');
    });
    $('#formPaste').submit(function(e){
        e.preventDefault();
        $.post('ajax.php?action=import_text', $(this).serialize() + '&csrf_token=' + CSRF_TOKEN, handleImportResponse, 'json');
    });

    // Clear Handler
    $('#btnClear').click(function(){
        Swal.fire({ title: 'Bersihkan Sesi?', text: "Semua data yang belum di-download akan hilang.", icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Bersihkan' }).then((result) => {
            if (result.isConfirmed) {
                $.post('ajax.php?action=clear', {csrf_token: CSRF_TOKEN}, function(res){
                    if(res.status==='success') {
                        $('#dataPanel, #btnClear, #btnDownload').hide();
                        $('#importPanel').slideDown();
                        $('#formUpload')[0].reset(); $('#formUrl')[0].reset(); $('#formPaste')[0].reset();
                        if(table) table.clear().draw();
                    }
                }, 'json');
            }
        });
    });

    // SNOMED Select2 Setup
    function formatSnomed(repo) {
        if (repo.loading) return repo.text;
        let term = repo.display || repo.text || '';
        let semanticTag = '';
        let match = term.match(/\(([^)]+)\)$/);
        if (match) { semanticTag = match[1]; term = term.replace(/\([^)]+\)$/, '').trim(); }
        let badges = `<span class="badge bg-success me-1"><i class="fa fa-fingerprint"></i> SNOMED-CT</span>`;
        if (semanticTag) badges += `<span class="badge bg-secondary me-1"><i class="fa fa-tag"></i> ${semanticTag}</span>`;
        return $("<div class='clearfix'><div class='fw-bold mb-1'>" + repo.id + " - " + term + "</div><div style='font-size: 0.75rem;'>" + badges + "</div></div>");
    }

    $('#m_snomed_code').select2({
        theme: 'bootstrap-5', dropdownParent: $('#modalMap'),
        placeholder: 'Ketik untuk mencari di SNOMED...', minimumInputLength: 2,
        ajax: {
            url: 'ajax.php?action=search_snomed_allergy', dataType: 'json', delay: 300,
            data: function(p) { return { term: p.term }; },
            processResults: function(d) { return { results: d.results }; }
        },
        templateResult: formatSnomed,
        templateSelection: function(repo) { return repo.text || repo.id; }
    }).on('select2:select', function(e) {
        $('#m_snomed_display').val(e.params.data.display);
        $('#snomed_ext_link').attr('href', 'https://browser.ihtsdotools.org/?perspective=full&conceptId1=' + e.params.data.id).show();
    }).on('select2:clear', function(e) {
        $('#snomed_ext_link').hide();
        $('#m_snomed_display').val('');
    });

    // Edit Button Click
    $('#tableAlergi tbody').on('click', '.btn-edit', function() {
        var btn = $(this);
        $('#m_index').val(btn.data('idx'));
        $('#m_keyword_lbl').text(btn.data('keyword'));
        $('#m_text_lbl').text(btn.data('txt'));
        $('#m_category').val(btn.data('cat'));
        
        var sc = btn.data('sc');
        var sd = btn.data('sd');
        $('#m_snomed_code').val(null).trigger('change');
        $('#snomed_ext_link').hide();
        if (sc && sc !== "null" && sc !== "") {
            var o = new Option(sc + ' - ' + sd, sc, true, true);
            $('#m_snomed_code').append(o).trigger('change');
            $('#m_snomed_display').val(sd);
            $('#snomed_ext_link').attr('href', 'https://browser.ihtsdotools.org/?perspective=full&conceptId1=' + sc).show();
        } else {
            $('#m_snomed_display').val('');
        }
        $('#modalMap').modal('show');
    });

    // Save Edit
    $('#btnSimpanMap').click(function() {
        var btn = $(this), orig = btn.html();
        btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);
        var fd = $('#formMap').serialize() + '&csrf_token=' + encodeURIComponent(CSRF_TOKEN);
        $.post('ajax.php?action=save_mapping', fd, function(res) {
            btn.html(orig).prop('disabled', false);
            if (res.status === 'success') {
                $('#modalMap').modal('hide');
                Swal.fire({icon: 'success', title: 'Tersimpan', showConfirmButton: false, timer: 1000});
                reloadTable();
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        }, 'json').fail(function() {
            btn.html(orig).prop('disabled', false);
            Swal.fire('Error', 'Gagal menghubungi server.', 'error');
        });
    });
});
</script>
</body>
</html>