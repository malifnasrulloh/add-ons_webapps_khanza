<table class="double-border-bottom" style="margin-bottom: 5px;">
    <tr>
        <td width="70" align="center" style="padding-right: 20px;">
            <img src="<?= $logo_src ?>" width="70" height="70">
        </td>
        <td>
            <b style="font-size:20px"><?= $setting['nama_instansi'] ?></b><br>
            <span style="font-size:11px"><?= $setting['alamat_instansi'] ?>, <?= $setting['kabupaten'] ?>, <?= $setting['propinsi'] ?></span><br>
            <span style="font-size:11px">Telp: <?= $setting['kontak'] ?> | E-mail: <?= $setting['email'] ?></span>
        </td>
    </tr>
</table>

<div class="header-title">LAPORAN OPERASI</div>

<table style="border-top: 1px solid #000; border-bottom: 1px solid #000;">
    <tr>
        <td width="15%">Nama Pasien</td>
        <td width="35%">: <span class="text-italic"><?= $d_pasien['nm_pasien'] ?></span></td>
        <td width="15%">No. Rekam Medis</td>
        <td width="35%">: <span class="text-italic"><?= $d_pasien['no_rkm_medis'] ?></span></td>
    </tr>
    <tr>
        <td>Umur</td>
        <td>: <span class="text-italic"><?= $d_pasien['umur'] ?></span></td>
        <td>Ruang</td>
        <td>: <span class="text-italic"><?= $ruang ?></span></td>
    </tr>
    <tr>
        <td>Tgl Lahir</td>
        <td>: <span class="text-italic"><?= formatTgl($d_pasien['tgl_lahir']) ?></span></td>
        <td>Jenis Kelamin</td>
        <td>: <span class="text-italic"><?= $d_pasien['jk']=='L'?'Laki-Laki':'Perempuan' ?></span></td>
    </tr>
</table>

<div class="gray-bar">PRE SURGICAL ASSESMENT</div>
<table style="border-bottom: 1px solid #000;">
    <tr>
        <td width="15%">Tanggal</td>
        <td width="25%">: <?= formatTgl($tgl_operasi) ?></td>
        <td width="10%">Waktu :</td>
        <td width="15%"><?= $jam_mulai ?></td>
        <td width="10%">Alergi</td>
        <td width="25%">: <?= $d_laporan['alergi'] ?? 'tidak ada' ?></td>
    </tr>
    <tr>
        <td>Dokter Bedah</td>
        <td colspan="5">: <span class="text-italic"><?= $dokter_bedah ?></span></td>
    </tr>
</table>

<div class="gray-bar" style="border-top:none; margin-top:0;">POST SURGICAL REPORT</div>

<table width="100%" cellspacing="0" cellpadding="0" style="border-bottom: 1px solid #000;">
    <tr>
        <td width="70%" style="padding: 0; vertical-align: top;">
            
            <table width="100%" style="margin-bottom: 5px;">
                <tr>
                    <td width="50%" style="padding-top: 5px;">
                        <div class="field-label">Tanggal & Waktu</div>
                        <div class="field-value" style="margin-left:0; padding-left:15px;">: <?= formatTgl($tgl_operasi) ?> <?= $jam_selesai ?></div>
                        
                        <div class="field-label">Dokter Bedah :</div>
                        <div class="field-value"><?= $dokter_bedah ?></div>

                        <div class="field-label">Dokter Bedah 2 :</div>
                        <div class="field-value"><?= ($dokter_bedah2!='-') ? $dokter_bedah2 : '-' ?></div>

                        <div class="field-label">Perawat Resusitas :</div>
                        <div class="field-value"><?= ($perawat_resusitas!='-') ? $perawat_resusitas : '-' ?></div>

                        <div class="field-label">Instrumen :</div>
                        <div class="field-value"><?= $instrumen ?></div>

                        <div class="field-label">Dokter Anak :</div>
                        <div class="field-value"><?= ($dokter_anak!='-') ? $dokter_anak : '-' ?></div>

                        <div class="field-label">Dokter Umum :</div>
                        <div class="field-value"><?= ($dokter_umum!='-') ? $dokter_umum : '-' ?></div>
                    </td>
                    
                    <td width="50%" style="padding-top: 20px;"> 
                        <div class="field-label">Asisten Bedah :</div>
                        <div class="field-value"><?= $asisten_bedah ?></div>

                        <div class="field-label">Asisten Bedah 2 :</div>
                        <div class="field-value"><?= ($asisten_bedah2!='-') ? $asisten_bedah2 : '-' ?></div>

                        <div class="field-label">Dokter Anastesi :</div>
                        <div class="field-value"><?= $dokter_anestesi ?></div>

                        <div class="field-label">Asisten Anastesi :</div>
                        <div class="field-value"><?= $asisten_anestesi ?></div>

                        <div class="field-label">Bidan :</div>
                        <div class="field-value"><?= ($bidan!='-') ? $bidan : '-' ?></div>

                        <div class="field-label">Onloop :</div>
                        <div class="field-value"><?= $omloop ?></div>
                    </td>
                </tr>
            </table>

            <div class="gray-sub-bar">Diagnosa Pre-Op / Pre Operation Diagnosis</div>
            <div style="padding: 2px 5px 5px 5px; font-style: italic; min-height: 12px;">
                <?= $d_laporan['diagnosa_preop'] ?? '-' ?>
            </div>

            <div class="gray-sub-bar">Jaringan Yang di-Eksisi/-Insisi</div>
            <div style="padding: 2px 5px 5px 5px; font-style: italic; min-height: 12px;">
                <?= $d_laporan['jaringan_dieksekusi'] ?? '-' ?>
            </div>

            <div class="gray-sub-bar">Diagnosa Post-Op / Post Operation Diagnosis</div>
            <div style="padding: 2px 5px 5px 5px; font-style: italic; min-height: 12px;">
                <?= $d_laporan['diagnosa_postop'] ?? '-' ?>
            </div>

        </td>

        <td width="30%" class="border-left text-center" style="vertical-align: top; padding-top: 20px;">
            
            <div style="margin-bottom: 20px;">
                Tipe/Jenis Anastesi<br>
                <span class="text-italic" style="font-weight:bold; font-size:11px;">
                    <?= $d_op['jenis_anasthesi'] ?>
                </span>
            </div>
            
            <div style="margin-bottom: 20px;">
                Dikirim ke Pemeriksaan PA<br>
                <span class="text-italic" style="font-weight:bold;">
                    <?= $d_laporan['permintaan_pa'] ?? 'Tidak' ?>
                </span>
            </div>

            <div style="margin-bottom: 20px;">
                Tipe/Kategori Operasi<br>
                <span class="text-italic">
                    <?= $d_op['kategori'] ?>
                </span>
            </div>

            <div style="margin-top: 10px;">
                Selesai Operasi<br>
                <?= formatTgl($tgl_operasi) ?> <?= $jam_selesai ?>
            </div>
        </td>
    </tr>
</table>

<div class="gray-bar" style="margin-top: 0; border-top: none;">REPORT ( PROCEDURES, SPECIFIC FINDINGS AND COMPLICATIONS )</div>
<div class="report-content">
    <?= nl2br($d_laporan['laporan_operasi'] ?? '-') ?>
</div>

<table style="margin-top: 10px;">
    <tr>
        <td width="65%"></td>
        <td width="35%" align="center">
            <?= formatTgl($tgl_operasi) ?>
            <br>Dokter Bedah
            <br>
            <?php if(!empty($qr_api)): ?>
                <img src="<?= $qr_api ?>" width="70" height="70" style="margin: 5px 0;">
            <?php else: ?>
                <br><br><br>
            <?php endif; ?>
            <br>
            <span style="text-decoration: underline;"><?= $dokter_bedah ?></span>
        </td>
    </tr>
</table>