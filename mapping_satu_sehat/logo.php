<?php
/**
 * logo.php — Fetch logo instansi dari database
 */
require_once 'conf.php';

try {
    $stmt = $pdo->query("SELECT logo FROM setting LIMIT 1");
    $row = $stmt->fetch();
    
    if ($row && !empty($row['logo'])) {
        // Blob data
        header("Content-type: image/png"); 
        echo $row['logo'];
    } else {
        // Output transparent pixel 1x1 if failed
        header("Content-type: image/png");
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    }
} catch (Exception $e) {
    header("Content-type: image/png");
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
}
?>
