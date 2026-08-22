<?php
$page_title = "Kinerja Pelayanan (Waktu Tunggu)";
require_once('includes/header.php');
require_once('includes/functions.php');

$tgl_awalnya = date('Y-m-01');
$tgl_akhirnya = date('Y-m-d');

// Ambil daftar penjamin (penjab)
$penjab_list = [];
$res_pj = $koneksi->query("SELECT kd_pj, png_jawab FROM penjab WHERE status='1' ORDER BY png_jawab ASC");
if($res_pj) {
    while($row_pj = $res_pj->fetch_assoc()) {
        $penjab_list[] = $row_pj;
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-2">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-stopwatch text-primary"></i> Laporan Waktu Tunggu Pelayanan (TAT Ralan)</h1>
    </div>
    
    <div class="alert alert-info py-2 shadow-sm mb-4" role="alert">
        <i class="fas fa-info-circle me-1"></i> <strong>Apa itu TAT?</strong> TAT (<em>Turn Around Time</em>) adalah total waktu yang dibutuhkan sejak pelayanan dimulai hingga selesai untuk satu proses spesifik. Dalam konteks Rumah Sakit, ini mengukur berapa lama pasien harus menunggu dari awal pendaftaran sampai mereka mendapat pelayanan secara menyeluruh.
    </div>

    <!-- Filter Card -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Dari Tanggal</label>
                    <input type="date" class="form-control" id="tgl_awal" value="<?php echo $tgl_awalnya; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="tgl_akhir" value="<?php echo $tgl_akhirnya; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Penjamin / Asuransi</label>
                    <select class="form-select" id="kd_pj">
                        <option value="">-- Semua Penjamin --</option>
                        <?php foreach($penjab_list as $pj): ?>
                            <option value="<?= $pj['kd_pj']; ?>"><?= $pj['png_jawab']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label font-weight-bold">Cari Pasien / No RM</label>
                    <input type="text" class="form-control" id="keyword" placeholder="Opsional...">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100" onclick="loadData()"><i class="fas fa-search me-1"></i> Cari</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Widget KPI -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Average TAT Pendaftaran -> Poli (Validasi)</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><span id="kpi-dp">0</span> <small class="text-xs">Menit</small></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-stethoscope fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Average TAT Resep -> Selesai Obat (Farmasi)</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><span id="kpi-ro">0</span> <small class="text-xs">Menit</small></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-pills fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Average Daftar -> Obat Diterima (Medis)</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><span id="kpi-total">0</span> <small class="text-xs">Menit</small></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-medkit fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Average TAT (Daftar -> Keluar Kasir)</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><span id="kpi-kasir">0</span> <small class="text-xs">Menit</small></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-flag-checkered fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Tren Rata-rata Waktu Pelayanan Harian</h6>
        </div>
        <div class="card-body">
            <canvas id="lineChartTAT" style="min-height: 350px; max-height: 350px;"></canvas>
        </div>
    </div>

    <?php if (is_ai_active()): ?>
    <!-- AI TAT ANALYZER CONTAINER -->
    <div class="card bg-dark border-secondary mt-4 shadow-sm mb-4">
        <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Waktu Tunggu Pelayanan AI (TAT Advisor)</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTatPrompt">
                    <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                </button>
                <button id="btnAnalyzeTat" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                </button>
            </div>
        </div>
        <div class="card-body text-light">
            <!-- Collapsible Prompt Tuning Area -->
            <div class="collapse mb-3" id="collapseTatPrompt">
                <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                    <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Waktu Tunggu):</label>
                    <textarea id="aiTatPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah Konsultan Alur Layanan Pasien & Efisiensi Operasional RS (TAT Advisor). Analisis data rata-rata waktu tunggu pelayanan (TAT) berikut (mencakup rata-rata durasi Pendaftaran->Poli, Resep->Obat, dan Daftar->Kasir) dan buatlah Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:
1. Penilaian Kecepatan Pelayanan (apakah rata-rata waktu tunggu di Poli & Farmasi memenuhi standar SPM Kemenkes).
2. Identifikasi Bottleneck (tunjukkan penjamin, poliklinik, atau hari tertentu yang mengalami penumpukan antrean terlama).
3. Rekomendasi Aksi Taktis (saran perbaikan alokasi dokter, pengaturan antrean farmasi, atau digitalisasi layanan untuk mempercepat TAT).</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Setel prompt khusus ini untuk menyesuaikan gaya laporan alur layanan yang dihasilkan AI.</small>
                        <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetTatPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                    </div>
                </div>
            </div>

            <!-- Display Container Output -->
            <div id="aiTatReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                <div class="text-muted small text-center py-4">
                    <i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
                    Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses ringkasan efisiensi waktu tunggu pelayanan secara otomatis.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Waktu pelayanan dianalisis berdasarkan periode filter terpilih.</small>
                <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiTatReportContainer', 'Laporan_Analisis_Waktu_Tunggu_AI.doc')">
                    <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                </button>
            </div>

            <!-- AI Interactive Chat Assistant -->
            <div class="mt-4 pt-3 border-top border-secondary">
                <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Alur Layanan dengan AI Assistant</h6>
                <div id="tatChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
                </div>
                <form id="tatChatForm">
                    <div class="input-group input-group-sm">
                        <input type="text" id="tatChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail antrean (misal: Poli mana yang paling sering terlambat melayani?)..." required>
                        <button class="btn btn-primary" type="submit" id="btnSendTatChat">
                            <i class="fas fa-paper-plane me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Rincian Data -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Rincian Waktu Pelayanan Per Pasien</h6>
            <span class="badge bg-secondary" id="countPasien">0 Pasien</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTableTAT" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th>Tgl. Registrasi</th>
                            <th>No. RM</th>
                            <th>Nama Pasien</th>
                            <th>Penjamin</th>
                            <th>Poliklinik</th>
                            <th>Jam Daftar</th>
                            <th>Jam Periksa</th>
                            <th>Daftar->Poli</th>
                            <th>Jam Obat</th>
                            <th>Resep->Obat</th>
                            <th>Jam Kasir</th>
                            <th class="text-center">Daftar->Kasir</th>
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
    var tableTAT;
    var lineChartInstance;
    var _tatResponseData = null;

    $(document).ready(function() {
        tableTAT = $('#dataTableTAT').DataTable({
            "responsive": true,
            "pageLength": 10,
            "dom": 'Bfrtip',
            "buttons": [
                {
                    extend: 'excelHtml5',
                    title: 'Laporan Waktu Tunggu Pelayanan Pasien Ralan',
                    className: 'btn btn-success btn-sm',
                    exportOptions: { columns: ':visible' }
                }
            ],
            "columns": [
                { "data": "tgl_registrasi" },
                { "data": "no_rkm_medis" },
                { "data": "nm_pasien" },
                { "data": "png_jawab", "className": "text-muted small" },
                { "data": "nm_poli" },
                { "data": "jam_reg", "className": "text-center" },
                { "data": "jam_periksa", "defaultContent": "-", "className": "text-center" },
                { "data": "durasi_dp", "defaultContent": "-", "className": "text-center bg-light text-primary", render: function(d) { return d ? d + " mnt" : "-"; } },
                { "data": "jam_selesai_obat", "defaultContent": "-", "className": "text-center" },
                { "data": "durasi_ro", "defaultContent": "-", "className": "text-center bg-light text-success", render: function(d) { return d ? d + " mnt" : "-"; } },
                { "data": "jam_kasir", "defaultContent": "-", "className": "text-center", render: function(d, type, row) { return row.is_ranap ? "<span class='badge bg-info'>Pasien Ranap</span>" : (d ? d : "-"); } },
                { "data": "durasi_kasir", "defaultContent": "-", "className": "text-center font-weight-bold bg-warning text-dark", render: function(d, type, row) { return row.is_ranap ? "-" : (d ? d + " mnt" : "-"); } }
            ]
        });

        // Data akan dimuat saat user klik tombol Cari
    });

    function loadData() {
        var tk_awal = $('#tgl_awal').val();
        var tk_akhir = $('#tgl_akhir').val();
        var penjab = $('#kd_pj').val();
        var search = $('#keyword').val();

        $('#kpi-dp, #kpi-ro, #kpi-total, #kpi-kasir').text('...');

        $.ajax({
            url: 'api/data_waktu_tunggu.php',
            type: 'GET',
            data: { tgl_awal: tk_awal, tgl_akhir: tk_akhir, kd_pj: penjab, keyword: search },
            dataType: 'json',
            success: function(res) {
                _tatResponseData = res;
                // Update KPI Cards
                $('#kpi-dp').text(res.summary.avg_daftar_periksa);
                $('#kpi-ro').text(res.summary.avg_resep_obat);
                $('#kpi-total').text(res.summary.avg_total_tat);
                $('#kpi-kasir').text(res.summary.avg_total_kasir);
                $('#countPasien').text(res.summary.jml_pasien + ' Pasien');

                // Update Table
                tableTAT.clear();
                tableTAT.rows.add(res.data);
                tableTAT.draw();

                renderChart(res.chart);
            },
            error: function(err) {
                alert("Gagal memuat data Waktu Tunggu Pelayanan.");
                console.error(err);
            }
        });
    }

    function renderChart(chartData) {
        if (lineChartInstance) { lineChartInstance.destroy(); }
        
        var ctxLine = document.getElementById('lineChartTAT').getContext('2d');
        lineChartInstance = new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Daftar -> Periksa (Poli)',
                        data: chartData.data_daftar_periksa,
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        pointBackgroundColor: '#4e73df',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Resep -> Selesai Obat (Farmasi)',
                        data: chartData.data_resep_obat,
                        borderColor: '#1cc88a',
                        backgroundColor: 'rgba(28, 200, 138, 0.1)',
                        pointBackgroundColor: '#1cc88a',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Daftar -> Keluar Kasir (Total Keluar RS)',
                        data: chartData.data_total_kasir,
                        borderColor: '#f6c23e',
                        backgroundColor: 'rgba(246, 194, 62, 0.1)',
                        pointBackgroundColor: '#f6c23e',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + ' Menit';
                            }
                        }
                    },
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Rata-rata TAT (Menit)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // --- AI TAT ADVISOR JS PIPELINE ---
    var currentTatReportContext = "";
    var tatChatHistoryData = [];
    const defaultTatPromptText = "Anda adalah Konsultan Alur Layanan Pasien & Efisiensi Operasional RS (TAT Advisor). Analisis data rata-rata waktu tunggu pelayanan (TAT) berikut (mencakup rata-rata durasi Pendaftaran->Poli, Resep->Obat, dan Daftar->Kasir) dan buatlah Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:\n1. Penilaian Kecepatan Pelayanan (apakah rata-rata waktu tunggu di Poli & Farmasi memenuhi standar SPM Kemenkes).\n2. Identifikasi Bottleneck (tunjukkan penjamin, poliklinik, atau hari tertentu yang mengalami penumpukan antrean terlama).\n3. Rekomendasi Aksi Taktis (saran perbaikan alokasi dokter, pengaturan antrean farmasi, atau digitalisasi layanan untuk mempercepat TAT).";

    function resetTatPrompt() {
        $('#aiTatPrompt').val(defaultTatPromptText);
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

    $(document).on('click', '#btnAnalyzeTat', function() {
        if (!_tatResponseData) {
            alert('Silakan cari dan tampilkan data terlebih dahulu.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
        $('#aiTatReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-info mb-2"></div><div class="small text-muted">AI sedang menganalisis alur layanan waktu tunggu...</div></div>');

        // Batasi detail data per pasien maks 50 baris untuk efisiensi token analisis
        var samplePasien = (_tatResponseData.data || []).map(function(p) {
            return {
                tgl: p.tgl_registrasi,
                no_rm: p.no_rkm_medis,
                pasien: p.nm_pasien,
                penjamin: p.png_jawab,
                poli: p.nm_poli,
                jam_reg: p.jam_reg,
                jam_periksa: p.jam_periksa,
                durasi_poli: p.durasi_dp,
                jam_selesai_obat: p.jam_selesai_obat,
                durasi_resep: p.durasi_ro,
                jam_kasir: p.jam_kasir,
                durasi_total: p.durasi_kasir
            };
        });

        var tatRawData = {
            periode: $('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val(),
            penjamin: $('#kd_pj option:selected').text(),
            summary: _tatResponseData.summary || {},
            sample_data_pasien: samplePasien
        };

        var formData = new URLSearchParams();
        formData.append('action', 'batch_summary');
        formData.append('raw_data', JSON.stringify([tatRawData]));
        formData.append('custom_prompt', $('#aiTatPrompt').val().trim());
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
            const aiThinkingContainer = document.getElementById('aiTatReportContainer');
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
                                $('#aiTatReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullText += data.choices[0].delta.content;
                                $('#aiTatReportContainer').html(parseMarkdownToHtml(fullText));
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

            if (!isError && fullText) {
                currentTatReportContext = fullText;
                tatChatHistoryData = [];
                $('#tatChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>');
            }
        }).catch(err => {
            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
            $('#aiTatReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
        });
    });

    $(document).on('submit', '#tatChatForm', function(e) {
        e.preventDefault();
        const input = $('#tatChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentTatReportContext) return;

        if (tatChatHistoryData.length === 0) {
            $('#tatChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#tatChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
            '</div>'
        );
        $('#tatChatHistory').scrollTop($('#tatChatHistory')[0].scrollHeight);

        input.val('');
        $('#tatChatInput, #btnSendTatChat').prop('disabled', true);

        var replyId = 'tat_reply_' + Date.now();
        $('#tatChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI TAT Assistant</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#tatChatHistory').scrollTop($('#tatChatHistory')[0].scrollHeight);

        var samplePasien = (_tatResponseData.data || []).map(function(p) {
            return {
                tgl: p.tgl_registrasi,
                no_rm: p.no_rkm_medis,
                pasien: p.nm_pasien,
                penjamin: p.png_jawab,
                poli: p.nm_poli,
                jam_reg: p.jam_reg,
                jam_periksa: p.jam_periksa,
                durasi_poli: p.durasi_dp,
                jam_selesai_obat: p.jam_selesai_obat,
                durasi_resep: p.durasi_ro,
                jam_kasir: p.jam_kasir,
                durasi_total: p.durasi_kasir
            };
        });

        var tatRawData = {
            periode: $('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val(),
            penjamin: $('#kd_pj option:selected').text(),
            summary: _tatResponseData ? _tatResponseData.summary : {},
            sample_data_pasien: samplePasien
        };

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentTatReportContext);
        chatData.append('raw_data', JSON.stringify([tatRawData]));
        chatData.append('custom_prompt', $('#aiTatPrompt').val().trim());
        chatData.append('history', JSON.stringify(tatChatHistoryData));
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
            const aiThinkingContainer = document.getElementById('aiTatReportContainer');
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
                                $('#tatChatHistory').scrollTop($('#tatChatHistory')[0].scrollHeight);
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            $('#financeChatInput, #btnSendFinanceChat').prop('disabled', false); // fix disable prop
            $('#tatChatInput, #btnSendTatChat').prop('disabled', false);

            if (!isError && fullReply) {
                tatChatHistoryData.push({ role: 'user', content: messageText });
                tatChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#tatChatInput, #btnSendTatChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error koneksi</span>');
        });
    });
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>
