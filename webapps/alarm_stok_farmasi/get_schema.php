<?php
require_once 'koneksi.php';

$tables = ['databarang', 'gudangbarang', 'kodesatuan', 'riwayat_barang_medis'];

foreach ($tables as $table) {
    echo "TABLE: $table\n";
    try {
        $result = $pdo->query("DESCRIBE $table")->fetchAll();
        foreach ($result as $row) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
?>
