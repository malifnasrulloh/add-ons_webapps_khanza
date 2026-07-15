<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Tahoma, Verdana, sans-serif; font-size: 11px; color: #000; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 2px; }
        .border-top { border-top: 1px solid #000; }
        .border-bottom { border-bottom: 1px solid #000; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
    </style>
</head>
<body>

    <table>
        <tr>
            <td width="70" align="center" valign="top"><img src="<?= $logo_src ?>" width="70"></td>
            <td align="center" valign="center">
                <b style="font-size:20px"><?= $setting['nama_instansi'] ?></b><br>
                <?= $setting['alamat_instansi'] ?>, <?= $setting['kabupaten'] ?>, <?= $setting['propinsi'] ?><br>
                Telp: <?= $setting['kontak'] ?>, Email: <?= $setting['email'] ?>
            </td>
        </tr>
    </table>
    <hr>
    
    <div style="text-align:center; font-weight:bold; font-size:14px; margin:10px 0; text-decoration:underline;">BILLING</div>

    <!--
	<table style="margin-bottom: 10px;">
        <tr>
            <td width="15%" valign="top">No. Rawat</td><td width="35%" valign="top">: <?= $no_rawat ?></td>
            <td width="15%" valign="top">Tgl Registrasi</td><td width="35%" valign="top">: <?= $data_pasien['tgl_registrasi'] ?> <?= $data_pasien['jam_reg'] ?></td>
        </tr>
        <tr>
            <td valign="top">No. R.M.</td><td valign="top">: <?= $data_pasien['no_rkm_medis'] ?></td>
            <td valign="top">Penanggung Jawab</td><td valign="top">: <?= $data_pasien['png_jawab'] ?></td>
        </tr>
        <tr>
            <td valign="top">Nama Pasien</td><td valign="top">: <b><?= $data_pasien['nm_pasien'] ?></b></td>
            <td valign="top">Dokter</td><td valign="top">: <?= $data_pasien['nm_dokter'] ?></td>
        </tr>
        <tr>
            <td valign="top">Alamat</td><td valign="top">: <?= $data_pasien['alamat'] ?></td>
            <td valign="top">Ruang/Poli</td><td valign="top">: <?= $nama_lokasi ?></td>
        </tr>
    </table>
	-->

    <table cellspacing="0" cellpadding="0">
        <!--<tr class="text-center text-bold">
            <td class="border-top border-bottom" width="5%" style="padding: 5px 0;">NO</td>
            <td class="border-top border-bottom" width="45%" align="left" style="padding: 5px 0;">NAMA PEMERIKSAAN / OBAT</td>
            <td class="border-top border-bottom" width="13%" align="right" style="padding: 5px 0;">BIAYA</td>
            <td class="border-top border-bottom" width="5%" style="padding: 5px 0;">JML</td>
            <td class="border-top border-bottom" width="12%" align="right" style="padding: 5px 0;">TAMBAHAN</td>
            <td class="border-top border-bottom" width="20%" align="right" style="padding: 5px 0;">TOTAL BIAYA</td>
        </tr> -->

        <?php foreach($data_billing as $row): 
            $is_header = ($row['biaya'] == 0 && $row['jumlah'] == 0 && $row['totalbiaya'] == 0);
            $nama_item = $row['nm_perawatan'];
            if($is_header) $nama_item = "<b>$nama_item</b>";
        ?>
        <tr>
            <td align="left" valign="top"><?= $row['no'] ?></td>
            
            <td align="left" valign="top">
                <span style="white-space: pre-wrap;"><?= $nama_item ?></span>
            </td>
            <td align="left" valign="top"><?= $row['pemisah'] ?></td>
            <td align="right" valign="top"><?= formatUang($row['biaya']) ?></td>
            <td align="center" valign="top"><?= ($row['jumlah'] > 0 ? $row['jumlah'] : '') ?></td>
            <td align="right" valign="top"><?= formatUang($row['tambahan']) ?></td>
            <td align="right" valign="top"><?= formatUang($row['totalbiaya']) ?></td>
        </tr>
        <?php endforeach; ?>

        <tr><td colspan="7"></td></tr>
        <tr>
            <td align="left" class="text-bold" style="padding-top: 5px;">Total Tagihan </td> <td align="left" valign="top" style="padding-top: 5px;">:</td>
            <td colspan="5" align="right" class="text-bold" style="padding-top: 5px; padding-bottom: 5px;"> <?= formatUang($total_tagihan) ?></td>
        </tr>
        <tr><td colspan="7" style="padding-top: 5px;"></td></tr>
    </table>

    <br>

    <table style="margin-top: 20px;">
        <tr>
			<td width="50%" align="center" valign="top"> &nbsp;
                <!--<div style="border: 1px solid #000; padding: 10px; font-size: 10px; text-align: left; margin: 10px;">
                    <b>Catatan:</b><br>
                    1. Kwitansi ini sah jika dibubuhi tanda tangan/stempel.<br>
                    2. Terima kasih atas kepercayaan Anda.
                </div> -->				
            </td>
            <td width="50%" align="center" valign="top">
                <?= $setting['kabupaten'] ?>, <?= tgl_indo($tgl_bayar) ?><br>
                Petugas Kasir
                <br><br>
                <?php if($qr_api): ?>
                    <img src="<?= $qr_api ?>" width="75">
                <?php else: ?>
                    <br><br><br>
                <?php endif; ?>
                <br>
                <b>( Petugas Kasir )</b>
            </td>            
        </tr>
    </table>

</body>
</html>