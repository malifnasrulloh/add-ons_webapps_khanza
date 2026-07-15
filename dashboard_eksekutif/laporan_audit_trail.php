<?php
/*
 * File: laporan_audit_trail.php (UPDATE V7 - CUSTOMIZABLE SYSTEM PROMPT, AI CHAT & EXPORT)
 * - Fix Performance: Query hanya jalan jika tombol filter diklik.
 * - Default State: Menampilkan pesan "Silakan filter data".
 * - Integration: Ditambahkan fitur pengaturan prompt kustom per modul, analisis query SQL kolektif/batch, chatbot interaktif, dan ekspor ke Word (.doc).
 */

$page_title = "Audit Trail System";
require_once('includes/header.php');
require_once('includes/functions.php');

// 1. Inisialisasi Parameter (Default tanggal hari ini untuk tampilan form saja)
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$is_submitted = isset($_GET['filter_submit']); // Cek apakah tombol sudah diklik

// --- Helper Function untuk Membangun Query ---
function build_filter_segment($col, $op, $val, &$params, &$types) {
    if (trim($val) === '') return " 1=1 "; 

    $target_cols = [];
    if ($col == 'all') {
        $target_cols = ['p.nama', 't.usere', 't.sqle'];
    } elseif ($col == 'user') {
        $target_cols = ['p.nama', 't.usere']; 
    } elseif ($col == 'sql') {
        $target_cols = ['t.sqle'];
    } else {
        $target_cols = ['t.sqle']; 
    }

    $sql_op = "LIKE";
    $sql_val = "%$val%";
    
    if ($op == 'not_contains') {
        $sql_op = "NOT LIKE";
        $sql_val = "%$val%";
    } elseif ($op == 'equals') {
        $sql_op = "=";
        $sql_val = "$val";
    } elseif ($op == 'starts_with') {
        $sql_op = "LIKE";
        $sql_val = "$val%";
    }

    $segments = [];
    foreach ($target_cols as $c) {
        $segments[] = "$c $sql_op ?";
        $params[] = $sql_val;
        $types .= "s";
    }

    $logic_internal = ($op == 'not_contains') ? " AND " : " OR ";
    return "(" . implode($logic_internal, $segments) . ")";
}

// 2. Main Logic Query (Hanya Jalan Jika Submitted)
$data_audit = [];
$pesan_error = "";

if ($koneksi && $is_submitted) {
    // Base Query
    $sql = "
        SELECT 
            t.tanggal, 
            t.sqle, 
            t.usere, 
            p.nama as nama_pegawai
        FROM trackersql t
        LEFT JOIN pegawai p ON t.usere = p.nik
        WHERE t.tanggal BETWEEN ? AND ? 
    ";
    
    $params = [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'];
    $types = "ss";

    // Filter 1
    if (!empty($_GET['val1'])) {
        $cond1 = build_filter_segment($_GET['col1'], $_GET['op1'], $_GET['val1'], $params, $types);
        $sql .= " AND ( $cond1 ";

        // Filter 2
        if (!empty($_GET['val2'])) {
            $logic1 = ($_GET['logic1'] == 'OR') ? " OR " : " AND ";
            $cond2 = build_filter_segment($_GET['col2'], $_GET['op2'], $_GET['val2'], $params, $types);
            $sql .= " $logic1 $cond2 ";

            // Filter 3
            if (!empty($_GET['val3'])) {
                $logic2 = ($_GET['logic2'] == 'OR') ? " OR " : " AND ";
                $cond3 = build_filter_segment($_GET['col3'], $_GET['op3'], $_GET['val3'], $params, $types);
                $sql .= " $logic2 $cond3 ";
            }
        }
        $sql .= " ) "; 
    }

    $sql .= " ORDER BY t.tanggal DESC LIMIT 1000";

    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $bind_names[] = $types;
        for ($i=0; $i<count($params);$i++) { $bind_names[] = &$params[$i]; }
        call_user_func_array(array($stmt, 'bind_param'), $bind_names);
        
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data_audit[] = $row;
        }
        $stmt->close();
    } else {
        $pesan_error = "Query Error: " . $koneksi->error;
    }
}

// Helper Options
$opt_cols = ['all' => 'Semua Kolom', 'user' => 'User (Nama/NIK)', 'sql' => 'Isi Query SQL'];
$opt_ops = ['contains' => 'Mengandung (Like)', 'not_contains' => 'TIDAK Mengandung (Not Like)', 'equals' => 'Sama Persis (=)', 'starts_with' => 'Dimulai dengan'];
?>

<div class="container-fluid">
    
    <div class="alert alert-secondary border-left-secondary shadow-sm mb-4">
        <i class="fas fa-user-secret me-2"></i>
        <strong>Audit Trail (TrackerSQL):</strong> Gunakan filter di bawah untuk menampilkan data. Data tidak dimuat otomatis demi performa.
    </div>

    <div class="card shadow-sm mb-4 border-left-primary">
        <div class="card-header py-2 bg-gray-100">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-search me-2"></i>Pencarian Bertingkat</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="laporan_audit_trail.php">
                
                <div class="row g-2 align-items-center mb-3 pb-3 border-bottom">
                    <div class="col-md-2">
                        <label class="small fw-bold">Dari Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="tgl_awal" value="<?php echo $tgl_awal; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Sampai Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="tgl_akhir" value="<?php echo $tgl_akhir; ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="small fw-bold text-muted d-block">&nbsp;</label>
                        <span class="badge bg-info text-dark"><i class="fas fa-info-circle"></i> Wajib diisi. Max 1000 data ditampilkan.</span>
                    </div>
                </div>

                <div class="row g-2 align-items-center mb-2">
                    <div class="col-md-1 text-end"><span class="badge bg-primary">Syarat 1</span></div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="col1">
                            <?php foreach($opt_cols as $k=>$v) echo "<option value='$k' ".((isset($_GET['col1']) && $_GET['col1']==$k)?'selected':'').">$v</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="op1">
                            <?php foreach($opt_ops as $k=>$v) echo "<option value='$k' ".((isset($_GET['op1']) && $_GET['op1']==$k)?'selected':'').">$v</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control form-control-sm" name="val1" value="<?php echo htmlspecialchars($_GET['val1'] ?? ''); ?>" placeholder="Kata kunci...">
                    </div>
                </div>

                <div class="row g-2 align-items-center mb-2">
                    <div class="col-md-1 text-end">
                        <select class="form-select form-select-sm bg-warning text-dark fw-bold" name="logic1">
                            <option value="AND" <?php echo ((isset($_GET['logic1']) && $_GET['logic1']=='AND')?'selected':''); ?>>AND</option>
                            <option value="OR" <?php echo ((isset($_GET['logic1']) && $_GET['logic1']=='OR')?'selected':''); ?>>OR</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="col2">
                            <?php foreach($opt_cols as $k=>$v) echo "<option value='$k' ".((isset($_GET['col2']) && $_GET['col2']==$k)?'selected':'').">$v</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="op2">
                            <?php foreach($opt_ops as $k=>$v) echo "<option value='$k' ".((isset($_GET['op2']) && $_GET['op2']==$k)?'selected':'').">$v</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control form-control-sm" name="val2" value="<?php echo htmlspecialchars($_GET['val2'] ?? ''); ?>" placeholder="Kata kunci kedua...">
                    </div>
                </div>

                <div class="row g-2 align-items-center mb-3">
                    <div class="col-md-1 text-end">
                        <select class="form-select form-select-sm bg-warning text-dark fw-bold" name="logic2">
                            <option value="AND" <?php echo ((isset($_GET['logic2']) && $_GET['logic2']=='AND')?'selected':''); ?>>AND</option>
                            <option value="OR" <?php echo ((isset($_GET['logic2']) && $_GET['logic2']=='OR')?'selected':''); ?>>OR</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="col3">
                            <?php foreach($opt_cols as $k=>$v) echo "<option value='$k' ".((isset($_GET['col3']) && $_GET['col3']==$k)?'selected':'').">$v</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="op3">
                            <?php foreach($opt_ops as $k=>$v) echo "<option value='$k' ".((isset($_GET['op3']) && $_GET['op3']==$k)?'selected':'').">$v</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control form-control-sm" name="val3" value="<?php echo htmlspecialchars($_GET['val3'] ?? ''); ?>" placeholder="Kata kunci ketiga...">
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-end">
                        <a href="laporan_audit_trail.php" class="btn btn-secondary btn-sm me-2"><i class="fas fa-sync"></i> Reset</a>
                        <button type="submit" name="filter_submit" value="1" class="btn btn-primary btn-sm px-4"><i class="fas fa-search me-2"></i> Terapkan Filter</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <?php if ($is_submitted): ?>
    
    <!-- AI Analisis Kolektif Dashboard (Super Admin Only) -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin' && is_ai_active()): ?>
    <div class="card shadow mb-4 border-left-info">
        <div class="card-header py-3 bg-info text-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-brain me-2"></i>Analisis Audit Trail Pintar (AI LLM)</h6>
            <div>
                <button type="button" class="btn btn-outline-light btn-sm me-2 fw-bold" data-bs-toggle="collapse" data-bs-target="#aiPromptCollapse" title="Sesuaikan instruksi analisis AI"><i class="fas fa-sliders-h me-1"></i> Tune Prompt</button>
                <button type="button" id="btnAnalyzeBatch" class="btn btn-light btn-sm font-weight-bold"><i class="fas fa-magic me-1 text-info"></i> Jalankan Analisis AI</button>
            </div>
        </div>
        
        <!-- Collapsible System Prompt Tuning Block -->
        <div class="collapse" id="aiPromptCollapse">
            <div class="p-3 border-bottom bg-light text-dark">
                <label class="small fw-bold text-muted d-block mb-1"><i class="fas fa-terminal me-1"></i>Instruksi Analisis AI (System Prompt):</label>
                <textarea id="aiCustomPrompt" class="form-control form-control-sm font-monospace" rows="5" style="font-size:0.85rem;">Ini adalah data audit-trail. Terjemahkan SQL query pada datatable ini ke penjelasan bahasa Indonesia yang singkat, ramah pengguna, dan mudah dipahami. Jelaskan apa tindakan yang dilakukan (misal menambah pasien, mengupdate kamar, menghapus obat, dan lain-lain) dan sebutkan data kunci seperti nama atau nomor rekam medis jika ada. Jangan sertakan tag teknis atau kode, langsung hasil terjemahannya saja. Sajikan data dalam bentuk kronologis untuk identifikasi who, what, and when.</textarea>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Anda dapat mengedit prompt di atas untuk menyesuaikan kriteria output AI.</small>
                    <button type="button" id="btnResetPrompt" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:0.75rem;"><i class="fas fa-undo me-1"></i> Reset ke Default</button>
                </div>
            </div>
        </div>
        
        <!-- Hasil Analisis Laporan AI -->
        <div class="card-body" id="aiAnalysisBody" style="display:none;">
            <div id="aiReportContainer" class="p-3 bg-light rounded border text-dark mb-3" style="max-height: 450px; overflow-y: auto;">
                <!-- Output Markdown HTML hasil analisis LLM -->
            </div>
            
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                <div>
                    <span class="badge bg-secondary p-2"><i class="fas fa-info-circle me-1"></i> Data log dianalisis: <span id="processedLogCount">0</span> log teratas (Maks. 500)</span>
                </div>
                <button id="btnExportDoc" class="btn btn-sm btn-success"><i class="fas fa-file-word me-1"></i> Ekspor Laporan ke Word (.doc)</button>
            </div>
            
            <!-- AI Chat Assistant Section -->
            <div class="chat-section">
                <h6 class="fw-bold mb-2 text-primary"><i class="fas fa-comments me-2"></i>Tanya Jawab & Diskusi Temuan Log Audit dengan AI</h6>
                
                <div id="chatHistory" class="p-3 bg-white border rounded mb-2 text-dark" style="height: 250px; overflow-y: auto;">
                    <div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait analisis log di atas...</div>
                </div>
                
                <form id="chatForm">
                    <div class="input-group">
                        <input type="text" id="chatInput" class="form-control" placeholder="Tanyakan analisis lebih lanjut (contoh: Adakah indikasi anomali akses pada data ini?)" required>
                        <button class="btn btn-info text-white" type="submit" id="btnSendChat"><i class="fas fa-paper-plane me-1"></i>Kirim</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card-body text-center p-4" id="aiAnalysisPlaceholder">
            <p class="text-muted mb-0"><i class="fas fa-robot fa-2x mb-2 d-block text-info"></i>Klik tombol <strong>"Jalankan Analisis AI"</strong> di atas untuk menganalisis dan merangkum seluruh log yang tampil saat ini ke bahasa manusia serta mengaktifkan chatbot diskusi.</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Hasil Pencarian Log</h6>
            <span class="badge bg-secondary"><?php echo count($data_audit); ?> Records Found</span>
        </div>
        <div class="card-body">
            <?php if (!empty($pesan_error)): ?>
                <div class="alert alert-danger"><?php echo $pesan_error; ?></div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm text-sm" id="dataTable" width="100%" cellspacing="0">
                    <thead class="table-dark">
                        <tr>
                            <th width="15%">Waktu</th>
                            <th width="20%">User (Pegawai)</th>
                            <th>Perintah SQL (Query)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_audit as $row): ?>
                        <tr>
                            <td class="align-middle font-monospace text-nowrap"><?php echo htmlspecialchars($row['tanggal']); ?></td>
                            <td class="align-middle">
                                <div class="fw-bold text-primary"><?php echo htmlspecialchars($row['nama_pegawai'] ?? 'Unknown'); ?></div>
                                <small class="text-muted"><i class="fas fa-id-badge me-1"></i><?php echo htmlspecialchars($row['usere']); ?></small>
                            </td>
                            <td class="align-middle">
                                <code class="d-block bg-gray-100 p-2 rounded text-dark border" style="max-height: 120px; overflow-y: auto; font-family: 'Consolas', monospace; font-size: 0.8rem;">
                                    <?php echo htmlspecialchars($row['sqle']); ?>
                                </code>
                                
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin'): ?>
                                <button class="btn btn-sm btn-outline-info py-0 px-2 mt-2 btn-translate" style="font-size: 0.75rem;" data-sql="<?php echo base64_encode($row['sqle']); ?>">
                                    <i class="fas fa-robot me-1"></i> Terjemahkan
                                </button>
                                <div class="translation-container mt-2 d-none p-2 rounded border" style="background-color: rgba(13, 202, 240, 0.05); border-color: rgba(13, 202, 240, 0.2) !important;">
                                    <div class="translation-loader"><i class="fas fa-spinner fa-spin me-1 text-info"></i> <span class="text-muted small">Menerjemahkan query...</span></div>
                                    <div class="translation-text text-info small" style="font-family: system-ui, -apple-system, sans-serif; line-height: 1.4;"></div>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-info text-center p-5 border shadow-sm bg-white">
            <h4><i class="fas fa-filter fa-2x text-gray-300 mb-3 d-block"></i>Silakan Terapkan Filter</h4>
            <p class="text-muted">Masukkan parameter tanggal dan kata kunci, lalu klik tombol <strong>"Terapkan Filter"</strong> untuk memuat data audit.</p>
        </div>
    <?php endif; ?>

</div>

<?php ob_start(); ?>
<script>
    // Custom Markdown Simple HTML Parser (Regex)
    function parseMarkdownToHtml(md) {
        if (!md) return '';
        let html = md
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");

        // Headings
        html = html.replace(/^### (.*?)$/gm, '<h5 class="fw-bold mt-3 text-info">$1</h5>');
        html = html.replace(/^## (.*?)$/gm, '<h4 class="fw-bold mt-4 text-primary border-bottom pb-1">$1</h4>');
        html = html.replace(/^# (.*?)$/gm, '<h3 class="fw-bold mt-4 text-dark">$1</h3>');

        // Bold & Italic
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

        // Bullet Lists
        html = html.replace(/^\s*[-*+]\s+(.*?)$/gm, '<li>$1</li>');
        html = html.replace(/(<li>.*?<\/li>)/gs, '<ul class="mb-3">$1</ul>');
        html = html.replace(/<\/ul>\s*<ul class="mb-3">/g, ''); // Clean double wraps

        // Paragraphs
        html = html.replace(/^\s*([^#<>\s\-*+].*?)$/gm, '<p class="mb-2">$1</p>');
        
        // Linebreaks
        html = html.replace(/\n\n/g, '<br>');

        return html;
    }

    $(document).ready(function() {
        var table = null;
        var chatHistoryData = []; // Array of {role, content}
        var currentReportContext = ""; // Holds the generated AI report text for chat context

        // Default prompt text
        const defaultPromptText = "Ini adalah data audit-trail. Terjemahkan SQL query pada datatable ini ke penjelasan bahasa Indonesia yang singkat, ramah pengguna, dan mudah dipahami. Jelaskan apa tindakan yang dilakukan (misal menambah pasien, mengupdate kamar, menghapus obat, dan lain-lain) dan sebutkan data kunci seperti nama atau nomor rekam medis jika ada. Jangan sertakan tag teknis atau kode, langsung hasil terjemahannya saja. Sajikan data dalam bentuk kronologis untuk identifikasi who, what, and when.";
        
        // Reset Prompt
        $('#btnResetPrompt').on('click', function() {
            $('#aiCustomPrompt').val(defaultPromptText);
            const resetBtn = $(this);
            const originalText = resetBtn.html();
            resetBtn.removeClass('btn-outline-secondary').addClass('btn-outline-success').html('<i class="fas fa-check me-1"></i> Reset!');
            setTimeout(function() {
                resetBtn.removeClass('btn-outline-success').addClass('btn-outline-secondary').html(originalText);
            }, 1500);
        });

        <?php if ($is_submitted): ?>
        table = $('#dataTable').DataTable({
            "responsive": true,
            "dom": 'Bfrtip',
            "buttons": [
                { extend: 'excel', className: 'btn-sm btn-success', title: 'Audit Log Export' },
                { extend: 'print', className: 'btn-sm btn-secondary' }
            ],
            "pageLength": 20,
            "ordering": false, 
            "language": {
                "search": "Cari di Halaman Ini:",
                "lengthMenu": "Tampilkan _MENU_ baris"
            }
        });

        // 1. Single Row Translate
        $('#dataTable').on('click', '.btn-translate', function(e) {
            e.preventDefault();
            const btn = $(this);
            const sqlBase64 = btn.data('sql');
            const cell = btn.closest('td');
            const container = cell.find('.translation-container');
            const loader = container.find('.translation-loader');
            const textDiv = container.find('.translation-text');

            container.removeClass('d-none');
            loader.show();
            textDiv.hide().text('');
            btn.prop('disabled', true);

            $.ajax({
                url: 'api/translate_audit.php',
                type: 'POST',
                dataType: 'json',
                global: false,
                data: {
                    sql: sqlBase64,
                    custom_prompt: $('#aiCustomPrompt').val().trim()
                },
                success: function(res) {
                    loader.hide();
                    btn.prop('disabled', false);
                    if (res.status === 'success') {
                        textDiv.text(res.translation).show();
                        btn.html('<i class="fas fa-sync-alt me-1"></i> Terjemahkan Ulang');
                    } else {
                        textDiv.html('<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i> Gagal: ' + res.message + '</span>').show();
                    }
                },
                error: function(xhr) {
                    loader.hide();
                    btn.prop('disabled', false);
                    let errMsg = 'Gagal menghubungi server penerjemah.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    textDiv.html('<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i> Error: ' + errMsg + '</span>').show();
                }
            });
        });

        // 2. Batch/Collective Analysis
        $('#btnAnalyzeBatch').on('click', function() {
            if (!table) return;

            // Kumpulkan data log setelah filter dari DataTable instance
            var logsData = [];
            
            table.rows({ filter: 'applied' }).every(function() {
                var rowData = this.data();
                if (!rowData) return;
                
                var tgl = $('<div>').html(rowData[0]).text().trim();
                var userCol = $('<div>').html(rowData[1]);
                var nama = userCol.find('.fw-bold').text().trim();
                var userRaw = userCol.find('small').text().trim();
                // Extract NIK/ID dari text
                var user = userRaw.replace(/[^\w]/g, ''); 

                var sql = $('<div>').html(rowData[2]).find('code').text().trim();

                logsData.push({
                    tgl: tgl,
                    user: user,
                    nama: nama,
                    sql: sql
                });
            });

            if (logsData.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Data Kosong',
                    text: 'Tidak ada data log yang tampil untuk dianalisis.'
                });
                return;
            }

            // Batasi maksimal 500 baris query terbaru sesuai permintaan pengguna
            logsData = logsData;

            $('#aiAnalysisPlaceholder').hide();
            $('#aiAnalysisBody').show();
            $('#aiReportContainer').html('<div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin fa-2x mb-3 text-info"></i><br>AI sedang mengumpulkan data dan menganalisis ' + logsData.length + ' log audit trail terbaru... Mohon tunggu sebentar.</div>');
            $('#processedLogCount').text(logsData.length);
            
            // Disable button
            $('#btnAnalyzeBatch').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menganalisis...');

            // Fetch API untuk Analisis Batch dengan SSE Streaming
            var formData = new URLSearchParams();
            formData.append('action', 'batch_summary');
            formData.append('logs', JSON.stringify(logsData));
            formData.append('custom_prompt', $('#aiCustomPrompt').val().trim());
            formData.append('stream', '1');

            fetch('api/translate_audit.php', {
                method: 'POST',
                body: formData,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).then(async response => {
                const reader = response.body.getReader();
                const decoder = new TextDecoder("utf-8");
                let fullText = "";
                let isError = false;
            let isThinking = false;
            const aiThinkingContainer = null;
                let buffer = "";

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    
                    buffer += decoder.decode(value, {stream: true});
                    const lines = buffer.split('\n');
                    buffer = lines.pop(); // Simpan potongan baris yang belum selesai ke buffer
                    
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
                                    $('#aiReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' + data.message + '</div>');
                                }
                                if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                    fullText += data.choices[0].delta.content;
                                    $('#aiReportContainer').html(parseMarkdownToHtml(fullText));
                                }
                            } catch(e) {}
                        } else if (line.startsWith('event: error')) {
                            isError = true;
                        }
                    }
                }
                
                $('#btnAnalyzeBatch').prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
                
                if (!isError && fullText) {
                    currentReportContext = fullText;
                    chatHistoryData = [];
                    $('#chatHistory').html('<div class="text-muted small text-center italic py-2">Mulai diskusi dengan mengajukan pertanyaan di bawah terkait analisis log di atas...</div>');
                }
            }).catch(err => {
                $('#btnAnalyzeBatch').prop('disabled', false).html('<i class="fas fa-magic me-1"></i> Jalankan Analisis AI');
                $('#aiReportContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: Gagal menghubungi server (' + err.message + ')</div>');
            });
        });

        // 3. AI Interactive Chat
        $('#chatForm').on('submit', function(e) {
            e.preventDefault();
            const input = $('#chatInput');
            const messageText = input.val().trim();
            if (!messageText || !currentReportContext) return;

            // Bersihkan placeholder awal chat
            if (chatHistoryData.length === 0) {
                $('#chatHistory').empty();
            }

            // Append User Message to UI
            const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            $('#chatHistory').append(
                '<div class="chat-msg mb-3 p-2 bg-light rounded border-start border-primary border-4">' +
                    '<div class="d-flex justify-content-between mb-1">' +
                        '<span class="fw-bold chat-sender text-primary"><i class="fas fa-user me-1"></i>Anda</span>' +
                        '<small class="text-muted chat-time">' + timeStr + '</small>' +
                    '</div>' +
                    '<div class="chat-text small">' + parseMarkdownToHtml(messageText) + '</div>' +
                '</div>'
            );
            $('#chatHistory').scrollTop($('#chatHistory')[0].scrollHeight);

            // Simpan input, disable form
            input.val('');
            $('#chatInput, #btnSendChat').prop('disabled', true);
            
            // Append loading bubble
            $('#chatHistory').append(
                '<div id="chatLoading" class="chat-msg mb-3 p-2 bg-light rounded border-start border-info border-4">' +
                    '<div class="fw-bold text-info"><i class="fas fa-robot me-1"></i>AI Assistant</div>' +
                    '<div class="small text-muted mt-1"><i class="fas fa-spinner fa-spin me-1"></i> Mengetik...</div>' +
                '</div>'
            );
            $('#chatHistory').scrollTop($('#chatHistory')[0].scrollHeight);

            // Ekstrak ulang data mentah dari datatables untuk dianalisis lebih dalam oleh AI
            var rawLogsData = [];
            if (table) {
                table.rows({ filter: 'applied' }).every(function() {
                    var rowData = this.data();
                    if (!rowData) return;
                    
                    var tgl = $('<div>').html(rowData[0]).text().trim();
                    var userCol = $('<div>').html(rowData[1]);
                    var nama = userCol.find('.fw-bold').text().trim();
                    var user = userCol.find('small').text().trim().replace(/[^\w]/g, ''); 
                    var sql = $('<div>').html(rowData[2]).find('code').text().trim();
                    rawLogsData.push({ tgl: tgl, user: user, nama: nama, sql: sql });
                });
                rawLogsData = rawLogsData; // Max 500
            }

            // Fetch API untuk Chat dengan SSE Streaming
            var chatData = new URLSearchParams();
            chatData.append('action', 'chat_discuss');
            chatData.append('message', messageText);
            chatData.append('report_context', currentReportContext);
            chatData.append('history', JSON.stringify(chatHistoryData));
            chatData.append('raw_logs', JSON.stringify(rawLogsData));
            chatData.append('stream', '1');
            
            // Siapkan DOM untuk menampung teks streaming
            $('#chatLoading').remove();
            var replyId = 'reply_' + Date.now();
            $('#chatHistory').append(
                '<div class="chat-msg mb-3 p-2 bg-light rounded border-start border-info border-4">' +
                    '<div class="d-flex justify-content-between mb-1">' +
                        '<span class="fw-bold chat-sender text-info"><i class="fas fa-robot me-1"></i>AI Assistant</span>' +
                        '<small class="text-muted chat-time">' + timeStr + '</small>' +
                    '</div>' +
                    '<div class="chat-text small" id="' + replyId + '"><i class="fas fa-spinner fa-spin text-info"></i> Mengetik...</div>' +
                '</div>'
            );
            $('#chatHistory').scrollTop($('#chatHistory')[0].scrollHeight);

            fetch('api/translate_audit.php', {
                method: 'POST',
                body: chatData,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).then(async response => {
                const reader = response.body.getReader();
                const decoder = new TextDecoder("utf-8");
                let fullReply = "";
                let isError = false;
            let isThinking = false;
            const aiThinkingContainer = null;
                let buffer = "";

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    
                    buffer += decoder.decode(value, {stream: true});
                    const lines = buffer.split('\n');
                    buffer = lines.pop(); // Simpan potongan baris yang belum selesai ke buffer
                    
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
                                    $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> ' + data.message + '</span>');
                                }
                                if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                    fullReply += data.choices[0].delta.content;
                                    $('#' + replyId).html(parseMarkdownToHtml(fullReply));
                                    $('#chatHistory').scrollTop($('#chatHistory')[0].scrollHeight);
                                }
                            } catch(e) {}
                        } else if (line.startsWith('event: error')) {
                            isError = true;
                        }
                    }
                }
                
                $('#chatInput, #btnSendChat').prop('disabled', false);
                input.focus();
                
                if (!isError && fullReply) {
                    chatHistoryData.push({ role: 'user', content: messageText });
                    chatHistoryData.push({ role: 'assistant', content: fullReply });
                }
            }).catch(err => {
                $('#chatInput, #btnSendChat').prop('disabled', false);
                input.focus();
                $('#' + replyId).html('<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Gagal menghubungi server (' + err.message + ')</span>');
            });
        });

        // 4. Ekspor ke Word (.doc) Client-Side Native
        $('#btnExportDoc').on('click', function() {
            var reportContent = $('#aiReportContainer').html();
            var chatContent = '';
            
            var chatItems = $('#chatHistory').find('.chat-msg');
            if (chatItems.length > 0) {
                chatContent += '<h2 style="color: #0f172a; border-top: 1px solid #dee2e6; padding-top: 20px; margin-top: 30px;"><i class="fas fa-comments"></i> Riwayat Diskusi Temuan AI</h2>';
                chatItems.each(function() {
                    var sender = $(this).find('.chat-sender').text().trim();
                    var time = $(this).find('.chat-time').text().trim();
                    var text = $(this).find('.chat-text').html();
                    chatContent += '<div style="margin-bottom: 15px; padding: 10px; border-left: 4px solid #0ea5e9; background-color: #f8fafc;">' +
                                   '<strong>' + sender + '</strong> <span style="font-size: 0.8rem; color: #64748b;">(' + time + ')</span>:<br>' +
                                   '<div style="font-size: 0.95rem; margin-top: 5px;">' + text + '</div>' +
                                   '</div>';
                });
            }

            var header = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>" +
                         "<head><meta charset='utf-8'><title>Laporan Analisis Audit Trail AI</title>" +
                         "<style>" +
                         "body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }" +
                         "h1 { color: #0284c7; border-bottom: 2px solid #0284c7; padding-bottom: 5px; margin-bottom: 15px; font-size: 20pt; }" +
                         "h2 { color: #0f172a; margin-top: 20px; font-size: 14pt; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; }" +
                         "h3 { color: #0369a1; margin-top: 15px; font-size: 12pt; }" +
                         "p { margin: 0 0 10px 0; font-size: 11pt; }" +
                         "ul, ol { margin-top: 0; margin-bottom: 10px; padding-left: 20px; }" +
                         "li { font-size: 11pt; margin-bottom: 4px; }" +
                         "code { background-color: #f1f5f9; padding: 2px 4px; font-family: 'Courier New', monospace; font-size: 9.5pt; color: #b91c1c; }" +
                         "strong { color: #1e293b; }" +
                         "</style></head><body>";
            
            var instansi = "<?php echo htmlspecialchars($nama_instansi); ?>";
            var range = "Periode Filter: <?php echo htmlspecialchars($tgl_awal); ?> s.d <?php echo htmlspecialchars($tgl_akhir); ?>";
            
            var title = "<h1>Laporan Ringkasan Eksekutif Audit Trail (Analisis AI)</h1>" +
                        "<p style='margin-bottom: 20px;'><strong>Nama Instansi:</strong> " + instansi + "<br>" +
                        "<strong>" + range + "</strong><br>" +
                        "<strong>Waktu Generate Laporan:</strong> " + new Date().toLocaleString('id-ID') + "</p><hr style='border: none; border-top: 1px solid #cbd5e1;'>";
                        
            var footer = "</body></html>";
            
            var sourceHTML = header + title + reportContent + chatContent + footer;
            
            var blob = new Blob(['\ufeff', sourceHTML], { type: 'application/msword' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'Laporan_Analisis_Audit_Trail_AI_' + new Date().toISOString().slice(0,10) + '.doc';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
        <?php endif; ?>
    });
</script>
<?php $page_js = ob_get_clean(); ?>

<?php require_once('includes/footer.php'); ?>