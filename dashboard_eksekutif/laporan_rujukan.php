<?php
/*
 * File: laporan_rujukan.php
 * Deskripsi: Halaman Dashboard Analisis Rujukan Masuk (Incoming Referrals Marketing Tracker)
 */

$page_title = "Analisis Rujukan Masuk";
require_once('includes/header.php');
require_once('includes/functions.php');

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01'); 
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<div class="container-fluid">
    
    <!-- Filter Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title text-primary mb-3"><i class="fas fa-filter me-2"></i>Filter Rujukan Masuk</h5>
            <form id="filterForm" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Dari Tanggal</label>
                    <input type="date" class="form-control" id="tgl_awal" value="<?php echo $tgl_awal; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="tgl_akhir" value="<?php echo $tgl_akhir; ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100" onclick="loadData()">
                        <i class="fas fa-search me-2"></i> Tampilkan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start border-4 border-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Rujukan Masuk</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="val-total-rujukan">0</div>
                            <div class="small mt-1 text-muted">Pasien dirujuk masuk ke RS</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-ambulance fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start border-4 border-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Faskes Perujuk Unik</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="val-faskes-unik">0</div>
                            <div class="small mt-1 text-muted">Jumlah instansi luar aktif</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-hospital fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-12 mb-4">
            <div class="card border-start border-4 border-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Kategori Rujukan Terbanyak</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="val-kategori-top">-</div>
                            <div class="small mt-1 text-muted">Kelompok spesialisasi rujukan</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-tags fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Area -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-bar me-2"></i>Top 10 Fasilitas Kesehatan Perujuk</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="chartFaskes"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3 bg-info text-white">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-pie me-2"></i>Distribusi Kategori Rujukan</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="chart-area" style="width: 100%; height: 260px;">
                        <canvas id="chartKategori"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Advisor -->
    <?php if (is_ai_active()): ?>
    <div class="card bg-dark border-secondary mt-4 shadow-sm mb-4 text-light">
        <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Rujukan Masuk AI (Market Advisor)</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRujukPrompt">
                    <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                </button>
                <button id="btnAnalyzeRujuk" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                </button>
            </div>
        </div>
        <div class="card-body text-light">
            <div class="collapse mb-3" id="collapseRujukPrompt">
                <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                    <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Pasar Rujukan):</label>
                    <textarea id="aiRujukPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah Konsultan Hubungan Masyarakat & Marketing Rumah Sakit. Analisis data rujukan masuk, kontribusi instansi perujuk, kategori spesialisasi rujukan, serta penyakit pasien rujukan berikut. Susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:
1. Analisis Kinerja Pasar (identifikasi asal rujukan terbesar dan tren kontribusinya).
2. Potensi Layanan (spesialisasi klinis/kategori rujukan apa yang memiliki volume terbesar dan ke mana fokus promosi harus diarahkan).
3. Rekomendasi Aliansi Faskes (saran konkret program kemitraan dengan puskesmas/klinik perujuk loyal, dan strategi merekrut faskes pasif).</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Gunakan prompt ini untuk menyesuaikan keluaran analisis strategis AI.</small>
                        <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetRujukPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                    </div>
                </div>
            </div>

            <div id="aiRujukReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                <div class="text-muted small text-center py-4">
                    <i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
                    Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memicu analisis pasar rujukan masuk secara otomatis.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Analisis marketing menggunakan sampel data transaksi rujukan teratas berdasarkan filter terpilih.</small>
                <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiRujukReportContainer', 'Laporan_Analisis_Pasar_Rujukan_AI.doc')">
                    <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                </button>
            </div>

            <!-- AI Chat Assistant -->
            <div class="mt-4 pt-3 border-top border-secondary">
                <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Pasar dengan AI Assistant</h6>
                <div id="rujukChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan terkait kemitraan faskes atau rujukan di bawah...</div>
                </div>
                <form id="rujukChatForm">
                    <div class="input-group input-group-sm">
                        <input type="text" id="rujukChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail rujukan (misal: Bagaimana strategi meningkatkan rujukan pasien bedah dari Klinik Pratama?)..." required>
                        <button class="btn btn-primary" type="submit" id="btnSendRujukChat">
                            <i class="fas fa-paper-plane me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Data Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-2"></i>Rincian Pasien Rujukan Masuk</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableRujukan" class="table table-bordered table-striped table-hover table-sm text-sm" width="100%">
                    <thead class="table-dark">
                        <tr>
                            <th>Tgl Registrasi</th>
                            <th>No. Rawat</th>
                            <th>Nama Pasien</th>
                            <th>Penjamin</th>
                            <th>Faskes Perujuk</th>
                            <th>Dokter Perujuk</th>
                            <th>Kategori</th>
                            <th>No. Rujukan</th>
                            <th>Diagnosa Rujukan</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php ob_start(); ?>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
    var tableRujukan, chartFaskes, chartKategori;
    var _rujukResponseData = null;

    $(document).ready(function() {
        // Init Datatable
        tableRujukan = $('#tableRujukan').DataTable({
            "responsive": true, "pageLength": 25, "dom": 'Bfrtip',
            buttons: [ {
                extend: 'excelHtml5', 
                title: 'Laporan Pasien Rujukan Masuk',
                className: 'btn-sm btn-success',
                exportOptions: { columns: ':visible' }
            }, {
                extend: 'print',
                className: 'btn-sm btn-secondary',
                exportOptions: { columns: ':visible' }
            } ],
            "order": [[ 0, "desc" ]],
            "columns": [
                { "data": "tgl_registrasi" },
                { "data": "no_rawat" },
                { "data": "nm_pasien", className: "fw-bold" },
                { "data": "png_jawab" },
                { "data": "perujuk" },
                { "data": "dokter_perujuk" },
                { "data": "kategori_rujuk" },
                { "data": "no_rujuk" },
                { "data": "nm_penyakit" }
            ]
        });

        // Load data awal
        loadData();
    });

    function loadData() {
        var params = {
            tgl_awal: $('#tgl_awal').val(),
            tgl_akhir: $('#tgl_akhir').val()
        };

        $.ajax({
            url: 'api/data_rujukan.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                _rujukResponseData = response;
                
                // 1. Update KPI
                $('#val-total-rujukan').text(response.summary.total_rujukan.toLocaleString('id-ID'));
                $('#val-faskes-unik').text(response.summary.total_faskes_unik.toLocaleString('id-ID'));
                $('#val-kategori-top').text(response.summary.kategori_top);

                // 2. Update Table
                tableRujukan.clear().rows.add(response.data).draw();

                // 3. Update Charts
                renderCharts(response.chart, response.categories);
            },
            error: function() { alert("Gagal memuat data rujukan."); }
        });
    }

    function renderCharts(faskesData, catData) {
        // Bar Chart Top Faskes
        var ctxBar = document.getElementById("chartFaskes").getContext('2d');
        if (chartFaskes) chartFaskes.destroy();
        
        chartFaskes = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: faskesData.labels,
                datasets: [{
                    label: "Jumlah Rujukan",
                    data: faskesData.values,
                    backgroundColor: '#4e73df',
                    borderColor: '#4e73df',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                indexAxis: 'y', // Horizontal Bar
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        });

        // Donut Chart Kategori
        var ctxPie = document.getElementById("chartKategori").getContext('2d');
        if (chartKategori) chartKategori.destroy();

        chartKategori = new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: catData.labels,
                datasets: [{
                    data: catData.values,
                    backgroundColor: ['#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
                    hoverOffset: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } } }
            }
        });
    }

    // --- AI ADVISOR IMPLEMENTATION ---
    var currentRujukReportContext = "";
    var rujukChatHistoryData = [];
    const defaultRujukPrompt = "Anda adalah Konsultan Hubungan Masyarakat & Marketing Rumah Sakit. Analisis data rujukan masuk, kontribusi instansi perujuk, kategori spesialisasi rujukan, serta penyakit pasien rujukan berikut. Susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:\\n1. Analisis Kinerja Pasar (identifikasi asal rujukan terbesar dan tren kontribusinya).\\n2. Potensi Layanan (spesialisasi klinis/kategori rujukan apa yang memiliki volume terbesar dan ke mana fokus promosi harus diarahkan).\\n3. Rekomendasi Aliansi Faskes (saran konkret program kemitraan dengan puskesmas/klinik perujuk loyal, dan strategi merekrut faskes pasif).";

    function resetRujukPrompt() {
        $('#aiRujukPrompt').val(defaultRujukPrompt);
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

    $(document).on('click', '#btnAnalyzeRujuk', function() {
        if (!_rujukResponseData || _rujukResponseData.data.length === 0) {
            alert('Silakan tampilkan data rujukan terlebih dahulu.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
        $('#aiRujukReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-info mb-2"></div><div class="small text-muted">AI sedang menganalisis pasar rujukan masuk...</div></div>');

        // Ambil sampel 30 baris data rujukan teratas untuk context AI
        var sampleRujukan = _rujukResponseData.data.map(function(r) {
            return { tgl: r.tgl_registrasi, no_rawat: r.no_rawat, pasien: r.nm_pasien, faskes: r.perujuk, dokter: r.dokter_perujuk, kategori: r.kategori_rujuk, diagnosa: r.nm_penyakit };
        });

        var contextData = {
            periode: $('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val(),
            summary: _rujukResponseData.summary,
            sample_data: sampleRujukan
        };

        var formData = new URLSearchParams();
        formData.append('action', 'batch_summary');
        formData.append('raw_data', JSON.stringify([contextData]));
        formData.append('custom_prompt', $('#aiRujukPrompt').val().trim());
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
            const aiThinkingContainer = document.getElementById('aiRujukReportContainer');
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
                                $('#aiRujukReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullText += data.choices[0].delta.content;
                                $('#aiRujukReportContainer').html(parseMarkdownToHtml(fullText));
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

            if (!isError && fullText) {
                currentRujukReportContext = fullText;
                rujukChatHistoryData = [];
                $('#rujukChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan terkait kemitraan faskes atau rujukan di bawah...</div>');
            }
        }).catch(err => {
            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
            $('#aiRujukReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
        });
    });

    $(document).on('submit', '#rujukChatForm', function(e) {
        e.preventDefault();
        const input = $('#rujukChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentRujukReportContext) return;

        if (rujukChatHistoryData.length === 0) {
            $('#rujukChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#rujukChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
            '</div>'
        );
        $('#rujukChatHistory').scrollTop($('#rujukChatHistory')[0].scrollHeight);

        input.val('');
        $('#rujukChatInput, #btnSendRujukChat').prop('disabled', true);

        var replyId = 'rujuk_reply_' + Date.now();
        $('#rujukChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI Assistant</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#rujukChatHistory').scrollTop($('#rujukChatHistory')[0].scrollHeight);

        var sampleRujukan = _rujukResponseData.data.map(function(r) {
            return { tgl: r.tgl_registrasi, no_rawat: r.no_rawat, pasien: r.nm_pasien, faskes: r.perujuk, dokter: r.dokter_perujuk, kategori: r.kategori_rujuk, diagnosa: r.nm_penyakit };
        });

        var contextData = {
            periode: $('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val(),
            summary: _rujukResponseData.summary,
            sample_data: sampleRujukan
        };

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentRujukReportContext);
        chatData.append('raw_data', JSON.stringify([contextData]));
        chatData.append('custom_prompt', $('#aiRujukPrompt').val().trim());
        chatData.append('history', JSON.stringify(rujukChatHistoryData));
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
            const aiThinkingContainer = document.getElementById('aiRujukReportContainer');
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
                                $('#rujukChatHistory').scrollTop($('#rujukChatHistory')[0].scrollHeight);
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            $('#rujukChatInput, #btnSendRujukChat').prop('disabled', false);

            if (!isError && fullReply) {
                rujukChatHistoryData.push({ role: 'user', content: messageText });
                rujukChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#rujukChatInput, #btnSendRujukChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error koneksi</span>');
        });
    });
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>
