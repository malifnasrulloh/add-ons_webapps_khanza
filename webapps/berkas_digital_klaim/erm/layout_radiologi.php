<style>
    /* CSS Dasar Layout */
    .page-break { page-break-after: always; margin-top: 30px; border-bottom: 2px dashed #ccc; padding-bottom: 30px; }
    .page-break:last-child { page-break-after: avoid; border: none; }
    
    .kop-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 10px; }
    .judul { text-align: center; font-weight: bold; font-size: 14px; text-decoration: underline; margin-bottom: 15px; }
    
    .grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .grid td { vertical-align: top; padding: 2px; }
    .label { font-weight: bold; width: 120px; }
    .sep { width: 10px; text-align: center; }
    
    .box-hasil { border: 1px solid #000; padding: 10px; min-height: 100px; margin-bottom: 10px; font-family: 'Courier New', monospace; font-size: 12px; }
    .section-header { font-weight: bold; background-color: #f0f0f0; border: 1px solid #000; padding: 5px; margin-top: 10px; }
    
    /* TABLE GRID KHUSUS PDF (Agar tidak renggang/blank) */
    .img-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .img-table td { text-align: center; padding: 5px; vertical-align: middle; border: 1px solid #eee; }
    .img-pdf { max-width: 300px; max-height: 300px; width: auto; height: auto; } /* Batasi ukuran agar muat 2 per baris */
</style>

<?php 
foreach($data_laporan as $idx => $row): 
    $pasien = $row['pasien'];
    $alamat_full = $pasien['alamat'] . ", " . $pasien['nm_kel'] . ", " . $pasien['nm_kec'] . ", " . $pasien['nm_kab'];
    
    // Key Unik untuk Checkbox (Tgl + Jam)
    // str_replace agar valid html ID
    $unique_key = str_replace(['-', ':', ' '], '', $row['tgl'] . $row['jam']);
?>

<div class="exam-container <?= ($idx < count($data_laporan)-1) ? 'page-break' : '' ?>">
    
    <table class="kop-table">
        <tr>
            <td width="60" align="center"><img src="<?= $logo_src ?>" width="50"></td>
            <td>
                <b style="font-size:14px"><?= $setting['nama_instansi'] ?></b><br>
                <?= $setting['alamat_instansi'] ?>, <?= $setting['kabupaten'] ?><br>
                Telp: <?= $setting['kontak'] ?>
            </td>
        </tr>
    </table>

    <div class="judul">HASIL PEMERIKSAAN RADIOLOGI</div>

    <table class="grid">
        <tr>
            <td width="55%">
                <table width="100%">
                    <tr><td class="label">No. Rawat</td><td class="sep">:</td><td><?= $no_rawat ?></td></tr>
                    <tr><td class="label">No. RM</td><td class="sep">:</td><td><?= $pasien['no_rkm_medis'] ?></td></tr>
                    <tr><td class="label">Nama Pasien</td><td class="sep">:</td><td><b><?= $pasien['nm_pasien'] ?></b></td></tr>
                    <tr><td class="label">JK / Umur</td><td class="sep">:</td><td><?= $pasien['jk']=='L'?'Laki-Laki':'Perempuan' ?> / <?= $pasien['umur'] ?></td></tr>
                    <tr><td class="label">Alamat</td><td class="sep">:</td><td><?= $alamat_full ?></td></tr>
                </table>
            </td>
            <td width="45%">
                <table width="100%">
                    <tr><td class="label">Penanggung Jawab</td><td class="sep">:</td><td><?= $pasien['dokter_penjab'] ?></td></tr>
                    <tr><td class="label">Tgl Pemeriksaan</td><td class="sep">:</td><td><?= date('d-m-Y', strtotime($row['tgl'])) ?> <?= $row['jam'] ?></td></tr>
                    <tr><td class="label">Pemeriksaan</td><td class="sep">:</td><td><b><?= $row['periksa'] ?></b></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-header">HASIL EXPERTISE</div>
    <div class="box-hasil">
        <?= nl2br($row['hasil']) ?>
    </div>

    <?php if(!empty($row['images'])): ?>
        <div class="section-header">CITRA RADIOLOGI</div>
        
        <?php if(!$is_pdf): ?>
            <div style="padding: 10px; background: #f9f9f9; border: 1px dashed #999;">
                <?php foreach($row['images'] as $img_path): ?>
                    <div class="img-wrapper">
                        <img src="../../radiologi/<?= $img_path ?>" class="img-preview">
                        <label class="chk-overlay">
                            <input type="checkbox" name="selected_imgs[<?= $unique_key ?>][]" value="<?= $img_path ?>" checked> Sertakan
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <table class="img-table">
                <?php 
                // Grouping gambar per 2 item untuk baris tabel
                $chunks = array_chunk($row['images'], 2);
                foreach($chunks as $chunk): 
                ?>
                <tr>
                    <?php foreach($chunk as $img_base64): ?>
                        <td width="50%" align="center">
                            <img src="<?= $img_base64 ?>" class="img-pdf">
                        </td>
                    <?php endforeach; ?>
                    
                    <?php if(count($chunk) == 1): ?>
                        <td width="50%"></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

    <?php endif; ?>

    <table style="width:100%; margin-top:20px; page-break-inside: avoid;">
        <tr>
            <td width="50%" align="center">
                Petugas Radiologi<br>
                <?php if($row['qr_petugas']): ?>
                    <img src="<?= $row['qr_petugas'] ?>" width="70" style="margin:5px;">
                <?php else: ?>
                    <br><br><br>
                <?php endif; ?>
                <br>
                ( <?= $pasien['nama_petugas'] ?> )
            </td>
            <td width="50%" align="center">
                Dokter Radiolog<br>
                <?php if($row['qr_dokter']): ?>
                    <img src="<?= $row['qr_dokter'] ?>" width="70" style="margin:5px;">
                <?php else: ?>
                    <br><br><br>
                <?php endif; ?>
                <br>
                ( <?= $pasien['dokter_penjab'] ?> )
            </td>
        </tr>
    </table>

</div>
<?php endforeach; ?>

</body>
</html>