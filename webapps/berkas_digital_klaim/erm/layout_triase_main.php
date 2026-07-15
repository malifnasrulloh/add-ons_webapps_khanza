<style>
    /* Reset & Base Style */
    * { box-sizing: border-box; }
    body { font-family: Tahoma, Arial, sans-serif; font-size: 11px; margin: 0; line-height: 1.3; }
    
    /* Table Styling Utama */
    table.main-table { width: 100%; border-collapse: collapse; margin-bottom: 0px; page-break-inside: avoid; table-layout: fixed; }
    table.main-table td, table.main-table th { border: 1px solid #000; padding: 3px 5px; vertical-align: top; word-wrap: break-word; }
    
    /* Font Helpers */
    .fs-10 { font-size: 10px; }
    .fs-12 { font-size: 12px; }
    .fs-14 { font-size: 14px; }
    .text-bold { font-weight: bold; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    
    /* Spesifik Triase */
    .bg-triase { background-color: <?= $config['warna_bg'] ?>; color: <?= $config['warna_txt'] ?>; font-weight: bold; text-align: center; }
    .bg-section { background-color: #E8E8AD; font-weight: bold; text-align: center; } /* Warna krem gelap khas Khanza */
</style>

<table class="main-table">
    <tr>
        <td width="8%" style="border-right: 0; vertical-align: middle;">
            <img src="<?= $logo_b64 ?>" style="width: 55px;">
        </td>
        <td width="40%" style="border-left: 0; border-right: 2px solid #000;">
            <div class="text-center">
                <span class="fs-14 text-bold"><?= strtoupper($setting['nama_instansi']) ?></span><br>
                <span class="fs-10">
                    <?= $setting['alamat_instansi'] ?>, <?= $setting['kabupaten'] ?>, <?= $setting['propinsi'] ?><br>
                    <?= $setting['kontak'] ?><br>
                    E-mail : <?= $setting['email'] ?>
                </span>
            </div>
        </td>
        <td width="52%" style="padding: 0;">
            <table width="100%" style="border-collapse: collapse; border: none;">
                <tr>
                    <td width="35%" style="border:0; padding: 2px 5px;">Nomor RM</td>
                    <td width="65%" style="border:0; padding: 2px 5px;">: <b><?= $d_umum['no_rkm_medis'] ?></b></td>
                </tr>
                <tr>
                    <td style="border:0; padding: 2px 5px;">Nama</td>
                    <td style="border:0; padding: 2px 5px;">: <?= $d_umum['nm_pasien'] ?></td>
                </tr>
                <tr>
                    <td style="border:0; padding: 2px 5px;">Tanggal Lahir</td>
                    <td style="border:0; padding: 2px 5px;">: <?= date('d-m-Y', strtotime($d_umum['tgl_lahir'])) ?></td>
                </tr>
                <tr>
                    <td style="border:0; padding: 2px 5px;">Jenis Kelamin</td>
                    <td style="border:0; padding: 2px 5px;">: <?= ($d_umum['jk']=='L'?'Laki-laki':'Perempuan') ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="main-table">
    <tr>
        <td class="bg-triase" style="padding: 5px; border-top: 0;">
            TRIASE PASIEN GAWAT DARURAT
        </td>
    </tr>
    <tr>
        <td class="text-center fs-10" style="border-top: 0;">
            Triase dilakukan segera setelah pasien datang dan sebelum pasien/ keluarga mendaftar di TPP IGD
        </td>
    </tr>
</table>

<table class="main-table">
    <tr>
        <td width="50%">Tanggal Kunjungan : <?= date('d-m-Y', strtotime($tgl_triase_fix)) ?></td>
        <td width="50%">Pukul : <?= date('H:i:s', strtotime($tgl_triase_fix)) ?></td>
    </tr>
</table>
<table class="main-table">
    <tr>
        <td width="30%">Cara Datang</td>
        <td width="70%"><?= $d_umum['cara_masuk'] ?? '-' ?></td>
    </tr>
    <tr>
        <td width="30%">Macam Kasus</td>
        <td width="70%"><?= $d_umum['macam_kasus'] ?? '-' ?></td>
    </tr>
</table>

<table class="main-table">
    <tr class="bg-section">
        <td width="30%">KETERANGAN</td>
        <td width="70%">TRIASE <?= $config['sub_judul'] ?></td>
    </tr>
    <tr>
        <td height="60">
            <b class="fs-10">ANAMNESA SINGKAT</b>
        </td>
        <td>
            <?= !empty($d_umum['keluhan_utama']) ? nl2br($d_umum['keluhan_utama']) : '-' ?>
        </td>
    </tr>
    <tr>
        <td>
            <b class="fs-10">TANDA VITAL</b>
        </td>
        <td>
            Suhu (C) : <?= $d_umum['suhu'] ?>, Nyeri : <?= $d_umum['nyeri'] ?>, Tensi : <?= $d_umum['tensi'] ?>, Nadi(/menit) : <?= $d_umum['nadi'] ?>, Saturasi O²(%) : <?= $d_umum['saturasi_o2'] ?>, Respirasi(/menit) : <?= $d_umum['napas'] ?>
        </td>
    </tr>
</table>

<table class="main-table">
    <tr class="bg-section text-center">
        <td width="30%">PEMERIKSAAN</td>
        <td class="bg-triase" width="70%">URGENSI</td>
    </tr>
    <?php if(!empty($checklist_data)): ?>
        <?php foreach($checklist_data as $check): ?>
        <tr>
            <td><?= strtoupper($check['kategori']); ?></td>
            <td style="background-color: <?= $config['warna_bg']; ?>; color: <?= $config['warna_txt']; ?>;">
                <?= $check['nilai']; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="2" class="text-center">- Tidak ada data checklist -</td></tr>
    <?php endif; ?>
    <tr>
        <td>PLAN</td>
        <td style="background-color: <?= $config['warna_bg']; ?>; color: <?= $config['warna_txt']; ?>;"><?= $d_khusus['plan'] ?? 'Zona Kuning' ?></td>
    </tr>
</table>

<table class="main-table">
    <tr class="bg-section text-center">
		<td width="30%"> &nbsp; </td>
        <td width="70%"class="text-center">
            Petugas Triase
        </td>
    </tr>
    <tr>
        <td width="30%">Tanggal & Jam</td>
        <td width="70%"><?= formatTgl($tgl_triase_fix) ?></td>
    </tr>
    <tr>
        <td width="30%">Catatan</td>
        <td width="70%">
            <?= !empty($d_khusus['catatan']) ? nl2br($d_khusus['catatan']) : '-' ?>
        </td>
    </tr>
    <tr>
        <td width="30%" style="border-top: 0;">Dokter/Petugas Jaga IGD</td>
        <td width="70%" style="border-top: 0; vertical-align: middle; height: 70px;">            
            <?php 
            // 1. Render QR Code DULUAN dengan float right agar posisi aman
            if($qr_b64): ?>
                <div style="float: right; margin-right: 10px;">
                    <img src="<?= $qr_b64 ?>" width="60">
                </div>
            <?php endif; ?>

            <div style="margin-top: 15px; float: left;">
                <?= $nama_perawat ?>
            </div>
        </td>
    </tr>
</table>