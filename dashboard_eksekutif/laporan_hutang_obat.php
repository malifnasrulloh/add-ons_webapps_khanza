<?php
$page_title = "Laporan Hutang Obat Belum Lunas";
require_once('includes/header.php');
require_once('includes/functions.php');
?>

<style>
    .widget-icon {
        opacity: 0.3;
        transition: transform 0.3s ease;
    }
    .card:hover .widget-icon {
        transform: scale(1.1);
        opacity: 0.5;
    }
    /* Warna Row DataTables Custom */
    .table-warning-soft { background-color: #fff3cd !important; }
    .table-danger-soft { background-color: #f8d7da !important; }
</style>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-file-invoice-dollar text-primary"></i> Dashboard Hutang Penjualan Farmasi</h1>
        <button class="btn btn-sm btn-primary shadow-sm" onclick="loadData()"><i class="fas fa-sync-alt fa-sm text-white-50"></i> Refresh Data</button>
    </div>

    <!-- Widget Cards KPI -->
    <div class="row">
        <!-- Total Sisa Hutang Keseluruhan -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Sisa Hutang</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-total-hutang">Loading...</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-check-alt fa-2x text-gray-800 widget-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Faktur Belum Lunas -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Faktur Belum Lunas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-total-faktur">...</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-receipt fa-2x text-gray-800 widget-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menunggu Jatuh Tempo -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Menunggu Tempo</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-menunggu-tempo">...</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hourglass-half fa-2x text-gray-800 widget-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Telat Jatuh Tempo (Lewat) -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Lewat Tempo (Telat)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-lewat-tempo">...</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-800 widget-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Proyeksi Hutang per Bulan Jatuh Tempo</h6>
                </div>
                <div class="card-body">
                    <canvas id="barChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Proporsi Sisa Hutang per Suplier</h6>
                </div>
                <div class="card-body">
                    <canvas id="pieChart" style="min-height: 300px;max-height: 300px; display: block; margin: 0 auto;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <?php if (is_ai_active()): ?>
    <!-- AI HUTANG ADVISOR CONTAINER -->
    <div class="card bg-dark border-secondary mt-4 shadow-sm mb-4">
        <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Proyeksi & Arus Kas Hutang AI (CFO Advisor)</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHutangPrompt">
                    <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                </button>
                <button id="btnAnalyzeHutang" class="btn btn-sm btn-success fw-bold" onclick="analyzeHutangData()">
                    <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                </button>
            </div>
        </div>
        <div class="card-body text-light">
            <!-- Collapsible Prompt Tuning Area -->
            <div class="collapse mb-3" id="collapseHutangPrompt">
                <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                    <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Hutang & Arus Kas):</label>
                    <textarea id="aiHutangPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah Konsultan Keuangan Rumah Sakit (CFO) & Auditor Keuangan Senior. Analisis data faktur obat belum lunas, lalu susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:
1. Beban Jatuh Tempo Terkini: Analisis total nominal yang mendekati atau telah lewat jatuh tempo.
2. Prioritas Pembayaran: Daftar suplier utama dengan tunggakan terbesar dan faktur penting yang harus segera dilunasi.
3. Rekomendasi Cash Flow: Strategi alokasi dana dan negosiasi perpanjangan tempo/cicilan suplier untuk menjaga likuiditas RS.
4. Mitigasi Risiko: Tindakan preventif agar tidak terjadi kekosongan stok obat akibat pemblokiran kiriman oleh suplier yang hutangnya tertunggak.</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Setel prompt khusus ini untuk menyesuaikan arah analisis keuangan Anda.</small>
                        <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetHutangPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                    </div>
                </div>
            </div>

            <!-- Display Container Output -->
            <div id="aiHutangReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                <div class="text-muted small text-center py-4">
                    <i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
                    Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses proyeksi risiko hutang dan arus kas farmasi secara otomatis.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Analisis didasarkan pada sisa hutang dan tanggal jatuh tempo faktur saat ini.</small>
                <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiHutangReportContainer', 'Laporan_Analisis_Hutang_Obat_AI.doc')">
                    <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                </button>
            </div>

            <!-- AI Interactive Chat Assistant -->
            <div class="mt-4 pt-3 border-top border-secondary">
                <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Hutang dengan AI Assistant</h6>
                <div id="hutangChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
                </div>
                <form id="hutangChatForm">
                    <div class="input-group input-group-sm">
                        <input type="text" id="hutangChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail (misal: Suplier mana yang memiliki jatuh tempo terdekat dengan nominal terbesar?)..." required>
                        <button class="btn btn-primary" type="submit" id="btnSendHutangChat">
                            <i class="fas fa-paper-plane me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- DataTables Rincian Faktur -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Data Faktur Obat Belum Lunas</h6>
            <span class="badge bg-danger">Merah: Sudah Jatuh Tempo</span>
            <span class="badge bg-warning text-dark">Kuning: Sisa Tempo < 7 Hari</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTableHutang" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th>Aksi</th>
                            <th>No. Faktur</th>
                            <th>No. Order</th>
                            <th>Tgl. Pesan</th>
                            <th>Jatuh Tempo</th>
                            <th>Suplier</th>
                            <th>Nama Petugas</th>
                            <th class="text-end">Total Tagihan</th>
                            <th class="text-end">Telah Dibayar</th>
                            <th class="text-end">Sisa Hutang</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL DETAIL FAKTUR OBAT ===== -->
<div class="modal fade" id="modalDetailFaktur" tabindex="-1" aria-labelledby="modalDetailFakturLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDetailFakturLabel">
                    <i class="fas fa-file-medical-alt me-2"></i>
                    Rincian Faktur: <span id="modalNoFaktur">-</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Info Header Faktur -->
                <div class="row mb-3" id="detailFakturInfo">
                    <div class="col-md-4"><small class="text-muted">Suplier</small><p class="fw-bold mb-1" id="dSuplier">-</p></div>
                    <div class="col-md-2"><small class="text-muted">Tgl. Pesan</small><p class="fw-bold mb-1" id="dTglPesan">-</p></div>
                    <div class="col-md-2"><small class="text-muted">Jatuh Tempo</small><p class="fw-bold mb-1" id="dTglTempo">-</p></div>
                    <div class="col-md-2"><small class="text-muted">Petugas</small><p class="fw-bold mb-1" id="dPetugas">-</p></div>
                    <div class="col-md-2"><small class="text-muted">Lokasi</small><p class="fw-bold mb-1" id="dBangsal">-</p></div>
                </div>
                <!-- Tabel Rincian Item -->
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped" id="tblDetailItems">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama Obat / Barang</th>
                                <th>Kode</th>
                                <th class="text-center">Jumlah</th>
                                <th>Satuan</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Diskon</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="bodyDetailItems">
                            <tr><td colspan="8" class="text-center text-muted">Memuat data...</td></tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="7" class="text-end">Total Tagihan Faktur:</td>
                                <td class="text-end text-primary" id="dTotalTagihan">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>
<!-- ===== END MODAL DETAIL FAKTUR ===== -->

<?php ob_start(); ?>

<script>
    var tableHutang;
    var barChartInstance = null;
    var pieChartInstance = null;

    const formatRupiah = (angka) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    };

    const parseDbDate = (dateStr) => {
        return new Date(dateStr);
    };

    $(document).ready(function() {
        tableHutang = $('#dataTableHutang').DataTable({
            "responsive": true,
            "pageLength": 10,
            "order": [[ 3, "asc" ]], // Urutkan berdasarkan Jatuh Tempo duluan
            "dom": 'Bfrtip',
            "buttons": [
                {
                    extend: 'excelHtml5',
                    title: 'Laporan Hutang Obat Belum Lunas',
                    className: 'btn btn-success btn-sm',
                    exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9] } // Skip kolom Aksi (index 0)
                },
                {
                    extend: 'print',
                    title: 'Laporan Hutang Obat Belum Lunas',
                    className: 'btn btn-secondary btn-sm',
                    exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9] } // Skip kolom Aksi (index 0)
                }
            ],
            "columns": [
                {
                    "data": "no_faktur",
                    "orderable": false,
                    "className": "text-center",
                    render: function(data) {
                        return '<button class="btn btn-info btn-sm py-0 px-2" onclick="lihatDetail(\'' + data + '\')" title="Lihat Rincian Obat">'
                            + '<i class="fas fa-boxes me-1"></i>Detail</button>';
                    }
                },
                { "data": "no_faktur" },
                { "data": "no_order" },
                { "data": "tgl_pesan" },
                { "data": "tgl_tempo", "className": "fw-bold" },
                { "data": "nama_suplier" },
                { "data": "nama_petugas" },
                { "data": "tagihan_val",    "className": "text-end", render: function(data) { return formatRupiah(data); } },
                { "data": "cicilan_val",    "className": "text-end", render: function(data) { return formatRupiah(data); } },
                { "data": "sisa_hutang_val","className": "text-end fw-bold", render: function(data) { return formatRupiah(data); } }
            ],
            "order": [[ 4, "asc" ]],  // Urutkan berdasarkan Jatuh Tempo (sekarang index 4)
            "createdRow": function(row, data, dataIndex) {
                let today = new Date();
                today.setHours(0,0,0,0);
                let tglTempo = parseDbDate(data.tgl_tempo);
                const diffTime = tglTempo - today;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                if (diffDays < 0) {
                    $(row).addClass('table-danger-soft');
                } else if (diffDays <= 7) {
                    $(row).addClass('table-warning-soft');
                }
            }
        });

        // Data akan dimuat saat user klik tombol Refresh Data
    });

    // ==========================================
    //  FUNGSI: Lihat Detail Rincian Obat Faktur
    // ==========================================
    function lihatDetail(noFaktur) {
        // Reset dan tampilkan modal
        $('#modalNoFaktur').text(noFaktur);
        $('#dSuplier, #dTglPesan, #dTglTempo, #dPetugas, #dBangsal').text('-');
        $('#dTotalTagihan').text('-');
        $('#bodyDetailItems').html('<tr><td colspan="8" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat rincian item...</td></tr>');
        var modal = new bootstrap.Modal(document.getElementById('modalDetailFaktur'));
        modal.show();

        // Fetch data rincian dari API
        $.ajax({
            url: 'api/data_detail_faktur_obat.php',
            type: 'GET',
            data: { no_faktur: noFaktur },
            dataType: 'json',
            success: function(res) {
                // Isi info header faktur
                if (res.faktur_info) {
                    var fi = res.faktur_info;
                    $('#dSuplier').text(fi.nama_suplier || '-');
                    $('#dTglPesan').text(fi.tgl_pesan || '-');
                    $('#dTglTempo').text(fi.tgl_tempo || '-');
                    $('#dPetugas').text(fi.nama_petugas || '-');
                    $('#dBangsal').text(fi.nm_bangsal || '-');
                    $('#dTotalTagihan').text(formatRupiah(parseFloat(fi.tagihan) || 0));
                }

                // Isi tabel item
                var tbody = '';
                if (res.items && res.items.length > 0) {
                    $.each(res.items, function(i, item) {
                        var diskonText = item.diskon > 0 ? formatRupiah(item.diskon) : '<span class="text-muted">-</span>';
                        tbody += '<tr>'
                            + '<td class="text-center">' + (i + 1) + '</td>'
                            + '<td><strong>' + item.nama_brng + '</strong></td>'
                            + '<td><small class="text-muted">' + item.kode_brng + '</small></td>'
                            + '<td class="text-center">' + item.jumlah + '</td>'
                            + '<td>' + (item.satuan || '-') + '</td>'
                            + '<td class="text-end">' + formatRupiah(item.h_beli) + '</td>'
                            + '<td class="text-end">' + diskonText + '</td>'
                            + '<td class="text-end fw-bold text-primary">' + formatRupiah(item.subtotal) + '</td>'
                            + '</tr>';
                    });
                } else {
                    tbody = '<tr><td colspan="8" class="text-center text-muted"><i class="fas fa-inbox me-2"></i>Tidak ada item ditemukan untuk faktur ini.</td></tr>';
                }
                $('#bodyDetailItems').html(tbody);
            },
            error: function() {
                $('#bodyDetailItems').html('<tr><td colspan="8" class="text-center text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Gagal memuat rincian faktur.</td></tr>');
            }
        });
    }

    function loadData() {
        $.ajax({
            url: 'api/data_hutang_obat.php',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                // Update KPI
                $('#kpi-total-hutang').text(formatRupiah(res.summary.total_sisa_hutang));
                $('#kpi-total-faktur').text(res.summary.total_faktur);
                $('#kpi-menunggu-tempo').text(formatRupiah(res.summary.menunggu_tempo));
                $('#kpi-lewat-tempo').text(formatRupiah(res.summary.lewat_tempo));

                // Update DataTables
                tableHutang.clear();
                tableHutang.rows.add(res.data);
                tableHutang.draw();

                // Generate Charts
                renderCharts(res.chart);
            },
            error: function(err) {
                console.error(err);
                alert("Gagal memuat data laporan hutang.");
            }
        });
    }

    function renderCharts(chartData) {
        // --- Bar Chart (Bulan Jatuh Tempo) ---
        if (barChartInstance) { barChartInstance.destroy(); }
        var ctxBar = document.getElementById('barChart').getContext('2d');
        barChartInstance = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: chartData.tempo_bulan.labels,
                datasets: [{
                    label: 'Proyeksi Beban Biaya (Rp)',
                    data: chartData.tempo_bulan.data,
                    backgroundColor: 'rgba(78, 115, 223, 0.7)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' ' + formatRupiah(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000) + ' Jt';
                                return value;
                            }
                        }
                    }
                }
            }
        });

        // --- Pie Chart (Proporsi Suplier) ---
        // Sort and get top 5 suplier, others merged to "Lainnya"
        let sortedSupliers = chartData.suplier.sort((a,b) => b.value - a.value);
        let topSupliers = sortedSupliers;
        let othersValue = sortedSupliers.slice(5).reduce((acc, curr) => acc + curr.value, 0);
        
        if (othersValue > 0) {
            topSupliers.push({name: 'Lainnya', value: othersValue});
        }

        let pieLabels = topSupliers.map(s => s.name);
        let pieData = topSupliers.map(s => s.value);
        
        // Colors palette
        let pieColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'];

        if (pieChartInstance) { pieChartInstance.destroy(); }
        var ctxPie = document.getElementById('pieChart').getContext('2d');
        pieChartInstance = new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieData,
                    backgroundColor: pieColors,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) { label += ': '; }
                                label += formatRupiah(context.raw);
                                return label;
                            }
                        }
                    }
                },
                cutout: '60%' // donut style
            }
        });
    }

    <?php if(is_ai_active()): ?>
    var hutangChatHistoryData = [];
    var currentHutangReportContext = "";
    const defaultHutangPrompt = "Anda adalah Konsultan Keuangan Rumah Sakit (CFO) & Auditor Keuangan Senior. Analisis data faktur obat belum lunas, lalu susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:\n1. Beban Jatuh Tempo Terkini: Analisis total nominal yang mendekati atau telah lewat jatuh tempo.\n2. Prioritas Pembayaran: Daftar suplier utama dengan tunggakan terbesar dan faktur penting yang harus segera dilunasi.\n3. Rekomendasi Cash Flow: Strategi alokasi dana dan negosiasi perpanjangan tempo/cicilan suplier untuk menjaga likuiditas RS.\n4. Mitigasi Risiko: Tindakan preventif agar tidak terjadi kekosongan stok obat akibat pemblokiran kiriman oleh suplier yang hutangnya tertunggak.";

    function resetHutangPrompt() {
        $('#aiHutangPrompt').val(defaultHutangPrompt);
    }

    async function analyzeHutangData() {
        const btn = document.getElementById('btnAnalyzeHutang');
        const responseDiv = document.getElementById('aiHutangReportContainer');
        const customPrompt = document.getElementById('aiHutangPrompt').value;

        if (!tableHutang || !tableHutang.data().any()) {
            Swal.fire('Data Kosong', 'Tidak ada data hutang untuk dianalisis. Silakan klik Refresh Data terlebih dahulu.', 'info');
            return;
        }

        // Kumpulkan data chart/KPI untuk konteks
        const totalHutang = $('#kpi-total-hutang').text();
        const totalFaktur = $('#kpi-total-faktur').text();
        const lewatTempo = $('#kpi-lewat-tempo').text();
        
        currentHutangReportContext = "Laporan Hutang Obat Belum Lunas.\n" +
                                     "Total Hutang Keseluruhan: " + totalHutang + "\n" +
                                     "Total Faktur: " + totalFaktur + "\n" +
                                     "Lewat Jatuh Tempo (Telat): " + lewatTempo + "\n\nData Rincian:\n";

        // Ambil data tabel
        let dtData = tableHutang.data().toArray();
        dtData.sort((a,b) => b.sisa_hutang_val - a.sisa_hutang_val);
        let sampleData = dtData; // Batasi 30 data terbesar agar tidak memotong respons token LLM (Anti-Truncation)

        sampleData.forEach(row => {
            currentHutangReportContext += `- Faktur ${row.no_faktur} dari ${row.nama_suplier}. Jatuh tempo: ${row.tgl_tempo}. Sisa Hutang: ${formatRupiah(row.sisa_hutang_val)}\n`;
        });

        // UI Updates
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...';
        responseDiv.innerHTML = '<div class="d-flex align-items-center"><div class="spinner-border spinner-border-sm text-info me-2"></div><em>AI sedang mempelajari data hutang Anda...</em></div>';

        // Panggil API Gateway secara streaming
        try {
            var requestData = new URLSearchParams();
            requestData.append('action', 'batch_summary');
            requestData.append('raw_data', JSON.stringify(sampleData));
            requestData.append('custom_prompt', customPrompt);
            requestData.append('stream', '1');

            const response = await fetch('api/ai_analyzer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: requestData
            });

            if (!response.ok) throw new Error('Network response was not ok');

            const reader = response.body.getReader();
            const decoder = new TextDecoder('utf-8');
            let aiText = '';
            let buffer = "";

            responseDiv.innerHTML = ''; // bersihkan div

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                
                buffer += decoder.decode(value, { stream: true });
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

                    if (line.startsWith('data: ')) {
                        const jsonStr = line.substring(6).trim();
                        if (jsonStr === '[DONE]') continue;
                        
                        try {
                            const chunk = JSON.parse(jsonStr);
                            if (chunk.choices && chunk.choices.length > 0 && chunk.choices[0].delta && chunk.choices[0].delta.content) {
                                aiText += chunk.choices[0].delta.content;
                            } else if (chunk.candidates && chunk.candidates.length > 0 && chunk.candidates[0].content && chunk.candidates[0].content.parts && chunk.candidates[0].content.parts[0].text) {
                                aiText += chunk.candidates[0].content.parts[0].text;
                            }
                            
                            if (typeof parseMarkdownBody === 'function') {
                                responseDiv.innerHTML = parseMarkdownBody(aiText);
                            } else {
                                responseDiv.innerText = aiText;
                            }
                        } catch (e) {}
                    } else if (line.trim().startsWith('{"status":"error"')) {
                        try {
                            const errData = JSON.parse(line.trim());
                            responseDiv.innerHTML = '<div class="alert alert-danger">' + errData.message + '</div>';
                        } catch(e) {}
                    }
                }
            }
        } catch (error) {
            console.error('AI Error:', error);
            responseDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Gagal menghubungi AI Server.</div>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-magic me-1"></i> Jalankan Analisis AI';
        }
    }

    // Handle Chat Discussion
    $(document).on('submit', '#hutangChatForm', function(e) {
        e.preventDefault();
        const input = $('#hutangChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentHutangReportContext) {
            if (!currentHutangReportContext) {
                Swal.fire('Analisis Diperlukan', 'Harap jalankan analisis awal terlebih dahulu sebelum memulai diskusi.', 'warning');
            }
            return;
        }

        if (hutangChatHistoryData.length === 0) {
            $('#hutangChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#hutangChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + messageText + '</div>' +
            '</div>'
        );
        $('#hutangChatHistory').scrollTop($('#hutangChatHistory')[0].scrollHeight);

        input.val('');
        $('#hutangChatInput, #btnSendHutangChat').prop('disabled', true);

        var replyId = 'hutang_reply_' + Date.now();
        $('#hutangChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI CFO Assistant</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#hutangChatHistory').scrollTop($('#hutangChatHistory')[0].scrollHeight);

        let sampleData = [];
        if (tableHutang && tableHutang.data().any()) {
            let dtData = tableHutang.data().toArray();
            dtData.sort((a,b) => b.sisa_hutang_val - a.sisa_hutang_val);
            sampleData = dtData;
        }

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentHutangReportContext);
        chatData.append('raw_data', JSON.stringify(sampleData));
        chatData.append('custom_prompt', $('#aiHutangPrompt').val().trim());
        chatData.append('history', JSON.stringify(hutangChatHistoryData));
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
            const aiThinkingContainer = document.getElementById('aiHutangReportContainer');
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
                                if (typeof parseMarkdownBody === 'function') {
                                    $('#' + replyId).html(parseMarkdownBody(fullReply));
                                } else {
                                    $('#' + replyId).text(fullReply);
                                }
                                $('#hutangChatHistory').scrollTop($('#hutangChatHistory')[0].scrollHeight);
                            }
                        } catch(e) {}
                    }
                }
            }

            $('#hutangChatInput, #btnSendHutangChat').prop('disabled', false);

            if (!isError && fullReply) {
                hutangChatHistoryData.push({ role: 'user', content: messageText });
                hutangChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#hutangChatInput, #btnSendHutangChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error koneksi</span>');
        });
    });
    <?php endif; ?>
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>
