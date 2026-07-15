<?php
/*
 * File: laporan_kinerja_dokter.php (UPDATE V2)
 * - Fitur Baru: Modal Detail Pasien per Dokter.
 * - Fitur Baru: Export Data Detail (Excel/PDF).
 * - Menampilkan total pendapatan billing yang dihasilkan dokter tersebut.
 */

$page_title = "Laporan Kinerja Dokter";
require_once('includes/header.php');
require_once('includes/functions.php');

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01'); 
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// Ambil List Penjamin untuk Dropdown
$penjab_list = [];
$sql_pj = "SELECT kd_pj, png_jawab FROM penjab WHERE status = '1' ORDER BY png_jawab";
$res_pj = $koneksi->query($sql_pj);
if ($res_pj) {
    while($row = $res_pj->fetch_assoc()) {
        $penjab_list[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title text-primary">Filter Periode & Penjamin</h5>
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
                    <label class="form-label">Penjamin</label>
                    <select class="form-select" name="kd_pj" id="kd_pj">
                        <option value="">- Semua Penjamin -</option>
                        <?php foreach($penjab_list as $pj): ?>
                            <option value="<?php echo $pj['kd_pj']; ?>"><?php echo $pj['png_jawab']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100" onclick="loadData()">
                        <i class="fas fa-search me-2"></i> Tampilkan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Grafik Volume Pelayanan Dokter (Top 15)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 400px;">
                        <canvas id="chartDokter"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Rincian Kinerja Seluruh Dokter</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nama Dokter</th>
                                    <th class="text-center">Jml Pasien Ralan</th>
                                    <th class="text-center">Jml Pasien Ranap</th>
                                    <th class="text-center">Total Volume</th>
                                    <th class="text-end">Total Billing</th>
                                    <th class="text-center" width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php if (is_ai_active()): ?>
        <!-- AI DOKTER ADVISOR CONTAINER -->
        <div class="col-lg-12 mb-4">
            <div class="card bg-dark border-secondary shadow-sm">
                <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
                    <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Kinerja Dokter AI (SDM Medis Advisor)</span>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDokterPrompt">
                            <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                        </button>
                        <button id="btnAnalyzeDokter" class="btn btn-sm btn-success fw-bold">
                            <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                        </button>
                    </div>
                </div>
                <div class="card-body text-light">
                    <!-- Collapsible Prompt Tuning Area -->
                    <div class="collapse mb-3" id="collapseDokterPrompt">
                        <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                            <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Kinerja Dokter):</label>
                            <textarea id="aiDokterPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah Konsultan Manajemen SDM Medis & Analis Kinerja Klinis RS. Analisis data volume pelayanan dokter (rawat jalan vs rawat inap) dan kontribusi billing pendapatan dokter berikut dan susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:
1. Produktivitas SDM Medis (identifikasi dokter paling produktif/load kerja tinggi dan dokter dengan volume terendah).
2. Kontribusi Pendapatan (evaluasi dokter dengan kontribusi pendapatan terbesar terhadap billing RS).
3. Analisis Beban Kerja & Risiko Kelelahan (identifikasi beban kerja tidak merata antar dokter spesialis sejenis).
4. Rekomendasi Alokasi Jadwal & Insentif Dokter bagi Direktur RS.</textarea>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted">Setel prompt khusus ini untuk menyesuaikan gaya laporan kinerja SDM yang dihasilkan AI.</small>
                                <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetDokterPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                            </div>
                        </div>
                    </div>

                    <!-- Display Container Output -->
                    <div id="aiDokterReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                        <div class="text-muted small text-center py-4">
                            <i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
                            Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses ringkasan kinerja SDM dokter secara otomatis.
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Kinerja dokter dianalisis berdasarkan penjamin dan rentang filter terpilih.</small>
                        <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiDokterReportContainer', 'Laporan_Analisis_Kinerja_Dokter_AI.doc')">
                            <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                        </button>
                    </div>

                    <!-- AI Interactive Chat Assistant -->
                    <div class="mt-4 pt-3 border-top border-secondary">
                        <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi SDM Medis dengan AI Assistant</h6>
                        <div id="dokterChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                            <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
                        </div>
                        <form id="dokterChatForm">
                            <div class="input-group input-group-sm">
                                <input type="text" id="dokterChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail kinerja (misal: Siapa dokter spesialis anak dengan volume tertinggi?)..." required>
                                <button class="btn btn-primary" type="submit" id="btnSendDokterChat">
                                    <i class="fas fa-paper-plane me-1"></i> Kirim
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-user-md me-2"></i>Rincian Pasien: <span id="modalTitleDokter" class="fw-bold">...</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="tableDetail" class="table table-striped table-hover table-sm w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu Registrasi</th>
                                <th>Waktu Tutup Billing</th>
                                <th>No. Rawat</th>
                                <th>No. RM</th>
                                <th>Nama Pasien</th>
                                <th>Status</th>
                                <th>Penjamin</th>
                                <th class="text-end">Total Billing (Rp)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="7" class="text-end">Total Pendapatan:</th>
                                <th class="text-end" id="totalPendapatanDokter">0</th>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<?php ob_start(); ?>
<script>
    var myChart; 
    var myTable; 
    var detailTable;

    // Helper format rupiah
    function formatMoney(amount) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
    }

    $(document).ready(function() {
        // 1. Init Main Table
        myTable = $('#dataTable').DataTable({
            "responsive": true,
            "columns": [
                { "data": "nm_dokter", "className": "fw-bold" },
                { "data": "ralan", "className": "text-center" },
                { "data": "ranap", "className": "text-center" },
                { "data": "total", "className": "text-center fw-bold" },
                { "data": "billing", "className": "text-end text-primary fw-bold", render: function(data) { return formatMoney(data); } },
                { 
                  "data": null, 
                  "className": "text-center",
                  "orderable": false,
                  render: function(data, type, row) {
                      return '<button class="btn btn-sm btn-info" onclick="openDetail(\''+row.kd_dokter+'\', \''+row.nm_dokter.replace(/'/g, "\\'")+'\')"><i class="fas fa-eye me-1"></i>Detail</button>';
                  }
                }
            ],
            "order": [[4, "desc"]], // Urutkan dari billing terbesar
            "pageLength": 10
        });

        detailTable = $('#detailTable').DataTable({
            "responsive": true,
            "dom": 'Bfrtip',
            "buttons": [
                { extend: 'excelHtml5', className: 'btn btn-success btn-sm', title: 'Detail Pendapatan Dokter' },
                { extend: 'pdfHtml5', className: 'btn btn-danger btn-sm', title: 'Detail Pendapatan Dokter' }
            ],
            "columns": [
                { "data": "tgl_registrasi" },
                { "data": "no_rawat" },
                { "data": "no_rkm_medis" },
                { "data": "nm_pasien", "className": "fw-bold" },
                { 
                  "data": "status_lanjut", 
                  "className": "text-center",
                  render: function(data) {
                      return data === 'Ralan' ? '<span class="badge bg-success">Ralan</span>' : '<span class="badge bg-warning text-dark">Ranap</span>';
                  }
                },
                { "data": "penjamin" },
                { "data": "total", className: "text-end fw-bold", render: function(data) { return formatMoney(data); } }
            ],
            "pageLength": 10,
            "footerCallback": function (row, data, start, end, display) {
                var api = this.api();
                // Hitung total pendapatan dari data yang tampil
                var total = api.column(7, { page: 'current' }).data().reduce(function (a, b) {
                    return parseFloat(a) + parseFloat(b);
                }, 0);
                // Update footer
                $('#totalPendapatanDokter').html(formatMoney(total));
            }
        });

        // Data akan dimuat saat user klik tombol Tampilkan
    });

    function loadData() {
        var tglAwal = $('#tgl_awal').val();
        var tglAkhir = $('#tgl_akhir').val();
        var kdPj = $('#kd_pj').val();

        $.ajax({
            url: 'api/data_kinerja_dokter.php',
            type: 'GET',
            data: { tgl_awal: tglAwal, tgl_akhir: tglAkhir, kd_pj: kdPj },
            dataType: 'json',
            success: function(response) {
                _dokterResponseData = response;
                renderChart(response.chart);
                
                myTable.clear();
                myTable.rows.add(response.table);
                myTable.draw();
            },
            error: function() { alert("Gagal memuat data."); }
        });
    }

    function openDetail(kdDokter, nmDokter) {
        var tglAwal = $('#tgl_awal').val();
        var tglAkhir = $('#tgl_akhir').val();
        var kdPj = $('#kd_pj').val();

        $('#modalTitleDokter').text(nmDokter);
        $('#modalDetail').modal('show');
        
        detailTable.clear().draw(); // Kosongkan dulu
        
        $.ajax({
            url: 'api/data_detail_kinerja_dokter.php',
            type: 'GET',
            data: { 
                tgl_awal: tglAwal, 
                tgl_akhir: tglAkhir, 
                kd_dokter: kdDokter,
                kd_pj: kdPj
            },
            dataType: 'json',
            success: function(response) {
                detailTable.clear();
                if (response.data && response.data.length > 0) {
                    detailTable.rows.add(response.data);
                }
                detailTable.draw();
                
                // Update Total Global di footer (bukan per page, tapi total semua data)
                var totalSemua = response.data.reduce(function(a, b) {
                    return a + parseFloat(b.total);
                }, 0);
                $('#totalPendapatanDokter').html(formatMoney(totalSemua));
            },
            error: function() { console.error("Gagal load detail"); }
        });
    }

    function renderChart(chartData) {
        var ctx = document.getElementById("chartDokter").getContext('2d');
        if(myChart) myChart.destroy();

        myChart = new Chart(ctx, {
            type: 'bar', 
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: "Rawat Jalan",
                        data: chartData.ralan,
                        backgroundColor: '#1cc88a',
                        hoverBackgroundColor: '#17a673',
                    },
                    {
                        label: "Rawat Inap",
                        data: chartData.ranap,
                        backgroundColor: '#f6c23e',
                        hoverBackgroundColor: '#dda20a',
                    }
                ],
            },
            options: {
                maintainAspectRatio: false,
                layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            }
        });
    }

    // --- AI DOKTER ADVISOR JS PIPELINE ---
    var currentDokterReportContext = "";
    var dokterChatHistoryData = [];
    const defaultDokterPromptText = "Anda adalah Konsultan Manajemen SDM Medis & Analis Kinerja Klinis RS. Analisis data volume pelayanan dokter (rawat jalan vs rawat inap) dan kontribusi billing pendapatan dokter berikut dan susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:\n1. Produktivitas SDM Medis (identifikasi dokter paling produktif/load kerja tinggi dan dokter dengan volume terendah).\n2. Kontribusi Pendapatan (evaluasi dokter dengan kontribusi pendapatan terbesar terhadap billing RS).\n3. Analisis Beban Kerja & Risiko Kelelahan (identifikasi beban kerja tidak merata antar dokter spesialis sejenis).\n4. Rekomendasi Alokasi Jadwal & Insentif Dokter bagi Direktur RS.";

    function resetDokterPrompt() {
        $('#aiDokterPrompt').val(defaultDokterPromptText);
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

    $(document).on('click', '#btnAnalyzeDokter', function() {
        if (!_dokterResponseData) {
            alert('Silakan tampilkan data kinerja dokter terlebih dahulu.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
        $('#aiDokterReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-info mb-2"></div><div class="small text-muted">AI sedang menganalisis kinerja SDM dokter...</div></div>');

        // Batasi sample data tabel 40 dokter tertinggi
        var sampleTable = (_dokterResponseData.table || []).map(function(d) {
            return {
                nama: d.nm_dokter,
                ralan: d.ralan,
                ranap: d.ranap,
                total_pasien: d.total,
                billing: d.billing
            };
        });

        var dokterRawData = {
            periode: $('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val(),
            penjamin: $('#kd_pj option:selected').text(),
            top_15_grafik: _dokterResponseData.chart || {},
            sample_tabel_dokter: sampleTable
        };

        var formData = new URLSearchParams();
        formData.append('action', 'batch_summary');
        formData.append('raw_data', JSON.stringify([dokterRawData]));
        formData.append('custom_prompt', $('#aiDokterPrompt').val().trim());
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
            const aiThinkingContainer = document.getElementById('aiDokterReportContainer');
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
                                $('#aiDokterReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullText += data.choices[0].delta.content;
                                $('#aiDokterReportContainer').html(parseMarkdownToHtml(fullText));
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

            if (!isError && fullText) {
                currentDokterReportContext = fullText;
                dokterChatHistoryData = [];
                $('#dokterChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>');
            }
        }).catch(err => {
            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
            $('#aiDokterReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
        });
    });

    $(document).on('submit', '#dokterChatForm', function(e) {
        e.preventDefault();
        const input = $('#dokterChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentDokterReportContext) return;

        if (dokterChatHistoryData.length === 0) {
            $('#dokterChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#dokterChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
            '</div>'
        );
        $('#dokterChatHistory').scrollTop($('#dokterChatHistory')[0].scrollHeight);

        input.val('');
        $('#dokterChatInput, #btnSendDokterChat').prop('disabled', true);

        var replyId = 'dokter_reply_' + Date.now();
        $('#dokterChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI SDM Assistant</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#dokterChatHistory').scrollTop($('#dokterChatHistory')[0].scrollHeight);

        var dokterRawData = {
            summary: _dokterResponseData ? { total_dokter: _dokterResponseData.table.length } : {}
        };

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentDokterReportContext);
        chatData.append('raw_data', JSON.stringify([dokterRawData]));
        chatData.append('custom_prompt', $('#aiDokterPrompt').val().trim());
        chatData.append('history', JSON.stringify(dokterChatHistoryData));
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
            const aiThinkingContainer = document.getElementById('aiDokterReportContainer');
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
                                $('#dokterChatHistory').scrollTop($('#dokterChatHistory')[0].scrollHeight);
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            $('#dokterChatInput, #btnSendDokterChat').prop('disabled', false);

            if (!isError && fullReply) {
                dokterChatHistoryData.push({ role: 'user', content: messageText });
                dokterChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#dokterChatInput, #btnSendDokterChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error koneksi</span>');
        });
    });
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>