<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once('../../../conf/conf.php');

header('Content-Type: application/json');

$koneksi = bukakoneksi();

$q = isset($_GET['term']) ? trim($_GET['term']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$q_esc = "%" . mysqli_real_escape_string($koneksi, $q) . "%";

// Count total
$sql_count = "SELECT COUNT(*) as total FROM penyakit WHERE kd_penyakit LIKE '$q_esc' OR nm_penyakit LIKE '$q_esc'";
$res_count = mysqli_query($koneksi, $sql_count);
$total = 0;
if ($res_count && $row = mysqli_fetch_assoc($res_count)) {
    $total = (int)$row['total'];
}

$more = ($offset + $limit) < $total;

$sql = "SELECT kd_penyakit as id, CONCAT(kd_penyakit, ' - ', nm_penyakit) as text, nm_penyakit as display 
        FROM penyakit 
        WHERE kd_penyakit LIKE '$q_esc' OR nm_penyakit LIKE '$q_esc'
        ORDER BY 
            CASE WHEN kd_penyakit = '" . mysqli_real_escape_string($koneksi, $q) . "' THEN 0 ELSE 1 END,
            nm_penyakit ASC
        LIMIT $limit OFFSET $offset";

$res = mysqli_query($koneksi, $sql);
$results = [];

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $results[] = $row;
    }
}

mysqli_close($koneksi);

echo json_encode([
    'results' => $results,
    'pagination' => ['more' => $more]
]);
