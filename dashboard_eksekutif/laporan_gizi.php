<?php
/*
 * File: laporan_gizi.php
 * Deskripsi: Halaman Dashboard Manajemen Gizi & Diet Pasien Ranap (Clinical Inpatient Nutrition Auditor)
 */

$page_title = "Manajemen Gizi & Diet Pasien";
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
            <h5 class="card-title text-primary mb-3"><i class="fas fa-filter me-2"></i>Filter Distribusi Gizi</h5>
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
            <div class="card border-start border-4 border-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Porsi Makanan</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="val-total-porsi">0</div>
                            <div class="small mt-1 text-muted">Porsi diet yang didistribusikan</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-utensils fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start border-4 border-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Rata-rata Porsi / Hari</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="val-avg-porsi">0</div>
                            <div class="small mt-1 text-muted">Beban porsi rata-rata harian</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-calculator fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-12 mb-4">
            <div class="card border-start border-4 border-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Menu Diet Aktif</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="val-diet-unik">0</div>
                            <div class="small mt-1 text-muted">Variasi menu diet klinis disajikan</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-apple-alt fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Area -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3 bg-success text-white">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-bar me-2"></i>Top 10 Penggunaan Menu Diet</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="chartGiziMenu"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3 bg-info text-white">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-line me-2"></i>Tren Harian Distribusi Porsi Makanan</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="chartGiziTren"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Advisor -->
    <?php if (is_ai_active()): ?>
    <div class="card bg-dark border-secondary mt-4 shadow-sm mb-4 text-light">
        <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Gizi & Diet AI (Dietetics Advisor)</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGiziPrompt">
                    <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                </button>
                <button id="btnAnalyzeGizi" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                </button>
            </div>
        </div>
        <div class="card-body text-light">
            <div class="collapse mb-3" id="collapseGiziPrompt">
                <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                    <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Catering & Gizi):</label>
                    <textarea id="aiGiziPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah Konsultan Manajemen Gizi & Catering Rumah Sakit. Analisis data distribusi makanan gizi, tren porsi makanan harian, bangsal tujuan, serta nama-nama menu diet pasien rawat inap berikut. Susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:
1. Analisis Volume Catering (evaluasi beban porsi makanan harian dan kecenderungan waktu makan tersibuk).
2. Rekomendasi Logistik & Pengadaan (saran konkret belanja bahan pangan berdasarkan variasi menu diet dominan).
3. Efisiensi Biaya Operasional (saran pengurangan food waste, standarisasi menu diet klinis, dan integrasi dengan estimasi ketersediaan bed bangsal).</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Gunakan prompt ini untuk menyelaraskan saran operasional catering AI.</small>
                        <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetGiziPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                    </div>
                </div>
            </div>

            <div id="aiGiziReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                <div class="text-muted small text-center py-4">
                    <i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
                    Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses data gizi pasien rawat inap secara otomatis.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Analisis operasional gizi menggunakan sampel data distribusi makanan teratas berdasarkan filter terpilih.</small>
                <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiGiziReportContainer', 'Laporan_Analisis_Gizi_Catering_AI.doc')">
                    <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                </button>
            </div>

            <!-- AI Chat Assistant -->
            <div class="mt-4 pt-3 border-top border-secondary">
                <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Gizi dengan AI Assistant</h6>
                <div id="giziChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan terkait diet pasien atau manajemen dapur di bawah...</div>
                </div>
                <form id="giziChatForm">
                    <div class="input-group input-group-sm">
                        <input type="text" id="giziChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail gizi (misal: Bagaimana menghitung estimasi porsi diet garam rendah untuk Bangsal VVIP?)..." required>
                        <button class="btn btn-primary" type="submit" id="btnSendGiziChat">
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
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-2"></i>Rincian Distribusi Makanan Pasien Ranap</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableGizi" class="table table-bordered table-striped table-hover table-sm text-sm" width="100%">
                    <thead class="table-dark">
                        <tr>
                            <th>Tanggal</th>
                            <th>Waktu Makan</th>
                            <th>No. Rawat</th>
                            <th>Nama Pasien</th>
                            <th>Bangsal / Ruangan</th>
                            <th>Kamar</th>
                            <th>Menu Diet disajikan</th>
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
    var tableGizi, chartGiziMenu, chartGiziTren;
    var _giziResponseData = null;

    $(document).ready(function() {
        // Init Datatable
        tableGizi = $('#tableGizi').DataTable({
            "responsive": true, "pageLength": 25, "dom": 'Bfrtip',
            buttons: [ {
                extend: 'excelHtml5', 
                title: 'Laporan Distribusi Gizi Pasien Rawat Inap',
                className: 'btn-sm btn-success',
                exportOptions: { columns: ':visible' }
            }, {
                extend: 'print',
                className: 'btn-sm btn-secondary',
                exportOptions: { columns: ':visible' }
            } ],
            "order": [[ 0, "desc" ]],
            "columns": [
                { "data": "tanggal" },
                { "data": "waktu", render: function(d) {
                    let badge = 'bg-secondary';
                    if (d.startsWith('Pagi')) badge = 'bg-success';
                    else if (d.startsWith('Siang')) badge = 'bg-warning text-dark';
                    else if (d.startsWith('Sore') || d.startsWith('Malam')) badge = 'bg-primary';
                    return '<span class="badge ' + badge + '">' + d + '</span>';
                }},
                { "data": "no_rawat" },
                { "data": "nm_pasien", className: "fw-bold" },
                { "data": "nm_bangsal" },
                { "data": "kd_kamar" },
                { "data": "nama_diet" }
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
            url: 'api/data_gizi.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                _giziResponseData = response;
                
                // 1. Update KPI
                $('#val-total-porsi').text(response.summary.total_porsi.toLocaleString('id-ID'));
                $('#val-avg-porsi').text(response.summary.avg_porsi.toLocaleString('id-ID'));
                $('#val-diet-unik').text(response.summary.total_diet_unik.toLocaleString('id-ID'));

                // 2. Update Table
                tableGizi.clear().rows.add(response.data).draw();

                // 3. Update Charts
                renderCharts(response.chart, response.trends);
            },
            error: function() { alert("Gagal memuat data distribusi gizi."); }
        });
    }

    function renderCharts(menuData, trendData) {
        // Bar Chart Menu
        var ctxBar = document.getElementById("chartGiziMenu").getContext('2d');
        if (chartGiziMenu) chartGiziMenu.destroy();
        
        chartGiziMenu = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: menuData.labels,
                datasets: [{
                    label: "Porsi Disajikan",
                    data: menuData.values,
                    backgroundColor: '#1cc88a',
                    borderColor: '#1cc88a',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Line Chart Tren
        var ctxLine = document.getElementById("chartGiziTren").getContext('2d');
        if (chartGiziTren) chartGiziTren.destroy();

        chartGiziTren = new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [{
                    label: "Distribusi Porsi Harian",
                    data: trendData.values,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // --- AI ADVISOR IMPLEMENTATION ---
    var currentGiziReportContext = "";
    var giziChatHistoryData = [];
    const defaultGiziPrompt = "Anda adalah Konsultan Manajemen Gizi & Catering Rumah Sakit. Analisis data distribusi makanan gizi, tren porsi makanan harian, bangsal tujuan, serta nama-nama menu diet pasien rawat inap berikut. Susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:\\n1. Analisis Volume Catering (evaluasi beban porsi makanan harian dan kecenderungan waktu makan tersibuk).\\n2. Rekomendasi Logistik & Pengadaan (saran konkret belanja bahan pangan berdasarkan variasi menu diet dominan).\\n3. Efisiensi Biaya Operasional (saran pengurangan food waste, standarisasi menu diet klinis, dan integrasi dengan estimasi ketersediaan bed bangsal).";

    function resetGiziPrompt() {
        $('#aiGiziPrompt').val(defaultGiziPrompt);
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

    $(document).on('click', '#btnAnalyzeGizi', function() {
        if (!_giziResponseData || _giziResponseData.data.length === 0) {
            alert('Silakan tampilkan data gizi terlebih dahulu.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
        $('#aiGiziReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-info mb-2"></div><div class="small text-muted">AI sedang menganalisis manajemen gizi...</div></div>');

        // Ambil sampel 30 baris data makanan teratas untuk context AI
        var sampleMakanan = _giziResponseData.data.map(function(m) {
            return { tgl: m.tanggal, waktu: m.waktu, no_rawat: m.no_rawat, pasien: m.nm_pasien, bangsal: m.nm_bangsal, diet: m.nama_diet };
        });

        var contextData = {
            periode: $('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val(),
            summary: _giziResponseData.summary,
            sample_data: sampleMakanan
        };

        var formData = new URLSearchParams();
        formData.append('action', 'batch_summary');
        formData.append('raw_data', JSON.stringify([contextData]));
        formData.append('custom_prompt', $('#aiGiziPrompt').val().trim());
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
            const aiThinkingContainer = document.getElementById('aiGiziReportContainer');
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
                                $('#aiGiziReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullText += data.choices[0].delta.content;
                                $('#aiGiziReportContainer').html(parseMarkdownToHtml(fullText));
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

            if (!isError && fullText) {
                currentGiziReportContext = fullText;
                giziChatHistoryData = [];
                $('#giziChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan terkait diet pasien atau manajemen dapur di bawah...</div>');
            }
        }).catch(err => {
            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
            $('#aiGiziReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
        });
    });

    $(document).on('submit', '#giziChatForm', function(e) {
        e.preventDefault();
        const input = $('#giziChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentGiziReportContext) return;

        if (giziChatHistoryData.length === 0) {
            $('#giziChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#giziChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
            '</div>'
        );
        $('#giziChatHistory').scrollTop($('#giziChatHistory')[0].scrollHeight);

        input.val('');
        $('#giziChatInput, #btnSendGiziChat').prop('disabled', true);

        var replyId = 'gizi_reply_' + Date.now();
        $('#giziChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI Assistant</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#giziChatHistory').scrollTop($('#giziChatHistory')[0].scrollHeight);

        var sampleMakanan = _giziResponseData.data.map(function(m) {
            return { tgl: m.tanggal, waktu: m.waktu, no_rawat: m.no_rawat, pasien: m.nm_pasien, bangsal: m.nm_bangsal, diet: m.nama_diet };
        });

        var contextData = {
            periode: $('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val(),
            summary: _giziResponseData.summary,
            sample_data: sampleMakanan
        };

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentGiziReportContext);
        chatData.append('raw_data', JSON.stringify([contextData]));
        chatData.append('custom_prompt', $('#aiGiziPrompt').val().trim());
        chatData.append('history', JSON.stringify(giziChatHistoryData));
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
            const aiThinkingContainer = document.getElementById('aiGiziReportContainer');
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
                                $('#giziChatHistory').scrollTop($('#giziChatHistory')[0].scrollHeight);
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            $('#giziChatInput, #btnSendGiziChat').prop('disabled', false);

            if (!isError && fullReply) {
                giziChatHistoryData.push({ role: 'user', content: messageText });
                giziChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#giziChatInput, #btnSendGiziChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error koneksi</span>');
        });
    });
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>
