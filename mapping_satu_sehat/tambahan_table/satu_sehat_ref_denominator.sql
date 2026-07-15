SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for satu_sehat_ref_denominator
-- Sumber: HL7 ValueSet v3-orderableDrugForm
-- System: https://terminology.hl7.org/CodeSystem/v3-orderableDrugForm
-- ----------------------------
DROP TABLE IF EXISTS `satu_sehat_ref_denominator`;
CREATE TABLE `satu_sehat_ref_denominator` (
  `code`    varchar(50)  NOT NULL,
  `display` varchar(120) DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=Dynamic;

-- ----------------------------
-- Records of satu_sehat_ref_denominator
-- ----------------------------
INSERT INTO `satu_sehat_ref_denominator` VALUES ('AER', 'Aerosol');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('CAP', 'Capsule');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('CAPLET', 'Caplet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('CHEWTAB', 'Chewable Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('CRM', 'Cream');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('DERMSPRY', 'Dermal Spray');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('DISINTAB', 'Disintegrating Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('DROP', 'Drops');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('DRTAB', 'Delayed Release Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ECTAB', 'Enteric Coated Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ELIXIR', 'Elixir');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ENEMA', 'Enema');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ERENTCAP', 'Extended Release Enteric Coated Capsule');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('GEL', 'Gel');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('GRAN', 'Granules');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('INHLPWD', 'Inhalant Powder');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('INHLSOL', 'Inhalant Solution');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('IVSOL', 'Intravenous Solution');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('MDINHL', 'Metered Dose Inhaler');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('NASSPRY', 'Nasal Spray');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('NDROP', 'Nasal Drops');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OINT', 'Ointment');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OPDROP', 'Ophthalmic Drops');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OPGEL', 'Ophthalmic Gel');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OPOINT', 'Ophthalmic Ointment');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ORDROP', 'Oral Drops');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ORINHL', 'Oral Inhalant');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ORTROCHE', 'Lozenge/Oral Troche');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OTDROP', 'Otic Drops');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('PASTE', 'Paste');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('PATCH', 'Patch');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('POWD', 'Powder');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('RINSE', 'Mouthwash/Rinse');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SHMP', 'Shampoo');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SLTAB', 'Sublingual Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SOL', 'Solution');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SPRY', 'Sprays');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SUPP', 'Suppository');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SUSP', 'Suspension');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SYRUP', 'Syrup');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TAB', 'Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TOPPWD', 'Topical Powder');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VAGCRM', 'Vaginal Cream');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VAGGEL', 'Vaginal Gel');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VAGSUPP', 'Vaginal Suppository');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VAGTAB', 'Vaginal Tablet');

SET FOREIGN_KEY_CHECKS = 1;
