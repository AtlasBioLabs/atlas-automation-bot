CREATE TABLE IF NOT EXISTS business_profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_name VARCHAR(190) NOT NULL,
  brand_name VARCHAR(190) NOT NULL,
  tagline VARCHAR(255) NULL,
  industry VARCHAR(120) NULL,
  sender_name VARCHAR(190) NOT NULL,
  sender_email VARCHAR(190) NOT NULL,
  reply_to_email VARCHAR(190) NULL,
  admin_notification_email VARCHAR(190) NULL,
  business_address TEXT NOT NULL,
  website_url VARCHAR(255) NULL,
  logo_url VARCHAR(255) NULL,
  primary_color VARCHAR(20) NOT NULL DEFAULT '#0A1A2F',
  secondary_color VARCHAR(20) NOT NULL DEFAULT '#FFFFFF',
  accent_color VARCHAR(20) NOT NULL DEFAULT '#2E6BFF',
  compliance_footer TEXT NOT NULL,
  default_signature TEXT NOT NULL,
  daily_send_limit INT UNSIGNED NOT NULL DEFAULT 30,
  timezone VARCHAR(80) NOT NULL DEFAULT 'UTC',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY business_profiles_active_index (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO business_profiles (
  id, business_name, brand_name, tagline, industry, sender_name, sender_email, reply_to_email,
  admin_notification_email, business_address, website_url, logo_url, primary_color, secondary_color,
  accent_color, compliance_footer, default_signature, daily_send_limit, timezone, active, created_at, updated_at
) VALUES (
  1,
  'Atlas BioLabs',
  'Atlas BioLabs',
  'Peptide sourcing support for qualified B2B buyers',
  'Peptide supply and sourcing',
  'Atlas BioLabs',
  'no-reply@example.com',
  '',
  'admin@example.com',
  'Business address placeholder',
  '',
  '',
  '#0A1A2F',
  '#FFFFFF',
  '#2E6BFF',
  'You are receiving this professional B2B email from Atlas BioLabs. This message is intended for qualified business sourcing conversations. No medical or human-use claims are made. You can unsubscribe using the link included in this email.',
  'Atlas BioLabs
Peptide sourcing support, MOQ flexibility, documentation support, batch transparency, and supply coordination.
Business address placeholder',
  30,
  'UTC',
  1,
  NOW(),
  NOW()
) ON DUPLICATE KEY UPDATE updated_at = NOW();

SET @schema_name := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'leads' AND COLUMN_NAME = 'business_profile_id') = 0,
  'ALTER TABLE leads ADD COLUMN business_profile_id INT UNSIGNED NULL AFTER id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'email_templates' AND COLUMN_NAME = 'business_profile_id') = 0,
  'ALTER TABLE email_templates ADD COLUMN business_profile_id INT UNSIGNED NULL AFTER id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'email_queue' AND COLUMN_NAME = 'business_profile_id') = 0,
  'ALTER TABLE email_queue ADD COLUMN business_profile_id INT UNSIGNED NULL AFTER id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'email_logs' AND COLUMN_NAME = 'business_profile_id') = 0,
  'ALTER TABLE email_logs ADD COLUMN business_profile_id INT UNSIGNED NULL AFTER id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'rfqs' AND COLUMN_NAME = 'business_profile_id') = 0,
  'ALTER TABLE rfqs ADD COLUMN business_profile_id INT UNSIGNED NULL AFTER id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'business_profile_id') = 0,
  'ALTER TABLE settings ADD COLUMN business_profile_id INT UNSIGNED NULL AFTER id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE leads SET business_profile_id = 1 WHERE business_profile_id IS NULL;
UPDATE email_templates SET business_profile_id = 1 WHERE business_profile_id IS NULL;
UPDATE email_queue SET business_profile_id = 1 WHERE business_profile_id IS NULL;
UPDATE email_logs SET business_profile_id = 1 WHERE business_profile_id IS NULL;
UPDATE rfqs SET business_profile_id = 1 WHERE business_profile_id IS NULL;
UPDATE settings SET business_profile_id = 1 WHERE business_profile_id IS NULL;

INSERT INTO settings (business_profile_id, setting_key, setting_value, created_at, updated_at) VALUES
(1, 'lead_categories', 'Skincare / cosmetic formulators
Ingredient distributors
Supplement / private-label teams
Contract manufacturers
Research supply buyers
Aesthetic / beauty product developers
Other', NOW(), NOW())
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW();

ALTER TABLE leads MODIFY business_profile_id INT UNSIGNED NOT NULL;
ALTER TABLE email_templates MODIFY business_profile_id INT UNSIGNED NOT NULL;
ALTER TABLE email_queue MODIFY business_profile_id INT UNSIGNED NOT NULL;
ALTER TABLE email_logs MODIFY business_profile_id INT UNSIGNED NOT NULL;
ALTER TABLE rfqs MODIFY business_profile_id INT UNSIGNED NOT NULL;
ALTER TABLE settings MODIFY business_profile_id INT UNSIGNED NOT NULL;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'leads' AND INDEX_NAME = 'leads_email_unique') > 0,
  'ALTER TABLE leads DROP INDEX leads_email_unique',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'email_templates' AND INDEX_NAME = 'email_templates_name_unique') > 0,
  'ALTER TABLE email_templates DROP INDEX email_templates_name_unique',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'settings' AND INDEX_NAME = 'settings_key_unique') > 0,
  'ALTER TABLE settings DROP INDEX settings_key_unique',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'leads' AND INDEX_NAME = 'leads_business_email_unique') = 0,
  'ALTER TABLE leads ADD UNIQUE KEY leads_business_email_unique (business_profile_id, email)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'email_templates' AND INDEX_NAME = 'email_templates_business_name_unique') = 0,
  'ALTER TABLE email_templates ADD UNIQUE KEY email_templates_business_name_unique (business_profile_id, name)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'settings' AND INDEX_NAME = 'settings_business_key_unique') = 0,
  'ALTER TABLE settings ADD UNIQUE KEY settings_business_key_unique (business_profile_id, setting_key)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'leads' AND INDEX_NAME = 'leads_business_status_index') = 0,
  'ALTER TABLE leads ADD KEY leads_business_status_index (business_profile_id, status)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'email_templates' AND INDEX_NAME = 'email_templates_business_stage_index') = 0,
  'ALTER TABLE email_templates ADD KEY email_templates_business_stage_index (business_profile_id, followup_stage)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'email_queue' AND INDEX_NAME = 'email_queue_business_status_index') = 0,
  'ALTER TABLE email_queue ADD KEY email_queue_business_status_index (business_profile_id, status, scheduled_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'email_logs' AND INDEX_NAME = 'email_logs_business_status_index') = 0,
  'ALTER TABLE email_logs ADD KEY email_logs_business_status_index (business_profile_id, status, created_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'rfqs' AND INDEX_NAME = 'rfqs_business_created_index') = 0,
  'ALTER TABLE rfqs ADD KEY rfqs_business_created_index (business_profile_id, created_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE email_templates
SET body = REPLACE(body, '{{atlas_signature}}', '{{default_signature}}')
WHERE business_profile_id = 1;
