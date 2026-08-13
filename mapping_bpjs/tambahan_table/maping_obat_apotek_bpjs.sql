-- Tabel Mapping Obat Apotek BPJS
CREATE TABLE IF NOT EXISTS `maping_obat_apotek_bpjs` (
  `kode_brng` varchar(15) NOT NULL,
  `kode_brng_apotek_bpjs` varchar(15) NOT NULL,
  `nama_brng_apotek_bpjs` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`kode_brng`,`kode_brng_apotek_bpjs`),
  KEY `kode_brng` (`kode_brng`),
  KEY `kode_brng_apotek_bpjs` (`kode_brng_apotek_bpjs`),
  CONSTRAINT `maping_obat_apotek_bpjs_ibfk_1` FOREIGN KEY (`kode_brng`) REFERENCES `databarang` (`kode_brng`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
