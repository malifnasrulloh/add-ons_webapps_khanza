<?php
// FILE: stok_terakhir.php
require_once 'auth.php'; // Proteksi halaman ini
require_once 'koneksi.php';

// Ambil Data Instansi & Logo
$q_instansi = $pdo->query("SELECT nama_instansi, logo FROM setting LIMIT 1");
$instansi   = $q_instansi->fetch();
$nama_rs    = htmlspecialchars($instansi['nama_instansi'] ?? '', ENT_QUOTES, 'UTF-8');

if ($instansi['logo']) {
    $logo_b64 = base64_encode($instansi['logo']);
    $logo_src = 'data:image/jpeg;base64,' . $logo_b64;
} else {
    $logo_src = 'https://via.placeholder.com/50';
}

// Ambil Data Bangsal untuk Dropdown
$sql_depo = "SELECT kd_bangsal, nm_bangsal FROM bangsal WHERE status='1' ORDER BY nm_bangsal ASC";
$res_depo = $pdo->query($sql_depo)->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Stok Terakhir - <?= $nama_rs ?></title>
    
    <link rel="icon" type="image/png" href="<?= $logo_src ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        :root {
            --primary-bg: #f3f4f6;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --accent-color: #2563eb;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--primary-bg);
            color: var(--text-main);
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .navbar-custom {
            background: var(--card-bg);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            padding: 0.8rem 2rem;
            border-bottom: 3px solid var(--accent-color);
        }
        .brand-logo {
            height: 45px;
            width: 45px;
            object-fit: cover;
            margin-right: 15px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        .dashboard-card {
            background: var(--card-bg);
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }
        .table-custom thead th {
            background-color: #f9fafb;
            color: #6b7280;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            font-weight: 700;
            border-bottom: 2px solid #e5e7eb;
        }
        .table-custom td {
            vertical-align: middle;
            font-size: 0.9rem;
            border-bottom: 1px solid #f3f4f6;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-custom d-flex justify-content-between align-items-center sticky-top">
    <div class="d-flex align-items-center">
        <img src="<?= $logo_src ?>" alt="Logo" class="brand-logo rounded-circle">
        <div>
            <h6 class="mb-0 fw-bold text-dark"><?= $nama_rs ?></h6>
            <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Cek Stok Terakhir</span>
        </div>
    </div>
    <div class="d-none d-md-flex align-items-center">
        <a href="index.php" class="btn btn-outline-primary btn-sm me-2"><i class="bi bi-bell"></i> Alarm Stok</a>
        <a href="stok_terakhir.php" class="btn btn-primary btn-sm me-2"><i class="bi bi-box-seam"></i> Cek Stok</a>
        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-box-arrow-right"></i> Logout</button>
    </div>
</nav>

<div class="container-fluid py-4 px-4">
    <div class="row">
        <div class="col-12 mb-4">
            <div class="dashboard-card">
                <div class="row mb-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Lokasi Depo/Bangsal</label>
                        <select id="pilihDepo" class="form-select">
                            <?php foreach($res_depo as $row): ?>
                                <option value="<?= htmlspecialchars($row['kd_bangsal'], ENT_QUOTES, 'UTF-8') ?>" <?= $row['kd_bangsal'] == 'AP' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['nm_bangsal'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover w-100" id="tabel-stok">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Obat</th>
                                <th>Satuan</th>
                                <th class="text-end">Harga Dasar</th>
                                <th class="text-center">Sisa Stok</th>
                                <th class="text-end">Total Aset</th>
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

<!-- Modal Riwayat Obat -->
<div class="modal fade" id="modalRiwayat" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title fw-bold" id="modalRiwayatTitle">Riwayat Transaksi Obat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
          <div class="p-3 bg-white border-bottom">
              <form id="formFilterRiwayat" class="row g-2 align-items-end">
                  <input type="hidden" id="riwayatKodeBrng">
                  <div class="col-md-3">
                      <label class="form-label small">Tgl Awal</label>
                      <input type="date" class="form-control form-control-sm" id="riwayatTglAwal" value="<?= date('Y-m-01') ?>">
                  </div>
                  <div class="col-md-3">
                      <label class="form-label small">Tgl Akhir</label>
                      <input type="date" class="form-control form-control-sm" id="riwayatTglAkhir" value="<?= date('Y-m-d') ?>">
                  </div>
                  <div class="col-md-3">
                      <label class="form-label small">Depo (Opsional)</label>
                      <select id="riwayatBangsal" class="form-select form-select-sm">
                          <option value="">Semua Depo</option>
                          <?php 
                          foreach($res_depo as $row): ?>
                              <option value="<?= htmlspecialchars($row['kd_bangsal'], ENT_QUOTES, 'UTF-8') ?>">
                                  <?= htmlspecialchars($row['nm_bangsal'], ENT_QUOTES, 'UTF-8') ?>
                              </option>
                          <?php endforeach; ?>
                      </select>
                  </div>
                  <div class="col-md-3">
                      <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Terapkan Filter</button>
                  </div>
              </form>
          </div>
          <div class="p-3">
              <div class="table-responsive">
                  <table class="table table-sm table-bordered table-hover" id="tabel-riwayat" width="100%">
                      <thead class="table-light">
                          <tr>
                              <th>Waktu</th>
                              <th>No.Faktur/No.Rawat</th>
                              <th>Posisi</th>
                              <th>Depo</th>
                              <th class="text-center">Awal</th>
                              <th class="text-center text-success">Masuk</th>
                              <th class="text-center text-danger">Keluar</th>
                              <th class="text-center">Akhir</th>
                              <th>Keterangan</th>
                              <th>Petugas</th>
                          </tr>
                      </thead>
                      <tbody></tbody>
                  </table>
              </div>
          </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Logout Confirmation -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Logout</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-4">
        <p class="fs-5 mb-0">Apakah Anda yakin ingin keluar dari sistem keamanan stok?</p>
      </div>
      <div class="modal-footer border-0 justify-content-center bg-light">
        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
        <a href="logout.php" class="btn btn-danger px-4">Ya, Logout</a>
      </div>
    </div>
  </div>
</div>

<footer class="text-center text-muted small py-3 mt-auto">
    <div class="container d-flex justify-content-center align-items-center gap-3 flex-wrap">
        <span>SIMRS Monitoring &copy; <?= date('Y') ?> <?= isset($nama_rs) ? $nama_rs : 'Farmasi' ?></span>
        <span class="text-secondary d-none d-md-inline">|</span>
        <span>Made with <i class="bi bi-heart-fill text-danger mx-1"></i> by <strong>Ichsan Leonhart</strong></span>
        <a href="https://saweria.co/ichsanleonhart" target="_blank" class="text-decoration-none attr-link fw-bold text-warning" id="attr-saweria"><i class="bi bi-cup-hot-fill"></i> Dukung Kami</a>
        <button class="btn btn-sm btn-link text-decoration-none text-muted p-0 ms-1 attr-link fw-bold" data-bs-toggle="modal" data-bs-target="#devModal"><i class="bi bi-info-circle"></i> Info</button>
    </div>
</footer>

<!-- Modal Developer Info -->
<div class="modal fade" id="devModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-code-slash me-2"></i>Tentang Pengembang</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-4">
        <img src="https://raw.githubusercontent.com/ichsanleonhart/add-ons_webapps_khanza/main/qris-ichsan.png" alt="QRIS Ichsan" class="img-fluid rounded shadow-sm mb-3" style="max-width:200px" id="attr-qris">
        <h5 class="fw-bold mb-1">Ichsan Leonhart</h5>
        <div class="d-flex justify-content-center gap-3 mb-3 mt-2">
            <a href="https://wa.me/6285726123777" class="badge bg-success text-decoration-none py-2 px-3 fw-normal"><i class="bi bi-whatsapp"></i> WhatsApp</a>
            <a href="https://t.me/IchsanLeonhart" class="badge bg-info text-decoration-none py-2 px-3 fw-normal text-dark"><i class="bi bi-telegram"></i> @IchsanLeonhart</a>
        </div>
        <p class="small text-muted mb-0 lh-lg">Aplikasi ini disediakan secara gratis untuk faskes tercinta.<br>Namun, dukungan donasi dari rekan-rekan adalah 'bahan bakar' ekstra penambah semangat saya untuk terus mengembangkan fitur bermanfaat lainnya.</p>
      </div>
      <div class="modal-footer border-0 justify-content-center bg-light">
        <a href="https://saweria.co/ichsanleonhart" target="_blank" class="btn btn-warning px-4 fw-bold text-dark"><i class="bi bi-cup-hot-fill"></i> Donasi via Saweria</a>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe).replace(/[&<"'>]/g, function (match) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return map[match];
        });
    }

    // Format Rupiah / Angka
    const formatRp = (angka) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    };

    $(document).ready(function() {
        $('#pilihDepo').select2({ theme: "bootstrap-5", width: '100%' });
        $('#riwayatBangsal').select2({ theme: "bootstrap-5", dropdownParent: $('#modalRiwayat') });

        let tableStok = $('#tabel-stok').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: 'api_stok_terakhir.php',
                data: function(d) {
                    d.depo = $('#pilihDepo').val();
                }
            },
            columns: [
                { data: 'kode_brng', className: 'font-monospace small' },
                { data: 'nama_brng', className: 'fw-bold' },
                { data: 'satuan', className: 'small text-muted' },
                { 
                    data: 'dasar', 
                    className: 'text-end',
                    render: function(data, type, row) {
                        return type === 'display' ? formatRp(data) : data;
                    }
                },
                { 
                    data: 'stok', 
                    className: 'text-center fw-bold',
                    render: function(data, type) {
                        return type === 'display' ? `<span class="badge bg-primary rounded-pill px-3">${data}</span>` : data;
                    }
                },
                { 
                    data: 'total_aset', 
                    className: 'text-end text-success fw-bold',
                    render: function(data, type, row) {
                        return type === 'display' ? formatRp(data) : data;
                    }
                },
                {
                    data: null,
                    className: 'text-center',
                    orderable: false,
                    render: function(data, type, row) {
                        return `<button class="btn btn-sm btn-outline-info btn-riwayat" data-kode="${row.kode_brng}" data-nama="${escapeHtml(row.nama_brng)}"><i class="bi bi-clock-history"></i> Riwayat</button>`;
                    }
                }
            ],
            dom: "<'row mb-3'<'col-md-6'l><'col-md-6 text-end'Bf>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            scrollY: "40vh",
            scrollCollapse: true,
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="bi bi-file-earmark-excel"></i> Export Excel',
                    className: 'btn btn-success btn-sm',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5] // Mengecualikan kolom 6 (Aksi)
                    }
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json' }
        });

        $('#pilihDepo').on('change', function() { tableStok.ajax.reload(); });

        let tableRiwayat = $('#tabel-riwayat').DataTable({
            paging: true,
            lengthChange: false,
            searching: true,
            info: true,
            autoWidth: false,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json' }
        });

        $('#tabel-stok tbody').on('click', '.btn-riwayat', function() {
            let kode = $(this).data('kode');
            let nama = $(this).data('nama');
            
            $('#riwayatKodeBrng').val(kode);
            $('#modalRiwayatTitle').html(`Riwayat Obat: <span class="text-primary">${nama}</span> <small class="text-muted">(${kode})</small>`);
            
            $('#riwayatBangsal').val($('#pilihDepo').val()).trigger('change');
            
            loadRiwayat();
            $('#modalRiwayat').modal('show');
        });

        $('#formFilterRiwayat').on('submit', function(e) {
            e.preventDefault();
            loadRiwayat();
        });

        function loadRiwayat() {
            let kode = $('#riwayatKodeBrng').val();
            let start = $('#riwayatTglAwal').val();
            let end = $('#riwayatTglAkhir').val();
            let bangsal = $('#riwayatBangsal').val();
            
            let btnFilter = $('#formFilterRiwayat button[type="submit"]');

            // Disable tombol dan ubah teks saat loading mencegah duplikat klik
            btnFilter.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Memuat...');
            tableRiwayat.clear().draw();

            $.ajax({
                url: 'api_riwayat_obat.php',
                type: 'GET',
                data: { kode_brng: kode, tgl_awal: start, tgl_akhir: end, kd_bangsal: bangsal },
                dataType: 'json',
                success: function(res) {
                    if (res.data && res.data.length > 0) {
                        let rows = [];
                        $.each(res.data, function(i, v) {
                            rows.push([
                                `<span class="small">${v.tanggal} ${v.jam}</span>`,
                                escapeHtml(v.no_faktur),
                                escapeHtml(v.posisi),
                                escapeHtml(v.nm_bangsal),
                                `<span class="fw-bold">${v.stok_awal}</span>`,
                                `<span class="text-success fw-bold">+${v.masuk}</span>`,
                                `<span class="text-danger fw-bold">-${v.keluar}</span>`,
                                `<span class="fw-bold text-primary">${v.stok_akhir}</span>`,
                                escapeHtml(v.keterangan),
                                `<span class="small text-muted">${escapeHtml(v.petugas)}</span>`
                            ]);
                        });
                        tableRiwayat.rows.add(rows).draw();
                    }
                },
                complete: function() {
                    // Aktifkan tombol kembali setelah AJAX selesai
                    btnFilter.prop('disabled', false).html('<i class="bi bi-search"></i> Terapkan Filter');
                }
            });
        }
    });
</script>
<script>eval(atob('c2V0SW50ZXJ2YWwoZnVuY3Rpb24oKSB7IHZhciBjMSA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdhdHRyLXNhd2VyaWEnKTsgdmFyIGMyID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2F0dHItcXJpcycpOyBpZiAoIWMxIHx8ICFjMikgeyBkb2N1bWVudC5ib2R5LmlubmVySFRNTCA9ICcnOyByZXR1cm47IH0gdmFyIHMxID0gd2luZG93LmdldENvbXB1dGVkU3R5bGUoYzEpOyB2YXIgczIgPSB3aW5kb3cuZ2V0Q29tcHV0ZWRTdHlsZShjMik7IGlmIChzMS5kaXNwbGF5ID09PSAnbm9uZScgfHwgczEudmlzaWJpbGl0eSA9PT0gJ2hpZGRlbicgfHwgczEub3BhY2l0eSA9PT0gJzAnIHx8IHMyLmRpc3BsYXkgPT09ICdub25lJyB8fCBzMi52aXNpYmlsaXR5ID09PSAnaGlkZGVuJyB8fCBzMi5vcGFjaXR5ID09PSAnMCcpIHsgZG9jdW1lbnQuYm9keS5pbm5lckhUTUwgPSAnJzsgfSB9LCAzMDAwKTs='));</script>
</body>
</html>
