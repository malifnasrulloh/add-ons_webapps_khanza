<?php
$page_title = "Analisis Komparatif";
require_once('includes/header.php');
?>
<style>
    .komp-header {
        background: linear-gradient(135deg, rgba(236,72,153,0.15), rgba(59,130,246,0.15));
        border: 1px solid rgba(236,72,153,0.3);
        border-radius: 16px; padding: 20px 24px; margin-bottom: 24px;
        display: flex; align-items: center; gap: 16px;
    }
    .komp-header .icon-box {
        width: 52px; height: 52px;
        background: linear-gradient(135deg, #ec4899, #3b82f6);
        border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: #fff;
        box-shadow: 0 4px 15px rgba(236,72,153,0.4); flex-shrink: 0;
    }
    .komp-header h1 { font-size: 1.4rem; font-weight: 700; margin: 0; }
    .komp-header p  { font-size: 0.82rem; margin: 0; opacity: 0.7; }

    .filter-glass {
        background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);
        border-radius: 14px; padding: 18px 20px; margin-bottom: 20px; backdrop-filter: blur(8px);
    }

    .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:992px) { .kpi-grid { grid-template-columns: 1fr; } }
    
    .kpi-card {
        background: rgba(30,41,59,0.7); border: 1px solid rgba(255,255,255,0.08);
        border-radius: 14px; padding: 20px;
    }
    .kpi-card .k-title { font-size: 1.1rem; font-weight: 700; color: #e2e8f0; margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 8px;}
    .kpi-card .k-val { font-size: 1.8rem; font-weight: 700; margin-bottom: 16px; }
    
    .comp-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; margin-bottom: 6px; padding: 6px 10px; border-radius: 6px; background: rgba(255,255,255,0.03); }
    .comp-row .c-label { opacity: 0.7; }
    .comp-row .c-val { font-family: monospace; font-weight: 600; }
    .comp-row .c-pct { padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 0.75rem; }
    
    .pct-pos-good { background: rgba(74,222,128,0.2); color: #4ade80; }
    .pct-neg-bad  { background: rgba(248,113,113,0.2); color: #f87171; }
    .pct-pos-bad  { background: rgba(248,113,113,0.2); color: #f87171; }
    .pct-neg-good { background: rgba(74,222,128,0.2); color: #4ade80; }
    .pct-neu      { background: rgba(148,163,184,0.2); color: #94a3b8; }
</style>

<div class="komp-header">
    <div class="icon-box"><i class="fas fa-not-equal"></i></div>
    <div>
        <h1>Analisis Komparatif (MoM & YoY)</h1>
        <p>Perbandingan performa finansial Bulan ini vs Bulan Lalu (MoM) & Tahun Lalu (YoY)</p>
    </div>
</div>

<div class="filter-glass">
    <div class="row g-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Bulan</label>
            <select id="inp-bln" class="form-select form-select-sm">
                <?php
                $bulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                foreach($bulan as $k => $v) {
                    $sel = (date('m') == $k) ? 'selected' : '';
                    echo "<option value='$k' $sel>$v</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Tahun</label>
            <select id="inp-thn" class="form-select form-select-sm">
                <?php
                $y = date('Y');
                for($i = $y; $i >= $y-5; $i--) {
                    echo "<option value='$i'>$i</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4">
            <button id="btn-load" class="btn btn-sm px-4 fw-semibold" style="background:linear-gradient(135deg,#ec4899,#3b82f6);color:#fff;border:none;" onclick="loadKomp()">
                <i class="fas fa-search me-1"></i> Bandingkan
            </button>
        </div>
    </div>
</div>

<div id="loader" class="text-center py-5" style="display:none;">
    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
    <p class="mt-3 text-muted">Menganalisis perbandingan periode...</p>
</div>

<div id="dashboard-content" style="display: none;">
    <!-- Info Metodologi untuk Tim Keuangan -->
    <div class="alert alert-info border-0 shadow-sm mb-4" style="background: rgba(56, 189, 248, 0.08); border-left: 4px solid #38bdf8 !important;">
        <div class="d-flex">
            <div class="me-3 mt-1"><i class="fas fa-info-circle fa-lg text-info"></i></div>
            <div class="small">
                <h6 class="fw-bold text-info mb-1" style="font-size:0.9rem;">Informasi Metodologi Perbandingan (Like-for-Like)</h6>
                <ul class="mb-0 ps-3 text-muted">
                    <li>Untuk perbandingan <b>Bulan Berjalan</b>, sistem menggunakan metode <b>Month-to-Date (MTD)</b>. Artinya, data hari ini (tgl <?php echo date('d'); ?>) dibandingkan dengan data tanggal 1 s/d <?php echo date('d'); ?> pada bulan/tahun lalu agar perbandingan tetap adil (Apple-to-Apple).</li>
                    <li><b>Indikator Warna:</b> <span class="text-success fw-bold">HIJAU</span> pada <i>Pendapatan/Laba</i> berarti kenaikan, namun pada <i>Biaya</i> berarti penurunan (Penghematan). Begitu juga sebaliknya untuk warna <span class="text-danger fw-bold">MERAH</span>.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="kpi-grid">
        <!-- Pendapatan -->
        <div class="kpi-card">
            <div class="k-title"><i class="fas fa-arrow-down me-2 text-success"></i>Pendapatan (Revenue)</div>
            <div class="k-val" id="val-rev" style="color:#4ade80;">Rp 0</div>
            
            <div class="comp-row">
                <div class="c-label">Bulan Lalu (MoM)</div>
                <div class="c-val" id="val-rev-mom">Rp 0</div>
                <div class="c-pct" id="pct-rev-mom">0%</div>
            </div>
            <div class="comp-row">
                <div class="c-label">Tahun Lalu (YoY)</div>
                <div class="c-val" id="val-rev-yoy">Rp 0</div>
                <div class="c-pct" id="pct-rev-yoy">0%</div>
            </div>
        </div>

        <!-- Biaya -->
        <div class="kpi-card">
            <div class="k-title"><i class="fas fa-arrow-up me-2 text-danger"></i>Biaya (Expenses)</div>
            <div class="k-val" id="val-exp" style="color:#f87171;">Rp 0</div>
            
            <div class="comp-row">
                <div class="c-label">Bulan Lalu (MoM)</div>
                <div class="c-val" id="val-exp-mom">Rp 0</div>
                <div class="c-pct" id="pct-exp-mom">0%</div>
            </div>
            <div class="comp-row">
                <div class="c-label">Tahun Lalu (YoY)</div>
                <div class="c-val" id="val-exp-yoy">Rp 0</div>
                <div class="c-pct" id="pct-exp-yoy">0%</div>
            </div>
        </div>

        <!-- Laba Bersih -->
        <div class="kpi-card">
            <div class="k-title"><i class="fas fa-piggy-bank me-2 text-info"></i>Laba Bersih (Net Profit)</div>
            <div class="k-val" id="val-net" style="color:#38bdf8;">Rp 0</div>
            
            <div class="comp-row">
                <div class="c-label">Bulan Lalu (MoM)</div>
                <div class="c-val" id="val-net-mom">Rp 0</div>
                <div class="c-pct" id="pct-net-mom">0%</div>
            </div>
            <div class="comp-row">
                <div class="c-label">Tahun Lalu (YoY)</div>
                <div class="c-val" id="val-net-yoy">Rp 0</div>
                <div class="c-pct" id="pct-net-yoy">0%</div>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
function fRp(angka) {
    if (angka === null || isNaN(angka)) return 'Rp 0';
    var abs = Math.abs(angka);
    var s = abs.toLocaleString('id-ID');
    return angka < 0 ? '(Rp ' + s + ')' : 'Rp ' + s;
}

function setPct(elId, val, type) {
    var el = $('#' + elId);
    var sign = val > 0 ? '+' : (val < 0 ? '' : '');
    el.text(sign + val + '%');
    
    el.removeClass('pct-pos-good pct-neg-bad pct-pos-bad pct-neg-good pct-neu');
    
    if (val == 0) { el.addClass('pct-neu'); return; }
    
    if (type === 'rev' || type === 'net') {
        if (val > 0) el.addClass('pct-pos-good');
        else el.addClass('pct-neg-bad');
    } else if (type === 'exp') {
        if (val > 0) el.addClass('pct-pos-bad');
        else el.addClass('pct-neg-good');
    }
}

function loadKomp() {
    var bln = $('#inp-bln').val();
    var thn = $('#inp-thn').val();
    
    $('#dashboard-content').hide();
    $('#loader').show();
    $('#btn-load').prop('disabled', true);

    $.ajax({
        url: 'api/data_akuntansi_komparatif.php',
        type: 'GET',
        data: { bln: bln, thn: thn },
        dataType: 'json',
        success: function(res) {
            $('#loader').hide();
            $('#btn-load').prop('disabled', false);
            if (!res.success) {
                $('#loader').html('<div class="alert alert-danger">Gagal memuat data: ' + res.message + '</div>').show();
                return;
            }
            $('#dashboard-content').fadeIn();
            
            var d = res.data;
            $('#val-rev').text(fRp(d.curr.rev));
            $('#val-exp').text(fRp(d.curr.exp));
            $('#val-net').text(fRp(d.curr.net));
            
            $('#val-rev-mom').text(fRp(d.mom.rev));
            $('#val-exp-mom').text(fRp(d.mom.exp));
            $('#val-net-mom').text(fRp(d.mom.net));
            
            $('#val-rev-yoy').text(fRp(d.yoy.rev));
            $('#val-exp-yoy').text(fRp(d.yoy.exp));
            $('#val-net-yoy').text(fRp(d.yoy.net));
            
            setPct('pct-rev-mom', d.mom_pct.rev, 'rev');
            setPct('pct-exp-mom', d.mom_pct.exp, 'exp');
            setPct('pct-net-mom', d.mom_pct.net, 'net');
            
            setPct('pct-rev-yoy', d.yoy_pct.rev, 'rev');
            setPct('pct-exp-yoy', d.yoy_pct.exp, 'exp');
            setPct('pct-net-yoy', d.yoy_pct.net, 'net');
        },
        error: function(err) {
            $('#loader').hide();
            $('#btn-load').prop('disabled', false);
            $('#loader').html('<div class="alert alert-danger">Kesalahan jaringan.</div>').show();
        }
    });
}

$(document).ready(function() {
    // loadKomp();
});
</script>
<?php $page_js = ob_get_clean(); ?>
<?php require_once('includes/footer.php'); ?>