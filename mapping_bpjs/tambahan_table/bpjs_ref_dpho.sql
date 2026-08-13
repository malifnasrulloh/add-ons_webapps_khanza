-- Tabel cache referensi DPHO BPJS Apotek (K-Farmasi)
-- Disinkronkan dari API BPJS: GET /referensi/dpho
-- Dipakai untuk detail read-only (prb, kronis, kemo, harga, sedia, stok) di modal mapping
CREATE TABLE IF NOT EXISTS bpjs_ref_dpho (
    kodeobat   VARCHAR(50)   NOT NULL,
    namaobat   VARCHAR(255)  NOT NULL,
    prb        TINYINT(1)    NOT NULL DEFAULT 0,
    kronis     TINYINT(1)    NOT NULL DEFAULT 0,
    kemo       TINYINT(1)    NOT NULL DEFAULT 0,
    harga      DECIMAL(15,2) NOT NULL DEFAULT 0,
    restriksi  TEXT          NULL,
    generik    VARCHAR(100)  NULL,
    aktif      VARCHAR(20)   NULL,
    sedia      VARCHAR(100)  NULL,
    stok       VARCHAR(100)  NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (kodeobat),
    KEY idx_nama (namaobat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
