/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.2.2-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: atlas_biolabs_bot
-- ------------------------------------------------------
-- Server version	12.2.2-MariaDB

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
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES
(1,'admin','admin@atlasbiolabs.com','$2y$10$OKHpbGCFvPOnUf/lLH6k3urmfWOabLJftBIwaVDNL0i7kqAqtUaCS','2026-05-12 04:38:18','2026-05-12 04:37:55','2026-05-12 04:38:18');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `business_profiles`
--

DROP TABLE IF EXISTS `business_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_profiles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `business_name` varchar(190) NOT NULL,
  `brand_name` varchar(190) NOT NULL,
  `tagline` varchar(255) DEFAULT NULL,
  `industry` varchar(120) DEFAULT NULL,
  `sender_name` varchar(190) NOT NULL,
  `sender_email` varchar(190) NOT NULL,
  `reply_to_email` varchar(190) DEFAULT NULL,
  `admin_notification_email` varchar(190) DEFAULT NULL,
  `business_address` text NOT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `primary_color` varchar(20) NOT NULL DEFAULT '#0A1A2F',
  `secondary_color` varchar(20) NOT NULL DEFAULT '#FFFFFF',
  `accent_color` varchar(20) NOT NULL DEFAULT '#2E6BFF',
  `compliance_footer` text NOT NULL,
  `default_signature` text NOT NULL,
  `daily_send_limit` int(10) unsigned NOT NULL DEFAULT 30,
  `timezone` varchar(80) NOT NULL DEFAULT 'UTC',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `business_profiles_active_index` (`active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_profiles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `business_profiles` WRITE;
/*!40000 ALTER TABLE `business_profiles` DISABLE KEYS */;
INSERT INTO `business_profiles` VALUES
(1,'Atlas BioLabs','Atlas BioLabs','Peptide sourcing support for qualified B2B buyers','Peptide supply and sourcing','Atlas BioLabs','no-reply@example.com','','admin@example.com','Business address placeholder','','','#0A1A2F','#FFFFFF','#2E6BFF','You are receiving this professional B2B email from Atlas BioLabs. This message is intended for qualified business sourcing conversations. No medical or human-use claims are made. You can unsubscribe using the link included in this email.','Atlas BioLabs\nPeptide sourcing support, MOQ flexibility, documentation support, batch transparency, and supply coordination.\nBusiness address placeholder',30,'UTC',1,'2026-05-12 04:53:36','2026-05-12 04:53:36'),
(2,'Local Test Business','Local Test Co','Practical test automation for local verification','Professional services','Local Test Co','test-sender@example.com','reply@example.com','admin@example.com','123 Test Street, Test City','https://example.com','','#123456','#FFFFFF','#0088CC','You are receiving this professional B2B email from Local Test Co. You can unsubscribe using the link included in this email.','Local Test Co\nProfessional services support\n123 Test Street, Test City',5,'UTC',1,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(3,'Queue Feature Test 20260512060018','Queue Feature Test 20260512060018','Queue workflow verification','B2B services','Queue Feature Test 20260512060018','queue-test@example.com','reply@example.com','admin@example.com','456 Queue Street','','','#111111','#FFFFFF','#3366FF','Compliance footer for Queue Feature Test 20260512060018','Queue Feature Test 20260512060018\n456 Queue Street',2,'UTC',1,'2026-05-12 07:00:18','2026-05-12 07:00:18');
/*!40000 ALTER TABLE `business_profiles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `email_campaigns`
--

DROP TABLE IF EXISTS `email_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_campaigns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` int(10) unsigned NOT NULL,
  `campaign_name` varchar(190) NOT NULL,
  `template_id` int(10) unsigned NOT NULL,
  `send_type` varchar(20) NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `selected_lead_ids` mediumtext DEFAULT NULL,
  `filter_rules` mediumtext DEFAULT NULL,
  `preview_count` int(10) unsigned NOT NULL DEFAULT 0,
  `skipped_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email_campaigns_business_created_index` (`business_profile_id`,`created_at`),
  KEY `email_campaigns_template_index` (`template_id`),
  CONSTRAINT `email_campaigns_business_fk` FOREIGN KEY (`business_profile_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_campaigns_template_fk` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_campaigns`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `email_campaigns` WRITE;
/*!40000 ALTER TABLE `email_campaigns` DISABLE KEYS */;
INSERT INTO `email_campaigns` VALUES
(1,3,'single 20260512060018',25,'single','2026-05-12 06:00:00','[8]','{\"q\":\"\",\"status\":\"\",\"category\":\"\",\"country\":\"\",\"source\":\"\",\"followup_stage\":\"\",\"created_from\":\"\",\"created_to\":\"\"}',1,0,'2026-05-12 07:00:18','2026-05-12 07:00:18'),
(2,3,'selected 20260512060018',25,'selected','2026-05-12 06:00:00','[9,11,12,13]','{\"q\":\"\",\"status\":\"\",\"category\":\"\",\"country\":\"\",\"source\":\"\",\"followup_stage\":\"\",\"created_from\":\"\",\"created_to\":\"\"}',1,3,'2026-05-12 07:00:18','2026-05-12 07:00:18'),
(3,3,'filtered 20260512060018',25,'filtered','2026-05-12 06:00:00','[8,9,10,11,12,13]','{\"q\":\"\",\"status\":\"\",\"category\":\"\",\"country\":\"\",\"source\":\"QueueTest\",\"followup_stage\":\"\",\"created_from\":\"\",\"created_to\":\"\"}',1,5,'2026-05-12 07:00:18','2026-05-12 07:00:18'),
(4,3,'duplicate 20260512060018',25,'single','2026-05-12 06:00:00','[9]','{\"q\":\"\",\"status\":\"\",\"category\":\"\",\"country\":\"\",\"source\":\"\",\"followup_stage\":\"\",\"created_from\":\"\",\"created_to\":\"\"}',0,1,'2026-05-12 07:00:18','2026-05-12 07:00:18'),
(5,3,'sender skip 20260512060046',28,'selected','2026-05-12 06:00:00','[14,15,16]','{\"q\":\"\",\"status\":\"\",\"category\":\"\",\"country\":\"\",\"source\":\"\",\"followup_stage\":\"\",\"created_from\":\"\",\"created_to\":\"\"}',3,0,'2026-05-12 07:00:46','2026-05-12 07:00:46');
/*!40000 ALTER TABLE `email_campaigns` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` int(10) unsigned NOT NULL,
  `lead_id` int(10) unsigned DEFAULT NULL,
  `template_id` int(10) unsigned DEFAULT NULL,
  `queue_id` int(10) unsigned DEFAULT NULL,
  `recipient_email` varchar(190) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email_logs_status_created_index` (`status`,`created_at`),
  KEY `email_logs_lead_index` (`lead_id`),
  KEY `email_logs_template_fk` (`template_id`),
  KEY `email_logs_queue_fk` (`queue_id`),
  KEY `email_logs_business_status_index` (`business_profile_id`,`status`,`created_at`),
  CONSTRAINT `email_logs_lead_fk` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `email_logs_queue_fk` FOREIGN KEY (`queue_id`) REFERENCES `email_queue` (`id`) ON DELETE SET NULL,
  CONSTRAINT `email_logs_template_fk` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
INSERT INTO `email_logs` VALUES
(1,1,2,1,2,'onemarketdev@gmail.com','Peptide sourcing support for One Market','sent',NULL,'2026-05-12 04:58:27'),
(2,1,2,1,3,'onemarketdev@gmail.com','Peptide sourcing support for One Market','sent',NULL,'2026-05-12 04:58:27'),
(3,1,2,1,4,'onemarketdev@gmail.com','Peptide sourcing support for One Market','sent',NULL,'2026-05-12 04:58:27'),
(4,1,4,1,5,'onemarketd@gmail.com','Peptide sourcing support for One Market','sent',NULL,'2026-05-12 04:58:27'),
(5,1,4,1,6,'onemarketd@gmail.com','Peptide sourcing support for One Market','sent',NULL,'2026-05-12 04:58:27'),
(6,1,5,9,7,'verification+1+20260512035827@example.test','Professional B2B introduction from Atlas BioLabs','sent',NULL,'2026-05-12 04:58:27'),
(7,2,6,17,14,'verification+2+20260512035827@example.test','Professional B2B introduction from Local Test Co','sent',NULL,'2026-05-12 04:58:27'),
(8,3,8,25,19,'eligible-single+20260512060018@example.test','Professional B2B introduction from Queue Feature Test 20260512060018','sent',NULL,'2026-05-12 07:00:19'),
(9,3,9,25,20,'eligible-selected+20260512060018@example.test','Professional B2B introduction from Queue Feature Test 20260512060018','sent',NULL,'2026-05-12 07:00:19'),
(10,3,10,25,21,'eligible-filtered+20260512060018@example.test','Professional B2B introduction from Queue Feature Test 20260512060018','sent',NULL,'2026-05-12 07:00:46'),
(11,1,7,4,16,'onemarev@gmail.com','Peptide sourcing and documentation support','sent',NULL,'2026-05-12 07:05:02'),
(12,1,7,4,17,'onemarev@gmail.com','Peptide sourcing and documentation support','sent',NULL,'2026-05-12 07:05:02'),
(13,1,7,4,18,'onemarev@gmail.com','Peptide sourcing and documentation support','sent',NULL,'2026-05-12 07:05:02');
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `email_queue`
--

DROP TABLE IF EXISTS `email_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` int(10) unsigned NOT NULL,
  `campaign_id` int(10) unsigned DEFAULT NULL,
  `campaign_name` varchar(190) DEFAULT NULL,
  `lead_id` int(10) unsigned NOT NULL,
  `template_id` int(10) unsigned NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email_queue_status_scheduled_index` (`status`,`scheduled_at`),
  KEY `email_queue_lead_index` (`lead_id`),
  KEY `email_queue_template_fk` (`template_id`),
  KEY `email_queue_business_status_index` (`business_profile_id`,`status`,`scheduled_at`),
  KEY `email_queue_campaign_index` (`campaign_id`),
  CONSTRAINT `email_queue_lead_fk` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_queue_template_fk` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_queue`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `email_queue` WRITE;
/*!40000 ALTER TABLE `email_queue` DISABLE KEYS */;
INSERT INTO `email_queue` VALUES
(2,1,NULL,NULL,2,1,'2026-05-12 03:39:47','2026-05-12 04:58:27','sent',NULL,'2026-05-12 04:39:47','2026-05-12 04:58:27'),
(3,1,NULL,NULL,2,1,'2026-05-12 03:39:52','2026-05-12 04:58:27','sent',NULL,'2026-05-12 04:39:52','2026-05-12 04:58:27'),
(4,1,NULL,NULL,2,1,'2026-05-12 03:39:56','2026-05-12 04:58:27','sent',NULL,'2026-05-12 04:39:56','2026-05-12 04:58:27'),
(5,1,NULL,NULL,4,1,'2026-05-12 03:41:50','2026-05-12 04:58:27','sent',NULL,'2026-05-12 04:41:50','2026-05-12 04:58:27'),
(6,1,NULL,NULL,4,1,'2026-05-12 03:43:07','2026-05-12 04:58:27','sent',NULL,'2026-05-12 04:43:07','2026-05-12 04:58:27'),
(7,1,NULL,NULL,5,9,'2026-05-12 03:58:27','2026-05-12 04:58:27','sent',NULL,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(8,1,NULL,NULL,2,6,'2026-05-15 03:58:27',NULL,'pending',NULL,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(9,1,NULL,NULL,2,6,'2026-05-15 03:58:27',NULL,'pending',NULL,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(10,1,NULL,NULL,2,6,'2026-05-15 03:58:27',NULL,'pending',NULL,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(11,1,NULL,NULL,4,6,'2026-05-15 03:58:27',NULL,'pending',NULL,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(12,1,NULL,NULL,4,6,'2026-05-15 03:58:27',NULL,'pending',NULL,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(13,1,NULL,NULL,5,6,'2026-05-15 03:58:27',NULL,'pending',NULL,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(14,2,NULL,NULL,6,17,'2026-05-12 03:58:27','2026-05-12 04:58:27','sent',NULL,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(15,2,NULL,NULL,6,20,'2026-05-15 03:58:27',NULL,'pending',NULL,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(16,1,NULL,NULL,7,4,'2026-05-12 03:59:44','2026-05-12 07:05:02','sent',NULL,'2026-05-12 04:59:44','2026-05-12 07:05:02'),
(17,1,NULL,NULL,7,4,'2026-05-12 03:59:55','2026-05-12 07:05:02','sent',NULL,'2026-05-12 04:59:55','2026-05-12 07:05:02'),
(18,1,NULL,NULL,7,4,'2026-05-12 05:07:15','2026-05-12 07:05:02','sent',NULL,'2026-05-12 06:07:15','2026-05-12 07:05:02'),
(19,3,1,'single 20260512060018',8,25,'2026-05-12 06:00:00','2026-05-12 07:00:19','sent',NULL,'2026-05-12 07:00:18','2026-05-12 07:00:19'),
(20,3,2,'selected 20260512060018',9,25,'2026-05-12 06:00:00','2026-05-12 07:00:19','sent',NULL,'2026-05-12 07:00:18','2026-05-12 07:00:19'),
(21,3,3,'filtered 20260512060018',10,25,'2026-05-12 06:00:00','2026-05-12 07:00:46','sent',NULL,'2026-05-12 07:00:18','2026-05-12 07:00:46'),
(22,3,NULL,NULL,8,28,'2026-05-17 06:00:19',NULL,'pending',NULL,'2026-05-12 07:00:19','2026-05-12 07:00:19'),
(23,3,NULL,NULL,9,28,'2026-05-17 06:00:19',NULL,'pending',NULL,'2026-05-12 07:00:19','2026-05-12 07:00:19'),
(24,3,5,'sender skip 20260512060046',14,28,'2026-05-12 06:00:00',NULL,'skipped','Lead is stopped, bounced, or unsubscribed.','2026-05-12 07:00:46','2026-05-12 07:00:46'),
(25,3,5,'sender skip 20260512060046',15,28,'2026-05-12 06:00:00',NULL,'skipped','Lead is stopped, bounced, or unsubscribed.','2026-05-12 07:00:46','2026-05-12 07:00:46'),
(26,3,5,'sender skip 20260512060046',16,28,'2026-05-12 06:00:00',NULL,'skipped','Lead is stopped, bounced, or unsubscribed.','2026-05-12 07:00:46','2026-05-12 07:00:46'),
(27,3,NULL,NULL,10,28,'2026-05-17 06:00:46',NULL,'pending',NULL,'2026-05-12 07:00:46','2026-05-12 07:00:46'),
(28,1,NULL,NULL,7,6,'2026-05-15 06:05:02',NULL,'pending',NULL,'2026-05-12 07:05:02','2026-05-12 07:05:02'),
(29,1,NULL,NULL,7,6,'2026-05-15 06:05:02',NULL,'pending',NULL,'2026-05-12 07:05:02','2026-05-12 07:05:02'),
(30,1,NULL,NULL,7,6,'2026-05-15 06:05:02',NULL,'pending',NULL,'2026-05-12 07:05:02','2026-05-12 07:05:02');
/*!40000 ALTER TABLE `email_queue` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` int(10) unsigned NOT NULL,
  `name` varchar(190) NOT NULL,
  `category` varchar(120) NOT NULL DEFAULT 'Other',
  `subject` varchar(255) NOT NULL,
  `body` mediumtext NOT NULL,
  `followup_stage` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_templates_business_name_unique` (`business_profile_id`,`name`),
  KEY `email_templates_stage_index` (`followup_stage`),
  KEY `email_templates_active_index` (`active`),
  KEY `email_templates_business_stage_index` (`business_profile_id`,`followup_stage`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_templates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `email_templates` WRITE;
/*!40000 ALTER TABLE `email_templates` DISABLE KEYS */;
INSERT INTO `email_templates` VALUES
(1,1,'Initial outreach - skincare/cosmetic formulators','Skincare / cosmetic formulators','Peptide sourcing support for {{company_name}}','Hello {{contact_name}},\n\nI am reaching out from {{brand_name}}. We support qualified B2B buyers with peptide sourcing coordination, MOQ flexibility, documentation support, and batch transparency.\n\nFor skincare and cosmetic formulation teams, our role is to help make sourcing conversations more organized, documented, and commercially practical.\n\nIf {{company_name}} is reviewing peptide supply options, I would be glad to share availability, documentation expectations, and sourcing next steps.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:31:27','2026-05-12 04:55:00'),
(2,1,'Initial outreach - ingredient distributors','Ingredient distributors','Documented peptide sourcing coordination','Hello {{contact_name}},\n\n{{brand_name}} supports peptide sourcing with MOQ flexibility, documentation support, batch transparency, and supply coordination for qualified B2B buyers.\n\nFor ingredient distribution teams, we focus on clear commercial communication, supply coordination, and documentation alignment without aggressive sales claims.\n\nIf {{company_name}} is evaluating peptide supply partners, we can share sourcing details and RFQ next steps.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:31:27','2026-05-12 04:55:00'),
(3,1,'Initial outreach - supplement/private-label teams','Supplement / private-label teams','Supply coordination support for {{company_name}}','Hello {{contact_name}},\n\nI am contacting you from {{brand_name}} regarding professional B2B peptide sourcing support.\n\n{{brand_name}} supports MOQ flexibility, documentation support, batch transparency, and supply coordination for qualified buyers in formulation, private-label, and distribution workflows.\n\nIf useful, we can help {{company_name}} review sourcing requirements and RFQ details.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:31:27','2026-05-12 04:55:00'),
(4,1,'Initial outreach - contract manufacturers','Contract manufacturers','Peptide sourcing and documentation support','Hello {{contact_name}},\n\n{{brand_name}} works with qualified B2B buyers that need organized peptide sourcing conversations, documentation support, MOQ flexibility, and batch transparency.\n\nFor contract manufacturing teams, we understand the importance of practical supply coordination and clear documentation before commercial decisions are made.\n\nIf {{company_name}} is reviewing peptide sourcing needs, I can share next steps for RFQs and documentation requests.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:31:27','2026-05-12 04:55:00'),
(5,1,'Initial outreach - research supply buyers','Research supply buyers','Peptide sourcing support for research supply teams','Hello {{contact_name}},\n\n{{brand_name}} supports qualified B2B research supply buyers with peptide sourcing coordination, MOQ flexibility, documentation support, and batch transparency.\n\nOur communication is focused on professional supply requirements, documentation expectations, and RFQ coordination.\n\nIf {{company_name}} is evaluating sourcing options, I would be glad to share availability and next steps.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:31:27','2026-05-12 04:55:00'),
(6,1,'Follow-up 1','Other','Following up from {{brand_name}}','Hello {{contact_name}},\n\nI wanted to follow up on my previous note from {{brand_name}}.\n\nWe support peptide sourcing with MOQ flexibility, documentation support, batch transparency, and supply coordination for qualified B2B buyers.\n\nIf this is relevant for {{company_name}}, I can share RFQ next steps or documentation expectations.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',1,1,'2026-05-12 04:31:27','2026-05-12 04:55:00'),
(7,1,'Follow-up 2','Other','Closing the loop from {{brand_name}}','Hello {{contact_name}},\n\nI am closing the loop on my previous outreach.\n\nIf peptide sourcing support, documentation alignment, MOQ flexibility, or batch transparency becomes relevant for {{company_name}}, {{brand_name}} would be glad to help coordinate the conversation.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',2,1,'2026-05-12 04:31:27','2026-05-12 04:55:00'),
(8,1,'RFQ confirmation email','Other','We received your {{brand_name}} RFQ','Hello {{contact_name}},\n\nThank you for contacting {{brand_name}}. We received your RFQ details and will review the request for sourcing fit, documentation expectations, MOQ support, and supply coordination next steps.\n\nOur team will follow up using the contact details provided.\n\n{{default_signature}}',9,1,'2026-05-12 04:31:27','2026-05-12 04:55:00'),
(9,1,'Generic - Initial B2B outreach','Other','Professional B2B introduction from {{brand_name}}','Hello {{contact_name}},\n\nI am reaching out from {{brand_name}}. {{tagline}}\n\nWe support professional {{industry}} conversations with clear coordination, practical next steps, and transparent business communication.\n\nIf this is relevant for {{company_name}}, I would be glad to share more details.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:55:00','2026-05-12 04:55:00'),
(10,1,'Generic - Service business outreach','Other','Service support for {{company_name}}','Hello {{contact_name}},\n\nI am contacting you from {{brand_name}} regarding professional service support for businesses in {{industry}}.\n\nIf {{company_name}} is reviewing outside support or vendor options, we can share capabilities, next steps, and fit criteria.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:55:00','2026-05-12 04:55:00'),
(11,1,'Generic - Product supplier outreach','Other','Supplier coordination from {{brand_name}}','Hello {{contact_name}},\n\n{{brand_name}} supports B2B product and supply conversations with clear documentation, practical coordination, and professional follow-up.\n\nIf {{company_name}} is reviewing supplier options, we can share availability, commercial details, and next steps.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:55:00','2026-05-12 04:55:00'),
(12,1,'Generic - Follow-up 1','Other','Following up from {{brand_name}}','Hello {{contact_name}},\n\nI wanted to follow up on my previous note from {{brand_name}}.\n\nIf this is relevant for {{company_name}}, I can share more details or route the conversation to the right next step.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',1,1,'2026-05-12 04:55:00','2026-05-12 04:55:00'),
(13,1,'Generic - Follow-up 2','Other','Closing the loop from {{brand_name}}','Hello {{contact_name}},\n\nI am closing the loop on my previous outreach.\n\nIf this becomes relevant for {{company_name}}, {{brand_name}} would be glad to help with a professional B2B conversation.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',2,1,'2026-05-12 04:55:00','2026-05-12 04:55:00'),
(14,1,'Generic - RFQ confirmation','Other','We received your request for {{brand_name}}','Hello {{contact_name}},\n\nThank you for contacting {{brand_name}}. We received your request and will review the details before following up.\n\n{{default_signature}}',9,1,'2026-05-12 04:55:00','2026-05-12 04:55:00'),
(15,1,'Generic - Quote follow-up','Other','Following up on your {{brand_name}} quote','Hello {{contact_name}},\n\nI wanted to follow up on the quote conversation with {{brand_name}}.\n\nIf you have questions about scope, timing, or next steps, we would be glad to help.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',3,1,'2026-05-12 04:55:00','2026-05-12 04:55:00'),
(16,1,'Generic - Re-engagement email','Other','Checking in from {{brand_name}}','Hello {{contact_name}},\n\nI am checking in from {{brand_name}} in case this is a better time to reconnect.\n\nIf {{company_name}} is reviewing options in {{industry}}, we can share current capabilities and next steps.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',4,1,'2026-05-12 04:55:00','2026-05-12 04:55:00'),
(17,2,'Generic - Initial B2B outreach','Other','Professional B2B introduction from {{brand_name}}','Hello {{contact_name}},\n\nI am reaching out from {{brand_name}}. {{tagline}}\n\nWe support professional {{industry}} conversations with clear coordination, practical next steps, and transparent business communication.\n\nIf this is relevant for {{company_name}}, I would be glad to share more details.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(18,2,'Generic - Service business outreach','Other','Service support for {{company_name}}','Hello {{contact_name}},\n\nI am contacting you from {{brand_name}} regarding professional service support for businesses in {{industry}}.\n\nIf {{company_name}} is reviewing outside support or vendor options, we can share capabilities, next steps, and fit criteria.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(19,2,'Generic - Product supplier outreach','Other','Supplier coordination from {{brand_name}}','Hello {{contact_name}},\n\n{{brand_name}} supports B2B product and supply conversations with clear documentation, practical coordination, and professional follow-up.\n\nIf {{company_name}} is reviewing supplier options, we can share availability, commercial details, and next steps.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(20,2,'Generic - Follow-up 1','Other','Following up from {{brand_name}}','Hello {{contact_name}},\n\nI wanted to follow up on my previous note from {{brand_name}}.\n\nIf this is relevant for {{company_name}}, I can share more details or route the conversation to the right next step.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',1,1,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(21,2,'Generic - Follow-up 2','Other','Closing the loop from {{brand_name}}','Hello {{contact_name}},\n\nI am closing the loop on my previous outreach.\n\nIf this becomes relevant for {{company_name}}, {{brand_name}} would be glad to help with a professional B2B conversation.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',2,1,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(22,2,'Generic - RFQ confirmation','Other','We received your request for {{brand_name}}','Hello {{contact_name}},\n\nThank you for contacting {{brand_name}}. We received your request and will review the details before following up.\n\n{{default_signature}}',9,1,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(23,2,'Generic - Quote follow-up','Other','Following up on your {{brand_name}} quote','Hello {{contact_name}},\n\nI wanted to follow up on the quote conversation with {{brand_name}}.\n\nIf you have questions about scope, timing, or next steps, we would be glad to help.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',3,1,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(24,2,'Generic - Re-engagement email','Other','Checking in from {{brand_name}}','Hello {{contact_name}},\n\nI am checking in from {{brand_name}} in case this is a better time to reconnect.\n\nIf {{company_name}} is reviewing options in {{industry}}, we can share current capabilities and next steps.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',4,1,'2026-05-12 04:58:27','2026-05-12 04:58:27'),
(25,3,'Generic - Initial B2B outreach','Other','Professional B2B introduction from {{brand_name}}','Hello {{contact_name}},\n\nI am reaching out from {{brand_name}}. {{tagline}}\n\nWe support professional {{industry}} conversations with clear coordination, practical next steps, and transparent business communication.\n\nIf this is relevant for {{company_name}}, I would be glad to share more details.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 07:00:18','2026-05-12 07:00:18'),
(26,3,'Generic - Service business outreach','Other','Service support for {{company_name}}','Hello {{contact_name}},\n\nI am contacting you from {{brand_name}} regarding professional service support for businesses in {{industry}}.\n\nIf {{company_name}} is reviewing outside support or vendor options, we can share capabilities, next steps, and fit criteria.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 07:00:18','2026-05-12 07:00:18'),
(27,3,'Generic - Product supplier outreach','Other','Supplier coordination from {{brand_name}}','Hello {{contact_name}},\n\n{{brand_name}} supports B2B product and supply conversations with clear documentation, practical coordination, and professional follow-up.\n\nIf {{company_name}} is reviewing supplier options, we can share availability, commercial details, and next steps.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 07:00:18','2026-05-12 07:00:18'),
(28,3,'Generic - Follow-up 1','Other','Following up from {{brand_name}}','Hello {{contact_name}},\n\nI wanted to follow up on my previous note from {{brand_name}}.\n\nIf this is relevant for {{company_name}}, I can share more details or route the conversation to the right next step.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',1,1,'2026-05-12 07:00:18','2026-05-12 07:00:18'),
(29,3,'Generic - Follow-up 2','Other','Closing the loop from {{brand_name}}','Hello {{contact_name}},\n\nI am closing the loop on my previous outreach.\n\nIf this becomes relevant for {{company_name}}, {{brand_name}} would be glad to help with a professional B2B conversation.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',2,1,'2026-05-12 07:00:18','2026-05-12 07:00:18'),
(30,3,'Generic - RFQ confirmation','Other','We received your request for {{brand_name}}','Hello {{contact_name}},\n\nThank you for contacting {{brand_name}}. We received your request and will review the details before following up.\n\n{{default_signature}}',9,1,'2026-05-12 07:00:18','2026-05-12 07:00:18'),
(31,3,'Generic - Quote follow-up','Other','Following up on your {{brand_name}} quote','Hello {{contact_name}},\n\nI wanted to follow up on the quote conversation with {{brand_name}}.\n\nIf you have questions about scope, timing, or next steps, we would be glad to help.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',3,1,'2026-05-12 07:00:18','2026-05-12 07:00:18'),
(32,3,'Generic - Re-engagement email','Other','Checking in from {{brand_name}}','Hello {{contact_name}},\n\nI am checking in from {{brand_name}} in case this is a better time to reconnect.\n\nIf {{company_name}} is reviewing options in {{industry}}, we can share current capabilities and next steps.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',4,1,'2026-05-12 07:00:18','2026-05-12 07:00:18');
/*!40000 ALTER TABLE `email_templates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `leads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` int(10) unsigned NOT NULL,
  `company_name` varchar(190) NOT NULL,
  `contact_name` varchar(190) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(80) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `category` varchar(120) NOT NULL DEFAULT 'Other',
  `source` varchar(120) DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'new',
  `notes` text DEFAULT NULL,
  `last_contacted_at` datetime DEFAULT NULL,
  `next_followup_at` datetime DEFAULT NULL,
  `followup_stage` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `bounced` tinyint(1) NOT NULL DEFAULT 0,
  `unsubscribed` tinyint(1) NOT NULL DEFAULT 0,
  `unsubscribe_token` char(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `leads_unsubscribe_token_unique` (`unsubscribe_token`),
  UNIQUE KEY `leads_business_email_unique` (`business_profile_id`,`email`),
  KEY `leads_status_index` (`status`),
  KEY `leads_next_followup_index` (`next_followup_at`),
  KEY `leads_category_index` (`category`),
  KEY `leads_business_status_index` (`business_profile_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES
(2,1,'One Market','Fomenky Jason','onemarketdev@gmail.com','671353758','onemarketc.com','Cameroon','Skincare / cosmetic formulators','We','emailed','ddsfsdfsdfds','2026-05-12 04:58:27','2026-05-15 03:58:27',0,0,0,'32aaa182700ffb9401402889aa164e757806fda649a8a6b8cc76106ee13af33b','2026-05-12 04:39:47','2026-05-12 04:58:27'),
(4,1,'One Market','Fomenky','onemarketd@gmail.com','650673248','https://onemarketc.com','Cameroon','Aesthetic / beauty product developers','We','emailed','jhgkjhmnngfh','2026-05-12 04:58:27','2026-05-15 03:58:27',0,0,0,'6e714a368b4f35fc526eb5ecd66200beea59cd6aea72b9c9501b18193f349847','2026-05-12 04:41:50','2026-05-12 04:58:27'),
(5,1,'Verification Company 1','Verification Contact','verification+1+20260512035827@example.test',NULL,NULL,NULL,'Other','Verification','emailed',NULL,'2026-05-12 04:58:27','2026-05-15 03:58:27',0,0,0,'62b60655fc5d6f365f8b85b09593ed408ff9d6c97b10f63098d9802dc3534d73','2026-05-12 04:58:27','2026-05-12 04:58:27'),
(6,2,'Verification Company 2','Verification Contact','verification+2+20260512035827@example.test',NULL,NULL,NULL,'Other','Verification','emailed',NULL,'2026-05-12 04:58:27','2026-05-15 03:58:27',0,0,0,'61c4567f47fcde6dca218a534427c94cdc7c8b8cbb77f88bf8cf84fcde8f0990','2026-05-12 04:58:27','2026-05-12 04:58:27'),
(7,1,'One Market','Fomenky','onemarev@gmail.com','650673248','onemarketc.com','Cameroon','Contract manufacturers','Web','emailed','','2026-05-12 07:05:02','2026-05-15 06:05:02',0,0,0,'afaa1ef4b05bfe80821aa00e17d71f90a0ce08279015e65d5101c7e677cbd25c','2026-05-12 04:59:44','2026-05-12 07:05:02'),
(8,3,'eligible-single Co','eligible-single Contact','eligible-single+20260512060018@example.test',NULL,NULL,'US','Prospects','QueueTest','emailed',NULL,'2026-05-12 07:00:19','2026-05-17 06:00:19',0,0,0,'fba7c220c16d7c3f4e49d9debda1edc98303a38555aed4e0e7217bc14c7882f0','2026-05-12 07:00:18','2026-05-12 07:00:19'),
(9,3,'eligible-selected Co','eligible-selected Contact','eligible-selected+20260512060018@example.test',NULL,NULL,'US','Prospects','QueueTest','emailed',NULL,'2026-05-12 07:00:19','2026-05-17 06:00:19',0,0,0,'6dfb9e203a2fedb16fcbae651bf3ada922e851a9f99fbe2058238db4dde3fd8b','2026-05-12 07:00:18','2026-05-12 07:00:19'),
(10,3,'eligible-filtered Co','eligible-filtered Contact','eligible-filtered+20260512060018@example.test',NULL,NULL,'US','Prospects','QueueTest','emailed',NULL,'2026-05-12 07:00:46','2026-05-17 06:00:46',0,0,0,'48f727d67c1008cd30b68e806716802e5583023e3bf609f010b1eaafff7a701f','2026-05-12 07:00:18','2026-05-12 07:00:46'),
(11,3,'skip-unsub Co','skip-unsub Contact','skip-unsub+20260512060018@example.test',NULL,NULL,'US','Prospects','QueueTest','new',NULL,NULL,NULL,0,0,1,'559afc83e30170170be7bab11994195b83c402637cf69752e0c95f0d18f2f702','2026-05-12 07:00:18','2026-05-12 07:00:18'),
(12,3,'skip-bounced Co','skip-bounced Contact','skip-bounced+20260512060018@example.test',NULL,NULL,'US','Prospects','QueueTest','new',NULL,NULL,NULL,0,1,0,'4a522d559b5e34bdb8d440811c097e7395ba264af0cb1d80c6db9627ac5801fe','2026-05-12 07:00:18','2026-05-12 07:00:18'),
(13,3,'skip-stopped Co','skip-stopped Contact','skip-stopped+20260512060018@example.test',NULL,NULL,'US','Prospects','QueueTest','interested',NULL,NULL,NULL,0,0,0,'a7b0c400eb4604354347c1e629a6f2c36afa7c1ff8bd4c99ff21a0ce1a1c6921','2026-05-12 07:00:18','2026-05-12 07:00:18'),
(14,3,'sender-unsub Co','sender-unsub Contact','sender-unsub+20260512060046@example.test',NULL,NULL,'US','Prospects','SenderSkip','unsubscribed',NULL,NULL,NULL,0,0,1,'732308a0b241535b51807246c1ab6be40cca1936b4aed38c8f1058622815d1b0','2026-05-12 07:00:46','2026-05-12 07:00:46'),
(15,3,'sender-bounced Co','sender-bounced Contact','sender-bounced+20260512060046@example.test',NULL,NULL,'US','Prospects','SenderSkip','bounced',NULL,NULL,NULL,0,1,0,'d05684a0f0b2bcb244a4bfb6df723c656dc7dfaae3e80c95c2919c9d0f62f520','2026-05-12 07:00:46','2026-05-12 07:00:46'),
(16,3,'sender-stopped Co','sender-stopped Contact','sender-stopped+20260512060046@example.test',NULL,NULL,'US','Prospects','SenderSkip','interested',NULL,NULL,NULL,0,0,0,'a8be118b5ec97cb4f357c510915f6f1efa52531f0ee2945c4ec6e97bc308e723','2026-05-12 07:00:46','2026-05-12 07:00:46');
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `rate_limits`
--

DROP TABLE IF EXISTS `rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rate_limits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(80) NOT NULL,
  `identifier` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `rate_limits_action_identifier_index` (`action`,`identifier`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rate_limits`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `rate_limits` WRITE;
/*!40000 ALTER TABLE `rate_limits` DISABLE KEYS */;
/*!40000 ALTER TABLE `rate_limits` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `rfqs`
--

DROP TABLE IF EXISTS `rfqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rfqs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` int(10) unsigned NOT NULL,
  `lead_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(190) NOT NULL,
  `company` varchar(190) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(80) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `product_interest` varchar(255) NOT NULL,
  `estimated_quantity` varchar(120) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `rfqs_created_index` (`created_at`),
  KEY `rfqs_lead_index` (`lead_id`),
  KEY `rfqs_business_created_index` (`business_profile_id`,`created_at`),
  CONSTRAINT `rfqs_lead_fk` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rfqs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `rfqs` WRITE;
/*!40000 ALTER TABLE `rfqs` DISABLE KEYS */;
/*!40000 ALTER TABLE `rfqs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` int(10) unsigned NOT NULL,
  `setting_key` varchar(120) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_business_key_unique` (`business_profile_id`,`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
(1,1,'daily_send_limit','30','2026-05-12 04:31:27','2026-05-12 06:58:38'),
(2,1,'admin_notification_email','admin@example.com','2026-05-12 04:31:27','2026-05-12 04:53:36'),
(3,1,'sender_name','Atlas BioLabs','2026-05-12 04:31:27','2026-05-12 04:53:36'),
(4,1,'sender_email','no-reply@example.com','2026-05-12 04:31:27','2026-05-12 04:53:36'),
(5,1,'business_address','Business address placeholder','2026-05-12 04:31:27','2026-05-12 06:58:38'),
(6,1,'email_provider','log','2026-05-12 04:31:27','2026-05-12 04:53:36'),
(7,1,'followup_1_days','3','2026-05-12 04:31:27','2026-05-12 06:58:38'),
(8,1,'followup_2_days','7','2026-05-12 04:31:27','2026-05-12 06:58:38'),
(9,1,'lead_categories','Skincare / cosmetic formulators\nIngredient distributors\nSupplement / private-label teams\nContract manufacturers\nResearch supply buyers\nAesthetic / beauty product developers\nOther','2026-05-12 04:56:04','2026-05-12 04:56:04'),
(10,2,'lead_categories','Prospects\nPartners\nCustomers\nOther','2026-05-12 04:58:27','2026-05-12 04:58:27'),
(11,2,'followup_1_days','3','2026-05-12 04:58:27','2026-05-12 04:58:27'),
(12,2,'followup_2_days','7','2026-05-12 04:58:27','2026-05-12 04:58:27'),
(13,1,'APP_NAME','Atlas BioLabs','2026-05-12 06:58:38','2026-05-12 06:58:38'),
(14,1,'BUSINESS_NAME','Atlas BioLabs','2026-05-12 06:58:38','2026-05-12 06:58:38'),
(15,1,'BUSINESS_TAGLINE','Peptide sourcing support for qualified B2B buyers','2026-05-12 06:58:38','2026-05-12 06:58:38'),
(16,1,'MAIL_PROVIDER','log','2026-05-12 06:58:38','2026-05-12 06:58:38'),
(17,1,'MAIL_FROM_NAME','Atlas BioLabs','2026-05-12 06:58:38','2026-05-12 06:58:38'),
(18,1,'MAIL_FROM_EMAIL','no-reply@example.com','2026-05-12 06:58:38','2026-05-12 06:58:38'),
(19,1,'MAIL_REPLY_TO','','2026-05-12 06:58:38','2026-05-12 06:58:38'),
(20,1,'MAIL_SMTP_HOST','','2026-05-12 06:58:38','2026-05-12 06:58:38'),
(21,1,'MAIL_SMTP_PORT','587','2026-05-12 06:58:38','2026-05-12 06:58:38'),
(22,1,'MAIL_SMTP_USER','','2026-05-12 06:58:38','2026-05-12 06:58:38'),
(23,1,'ADMIN_EMAIL','admin@example.com','2026-05-12 06:58:38','2026-05-12 06:58:38'),
(24,1,'DEFAULT_SIGNATURE','Atlas BioLabs\nPeptide sourcing support, MOQ flexibility, documentation support, batch transparency, and supply coordination.\nBusiness address placeholder','2026-05-12 06:58:38','2026-05-12 06:58:38'),
(25,1,'COMPLIANCE_FOOTER','You are receiving this professional B2B email from Atlas BioLabs. This message is intended for qualified business sourcing conversations. No medical or human-use claims are made. You can unsubscribe using the link included in this email.','2026-05-12 06:58:38','2026-05-12 06:58:38'),
(26,1,'UNSUBSCRIBE_FOOTER_TEXT','You can unsubscribe using the link included in this email.','2026-05-12 06:58:38','2026-05-12 06:58:38'),
(31,3,'MAIL_PROVIDER','log','2026-05-12 07:00:18','2026-05-12 07:00:18'),
(32,3,'MAIL_FROM_NAME','Queue Feature Test 20260512060018','2026-05-12 07:00:18','2026-05-12 07:00:18'),
(33,3,'MAIL_FROM_EMAIL','queue-test@example.com','2026-05-12 07:00:18','2026-05-12 07:00:18'),
(34,3,'MAIL_REPLY_TO','reply@example.com','2026-05-12 07:00:18','2026-05-12 07:00:18'),
(35,3,'BUSINESS_ADDRESS','456 Queue Street','2026-05-12 07:00:18','2026-05-12 07:00:18'),
(36,3,'COMPLIANCE_FOOTER','Compliance footer for Queue Feature Test 20260512060018','2026-05-12 07:00:18','2026-05-12 07:00:18'),
(37,3,'DEFAULT_SIGNATURE','Queue Feature Test 20260512060018\n456 Queue Street','2026-05-12 07:00:18','2026-05-12 07:00:18'),
(38,3,'DAILY_SEND_LIMIT','10','2026-05-12 07:00:18','2026-05-12 07:00:46'),
(39,3,'FOLLOWUP_1_DAYS','5','2026-05-12 07:00:18','2026-05-12 07:00:18'),
(40,3,'FOLLOWUP_2_DAYS','9','2026-05-12 07:00:18','2026-05-12 07:00:18'),
(41,3,'lead_categories','Prospects\nOther','2026-05-12 07:00:18','2026-05-12 07:00:18');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-05-12  7:06:46
