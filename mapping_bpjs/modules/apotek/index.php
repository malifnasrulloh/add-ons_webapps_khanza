<?php
/**
 * modules/apotek/index.php — Halaman Mapping Obat ke BPJS Apotek (K-Farmasi)
 * Mengikuti pola interaksi modules/obat/index.php pada mapping_satu_sehat
 * (modal mapping, Select2 AJAX referensi BPJS, server-side paging).
 */
require_once '../../conf.php';
require_once '../../auth_check.php';
check_module_access('bpjs_mapping_obat_apotek'); // RBAC Guard
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mapping Obat — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?></title>
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
        .navbar-brand { font-weight: 700; }
        .select2-container { z-index: 9999; }
        .nav-back { color: #6366f1; font-weight: 500; text-decoration: none; font-size: .875rem; }
        .nav-back:hover { color: #4338ca; }
        .page-header { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; border-radius: 16px; padding: 1.5rem 2rem; margin-bottom: 1.5rem; }
        .page-header h4 { font-weight: 700; margin: 0; }
        .page-header p { margin: 0.25rem 0 0; opacity: 0.8; font-size: .875rem; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .badge-mapped { background-color: #dcfce7; color: #166534; font-weight: 500; }
        .badge-unmapped { background-color: #fee2e2; color: #991b1b; font-weight: 500; }
        .footer-credit { text-align: center; padding: 1.5rem; font-size: .72rem; color: #94a3b8; cursor: pointer; transition: all 0.2s; }
        .footer-credit:hover { color: #6366f1; background: rgba(99, 102, 241, 0.05); }
        .footer-credit a { color: #6d28d9; text-decoration: none; font-weight: 600; }
        .footer-credit a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white border-bottom px-3 py-2 mb-4">
    <a class="nav-back" href="../../index.php">
        <i class="fa fa-arrow-left me-2"></i>Dashboard
    </a>
    <span class="navbar-brand text-primary mb-0" style="font-size:1rem">
        <i class="fa-solid fa-pills me-2"></i> Mapping Obat — <?= htmlspecialchars($APP_INSTANSI, ENT_QUOTES, 'UTF-8') ?>
    </span>
    <div>
        <span class="text-muted small me-3"><i class="fa fa-user me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
        <a href="../../logout.php" class="text-danger small text-decoration-none">
            <i class="fa fa-right-from-bracket"></i> Logout
        </a>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4><i class="fa-solid fa-pills me-2"></i> Mapping Obat ke BPJS Apotek (K-Farmasi)</h4>
            <p>Klik tombol Mapping pada baris obat untuk menetapkan kode obat BPJS.</p>
        </div>
    </div>

    <!-- Panel Pencarian Server-Side -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <label class="form-label fw-semibold text-primary small">Cari Nama Obat (Server)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" id="keyword_obat" class="form-control"
                               placeholder="Ketik nama obat lalu tekan Enter atau klik Tampilkan...">
                        <button class="btn btn-primary px-4" id="btnCariServer">
                            <i class="fa fa-filter me-1"></i> Tampilkan
                        </button>
                    </div>
                    <div class="form-text">Kosongkan untuk menampilkan <strong>semua data</strong> (server-side paging). Gunakan filter DataTables untuk menyaring di halaman ini.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelObat" class="table table-striped table-hover table-bordered w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="10%">Kode RS</th>
                            <th width="30%">Nama Obat (RS)</th>
                            <th width="40%">Detail Mapping (Kode &amp; Nama BPJS)</th>
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

<!-- Modal Mapping Obat -->
<div class="modal fade" id="modalMap" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-edit me-2"></i>Form Mapping Obat — BPJS Apotek</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="m_kode_brng">
                <div class="alert alert-light border d-flex align-items-center mb-3">
                    <i class="fa fa-capsules fa-2x me-3 text-warning"></i>
                    <div>
                        <div class="fw-bold fs-5" id="m_nama_brng_label">Nama Obat</div>
                        <small class="text-muted" id="m_kode_brng_label">Kode RS</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-primary">1. Kode Obat BPJS (Referensi)</label>
                    <div class="input-group">
                        <select class="form-select" id="select_bpjs" style="width:85%"></select>
                        <?php if (!empty($_SESSION['is_admin'])): ?>
                        <button type="button" id="btnLoadDpho" class="btn btn-outline-info" title="Muat &amp; cari DPHO BPJS (Super Admin)">
                            <i class="fa fa-list-check me-1"></i>DPHO
                        </button>
                        <?php endif; ?>
                    </div>
                    <!-- Badge sumber data -->
                    <div class="mt-1 d-flex align-items-center gap-2 flex-wrap">
                        <span id="bpjs_source_badge" class="badge bg-secondary" style="font-size:.7rem;">
                            <i class="fa fa-database me-1"></i>Sumber: Database Lokal
                        </span>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label fw-bold">2. Kode Obat BPJS</label>
                        <input type="text" class="form-control" id="m_kode_bpjs" maxlength="20" placeholder="Contoh: 14250804054">
                        <div class="form-text small">Terisi otomatis dari referensi. Boleh diketik manual jika tidak ditemukan.</div>
                    </div>
                    <div class="col-md-7 mb-3">
                        <label class="form-label fw-bold">3. Nama Obat BPJS</label>
                        <input type="text" class="form-control" id="m_nama_bpjs" placeholder="Contoh: Alprazolam 1 SK tab 1 mg">
                    </div>
                </div>

                <!-- Detail DPHO BPJS (Read-Only) -->
                <div id="dpho_detail_block" class="border rounded-3 p-3 bg-light d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <label class="form-label fw-bold text-primary m-0" style="font-size:.9rem;">
                            <i class="fa-solid fa-circle-info me-1"></i>Detail DPHO BPJS (Read-Only)
                        </label>
                        <span class="badge bg-info text-dark" style="font-size:.65rem;">
                            <i class="fa fa-cloud me-1"></i>Sumber: Data DPHO
                        </span>
                    </div>
                    <div class="row g-2 small" id="dpho_detail_grid"></div>
                    <div class="form-text mt-2" style="font-size:.72rem;">Data ini hanya referensi dari BPJS dan tidak ikut tersimpan ke mapping.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSimpanObat">
                    <i class="fa fa-save me-1"></i> Simpan Mapping
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Referensi BPJS / DPHO -->
<div class="modal fade" id="modalBpjsRef" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-notes-medical me-2"></i>Referensi Obat BPJS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-2 border-bottom bg-white">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" id="dphoSearchKey" class="form-control" placeholder="Cari kode / nama / restriksi DPHO...">
                        <button class="btn btn-outline-primary" id="btnDphoSearch" type="button"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </div>
                    <div class="form-text" style="font-size:.7rem;" id="dphoListInfo">Data dari cache lokal (sinkronisasi terakhir sesuai tombol Muat DPHO).</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover m-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode Obat</th>
                                <th>Nama Obat</th>
                                <th>Harga / Restriksi</th>
                                <th class="text-end">Pilih</th>
                            </tr>
                        </thead>
                        <tbody id="bpjsRefBody"></tbody>
                    </table>
                </div>
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
                    Halo rekan-rekan IT dan Super Admin. Terima kasih telah menggunakan aplikasi pemetaan BPJS Apotek ini.<br><br>
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
    // 1. Init DataTables — Server-Side Pagination
    var table = $('#tabelObat').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "ajax.php?action=load_table",
            "data": function(d) {
                d.keyword = $('#keyword_obat').val();
            }
        },
        "dom": "<'row mb-3'<'col-md-2'l><'col-md-6 text-center'B><'col-md-4'f>>" +
               "<'row'<'col-md-12'tr>>" +
               "<'row'<'col-md-5'i><'col-md-7'p>>",
        "buttons": [
            { extend: 'excelHtml5', text: '<i class="fa fa-file-excel"></i> Export Excel', className: 'btn btn-success btn-sm' }
        ],
        "columns": [
            { data: 'kode_brng' },
            { data: 'nama_brng' },
            { data: 'info' },
            { data: 'status', className: "text-center" },
            { data: 'btn', className: "text-center", orderable: false }
        ],
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
        "language": {
            "search": "Filter di halaman ini:",
            "processing": "<i class='fa fa-spinner fa-spin'></i> Memuat...",
            "zeroRecords": "Tidak ada data yang cocok",
            "info": "Menampilkan _START_-_END_ dari _TOTAL_ data",
            "infoEmpty": "Tidak ada data",
            "infoFiltered": "(difilter dari _MAX_ total)",
            "lengthMenu": "Tampilkan _MENU_ baris"
        }
    });

    // 2. Event tombol cari server
    $('#btnCariServer').click(function() { table.ajax.reload(); });
    $('#keyword_obat').on('keyup', function(e) {
        if (e.key === 'Enter') table.ajax.reload();
    });

    // 3. Select2 Referensi BPJS (AJAX Search) — dengan badge sumber data
    var bpjsLastSource = 'database';
    var dphoListCache = {}; // cache baris DPHO saat modal daftar terbuka

    // Helper: update badge sesuai state
    function bpjsSetBadge(state) {
        var badge = $('#bpjs_source_badge');
        badge.removeClass('bg-secondary bg-success bg-warning bg-info text-dark');
        switch (state) {
            case 'loading':
                badge.addClass('bg-info')
                    .html('<i class="fa fa-spinner fa-spin me-1"></i>Menghubungi API BPJS Kesehatan...');
                break;
            case 'api':
                badge.addClass('bg-success')
                    .html('<i class="fa fa-cloud me-1"></i>Sumber: API BPJS Kesehatan');
                break;
            case 'fallback':
                badge.addClass('bg-warning text-dark')
                    .html('<i class="fa fa-triangle-exclamation me-1"></i>API BPJS gagal &mdash; menggunakan Database Lokal');
                break;
            default: // 'database'
                badge.addClass('bg-secondary')
                    .html('<i class="fa fa-database me-1"></i>Sumber: Database Lokal');
        }
    }

    $('#select_bpjs').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalMap'),
        placeholder: 'Ketik kode atau nama obat BPJS...',
        minimumInputLength: 2,
        ajax: {
            url: 'ajax.php?action=select_bpjs',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return { term: params.term, page: params.page || 1 };
            },
            beforeSend: function() {
                bpjsSetBadge('loading');
            },
            processResults: function(data, params) {
                bpjsLastSource = data.source || 'database';
                bpjsSetBadge(bpjsLastSource);
                params.page = params.page || 1;
                return {
                    results: data.results,
                    pagination: { more: data.pagination ? data.pagination.more : false }
                };
            },
            error: function() {
                bpjsSetBadge('fallback');
            },
            cache: false
        }
    }).on('select2:select', function(e) {
        var d = e.params.data;
        $('#m_kode_bpjs').val(d.kode);
        $('#m_nama_bpjs').val(d.nama);
        fetchDphoDetail(d.kode);
    });

    // 3b. Detail DPHO (read-only)
    function renderDphoDetail(row) {
        if (!row) { hideDphoDetail(); return; }
        var d = {
            kodeobat: row.kodeobat || row.kode || '',
            namaobat: row.namaobat || row.nama || '',
            prb:      !!row.prb,
            kronis:   !!row.kronis,
            kemo:     !!row.kemo,
            harga:    row.harga,
            sedia:    row.sedia || '',
            stok:     row.stok || '',
            restriksi: row.restriksi || '',
            generik:  row.generik || '',
            aktif:    row.aktif || ''
        };
        if (!d.kodeobat) { hideDphoDetail(); return; }
        var boolBadge = function(v) {
            return v
                ? '<span class="badge bg-success"><i class="fa fa-check me-1"></i>Ya</span>'
                : '<span class="badge bg-secondary"><i class="fa fa-xmark me-1"></i>Tidak</span>';
        };
        var fmtRp = function(v) {
            return v ? 'Rp ' + Number(v).toLocaleString('id-ID') : '-';
        };
        var grid = [
            '<div class="col-md-6"><span class="text-muted">Kode Obat</span><div class="fw-semibold">' + $('<span>').text(d.kodeobat).html() + '</div></div>',
            '<div class="col-md-6"><span class="text-muted">Nama Obat</span><div class="fw-semibold">' + $('<span>').text(d.namaobat).html() + '</div></div>',
            '<div class="col-md-6"><span class="text-muted">PRB</span><div>' + boolBadge(d.prb) + '</div></div>',
            '<div class="col-md-6"><span class="text-muted">Kronis</span><div>' + boolBadge(d.kronis) + '</div></div>',
            '<div class="col-md-6"><span class="text-muted">Kemo</span><div>' + boolBadge(d.kemo) + '</div></div>',
            '<div class="col-md-6"><span class="text-muted">Harga</span><div class="fw-semibold">' + fmtRp(d.harga) + '</div></div>',
            '<div class="col-md-6"><span class="text-muted">Sedia</span><div>' + $('<span>').text(d.sedia || '-').html() + '</div></div>',
            '<div class="col-md-6"><span class="text-muted">Stok</span><div>' + $('<span>').text(d.stok || '-').html() + '</div></div>'
        ];
        var extra = [];
        if (d.restriksi) extra.push('Restriksi: ' + d.restriksi);
        if (d.generik)   extra.push('Generik: ' + d.generik);
        if (d.aktif)     extra.push('Aktif: ' + d.aktif);
        if (extra.length) {
            grid.push('<div class="col-12"><span class="text-muted">Info Lain</span><div class="text-dark small">' + $('<span>').text(extra.join(' &middot; ')).html() + '</div></div>');
        }
        $('#dpho_detail_grid').html(grid.join(''));
        $('#dpho_detail_block').removeClass('d-none');
    }

    function hideDphoDetail() {
        $('#dpho_detail_block').addClass('d-none');
        $('#dpho_detail_grid').html('');
    }

    function fetchDphoDetail(kode) {
        if (!kode) { hideDphoDetail(); return; }
        $.getJSON('ajax.php?action=dpho_detail&kode=' + encodeURIComponent(kode), function(res) {
            if (res.status === 'success' && res.data) {
                renderDphoDetail(res.data);
            } else {
                hideDphoDetail();
            }
        }).fail(function() { hideDphoDetail(); });
    }

    // Ketik manual kode → coba cari detail di cache DPHO
    $('#m_kode_bpjs').on('change', function() {
        fetchDphoDetail($(this).val().trim());
    });

    // 4. Buka Modal Mapping
    $('#tabelObat tbody').on('click', '.btn-map', function() {
        var data = table.row($(this).data('idx')).data();
        if (!data) return;
        $('#m_kode_brng').val(data.kode_brng);
        $('#m_nama_brng_label').text(data.nama_brng);
        $('#m_kode_brng_label').text(data.kode_brng);

        // Reset badge sumber & referensi
        bpjsLastSource = 'database';
        bpjsSetBadge('database');

        $('#select_bpjs').val(null).trigger('change');
        if (data.kode_brng_apotek_bpjs) {
            var opt = new Option(data.kode_brng_apotek_bpjs + ' - ' + data.nama_brng_apotek_bpjs, data.kode_brng_apotek_bpjs, true, true);
            $('#select_bpjs').append(opt).trigger('change');
            $('#m_kode_bpjs').val(data.kode_brng_apotek_bpjs);
            $('#m_nama_bpjs').val(data.nama_brng_apotek_bpjs);
            fetchDphoDetail(data.kode_brng_apotek_bpjs);
        } else {
            $('#m_kode_bpjs').val('');
            $('#m_nama_bpjs').val('');
            hideDphoDetail();
        }
        var modal = new bootstrap.Modal(document.getElementById('modalMap'));
        modal.show();
    });

    // 5. Hapus Mapping (konfirmasi)
    $('#tabelObat tbody').on('click', '.btn-delete', function() {
        var data = table.row($(this).data('idx')).data();
        if (!data) return;
        var kode = data.kode_brng;
        Swal.fire({
            title: 'Hapus Mapping?',
            text: 'Data mapping obat ' + data.nama_brng + ' akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.post('ajax.php?action=delete_mapping', { csrf_token: CSRF_TOKEN, kode_brng: kode }, function(resp) {
                if (resp.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Dihapus', text: resp.message, timer: 1500, showConfirmButton: false });
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire('Gagal', resp.message, 'error');
                }
            }, 'json').fail(function(xhr) {
                Swal.fire('Error!', 'Koneksi server gagal.', 'error');
            });
        });
    });

    // 6. Tombol DPHO — sinkronkan cache DPHO lalu tampilkan daftar
    $('#btnLoadDpho').click(function() {
        var btn = $(this);
        btn.html('<i class="fa fa-spinner fa-spin me-1"></i>Memuat DPHO...').prop('disabled', true);
        $.post('ajax.php?action=sync_dpho', { csrf_token: CSRF_TOKEN }, function(res) {
            btn.html('<i class="fa fa-list-check me-1"></i>DPHO').prop('disabled', false);
            if (res.status === 'success') {
                loadDphoList('');
            } else {
                Swal.fire('Informasi', res.message, 'info');
            }
        }, 'json').fail(function() {
            btn.html('<i class="fa fa-list-check me-1"></i>DPHO').prop('disabled', false);
            Swal.fire('Gagal', 'Tidak dapat terhubung ke server BPJS.', 'error');
        });
    });

    function loadDphoList(keyword) {
        dphoListCache = {};
        $.getJSON('ajax.php?action=search_bpjs_dpho&keyword=' + encodeURIComponent(keyword), function(res) {
            if (res.status === 'success') {
                renderBpjsModal(res.data || []);
                $('#dphoListInfo').text('Cache DPHO lokal — ' + (res.data || []).length + ' data ditampilkan.');
            } else {
                Swal.fire('Informasi', res.message, 'info');
            }
        }).fail(function() {
            Swal.fire('Gagal', 'Tidak dapat mengambil daftar DPHO.', 'error');
        });
    }

    $('#btnDphoSearch').click(function() {
        loadDphoList($('#dphoSearchKey').val().trim());
    });
    $('#dphoSearchKey').on('keyup', function(e) {
        if (e.key === 'Enter') loadDphoList($(this).val().trim());
    });

    function renderBpjsModal(list) {
        if (!Array.isArray(list) || list.length === 0) {
            $('#bpjsRefBody').html('<tr><td colspan="4" class="text-center text-muted py-4">Tidak ada data referensi ditemukan.</td></tr>');
        } else {
            var html = '';
            list.forEach(function(item) {
                var kode = item.kode || item.kodeobat || '-';
                var nama = item.nama || item.namaobat || '-';
                var harga = item.harga ? 'Rp ' + Number(item.harga).toLocaleString('id-ID') : '-';
                var restriksi = item.restriksi ? ' | ' + item.restriksi : '';
                var tags = [];
                if (item.prb)    tags.push('<span class="badge bg-primary" style="font-size:.6rem;">PRB</span>');
                if (item.kronis) tags.push('<span class="badge bg-warning text-dark" style="font-size:.6rem;">Kronis</span>');
                if (item.kemo)   tags.push('<span class="badge bg-danger" style="font-size:.6rem;">Kemo</span>');
                html += '<tr>' +
                    '<td><code>' + $('<span>').text(kode).html() + '</code></td>' +
                    '<td>' + $('<span>').text(nama).html() + ' ' + tags.join(' ') + '</td>' +
                    '<td><small>' + $('<span>').text(harga + restriksi).html() + '</small></td>' +
                    '<td class="text-end"><button class="btn btn-sm btn-primary btn-pick-bpjs" data-kode="' + $('<span>').text(kode).html() + '"><i class="fa-solid fa-check"></i></button></td>' +
                    '</tr>';
                if (kode !== '-') dphoListCache[kode] = item;
            });
            $('#bpjsRefBody').html(html);
        }
        new bootstrap.Modal('#modalBpjsRef').show();
    }

    // Pilih dari daftar DPHO → isi referensi + detail pada modal mapping
    $('#bpjsRefBody').on('click', '.btn-pick-bpjs', function() {
        var kode = $(this).data('kode');
        var item = dphoListCache[kode] || {};
        var nama = item.nama || item.namaobat || '';
        $('#m_kode_bpjs').val(kode);
        $('#m_nama_bpjs').val(nama);
        var opt = new Option(kode + ' - ' + nama, kode, true, true);
        $('#select_bpjs').append(opt).trigger('change');
        bpjsSetBadge('api');
        renderDphoDetail(item);
        bootstrap.Modal.getInstance(document.getElementById('modalBpjsRef')).hide();
    });

    // 7. Simpan mapping via AJAX (dengan CSRF)
    $('#btnSimpanObat').click(function() {
        var btn = $(this);
        var origHtml = btn.html();
        var kode = $('#m_kode_bpjs').val().trim();
        var nama = $('#m_nama_bpjs').val().trim();
        var kodeRs = $('#m_kode_brng').val();

        if (!kodeRs) {
            Swal.fire('Peringatan', 'Pilih dulu obat RS dari tabel daftar obat.', 'warning');
            return;
        }
        if (!kode) {
            Swal.fire('Peringatan', 'Kode Obat BPJS wajib diisi.', 'warning');
            return;
        }
        if (!/^\d+$/.test(kode)) {
            Swal.fire('Peringatan', 'Kode Obat BPJS harus berupa angka.', 'warning');
            return;
        }

        btn.html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

        $.post('ajax.php?action=save_mapping', {
            csrf_token: CSRF_TOKEN,
            kode_brng: kodeRs,
            kode_brng_apotek_bpjs: kode,
            nama_brng_apotek_bpjs: nama
        }, function(resp) {
            btn.html(origHtml).prop('disabled', false);
            if (resp.status === 'success') {
                btn.html('<i class="fa fa-check"></i> Tersimpan!').addClass('btn-success').removeClass('btn-primary');
                setTimeout(function() {
                    btn.html(origHtml).removeClass('btn-success').addClass('btn-primary');
                    bootstrap.Modal.getInstance(document.getElementById('modalMap')).hide();
                    table.ajax.reload(null, false);
                }, 1500);
            } else {
                Swal.fire('Gagal!', resp.message, 'error');
            }
        }, 'json').fail(function() {
            btn.html(origHtml).prop('disabled', false);
            Swal.fire('Error!', 'Koneksi server gagal.', 'error');
        });
    });

    // Anti-Tampering
    setInterval(function() {
        var el = document.getElementById('footer-credit-block');
        if (!el) { document.body.innerHTML = ''; return; }
        var html = el.innerHTML;
        var cs = window.getComputedStyle(el);
        var checks = [atob('SWNoc2FuIExlb25oYXJ0'),atob('c2F3ZXJpYS5jby9pY2hzYW5sZW9uaGFydA=='),atob('NjI4NTcyNjEyMzc3Nw=='),atob('QEljaHNhbkxlb25oYXJ0'),atob('aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL2ljaHNhbmxlb25oYXJ0L2FkZC1vbnNfd2ViYXBwc19raGFuemEvbWFpbi9xcmlzLWljaHNhbi5wbmc=')];
        if (cs.display==='none'||cs.visibility==='hidden'||cs.opacity==='0') { document.body.innerHTML=''; return; }
        for(var i=0;i<checks.length;i++) { if(html.indexOf(checks[i])===-1) { document.body.innerHTML=''; return; } }
    }, 3000);
});
</script>
</body>
</html>
