<?php
/*
 * File: laporan_analisa_lengkap.php (UPDATE V3 - WIDE TABLE)
 * - Menampilkan semua kolom detail (SEP, Wilayah, Perusahaan, dll).
 * - Tabel menggunakan scroll horizontal (scrollX).
 */

$page_title = "Analisa Data Lengkap (Deep Dive)";
require_once('includes/header.php');
require_once('includes/functions.php');

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01'); 
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

$penjabs = [];
$sql_pj = "SELECT kd_pj, png_jawab FROM penjab WHERE status='1' ORDER BY png_jawab";
$res_pj = $koneksi->query($sql_pj);
while($row = $res_pj->fetch_assoc()){ $penjabs[] = $row; }
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<style>
    /* Agar header tabel tidak pecah saat scroll horizontal */
    th { white-space: nowrap; }
</style>

<div class="container-fluid">

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title text-primary mb-3"><i class="fas fa-filter me-2"></i>Filter Data (Tgl Bayar)</h5>
            <form id="filterForm" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Dari Tanggal</label>
                    <input type="date" class="form-control" id="tgl_awal" value="<?php echo $tgl_awal; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="tgl_akhir" value="<?php echo $tgl_akhir; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Status Bayar</label>
                    <select class="form-select" id="status_bayar">
                        <option value="">-- Semua --</option>
                        <option value="Sudah Bayar" selected>Sudah Bayar</option>
                        <option value="Belum Bayar">Belum Bayar</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Jenis Kunjungan</label>
                    <select class="form-select" id="status_lanjut">
                        <option value="">-- Semua --</option>
                        <option value="Ralan">Rawat Jalan</option>
                        <option value="Ranap">Rawat Inap</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Penjamin</label>
                    <select class="form-select select2-single" id="kd_pj">
                        <option value="">-- Semua Penjamin --</option>
                        <?php foreach($penjabs as $p): ?>
                            <option value="<?php echo $p['kd_pj']; ?>"><?php echo $p['png_jawab']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100" onclick="loadData()">
                        <i class="fas fa-search me-2"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-start border-4 border-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Pendapatan (Filter Ini)</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="val-pendapatan">Rp 0</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-money-bill-wave fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-start border-4 border-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Kunjungan</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="val-kunjungan">0</div>
                            <div class="small mt-1 text-muted"><span id="val-ralan">0</span> Ralan | <span id="val-ranap">0</span> Ranap</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Data Detail Lengkap</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover table-sm text-sm" id="dataTable" width="100%" cellspacing="0">
                    <thead class="table-dark">
                        <tr>
                            <th>Tgl Reg</th>
                            <th>Tgl Bayar</th>
                            <th>No. Rawat</th>
                            <th>Sts Pasien</th> <th>Jns Rawat</th>
                            <th>No. RM</th>
                            <th>Nama Pasien</th>
                            <th>No. Tlp</th>
                            <th>Penjamin</th>
                            <th>Poli</th>
                            <th>Dokter</th>
                            <th>Dr. Perujuk</th>
                            <th>Diagnosa</th>
                            <th>No. SEP</th>
                            <th>No. Rujukan</th>
                            <th>Faskes Rujuk</th>
                            <th>Perusahaan</th>
                            <th>Kabupaten</th>
                            <th>Kecamatan</th>
                            <th>Kelurahan</th>
                            <th class="text-end">Komponen Obat</th>
                            <th class="text-end">Komponen Tindakan</th>
                            <th class="text-end">Total Biaya</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="20" class="text-end">TOTAL HALAMAN INI:</td>
                            <td class="text-end text-success" id="pageTotalObat">0</td>
                            <td class="text-end text-info" id="pageTotalTindakan">0</td>
                            <td class="text-end text-primary" id="pageTotal">0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <?php if (is_ai_active()): ?>
    <!-- AI DEEP DIVE ADVISOR CONTAINER -->
    <div class="card bg-dark border-secondary mt-4 shadow-sm mb-4 text-light" id="ai-advisor-container">
        <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Data Lengkap AI (Executive Insights)</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAnalisaPrompt">
                    <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                </button>
                <button id="btnAnalyzeAnalisa" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                </button>
            </div>
        </div>
        <div class="card-body text-light">
            <!-- Collapsible Prompt Tuning Area -->
            <div class="collapse mb-3" id="collapseAnalisaPrompt">
                <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                    <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Data Lengkap):</label>
                    <textarea id="aiAnalisaPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah Analis Data Rumah Sakit Senior. Analisis data pendapatan, kunjungan, penjamin, dan rincian transaksi pasien berikut. Identifikasi tren utama, pola kunjungan ralan/ranap, kontribusi penjamin terbesar, serta anomali pembiayaan. Berikan insight eksekutif yang strategis dan rekomendasi aksi dalam Bahasa Indonesia secara terstruktur.</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Setel prompt khusus ini untuk menyesuaikan gaya analisis AI.</small>
                        <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetAnalisaPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                    </div>
                </div>
            </div>

            <!-- Display Container Output -->
            <div id="aiAnalisaReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                <div class="text-muted small text-center py-4">
                    <i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
                    Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memulai analisis cerdas data lengkap secara otomatis.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Analisis mendalam menggunakan sampel data transaksi teratas berdasarkan filter pencarian.</small>
                <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiAnalisaReportContainer', 'Laporan_Analisis_Data_Lengkap_AI.doc')">
                    <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                </button>
            </div>

            <!-- AI Interactive Chat Assistant -->
            <div class="mt-4 pt-3 border-top border-secondary">
                <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Transaksi dengan AI Assistant</h6>
                <div id="analisaChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
                </div>
                <form id="analisaChatForm">
                    <div class="input-group input-group-sm">
                        <input type="text" id="analisaChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail data (misal: Mengapa biaya Ranap bulan ini meningkat tajam?)..." required>
                        <button class="btn btn-primary" type="submit" id="btnSendAnalisaChat">
                            <i class="fas fa-paper-plane me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<div class="modal fade" id="modalDetailNota" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Detail Nota</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="isi-nota-container">
                <p class="text-center">Memuat data...</p>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
    var myTable;

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    }

    $(document).ready(function() {
        $('.select2-single').select2({ theme: "bootstrap-5", placeholder: "Pilih Penjamin", allowClear: true });

        myTable = $('#dataTable').DataTable({
            "responsive": false, // Matikan responsive agar scrollX bekerja
            "scrollX": true,     // Aktifkan scroll horizontal
            "dom": 'Bfrtip', 
            /* "buttons": [
                { extend: 'excelHtml5', className: 'btn btn-success btn-sm', text: '<i class="fas fa-file-excel"></i> Excel Full', title: 'Analisa Data Lengkap' },
                { extend: 'print', className: 'btn btn-secondary btn-sm', text: '<i class="fas fa-print"></i> Print' }
            ],  */
			"buttons": [
                { 
                    extend: 'excelHtml5', 
                    className: 'btn btn-success btn-sm', 
                    text: '<i class="fas fa-file-excel"></i> Excel Full', 
                    title: 'Analisa Data Lengkap',
                    exportOptions: {
                        // Pastikan kolom Aksi (terakhir) tidak ikut
                        columns: ':visible:not(:last-child)',
                        format: {
                            body: function(data, row, column, node) {
                                // 1. KHUSUS KOLOM RUPIAH (Index 20, 21, 22)
                                if (column === 20 || column === 21 || column === 22) {
                                    return typeof data === 'string' ?
                                        data.replace(/\./g, '').replace(',', '.') :
                                        data;
                                }

                                // 2. KHUSUS KOLOM TEXT LAINNYA (Untuk membersihkan <span class="badge">)
                                // Jika data adalah string dan mengandung tanda kurung siku HTML (<)
                                if (typeof data === 'string' && data.indexOf('<') > -1) {
                                    // Regex ini akan menghapus semua tag HTML dan menyisakan teksnya saja
                                    // Contoh: <span class="badge">Baru</span>  Menjadi:  Baru
                                    return data.replace(/<[^>]+>/g, "").trim();
                                }

                                return data;
                            }
                        }
                    }
                },
                { 
                    extend: 'print', 
                    className: 'btn btn-secondary btn-sm', 
                    text: '<i class="fas fa-print"></i> Print',
                    exportOptions: {
                        columns: ':visible:not(:last-child)' 
                    }
                }
            ],
            "pageLength": 25,
            "columns": [
                { "data": "tgl_registrasi" },
                { "data": "tgl_byr" },
                { "data": "no_rawat" },
                { "data": "Pasien_Baru_Lama", render: function(d){ return d=='Baru'?'<span class="badge bg-info">Baru</span>':'Lama'; } },
                { "data": "status_lanjut", render: function(d){ return d=='Ranap'?'<span class="badge bg-warning text-dark">Ranap</span>':'Ralan'; } },
                { "data": "no_rkm_medis" },
                { "data": "nm_pasien", className: "fw-bold" },
                { "data": "no_tlp" },
                { "data": "png_jawab" },
                { "data": "nm_poli" },
                { "data": "nm_dokter" },
                { "data": "dokter_perujuk" },
                { "data": "nm_penyakit", render: function(d,t,r){ return (r.kd_penyakit||'') + ' ' + (d||''); } },
                { "data": "no_sep" },
                { "data": "no_rujukan" },
                { "data": "nmppkrujukan" },
                { "data": "nama_perusahaan" },
                { "data": "nm_kab" },
                { "data": "nm_kec" },
                { "data": "nm_kel" },
                { "data": "BiayaObat", className: "text-end fw-bold text-success", render: $.fn.dataTable.render.number('.', ',', 0, '') },
                { "data": "BiayaTindakan", className: "text-end fw-bold text-info", render: $.fn.dataTable.render.number('.', ',', 0, '') },
                { "data": "TotalBiaya", className: "text-end fw-bold text-primary", render: $.fn.dataTable.render.number('.', ',', 0, '') },
                { 
                    "data": null, className: "text-center",
                    "render": function(data, type, row) {
                        return `<button class="btn btn-sm btn-outline-success btn-lihat-nota" data-norawat="${row.no_rawat}"><i class="fas fa-receipt"></i></button>`;
                    }
                }
            ],
            "order": [[ 1, "desc" ]],
            "footerCallback": function (row, data, start, end, display) {
                var api = this.api();
                var intVal = function (i) { return typeof i === 'string' ? i.replace(/[\.,]/g, '') * 1 : typeof i === 'number' ? i : 0; };
                
                var pageTotalObat = api.column(20, { page: 'current' }).data().reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);
                
                var pageTotalTindakan = api.column(21, { page: 'current' }).data().reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);
                
                var pageTotal = api.column(22, { page: 'current' }).data().reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);
                
                $('#pageTotalObat').html(formatRupiah(pageTotalObat));
                $('#pageTotalTindakan').html(formatRupiah(pageTotalTindakan));
                $('#pageTotal').html(formatRupiah(pageTotal));
            }
        });

        // Data akan dimuat saat user klik tombol Tampilkan
        
        // Listener Tombol Nota
        $(document).on('click', '.btn-lihat-nota', function() {
            var noRawat = $(this).data('norawat');
            $("#isi-nota-container").html("<p class='text-center'>Memuat data...</p>");
            $('#modalDetailNota').modal('show');
            $.ajax({
                url: "api/get_detail_nota.php", type: "GET", data: { no_rawat: noRawat }, dataType: "json",
                success: function(response) {
                    var html = '<table class="table table-sm table-striped"><thead><tr><th>Ket</th><th>Nama</th><th class="text-end">Biaya</th><th class="text-center">Jml</th><th class="text-end">Total</th></tr></thead><tbody>';
                    var grandTotal = 0;
                    if (response.length > 0) {
                        response.forEach(function(item) {
                            html += `<tr><td>${item.status}</td><td>${item.nm_perawatan}</td><td class="text-end">${formatRupiah(item.biaya)}</td><td class="text-center">${item.jumlah}</td><td class="text-end">${formatRupiah(item.totalbiaya)}</td></tr>`;
                            grandTotal += parseFloat(item.totalbiaya);
                        });
                    }
                    html += '</tbody><tfoot class="fw-bold"><tr><td colspan="4" class="text-end">TOTAL:</td><td class="text-end">'+formatRupiah(grandTotal)+'</td></tr></tfoot></table>';
                    $("#isi-nota-container").html(html);
                }
            });
        });
    });

    function loadData() {
        $('#val-pendapatan').text('Loading...');
        $('#val-kunjungan').text('...');
        
        var params = {
            tgl_awal: $('#tgl_awal').val(),
            tgl_akhir: $('#tgl_akhir').val(),
            status_bayar: $('#status_bayar').val(),
            status_lanjut: $('#status_lanjut').val(),
            kd_pj: $('#kd_pj').val()
        };

        $.ajax({
            url: 'api/data_analisa_lengkap.php', type: 'GET', data: params, dataType: 'json',
            success: function(response) {
                $('#val-pendapatan').text(formatRupiah(response.summary.total_pendapatan));
                $('#val-kunjungan').text(response.summary.total_kunjungan.toLocaleString());
                $('#val-ralan').text(response.summary.total_ralan);
                $('#val-ranap').text(response.summary.total_ranap);

                myTable.clear();
                if (response.data.length > 0) {
                    myTable.rows.add(response.data).draw();
                } else {
                    myTable.draw();
                }
            },
            error: function() { alert("Gagal memuat data."); }
        });
    }

    <?php if (is_ai_active()): ?>
    // AI Advisor Logic
    var analisaChatHistoryData = [];
    var currentAnalisaReportContext = "";
    const defaultAnalisaPrompt = "Anda adalah Analis Data Rumah Sakit Senior. Analisis data pendapatan, kunjungan, penjamin, dan rincian transaksi pasien berikut. Identifikasi tren utama, pola kunjungan ralan/ranap, kontribusi penjamin terbesar, serta anomali pembiayaan. Berikan insight eksekutif yang strategis dan rekomendasi aksi dalam Bahasa Indonesia secara terstruktur.";

    function resetAnalisaPrompt() {
        $('#aiAnalisaPrompt').val(defaultAnalisaPrompt);
    }

    function parseMarkdownToHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/^\s*###\s+(.*?)$/gm, '<h5 class="text-info mt-3 mb-2">$1</h5>')
            .replace(/^\s*##\s+(.*?)$/gm, '<h4 class="text-primary mt-3 mb-2">$1</h4>')
            .replace(/^\s*#\s+(.*?)$/gm, '<h3 class="text-primary mt-3 mb-2">$1</h3>')
            .replace(/^\s*[-*+]\s+(.*?)$/gm, '<li>$1</li>')
            .replace(/(<li>.*?<\/li>)/gs, '<ul class="mb-2">$1</ul>')
            .replace(/<\/ul>\s*<ul class="mb-2">/g, '')
            .replace(/^\s*([^#<>\s\-*+].*?)$/gm, '<p class="mb-2">$1</p>')
            .replace(/\n\n/g, '<br>');
    }

    function exportToWord(elementId, fileName) {
        var content = document.getElementById(elementId).innerHTML;
        var header = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>" +
                     "<head><meta charset='utf-8'><title>Laporan Ekspor</title>" +
                     "<style>body { font-family: Arial, sans-serif; line-height: 1.6; } h1, h2, h3 { color: #0284c7; }</style></head><body>";
        var footer = "</body></html>";
        
        var blob = new Blob(['\ufeff', header + content + footer], { type: 'application/msword' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = fileName || 'Laporan.doc';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    $(document).on('click', '#btnAnalyzeAnalisa', function() {
        if (!myTable || !myTable.data().any()) {
            alert('Silakan tampilkan data terlebih dahulu.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
        $('#aiAnalisaReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-info mb-2"></div><div class="small text-muted">AI sedang menganalisis data lengkap...</div></div>');

        // Ambil data transaksi teratas (maks 30 baris - Anti-Truncation)
        let rowData = [];
        let dtData = myTable.data().toArray();
        dtData.sort((a, b) => b.TotalBiaya - a.TotalBiaya);
        let limit = Math.min(dtData.length, 30); 
        
        for(let i = 0; i < limit; i++){
             rowData.push({
                 tgl_reg: dtData[i].tgl_registrasi,
                 tgl_bayar: dtData[i].tgl_byr,
                 jenis: dtData[i].status_lanjut,
                 pasien: dtData[i].nm_pasien,
                 penjamin: dtData[i].png_jawab,
                 poli: dtData[i].nm_poli,
                 dokter: dtData[i].nm_dokter,
                 diagnosa: dtData[i].nm_penyakit,
                 kabupaten: dtData[i].nm_kab,
                 kecamatan: dtData[i].nm_kec,
                 kelurahan: dtData[i].nm_kel,
                 biaya: dtData[i].TotalBiaya
             });
        }

        let contextData = {
            summary: {
                total_pendapatan: $('#val-pendapatan').text(),
                total_kunjungan: $('#val-kunjungan').text(),
                ralan: $('#val-ralan').text(),
                ranap: $('#val-ranap').text(),
                filter: {
                    tgl_awal: $('#tgl_awal').val(),
                    tgl_akhir: $('#tgl_akhir').val(),
                    status_bayar: $('#status_bayar option:selected').text(),
                    penjamin: $('#kd_pj option:selected').text()
                }
            },
            sample_data: rowData
        };

        var formData = new URLSearchParams();
        formData.append('action', 'batch_summary');
        formData.append('raw_data', JSON.stringify([contextData]));
        formData.append('custom_prompt', $('#aiAnalisaPrompt').val().trim());
        formData.append('stream', '1');

        fetch('api/ai_analyzer.php', {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).then(async response => {
            if (!response.ok) throw new Error('Network response was not ok');
            const reader = response.body.getReader();
            const decoder = new TextDecoder("utf-8");
            let fullText = "";
            let isError = false;
            let isThinking = false;
            const aiThinkingContainer = document.getElementById('aiAnalisaReportContainer');
            let buffer = "";

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, {stream: true});
                const lines = buffer.split('\n');
                buffer = lines.pop();

                for (let line of lines) {
                    if (line === 'event: thinking') {
                        isThinking = true;
                        continue;
                    }
                    if (isThinking && line.startsWith('data: ')) {
                        isThinking = false;
                        try {
                            const td = JSON.parse(line.substring(6));
                            if (typeof aiThinkingContainer !== 'undefined' && aiThinkingContainer) {
                                aiThinkingContainer.innerHTML = buildThinkingHTML(td.row_count || 0, td.message || '');
                            }
                        } catch(e) {}
                        continue;
                    }

                    line = line.trim();
                    if (line.startsWith('data: ')) {
                        const dataStr = line.substring(6);
                        if (dataStr === '[DONE]') continue;
                        try {
                            const data = JSON.parse(dataStr);
                            if (data.message) {
                                isError = true;
                                $('#aiAnalisaReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullText += data.choices[0].delta.content;
                                $('#aiAnalisaReportContainer').html(parseMarkdownToHtml(fullText));
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

            if (!isError && fullText) {
                currentAnalisaReportContext = fullText;
                analisaChatHistoryData = [];
                $('#analisaChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>');
            }
        }).catch(err => {
            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
            $('#aiAnalisaReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
        });
    });

    $(document).on('submit', '#analisaChatForm', function(e) {
        e.preventDefault();
        const input = $('#analisaChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentAnalisaReportContext) return;

        if (analisaChatHistoryData.length === 0) {
            $('#analisaChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#analisaChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
            '</div>'
        );
        $('#analisaChatHistory').scrollTop($('#analisaChatHistory')[0].scrollHeight);

        input.val('');
        $('#analisaChatInput, #btnSendAnalisaChat').prop('disabled', true);

        var replyId = 'analisa_reply_' + Date.now();
        $('#analisaChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI Assistant</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#analisaChatHistory').scrollTop($('#analisaChatHistory')[0].scrollHeight);

        // Ambil data transaksi teratas (maks 30 baris)
        let rowData = [];
        let dtData = myTable.data().toArray();
        dtData.sort((a, b) => b.TotalBiaya - a.TotalBiaya);
        let limit = Math.min(dtData.length, 30); 
        
        for(let i = 0; i < limit; i++){
             rowData.push({
                 tgl_reg: dtData[i].tgl_registrasi,
                 tgl_bayar: dtData[i].tgl_byr,
                 jenis: dtData[i].status_lanjut,
                 pasien: dtData[i].nm_pasien,
                 penjamin: dtData[i].png_jawab,
                 poli: dtData[i].nm_poli,
                 dokter: dtData[i].nm_dokter,
                 diagnosa: dtData[i].nm_penyakit,
                 kabupaten: dtData[i].nm_kab,
                 kecamatan: dtData[i].nm_kec,
                 kelurahan: dtData[i].nm_kel,
                 biaya_obat: dtData[i].BiayaObat,
                 biaya_tindakan: dtData[i].BiayaTindakan,
                 biaya: dtData[i].TotalBiaya
             });
        }

        let contextData = {
            summary: {
                total_pendapatan: $('#val-pendapatan').text(),
                total_kunjungan: $('#val-kunjungan').text(),
                ralan: $('#val-ralan').text(),
                ranap: $('#val-ranap').text(),
                filter: {
                    tgl_awal: $('#tgl_awal').val(),
                    tgl_akhir: $('#tgl_akhir').val(),
                    status_bayar: $('#status_bayar option:selected').text(),
                    penjamin: $('#kd_pj option:selected').text()
                }
            },
            sample_data: rowData
        };

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentAnalisaReportContext);
        chatData.append('raw_data', JSON.stringify([contextData]));
        chatData.append('custom_prompt', $('#aiAnalisaPrompt').val().trim());
        chatData.append('history', JSON.stringify(analisaChatHistoryData));
        chatData.append('stream', '1');

        fetch('api/ai_analyzer.php', {
            method: 'POST',
            body: chatData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).then(async response => {
            const reader = response.body.getReader();
            const decoder = new TextDecoder("utf-8");
            let fullReply = "";
            let isError = false;
            let isThinking = false;
            const aiThinkingContainer = document.getElementById('aiAnalisaReportContainer');
            let buffer = "";

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, {stream: true});
                const lines = buffer.split('\n');
                buffer = lines.pop();

                for (let line of lines) {
                    if (line === 'event: thinking') {
                        isThinking = true;
                        continue;
                    }
                    if (isThinking && line.startsWith('data: ')) {
                        isThinking = false;
                        try {
                            const td = JSON.parse(line.substring(6));
                            if (typeof aiThinkingContainer !== 'undefined' && aiThinkingContainer) {
                                aiThinkingContainer.innerHTML = buildThinkingHTML(td.row_count || 0, td.message || '');
                            }
                        } catch(e) {}
                        continue;
                    }

                    line = line.trim();
                    if (line.startsWith('data: ')) {
                        const dataStr = line.substring(6);
                        if (dataStr === '[DONE]') continue;
                        try {
                            const data = JSON.parse(dataStr);
                            if (data.message) {
                                isError = true;
                                $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> ' + data.message + '</span>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullReply += data.choices[0].delta.content;
                                $('#' + replyId).html(parseMarkdownToHtml(fullReply));
                                $('#analisaChatHistory').scrollTop($('#analisaChatHistory')[0].scrollHeight);
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            $('#analisaChatInput, #btnSendAnalisaChat').prop('disabled', false);

            if (!isError && fullReply) {
                analisaChatHistoryData.push({ role: 'user', content: messageText });
                analisaChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#analisaChatInput, #btnSendAnalisaChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error koneksi</span>');
        });
    });
    <?php endif; ?>
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>