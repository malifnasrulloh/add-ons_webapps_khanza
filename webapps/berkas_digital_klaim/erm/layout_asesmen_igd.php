<style>
    /* Reset & Base Fonts */
    * { box-sizing: border-box; }
    body { font-family: Tahoma, Arial, sans-serif; font-size: 11px; margin: 0; padding: 0; }
    
    /* Table Styling */
    table.main-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; page-break-inside: avoid; }
    table.main-table td, table.main-table th { border: 1px solid #000; padding: 3px 5px; vertical-align: top; }
    
    /* Header Styles */
    .header-gray { background-color: #EFEFEF; font-weight: bold; padding: 5px; border: 1px solid #000; font-size: 11px; }
    .no-border-top { border-top: none !important; }
    .no-border-bottom { border-bottom: none !important; }
    .text-center { text-align: center; }
    .text-bold { font-weight: bold; }
    .fs-10 { font-size: 10px; }
    .fs-12 { font-size: 12px; }
    .fs-14 { font-size: 14px; }
    
    /* Layout Helper */
    .w-50 { width: 50%; }
    .w-33 { width: 33.33%; }
    .h-20 { height: 20px; }
    .h-40 { height: 40px; }
    
    /* Kop Surat Custom */
    .kop-wrapper { width: 100%; border-bottom: 3px double #000; margin-bottom: 5px; padding-bottom: 5px; }
    .kop-table { width: 100%; border: none; }
    .kop-table td { border: none; vertical-align: middle; }
</style>

<div class="kop-wrapper">
    <table class="kop-table">
        <tr>
            <td width="15%" class="text-center">
                <img src="<?= $logo_src ?>" style="width: 70px; height: 70px;">
            </td>
            <td width="85%" class="text-center">
                <span class="fs-14 text-bold"> <b style="font-size:20px"><?= strtoupper($setting['nama_instansi']) ?> </b></span><br>
                <span class="fs-10"><?= $setting['alamat_instansi'] ?>, <?= $setting['kabupaten'] ?></span><br>
                <span class="fs-10">Telp: <?= $setting['kontak'] ?> | E-mail: <?= $setting['email'] ?></span>
            </td>
        </tr>
    </table>
</div>

<div class="text-center text-bold fs-12" style="margin-bottom: 5px; background-color: #EFEFEF; padding: 3px; border: 1px solid #000; border-bottom: none;">
    PENILAIAN AWAL MEDIS IGD
</div>

<table class="main-table" style="margin-top: -6px;">
    <tr>
        <td width="45%" style="padding: 0;">
            <table width="100%" style="border: none;">
                <tr>
                    <td style="border: none; width: 70px;">No. RM</td>
                    <td style="border: none;">: <b><?= $data['no_rkm_medis'] ?></b></td>
                </tr>
                <tr>
                    <td style="border: none; width: 70px;">Nama Pasien</td>
                    <td style="border: none;">: <?= $data['nm_pasien'] ?></td>
                </tr>
            </table>
        </td>
        <td width="23%" style="padding: 0;">
            <table width="100%" style="border: none;">
                <tr>
                    <td style="border: none; ">Jenis Kelamin</td>
                    <td style="border: none; ">: <?= $data['jk'] == 'L' ? 'Laki-Laki' : 'Perempuan' ?></td>
                </tr>
                <tr>
                    <td style="border: none; ">Tanggal Lahir</td>
                    <td style="border: none;">: <?= isset($data['tgl_lahir']) ? date('d/m/Y', strtotime($data['tgl_lahir'])) : '-' ?></td>
                </tr>
            </table>
        </td>
        <td width="32%" style="padding: 0;">
            <table width="100%" style="border: none;">
                <tr>
                    <td style="border: none; ">Tanggal</td>
                    <td style="border: none; ">: <?= isset($data['tanggal']) ? date('d/m/Y H:i:s', strtotime($data['tanggal'])) : '-' ?></td>
                </tr>
                <tr>
                    <td style="border: none;">Anamnesis</td>
                    <td style="border: none;">: <?= $data['anamnesis'] ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="header-gray">I. RIWAYAT KESEHATAN</div>
<table class="main-table" style="margin-top: 0;">
    <tr>
        <td colspan="2">
            Keluhan Utama : <?= $data['keluhan_utama'] ?>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            Riwayat Penyakit Sekarang : <?= $data['rps'] ?>
        </td>
    </tr>
    <tr>
        <td width="50%">Riwayat Penyakit Dahulu : <?= $data['rpd'] ?></td>
        <td width="50%">Riwayat Penyakit dalam Keluarga : <?= $data['rpk'] ?></td>
    </tr>
    <tr>
        <td>Riwayat Pengobatan : <?= $data['rpo'] ?></td>
        <td>Riwayat Alergi : <?= $data['alergi'] ?></td>
    </tr>
</table>

<div class="header-gray" style="margin-top: -4px;">II. PEMERIKSAAN FISIK</div>
<table class="main-table" style="margin-top: 0;">
    <tr>
        <td width="33%">Keadaan Umum : <?= $data['keadaan'] ?></td>
        <td width="33%">Kesadaran : <?= $data['kesadaran'] ?></td>
        <td width="34%" class="text-center">GCS(E,V,M) : <?= $data['gcs'] ?></td>
    </tr>
    <tr>
        <td colspan="3" class="text-center" style="padding: 5px;">
            <span style="margin-right: 15px;">Tanda Vital :</span>
            <span style="margin-right: 15px;">TD : <?= $data['td'] ?> mmHg</span>
            <span style="margin-right: 15px;">N : <?= $data['nadi'] ?> x/m</span>
            <span style="margin-right: 15px;">R : <?= $data['rr'] ?> x/m</span>
            <span style="margin-right: 15px;">S : <?= $data['suhu'] ?> °C</span>
            <span style="margin-right: 15px;">SPO2 : <?= $data['spo'] ?> %</span>
            <span style="margin-right: 15px;">BB : <?= $data['bb'] ?> Kg</span>
            <span>TB : <?= $data['tb'] ?> cm</span>
        </td>
    </tr>
</table>

<table class="main-table" style="margin-top: -6px; border-top: none;">
    <tr>
        <td width="25%" style="padding: 0; border-top: none;">
            <table width="100%" style="border: none;">
                <tr><td style="border: none; border-bottom: 1px solid #eee; padding: 2px;">Kepala</td><td style="border: none; border-bottom: 1px solid #eee; text-align:right; padding: 2px;"><?= $data['kepala'] ?></td></tr>
                <tr><td style="border: none; border-bottom: 1px solid #eee; padding: 2px;">Mata</td><td style="border: none; border-bottom: 1px solid #eee; text-align:right; padding: 2px;"><?= $data['mata'] ?></td></tr>
                <tr><td style="border: none; border-bottom: 1px solid #eee; padding: 2px;">Gigi & Mulut</td><td style="border: none; border-bottom: 1px solid #eee; text-align:right; padding: 2px;"><?= $data['gigi'] ?></td></tr>
                <tr><td style="border: none; padding: 2px;">Leher</td><td style="border: none; text-align:right; padding: 2px;"><?= $data['leher'] ?></td></tr>
            </table>
        </td>
        <td width="25%" style="padding: 0; border-top: none;">
            <table width="100%" style="border: none;">
                <tr><td style="border: none; border-bottom: 1px solid #eee; padding: 2px;">Thoraks</td><td style="border: none; border-bottom: 1px solid #eee; text-align:right; padding: 2px;"><?= $data['thoraks'] ?></td></tr>
                <tr><td style="border: none; border-bottom: 1px solid #eee; padding: 2px;">Abdomen</td><td style="border: none; border-bottom: 1px solid #eee; text-align:right; padding: 2px;"><?= $data['abdomen'] ?></td></tr>
                <tr><td style="border: none; border-bottom: 1px solid #eee; padding: 2px;">Genital & Anus</td><td style="border: none; border-bottom: 1px solid #eee; text-align:right; padding: 2px;"><?= $data['genital'] ?></td></tr>
                <tr><td style="border: none; padding: 2px;">Ekstremitas</td><td style="border: none; text-align:right; padding: 2px;"><?= $data['ekstremitas'] ?></td></tr>
            </table>
        </td>
        <td width="50%" style="border-top: none; vertical-align: top;">
            <?= nl2br($data['ket_fisik']) ?>
        </td>
    </tr>
</table>

<div class="header-gray" style="margin-top: -4px;">III. STATUS LOKALIS</div>
<table class="main-table" style="margin-top: 0;">
    <tr>
        <td class="text-center" style="padding: 10px;">
             <?php if(!empty($anatomi_src)): ?>
                <img src="<?= $anatomi_src ?>" style="width: 100%; max-height: 150px; object-fit: contain;">
             <?php else: ?>
                <i>(Gambar Anatomi Tidak Tersedia)</i>
             <?php endif; ?>
        </td>
    </tr>
    <tr>
        <td>Keterangan : <?= nl2br($data['ket_lokalis']) ?></td>
    </tr>
</table>

<div class="header-gray" style="margin-top: -4px;">IV. PEMERIKSAAN PENUNJANG</div>
<table class="main-table" style="margin-top: 0;">
    <tr>
        <td width="33%">EKG : <?= $data['ekg'] ?></td>
        <td width="33%">Radiologi : <?= $data['rad'] ?></td>
        <td width="34%">Laboratorium : <?= $data['lab'] ?></td>
    </tr>
</table>

<div class="header-gray" style="margin-top: -4px;">V. DIAGNOSIS</div>
<table class="main-table" style="margin-top: 0;">
    <tr>
        <td style="height: 40px;"><?= nl2br($data['diagnosis']) ?></td>
    </tr>
</table>

<div class="header-gray" style="margin-top: -4px;">VI. TATALAKSANA</div>
<table class="main-table" style="margin-top: 0;">
    <tr>
        <td style="height: 100px;"><?= nl2br($data['tata']) ?></td>
    </tr>
</table>

<table class="main-table" style="margin-top: -1px; border-top: none;">
    <tr>
		<td width="50%" class="text-center" style="border-right: 1px solid #000; vertical-align: bottom;">
            Tanggal dan Jam
		</td>
		<td width="50%" class="text-center" style="border-right: 1px solid #000; vertical-align: bottom;">
            Nama Dokter dan Tanda Tangan
		</td>	
	</tr>
	
	<tr>
        <td width="50%" class="text-center" style="border-right: 1px solid #000; vertical-align: bottom;">            
            <?= isset($data['tanggal']) ? date('d/m/Y H:i:s', strtotime($data['tanggal'])) . ' WIB' : '-' ?>
        </td>
        <td width="50%" class="text-center" style="vertical-align: top;">            
            <?php if(!empty($qr_api)): ?>
                <img src="<?= $qr_api ?>" style="width: 70px; height: 70px; margin-top: 10px;"><br>
            <?php else: ?>
                <br><br>
            <?php endif; ?>
            <span class="fs-10"><?= $data['nm_dokter'] ?></span>
        </td>
    </tr>
</table>