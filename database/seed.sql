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

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `business_profiles` WRITE;
/*!40000 ALTER TABLE `business_profiles` DISABLE KEYS */;
INSERT IGNORE INTO `business_profiles` VALUES
(1,'Atlas BioLabs','Atlas BioLabs','Peptide sourcing support for qualified B2B buyers','Peptide supply and sourcing','Atlas BioLabs','no-reply@example.com','','admin@example.com','Business address placeholder','','','#0A1A2F','#FFFFFF','#2E6BFF','You are receiving this professional B2B email from Atlas BioLabs. This message is intended for qualified business sourcing conversations. No medical or human-use claims are made. You can unsubscribe using the link included in this email.','Atlas BioLabs\nPeptide sourcing support, MOQ flexibility, documentation support, batch transparency, and supply coordination.\nBusiness address placeholder',30,'Africa/Douala',1,'2026-05-12 04:53:36','2026-05-12 04:53:36');
/*!40000 ALTER TABLE `business_profiles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

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

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT IGNORE INTO `settings` VALUES
(1,1,'daily_send_limit','30','2026-05-12 04:31:27','2026-05-12 06:58:38'),
(2,1,'admin_notification_email','admin@example.com','2026-05-12 04:31:27','2026-05-12 04:53:36'),
(3,1,'sender_name','Atlas BioLabs','2026-05-12 04:31:27','2026-05-12 04:53:36'),
(4,1,'sender_email','no-reply@example.com','2026-05-12 04:31:27','2026-05-12 04:53:36'),
(5,1,'business_address','Business address placeholder','2026-05-12 04:31:27','2026-05-12 06:58:38'),
(6,1,'email_provider','log','2026-05-12 04:31:27','2026-05-12 04:53:36'),
(7,1,'followup_1_days','3','2026-05-12 04:31:27','2026-05-12 06:58:38'),
(8,1,'followup_2_days','7','2026-05-12 04:31:27','2026-05-12 06:58:38'),
(9,1,'lead_categories','Skincare / cosmetic formulators\nIngredient distributors\nSupplement / private-label teams\nContract manufacturers\nResearch supply buyers\nAesthetic / beauty product developers\nOther','2026-05-12 04:56:04','2026-05-12 04:56:04'),
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
(26,1,'UNSUBSCRIBE_FOOTER_TEXT','You can unsubscribe using the link included in this email.','2026-05-12 06:58:38','2026-05-12 06:58:38');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `email_templates` WRITE;
/*!40000 ALTER TABLE `email_templates` DISABLE KEYS */;
INSERT IGNORE INTO `email_templates` VALUES
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
(16,1,'Generic - Re-engagement email','Other','Checking in from {{brand_name}}','Hello {{contact_name}},\n\nI am checking in from {{brand_name}} in case this is a better time to reconnect.\n\nIf {{company_name}} is reviewing options in {{industry}}, we can share current capabilities and next steps.\n\n{{default_signature}}\n\nUnsubscribe: {{unsubscribe_link}}',4,1,'2026-05-12 04:55:00','2026-05-12 04:55:00');
/*!40000 ALTER TABLE `email_templates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `saved_segments` WRITE;
/*!40000 ALTER TABLE `saved_segments` DISABLE KEYS */;
INSERT INTO `saved_segments` (`business_profile_id`, `name`, `filter_rules`, `created_at`, `updated_at`) VALUES
(1, 'US skincare formulators', '{"country":"US","category":"Skincare / cosmetic formulators"}', NOW(), NOW()),
(1, 'Ingredient distributors', '{"category":"Ingredient distributors"}', NOW(), NOW()),
(1, 'Contract manufacturers', '{"category":"Contract manufacturers"}', NOW(), NOW()),
(1, 'Research buyers', '{"category":"Research supply buyers"}', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `filter_rules` = VALUES(`filter_rules`),
  `updated_at` = NOW();
/*!40000 ALTER TABLE `saved_segments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

