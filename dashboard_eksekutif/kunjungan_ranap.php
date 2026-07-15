<?php
/*
 * File: kunjungan_ranap.php
 * Deskripsi: Monitoring Ranap Aktif & Audit Piutang Belum Closing.
 */
$page_title = "Billing Rawat Inap & Audit";
require_once('includes/header.php');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<style>
/* === SKELETON LOADER LAZY BILLING === */
.skeleton-text {
    display: inline-block;
    width: 90px;
    height: 14px;
    background: linear-gradient(90deg, #e0e0e0 25%, #f5f5f5 50%, #e0e0e0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
    border-radius: 4px;
    vertical-align: middle;
}
@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}
html.theme-glass-solid .skeleton-text,
html.theme-glass-animated .skeleton-text {
    background: linear-gradient(90deg, rgba(255,255,255,0.1) 25%, rgba(255,255,255,0.25) 50%, rgba(255,255,255,0.1) 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
}
</style>

<div class="container-fluid">
    
    <div class="card shadow-sm mb-4 border-start border-4 border-info">
        <div class="card-body py-3">
            <form id="formFilter">
                <div class="row align-items-end g-2">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Dari Tanggal Masuk</label>
                        <input type="date" id="tgl_awal" class="form-control form-control-sm" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Sampai Tanggal</label>
                        <input type="date" id="tgl_akhir" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" id="chk_audit">
                            <label class="form-check-label small" for="chk_audit">
                                <strong>Mode Audit</strong> (Termasuk Pasien Sudah Pulang)
                            </label>
                        </div>
                        <small class="text-muted" style="font-size: 0.7rem;">
                            Jika dicentang, menampilkan pasien pulang yang belum lunas sesuai periode tanggal masuk.
                        </small>
                    </div>
                    <div class="col-md-2">
                        <button type="button" onclick="reloadTable()" class="btn btn-sm btn-primary w-100 fw-bold">
                            <i class="fas fa-filter me-1"></i> Terapkan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Pasien & Estimasi Biaya</h6>
            <div>
                <button onclick="reloadTable()" class="btn btn-sm btn-light border"><i class="fas fa-sync-alt text-gray-500"></i></button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle table-sm" id="tableKunjungan" width="100%" cellspacing="0">
                    <thead class="table-dark">
                        <tr>
                            <th width="10%">Tgl Masuk</th>
                            <th width="15%">No. Rawat / Pasien</th>
                            <th width="15%">DPJP / Dokter</th>
                            <th width="12%">Kamar / Penjamin</th>
                            <th width="15%">Diagnosa</th>
                            <th width="4%" class="text-center">Lab</th>
                            <th width="4%" class="text-center">Rad</th>
                            <th width="6%" class="text-center">Berkas</th>
                            <th width="8%" class="text-end bg-secondary">Plafon</th>
                            <th width="8%" class="text-end bg-warning text-dark">Est. Biaya</th>
                            <th width="8%" class="text-end">Selisih</th>
                            <th width="5%" class="text-center">Status</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetailBilling" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2"></i>Rincian Billing</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between bg-light p-2 mb-2 rounded border">
                    <div><strong>Pasien:</strong> <span id="lbl-pasien">-</span></div>
                    <div><strong>No. Rawat:</strong> <span id="lbl-norawat">-</span></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover" style="font-size: 0.85rem;">
                        <thead class="table-dark text-center">
                            <tr>
                                <th width="20%">Kategori / Keterangan</th>
                                <th width="25%">Tagihan / Tindakan</th>
                                <th width="12%">Biaya</th>
                                <th width="5%">Jml</th>
                                <th width="12%">Tambahan</th>
                                <th width="15%">Total Biaya</th>
                            </tr>
                        </thead>
                        <tbody id="bodyDetailBilling"></tbody>
                        <tfoot class="table-light fw-bold fs-5">
                            <tr>
                                <td colspan="5" class="text-end">TOTAL TAGIHAN:</td>
                                <td class="text-end text-primary" id="lbl-total">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>


<script>
    var tableKunjungan;

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    }

    $(document).ready(function() {
        // Logika UX: Disable tanggal jika tidak dalam mode audit (karena defaultnya Active Only)
        $('#chk_audit').change(function() {
            if($(this).is(':checked')) {
                $('#tgl_awal, #tgl_akhir').prop('disabled', false).removeClass('bg-light');
            } else {
                $('#tgl_awal, #tgl_akhir').prop('disabled', true).addClass('bg-light');
            }
        }).trigger('change'); // Trigger on load

        tableKunjungan = $('#tableKunjungan').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "api/data_kunjungan_ranap.php",
                "type": "GET",
                "global": false, // Hilangkan loading pop-up agar nyaman saat mengetik/search
                "data": function(d) {
                    // KIRIM PARAMETER FILTER
                    d.mode = $('#chk_audit').is(':checked') ? 'audit' : 'active';
                    d.tgl_awal = $('#tgl_awal').val();
                    d.tgl_akhir = $('#tgl_akhir').val();
                }
            },
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
            "dom": 'Bfrtip',
            "buttons": [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export Excel (pilih dulu [Show all rows])',
                    className: 'btn btn-success btn-sm mb-3',
                    title: 'Laporan Billing Rawat Inap',
                    exportOptions: {
                        columns: ':visible:not(:last-child)', // Jangan export kolom Aksi
                        format: {
                            body: function(data, row, column, node) {
                                // 1. KOLOM RUPIAH (Plafon, Estimasi, Selisih)
                                if (column === 4 || column === 5 || column === 6) {
                                     var cellText = node ? node.textContent.trim() : '';
                                     if (!cellText || cellText.includes('Memuat') || cellText.includes('2026')) {
                                         return 0;
                                     }
                                     
                                     // Tentukan apakah nilainya negatif/over (ditandai dengan parenthese/minus/over)
                                     var isNegative = cellText.includes('(') || cellText.includes('-') || cellText.toLowerCase().includes('over');
                                     var digits = cellText.replace(/\D/g, '');
                                     if (!digits) return 0;
                                     
                                     return isNegative ? -parseInt(digits, 10) : parseInt(digits, 10);
                                }
                                
                                // 2. KOLOM TEKS (Pasien, Dokter, Kamar, Penjamin, Status)
                                var str = (data === null || data === undefined) ? '' : String(data);
                                if (str.indexOf('<') > -1) {
                                    // Ganti <br> dengan " - "
                                    // Hapus HTML tag
                                    return str.replace(/<br\s*\/?>/gi, " - ").replace(/<[^>]+>/g, "").trim();
                                }

                                return data;
                            }
                        }
                    }
                },
                {
                    extend: 'pageLength',
                    className: 'btn btn-secondary btn-sm mb-3'
                }
            ],
            "order": [],
            "createdRow": function(row, data, dataIndex) {
                // Highlight jika Over Plafon
                if (data.is_over === true) {
                    $(row).addClass('table-danger');
                }
                // Highlight beda warna jika pasien sudah pulang (tapi belum bayar)
                if (data.status_pulang !== '-' && data.status_pulang !== 'Masih Dirawat') {
                    $(row).addClass('table-warning');
                }
            },
            "columns": [
                { "data": "waktu" },
                { 
                    "data": null,
                    "render": function(data) {
                        let lama = data.lama_rawat ? `<span class="badge bg-info text-dark ms-1">${data.lama_rawat}</span>` : '';
                        return `<b>${data.no_rawat}</b> ${lama}<br>${data.pasien} <br><small class="text-muted">RM: ${data.rm}</small>`;
                    }
                },
                { 
                    "data": "dpjp",
                    "render": function(data, type, row) {
                        if (data === null || data === '') return `<span class="skeleton-cell" data-norawat="${row.no_rawat}" data-col="dpjp"><span class="skeleton-text" style="width:100px"></span></span>`;
                        let html = `<b>${data}</b>`;
                        if (row.is_dpjp_fallback) html += `<br><small class="badge bg-warning text-dark" style="font-size:0.7em;">DPJP -</small>`;
                        return html;
                    }
                },
                { 
                    "data": null,
                    "render": function(data) {
                        let penjamin = data.penjamin.toLowerCase();
                        let badgeClass = 'bg-secondary';
                        let badgeStyle = '';
                        if (penjamin.includes('bpjs')) { badgeClass = 'bg-success'; } 
                        else if (penjamin.includes('umum')) { badgeClass = 'bg-primary'; } 
                        else if (penjamin.includes('asuransi') || penjamin.includes('inhealth')) { 
                            badgeClass = ''; badgeStyle = 'background-color: #e83e8c; color: white;'; 
                        }
                        return `${data.kamar}<br><span class="badge ${badgeClass}" style="${badgeStyle} border: 1px solid #ddd;">${data.penjamin}</span>`;
                    }
                },
                {
                    "data": "diagnosa_awal",
                    "orderable": false,
                    "render": function(data, type, row) { 
                        let h = '';
                        if(data && data !== '-') h += `<b>Awal:</b> ${data}<br>`;
                        if(row.diagnosa_akhir && row.diagnosa_akhir !== '-') h += `<span class="text-success"><b>Akhir:</b> ${row.diagnosa_akhir}</span>`;
                        return h || '-'; 
                    }
                },
                { 
                    "data": null, "className": "text-center", "orderable": false,
                    "render": function(data, type, row) {
                        if (row.count_lab > 0) return `<button class="btn btn-outline-info btn-sm btn-counter btn-modal" data-type="lab" data-id="${row.no_rawat}"><i class="fas fa-flask"></i><span class="badge bg-danger badge-counter" style="font-size:0.6rem;">${row.count_lab}</span></button>`;
                        return '<small class="text-muted">-</small>';
                    }
                },
                { 
                    "data": null, "className": "text-center", "orderable": false,
                    "render": function(data, type, row) {
                        if (row.count_rad > 0) return `<button class="btn btn-outline-warning btn-sm btn-counter btn-modal" data-type="rad" data-id="${row.no_rawat}"><i class="fas fa-x-ray"></i><span class="badge bg-danger badge-counter" style="font-size:0.6rem;">${row.count_rad}</span></button>`;
                        return '<small class="text-muted">-</small>';
                    }
                },
                { 
                    "data": null, "className": "text-center", "orderable": false,
                    "render": function(data, type, row) {
                        let icons = '';
                        if (row.klaim_resume > 0) icons += '<i class="fas fa-file-medical text-success" title="Resume Medis" style="margin:2px; font-size:1.1rem;"></i>';
                        if (row.klaim_triase > 0) icons += '<i class="fas fa-truck-medical text-danger" title="Triase IGD" style="margin:2px; font-size:1.1rem;"></i>';
                        if (row.klaim_asesmen > 0) icons += '<i class="fas fa-stethoscope text-primary" title="Asesmen IGD" style="margin:2px; font-size:1.1rem;"></i>';
                        if (row.klaim_operasi > 0) icons += '<i class="fas fa-procedures text-warning" title="Laporan Operasi" style="margin:2px; font-size:1.1rem;"></i>';
                        
                        if (icons === '') return '<small class="text-muted">-</small>';
                        return `<button class="btn btn-light btn-sm border shadow-sm" onclick="bukaBerkasKlaim('${row.no_rawat}')" title="Buka Berkas Klaim">${icons}</button>`;
                    }
                },
                { 
                    "data": "plafon", 
                    "className": "text-end fw-bold",
                    "render": function(data, type, row) {
                        if (data === null) return `<span class="skeleton-cell" data-norawat="${row.no_rawat}" data-col="plafon"><span class="skeleton-text"></span></span>`;
                        return data;
                    }
                },
                { 
                    "data": "estimasi", 
                    "className": "text-end fw-bold text-primary",
                    "render": function(data, type, row) {
                        if (data === null) return `<span class="skeleton-cell" data-norawat="${row.no_rawat}" data-col="estimasi"><span class="skeleton-text"></span></span>`;
                        return data;
                    }
                },
                { 
                    "data": "selisih", 
                    "className": "text-end fw-bold",
                    "render": function(data, type, row) {
                        if (data === null) return `<span class="skeleton-cell" data-norawat="${row.no_rawat}" data-col="selisih"><span class="skeleton-text"></span></span>`;
                        if (!data || data === '-') return '-';
                        return (row.is_over) ? `<span class="text-danger">(${data})</span>` : `<span class="text-success">+${data}</span>`;
                    }
                },
                { 
                    "data": "status_pulang",
                    "className": "text-center",
                    "render": function(data) {
                        if(data === 'Masih Dirawat' || data === '-') {
                            return '<span class="badge bg-info text-dark">Aktif</span>';
                        }
                        return `<span class="badge bg-warning text-dark">${data}</span>`;
                    }
                },
                { 
                    "data": null, "className": "text-center", 
                    "render": function(data, type, row) {
                        return `<div class="btn-group btn-group-sm shadow-sm">
                                <button class="btn btn-primary" onclick="showDetailBilling('${row.no_rawat}', '${row.pasien.replace(/'/g, "\\'")}')" title="Rincian Billing"><i class="fas fa-file-invoice-dollar"></i></button>
                                <button class="btn btn-secondary btn-modal" data-type="riwayat" data-id="${row.no_rawat}" title="Riwayat Lengkap Pasien & AI Casemix"><i class="fas fa-stream"></i></button>
                                </div>`;
                    }
                }
            ],
            "drawCallback": function() {
                loadBillingAsync();
            }
        });
    });

    function reloadTable() { tableKunjungan.ajax.reload(); }

    // =====================================================
    // LAZY BILLING LOADER — Fetch biaya async per baris (Ranap)
    // =====================================================
    var _billingQueue = [];
    var _billingRunning = 0;
    var _billingConcurrency = 3;

    function loadBillingAsync() {
        var cells = document.querySelectorAll('.skeleton-cell');
        _billingQueue = [];
        cells.forEach(function(el) {
            var noRawat = el.getAttribute('data-norawat');
            if (!_billingQueue.some(function(i){ return i.no_rawat === noRawat; })) {
                // Ambil kd_pj dari data baris di DataTable
                var rowData = tableKunjungan.rows().data().toArray().find(function(r){ return r.no_rawat === noRawat; });
                _billingQueue.push({ no_rawat: noRawat, kd_pj: rowData ? (rowData.kd_pj || '-') : '-' });
            }
        });
        _processBillingQueue();
    }

    function _processBillingQueue() {
        while (_billingRunning < _billingConcurrency && _billingQueue.length > 0) {
            var item = _billingQueue.shift();
            _billingRunning++;
            _fetchOneBilling(item);
        }
    }

    function _fetchOneBilling(item) {
        $.ajax({
            url: 'api/hitung_estimasi_ranap.php',
            type: 'GET',
            global: false,  // Jangan trigger globalLoadingOverlay
            data: { no_rawat: item.no_rawat, kd_pj: item.kd_pj },
            dataType: 'json',
            success: function(res) {
                var nr = res.no_rawat;
                // Estimasi
                document.querySelectorAll('.skeleton-cell[data-norawat="' + nr + '"][data-col="estimasi"]').forEach(function(el) {
                    el.outerHTML = '<span class="fw-bold text-primary">Rp ' + (res.estimasi || '0') + '</span>';
                });
                // Plafon
                document.querySelectorAll('.skeleton-cell[data-norawat="' + nr + '"][data-col="plafon"]').forEach(function(el) {
                    el.outerHTML = res.plafon || '-';
                });
                // Selisih + Progress Bar
                document.querySelectorAll('.skeleton-cell[data-norawat="' + nr + '"][data-col="selisih"]').forEach(function(el) {
                    var html = '-';
                    if (res.has_plafon) {
                        var barColor = res.is_over ? 'bg-danger' : (res.pct >= 80 ? 'bg-warning' : 'bg-success');
                        var selLabel = res.is_over
                            ? '<span class="text-danger fw-bold">+' + res.selisih + ' (OVER)</span>'
                            : '<span class="text-success fw-bold">Sisa: ' + res.selisih + '</span>';
                        html = selLabel +
                               '<div class="progress mt-1" style="height:5px;" title="' + res.pct + '% dari plafon">' +
                               '  <div class="progress-bar ' + barColor + '" style="width:' + res.pct + '%;"></div>' +
                               '</div>' +
                               '<small class="text-muted" style="font-size:0.68rem;">' + res.pct + '% terpakai</small>';
                    }
                    el.outerHTML = html;
                });
                // DPJP
                document.querySelectorAll('.skeleton-cell[data-norawat="' + nr + '"][data-col="dpjp"]').forEach(function(el) {
                    var dpjpHtml = '<b>' + (res.dpjp || '-') + '</b>';
                    if (res.is_dpjp_fallback) dpjpHtml += '<br><small class="badge bg-warning text-dark" style="font-size:0.7em;">DPJP -</small>';
                    el.outerHTML = dpjpHtml;
                });
            },
            error: function() {
                document.querySelectorAll('.skeleton-cell[data-norawat="' + item.no_rawat + '"]').forEach(function(el) {
                    el.outerHTML = '<span class="text-muted">-</span>';
                });
            },
            complete: function() {
                _billingRunning--;
                _processBillingQueue();
            }
        });
    }

    function showDetailBilling(noRawat, namaPasien) {
        $('#lbl-pasien').text(namaPasien);
        $('#lbl-norawat').text(noRawat);
        $('#bodyDetailBilling').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div><br>Menghitung ulang rincian biaya...</td></tr>');
        $('#lbl-total').text('...');
        $('#modalDetailBilling').modal('show');

        $.ajax({
            url: 'api/data_rincian_billing.php',
            type: 'GET',
            data: { no_rawat: noRawat },
            dataType: 'json',
            success: function(res) {
                var html = '';
                if (res.data && res.data.length > 0) {
                    res.data.forEach(function(item) {
                        if (item.is_header) {
                            html += `<tr class="table-secondary fw-bold"><td colspan="6">${item.keterangan} ${item.tagihan}</td></tr>`;
                        } else {
                            var style = (item.total < 0) ? 'text-danger fw-bold' : '';
                            html += `<tr>
                                        <td>${item.keterangan}</td>
                                        <td>${item.tagihan}</td>
                                        <td class="text-end">${formatRupiah(item.biaya)}</td>
                                        <td class="text-center">${item.jumlah}</td>
                                        <td class="text-end">${formatRupiah(item.tambahan)}</td>
                                        <td class="text-end fw-bold ${style}">${formatRupiah(item.total)}</td>
                                     </tr>`;
                        }
                    });
                } else {
                    html = '<tr><td colspan="6" class="text-center">Tidak ada data tagihan.</td></tr>';
                }
                $('#bodyDetailBilling').html(html);
                $('#lbl-total').text(res.total_rupiah);
            }
        });
    }

</script>

<div class="modal fade" id="modalUniversal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title fw-bold" id="modalTitle">Riwayat Pasien & AI Cost Auditor</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="modalContent"></div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tableKunjungan tbody').on('click', '.btn-modal', function() {
        var type = $(this).data('type');
        var no_rawat = $(this).data('id');
        var url = 'api/riwayat/view_' + type + '.php';
        var title = '';

        if(type === 'riwayat') title = 'Riwayat Lengkap Pasien & AI Casemix';
        else if(type === 'lab') title = 'Hasil Laboratorium';
        else if(type === 'rad') title = 'Hasil Radiologi';
        
        if (type === 'riwayat') {
            $('#modalUniversal .modal-dialog').removeClass('modal-xl').addClass('modal-fullscreen');
        } else {
            $('#modalUniversal .modal-dialog').removeClass('modal-fullscreen').addClass('modal-xl');
        }

        $('#modalTitle').html(title);
        $('#modalContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#modalUniversal').modal('show');
        
        var ajaxData = { no_rawat: no_rawat };
        if(type === 'riwayat') ajaxData.ai_mode = 'mpp';

        $.ajax({
            url: url, method: 'POST', data: ajaxData,
            success: function(res) { $('#modalContent').html(res); },
            error: function() { $('#modalContent').html('<div class="p-3 text-danger">Gagal memuat data</div>'); }
        });
    });
});

function bukaBerkasKlaim(no_rawat) {
    $('#isiModalBerkas').html('<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Memuat berkas...</div>');
    $('#modalBerkas').modal('show');
    
    fetch('api/riwayat/modal_klaim_viewer.php?no_rawat=' + encodeURIComponent(no_rawat))
        .then(res => res.text())
        .then(html => { $('#isiModalBerkas').html(html); })
        .catch(err => { $('#isiModalBerkas').html('<div class="alert alert-danger">Gagal memuat berkas: ' + err + '</div>'); });
}
</script>

<div class="modal fade" id="modalBerkas" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-2">
                <h6 class="modal-title fw-bold">Berkas Klaim BPJS (ERM Inti)</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="isiModalBerkas" style="background:#f0f0f0; padding:20px; max-height:85vh; overflow-y:auto;">
                <div class="text-center"><i>Memuat...</i></div>
            </div>
        </div>
    </div>
</div>
<?php $page_js = ob_get_clean(); ?>
<?php require_once('includes/footer.php'); ?>