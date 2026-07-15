SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for satu_sehat_ref_form
-- ----------------------------
DROP TABLE IF EXISTS `satu_sehat_ref_form`;
CREATE TABLE `satu_sehat_ref_form`  (
  `code` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `display` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of satu_sehat_ref_form
-- ----------------------------
INSERT INTO `satu_sehat_ref_form` VALUES ('BS001', 'Aerosol Foam');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS002', 'Aerosol Metered Dose');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS003', 'Aerosol Spray');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS004', 'Oral Spray');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS005', 'Buscal Spray');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS006', 'Transdermal Spray');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS007', 'Topical Spray');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS008', 'Serbuk Spray');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS009', 'Eliksir');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS010', 'Emulsi');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS011', 'Enema');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS012', 'Gas');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS013', 'Gel');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS014', 'Gel Mata');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS015', 'Granul E?ervescent');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS016', 'Granula');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS017', 'Intra Uterine Device (IUD)');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS018', 'Implant');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS019', 'Kapsul');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS020', 'Kapsul Lunak');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS021', 'Kapsul Pelepasan Lambat');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS022', 'Kaplet');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS023', 'Kaplet Salut Selaput');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS024', 'Kaplet Salut Enterik');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS025', 'Kaplet Salut Gula');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS026', 'Kaplet Pelepasan Lambat');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS027', 'Kaplet Pelepasan Cepat');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS028', 'Kaplet Kunyah');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS029', '\"Kaplet       Kunyah        Salut');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS030', 'Krim');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS031', 'Krim Lemak');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS032', 'Larutan');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS033', 'Larutan Inhalasi');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS034', 'Larutan Injeksi');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS035', 'Infus');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS036', 'Obat Kumur');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS037', 'Ovula');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS038', 'Pasta');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS039', 'Pil');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS040', 'Patch');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS041', 'Pessary');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS042', 'Salep');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS043', 'Salep Mata');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS044', 'Sampo');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS045', 'Semprot Hidung');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS046', 'Serbuk Aerosol');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS047', 'Serbuk Oral');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS048', 'Serbuk Inhaler');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS049', 'Serbuk Injeksi');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS050', 'Serbuk Injeksi Lio?lisasi');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS051', 'Serbuk Infus');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS052', '\"Serbuk  Obat Luar / Serbuk');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS053', 'Serbuk Steril');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS054', 'Serbuk E?ervescent');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS055', 'Sirup');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS056', 'Sirup Kering');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS057', '\"Sirup      Kering    Pelepasan');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS058', 'Subdermal Implants');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS059', 'Supositoria');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS060', 'Suspensi');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS061', 'Suspensi Injeksi');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS062', 'Suspensi / Cairan Obat Luar');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS063', 'Cairan Steril');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS064', 'Cairan Mata');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS065', 'Cairan Diagnostik');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS066', 'Tablet');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS067', 'Tablet E?ervescent');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS068', 'Tablet Hisap');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS069', 'Tablet Kunyah');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS070', 'Tablet Pelepasan Cepat');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS071', 'Tablet Pelepasan Lambat');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS072', 'Tablet Disintegrasi Oral');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS073', 'Tablet Dispersibel');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS074', 'Tablet Cepat Larut');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS075', 'Tablet Salut Gula');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS076', 'Tablet Salut Enterik');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS077', 'Tablet Salut Selaput');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS078', 'Tablet Sublingual');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS079', '\"Tablet Sublingual Pelepasan');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS080', 'Tablet Vaginal');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS081', 'Tablet Lapis');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS082', 'Tablet Lapis Lepas Lambat');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS083', 'Chewing Gum');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS084', 'Tetes Mata');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS085', 'Tetes Hidung');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS086', 'Tetes Telinga');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS087', 'Tetes Oral (Oral Drops)');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS088', 'Tetes Mata Dan Telinga');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS089', 'Transdermal');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS090', 'Transdermal Urethral');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS091', 'Tulle/Plester Obat');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS092', 'Vaginal Cream');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS093', 'Vaginal Gel');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS094', 'Vaginal Douche');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS095', 'Vaginal Ring');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS096', 'Vaginal Tissue');
INSERT INTO `satu_sehat_ref_form` VALUES ('BS097', 'Suspensi Inhalasi');
INSERT INTO `satu_sehat_ref_form` VALUES ('Lambat\"', NULL);
INSERT INTO `satu_sehat_ref_form` VALUES ('Selaput\"', NULL);
INSERT INTO `satu_sehat_ref_form` VALUES ('Tabur\"', NULL);

SET FOREIGN_KEY_CHECKS = 1;
