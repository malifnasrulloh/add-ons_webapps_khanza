<?php
$page_title = "Peta Demografi Pasien";
require_once('includes/header.php');

$tgl_awalnya = date('Y-m-01');
$tgl_akhirnya = date('Y-m-d');

// Ambil daftar penjamin (penjab) untuk filter
$penjab_list = [];
$res_pj = $koneksi->query("SELECT kd_pj, png_jawab FROM penjab WHERE status='1' ORDER BY png_jawab ASC");
if($res_pj) {
    while($row_pj = $res_pj->fetch_assoc()) {
        $penjab_list[] = $row_pj;
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-map-marked-alt text-primary"></i> Peta Demografi & Area Asal Pasien</h1>
    </div>

    <!-- Filter Card -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Dari Tanggal Kunjungan</label>
                    <input type="date" class="form-control" id="tgl_awal" value="<?php echo $tgl_awalnya; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="tgl_akhir" value="<?php echo $tgl_akhirnya; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Penjamin / Asuransi</label>
                    <select class="form-select" id="kd_pj">
                        <option value="">-- Semua Penjamin --</option>
                        <?php foreach($penjab_list as $pj): ?>
                            <option value="<?= $pj['kd_pj']; ?>"><?= $pj['png_jawab']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100" onclick="loadData()"><i class="fas fa-search me-1"></i> Cari</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Widget KPI -->
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Kunjungan Pasien Baru</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><span id="kpi-baru">0</span> <small class="text-xs">Org</small></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Kunjungan Pasien Lama</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><span id="kpi-lama">0</span> <small class="text-xs">Org</small></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Kunjungan Keseluruhan</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><span id="kpi-total">0</span> <small class="text-xs">Org</small></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hospital-user fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Visualisasi Geospasial / Chart -->
    <div class="row">
        <!-- Pie Chart Kabupaten -->
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top 5 Kabupaten Asal</h6>
                </div>
                <div class="card-body h-100 d-flex flex-column justify-content-center align-items-center pb-5">
                    <div class="chart-pie">
                        <canvas id="pieChartKab"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Horizontal Bar Chart Kecamatan -->
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top 10 Kecamatan Kunjungan Terpadat</h6>
                </div>
                <div class="card-body pb-5">
                    <div class="chart-bar" style="height: 300px;">
                        <canvas id="barChartKec"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bar Chart Kelurahan -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Top 10 Kelurahan/Desa Asal Kunjungan Tertinggi</h6>
        </div>
        <div class="card-body">
            <div class="chart-bar" style="height: 350px;">
                <canvas id="barChartKel"></canvas>
            </div>
        </div>
    </div>

    <?php if (is_ai_active()): ?>
    <!-- AI DEMOGRAFI ANALYZER CONTAINER -->
    <div class="card bg-dark border-secondary mt-4 shadow-sm mb-4" id="ai-demo-card" style="display:none;">
        <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Demografi & Area Pasien AI (AI Patient Demographics & Marketing Advisor)</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDemoPrompt">
                    <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                </button>
                <button id="btnAnalyzeDemo" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                </button>
            </div>
        </div>
        <div class="card-body text-light">
            <!-- Collapsible Prompt Tuning Area -->
            <div class="collapse mb-3" id="collapseDemoPrompt">
                <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                    <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Demografi Pasien):</label>
                    <textarea id="aiDemoPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah AI Patient Demographics & Marketing Advisor yang ahli dalam analisis wilayah dan pemasaran rumah sakit. Analisis data demografi asal wilayah pasien (kabupaten, kecamatan, kelurahan) dan hubungannya dengan cara bayar/penjamin berikut. Baca tren demografi pasien (usia/jenis kelamin jika ada) dan berikan rekomendasi strategis bagi tim pemasaran untuk merancang promosi kesehatan atau CSR di wilayah yang potensial tetapi memiliki angka kunjungan rendah.</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Setel prompt khusus ini untuk menyesuaikan gaya analisis demografi pasien yang dihasilkan AI.</small>
                        <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetDemoPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                    </div>
                </div>
            </div>

            <!-- Display Container Output -->
            <div id="aiDemoReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                <div class="text-muted small text-center py-4">
                    <i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
                    Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses ringkasan analisis demografi secara otomatis.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Demografi dianalisis berdasarkan kunjungan pasien dalam rentang tanggal cutoff terpilih.</small>
                <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiDemoReportContainer', 'Laporan_Analisis_Demografi_Pasien_AI.doc')">
                    <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                </button>
            </div>

            <!-- AI Interactive Chat Assistant -->
            <div class="mt-4 pt-3 border-top border-secondary">
                <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Demografi Pasien dengan AI Assistant</h6>
                <div id="demoChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
                </div>
                <form id="demoChatForm">
                    <div class="input-group input-group-sm">
                        <input type="text" id="demoChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail demografi (misal: Kelurahan mana di Kecamatan X dengan kunjungan terendah?)..." required>
                        <button class="btn btn-primary" type="submit" id="btnSendDemoChat">
                            <i class="fas fa-paper-plane me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- DataTables Detail -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Rincian Komposisi Pasien Per Daerah (Kelurahan)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="dataTableDemografi" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th>Kelurahan / Desa</th>
                            <th>Kecamatan</th>
                            <th>Kabupaten / Kota</th>
                            <th class="text-center text-info">Status Lanjut</th>
                            <th class="text-center text-warning">Jenis Kelamin</th>
                            <th>Poli Tujuan</th>
                            <th class="text-center text-primary">Pasien Baru</th>
                            <th class="text-center text-success">Pasien Lama</th>
                            <th class="text-center fw-bold">Total Kunjungan</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>

<script>
    var tableDemo;
    var pieChartKabInstance;
    var barChartKecInstance;
    var barChartKelInstance;

    $(document).ready(function() {
        tableDemo = $('#dataTableDemografi').DataTable({
            "responsive": true,
            "pageLength": 25,
            "order": [[ 8, "desc" ]], // Urut berdasarkan Total Kunjungan yang terbanyak
            "dom": 'Bfrtip',
            "buttons": [
                {
                    extend: 'excelHtml5',
                    title: 'Laporan Kepadataan Kunjungan Pasien Per Daerah (Demografi)',
                    className: 'btn btn-success btn-sm',
                    exportOptions: { columns: ':visible' }
                }
            ],
            "columns": [
                { "data": "nm_kel", "className": "fw-bold" },
                { "data": "nm_kec", "className": "text-muted" },
                { "data": "nm_kab", "className": "text-muted" },
                { "data": "status_lanjut", "className": "text-center small" },
                { "data": "jk", "className": "text-center small" },
                { "data": "nm_poli", "className": "small" },
                { "data": "baru", "className": "text-center text-primary" },
                { "data": "lama", "className": "text-center text-success" },
                { "data": "total", "className": "text-center fw-bold" }
            ]
        });

        // Data akan dimuat saat user klik tombol Cari
    });

    function loadData() {
        var tgl1 = $('#tgl_awal').val();
        var tgl2 = $('#tgl_akhir').val();
        var penjab = $('#kd_pj').val();

        $('#kpi-baru, #kpi-lama, #kpi-total').text('...');

        $.ajax({
            url: 'api/data_demografi.php',
            type: 'GET',
            data: { tgl_awal: tgl1, tgl_akhir: tgl2, kd_pj: penjab },
            dataType: 'json',
            success: function(res) {
                _demografiResponseData = res.data || [];
                $('#ai-demo-card').show();

                // Formatting custom string ID Number
                let idID = new Intl.NumberFormat('id-ID');

                // Update KPI Cards
                $('#kpi-baru').text(idID.format(res.summary.total_pasien_baru));
                $('#kpi-lama').text(idID.format(res.summary.total_pasien_lama));
                $('#kpi-total').text(idID.format(res.summary.total_kunjungan));

                // Update Table
                tableDemo.clear();
                tableDemo.rows.add(res.data);
                tableDemo.draw();

                // Render Visualisasi Charts
                renderPieKabupaten(res.chart.kabupaten);
                renderBarKecamatan(res.chart.kecamatan);
                renderBarKelurahan(res.chart.kelurahan);
            },
            error: function(err) {
                alert("Gagal memuat data Demografi Pasien.");
                console.error(err);
            }
        });
    }

    // Palette warna custom cerah ala dashboard modern
    const C_PALETTE = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69','#e83e8c', '#6f42c1', '#fd7e14'];

    function renderPieKabupaten(chartData) {
        if (pieChartKabInstance) { pieChartKabInstance.destroy(); }
        var ctx = document.getElementById("pieChartKab");
        pieChartKabInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.data,
                    backgroundColor: C_PALETTE.slice(0, chartData.labels.length),
                    hoverBackgroundColor: C_PALETTE.slice(0, chartData.labels.length),
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 20, boxWidth: 12 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let val = context.raw;
                                let percentage = Math.round((val / total) * 100);
                                return ' ' + context.label + ': ' + val + ' Pasien (' + percentage + '%)';
                            }
                        }
                    }
                },
                cutout: '70%',
            },
        });
    }

    function renderBarKecamatan(chartData) {
        if (barChartKecInstance) { barChartKecInstance.destroy(); }
        var ctx = document.getElementById("barChartKec");
        barChartKecInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: "Kunjungan",
                    backgroundColor: "#36b9cc",
                    hoverBackgroundColor: "#2c9faf",
                    borderColor: "#36b9cc",
                    data: chartData.data,
                    borderRadius: 4,
                }],
            },
            options: {
                maintainAspectRatio: false,
                indexAxis: 'y', // Membuatnya Horizontal Bar Chart
                plugins: { legend: { display: false } }, // Sembunyikan legenda
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { drawBorder: false, color: "#ebedef" },
                    },
                    y: {
                        grid: { display: false, drawBorder: false }
                    }
                }
            }
        });
    }

    function renderBarKelurahan(chartData) {
        if (barChartKelInstance) { barChartKelInstance.destroy(); }
        var ctx = document.getElementById("barChartKel");
        barChartKelInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: "Kunjungan",
                    backgroundColor: "#4e73df",
                    hoverBackgroundColor: "#2e59d9",
                    borderColor: "#4e73df",
                    data: chartData.data,
                    borderRadius: 4,
                    maxBarThickness: 50
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { maxTicksLimit: 10 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: "rgb(234, 236, 244)", drawBorder: false },
                    }
                }
            }
        });
    }

    // --- AI DEMOGRAFI ADVISOR JS PIPELINE ---
    var _demografiResponseData = null;
    var currentDemoReportContext = "";
    var demoChatHistoryData = [];
    const defaultDemoPromptText = "Anda adalah AI Patient Demographics & Marketing Advisor yang ahli dalam analisis wilayah dan pemasaran rumah sakit. Analisis data demografi asal wilayah pasien (kabupaten, kecamatan, kelurahan) dan hubungannya dengan cara bayar/penjamin berikut. Baca tren demografi pasien (usia/jenis kelamin jika ada) dan berikan rekomendasi strategis bagi tim pemasaran untuk merancang promosi kesehatan atau CSR di wilayah yang potensial tetapi memiliki angka kunjungan rendah.";

    function resetDemoPrompt() {
        $('#aiDemoPrompt').val(defaultDemoPromptText);
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

    $(document).on('click', '#btnAnalyzeDemo', function() {
        if (!_demografiResponseData || _demografiResponseData.length === 0) {
            alert('Silakan tampilkan data demografi terlebih dahulu.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
        $('#aiDemoReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-primary mb-2"></div><div class="small text-muted">AI sedang menganalisis demografi pasien...</div></div>');

        // Slice to 30 records to prevent truncation while ensuring context remains rich
        var sampleDemo = _demografiResponseData;

        var demografiRawData = {
            periode: $('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val(),
            penjamin: $('#kd_pj option:selected').text(),
            summary: {
                total_baru: $('#kpi-baru').text(),
                total_lama: $('#kpi-lama').text(),
                total_kunjungan: $('#kpi-total').text()
            },
            sample_data: sampleDemo
        };

        var formData = new URLSearchParams();
        formData.append('action', 'batch_summary');
        formData.append('raw_data', JSON.stringify([demografiRawData]));
        formData.append('custom_prompt', $('#aiDemoPrompt').val().trim());
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
            const aiThinkingContainer = document.getElementById('aiDemoReportContainer');
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
                                $('#aiDemoReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullText += data.choices[0].delta.content;
                                $('#aiDemoReportContainer').html(parseMarkdownToHtml(fullText));
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

            if (!isError && fullText) {
                currentDemoReportContext = fullText;
                demoChatHistoryData = [];
                $('#demoChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>');
            }
        }).catch(err => {
            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
            $('#aiDemoReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
        });
    });

    $(document).on('submit', '#demoChatForm', function(e) {
        e.preventDefault();
        const input = $('#demoChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentDemoReportContext) return;

        if (demoChatHistoryData.length === 0) {
            $('#demoChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#demoChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
            '</div>'
        );
        $('#demoChatHistory').scrollTop($('#demoChatHistory')[0].scrollHeight);

        input.val('');
        $('#demoChatInput, #btnSendDemoChat').prop('disabled', true);

        var replyId = 'demo_reply_' + Date.now();
        $('#demoChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI Pemasaran Assistant</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#demoChatHistory').scrollTop($('#demoChatHistory')[0].scrollHeight);

        var sampleDemo = _demografiResponseData;
        var demografiRawData = {
            periode: $('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val(),
            penjamin: $('#kd_pj option:selected').text(),
            summary: {
                total_baru: $('#kpi-baru').text(),
                total_lama: $('#kpi-lama').text(),
                total_kunjungan: $('#kpi-total').text()
            },
            sample_data: sampleDemo
        };

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentDemoReportContext);
        chatData.append('raw_data', JSON.stringify([demografiRawData]));
        chatData.append('custom_prompt', $('#aiDemoPrompt').val().trim());
        chatData.append('history', JSON.stringify(demoChatHistoryData));
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
            const aiThinkingContainer = document.getElementById('aiDemoReportContainer');
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
                                $('#demoChatHistory').scrollTop($('#demoChatHistory')[0].scrollHeight);
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            $('#demoChatInput, #btnSendDemoChat').prop('disabled', false);

            if (!isError && fullReply) {
                demoChatHistoryData.push({ role: 'user', content: messageText });
                demoChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#demoChatInput, #btnSendDemoChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error koneksi</span>');
        });
    });
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>
