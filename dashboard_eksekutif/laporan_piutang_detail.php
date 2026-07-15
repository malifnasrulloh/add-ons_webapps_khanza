<?php
/*
 * File laporan_piutang_detail.php (PERBAIKAN ACTION FORM)
 * Menampilkan tabel detail transaksi PIUTANG per shift.
 * - Menggunakan Accordion & Table Responsive
 * - Action Form sudah diarahkan ke file ini sendiri
 * PHP 7.3 compatible.
 */

// 1. Set Judul & Sertakan Header
$page_title = "Laporan Detail Piutang per Shift";
require_once('includes/header.php');
require_once('includes/functions.php');

// 2. Ambil Parameter
$tgl_awal = isset($_GET['tgl_awal']) ? htmlspecialchars($_GET['tgl_awal']) : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? htmlspecialchars($_GET['tgl_akhir']) : date('Y-m-d');

// 3. Ambil Info Shift
$shift_times = getShiftTimes($koneksi);
if (empty($shift_times)) {
    die("Error: Data 'closing_kasir' tidak ditemukan.");
}

// 4. Siapkan Kueri SQL (KHUSUS PIUTANG)
// Perbedaan utama dengan laporan_detail.php adalah penggunaan "IN" pada subquery piutang_pasien

// Kueri Ralan (Piutang)
$sql_ralan = "
    SELECT 
        reg_periksa.no_rawat, nota_jalan.no_nota, pasien.nm_pasien, 
        nota_jalan.tanggal, nota_jalan.jam, dokter.nm_dokter, penjab.png_jawab,
        piutang_pasien.totalpiutang AS total_rupiah
    FROM reg_periksa 
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis 
    INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj 
    INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter 
    INNER JOIN nota_jalan ON reg_periksa.no_rawat = nota_jalan.no_rawat 
    INNER JOIN piutang_pasien ON reg_periksa.no_rawat = piutang_pasien.no_rawat
    WHERE reg_periksa.status_lanjut = 'Ralan' 
        AND CONCAT(nota_jalan.tanggal, ' ', nota_jalan.jam) BETWEEN ? AND ? 
    ORDER BY nota_jalan.tanggal, nota_jalan.jam
";
$stmt_ralan = $koneksi->prepare($sql_ralan);

// Kueri Ranap (Piutang)
$sql_ranap = "
    SELECT 
        reg_periksa.no_rawat, nota_inap.no_nota, pasien.nm_pasien, 
        nota_inap.tanggal, nota_inap.jam, penjab.png_jawab,
        piutang_pasien.totalpiutang AS total_rupiah,
        COALESCE(
            (SELECT dokter.nm_dokter 
             FROM dpjp_ranap 
             INNER JOIN dokter ON dpjp_ranap.kd_dokter = dokter.kd_dokter 
             WHERE dpjp_ranap.no_rawat = reg_periksa.no_rawat 
             LIMIT 1),
            (SELECT dokter.nm_dokter 
             FROM dokter 
             WHERE dokter.kd_dokter = reg_periksa.kd_dokter)
        ) AS dokter_dpjp
    FROM reg_periksa 
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis 
    INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj 
    INNER JOIN nota_inap ON reg_periksa.no_rawat = nota_inap.no_rawat 
    INNER JOIN piutang_pasien ON reg_periksa.no_rawat = piutang_pasien.no_rawat
    WHERE reg_periksa.status_lanjut = 'Ranap' 
        AND CONCAT(nota_inap.tanggal, ' ', nota_inap.jam) BETWEEN ? AND ? 
    ORDER BY nota_inap.tanggal, nota_inap.jam
";
$stmt_ranap = $koneksi->prepare($sql_ranap);

// Kueri Pemasukan Lain & Pengeluaran (Biasanya tunai, tapi kita biarkan ada untuk kelengkapan shift)
$sql_pemasukan = "SELECT pemasukan_lain.tanggal, pemasukan_lain.keterangan, pemasukan_lain.besar, kategori_pemasukan_lain.nama_kategori FROM pemasukan_lain INNER JOIN kategori_pemasukan_lain ON pemasukan_lain.kode_kategori = kategori_pemasukan_lain.kode_kategori WHERE pemasukan_lain.tanggal BETWEEN ? AND ? ORDER BY pemasukan_lain.tanggal";
$stmt_pemasukan = $koneksi->prepare($sql_pemasukan);

$sql_pengeluaran = "SELECT pengeluaran_harian.tanggal, pengeluaran_harian.keterangan, pengeluaran_harian.biaya, kategori_pengeluaran_harian.nama_kategori FROM pengeluaran_harian INNER JOIN kategori_pengeluaran_harian ON pengeluaran_harian.kode_kategori = kategori_pengeluaran_harian.kode_kategori WHERE pengeluaran_harian.tanggal BETWEEN ? AND ? ORDER BY pengeluaran_harian.tanggal";
$stmt_pengeluaran = $koneksi->prepare($sql_pengeluaran);

if (!$stmt_ralan || !$stmt_ranap || !$stmt_pemasukan || !$stmt_pengeluaran) {
    die("Gagal mempersiapkan kueri SQL: " . $koneksi->error);
}

// --- KUERI AGREGAT HARIAN UNTUK SUMMARY ---
$sql_sum_ralan = "
    SELECT SUM(piutang_pasien.totalpiutang) AS total_harian
    FROM reg_periksa 
    INNER JOIN nota_jalan ON reg_periksa.no_rawat = nota_jalan.no_rawat 
    INNER JOIN piutang_pasien ON reg_periksa.no_rawat = piutang_pasien.no_rawat
    WHERE reg_periksa.status_lanjut = 'Ralan' 
        AND nota_jalan.tanggal = ?
";
$stmt_sum_ralan = $koneksi->prepare($sql_sum_ralan);

$sql_sum_ranap = "
    SELECT SUM(piutang_pasien.totalpiutang) AS total_harian
    FROM reg_periksa 
    INNER JOIN nota_inap ON reg_periksa.no_rawat = nota_inap.no_rawat 
    INNER JOIN piutang_pasien ON reg_periksa.no_rawat = piutang_pasien.no_rawat
    WHERE reg_periksa.status_lanjut = 'Ranap' 
        AND nota_inap.tanggal = ?
";
$stmt_sum_ranap = $koneksi->prepare($sql_sum_ranap);

// Untuk pemasukan dan pengeluaran tunai tetap sama
$sql_sum_pemasukan = "SELECT SUM(besar) AS total_harian FROM pemasukan_lain WHERE tanggal = ?";
$stmt_sum_pemasukan = $koneksi->prepare($sql_sum_pemasukan);

$sql_sum_pengeluaran = "SELECT SUM(biaya) AS total_harian FROM pengeluaran_harian WHERE tanggal = ?";
$stmt_sum_pengeluaran = $koneksi->prepare($sql_sum_pengeluaran);

// Array penampung data lengkap piutang untuk LLM context
$all_piutang_data = [];

// 5. Siapkan Loop Tanggal
$start_date = new DateTime($tgl_awal);
$end_date = new DateTime($tgl_akhir);
$end_date->modify('+1 day'); 
$interval = new DateInterval('P1D');
$date_range = new DatePeriod($start_date, $interval, $end_date);
?>

<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title text-warning">Filter Laporan Detail Piutang</h5>
            
            <!-- 
            =============================================================================
            PERBAIKAN DI SINI: Action diarahkan ke laporan_piutang_detail.php
            =============================================================================
            -->
            <form action="laporan_piutang_detail.php" method="GET" class="row g-3">
                <div class="col-md-5">
                    <label for="tgl_awal" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" name="tgl_awal" id="tgl_awal" value="<?php echo $tgl_awal; ?>">
                </div>
                <div class="col-md-5">
                    <label for="tgl_akhir" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" name="tgl_akhir" id="tgl_akhir" value="<?php echo $tgl_akhir; ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-warning w-100 text-white">Tampilkan</button>
                </div>
            </form>
            
        </div>
    </div>

    <?php if (is_ai_active()): ?>
    <!-- AI PIUTANG ANALYZER CONTAINER -->
    <div class="card bg-dark border-secondary mt-4 shadow-sm mb-4">
        <div class="card-header bg-gradient bg-warning text-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><i class="fas fa-brain me-2"></i>Analisis Umur & Aging Piutang (AI Receivables Advisor)</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePiutangPrompt">
                    <i class="fas fa-sliders-h me-1"></i> Tune Prompt
                </button>
                <button id="btnAnalyzePiutang" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-magic me-1"></i> Jalankan Analisis AI
                </button>
            </div>
        </div>
        <div class="card-body text-light">
            <!-- Collapsible Prompt Tuning Area -->
            <div class="collapse mb-3" id="collapsePiutangPrompt">
                <div class="p-3 rounded border border-secondary bg-black bg-opacity-50">
                    <label class="form-label text-warning small fw-bold">System Prompt (Instruksi Analisis Piutang):</label>
                    <textarea id="aiPiutangPrompt" class="form-control form-control-sm bg-dark text-light border-secondary" rows="4">Anda adalah AI Receivables & Billing Collector Advisor yang ahli dalam mengelola piutang rumah sakit. Analisis data detail piutang per penjamin (seperti BPJS, Asuransi Swasta, Perusahaan) dan umur piutang (Aging Receivables) berikut. Identifikasi piutang macet, klaim pending/dispute lama, serta berikan rekomendasi taktis agar penagihan dana masuk lebih cepat guna memperkuat likuiditas rumah sakit.</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Setel prompt khusus ini untuk menyesuaikan gaya laporan piutang yang dihasilkan AI.</small>
                        <button class="btn btn-xs btn-outline-warning text-warning" onclick="resetPiutangPrompt()"><i class="fas fa-undo me-1"></i>Reset Prompt Default</button>
                    </div>
                </div>
            </div>

            <!-- Display Container Output -->
            <div id="aiPiutangReportContainer" class="p-3 rounded border border-secondary bg-black bg-opacity-25 text-light" style="min-height: 120px; max-height: 500px; overflow-y: auto;">
                <div class="text-muted small text-center py-4">
                    <i class="fas fa-robot fa-2x mb-2 text-warning d-block"></i>
                    Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk memproses ringkasan analisis piutang secara otomatis.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Piutang dianalisis berdasarkan rentang tanggal cutoff terpilih.</small>
                <button class="btn btn-sm btn-outline-info" onclick="exportToWord('aiPiutangReportContainer', 'Laporan_Analisis_Piutang_AI.doc')">
                    <i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)
                </button>
            </div>

            <!-- AI Interactive Chat Assistant -->
            <div class="mt-4 pt-3 border-top border-secondary">
                <h6 class="fw-bold text-info mb-2"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Penagihan Piutang dengan AI Assistant</h6>
                <div id="piutangChatHistory" class="p-3 rounded border border-secondary bg-black bg-opacity-50 mb-2" style="max-height: 300px; overflow-y: auto; min-height: 100px;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>
                </div>
                <form id="piutangChatForm">
                    <div class="input-group input-group-sm">
                        <input type="text" id="piutangChatInput" class="form-control bg-dark text-light border-secondary" placeholder="Tanyakan detail piutang (misal: Mana piutang BPJS dengan nominal terbesar?)..." required>
                        <button class="btn btn-primary" type="submit" id="btnSendPiutangChat">
                            <i class="fas fa-paper-plane me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="accordion" id="accordionTanggal">
        <?php
        if ($date_range):
            $day_index = 0; 
            foreach ($date_range as $tanggal):
                $tanggal_str = $tanggal->format('Y-m-d');
                $day_id = 'hari-' . $tanggal->format('Ymd');
                
                // Ambil summary harian
                $stmt_sum_ralan->bind_param("s", $tanggal_str);
                $stmt_sum_ralan->execute();
                $harian_ralan = $stmt_sum_ralan->get_result()->fetch_assoc()['total_harian'] ?? 0;

                $stmt_sum_ranap->bind_param("s", $tanggal_str);
                $stmt_sum_ranap->execute();
                $harian_ranap = $stmt_sum_ranap->get_result()->fetch_assoc()['total_harian'] ?? 0;

                $stmt_sum_pemasukan->bind_param("s", $tanggal_str);
                $stmt_sum_pemasukan->execute();
                $harian_pemasukan = $stmt_sum_pemasukan->get_result()->fetch_assoc()['total_harian'] ?? 0;

                $stmt_sum_pengeluaran->bind_param("s", $tanggal_str);
                $stmt_sum_pengeluaran->execute();
                $harian_pengeluaran = $stmt_sum_pengeluaran->get_result()->fetch_assoc()['total_harian'] ?? 0;
                
                $grand_total_harian = $harian_ralan + $harian_ranap + $harian_pemasukan - $harian_pengeluaran;
        ?>
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading-<?php echo $day_id; ?>">
                <button class="accordion-button fs-5 fw-bold d-flex align-items-center gap-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $day_id; ?>" aria-expanded="true" aria-controls="collapse-<?php echo $day_id; ?>">
                    <span class="me-auto">Laporan Tanggal: <?php echo $tanggal->format('d-m-Y'); ?></span>
                    <span class="badge bg-primary rounded-pill d-flex align-items-center" style="font-size:1rem;" title="Grand Total Hari Ini" onclick="event.stopPropagation()">
                        Total: <?php echo formatRupiah($grand_total_harian); ?>
                    </span>
                </button>
            </h2>
            <div id="collapse-<?php echo $day_id; ?>" class="accordion-collapse collapse <?php echo ($day_index == 0) ? 'show' : ''; ?>" aria-labelledby="heading-<?php echo $day_id; ?>" data-bs-parent="#accordionTanggal">
                <div class="accordion-body">
                    
                    <div class="accordion" id="accordionShift-<?php echo $day_id; ?>">
                        <?php
                        $shift_index = 0; 
                        foreach ($shift_times as $nama_shift => $times):
                            $shift_id = $day_id . '-shift-' . $shift_index;
                            $range = getShiftDateTimeRange($tanggal_str, $nama_shift, $shift_times);
                            
                            // Eksekusi Kueri
                            $stmt_ralan->bind_param("ss", $range['start'], $range['end']);
                            $stmt_ralan->execute();
                            $result_ralan = $stmt_ralan->get_result();
                            $data_ralan = [];
                            while ($row = $result_ralan->fetch_assoc()) {
                                $data_ralan[] = $row;
                                // Simpan ke array raw data
                                $all_piutang_data[] = [
                                    'tanggal' => $row['tanggal'],
                                    'jam' => $row['jam'],
                                    'tipe' => 'Ralan',
                                    'no_rawat' => $row['no_rawat'],
                                    'no_nota' => $row['no_nota'],
                                    'pasien' => $row['nm_pasien'],
                                    'penjamin' => $row['png_jawab'],
                                    'dokter' => $row['nm_dokter'],
                                    'total_rupiah' => (float)$row['total_rupiah']
                                ];
                            }
                            
                            $stmt_ranap->bind_param("ss", $range['start'], $range['end']);
                            $stmt_ranap->execute();
                            $result_ranap = $stmt_ranap->get_result();
                            $data_ranap = [];
                            while ($row = $result_ranap->fetch_assoc()) {
                                $data_ranap[] = $row;
                                // Simpan ke array raw data
                                $all_piutang_data[] = [
                                    'tanggal' => $row['tanggal'],
                                    'jam' => $row['jam'],
                                    'tipe' => 'Ranap',
                                    'no_rawat' => $row['no_rawat'],
                                    'no_nota' => $row['no_nota'],
                                    'pasien' => $row['nm_pasien'],
                                    'penjamin' => $row['png_jawab'],
                                    'dokter' => $row['dokter_dpjp'],
                                    'total_rupiah' => (float)$row['total_rupiah']
                                ];
                            }

                            $stmt_pemasukan->bind_param("ss", $range['start'], $range['end']);
                            $stmt_pemasukan->execute();
                            $result_pemasukan = $stmt_pemasukan->get_result();
                            $data_pemasukan = [];
                            while ($row = $result_pemasukan->fetch_assoc()) $data_pemasukan[] = $row;

                            $stmt_pengeluaran->bind_param("ss", $range['start'], $range['end']);
                            $stmt_pengeluaran->execute();
                            $result_pengeluaran = $stmt_pengeluaran->get_result();
                            $data_pengeluaran = [];
                            while ($row = $result_pengeluaran->fetch_assoc()) $data_pengeluaran[] = $row;
                            
                            // --- Hitung Summary Total per Shift ---
                            $total_ralan     = array_sum(array_column($data_ralan, 'total_rupiah'));
                            $total_ranap     = array_sum(array_column($data_ranap, 'total_rupiah'));
                            $total_pemasukan = array_sum(array_column($data_pemasukan, 'besar'));
                            $total_pengeluaran = array_sum(array_column($data_pengeluaran, 'biaya'));
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-<?php echo $shift_id; ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $shift_id; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $shift_id; ?>">
                                    Shift <?php echo htmlspecialchars($nama_shift); ?> (<?php echo $range['start'] . ' s/d ' . $range['end']; ?>)
                                </button>
                            </h2>
                            <div class="d-flex flex-wrap gap-2 align-items-center px-3 py-2" style="background:#f0f4f8; border-bottom:1px solid #dee2e6; font-size:0.82rem;">
                                <span style="color:#555; font-weight:600;">Ringkasan Shift:</span>
                                <span style="display:inline-block; background:#0d6efd; color:#fff; border-radius:50px; padding:3px 10px; font-weight:600;">
                                    &#128203; <?php echo count($data_ralan); ?> Ralan &mdash; <?php echo formatRupiah($total_ralan); ?>
                                </span>
                                <span style="display:inline-block; background:#0dcaf0; color:#000; border-radius:50px; padding:3px 10px; font-weight:600;">
                                    &#127916; <?php echo count($data_ranap); ?> Ranap &mdash; <?php echo formatRupiah($total_ranap); ?>
                                </span>
                                <span style="display:inline-block; background:#198754; color:#fff; border-radius:50px; padding:3px 10px; font-weight:600;">
                                    &#43; Lain-lain: <?php echo formatRupiah($total_pemasukan); ?>
                                </span>
                                <span style="display:inline-block; background:#dc3545; color:#fff; border-radius:50px; padding:3px 10px; font-weight:600;">
                                    &minus; Keluar: <?php echo formatRupiah($total_pengeluaran); ?>
                                </span>
                            </div>
                            <div id="collapse-<?php echo $shift_id; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo $shift_id; ?>" data-bs-parent="#accordionShift-<?php echo $day_id; ?>">
                                <div class="accordion-body">
                                    
                                    <ul class="nav nav-tabs" id="tab-<?php echo $shift_id; ?>" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="ralan-tab-<?php echo $shift_id; ?>" data-bs-toggle="tab" data-bs-target="#ralan-<?php echo $shift_id; ?>" type="button">
                                                Ralan (<?php echo count($data_ralan); ?>)
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="ranap-tab-<?php echo $shift_id; ?>" data-bs-toggle="tab" data-bs-target="#ranap-<?php echo $shift_id; ?>" type="button">
                                                Ranap (<?php echo count($data_ranap); ?>)
                                            </button>
                                        </li>
                                        <!-- Tab Lain & Keluar tetap ditampilkan meski biasanya kosong di piutang -->
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="pemasukan-tab-<?php echo $shift_id; ?>" data-bs-toggle="tab" data-bs-target="#pemasukan-<?php echo $shift_id; ?>" type="button">
                                                Lain (<?php echo count($data_pemasukan); ?>)
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="pengeluaran-tab-<?php echo $shift_id; ?>" data-bs-toggle="tab" data-bs-target="#pengeluaran-<?php echo $shift_id; ?>" type="button">
                                                Keluar (<?php echo count($data_pengeluaran); ?>)
                                            </button>
                                        </li>
                                    </ul>
                                    
                                    <div class="tab-content" id="tab-content-<?php echo $shift_id; ?>">
                                        <!-- TAB RALAN -->
                                        <div class="tab-pane fade show active" id="ralan-<?php echo $shift_id; ?>" role="tabpanel">
                                            <div class="card-body border border-top-0 p-3">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-bordered table-sm" style="width:100%">
                                                        <thead>
                                                            <tr>
                                                                <th>Waktu Bayar</th>
                                                                <th>No. Rawat</th>
                                                                <th>No. Nota</th>
                                                                <th>Nama Pasien</th>
                                                                <th>Cara Bayar</th>
                                                                <th>Dokter</th>
                                                                <th class="text-end">Total (Rp)</th>
                                                                <th>Aksi</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($data_ralan as $data): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($data['tanggal'] . ' ' . $data['jam']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['no_rawat']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['no_nota']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['nm_pasien']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['png_jawab']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['nm_dokter']); ?></td>
                                                                <td class="text-end"><?php echo formatRupiah($data['total_rupiah']); ?></td>
                                                                <td>
                                                                    <button type="button" class="btn btn-success btn-sm btn-lihat-nota" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#modalDetailNota"
                                                                            data-norawat="<?php echo htmlspecialchars($data['no_rawat']); ?>"
                                                                            data-nonota="<?php echo htmlspecialchars($data['no_nota']); ?>">
                                                                        Nota
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- TAB RANAP -->
                                        <div class="tab-pane fade" id="ranap-<?php echo $shift_id; ?>" role="tabpanel">
                                            <div class="card-body border border-top-0 p-3">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-bordered table-sm" style="width:100%">
                                                        <thead>
                                                            <tr>
                                                                <th>Waktu Bayar</th>
                                                                <th>No. Rawat</th>
                                                                <th>No. Nota</th>
                                                                <th>Nama Pasien</th>
                                                                <th>Cara Bayar</th>
                                                                <th>Dokter DPJP</th>
                                                                <th class="text-end">Total (Rp)</th>
                                                                <th>Aksi</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($data_ranap as $data): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($data['tanggal'] . ' ' . $data['jam']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['no_rawat']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['no_nota']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['nm_pasien']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['png_jawab']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['dokter_dpjp']); ?></td>
                                                                <td class="text-end"><?php echo formatRupiah($data['total_rupiah']); ?></td>
                                                                <td>
                                                                    <button type="button" class="btn btn-success btn-sm btn-lihat-nota" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#modalDetailNota"
                                                                            data-norawat="<?php echo htmlspecialchars($data['no_rawat']); ?>"
                                                                            data-nonota="<?php echo htmlspecialchars($data['no_nota']); ?>">
                                                                        Nota
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- TAB PEMASUKAN -->
                                        <div class="tab-pane fade" id="pemasukan-<?php echo $shift_id; ?>" role="tabpanel">
                                            <div class="card-body border border-top-0 p-3">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-bordered table-sm" style="width:100%">
                                                        <thead><tr><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th class="text-end">Besar</th></tr></thead>
                                                        <tbody>
                                                            <?php foreach ($data_pemasukan as $data): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($data['tanggal']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['nama_kategori']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['keterangan']); ?></td>
                                                                <td class="text-end"><?php echo formatRupiah($data['besar']); ?></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- TAB PENGELUARAN -->
                                        <div class="tab-pane fade" id="pengeluaran-<?php echo $shift_id; ?>" role="tabpanel">
                                            <div class="card-body border border-top-0 p-3">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-bordered table-sm" style="width:100%">
                                                        <thead><tr><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th class="text-end">Biaya</th></tr></thead>
                                                        <tbody>
                                                            <?php foreach ($data_pengeluaran as $data): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($data['tanggal']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['nama_kategori']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['keterangan']); ?></td>
                                                                <td class="text-end"><?php echo formatRupiah($data['biaya']); ?></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <?php $shift_index++; endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php $day_index++; endforeach; endif; ?>
    </div>
    
    <?php
    $stmt_ralan->close();
    $stmt_ranap->close();
    $stmt_pemasukan->close();
    $stmt_pengeluaran->close();
    $stmt_sum_ralan->close();
    $stmt_sum_ranap->close();
    $stmt_sum_pemasukan->close();
    $stmt_sum_pengeluaran->close();
    ?>
</div>

<!-- Modal "Lihat Nota" (Sama dengan laporan_detail.php) -->
<div class="modal fade" id="modalDetailNota" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel">Detail Isi Nota: <span id="nomor-nota-modal">...</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="isi-nota-container"><p class="text-center">Memuat data...</p></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
    // Global data piutang untuk analisis AI
    var _piutangResponseData = <?php echo json_encode($all_piutang_data); ?>;
    var currentPiutangReportContext = "";
    var piutangChatHistoryData = [];
    const defaultPiutangPromptText = "Anda adalah AI Receivables & Billing Collector Advisor yang ahli dalam mengelola piutang rumah sakit. Analisis data detail piutang per penjamin (seperti BPJS, Asuransi Swasta, Perusahaan) dan umur piutang (Aging Receivables) berikut. Identifikasi piutang macet, klaim pending/dispute lama, serta berikan rekomendasi taktis agar penagihan dana masuk lebih cepat guna memperkuat likuiditas rumah sakit.";

    function resetPiutangPrompt() {
        $('#aiPiutangPrompt').val(defaultPiutangPromptText);
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

    function formatRupiah(angka) {
        if(angka == null || isNaN(angka)) return "Rp 0";
        var number_string = angka.toString().replace(/[^,\d]/g, ''),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);
        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return 'Rp ' + rupiah;
    }

    $(document).ready(function() {
		$('table').DataTable({ 
            "responsive": true, 
            "order": [[ 0, "desc" ]],
            "pageLength": 10, 
            "lengthChange": false,
            "dom": 'Bfrtip',
            "buttons": [
                {
                    extend: 'excelHtml5',
                    className: 'btn btn-success btn-sm',
                    title: 'Export Data',
                    exportOptions: {
                        columns: ':visible:not(:last-child)', // Cegah kolom Aksi ikut ter-export
                        format: {
                            body: function(data, row, column, node) {
                                if (typeof data === 'string' && (data.includes('Rp') || /^\d{1,3}(\.\d{3})+/.test(data))) {
                                     return data.replace(/[^\d,-]/g, '').replace(',', '.');
                                }
                                return data;
                            }
                        }
                    }
                }
            ]
        });
        
        $(document).on('click', '.btn-lihat-nota', function() {
            var noRawat = $(this).data('norawat');
            var noNota = $(this).data('nonota');
            $("#nomor-nota-modal").text(noNota + " (No. Rawat: " + noRawat + ")");
            $("#isi-nota-container").html("<p class='text-center'>Memuat data...</p>");

            $.ajax({
                url: "api/get_detail_nota.php", 
                type: "GET",
                data: { no_rawat: noRawat },
                dataType: "json",
                success: function(response) {
                    var html = '<table class="table table-sm">';
                    html += '<thead style="border-bottom: 2px solid #333;"><tr>';
                    html += '<th scope="col" style="width: 5%;">Ket.</th>';
                    html += '<th scope="col" style="width: 45%;">Perawatan/Tindakan/Obat</th>';
                    html += '<th scope="col" style="width: 20%;">Status</th>';
                    html += '<th scope="col" class="text-end" style="width: 10%;">Biaya</th>';
                    html += '<th scope="col" class="text-center" style="width: 5%;">Jml</th>';
                    html += '<th scope="col" class="text-end" style="width: 15%;">Total</th>';
                    html += '</tr></thead><tbody>';
                    
                    var grandTotal = 0;
                    
                    if (Array.isArray(response) && response.length > 0) {
                        response.forEach(function(item) {
                            var no = item.no || '';
                            var nm_perawatan = item.nm_perawatan || 'N/A';
                            var status = item.status || 'N/A';
                            
                            // Clean up zero values untuk mereduksi visual clutter
                            var biayaText = parseFloat(item.biaya) > 0 ? formatRupiah(item.biaya) : '';
                            var jumlahText = parseFloat(item.jumlah) > 0 ? parseFloat(item.jumlah) : '';
                            var totalbiayaText = parseFloat(item.totalbiaya) !== 0 ? formatRupiah(item.totalbiaya) : '';
                            var statusText = (status === '-' || status === '') ? '' : status;

                            html += '<tr>';
                            html += '<td>' + (no || '') + '</td>';
                            html += '<td>' + (nm_perawatan) + '</td>';
                            html += '<td>' + (statusText) + '</td>';
                            html += '<td class="text-end">' + (biayaText) + '</td>';
                            html += '<td class="text-center">' + (jumlahText) + '</td>';
                            html += '<td class="text-end">' + (totalbiayaText) + '</td>';
                            html += '</tr>';
                            
                            var totalbiayaNum = parseFloat(item.totalbiaya) || 0;
                            if (status !== '' && status !== '-') {
                                grandTotal += totalbiayaNum;
                            }
                        });
                    } else {
                        html += '<tr><td colspan="6" class="text-center">Tidak ada data detail billing ditemukan.</td></tr>';
                    }
                    
                    html += '</tbody><tfoot style="border-top: 2px solid #333;">';
                    html += '<tr><th colspan="5" class="text-end h5">Grand Total:</th><th class="text-end h5">' + formatRupiah(grandTotal) + '</th></tr>';
                    html += '</tfoot></table>';
                    
                    $("#isi-nota-container").html(html);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $("#isi-nota-container").html("<p class='text-danger'>Gagal memuat data. Status: " + textStatus + ", Error: " + errorThrown + "</p>");
                }
            });
        });

        // AI Receivables Advisor Action Handlers
        $(document).on('click', '#btnAnalyzePiutang', function() {
            if (!_piutangResponseData || _piutangResponseData.length === 0) {
                alert('Tidak ada data piutang untuk dianalisis.');
                return;
            }

            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');
            $('#aiPiutangReportContainer').html('<div class="text-center py-4"><div class="spinner-border text-warning mb-2"></div><div class="small text-muted">AI sedang menganalisis data piutang...</div></div>');

            // Slice to 30 records to prevent truncation while ensuring context remains rich
            var samplePiutang = _piutangResponseData;

            var piutangRawData = {
                periode: $('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val(),
                total_records: _piutangResponseData.length,
                sample_data: samplePiutang
            };

            var formData = new URLSearchParams();
            formData.append('action', 'batch_summary');
            formData.append('raw_data', JSON.stringify([piutangRawData]));
            formData.append('custom_prompt', $('#aiPiutangPrompt').val().trim());
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
            const aiThinkingContainer = document.getElementById('aiPiutangReportContainer');
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
                                    $('#aiPiutangReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                                }
                                if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                    fullText += data.choices[0].delta.content;
                                    $('#aiPiutangReportContainer').html(parseMarkdownToHtml(fullText));
                                }
                            } catch(e) {}
                        } else if (line.startsWith('event: error')) {
                            isError = true;
                        }
                    }
                }

                btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');

                if (!isError && fullText) {
                    currentPiutangReportContext = fullText;
                    piutangChatHistoryData = [];
                    $('#piutangChatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait laporan di atas...</div>');
                }
            }).catch(err => {
                btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
                $('#aiPiutangReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
            });
        });

        $(document).on('submit', '#piutangChatForm', function(e) {
            e.preventDefault();
            const input = $('#piutangChatInput');
            const messageText = input.val().trim();
            if (!messageText || !currentPiutangReportContext) return;

            if (piutangChatHistoryData.length === 0) {
                $('#piutangChatHistory').empty();
            }

            const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            $('#piutangChatHistory').append(
                '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-warning border-3">' +
                    '<div class="d-flex justify-content-between mb-1">' +
                        '<span class="fw-bold small text-warning"><i class="fas fa-user me-1"></i>Anda</span>' +
                        '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                    '</div>' +
                    '<div class="small text-light">' + parseMarkdownToHtml(messageText) + '</div>' +
                '</div>'
            );
            $('#piutangChatHistory').scrollTop($('#piutangChatHistory')[0].scrollHeight);

            input.val('');
            $('#piutangChatInput, #btnSendPiutangChat').prop('disabled', true);

            var replyId = 'piutang_reply_' + Date.now();
            $('#piutangChatHistory').append(
                '<div class="chat-msg mb-2 p-2 bg-dark rounded border-start border-info border-3">' +
                    '<div class="d-flex justify-content-between mb-1">' +
                        '<span class="fw-bold small text-info"><i class="fas fa-robot me-1"></i>AI Collector Assistant</span>' +
                        '<small class="text-muted" style="font-size:0.7rem">' + timeStr + '</small>' +
                    '</div>' +
                    '<div class="small text-light" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info me-1"></i> Mengetik...</div>' +
                '</div>'
            );
            $('#piutangChatHistory').scrollTop($('#piutangChatHistory')[0].scrollHeight);

            var samplePiutang = _piutangResponseData;
            var piutangRawData = {
                periode: $('#tgl_awal').val() + ' s.d ' + $('#tgl_akhir').val(),
                total_records: _piutangResponseData.length,
                sample_data: samplePiutang
            };

            var chatData = new URLSearchParams();
            chatData.append('action', 'chat_discuss');
            chatData.append('message', messageText);
            chatData.append('report_context', currentPiutangReportContext);
            chatData.append('raw_data', JSON.stringify([piutangRawData]));
            chatData.append('custom_prompt', $('#aiPiutangPrompt').val().trim());
            chatData.append('history', JSON.stringify(piutangChatHistoryData));
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
            const aiThinkingContainer = document.getElementById('aiPiutangReportContainer');
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
                                    $('#piutangChatHistory').scrollTop($('#piutangChatHistory')[0].scrollHeight);
                                }
                            } catch(e) {}
                        } else if (line.startsWith('event: error')) {
                            isError = true;
                        }
                    }
                }

                $('#piutangChatInput, #btnSendPiutangChat').prop('disabled', false);

                if (!isError && fullReply) {
                    piutangChatHistoryData.push({ role: 'user', content: messageText });
                    piutangChatHistoryData.push({ role: 'assistant', content: fullReply });
                }
            }).catch(err => {
                $('#piutangChatInput, #btnSendPiutangChat').prop('disabled', false);
                $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Error koneksi</span>');
            });
        });
    });
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>