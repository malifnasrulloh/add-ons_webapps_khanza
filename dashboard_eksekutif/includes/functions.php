<?php
// File: includes/functions.php (SECURITY HARDENED & POLYMORPHIC)

/**
 * Format currency to Indonesian Rupiah
 */
function formatRupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}

/**
 * Retrieve shift times from database (Polymorphic: supports PDO & MySQLi)
 */
function getShiftTimes($conn = null) {
    if ($conn === null) {
        global $koneksi_pdo;
        $conn = $koneksi_pdo;
    }
    
    $shifts = [];
    $sql = "SELECT closing_kasir.shift, closing_kasir.jam_masuk, closing_kasir.jam_pulang FROM closing_kasir";
    
    if ($conn instanceof PDO) {
        $stmt = $conn->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $shifts[$row['shift']] = [
                'masuk' => $row['jam_masuk'],
                'pulang' => $row['jam_pulang']
            ];
        }
    } else {
        // Fallback for legacy MySQLi
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $shifts[$row['shift']] = [
                    'masuk' => $row['jam_masuk'],
                    'pulang' => $row['jam_pulang']
                ];
            }
        }
    }
    return $shifts;
}

/**
 * Compute the DateTime range for a given date and shift, handling night shifts crossing midnight.
 */
function getShiftDateTimeRange($tanggal_str, $shift, $shift_times) {
    if (!isset($shift_times[$shift])) {
        return null; 
    }
    $jam_masuk = $shift_times[$shift]['masuk'];
    $jam_pulang = $shift_times[$shift]['pulang'];
    
    $dt_awal_str = $tanggal_str . ' ' . $jam_masuk;
    $dt_akhir_str = $tanggal_str . ' ' . $jam_pulang;

    // Handle cross-midnight night shifts
    if (strtotime($jam_masuk) > strtotime($jam_pulang)) {
        $tanggal_obj = new DateTime($tanggal_str);
        $tanggal_obj->modify('+1 day');
        $tanggal_akhir_str = $tanggal_obj->format('Y-m-d');
        $dt_akhir_str = $tanggal_akhir_str . ' ' . $jam_pulang;
    }

    return [
        'start' => $dt_awal_str,
        'end' => $dt_akhir_str
    ];
}

/**
 * Retrieve a numeric column value (Polymorphic: supports PDO & MySQLi)
 */
function cariIsiAngka($conn, $sql, $parameter) {
    if ($conn === null) {
        global $koneksi_pdo;
        $conn = $koneksi_pdo;
    }
    
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare($sql);
        $stmt->execute([$parameter]);
        $val = $stmt->fetchColumn();
        return $val !== false ? floatval($val) : 0.0;
    } else {
        // Fallback for legacy MySQLi
        $value = 0;
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $parameter);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_array()) {
                $value = $row[0];
            }
            $stmt->close();
        }
        return floatval($value);
    }
}

/**
 * Retrieve a string column value (Polymorphic: supports PDO & MySQLi)
 */
function cariIsi($conn, $sql, $parameter) {
    if ($conn === null) {
        global $koneksi_pdo;
        $conn = $koneksi_pdo;
    }
    
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare($sql);
        $stmt->execute([$parameter]);
        $val = $stmt->fetchColumn();
        return $val !== false ? strval($val) : "";
    } else {
        // Fallback for legacy MySQLi
        $value = "";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $parameter);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_array()) {
                $value = $row[0];
            }
            $stmt->close();
        }
        return $value;
    }
}


?>