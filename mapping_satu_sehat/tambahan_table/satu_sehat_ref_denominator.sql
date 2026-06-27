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

 Date: 27/06/2026 20:03:04
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for satu_sehat_ref_denominator
-- ----------------------------
DROP TABLE IF EXISTS `satu_sehat_ref_denominator`;
CREATE TABLE `satu_sehat_ref_denominator` (
  `code` varchar(50) NOT NULL,
  `display` varchar(120) DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of satu_sehat_ref_denominator
-- ----------------------------
BEGIN;
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('AER', 'Aerosol');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('APPFUL', 'Applicatorful');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('BAINHL', 'Breath Activated Inhaler');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('BAINHLPWD', 'Breath Activated Powder Inhaler');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('BAR', 'Bar');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('BARSOAP', 'Bar Soap');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('BEAD', 'Beads');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('BUCTAB', 'Buccal Tablet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('CAKE', 'Cake');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('CAP', 'Capsule');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('CAPLET', 'Caplet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('CEMENT', 'Cement');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('CHEWBAR', 'Chewable Bar');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('CHEWTAB', 'Chewable Tablet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('CPTAB', 'Coated Particles Tablet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('CRM', 'Cream');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('CRYS', 'Crystals');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('DERMSPRY', 'Dermal Spray');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('DISINTAB', 'Disintegrating Tablet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('DISK', 'Disk');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('DOUCHE', 'Douche');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('DROP', 'Drops');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('DRTAB', 'Delayed Release Tablet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ECTAB', 'Enteric Coated Tablet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ELIXIR', 'Elixir');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ENEMA', 'Enema');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ENTCAP', 'Enteric Coated Capsule');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ERCAP', 'Extended Release Capsule');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ERCAP12', '12 Hour Extended Release Capsule');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ERCAP24', '24 Hour Extended Release Capsule');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ERECTAB', 'Extended Release Enteric Coated Tablet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ERENTCAP', 'Extended Release Enteric Coated Capsule');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ERSUSP', 'Extended-Release Suspension');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ERSUSP12', '12 Hour Extended-Release Suspension');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ERSUSP24', '24 Hour Extended Release Suspension');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ERTAB', 'Extended Release Tablet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ERTAB12', '12 Hour Extended Release Tablet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ERTAB24', '24 Hour Extended Release Tablet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('FLAKE', 'Flakes');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('FOAM', 'Foam');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('FOAMAPL', 'Foam with Applicator');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('GASINHL', 'Gas for Inhalation');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('GEL', 'Gel');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('GELAPL', 'Gel with Applicator');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('GRAN', 'Granules');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('GUM', 'ChewingGum');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('INHL', 'Inhalant');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('INHLPWD', 'Inhalant Powder');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('INHLSOL', 'Inhalant Solution');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('IPSOL', 'Intraperitoneal Solution');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('IRSOL', 'Irrigation Solution');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ITSUSP', 'Intrathecal Suspension');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('IVSOL', 'Intravenous Solution');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('LIN', 'Liniment');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('LIQCLN', 'Liquid Cleanser');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('LIQSOAP', 'Medicated Liquid Soap');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('LTN', 'Lotion');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('MDINHL', 'Metered Dose Inhaler');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('MDINHLPWD', 'Metered Dose Powder Inhaler');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('MEDBAR', 'Medicated Bar Soap');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('MEDPAD', 'Medicated Pad');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('MEDSWAB', 'Medicated swab');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('MUCTOPSOL', 'Mucous Membrane Topical Solution');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('NASCRM', 'Nasal Cream');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('NASGEL', 'Nasal Gel');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('NASINHL', 'Nasal Inhalant');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('NASOINT', 'Nasal Ointment');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('NASSPRY', 'Nasal Spray');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('NDROP', 'Nasal Drops');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('OIL', 'Oil');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('OINT', 'Ointment');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('OINTAPL', 'Ointment with Applicator');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('OPCRM', 'Ophthalmic Cream');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('OPDROP', 'Ophthalmic Drops');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('OPGEL', 'Ophthalmic Gel');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('OPIRSOL', 'Ophthalmic Irrigation Solution');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('OPOINT', 'Ophthalmic Ointment');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('OPSUSP', 'Ophthalmic Suspension');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ORALSOL', 'Oral Solution');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ORCAP', 'Oral Capsule');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ORCRM', 'Oral Cream');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ORDROP', 'Oral Drops');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ORINHL', 'Oral Inhalant');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ORSUSP', 'Oral Suspension');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ORTAB', 'Oral Tablet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('ORTROCHE', 'Lozenge/Oral Troche');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('OTCRM', 'Otic Cream');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('OTDROP', 'Otic Drops');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('OTGEL', 'Otic Gel');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('OTOINT', 'Otic Ointment');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('OTSUSP', 'Otic Suspension');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('PAD', 'Pad');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('PASTE', 'Paste');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('PATCH', 'Patch');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('PELLET', 'Pellet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('PILL', 'Pill');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('POWD', 'Powder');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('PUD', 'Pudding');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('PUFF', 'Puff');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('PWDSPRY', 'Powder Spray');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('RECCRM', 'Rectal Cream');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('RECFORM', 'Rectal foam');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('RECOINT', 'Rectal Ointment');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('RECPWD', 'Rectal Powder');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('RECSOL', 'Rectal Solution');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('RECSPRY', 'Rectal Spray');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('RECSUPP', 'Rectal Suppository');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('RECSUSP', 'Rectal Suspension');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('RINSE', 'Mouthwash/Rinse');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('SCOOP', 'Scoops');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('SHMP', 'Shampoo');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('SLTAB', 'Sublingual Tablet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('SOL', 'Solution');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('SPRY', 'Sprays');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('SPRYADAPT', 'Spray with Adaptor');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('SRBUCTAB', 'Sustained Release Buccal Tablet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('SUPP', 'Suppository');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('SUSP', 'Suspension');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('SWAB', 'Swab');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('SYRUP', 'Syrup');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TAB', 'Tablet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TINC', 'Tincture');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TOPCRM', 'Topical Cream');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TOPGEL', 'Topical Gel');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TOPLTN', 'Topical Lotion');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TOPOIL', 'Topical Oil');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TOPOINT', 'Topical Ointment');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TOPPWD', 'Topical Powder');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TOPSOL', 'Topical Solution');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TPASTE', 'Toothpaste');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TPATCH', 'Transdermal Patch');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TPATH16', '16 Hour Transdermal Patch');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TPATH24', '24 Hour Transdermal Patch');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TPATH2WK', 'Biweekly Transdermal Patch');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TPATH72', '72 Hour Transdermal Patch');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('TPATHWK', 'Weekly Transdermal Patch');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('URETHGEL', 'Urethral Gel');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('URETHSUPP', 'Urethral suppository');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('VAGCRM', 'Vaginal Cream');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('VAGCRMAPL', 'Vaginal Cream with Applicator');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('VAGFOAM', 'Vaginal foam');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('VAGFOAMAPL', 'Vaginal foam with applicator');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('VAGGEL', 'Vaginal Gel');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('VAGOINT', 'Vaginal Ointment');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('VAGOINTAPL', 'Vaginal Ointment with Applicator');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('VAGPWD', 'Vaginal Powder');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('VAGSPRY', 'Vaginal Spray');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('VAGSUPP', 'Vaginal Suppository');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('VAGTAB', 'Vaginal Tablet');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('VGELAPL', 'Vaginal Gel with Applicator');
INSERT INTO `satu_sehat_ref_denominator` (`code`, `display`) VALUES ('WAFER', 'Wafer');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
