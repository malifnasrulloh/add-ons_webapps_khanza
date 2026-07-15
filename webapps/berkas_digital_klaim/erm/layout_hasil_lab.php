<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Tahoma, sans-serif; font-size: 11px; color: #000; line-height: 1.2; }
        
        /* HEADER GRID */
        .grid-header { width: 100%; border-collapse: collapse; margin-bottom: 5px; font-size: 10px; }
        .grid-header td { vertical-align: top; padding: 1px 3px; }
        .label { font-weight: bold; width: 110px; }
        .sep { width: 10px; text-align: center; }
        
        /* KOP SURAT */
        .kop-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 10px; }
        
        /* TABLE HASIL LAB */
        .table-lab { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 11px; }
        .table-lab th { 
            border-top: 1px solid #000; 
            border-bottom: 1px solid #000; 
            padding: 4px; 
            text-align: left;
            font-weight: bold;
        }
        .table-lab td { 
            padding: 3px 4px; 
            border-bottom: 1px dotted #ccc; 
        }
        
        /* WARNA HASIL */
        .text-blue { color: #0000FF; font-weight: bold; }
        .text-red { color: #FF0000; font-weight: bold; }
        
        /* GROUP HEADER */
        .group-header {
            font-weight: bold;
            font-style: italic;
            background-color: #f0f0f0;
            padding: 4px;
        }
        
        /* FOOTER KESAN */
        .box-kesan { border: 1px solid #000; padding: 5px; margin-top: 10px; font-size: 11px; }
    </style>
</head>
<body>

    <table class="kop-table">
        <tr>
            <td width="70" align="center" style="padding-right: 20px;"><img src="<?= $logo_src ?>" width="70"></td>
            <td>
                <b style="font-size:20px"><?= $setting['nama_instansi'] ?></b><br>
                <?= $setting['alamat_instansi'] ?>, <?= $setting['kabupaten'] ?>,  <?= $setting['propinsi'] ?><br>
                Telp: <?= $setting['kontak'] ?> <br>
				Email: <?= $setting['email'] ?>
            </td>
        </tr>
    </table>

    <div style="text-align:center; font-weight:bold; font-size:14px; margin-bottom:10px;">
        HASIL PEMERIKSAAN LABORATORIUM
    </div>

    <table class="grid-header">
        <tr>
            <td width="50%">
                <table width="100%">
                    <tr><td class="label">No. Periksa/Rawat</td><td class="sep">:</td><td><?= $no_rawat ?></td></tr>
                    <tr><td class="label">No. RM</td><td class="sep">:</td><td><?= $data_pasien['no_rkm_medis'] ?></td></tr>
                    <tr><td class="label">Nama Pasien</td><td class="sep">:</td><td><b><?= $data_pasien['nm_pasien'] ?></b></td></tr>
                    <tr><td class="label">JK / Umur</td><td class="sep">:</td><td><?= $data_pasien['jk']=='L'?'Laki-Laki':'Perempuan' ?> / <?= $data_pasien['umur'] ?></td></tr>
                    <tr><td class="label">Alamat</td><td class="sep">:</td><td><?= substr($data_pasien['alamat_lengkap'], 0, 50) ?>...</td></tr>
                </table>
            </td>
            <td width="50%">
                <table width="100%">
                    <tr><td class="label">Penanggung Jawab</td><td class="sep">:</td><td><?= $data_pasien['dokter_penjab'] ?></td></tr>
                    <tr><td class="label">Dokter Pengirim</td><td class="sep">:</td><td><?= $data_pasien['dokter_pengirim'] ?></td></tr>
                    <tr><td class="label">Tgl/Jam Permintaan</td><td class="sep">:</td><td><?= date('d-m-Y', strtotime($tgl_order)) ?> <?= $jam_order ?></td></tr>
                    <tr><td class="label">Tgl/Jam Keluar</td><td class="sep">:</td><td><?= date('d-m-Y', strtotime($tgl_periksa)) ?> <?= $jam_periksa ?></td></tr>
                    <tr><td class="label">Ruang / Poli</td><td class="sep">:</td><td><?= $nama_lokasi ?></td></tr>
                    <tr><td class="label">No. Permintaan</td><td class="sep">:</td><td><?= $no_order ?></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="table-lab">
        <thead>
            <tr>
                <th width="35%">Pemeriksaan</th>
                <th width="15%">Hasil</th>
                <th width="10%">Satuan</th>
                <th width="20%">Nilai Rujukan</th>
                <th width="20%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $current_cat = "";
            foreach($data_lab as $row):
                // Grouping Kategori
                if($row['kategori'] != $current_cat) {
                    echo "<tr><td colspan='5' class='group-header'>".htmlspecialchars(strtoupper($row['kategori']), ENT_QUOTES, 'UTF-8')."</td></tr>";
                    $current_cat = $row['kategori'];
                }

                // Logic Warna (L=Biru, H=Merah)
                $class_hasil = "";
                $ket = trim($row['keterangan']);

                if($ket == 'L' || strtolower($ket) == 'low') {
                    $class_hasil = "text-blue";
                } elseif($ket == 'H' || $ket == '*' || strtolower($ket) == 'high') {
                    $class_hasil = "text-red";
                }
            ?>
            <tr>
                <td style="padding-left: 15px;"><?= htmlspecialchars($row['Pemeriksaan'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-center <?= $class_hasil ?>"><?= htmlspecialchars($row['nilai'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-center"><?= htmlspecialchars($row['satuan'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-center"><?= htmlspecialchars($row['nilai_rujukan'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-center <?= $class_hasil ?>"><?= htmlspecialchars($row['keterangan'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if(!empty($d_saran['kesan']) || !empty($d_saran['saran'])): ?>
    <div class="box-kesan">
        <b>Kesan:</b><br>
        <?= nl2br($d_saran['kesan']) ?><br><br>
        <b>Saran:</b><br>
        <?= nl2br($d_saran['saran']) ?>
    </div>
    <?php endif; ?>

    <div style="font-size:10px; margin-top:5px; border-top:1px solid #000; padding-top:2px;">
        <i>Catatan: Jika ada keragu-raguan pemeriksaan, diharapkan menghubungi laboratorium.</i>
    </div>

    <table style="width:100%; margin-top:20px;">
        <tr>
            <td width="50%" align="center">
                Petugas Laboratorium<br>
                <?php if($qr_petugas): ?>
                    <img src="<?= $qr_petugas ?>" width="70" style="margin:5px;">
                <?php else: ?>
                    <br><br><br>
                <?php endif; ?>
                <br>
                ( <?= $data_pasien['nama_petugas'] ?> )
            </td>
            <td width="50%" align="center">
                Penanggung Jawab<br>
                <?php if($qr_dokter): ?>
                    <img src="<?= $qr_dokter ?>" width="70" style="margin:5px;">
                <?php else: ?>
                    <br><br><br>
                <?php endif; ?>
                <br>
                ( <?= $data_pasien['dokter_penjab'] ?> )
            </td>
        </tr>
    </table>

</body>
</html>