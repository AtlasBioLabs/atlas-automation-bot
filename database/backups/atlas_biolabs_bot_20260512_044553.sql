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
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  CONSTRAINT `email_logs_lead_fk` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `email_logs_queue_fk` FOREIGN KEY (`queue_id`) REFERENCES `email_queue` (`id`) ON DELETE SET NULL,
  CONSTRAINT `email_logs_template_fk` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
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
  CONSTRAINT `email_queue_lead_fk` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_queue_template_fk` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_queue`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `email_queue` WRITE;
/*!40000 ALTER TABLE `email_queue` DISABLE KEYS */;
INSERT INTO `email_queue` VALUES
(2,2,1,'2026-05-12 03:39:47',NULL,'pending',NULL,'2026-05-12 04:39:47','2026-05-12 04:39:47'),
(3,2,1,'2026-05-12 03:39:52',NULL,'pending',NULL,'2026-05-12 04:39:52','2026-05-12 04:39:52'),
(4,2,1,'2026-05-12 03:39:56',NULL,'pending',NULL,'2026-05-12 04:39:56','2026-05-12 04:39:56'),
(5,4,1,'2026-05-12 03:41:50',NULL,'pending',NULL,'2026-05-12 04:41:50','2026-05-12 04:41:50'),
(6,4,1,'2026-05-12 03:43:07',NULL,'pending',NULL,'2026-05-12 04:43:07','2026-05-12 04:43:07');
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
  `name` varchar(190) NOT NULL,
  `category` varchar(120) NOT NULL DEFAULT 'Other',
  `subject` varchar(255) NOT NULL,
  `body` mediumtext NOT NULL,
  `followup_stage` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_templates_name_unique` (`name`),
  KEY `email_templates_stage_index` (`followup_stage`),
  KEY `email_templates_active_index` (`active`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_templates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `email_templates` WRITE;
/*!40000 ALTER TABLE `email_templates` DISABLE KEYS */;
INSERT INTO `email_templates` VALUES
(1,'Initial outreach - skincare/cosmetic formulators','Skincare / cosmetic formulators','Peptide sourcing support for {{company_name}}','Hello {{contact_name}},\n\nI am reaching out from Atlas BioLabs. We support qualified B2B buyers with peptide sourcing coordination, MOQ flexibility, documentation support, and batch transparency.\n\nFor skincare and cosmetic formulation teams, our role is to help make sourcing conversations more organized, documented, and commercially practical.\n\nIf {{company_name}} is reviewing peptide supply options, I would be glad to share availability, documentation expectations, and sourcing next steps.\n\n{{atlas_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:31:27','2026-05-12 04:31:27'),
(2,'Initial outreach - ingredient distributors','Ingredient distributors','Documented peptide sourcing coordination','Hello {{contact_name}},\n\nAtlas BioLabs supports peptide sourcing with MOQ flexibility, documentation support, batch transparency, and supply coordination for qualified B2B buyers.\n\nFor ingredient distribution teams, we focus on clear commercial communication, supply coordination, and documentation alignment without aggressive sales claims.\n\nIf {{company_name}} is evaluating peptide supply partners, we can share sourcing details and RFQ next steps.\n\n{{atlas_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:31:27','2026-05-12 04:31:27'),
(3,'Initial outreach - supplement/private-label teams','Supplement / private-label teams','Supply coordination support for {{company_name}}','Hello {{contact_name}},\n\nI am contacting you from Atlas BioLabs regarding professional B2B peptide sourcing support.\n\nAtlas BioLabs supports MOQ flexibility, documentation support, batch transparency, and supply coordination for qualified buyers in formulation, private-label, and distribution workflows.\n\nIf useful, we can help {{company_name}} review sourcing requirements and RFQ details.\n\n{{atlas_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:31:27','2026-05-12 04:31:27'),
(4,'Initial outreach - contract manufacturers','Contract manufacturers','Peptide sourcing and documentation support','Hello {{contact_name}},\n\nAtlas BioLabs works with qualified B2B buyers that need organized peptide sourcing conversations, documentation support, MOQ flexibility, and batch transparency.\n\nFor contract manufacturing teams, we understand the importance of practical supply coordination and clear documentation before commercial decisions are made.\n\nIf {{company_name}} is reviewing peptide sourcing needs, I can share next steps for RFQs and documentation requests.\n\n{{atlas_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:31:27','2026-05-12 04:31:27'),
(5,'Initial outreach - research supply buyers','Research supply buyers','Peptide sourcing support for research supply teams','Hello {{contact_name}},\n\nAtlas BioLabs supports qualified B2B research supply buyers with peptide sourcing coordination, MOQ flexibility, documentation support, and batch transparency.\n\nOur communication is focused on professional supply requirements, documentation expectations, and RFQ coordination.\n\nIf {{company_name}} is evaluating sourcing options, I would be glad to share availability and next steps.\n\n{{atlas_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',0,1,'2026-05-12 04:31:27','2026-05-12 04:31:27'),
(6,'Follow-up 1','Other','Following up from Atlas BioLabs','Hello {{contact_name}},\n\nI wanted to follow up on my previous note from Atlas BioLabs.\n\nWe support peptide sourcing with MOQ flexibility, documentation support, batch transparency, and supply coordination for qualified B2B buyers.\n\nIf this is relevant for {{company_name}}, I can share RFQ next steps or documentation expectations.\n\n{{atlas_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',1,1,'2026-05-12 04:31:27','2026-05-12 04:31:27'),
(7,'Follow-up 2','Other','Closing the loop from Atlas BioLabs','Hello {{contact_name}},\n\nI am closing the loop on my previous outreach.\n\nIf peptide sourcing support, documentation alignment, MOQ flexibility, or batch transparency becomes relevant for {{company_name}}, Atlas BioLabs would be glad to help coordinate the conversation.\n\n{{atlas_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',2,1,'2026-05-12 04:31:27','2026-05-12 04:31:27'),
(8,'RFQ confirmation email','Other','We received your Atlas BioLabs RFQ','Hello {{contact_name}},\n\nThank you for contacting Atlas BioLabs. We received your RFQ details and will review the request for sourcing fit, documentation expectations, MOQ support, and supply coordination next steps.\n\nOur team will follow up using the contact details provided.\n\n{{atlas_signature}}',9,1,'2026-05-12 04:31:27','2026-05-12 04:31:27');
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
  UNIQUE KEY `leads_email_unique` (`email`),
  UNIQUE KEY `leads_unsubscribe_token_unique` (`unsubscribe_token`),
  KEY `leads_status_index` (`status`),
  KEY `leads_next_followup_index` (`next_followup_at`),
  KEY `leads_category_index` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES
(2,'One Market','Fomenky Jason','onemarketdev@gmail.com','671353758','onemarketc.com','Cameroon','Skincare / cosmetic formulators','We','queued','ddsfsdfsdfds',NULL,NULL,0,0,0,'32aaa182700ffb9401402889aa164e757806fda649a8a6b8cc76106ee13af33b','2026-05-12 04:39:47','2026-05-12 04:39:56'),
(4,'One Market','Fomenky','onemarketd@gmail.com','650673248','https://onemarketc.com','Cameroon','Aesthetic / beauty product developers','We','queued','jhgkjhmnngfh',NULL,NULL,0,0,0,'6e714a368b4f35fc526eb5ecd66200beea59cd6aea72b9c9501b18193f349847','2026-05-12 04:41:50','2026-05-12 04:43:07');
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
  `setting_key` varchar(120) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
(1,'daily_send_limit','30','2026-05-12 04:31:27','2026-05-12 04:31:27'),
(2,'admin_notification_email','admin@example.com','2026-05-12 04:31:27','2026-05-12 04:31:27'),
(3,'sender_name','Atlas BioLabs','2026-05-12 04:31:27','2026-05-12 04:31:27'),
(4,'sender_email','no-reply@example.com','2026-05-12 04:31:27','2026-05-12 04:31:27'),
(5,'business_address','Business address placeholder','2026-05-12 04:31:27','2026-05-12 04:31:27'),
(6,'email_provider','log','2026-05-12 04:31:27','2026-05-12 04:31:27'),
(7,'followup_1_days','3','2026-05-12 04:31:27','2026-05-12 04:31:27'),
(8,'followup_2_days','7','2026-05-12 04:31:27','2026-05-12 04:31:27');
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

-- Dump completed on 2026-05-12  4:45:55
