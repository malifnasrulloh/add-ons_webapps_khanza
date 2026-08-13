<?php
require_once 'conf.php';

try {
    $stmt = $pdo->query("SELECT logo FROM setting LIMIT 1");
    $row = $stmt->fetch();
    
    if ($row && !empty($row['logo'])) {
        header("Content-type: image/png"); 
        echo $row['logo'];
    } else {
        header("Content-type: image/png");
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    }
} catch (Exception $e) {
    header("Content-type: image/png");
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
}
?>
