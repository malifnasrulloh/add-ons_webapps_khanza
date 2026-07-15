SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for satu_sehat_ref_route
-- ----------------------------
DROP TABLE IF EXISTS `satu_sehat_ref_route`;
CREATE TABLE `satu_sehat_ref_route`  (
  `code` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `display` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of satu_sehat_ref_route
-- ----------------------------
INSERT INTO `satu_sehat_ref_route` VALUES ('Chewing Gum', 'Chewing Gum');
INSERT INTO `satu_sehat_ref_route` VALUES ('cutaneous', 'Cutaneous');
INSERT INTO `satu_sehat_ref_route` VALUES ('implant', 'Implant');
INSERT INTO `satu_sehat_ref_route` VALUES ('Inhal', 'Inhalation');
INSERT INTO `satu_sehat_ref_route` VALUES ('Inhal.aerosol', 'Inhalation Aerosol');
INSERT INTO `satu_sehat_ref_route` VALUES ('Inhal.powder', 'Inhalation Powder');
INSERT INTO `satu_sehat_ref_route` VALUES ('Inhal.solution', 'Inhalation Solution');
INSERT INTO `satu_sehat_ref_route` VALUES ('inj.intramuscular', 'Injection Intramuscular');
INSERT INTO `satu_sehat_ref_route` VALUES ('inj.intrathecal', 'Injection Intrathecal');
INSERT INTO `satu_sehat_ref_route` VALUES ('inj.intravenous', 'Injection Intravenous');
INSERT INTO `satu_sehat_ref_route` VALUES ('inj.subcutaneous', 'Injection Subcutaneous');
INSERT INTO `satu_sehat_ref_route` VALUES ('Instill', 'Instillation');
INSERT INTO `satu_sehat_ref_route` VALUES ('Instill.solution', 'Instillation Solution');
INSERT INTO `satu_sehat_ref_route` VALUES ('intravesical', 'Intravesical');
INSERT INTO `satu_sehat_ref_route` VALUES ('lamella', 'Lamella');
INSERT INTO `satu_sehat_ref_route` VALUES ('N', 'Nasal');
INSERT INTO `satu_sehat_ref_route` VALUES ('O', 'Oral');
INSERT INTO `satu_sehat_ref_route` VALUES ('ocular', 'Ocular');
INSERT INTO `satu_sehat_ref_route` VALUES ('ointment', 'Ointment');
INSERT INTO `satu_sehat_ref_route` VALUES ('oral aerosol', 'Oral Aerosol');
INSERT INTO `satu_sehat_ref_route` VALUES ('otic', 'Otic');
INSERT INTO `satu_sehat_ref_route` VALUES ('P', 'Parenteral');
INSERT INTO `satu_sehat_ref_route` VALUES ('R', 'Rectal');
INSERT INTO `satu_sehat_ref_route` VALUES ('s.c. implant', 'S.C. Implant');
INSERT INTO `satu_sehat_ref_route` VALUES ('SL', 'Sublingual/Buccal/Oro mucosal');
INSERT INTO `satu_sehat_ref_route` VALUES ('stomatologic', 'stomatologic');
INSERT INTO `satu_sehat_ref_route` VALUES ('TD', 'Transdermal');
INSERT INTO `satu_sehat_ref_route` VALUES ('TD patch', 'Transdermal Patch');
INSERT INTO `satu_sehat_ref_route` VALUES ('Topical', 'Topikal (Oles/Kulit/Mata/Telinga)');
INSERT INTO `satu_sehat_ref_route` VALUES ('urethral', 'Urethral');
INSERT INTO `satu_sehat_ref_route` VALUES ('V', 'Vaginal');

SET FOREIGN_KEY_CHECKS = 1;
