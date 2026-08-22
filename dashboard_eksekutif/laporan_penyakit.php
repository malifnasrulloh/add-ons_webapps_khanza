<?php
/*
 * File: laporan_penyakit.php (UPDATE V5 - FINAL FIX NO RAWAT)
 * - Menampilkan No. Rawat di tabel Modal Detail (Wajib ada).
 * - Memastikan sinkronisasi antara HTML <thead> dan JS Columns.
 */

$page_title = "Laporan 10 Besar Penyakit";
require_once('includes/header.php');
require_once('includes/functions.php');

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01'); 
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
?>

<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title text-primary">Filter & Pencarian</h5>
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" name="tgl_awal" id="tgl_awal" value="<?php echo $tgl_awal; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" name="tgl_akhir" id="tgl_akhir" value="<?php echo $tgl_akhir; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenis Kunjungan</label>
                    <select class="form-select" name="status_lanjut" id="status_lanjut">
                        <option value="">-- Semua --</option>
                        <option value="Ralan">Rawat Jalan</option>
                        <option value="Ranap">Rawat Inap</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100" onclick="loadData()">
                        <i class="fas fa-search me-2"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Grafik Morbiditas (Top 10)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 450px;">
                        <canvas id="chartPenyakit"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tabel Peringkat</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm" id="dataTable" width="100%" cellspacing="0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Penyakit</th>
                                    <th class="text-end">Jml</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (is_ai_active()): ?>
    <!-- AI PENYAKIT ADVISOR CONTAINER -->
    <div class="card bg-dark border-secondary mt-4 shadow-sm mb-4">
        <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Demografi Penyakit AI (Morbiditas Advisor)</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePenyakitPrompt">
                    <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                </button>
                <button id="btnAnalyzePenyakit" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                </button>
            </div>
        </div>
        <div class="card-body text-light">
            <!-- Collapsible Prompt Tuning Area -->
            <div class="collapse mb-3" id="collapsePenyakitPrompt">
                <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                    <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Morbiditas & Demografi):</label>
                    <textarea id="aiPenyakitPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah Ahli Epidemiologi Klinis & Direktur Pelayanan Medis RS. Analisis data morbiditas (10 besar penyakit) berikut (mencakup total kasus, jenis kelamin, serta persebaran umur) dan susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:
1. Analisis Tren Morbiditas (penyakit mana yang paling mendominasi dan implikasi klinisnya).
2. Demografi Pasien (analisis kelompok umur dan jenis kelamin yang rentan terhadap penyakit tertentu).
3. Kesiapan Sumber Daya RS (rekomendasi penyediaan dokter spesialis, alokasi bed rawat inap, atau pengadaan stok obat/alkes pencegahan penyakit musiman).
4. Program Preventif & Edukasi (rekomendasi kampanye kesehatan masyarakat atau skrining dini luar RS).</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Setel prompt khusus ini untuk menyesuaikan gaya laporan epidemiologi penyakit yang dihasilkan AI.</small>
                        <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetPenyakitPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                    </div>
                </div>
            </div>

            <!-- Display Container Output -->
            <div id="aiPenyakitReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                <div class="text-muted small text-center py-4">
                    <i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
                    Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses ringkasan tren demografi penyakit secara otomatis.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Tren morbiditas dianalisis berdasarkan unit rawat dan rentang waktu terpilih.</small>
                <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiPenyakitReportContainer', 'Laporan_Analisis_Demografi_Penyakit_AI.doc')">
                    <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                </button>
            </div>

            <!-- AI Interactive Chat Assistant -->
            <div class="mt-4 pt-3 border-top border-secondary">
                <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Penyakit dengan AI Assistant</h6>
                <div id="penyakitChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
                </div>
                <form id="penyakitChatForm">
                    <div class="input-group input-group-sm">
                        <input type="text" id="penyakitChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail demografi (misal: Apakah penyakit Demam Berdarah dominan di usia anak-anak?)..." required>
                        <button class="btn btn-primary" type="submit" id="btnSendPenyakitChat">
                            <i class="fas fa-paper-plane me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Rincian Pasien: <span id="modalTitlePenyakit" class="fw-bold">...</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="tableDetail" class="table table-striped table-hover table-sm w-100">
                        <thead>
                            <tr>
                                <th>No. Rawat</th>
                                <th>Tgl Reg</th>
                                <th>No. RM</th>
                                <th>Nama Pasien</th>
                                <th>L/P</th>
                                <th>Umur</th>
                                <th>Alamat (Kab/Kec/Kel)</th>
                                <th>Dokter</th>
                                <th>Penjamin</th>
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
<script>
    var myChart; 
    var myTable; 
    var detailTable; 
    var _penyakitResponseData = null;

    $(document).ready(function() {
        // 1. Init Table Summary
        myTable = $('#dataTable').DataTable({
            "responsive": true,
            "dom": 'Bfrtip', 
            "buttons": [
                { extend: 'excelHtml5', className: 'btn btn-success btn-sm', text:'Excel', title: 'Top 10 Penyakit' },
                { extend: 'print', className: 'btn btn-secondary btn-sm', text:'Print' }
            ],
            "columns": [
                { "data": "kode", className: "fw-bold" },
                { "data": "nama" },
                { "data": "jumlah", className: "text-end fw-bold" },
                { 
                    "data": null,
                    className: "text-center",
                    render: function(data, type, row) {
                        // Tombol aksi untuk membuka modal detail
                        return `<button class="btn btn-sm btn-info text-white" onclick="openDetail('${row.kode}', '${row.nama}')"><i class="fas fa-list"></i></button>`;
                    }
                }
            ],
            "order": [[ 2, "desc" ]],
            "pageLength": 10,
            "searching": false, 
            "lengthChange": false
        });

        // 2. Init Table Detail (Di dalam Modal)
        detailTable = $('#tableDetail').DataTable({
            "responsive": true,
            "dom": 'Bfrtip',
            "buttons": [
                { extend: 'excelHtml5', className: 'btn btn-success btn-sm', text: 'Export Excel Detail' }
            ],
            "columns": [
                // PERBAIKAN DISINI: Mapping Kolom harus sesuai urutan <thead>
                { "data": "no_rawat", className: "fw-bold text-primary" }, 
                { "data": "tgl_registrasi" },
                { "data": "no_rkm_medis" },
                { "data": "nm_pasien" },
                { "data": "jk" },
                { "data": "umur" },
                { "data": "alamat_lengkap" },
                { "data": "nm_dokter" },
                { "data": "png_jawab" }
            ],
            "order": [[ 1, "asc" ]], // Urutkan berdasarkan Tanggal Registrasi
            "pageLength": 10
        });

        // Data akan dimuat saat user klik tombol Tampilkan
    });

    function loadData() {
        var tglAwal = $('#tgl_awal').val();
        var tglAkhir = $('#tgl_akhir').val();
        var statusLanjut = $('#status_lanjut').val();

        $.ajax({
            url: 'api/data_top_penyakit.php',
            type: 'GET',
            data: { tgl_awal: tglAwal, tgl_akhir: tglAkhir, status_lanjut: statusLanjut },
            dataType: 'json',
            success: function(response) {
                _penyakitResponseData = response;
                renderChart(response.chart);
                myTable.clear();
                myTable.rows.add(response.table);
                myTable.draw();
            },
            error: function() { alert("Gagal memuat data."); }
        });
    }

    function openDetail(kdPenyakit, nmPenyakit) {
        var tglAwal = $('#tgl_awal').val();
        var tglAkhir = $('#tgl_akhir').val();
        var statusLanjut = $('#status_lanjut').val();

        $('#modalTitlePenyakit').text(kdPenyakit + ' - ' + nmPenyakit);
        $('#modalDetail').modal('show');
        
        // Bersihkan tabel sebelum load baru
        detailTable.clear().draw();
        
        $.ajax({
            url: 'api/data_detail_penyakit.php',
            type: 'GET',
            data: { 
                tgl_awal: tglAwal, 
                tgl_akhir: tglAkhir, 
                status_lanjut: statusLanjut,
                kd_penyakit: kdPenyakit 
            },
            dataType: 'json',
            success: function(response) {
                detailTable.clear();
                if (response.data && response.data.length > 0) {
                    detailTable.rows.add(response.data);
                }
                detailTable.draw();
            },
            error: function() { console.error("Gagal load detail"); }
        });
    }

    function renderChart(chartData) {
        var ctx = document.getElementById("chartPenyakit").getContext('2d');
        if(myChart) myChart.destroy();

        myChart = new Chart(ctx, {
            type: 'bar', 
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: "Jumlah Kasus",
                    data: chartData.data,
                    backgroundColor: '#4e73df',
                    borderColor: '#4e73df',
                    borderWidth: 1,
                    borderRadius: 5
                }],
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: { label: function(context) { return 'Jumlah: ' + context.parsed.x; } }
                    }
                },
                scales: {
                    x: { beginAtZero: true, grid: { display: true, drawBorder: false } },
                    y: { grid: { display: false } }
                }
            }
        });
    }

    // --- AI PENYAKIT ADVISOR JS PIPELINE ---
    var currentPenyakitReportContext = "";
    var penyakitChatHistoryData = [];
    const defaultPenyakitPromptText = "Anda adalah Ahli Epidemiologi Klinis & Direktur Pelayanan Medis RS. Analisis data morbiditas (10 besar penyakit) berikut (mencakup total kasus, jenis kelamin, serta persebaran umur) dan susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:\n1. Analisis Tren Morbiditas (penyakit mana yang paling mendominasi dan implikasi klinisnya).\n2. Demografi Pasien (analisis kelompok umur dan jenis kelamin yang rentan terhadap penyakit tertentu).\n3. Kesiapan Sumber Daya RS (rekomendasi penyediaan dokter spesialis, alokasi bed rawat inap, atau pengadaan stok obat/alkes pencegahan penyakit musiman).\n4. Program Preventif & Edukasi (rekomendasi kampanye kesehatan masyarakat atau skrining dini luar RS).";

    function resetPenyakitPrompt() {
        $('#aiPenyakitPrompt').val(defaultPenyakitPromptText);
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

    $(document).on('click', '#btnAnalyzePenyakit', function() {
        if (!_penyakitResponseData) {
            alert('Silakan tampilkan data penyakit terlebih dahulu.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
        $('#aiPenyakitReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-info mb-2"></div><div class="small text-muted">AI sedang menganalisis demografi penyakit...</div></div>');

        var penyakitRawData = {
            periode: $('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val(),
            jenis_kunjungan: $('#status_lanjut option:selected').text(),
            top_10_penyakit: _penyakitResponseData.table || [],
            grafik_morbiditas: _penyakitResponseData.chart || {}
        };

        var formData = new URLSearchParams();
        formData.append('action', 'batch_summary');
        formData.append('raw_data', JSON.stringify([penyakitRawData]));
        formData.append('custom_prompt', $('#aiPenyakitPrompt').val().trim());
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
            const aiThinkingContainer = document.getElementById('aiPenyakitReportContainer');
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
                                $('#aiPenyakitReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullText += data.choices[0].delta.content;
                                $('#aiPenyakitReportContainer').html(parseMarkdownToHtml(fullText));
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

            if (!isError && fullText) {
                currentPenyakitReportContext = fullText;
                penyakitChatHistoryData = [];
                $('#penyakitChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>');
            }
        }).catch(err => {
            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
            $('#aiPenyakitReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
        });
    });

    $(document).on('submit', '#penyakitChatForm', function(e) {
        e.preventDefault();
        const input = $('#penyakitChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentPenyakitReportContext) return;

        if (penyakitChatHistoryData.length === 0) {
            $('#penyakitChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#penyakitChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
            '</div>'
        );
        $('#penyakitChatHistory').scrollTop($('#penyakitChatHistory')[0].scrollHeight);

        input.val('');
        $('#penyakitChatInput, #btnSendPenyakitChat').prop('disabled', true);

        var replyId = 'penyakit_reply_' + Date.now();
        $('#penyakitChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI Morbiditas Assistant</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#penyakitChatHistory').scrollTop($('#penyakitChatHistory')[0].scrollHeight);

        var penyakitRawData = {
            summary: _penyakitResponseData ? { total_penyakit: _penyakitResponseData.table.length } : {}
        };

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentPenyakitReportContext);
        chatData.append('raw_data', JSON.stringify([penyakitRawData]));
        chatData.append('custom_prompt', $('#aiPenyakitPrompt').val().trim());
        chatData.append('history', JSON.stringify(penyakitChatHistoryData));
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
            const aiThinkingContainer = document.getElementById('aiPenyakitReportContainer');
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
                                $('#penyakitChatHistory').scrollTop($('#penyakitChatHistory')[0].scrollHeight);
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            $('#penyakitChatInput, #btnSendPenyakitChat').prop('disabled', false);

            if (!isError && fullReply) {
                penyakitChatHistoryData.push({ role: 'user', content: messageText });
                penyakitChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#penyakitChatInput, #btnSendPenyakitChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error koneksi</span>');
        });
    });
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>