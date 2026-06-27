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

 Date: 27/06/2026 20:04:21
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for satu_sehat_ref_route
-- ----------------------------
DROP TABLE IF EXISTS `satu_sehat_ref_route`;
CREATE TABLE `satu_sehat_ref_route` (
  `code` varchar(50) NOT NULL,
  `display` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of satu_sehat_ref_route
-- ----------------------------
BEGIN;
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('Chewable', 'Chewable tab');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('cutaneous', 'Cutaneous');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('implant', 'Implant');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('Inhal', 'Inhalation');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('Inhal.aerosol', 'Inhalation Aerosol');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('Inhal.powder', 'Inhalation Powder');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('Inhal.solution', 'Inhalation Solution');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('inj.intramuscular', 'Injection Intramuscular');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('inj.intrathecal', 'Injection Intrathecal');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('inj.intravenous', 'Injection Intravenous');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('inj.subcutaneous', 'Injection Subcutaneous');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('Instill', 'Instillation');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('Instill.solution', 'Instillation Solution');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('intravesical', 'Intravesical');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('lamella', 'Lamella');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('mouthwash', 'Gargle');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('N', 'Nasal');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('O', 'Oral');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('ocular', 'Ocular');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('ointment', 'Ointment');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('oral aerosol', 'Oral Aerosol');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('otic', 'Otic');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('P', 'Parenteral');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('R', 'Rectal');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('s.c. implant', 'S.C. Implant');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('SL', 'Sublingual/Buccal/Oro mucosal');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('stomatologic', 'stomatologic');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('TD', 'Transdermal');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('TD patch', 'Transdermal Patch');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('Topical', 'Topikal (Oles/Kulit/Mata/Telinga)');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('urethral', 'Urethral');
INSERT INTO `satu_sehat_ref_route` (`code`, `display`) VALUES ('V', 'Vaginal');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
