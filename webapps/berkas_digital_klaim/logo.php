<?php
/*
 * File: /webapps/berkas_digital_perawatan/logo.php
 */
require_once('../conf/conf.php');
$koneksi = bukakoneksi();
$sql = "SELECT logo FROM setting LIMIT 1";
$result = mysqli_query($koneksi, $sql);
if ($row = mysqli_fetch_assoc($result)) {
    header("Content-type: image/jpeg");
    echo $row['logo'];
}
mysqli_close($koneksi);
?>