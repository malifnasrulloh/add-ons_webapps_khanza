/*
 Navicat Premium Dump SQL

 Source Server         : localhost
 Source Server Type    : MariaDB
 Source Server Version : 110410 (11.4.10-MariaDB-log)
 Source Host           : localhost
 Source Schema         : sik
 
 Target Server Type    : MariaDB
 Target Server Version : 110410 (11.4.10-MariaDB-log)
 File Encoding         : 65001

 Date: 27/06/2026 20:03:32
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for satu_sehat_ref_form
-- ----------------------------
DROP TABLE IF EXISTS `satu_sehat_ref_form`;
CREATE TABLE `satu_sehat_ref_form` (
  `code` varchar(50) NOT NULL,
  `display` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of satu_sehat_ref_form
-- ----------------------------
BEGIN;
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS001', 'Aerosol Foam');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS002', 'Aerosol Metered Dose');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS003', 'Aerosol Spray');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS004', 'Oral Spray');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS005', 'Buscal Spray');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS006', 'Transdermal Spray');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS007', 'Topical Spray');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS008', 'Serbuk Spray');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS009', 'Eliksir');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS010', 'Emulsi');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS011', 'Enema');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS012', 'Gas');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS013', 'Gel');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS014', 'Gel Mata');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS015', 'Granul Effervescent');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS016', 'Granula');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS017', 'Intra Uterine Device (IUD)');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS018', 'Implant');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS019', 'Kapsul');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS020', 'Kapsul Lunak');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS021', 'Kapsul Pelepasan Lambat');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS022', 'Kaplet');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS023', 'Kaplet Salut Selaput');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS024', 'Kaplet Salut Enterik');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS025', 'Kaplet Salut Gula');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS026', 'Kaplet Pelepasan Lambat');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS027', 'Kaplet Pelepasan Cepat');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS028', 'Kaplet Kunyah');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS029', 'Kaplet Kunyah Salut Selaput');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS030', 'Krim');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS031', 'Krim Lemak');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS032', 'Larutan');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS033', 'Larutan Inhalasi');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS034', 'Larutan Injeksi');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS035', 'Infus');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS036', 'Obat Kumur');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS037', 'Ovula');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS038', 'Pasta');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS039', 'Pil');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS040', 'Patch');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS041', 'Pessary');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS042', 'Salep');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS043', 'Salep Mata');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS044', 'Sampo');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS045', 'Semprot Hidung');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS046', 'Serbuk Aerosol');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS047', 'Serbuk Oral');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS048', 'Serbuk Inhaler');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS049', 'Serbuk Injeksi');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS050', 'Serbuk Injeksi Liofilisasi');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS051', 'Serbuk Infus');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS052', 'Serbuk Obat Luar / Serbuk Tabur');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS053', 'Serbuk Steril');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS054', 'Serbuk Effervescent');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS055', 'Sirup');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS056', 'Sirup Kering');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS057', 'Sirup Kering Pelepasan Lambat');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS058', 'Subdermal Implants');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS059', 'Supositoria');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS060', 'Suspensi');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS061', 'Suspensi Injeksi');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS062', 'Suspensi / Cairan Obat Luar');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS063', 'Cairan Steril');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS064', 'Cairan Mata');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS065', 'Cairan Diagnostik');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS066', 'Tablet');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS067', 'Tablet Effervescent');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS068', 'Tablet Hisap');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS069', 'Tablet Kunyah');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS070', 'Tablet Pelepasan Cepat');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS071', 'Tablet Pelepasan Lambat');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS072', 'Tablet Disintegrasi Oral');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS073', 'Tablet Dispersibel');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS074', 'Tablet Cepat Larut');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS075', 'Tablet Salut Gula');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS076', 'Tablet Salut Enterik');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS077', 'Tablet Salut Selaput');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS078', 'Tablet Sublingual');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS079', 'Tablet Sublingual Pelepasan Lambat');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS080', 'Tablet Vaginal');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS081', 'Tablet Lapis');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS082', 'Tablet Lapis Lepas Lambat');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS083', 'Chewing Gum');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS084', 'Tetes Mata');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS085', 'Tetes Hidung');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS086', 'Tetes Telinga');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS087', 'Tetes Oral (Oral Drops)');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS088', 'Tetes Mata Dan Telinga');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS089', 'Transdermal');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS090', 'Transdermal Urethral');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS091', 'Tulle/Plester Obat');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS092', 'Vaginal Cream');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS093', 'Vaginal Gel');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS094', 'Vaginal Douche');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS095', 'Vaginal Ring');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS096', 'Vaginal Tissue');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('BS097', 'Suspensi Inhalasi');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('MF000001', 'Orodispersible Film');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('MF000001', 'Cairan Obat Dalam');
INSERT INTO `satu_sehat_ref_form` (`code`, `display`) VALUES ('MF000001', 'Cairan Obat Luar');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
