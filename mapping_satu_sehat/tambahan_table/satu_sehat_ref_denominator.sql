/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: sik
-- ------------------------------------------------------
-- Server version	11.4.10-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `satu_sehat_ref_denominator`
--

DROP TABLE IF EXISTS `satu_sehat_ref_denominator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `satu_sehat_ref_denominator` (
  `code` varchar(50) NOT NULL,
  `display` varchar(120) DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `satu_sehat_ref_denominator`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `satu_sehat_ref_denominator` WRITE;
/*!40000 ALTER TABLE `satu_sehat_ref_denominator` DISABLE KEYS */;
INSERT INTO `satu_sehat_ref_denominator` VALUES ('AER','Aerosol');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('APPFUL','Applicatorful');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('BAINHL','Breath Activated Inhaler');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('BAINHLPWD','Breath Activated Powder Inhaler');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('BAR','Bar');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('BARSOAP','Bar Soap');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('BEAD','Beads');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('BUCTAB','Buccal Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('CAKE','Cake');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('CAP','Capsule');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('CAPLET','Caplet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('CEMENT','Cement');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('CHEWBAR','Chewable Bar');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('CHEWTAB','Chewable Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('CPTAB','Coated Particles Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('CRM','Cream');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('CRYS','Crystals');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('DERMSPRY','Dermal Spray');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('DISINTAB','Disintegrating Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('DISK','Disk');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('DOUCHE','Douche');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('DROP','Drops');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('DRTAB','Delayed Release Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ECTAB','Enteric Coated Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ELIXIR','Elixir');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ENEMA','Enema');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ENTCAP','Enteric Coated Capsule');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ERCAP','Extended Release Capsule');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ERCAP12','12 Hour Extended Release Capsule');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ERCAP24','24 Hour Extended Release Capsule');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ERECTAB','Extended Release Enteric Coated Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ERENTCAP','Extended Release Enteric Coated Capsule');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ERSUSP','Extended-Release Suspension');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ERSUSP12','12 Hour Extended-Release Suspension');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ERSUSP24','24 Hour Extended Release Suspension');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ERTAB','Extended Release Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ERTAB12','12 Hour Extended Release Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ERTAB24','24 Hour Extended Release Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('FLAKE','Flakes');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('FOAM','Foam');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('FOAMAPL','Foam with Applicator');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('GASINHL','Gas for Inhalation');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('GEL','Gel');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('GELAPL','Gel with Applicator');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('GRAN','Granules');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('GUM','ChewingGum');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('INHL','Inhalant');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('INHLPWD','Inhalant Powder');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('INHLSOL','Inhalant Solution');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('IPSOL','Intraperitoneal Solution');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('IRSOL','Irrigation Solution');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ITSUSP','Intrathecal Suspension');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('IVSOL','Intravenous Solution');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('LIN','Liniment');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('LIQCLN','Liquid Cleanser');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('LIQSOAP','Medicated Liquid Soap');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('LTN','Lotion');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('MDINHL','Metered Dose Inhaler');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('MDINHLPWD','Metered Dose Powder Inhaler');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('MEDBAR','Medicated Bar Soap');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('MEDPAD','Medicated Pad');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('MEDSWAB','Medicated swab');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('MUCTOPSOL','Mucous Membrane Topical Solution');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('NASCRM','Nasal Cream');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('NASGEL','Nasal Gel');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('NASINHL','Nasal Inhalant');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('NASOINT','Nasal Ointment');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('NASSPRY','Nasal Spray');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('NDROP','Nasal Drops');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OIL','Oil');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OINT','Ointment');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OINTAPL','Ointment with Applicator');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OPCRM','Ophthalmic Cream');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OPDROP','Ophthalmic Drops');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OPGEL','Ophthalmic Gel');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OPIRSOL','Ophthalmic Irrigation Solution');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OPOINT','Ophthalmic Ointment');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OPSUSP','Ophthalmic Suspension');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ORALSOL','Oral Solution');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ORCAP','Oral Capsule');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ORCRM','Oral Cream');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ORDROP','Oral Drops');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ORINHL','Oral Inhalant');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ORSUSP','Oral Suspension');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ORTAB','Oral Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('ORTROCHE','Lozenge/Oral Troche');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OTCRM','Otic Cream');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OTDROP','Otic Drops');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OTGEL','Otic Gel');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OTOINT','Otic Ointment');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('OTSUSP','Otic Suspension');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('PAD','Pad');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('PASTE','Paste');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('PATCH','Patch');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('PELLET','Pellet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('PILL','Pill');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('POWD','Powder');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('PUD','Pudding');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('PUFF','Puff');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('PWDSPRY','Powder Spray');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('RECCRM','Rectal Cream');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('RECFORM','Rectal foam');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('RECOINT','Rectal Ointment');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('RECPWD','Rectal Powder');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('RECSOL','Rectal Solution');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('RECSPRY','Rectal Spray');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('RECSUPP','Rectal Suppository');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('RECSUSP','Rectal Suspension');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('RINSE','Mouthwash/Rinse');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SCOOP','Scoops');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SHMP','Shampoo');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SLTAB','Sublingual Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SOL','Solution');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SPRY','Sprays');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SPRYADAPT','Spray with Adaptor');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SRBUCTAB','Sustained Release Buccal Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SUPP','Suppository');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SUSP','Suspension');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SWAB','Swab');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('SYRUP','Syrup');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TAB','Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TINC','Tincture');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TOPCRM','Topical Cream');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TOPGEL','Topical Gel');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TOPLTN','Topical Lotion');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TOPOIL','Topical Oil');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TOPOINT','Topical Ointment');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TOPPWD','Topical Powder');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TOPSOL','Topical Solution');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TPASTE','Toothpaste');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TPATCH','Transdermal Patch');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TPATH16','16 Hour Transdermal Patch');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TPATH24','24 Hour Transdermal Patch');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TPATH2WK','Biweekly Transdermal Patch');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TPATH72','72 Hour Transdermal Patch');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('TPATHWK','Weekly Transdermal Patch');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('URETHGEL','Urethral Gel');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('URETHSUPP','Urethral suppository');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VAGCRM','Vaginal Cream');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VAGCRMAPL','Vaginal Cream with Applicator');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VAGFOAM','Vaginal foam');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VAGFOAMAPL','Vaginal foam with applicator');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VAGGEL','Vaginal Gel');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VAGOINT','Vaginal Ointment');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VAGOINTAPL','Vaginal Ointment with Applicator');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VAGPWD','Vaginal Powder');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VAGSPRY','Vaginal Spray');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VAGSUPP','Vaginal Suppository');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VAGTAB','Vaginal Tablet');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('VGELAPL','Vaginal Gel with Applicator');
INSERT INTO `satu_sehat_ref_denominator` VALUES ('WAFER','Wafer');
/*!40000 ALTER TABLE `satu_sehat_ref_denominator` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping events for database 'sik'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-06-26 18:59:31
