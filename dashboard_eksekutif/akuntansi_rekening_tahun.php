<?php
/**
 * akuntansi_rekening_tahun.php
 * Manajemen Saldo Rekening Tahunan — Replika DlgRekeningTahun.java
 * 
 * CRUD: Tambah / Edit / Hapus saldo awal rekening per tahun.
 * Kolom tampil: Tahun, Kode, Nama, Tipe, Balance, Saldo Awal, Mutasi D, Mutasi K, Saldo Akhir
 */
$page_title = "Rekening Tahunan";
require_once('includes/header.php');
?>
<style>
.page-header {
    background: linear-gradient(135deg,rgba(99,102,241,.15),rgba(168,85,247,.15));
    border:1px solid rgba(99,102,241,.3); border-radius:16px;
    padding:20px 24px; margin-bottom:24px; display:flex; align-items:center; gap:16px;
}
.page-header .icon-box {
    width:52px;height:52px;background:linear-gradient(135deg,#6366f1,#a855f7);
    border-radius:14px;display:flex;align-items:center;justify-content:center;
    font-size:1.4rem;color:#fff;box-shadow:0 4px 15px rgba(99,102,241,.4);flex-shrink:0;
}
.page-header h1{font-size:1.3rem;font-weight:700;margin:0}
.page-header p{font-size:.82rem;margin:0;opacity:.7}

.filter-glass{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:14px;padding:18px 20px;margin-bottom:18px;backdrop-filter:blur(8px);}

/* FORM AREA */
.form-card{background:rgba(99,102,241,.06);border:1px solid rgba(99,102,241,.2);border-radius:14px;padding:20px;margin-bottom:18px;}
.form-card .form-label{font-size:.8rem;font-weight:600;opacity:.8}
.rek-chip-rt{display:inline-flex;align-items:center;gap:6px;background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);border-radius:20px;padding:5px 12px;font-size:.8rem;cursor:pointer;transition:all .2s;width:100%}
.rek-chip-rt:hover{background:rgba(99,102,241,.25)}
.rek-chip-rt .kd{font-weight:700;color:#818cf8}
.mode-badge{display:inline-block;padding:3px 10px;border-radius:6px;font-size:.75rem;font-weight:700}
.mode-new  {background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.3)}
.mode-edit {background:rgba(245,158,11,.15);color:#fbbf24;border:1px solid rgba(245,158,11,.3)}

/* TABLE */
.tbl-rt thead th{
    background:linear-gradient(135deg,rgba(99,102,241,.2),rgba(168,85,247,.15))!important;
    color:#e2e8f0!important;font-size:.77rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;
    border-color:rgba(255,255,255,.1)!important;white-space:nowrap;
}
.tbl-rt .num-right{text-align:right;font-variant-numeric:tabular-nums}
.tbl-rt .balance-D{color:#38bdf8}
.tbl-rt .balance-K{color:#4ade80}
.tbl-rt .neg-val{color:#f87171}
.tbl-rt .pos-val{color:#4ade80}
.tbl-rt tbody tr{cursor:pointer;transition:background .15s}
.tbl-rt tbody tr:hover{background:rgba(99,102,241,.07)!important}
.tbl-rt tbody tr.selected-row{background:rgba(99,102,241,.15)!important}
.kpi-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:18px}
.kpi-card{background:rgba(30,41,59,.7);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:12px 16px;text-align:center}
.kpi-card .kpi-lbl{font-size:.7rem;text-transform:uppercase;opacity:.6;margin-bottom:3px}
.kpi-card .kpi-val{font-size:1.1rem;font-weight:700;color:#e2e8f0}
</style>

<!-- PAGE HEADER -->
<div class="page-header">
    <div class="icon-box"><i class="fas fa-calendar-alt"></i></div>
    <div>
        <h1>Manajemen Rekening Tahunan</h1>
        <p>Konfigurasi saldo awal rekening per tahun · Replika DlgRekeningTahun Khanza</p>
    </div>
</div>

<!-- FORM AREA -->
<div class="form-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold m-0" style="color:#818cf8"><i class="fas fa-edit me-2"></i>Form Input</h6>
        <span class="mode-badge mode-new" id="mode-badge">+ Tambah Baru</span>
    </div>
    <div class="row g-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label">Tahun</label>
            <select id="inp-thn" class="form-select form-select-sm">
                <?php for($y = date('Y') + 1; $y >= 2015; $y--) { ?>
                <option value="<?php echo $y; ?>" <?php echo ($y == date('Y') ? 'selected' : ''); ?>><?php echo $y; ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Rekening</label>
            <div class="rek-chip-rt" id="btn-pilih-rek">
                <i class="fas fa-search text-indigo-400" style="font-size:.8rem;color:#818cf8"></i>
                <span id="rek-display" class="text-muted small">Klik untuk pilih rekening...</span>
            </div>
            <input type="hidden" id="inp-kd-rek">
            <input type="hidden" id="inp-kd-rek-orig"> <!-- kd_rek asli saat mode edit -->
        </div>
        <div class="col-md-3">
            <label class="form-label">Saldo Awal (Rp)</label>
            <input type="number" id="inp-saldo" class="form-control form-control-sm" placeholder="0" min="0" step="1">
        </div>
        <div class="col-md-3">
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-primary" id="btn-simpan"><i class="fas fa-save me-1"></i>Simpan</button>
                <button class="btn btn-sm btn-warning text-dark" id="btn-edit" style="display:none"><i class="fas fa-pen me-1"></i>Ganti</button>
                <button class="btn btn-sm btn-secondary" id="btn-baru"><i class="fas fa-plus me-1"></i>Baru</button>
            </div>
        </div>
    </div>
    <div id="form-alert" class="mt-2" style="display:none"></div>
</div>

<!-- FILTER & SEARCH -->
<div class="filter-glass d-flex gap-3 align-items-end flex-wrap">
    <div>
        <label class="form-label small fw-semibold mb-1">Tahun Tampil</label>
        <select id="filter-thn" class="form-select form-select-sm" style="min-width:90px">
            <?php for($y = date('Y') + 1; $y >= 2015; $y--) { ?>
            <option value="<?php echo $y; ?>" <?php echo ($y == date('Y') ? 'selected' : ''); ?>><?php echo $y; ?></option>
            <?php } ?>
        </select>
    </div>
    <div style="flex:1;min-width:200px">
        <label class="form-label small fw-semibold mb-1">Cari Rekening</label>
        <input type="text" id="filter-cari" class="form-control form-control-sm" placeholder="Kode / Nama / Tipe / Balance...">
    </div>
    <div>
        <button class="btn btn-sm btn-success" id="btn-tampil"><i class="fas fa-search me-1"></i>Tampilkan</button>
        <button class="btn btn-sm btn-outline-danger ms-1" id="btn-hapus-sel" style="display:none"><i class="fas fa-trash me-1"></i>Hapus</button>
    </div>
    <div class="ms-auto">
        <button class="btn btn-sm btn-outline-info" id="btn-export"><i class="fas fa-file-excel me-1"></i>Export CSV</button>
    </div>
</div>

<!-- KPI -->
<div class="kpi-bar" id="kpi-area" style="display:none">
    <div class="kpi-card"><div class="kpi-lbl">Total Rekening</div><div class="kpi-val" id="kpi-total">0</div></div>
    <div class="kpi-card"><div class="kpi-lbl">Total Saldo Awal</div><div class="kpi-val" id="kpi-saldo-awal" style="color:#818cf8">Rp 0</div></div>
    <div class="kpi-card"><div class="kpi-lbl">Total Saldo Akhir</div><div class="kpi-val" id="kpi-saldo-akhir" style="color:#4ade80">Rp 0</div></div>
</div>

<!-- TABLE -->
<div class="card shadow-sm" id="card-table" style="display:none">
    <div class="card-header py-2 d-flex align-items-center justify-content-between">
        <h6 class="m-0 fw-bold" style="color:#818cf8"><i class="fas fa-table me-2"></i>Data Rekening Tahunan</h6>
        <small class="text-muted" id="tbl-info"></small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:550px;overflow-y:auto">
            <table class="table table-sm table-hover mb-0 tbl-rt">
                <thead>
                    <tr>
                        <th>Thn</th>
                        <th>Kode</th>
                        <th>Nama Rekening</th>
                        <th>Tipe</th>
                        <th>Bal</th>
                        <th class="num-right">Saldo Awal</th>
                        <th class="num-right">Mut. Debet</th>
                        <th class="num-right">Mut. Kredit</th>
                        <th class="num-right">Saldo Akhir</th>
                    </tr>
                </thead>
                <tbody id="tbl-body">
                    <tr><td colspan="9" class="text-center text-muted py-5">Pilih tahun lalu klik Tampilkan</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- REKENING PICKER MODAL -->
<div class="modal fade" id="rek-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-search me-2 text-indigo-400" style="color:#818cf8"></i>Pilih Rekening</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2">
                <input type="text" id="rek-search" class="form-control form-control-sm mb-2" placeholder="Ketik kode atau nama...">
                <div style="max-height:400px;overflow-y:auto">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Kode</th><th>Nama</th><th>Tipe</th><th>Bal</th></tr></thead>
                        <tbody id="rek-modal-body"><tr><td colspan="4" class="text-center text-muted py-3">Ketik untuk mencari...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
var _allRows    = [];
var _rekModal   = null;
var _selectedRow= null; /* {thn, kd_rek} saat edit mode */
var _isEditMode = false;

function fRp(v) {
    if (!v && v !== 0) return 'Rp 0';
    var neg = v < 0, abs = Math.abs(Math.round(v));
    var s = abs.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return neg ? '(Rp ' + s + ')' : 'Rp ' + s;
}

/* ── Rekening Picker ── */
function openRekModal() {
    if (!_rekModal) _rekModal = new bootstrap.Modal(document.getElementById('rek-modal'));
    document.getElementById('rek-search').value = '';
    cariRek('');
    _rekModal.show();
    setTimeout(function(){ document.getElementById('rek-search').focus(); }, 350);
}

function cariRek(q) {
    fetch('api/akuntansi_cari_rekening.php?q=' + encodeURIComponent(q) + '&thn=' + document.getElementById('inp-thn').value)
        .then(function(r){ return r.json(); })
        .then(function(d) {
            var tb = document.getElementById('rek-modal-body');
            if (!d.success || !d.results.length) {
                tb.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ditemukan.</td></tr>'; return;
            }
            tb.innerHTML = d.results.map(function(r) {
                return '<tr onclick="pilihRek(\'' + r.kd_rek + '\',\'' + r.nm_rek.replace(/\\/g,'\\\\').replace(/'/g,"\\'") + '\')" style="cursor:pointer">'
                    + '<td><strong>' + r.kd_rek + '</strong></td><td>' + r.nm_rek + '</td>'
                    + '<td><span class="badge bg-secondary">' + r.tipe + '</span></td>'
                    + '<td><span class="badge ' + (r.balance==='D'?'bg-primary':'bg-success') + '">' + r.balance + '</span></td></tr>';
            }).join('');
        });
}

function pilihRek(kd, nm) {
    document.getElementById('inp-kd-rek').value = kd;
    document.getElementById('rek-display').innerHTML = '<span class="kd">' + kd + '</span>&nbsp;' + nm;
    document.getElementById('rek-display').className = '';
    if (_rekModal) _rekModal.hide();
}

/* ── Form Alert ── */
function showAlert(msg, type) {
    var el = document.getElementById('form-alert');
    el.innerHTML = '<div class="alert alert-' + type + ' py-2 small mb-0">' + msg + '</div>';
    el.style.display = 'block';
    if (type === 'success') setTimeout(function(){ el.style.display = 'none'; }, 3000);
}

/* ── Reset Form (Baru) ── */
function resetForm() {
    _isEditMode = false;
    _selectedRow = null;
    document.getElementById('inp-kd-rek').value = '';
    document.getElementById('inp-kd-rek-orig').value = '';
    document.getElementById('inp-saldo').value = '';
    document.getElementById('rek-display').textContent = 'Klik untuk pilih rekening...';
    document.getElementById('rek-display').className = 'text-muted small';
    document.getElementById('btn-simpan').style.display = '';
    document.getElementById('btn-edit').style.display = 'none';
    document.getElementById('mode-badge').textContent = '+ Tambah Baru';
    document.getElementById('mode-badge').className = 'mode-badge mode-new';
    document.getElementById('form-alert').style.display = 'none';
    document.querySelectorAll('.tbl-rt tbody tr.selected-row').forEach(function(r){ r.classList.remove('selected-row'); });
}

/* ── Tampilkan Data ── */
function tampil() {
    var thn  = document.getElementById('filter-thn').value;
    var cari = document.getElementById('filter-cari').value;
    document.getElementById('tbl-body').innerHTML = '<tr><td colspan="9" class="text-center py-4"><div class="spinner-border spinner-border-sm text-indigo-400"></div> Memuat...</td></tr>';
    document.getElementById('card-table').style.display = 'block';
    document.getElementById('kpi-area').style.display = 'none';
    document.getElementById('btn-hapus-sel').style.display = 'none';
    _selectedRow = null;

    fetch('api/akuntansi_rekening_tahun_api.php?thn=' + encodeURIComponent(thn) + '&cari=' + encodeURIComponent(cari))
        .then(function(r){ return r.json(); })
        .then(function(d) {
            if (!d.success) {
                document.getElementById('tbl-body').innerHTML = '<tr><td colspan="9" class="text-center text-danger py-4">' + d.message + '</td></tr>'; return;
            }
            _allRows = d.rows;
            renderTable(d.rows);
        });
}

function renderTable(rows) {
    if (!rows.length) {
        document.getElementById('tbl-body').innerHTML = '<tr><td colspan="9" class="text-center text-muted py-5"><i class="fas fa-inbox me-2"></i>Tidak ada data rekening tahunan untuk tahun ini.</td></tr>';
        document.getElementById('kpi-area').style.display = 'none';
        return;
    }
    var html = '', ttlSa = 0, ttlSakhir = 0;
    rows.forEach(function(row, i) {
        ttlSa     += row.saldo_awal;
        ttlSakhir += row.saldo_akhir;
        var neg    = row.saldo_akhir < 0;
        var valCls = neg ? 'neg-val' : 'pos-val';
        var balCls = row.balance === 'D' ? 'balance-D' : 'balance-K';
        html += '<tr data-idx="' + i + '">'
              + '<td><span class="badge bg-secondary">' + row.thn + '</span></td>'
              + '<td><code class="small">' + row.kd_rek + '</code></td>'
              + '<td class="small">' + row.nm_rek + '</td>'
              + '<td><span class="badge bg-secondary">' + row.tipe + '</span></td>'
              + '<td class="' + balCls + ' fw-bold">' + row.balance + '</td>'
              + '<td class="num-right small">' + fRp(row.saldo_awal) + '</td>'
              + '<td class="num-right small text-info">' + fRp(row.md) + '</td>'
              + '<td class="num-right small text-success">' + fRp(row.mk) + '</td>'
              + '<td class="num-right small ' + valCls + '">' + fRp(row.saldo_akhir) + '</td>'
              + '</tr>';
    });
    document.getElementById('tbl-body').innerHTML = html;
    document.querySelectorAll('.tbl-rt tbody tr').forEach(function(tr) {
        tr.addEventListener('click', function() { selectRow(parseInt(this.dataset.idx)); });
    });

    document.getElementById('tbl-info').textContent = rows.length + ' rekening';
    document.getElementById('kpi-total').textContent = rows.length;
    document.getElementById('kpi-saldo-awal').textContent = fRp(ttlSa);
    document.getElementById('kpi-saldo-akhir').textContent = fRp(ttlSakhir);
    document.getElementById('kpi-area').style.display = 'grid';
}

function selectRow(idx) {
    var row = _allRows[idx];
    if (!row) return;
    _isEditMode = true;
    _selectedRow = row;

    document.querySelectorAll('.tbl-rt tbody tr').forEach(function(r){ r.classList.remove('selected-row'); });
    document.querySelector('.tbl-rt tbody tr[data-idx="' + idx + '"]').classList.add('selected-row');

    document.getElementById('inp-thn').value    = row.thn;
    document.getElementById('inp-kd-rek').value = row.kd_rek;
    document.getElementById('inp-kd-rek-orig').value = row.kd_rek;
    document.getElementById('inp-saldo').value  = row.saldo_awal;
    document.getElementById('rek-display').innerHTML = '<span class="kd">' + row.kd_rek + '</span>&nbsp;' + row.nm_rek;
    document.getElementById('rek-display').className = '';
    document.getElementById('btn-simpan').style.display = 'none';
    document.getElementById('btn-edit').style.display   = '';
    document.getElementById('mode-badge').textContent = '✏ Edit Mode';
    document.getElementById('mode-badge').className   = 'mode-badge mode-edit';
    document.getElementById('btn-hapus-sel').style.display = '';
}

/* ── Simpan (INSERT) ── */
function simpan() {
    var thn    = document.getElementById('inp-thn').value;
    var kd_rek = document.getElementById('inp-kd-rek').value.trim();
    var saldo  = document.getElementById('inp-saldo').value.trim();

    if (!kd_rek) { showAlert('Pilih rekening terlebih dahulu.', 'warning'); return; }
    if (saldo === '' || isNaN(parseFloat(saldo))) { showAlert('Saldo awal harus berupa angka.', 'warning'); return; }

    fetch('api/akuntansi_rekening_tahun_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({thn: thn, kd_rek: kd_rek, saldo_awal: parseFloat(saldo)})
    }).then(function(r){ return r.json(); }).then(function(d) {
        showAlert(d.message, d.success ? 'success' : 'danger');
        if (d.success) { resetForm(); tampil(); }
    });
}

/* ── Ganti (UPDATE) ── */
function ganti() {
    var thn    = document.getElementById('inp-thn').value;
    var kd_rek = document.getElementById('inp-kd-rek-orig').value.trim(); // pakai kd_rek original
    var saldo  = document.getElementById('inp-saldo').value.trim();

    if (!kd_rek) { showAlert('Tidak ada data yang dipilih.', 'warning'); return; }
    if (saldo === '' || isNaN(parseFloat(saldo))) { showAlert('Saldo awal tidak valid.', 'warning'); return; }

    if (!confirm('Yakin mengubah saldo awal rekening ' + kd_rek + ' tahun ' + thn + '?')) return;

    fetch('api/akuntansi_rekening_tahun_api.php', {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({thn: thn, kd_rek: kd_rek, saldo_awal: parseFloat(saldo)})
    }).then(function(r){ return r.json(); }).then(function(d) {
        showAlert(d.message, d.success ? 'success' : 'danger');
        if (d.success) { resetForm(); tampil(); }
    });
}

/* ── Hapus (DELETE) ── */
function hapus() {
    if (!_selectedRow) { alert('Pilih baris yang ingin dihapus.'); return; }
    if (!confirm('Yakin HAPUS rekening ' + _selectedRow.kd_rek + ' tahun ' + _selectedRow.thn + '?\nTindakan ini tidak bisa dibatalkan.')) return;

    fetch('api/akuntansi_rekening_tahun_api.php', {
        method: 'DELETE',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({thn: _selectedRow.thn, kd_rek: _selectedRow.kd_rek})
    }).then(function(r){ return r.json(); }).then(function(d) {
        showAlert(d.message, d.success ? 'success' : 'danger');
        if (d.success) { resetForm(); tampil(); }
    });
}

/* ── Export CSV ── */
function exportCSV() {
    if (!_allRows.length) { alert('Tidak ada data.'); return; }
    var csv = 'Tahun,Kode,Nama,Tipe,Balance,Saldo Awal,Mutasi Debet,Mutasi Kredit,Saldo Akhir\n';
    _allRows.forEach(function(r) {
        csv += '"' + r.thn + '","' + r.kd_rek + '","' + r.nm_rek + '","' + r.tipe + '","' + r.balance + '",'
             + r.saldo_awal + ',' + r.md + ',' + r.mk + ',' + r.saldo_akhir + '\n';
    });
    var blob = new Blob(['\uFEFF' + csv], {type:'text/csv;charset=utf-8;'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'RekeningTahunan_' + document.getElementById('filter-thn').value + '.csv';
    a.click();
}

/* ── Event Bindings ── */
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btn-pilih-rek').addEventListener('click', openRekModal);
    document.getElementById('rek-search').addEventListener('input', function(){ cariRek(this.value); });
    document.getElementById('btn-simpan').addEventListener('click', simpan);
    document.getElementById('btn-edit').addEventListener('click', ganti);
    document.getElementById('btn-baru').addEventListener('click', resetForm);
    document.getElementById('btn-hapus-sel').addEventListener('click', hapus);
    document.getElementById('btn-tampil').addEventListener('click', tampil);
    document.getElementById('btn-export').addEventListener('click', exportCSV);
    document.getElementById('filter-cari').addEventListener('keypress', function(e){ if(e.key==='Enter') tampil(); });
    /* Load saat pertama buka */
    tampil();
});
</script>
<?php $page_js = ob_get_clean(); ?>
<?php require_once('includes/footer.php'); ?>
