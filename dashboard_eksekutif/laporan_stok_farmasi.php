<?php
/*
 * File: laporan_stok_farmasi.php (UPDATE V2)
 * Fitur Baru: 
 * - Searchable Dropdown (Select2) untuk Filter Lokasi.
 * - Filter Lokasi/Depo pada Modal Riwayat Stok Digital.
 */
$page_title = "Monitoring Stok Farmasi";
require_once('includes/header.php');

// Ambil daftar bangsal untuk filter
$bangsals = [];
$sql_bangsal = "SELECT kd_bangsal, nm_bangsal FROM bangsal WHERE status='1' ORDER BY nm_bangsal";
$res_bangsal = $koneksi->query($sql_bangsal);
while($row = $res_bangsal->fetch_assoc()){
    $bangsals[] = $row;
}
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<style>
    /* Custom CSS */
    .obat-card { border-left: 4px solid #4e73df; margin-bottom: 10px; }
    .obat-card .nama-obat { font-weight: bold; color: #333; font-size: 1.1rem; }
    .obat-card .stok-besar { font-size: 1.5rem; font-weight: bold; color: #1cc88a; }
    .obat-card .lokasi { font-size: 0.8rem; color: #858796; }
    /* Fix z-index select2 dalam modal */
    .select2-container--open { z-index: 9999999 !important; }
</style>

<div class="container-fluid">
    
    <div class="row mb-4">
        <div class="col-md-4 mb-2">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Nilai Aset (Rp)</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="val-aset">Loading...</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-money-bill-wave fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Item Obat</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="val-item">...</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-pills fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Stok Menipis (< 10)</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="val-kritis">...</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-5">
                    <select class="form-select select2-single" id="kd_bangsal">
                        <option value="">-- Semua Lokasi/Depo --</option>
                        <?php foreach($bangsals as $b): ?>
                            <option value="<?php echo $b['kd_bangsal']; ?>"><?php echo $b['nm_bangsal']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control" id="keyword" placeholder="Cari nama obat...">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary w-100" onclick="loadData()">Cari</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (is_ai_active()): ?>
    <!-- AI STOK FARMASI ANALYZER CONTAINER -->
    <div class="card bg-dark border-secondary mt-4 shadow-sm mb-4" id="ai-stok-card" style="display:none;">
        <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Aset & Stok Farmasi AI (AI Pharmacy Stock & Purchase Advisor)</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStokPrompt">
                    <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                </button>
                <button id="btnAnalyzeStok" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                </button>
            </div>
        </div>
        <div class="card-body text-light">
            <!-- Collapsible Prompt Tuning Area -->
            <div class="collapse mb-3" id="collapseStokPrompt">
                <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                    <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Stok Farmasi):</label>
                    <textarea id="aiStokPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah AI Pharmacy Stock & Purchase Advisor yang ahli. Analisis data persediaan obat/alkes (stok aktif, nilai aset, obat lambat bergerak/dead stock, barang kedaluwarsa) berikut. Deteksi barang kritis/menipis dan berikan rekomendasi perencanaan pembelian (procurement) serta efisiensi anggaran farmasi agar tidak terjadi kekosongan obat maupun kelebihan stok.</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Setel prompt khusus ini untuk menyesuaikan gaya analisis farmasi yang dihasilkan AI.</small>
                        <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetStokPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                    </div>
                </div>
            </div>

            <!-- Display Container Output -->
            <div id="aiStokReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                <div class="text-muted small text-center py-4">
                    <i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
                    Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses ringkasan analisis stok farmasi secara otomatis.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Stok farmasi dianalisis berdasarkan snapshot data inventori saat ini.</small>
                <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiStokReportContainer', 'Laporan_Analisis_Stok_Farmasi_AI.doc')">
                    <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                </button>
            </div>

            <!-- AI Interactive Chat Assistant -->
            <div class="mt-4 pt-3 border-top border-secondary">
                <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Stok Farmasi dengan AI Assistant</h6>
                <div id="stokChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
                </div>
                <form id="stokChatForm">
                    <div class="input-group input-group-sm">
                        <input type="text" id="stokChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail stok (misal: Obat apa saja yang paling kritis stoknya di Depo A?)..." required>
                        <button class="btn btn-primary" type="submit" id="btnSendStokChat">
                            <i class="fas fa-paper-plane me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Data Stok Aktif</h6>
            <small class="text-muted">Menampilkan max 500 data teratas</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableStok" class="table table-hover table-striped" width="100%">
                    <thead class="table-dark">
                        <tr>
                            <th>Nama Obat / Barang</th>
                            <th class="text-center">Satuan</th>
                            <th class="text-end">Total Stok</th>
                            <th class="text-end">Nilai Aset</th>
                            <th>Lokasi (Depo)</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRiwayat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-history me-2"></i>Kartu Stok Digital: <span id="modalTitleObat" class="fw-bold">...</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3 g-2">
                    <div class="col-md-3">
                        <label class="small fw-bold">Dari Tanggal</label>
                        <input type="date" class="form-control" id="hist_tgl_awal" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="hist_tgl_akhir" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4">
                         <label class="small fw-bold">Filter Depo/Gudang</label>
                         <select class="form-select select2-modal" id="hist_kd_bangsal">
                            <option value="">-- Semua Lokasi --</option>
                            <?php foreach($bangsals as $b): ?>
                                <option value="<?php echo $b['kd_bangsal']; ?>"><?php echo $b['nm_bangsal']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-info text-white w-100" onclick="refreshHistory()">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table id="tableRiwayat" class="table table-sm table-bordered table-hover w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu</th>
                                <th>Keterangan / Posisi</th>
                                <th>Lokasi</th>
                                <th>No. Faktur</th>
                                <th class="text-center">Masuk</th>
                                <th class="text-center">Keluar</th>
                                <th class="text-center">Saldo</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    var tableStok, tableRiwayat;
    var currentKodeBrng = '';

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    }

    $(document).ready(function() {
        // 1. Inisialisasi Select2 pada Filter Utama
        $('.select2-single').select2({
            theme: "bootstrap-5",
            placeholder: "Pilih atau Ketik Nama Depo...",
            allowClear: true
        });

        // 2. Inisialisasi Select2 pada Modal (dropdownParent penting agar bisa search di dalam modal)
        $('.select2-modal').select2({
            theme: "bootstrap-5",
            placeholder: "Pilih Depo...",
            allowClear: true,
            dropdownParent: $('#modalRiwayat') 
        });

        // 3. Init Table Stok
        tableStok = $('#tableStok').DataTable({
            "responsive": true,
            "dom": 'Bfrtip',
            //"buttons": [ 'excel', 'print' ],
            buttons: [ 
                {
                    extend: 'excelHtml5',
                    title: 'Laporan Stok Farmasi',
                    className: 'btn btn-success btn-sm',
                    exportOptions: {
                        columns: ':visible:not(:last-child)', // Cegah kolom Aksi ikut ter-export
                        format: {
                            body: function(data, row, column, node) {
                                // PENTING: Paksa data jadi String dulu agar tidak crash pada tipe Number (Stok)
                                var strData = (data === null || data === undefined) ? '' : String(data);

                                // 1. KHUSUS KOLOM ANGKA (Stok: Col 2 & Aset: Col 3)
                                if (column === 2 || column === 3) {
                                    // Hapus tag HTML (jika ada)
                                    let clean = strData.replace(/<[^>]+>/g, "");
                                    // Bersihkan karakter non-angka (kecuali koma & minus)
                                    // Ganti koma desimal jadi titik (standar Excel)
                                    return clean.replace(/[^\d,-]/g, '').replace(',', '.');
                                }

                                // 2. BERSIHKAN HTML PADA KOLOM TEKS (Misal: Nama Obat)
                                // Ganti <br> dengan strip " - " supaya tidak nempel
                                if (strData.indexOf('<') > -1) {
                                    return strData.replace(/<br\s*\/?>/gi, " - ").replace(/<[^>]+>/g, "").trim();
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
			"pageLength": 10,
            "order": [[ 2, "desc" ]], 
            "columns": [
                { "data": "nama_brng", 
                  "render": function(data, type, row) {
                      return `<div><strong>${data}</strong><br><small class="text-muted">${row.kode_brng}</small></div>`;
                  }
                },
                { "data": "satuan", className: "text-center" },
                { "data": "total_stok", className: "text-end fw-bold fs-5 text-success" },
                { "data": "total_aset", className: "text-end", render: function(data) { return formatRupiah(data); } },
                { "data": "lokasi_stok", className: "small text-muted" },
                { 
                    "data": null,
                    "className": "text-center",
                    "render": function(data, type, row) {
                        return `<button class="btn btn-sm btn-warning text-white" onclick="openHistory('${row.kode_brng}', '${row.nama_brng}')">
                                    <i class="fas fa-history"></i> Riwayat
                                </button>`;
                    }
                }
            ]
        });

        // 4. Init Table Riwayat
        tableRiwayat = $('#tableRiwayat').DataTable({
            "responsive": true,
            "order": [[ 0, "desc" ]],
            "pageLength": 10,
            "columns": [
                { "data": "tanggal", render: function(data, type, row) { return data + ' ' + row.jam; } },
                { "data": "posisi", render: function(data, type, row) { return `<strong>${data}</strong><br><small>${row.keterangan}</small>`; } },
                { "data": "nm_bangsal" },
                { "data": "no_faktur" },
                { "data": "masuk", className: "text-center text-success fw-bold" },
                { "data": "keluar", className: "text-center text-danger fw-bold" },
                { "data": "stok_akhir", className: "text-center fw-bold bg-light" }
            ]
        });

        // Data akan dimuat saat user klik tombol Cari
    });

    function loadData() {
        var bangsal = $('#kd_bangsal').val();
        var keyword = $('#keyword').val();

        // Loading state
        $('#val-aset').text('Loading...');
        $('#val-item').text('...');

        $.ajax({
            url: 'api/data_stok_farmasi.php',
            type: 'GET',
            data: { kd_bangsal: bangsal, keyword: keyword },
            dataType: 'json',
            success: function(response) {
                _stokResponseData = response.data || [];
                _stokSummaryData = response.summary || {};
                $('#ai-stok-card').show();

                $('#val-aset').text(formatRupiah(response.summary.total_aset));
                $('#val-item').text(response.summary.total_item);
                $('#val-kritis').text(response.summary.stok_kritis);

                tableStok.clear();
                tableStok.rows.add(response.data);
                tableStok.draw();
            },
            error: function() { alert("Gagal memuat data stok."); }
        });
    }

    function openHistory(kode, nama) {
        currentKodeBrng = kode;
        $('#modalTitleObat').text(nama);
        $('#modalRiwayat').modal('show');
        
        // Reset filter modal ke default
        $('#hist_kd_bangsal').val('').trigger('change'); 
        
        refreshHistory();
    }

    function refreshHistory() {
        if(!currentKodeBrng) return;
        var tgl1 = $('#hist_tgl_awal').val();
        var tgl2 = $('#hist_tgl_akhir').val();
        var bangsal = $('#hist_kd_bangsal').val(); // Ambil nilai filter modal

        tableRiwayat.clear().draw();
        
        $.ajax({
            url: 'api/data_riwayat_obat.php',
            type: 'GET',
            data: { 
                kode_brng: currentKodeBrng, 
                tgl_awal: tgl1, 
                tgl_akhir: tgl2,
                kd_bangsal: bangsal // Kirim ke API
            },
            dataType: 'json',
            success: function(response) {
                tableRiwayat.rows.add(response.data);
                tableRiwayat.draw();
            },
            error: function() { console.error("Gagal load riwayat"); }
        });
    }

    // --- AI STOK FARMASI ADVISOR JS PIPELINE ---
    var _stokResponseData = null;
    var _stokSummaryData = null;
    var currentStokReportContext = "";
    var stokChatHistoryData = [];
    const defaultStokPromptText = "Anda adalah AI Pharmacy Stock & Purchase Advisor yang ahli. Analisis data persediaan obat/alkes (stok aktif, nilai aset, obat lambat bergerak/dead stock, barang kedaluwarsa) berikut. Deteksi barang kritis/menipis dan berikan rekomendasi perencanaan pembelian (procurement) serta efisiensi anggaran farmasi agar tidak terjadi kekosongan obat maupun kelebihan stok.";

    function resetStokPrompt() {
        $('#aiStokPrompt').val(defaultStokPromptText);
    }

    function parseMarkdownToHtml(md) {
        if (!md) return '';
        return md
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/^### (.*?)$/gm, '<h5 class="fw-bold text-info mt-3">$1</h5>')
            .replace(/^## (.*?)$/gm, '<h4 class="fw-bold text-primary mt-4 border-bottom border-secondary pb-1">$1</h4>')
            .replace(/^# (.*?)$/gm, '<h3 class="fw-bold text-primary mt-4">$1</h3>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
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

    $(document).on('click', '#btnAnalyzeStok', function() {
        if (!_stokResponseData || _stokResponseData.length === 0) {
            alert('Silakan tampilkan data stok farmasi terlebih dahulu.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
        $('#aiStokReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-primary mb-2"></div><div class="small text-muted">AI sedang menganalisis stok farmasi...</div></div>');

        // Slice to 30 records to prevent truncation while ensuring context remains rich
        var sampleStok = _stokResponseData;

        var stokRawData = {
            lokasi: $('#kd_bangsal option:selected').text(),
            summary: {
                total_aset: _stokSummaryData.total_aset,
                total_item: _stokSummaryData.total_item,
                stok_kritis: _stokSummaryData.stok_kritis
            },
            sample_data: sampleStok
        };

        var formData = new URLSearchParams();
        formData.append('action', 'batch_summary');
        formData.append('raw_data', JSON.stringify([stokRawData]));
        formData.append('custom_prompt', $('#aiStokPrompt').val().trim());
        formData.append('stream', '1');

        fetch('api/ai_analyzer.php', {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).then(async response => {
            const reader = response.body.getReader();
            const decoder = new TextDecoder("utf-8");
            let fullText = "";
            let isError = false;
            let isThinking = false;
            const aiThinkingContainer = document.getElementById('aiStokReportContainer');
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
                                $('#aiStokReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullText += data.choices[0].delta.content;
                                $('#aiStokReportContainer').html(parseMarkdownToHtml(fullText));
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

            if (!isError && fullText) {
                currentStokReportContext = fullText;
                stokChatHistoryData = [];
                $('#stokChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>');
            }
        }).catch(err => {
            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
            $('#aiStokReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
        });
    });

    $(document).on('submit', '#stokChatForm', function(e) {
        e.preventDefault();
        const input = $('#stokChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentStokReportContext) return;

        if (stokChatHistoryData.length === 0) {
            $('#stokChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#stokChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
            '</div>'
        );
        $('#stokChatHistory').scrollTop($('#stokChatHistory')[0].scrollHeight);

        input.val('');
        $('#stokChatInput, #btnSendStokChat').prop('disabled', true);

        var replyId = 'stok_reply_' + Date.now();
        $('#stokChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI Farmasi Assistant</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#stokChatHistory').scrollTop($('#stokChatHistory')[0].scrollHeight);

        var sampleStok = _stokResponseData;
        var stokRawData = {
            lokasi: $('#kd_bangsal option:selected').text(),
            summary: {
                total_aset: _stokSummaryData.total_aset,
                total_item: _stokSummaryData.total_item,
                stok_kritis: _stokSummaryData.stok_kritis
            },
            sample_data: sampleStok
        };

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentStokReportContext);
        chatData.append('raw_data', JSON.stringify([stokRawData]));
        chatData.append('custom_prompt', $('#aiStokPrompt').val().trim());
        chatData.append('history', JSON.stringify(stokChatHistoryData));
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
            const aiThinkingContainer = document.getElementById('aiStokReportContainer');
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
                                $('#stokChatHistory').scrollTop($('#stokChatHistory')[0].scrollHeight);
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            $('#stokChatInput, #btnSendStokChat').prop('disabled', false);

            if (!isError && fullReply) {
                stokChatHistoryData.push({ role: 'user', content: messageText });
                stokChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#stokChatInput, #btnSendStokChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error koneksi</span>');
        });
    });
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>