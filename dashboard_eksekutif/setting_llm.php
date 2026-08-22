<?php
/*
 * File: setting_llm.php
 * Deskripsi: Halaman konfigurasi LLM (Super Admin Only)
 */

session_start();
require_once 'config/koneksi.php';

// Proteksi Khusus Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Super Admin') {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini.";
    header('Location: index.php');
    exit;
}

$page_title = "Pengaturan LLM";
$config_file = 'config/llm_config.json';

// Baca data config saat ini
$llm_config = [
    'ai_status' => 'on',
    'api_endpoint' => '',
    'api_key' => '',
    'model' => 'Flash',
    'fallback_models' => ['Flash', 'ag/gemini-3.5-flash-low', 'ag/gemini-pro-agent', 'Claude'],
    'prompt' => ''
];

if (file_exists($config_file)) {
    $current_config = json_decode(file_get_contents($config_file), true);
    if (is_array($current_config)) {
        $llm_config = array_merge($llm_config, $current_config);
    }
}

// Pastikan CSRF Token ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-robot me-2"></i> Pengaturan LLM</h1>
</div>

<div class="row">
    <div class="col-md-10 col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-sliders-h me-2"></i>Konfigurasi OpenAI-Compatible API</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info text-sm" style="font-size: 0.85rem;">
                    <i class="fas fa-info-circle me-1"></i>
                    Pengaturan ini digunakan untuk menerjemahkan query SQL hasil Audit Trail menjadi deskripsi log aktivitas dalam bahasa manusia yang ramah pengguna.
                </div>

                <form id="formLLM" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <div class="mb-4 p-3 rounded bg-light border border-secondary d-flex justify-content-between align-items-center">
                        <div>
                            <label class="form-check-label fw-bold text-dark mb-0" for="ai_status">Aktifkan Fitur Analisis AI</label>
                            <div class="small text-muted">Jika dinonaktifkan, seluruh panel AI dan tombol analisis di semua halaman dasbor akan disembunyikan.</div>
                        </div>
                        <div class="form-check form-switch fs-4 mb-0">
                            <input type="hidden" name="ai_status" value="off">
                            <input class="form-check-input" type="checkbox" role="switch" id="ai_status" name="ai_status" value="on" <?php echo (!isset($llm_config['ai_status']) || $llm_config['ai_status'] !== 'off') ? 'checked' : ''; ?>>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="api_endpoint" class="form-label fw-bold">API Endpoint URL</label>
                        <input type="url" class="form-control" id="api_endpoint" name="api_endpoint" 
                               value="<?php echo htmlspecialchars($llm_config['api_endpoint']); ?>" 
                               placeholder="https://example.com/v1" required>
                        <div class="invalid-feedback">Endpoint URL wajib diisi dengan format URL yang valid.</div>
                        <small class="text-muted">Masukkan endpoint dasar API (misal: <code>https://localhost/ollama/v1</code>).</small>
                    </div>

                    <div class="mb-3">
                        <label for="api_key" class="form-label fw-bold">API Key</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="api_key" name="api_key" 
                                   value="<?php echo htmlspecialchars($llm_config['api_key']); ?>" 
                                   placeholder="sk-..." required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                            <div class="invalid-feedback">API Key wajib diisi.</div>
                        </div>
                        <small class="text-muted">API key rahasia untuk otentikasi ke server LLM.</small>
                    </div>

                    <div class="mb-3">
                        <label for="model" class="form-label fw-bold">Nama Model Utama (Primary Model)</label>
                        <div class="input-group">
                            <select class="form-select select2-tags" id="model" name="model" required>
                                <option value="<?php echo htmlspecialchars($llm_config['model']); ?>" selected><?php echo htmlspecialchars($llm_config['model']); ?></option>
                            </select>
                            <button class="btn btn-outline-primary" type="button" id="btnFetchModels" title="Ambil Daftar Model Aktif">
                                <i class="fas fa-sync-alt" id="fetchIcon"></i> Ambil Model
                            </button>
                            <div class="invalid-feedback">Nama model wajib diisi.</div>
                        </div>
                        <small class="text-muted">Pilih nama model dari daftar atau ketik nama model kustom Anda lalu tekan Enter.</small>
                    </div>

                    <div class="mb-3">
                        <label for="fallback_models" class="form-label fw-bold">Daftar Model Cadangan (Fallback Models Chain)</label>
                        <select class="form-select select2-tags" id="fallback_models" name="fallback_models[]" multiple="multiple">
                            <?php 
                            $fallbacks = is_array($llm_config['fallback_models']) ? $llm_config['fallback_models'] : explode(',', $llm_config['fallback_models']);
                            foreach ($fallbacks as $fm) {
                                $fm = trim($fm);
                                if (!empty($fm)) {
                                    echo '<option value="' . htmlspecialchars($fm) . '" selected>' . htmlspecialchars($fm) . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <small class="text-muted">Ketik nama model lalu tekan Enter. Sistem otomatis berpindah model jika model utama kehabisan token (Rate Limit/HTTP 429).</small>
                    </div>

                    <div class="mb-3">
                        <label for="prompt" class="form-label fw-bold">System Prompt Fallback (Opsional)</label>
                        <textarea class="form-control" id="prompt" name="prompt" rows="4"><?php echo htmlspecialchars($llm_config['prompt']); ?></textarea>
                        <small class="text-muted">Prompt ini bertindak sebagai fallback global. Tiap halaman analisis (seperti Laporan Audit Trail) menyediakan custom default prompt sendiri yang dapat ditune langsung oleh pengguna.</small>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" id="btnTestConn" class="btn btn-outline-primary px-4">
                            <i class="fas fa-vial me-2"></i> Uji Koneksi LLM
                        </button>
                        <button type="submit" id="btnSaveConfig" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(document).ready(function() {
    // Initialize Select2 with tags support
    $('.select2-tags').select2({
        theme: 'bootstrap-5',
        tags: true,
        width: '100%',
        tokenSeparators: [','] // Allow comma separated for tags
    });

    // Toggle Visibility API Key
    $('#toggleApiKey').on('click', function() {
        const apiKeyInput = $('#api_key');
        const eyeIcon = $('#eyeIcon');
        if (apiKeyInput.attr('type') === 'password') {
            apiKeyInput.attr('type', 'text');
            eyeIcon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            apiKeyInput.attr('type', 'password');
            eyeIcon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Fetch Available Models via AJAX
    $('#btnFetchModels').on('click', function() {
        const endpoint = $('#api_endpoint').val().trim();
        const key = $('#api_key').val().trim();

        if (!endpoint || !key) {
            Swal.fire({
                icon: 'warning',
                title: 'Input Tidak Lengkap',
                text: 'Harap isi Endpoint dan API Key terlebih dahulu untuk mengambil daftar model.'
            });
            return;
        }

        const originalBtnText = $('#btnFetchModels').html();
        $('#btnFetchModels').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Loading...');

        $.ajax({
            url: 'api/config_llm.php',
            type: 'POST',
            dataType: 'json',
            global: false,
            data: {
                action: 'fetch_models',
                api_endpoint: endpoint,
                api_key: key
            },
            success: function(res) {
                $('#btnFetchModels').prop('disabled', false).html(originalBtnText);
                if (res.status === 'success') {
                    const modelSelect = $('#model');
                    const fallbackSelect = $('#fallback_models');
                    
                    if (res.models && res.models.length > 0) {
                        res.models.forEach(function(modelName) {
                            // Cek apakah modelName sudah ada di option model
                            if (modelSelect.find("option[value='" + modelName + "']").length === 0) {
                                modelSelect.append(new Option(modelName, modelName, false, false));
                            }
                            // Cek apakah modelName sudah ada di option fallback
                            if (fallbackSelect.find("option[value='" + modelName + "']").length === 0) {
                                fallbackSelect.append(new Option(modelName, modelName, false, false));
                            }
                        });
                        
                        // Refresh Select2
                        modelSelect.trigger('change');
                        fallbackSelect.trigger('change');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Daftar Model Diperbarui',
                            text: 'Berhasil memuat ' + res.models.length + ' model dari server LLM. Silakan buka dropdown untuk memilih.',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'Tidak Ada Model',
                            text: 'Daftar model kosong dari endpoint.'
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Mengambil Model',
                        text: res.message
                    });
                }
            },
            error: function(xhr) {
                $('#btnFetchModels').prop('disabled', false).html(originalBtnText);
                let errMsg = 'Gagal menghubungi server untuk mendapatkan daftar model.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: errMsg
                });
            }
        });
    });

    // Uji Koneksi LLM via AJAX
    $('#btnTestConn').on('click', function() {
        const endpoint = $('#api_endpoint').val().trim();
        const key = $('#api_key').val().trim();
        const model = $('#model').val().trim();
        const prompt = $('#prompt').val().trim();

        if (!endpoint || !key || !model) {
            Swal.fire({
                icon: 'warning',
                title: 'Input Tidak Lengkap',
                text: 'Harap isi Endpoint, API Key, dan Model sebelum melakukan pengujian.'
            });
            return;
        }

        const originalBtnText = $('#btnTestConn').html();
        $('#btnTestConn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Menguji...');

        $.ajax({
            url: 'api/config_llm.php',
            type: 'POST',
            dataType: 'json',
            global: false, // Matikan overlay global loading agar tidak mengganggu visual form
            data: {
                action: 'test',
                api_endpoint: endpoint,
                api_key: key,
                model: model,
                prompt: prompt
            },
            success: function(res) {
                $('#btnTestConn').prop('disabled', false).html(originalBtnText);
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sukses Koneksi LLM',
                        text: res.message,
                        customClass: {
                            popup: 'glass-modal'
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Koneksi LLM',
                        text: res.message
                    });
                }
            },
            error: function(xhr) {
                $('#btnTestConn').prop('disabled', false).html(originalBtnText);
                let errMsg = 'Terjadi kesalahan sistem saat mencoba menghubungi API.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: errMsg
                });
            }
        });
    });

    // Simpan Konfigurasi via AJAX (Rule 11)
    $('#formLLM').on('submit', function(e) {
        e.preventDefault();
        
        // Validasi Bootstrap
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        const formData = $(this).serialize();
        const saveBtn = $('#btnSaveConfig');
        const originalBtnText = saveBtn.html();

        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Menyimpan...');

        $.ajax({
            url: 'api/config_llm.php',
            type: 'POST',
            dataType: 'json',
            global: false,
            data: formData,
            success: function(res) {
                // Dopamine visual feedback (Rule 12)
                if (res.status === 'success') {
                    saveBtn.removeClass('btn-primary').addClass('btn-success').html('<i class="fas fa-check me-2"></i> Tersimpan!');
                    setTimeout(function() {
                        saveBtn.removeClass('btn-success').addClass('btn-primary').html(originalBtnText).prop('disabled', false);
                    }, 2000);
                } else {
                    saveBtn.prop('disabled', false).html(originalBtnText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan',
                        text: res.message
                    });
                }
            },
            error: function(xhr) {
                saveBtn.prop('disabled', false).html(originalBtnText);
                let errMsg = 'Gagal mengirim permintaan penyimpanan.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: errMsg
                });
            }
        });
    });
});
</script>
<?php $page_js = ob_get_clean(); ?>

<?php include 'includes/footer.php'; ?>
