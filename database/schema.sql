/*M!999999\- enable the sandbox mode */ 

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `timezone` varchar(100) NOT NULL DEFAULT 'Africa/Douala',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `business_profiles_active_index` (`active`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaigns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` int(10) unsigned NOT NULL,
  `name` varchar(190) NOT NULL,
  `description` text DEFAULT NULL,
  `template_id` int(10) unsigned NOT NULL,
  `audience_type` varchar(30) NOT NULL,
  `filter_rules` mediumtext DEFAULT NULL,
  `total_recipients` int(10) unsigned NOT NULL DEFAULT 0,
  `eligible_recipients` int(10) unsigned NOT NULL DEFAULT 0,
  `skipped_recipients` int(10) unsigned NOT NULL DEFAULT 0,
  `skipped_reasons` mediumtext DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'queued',
  `scheduled_at` datetime NOT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `campaigns_business_created_index` (`business_profile_id`,`created_at`),
  KEY `campaigns_template_index` (`template_id`),
  KEY `campaigns_status_index` (`business_profile_id`,`status`),
  KEY `campaigns_created_by_fk` (`created_by`),
  CONSTRAINT `campaigns_business_fk` FOREIGN KEY (`business_profile_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campaigns_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `campaigns_template_fk` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `provider_reference` varchar(255) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  KEY `email_queue_campaign_status_index` (`business_profile_id`,`campaign_id`,`status`),
  CONSTRAINT `email_queue_lead_fk` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_queue_template_fk` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rfqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rfqs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` int(10) unsigned NOT NULL,
  `source` varchar(120) NOT NULL DEFAULT 'website_rfq',
  `lead_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(190) NOT NULL,
  `company` varchar(190) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(80) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `product_interest` varchar(255) NOT NULL,
  `estimated_quantity` varchar(120) DEFAULT NULL,
  `timeline` varchar(190) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `items_json` mediumtext DEFAULT NULL,
  `page_url` varchar(500) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `rfqs_created_index` (`created_at`),
  KEY `rfqs_lead_index` (`lead_id`),
  KEY `rfqs_business_created_index` (`business_profile_id`,`created_at`),
  KEY `rfqs_source_index` (`business_profile_id`,`source`,`created_at`),
  CONSTRAINT `rfqs_lead_fk` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saved_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `saved_segments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` int(10) unsigned NOT NULL,
  `name` varchar(190) NOT NULL,
  `filter_rules` mediumtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `saved_segments_business_name_unique` (`business_profile_id`,`name`),
  KEY `saved_segments_business_index` (`business_profile_id`),
  CONSTRAINT `saved_segments_business_fk` FOREIGN KEY (`business_profile_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

