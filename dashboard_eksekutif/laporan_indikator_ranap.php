<?php
/*
 * File: laporan_indikator_ranap.php (UPDATE V2)
 * - Tab Global: Data agregat RS (Exclude Pindah Kamar).
 * - Tab Bangsal: Data per ruang (Include Pindah Kamar).
 */

$page_title = "Indikator Pelayanan Rawat Inap";
require_once('includes/header.php');
require_once('includes/functions.php');

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01'); 
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
?>

<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title text-primary">Filter Periode Laporan</h5>
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" name="tgl_awal" id="tgl_awal" value="<?php echo $tgl_awal; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" name="tgl_akhir" id="tgl_akhir" value="<?php echo $tgl_akhir; ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100" onclick="loadAllData()">
                        <i class="fas fa-search me-2"></i> Hitung
                    </button>
                </div>
            </form>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="global-tab" data-bs-toggle="tab" data-bs-target="#global" type="button" role="tab">Laporan Global (RS)</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="bangsal-tab" data-bs-toggle="tab" data-bs-target="#bangsal" type="button" role="tab">Per Bangsal</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="kelas-tab" data-bs-toggle="tab" data-bs-target="#kelas" type="button" role="tab">Per Kelas</button>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">
        
        <div class="tab-pane fade show active" id="global" role="tabpanel">
            <div class="alert alert-info shadow-sm">
                <h6 class="fw-bold"><i class="fas fa-info-circle me-2"></i>Data Dasar Perhitungan (RS):</h6>
                <div class="row text-center" id="data-dasar-container">
                    <div class="col">Loading...</div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-start border-4 border-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">BOR (Occupancy)</div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800" id="val-bor">...</div>
                                    <small class="text-muted">Target: 60-85%</small>
                                </div>
                                <div class="col-auto"><i class="fas fa-bed fa-2x text-gray-300"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-start border-4 border-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">ALOS (Length of Stay)</div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800" id="val-alos">...</div>
                                    <small class="text-muted">Target: 6-9 Hari</small>
                                </div>
                                <div class="col-auto"><i class="fas fa-clock fa-2x text-gray-300"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-start border-4 border-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">TOI (Turn Over Interval)</div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800" id="val-toi">...</div>
                                    <small class="text-muted">Target: 1-3 Hari</small>
                                </div>
                                <div class="col-auto"><i class="fas fa-sync-alt fa-2x text-gray-300"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-start border-4 border-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">BTO (Bed Turn Over)</div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800" id="val-bto">...</div>
                                    <small class="text-muted">Target: 40-50 Kali/Th</small>
                                </div>
                                <div class="col-auto"><i class="fas fa-people-arrows fa-2x text-gray-300"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-start border-4 border-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">NDR (Net Death Rate)</div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800" id="val-ndr">...</div>
                                    <small class="text-muted">< 25 per 1000</small>
                                </div>
                                <div class="col-auto"><i class="fas fa-heart-broken fa-2x text-gray-300"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-start border-4 border-dark shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">GDR (Gross Death Rate)</div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800" id="val-gdr">...</div>
                                    <small class="text-muted">< 45 per 1000</small>
                                </div>
                                <div class="col-auto"><i class="fas fa-cross fa-2x text-gray-300"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="bangsal" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Catatan:</strong> Untuk perhitungan per bangsal, pasien "Pindah Kamar" dihitung sebagai Pasien Keluar (Discharge) agar perhitungan pemakaian tempat tidur (TOI/BTO) akurat.
                    </div>
                    <div class="table-responsive">
                        <table id="table-bangsal" class="table table-striped table-bordered table-hover" style="width:100%">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nama Bangsal</th>
                                    <th class="text-center">Bed (TT)</th>
                                    <th class="text-center">Hari Rawat (HP)</th>
                                    <th class="text-center">Pasien Keluar (D)</th>
                                    <th class="text-center">BOR (%)</th>
                                    <th class="text-center">ALOS</th>
                                    <th class="text-center">TOI</th>
                                    <th class="text-center">BTO</th>
                                    <th class="text-center">NDR</th>
                                    <th class="text-center">GDR</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="kelas" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Catatan:</strong> Laporan ini mengelompokkan indikator berdasarkan kelas kamar dengan kriteria filtering otomatis sebagai berikut:
                        <ul class="mb-0 mt-2">
                            <li><strong>Bed Bayi:</strong> Terdeteksi jika nama bangsal mengandung kata <code>'bayi'</code> atau <code>'box bayi'</code>.</li>
                            <li><strong>Isolasi:</strong> Terdeteksi jika nama bangsal mengandung kata <code>'isolasi'</code>.</li>
                            <li><strong>Intensive:</strong> Terdeteksi jika nama bangsal mengandung kata <code>'ICU'</code> atau <code>'HCU'</code> (NICU, PICU, etc).</li>
                            <li><strong>Prioritas:</strong> Sistem menggunakan logika <em>mutually exclusive</em> dengan urutan prioritas: Bed Bayi > Isolasi > Intensive > Enum Kelas Kamar. Hal ini menjamin tidak terjadi dobel hitung antar kategori.</li>
                        </ul>
                    </div>
                    <div class="table-responsive">
                        <table id="table-kelas" class="table table-striped table-bordered table-hover" style="width:100%">
                            <thead class="table-dark">
                                <tr>
                                    <th>Grup / Kelas Kamar</th>
                                    <th class="text-center">Bed (TT)</th>
                                    <th class="text-center">Hari Rawat (HP)</th>
                                    <th class="text-center">Pasien Keluar (D)</th>
                                    <th class="text-center">BOR (%)</th>
                                    <th class="text-center">ALOS</th>
                                    <th class="text-center">TOI</th>
                                    <th class="text-center">BTO</th>
                                    <th class="text-center">NDR</th>
                                    <th class="text-center">GDR</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
		
		<?php if (is_ai_active()): ?>
		<!-- AI OPERATIONS ANALYZER CONTAINER -->
		<div class="card bg-dark border-secondary mt-4 shadow-sm mb-4">
			<div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2">
				<span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Kinerja Operasional Rawat Inap (AI Operations Advisor)</span>
				<div class="d-flex gap-2">
					<button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRanapPrompt">
						<i class="fas fa-sliders-h me-1"></i> Tune Prompt
					</button>
					<button id="btnAnalyzeRanap" class="btn btn-sm btn-success fw-bold">
						<i class="fas fa-magic me-1"></i> Jalankan Analisis AI
					</button>
				</div>
			</div>
			<div class="card-body text-light">
				<!-- Collapsible Prompt Tuning Area -->
				<div class="collapse mb-3" id="collapseRanapPrompt">
					<div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
						<label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Rawat Inap):</label>
						<textarea id="aiRanapPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah Konsultan Tata Kelola Rumah Sakit & Ahli Operasional Layanan Rawat Inap. Analisis indikator BOR, ALOS, TOI, BTO, NDR, dan GDR berikut (secara global, per bangsal, dan per kelas kamar) dan susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:
1. Penilaian Kinerja Operasional Rawat Inap (apakah BOR, ALOS, TOI berada di rentang ideal Barber Johnson).
2. Identifikasi Bottleneck/Bangsal dengan utilisasi kritis (terlalu tinggi yang berisiko burnout, atau terlalu rendah yang merugi).
3. Analisis Kualitas Medis & Risiko Klinis (interpretasi NDR/GDR dan jika ada bangsal/kelas dengan tingkat kematian tinggi).
4. Rekomendasi Aksi Taktis Manajemen Bed & Alokasi Staf bagi Direktur RS.</textarea>
						<div class="d-flex justify-content-between align-items-center mt-2">
							<small class="text-muted">Setel prompt khusus ini untuk menyesuaikan gaya laporan operasional yang dihasilkan AI.</small>
							<button class="btn btn-xs btn-outline-warning text-warning" onclick="resetRanapPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
						</div>
					</div>
				</div>

				<!-- Display Container Output -->
				<div id="aiRanapReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
					<div class="text-muted small text-center py-4">
						<i class="fas fa-robot fa-2x mb-2 text-primary d-block"></i>
						Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses ringkasan kinerja operasional rawat inap secara otomatis.
					</div>
				</div>

				<div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
					<small class="text-muted"><i class="fas fa-info-circle me-1"></i> Data operasional dianalisis berdasarkan periode filter terpilih.</small>
					<button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiRanapReportContainer', 'Laporan_Analisis_Operasional_Ranap_AI.doc')">
						<i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
					</button>
				</div>

				<!-- AI Interactive Chat Assistant -->
				<div class="mt-4 pt-3 border-top border-secondary">
					<h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Kinerja Ranap dengan AI Assistant</h6>
					<div id="ranapChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
						<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
					</div>
					<form id="ranapChatForm">
						<div class="input-group input-group-sm">
							<input type="text" id="ranapChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail indikator (misal: Mengapa BOR bangsal Melati sangat rendah?)..." required>
							<button class="btn btn-primary" type="submit" id="btnSendRanapChat">
								<i class="fas fa-paper-plane me-1"></i> Kirim
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Kamus Indikator (Referensi)</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-sm text-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Indikator</th>
                                <th>Rumus</th>
                                <th>Nilai Ideal (Barber Johnson)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>BOR</td>
                                <td>(Hari Perawatan / (Tempat Tidur x Periode Hari)) x 100</td>
                                <td>60 - 85 %</td>
                            </tr>
                            <tr>
                                <td>ALOS</td>
                                <td>Hari Perawatan / Pasien Keluar (Hidup + Mati)</td>
                                <td>6 - 9 Hari</td>
                            </tr>
                            <tr>
                                <td>TOI</td>
                                <td>((Tempat Tidur x Periode) - Hari Perawatan) / Pasien Keluar</td>
                                <td>1 - 3 Hari</td>
                            </tr>
                            <tr>
                                <td>BTO</td>
                                <td>Pasien Keluar / Tempat Tidur</td>
                                <td>40 - 50 Kali / Tahun</td>
                            </tr>
                            <tr>
                                <td>NDR</td>
                                <td>(Meninggal > 48 Jam / Pasien Keluar) x 1000</td>
                                <td>< 25 per 1000</td>
                            </tr>
                            <tr>
                                <td>GDR</td>
                                <td>(Total Meninggal / Pasien Keluar) x 1000</td>
                                <td>< 45 per 1000</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<?php ob_start(); ?>
<script>
    var tableBangsal;
    var tableKelas;
    var _globalIndicatorData = null;
    var _bangsalIndicatorData = null;
    var _kelasIndicatorData = null;

    $(document).ready(function() {
        // Init DataTables untuk Bangsal
        tableBangsal = $('#table-bangsal').DataTable({
            "responsive": true,
            "dom": 'Bfrtip',
            "buttons": [
                { extend: 'excelHtml5', className: 'btn btn-success btn-sm', title: 'Laporan Indikator Per Bangsal' },
                { extend: 'pdfHtml5', className: 'btn btn-danger btn-sm', title: 'Laporan Indikator Per Bangsal' },
                { extend: 'print', className: 'btn btn-secondary btn-sm' }
            ],
            "columns": [
                { "data": "bangsal" },
                { "data": "bed", className: "text-center" },
                { "data": "hp", className: "text-center" },
                { "data": "d", className: "text-center" },
                { "data": "bor", className: "text-center fw-bold" },
                { "data": "alos", className: "text-center" },
                { "data": "toi", className: "text-center" },
                { "data": "bto", className: "text-center" },
                { "data": "ndr", className: "text-center" },
                { "data": "gdr", className: "text-center" }
            ]
        });

        // Init DataTables untuk Kelas
        tableKelas = $('#table-kelas').DataTable({
            "responsive": true,
            "dom": 'Bfrtip',
            "buttons": [
                { extend: 'excelHtml5', className: 'btn btn-success btn-sm', title: 'Laporan Indikator Per Kelas' },
                { extend: 'pdfHtml5', className: 'btn btn-danger btn-sm', title: 'Laporan Indikator Per Kelas' },
                { extend: 'print', className: 'btn btn-secondary btn-sm' }
            ],
            "columns": [
                { "data": "kelas" },
                { "data": "bed", className: "text-center" },
                { "data": "hp", className: "text-center" },
                { "data": "d", className: "text-center" },
                { "data": "bor", className: "text-center fw-bold" },
                { "data": "alos", className: "text-center" },
                { "data": "toi", className: "text-center" },
                { "data": "bto", className: "text-center" },
                { "data": "ndr", className: "text-center" },
                { "data": "gdr", className: "text-center" }
            ]
        });

        // Load data pertama kali
        loadAllData();
    });

    function loadAllData() {
        var tglAwal = $('#tgl_awal').val();
        var tglAkhir = $('#tgl_akhir').val();

        // 1. Load Data Global
        $('#data-dasar-container').html('<div class="col">Sedang menghitung...</div>');
        $.ajax({
            url: 'api/data_indikator_ranap.php',
            type: 'GET',
            data: { tgl_awal: tglAwal, tgl_akhir: tglAkhir },
            dataType: 'json',
            success: function(response) {
                _globalIndicatorData = response;
                updateUIGlobal(response);
            },
            error: function() { $('#data-dasar-container').html('<div class="col text-danger">Gagal memuat data global.</div>'); }
        });

        // 2. Load Data Per Bangsal
        $.ajax({
            url: 'api/data_indikator_per_bangsal.php',
            type: 'GET',
            data: { tgl_awal: tglAwal, tgl_akhir: tglAkhir },
            dataType: 'json',
            success: function(response) {
                _bangsalIndicatorData = response.data;
                tableBangsal.clear();
                tableBangsal.rows.add(response.data);
                tableBangsal.draw();
            },
            error: function() { console.error("Gagal memuat data bangsal"); }
        });

        // 3. Load Data Per Kelas
        $.ajax({
            url: 'api/data_indikator_per_kelas.php',
            type: 'GET',
            data: { tgl_awal: tglAwal, tgl_akhir: tglAkhir },
            dataType: 'json',
            success: function(response) {
                _kelasIndicatorData = response.data;
                tableKelas.clear();
                tableKelas.rows.add(response.data);
                tableKelas.draw();
            },
            error: function() { console.error("Gagal memuat data kelas"); }
        });
    }

    function updateUIGlobal(data) {
        var d = data.data_dasar;
        var htmlDasar = `
            <div class="col-md-2 col-6 mb-2"><div class="fw-bold text-primary">${d.jumlah_bed}</div><small>Tempat Tidur</small></div>
            <div class="col-md-2 col-6 mb-2"><div class="fw-bold text-success">${d.hari_perawatan}</div><small>Hari Perawatan</small></div>
            <div class="col-md-2 col-6 mb-2"><div class="fw-bold text-info">${d.pasien_keluar}</div><small>Pasien Keluar</small></div>
            <div class="col-md-2 col-6 mb-2"><div class="fw-bold text-danger">${d.pasien_mati}</div><small>Mati (GDR)</small></div>
            <div class="col-md-2 col-6 mb-2"><div class="fw-bold text-dark">${d.pasien_mati_48}</div><small>Mati >48h (NDR)</small></div>
            <div class="col-md-2 col-6 mb-2"><div class="fw-bold text-secondary">${data.periode.hari}</div><small>Periode (Hari)</small></div>
        `;
        $('#data-dasar-container').html(htmlDasar);

        var i = data.indikator;
        $('#val-bor').text(i.bor + ' %');
        $('#val-alos').text(i.alos + ' Hari');
        $('#val-toi').text(i.toi + ' Hari');
        $('#val-bto').text(i.bto + ' Kali');
        $('#val-ndr').text(i.ndr + ' ‰');
        $('#val-gdr').text(i.gdr + ' ‰');
    }

    // --- AI OPERATIONS ADVISOR JS PIPELINE ---
    var currentRanapReportContext = "";
    var ranapChatHistoryData = [];
    const defaultRanapPromptText = "Anda adalah Konsultan Tata Kelola Rumah Sakit & Ahli Operasional Layanan Rawat Inap. Analisis indikator BOR, ALOS, TOI, BTO, NDR, dan GDR berikut (secara global, per bangsal, dan per kelas kamar) dan susun Laporan Naratif Eksekutif dalam Bahasa Indonesia yang berfokus pada:\n1. Penilaian Kinerja Operasional Rawat Inap (apakah BOR, ALOS, TOI berada di rentang ideal Barber Johnson).\n2. Identifikasi Bottleneck/Bangsal dengan utilisasi kritis (terlalu tinggi yang berisiko burnout, atau terlalu rendah yang merugi).\n3. Analisis Kualitas Medis & Risiko Klinis (interpretasi NDR/GDR dan jika ada bangsal/kelas dengan tingkat kematian tinggi).\n4. Rekomendasi Aksi Taktis Manajemen Bed & Alokasi Staf bagi Direktur RS.";

    function resetRanapPrompt() {
        $('#aiRanapPrompt').val(defaultRanapPromptText);
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

    $(document).on('click', '#btnAnalyzeRanap', function() {
        if (!_globalIndicatorData) {
            alert('Silakan tunggu data indikator dimuat terlebih dahulu.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
        $('#aiRanapReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-info mb-2"></div><div class="small text-muted">AI sedang menganalisis indikator ranap...</div></div>');

        var ranapRawData = {
            periode: $('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val(),
            global: _globalIndicatorData,
            bangsal: _bangsalIndicatorData || [],
            kelas: _kelasIndicatorData || []
        };

        var formData = new URLSearchParams();
        formData.append('action', 'batch_summary');
        formData.append('raw_data', JSON.stringify([ranapRawData]));
        formData.append('custom_prompt', $('#aiRanapPrompt').val().trim());
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
            const aiThinkingContainer = document.getElementById('aiRanapReportContainer');
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
                                $('#aiRanapReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                            }
                            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                fullText += data.choices[0].delta.content;
                                $('#aiRanapReportContainer').html(parseMarkdownToHtml(fullText));
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

            if (!isError && fullText) {
                currentRanapReportContext = fullText;
                ranapChatHistoryData = [];
                $('#ranapChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan operasional di atas...</div>');
            }
        }).catch(err => {
            btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
            $('#aiRanapReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
        });
    });

    $(document).on('submit', '#ranapChatForm', function(e) {
        e.preventDefault();
        const input = $('#ranapChatInput');
        const messageText = input.val().trim();
        if (!messageText || !currentRanapReportContext) return;

        if (ranapChatHistoryData.length === 0) {
            $('#ranapChatHistory').empty();
        }

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        $('#ranapChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-primary border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
            '</div>'
        );
        $('#ranapChatHistory').scrollTop($('#ranapChatHistory')[0].scrollHeight);

        input.val('');
        $('#ranapChatInput, #btnSendRanapChat').prop('disabled', true);

        var replyId = 'ranap_reply_' + Date.now();
        $('#ranapChatHistory').append(
            '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                '<div class="d-flex justify-content-between mb-1">' +
                    '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI Operations Assistant</span>' +
                    '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                '</div>' +
                '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
            '</div>'
        );
        $('#ranapChatHistory').scrollTop($('#ranapChatHistory')[0].scrollHeight);

        var ranapRawData = {
            global: _globalIndicatorData || {},
            bangsal: _bangsalIndicatorData || []
        };

        var chatData = new URLSearchParams();
        chatData.append('action', 'chat_discuss');
        chatData.append('message', messageText);
        chatData.append('report_context', currentRanapReportContext);
        chatData.append('raw_data', JSON.stringify([ranapRawData]));
        chatData.append('custom_prompt', $('#aiRanapPrompt').val().trim());
        chatData.append('history', JSON.stringify(ranapChatHistoryData));
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
            const aiThinkingContainer = document.getElementById('aiRanapReportContainer');
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
                                $('#ranapChatHistory').scrollTop($('#ranapChatHistory')[0].scrollHeight);
                            }
                        } catch(e) {}
                    } else if (line.startsWith('event: error')) {
                        isError = true;
                    }
                }
            }

            $('#ranapChatInput, #btnSendRanapChat').prop('disabled', false);

            if (!isError && fullReply) {
                ranapChatHistoryData.push({ role: 'user', content: messageText });
                ranapChatHistoryData.push({ role: 'assistant', content: fullReply });
            }
        }).catch(err => {
            $('#ranapChatInput, #btnSendRanapChat').prop('disabled', false);
            $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error koneksi</span>');
        });
    });
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>