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

ALTER TABLE leads ADD COLUMN IF NOT EXISTS business_profile_id INT UNSIGNED NULL AFTER id;
ALTER TABLE email_templates ADD COLUMN IF NOT EXISTS business_profile_id INT UNSIGNED NULL AFTER id;
ALTER TABLE email_queue ADD COLUMN IF NOT EXISTS business_profile_id INT UNSIGNED NULL AFTER id;
ALTER TABLE email_logs ADD COLUMN IF NOT EXISTS business_profile_id INT UNSIGNED NULL AFTER id;
ALTER TABLE rfqs ADD COLUMN IF NOT EXISTS business_profile_id INT UNSIGNED NULL AFTER id;
ALTER TABLE settings ADD COLUMN IF NOT EXISTS business_profile_id INT UNSIGNED NULL AFTER id;

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

ALTER TABLE leads DROP INDEX IF EXISTS leads_email_unique;
ALTER TABLE email_templates DROP INDEX IF EXISTS email_templates_name_unique;
ALTER TABLE settings DROP INDEX IF EXISTS settings_key_unique;

ALTER TABLE leads ADD UNIQUE KEY IF NOT EXISTS leads_business_email_unique (business_profile_id, email);
ALTER TABLE email_templates ADD UNIQUE KEY IF NOT EXISTS email_templates_business_name_unique (business_profile_id, name);
ALTER TABLE settings ADD UNIQUE KEY IF NOT EXISTS settings_business_key_unique (business_profile_id, setting_key);

ALTER TABLE leads ADD KEY IF NOT EXISTS leads_business_status_index (business_profile_id, status);
ALTER TABLE email_templates ADD KEY IF NOT EXISTS email_templates_business_stage_index (business_profile_id, followup_stage);
ALTER TABLE email_queue ADD KEY IF NOT EXISTS email_queue_business_status_index (business_profile_id, status, scheduled_at);
ALTER TABLE email_logs ADD KEY IF NOT EXISTS email_logs_business_status_index (business_profile_id, status, created_at);
ALTER TABLE rfqs ADD KEY IF NOT EXISTS rfqs_business_created_index (business_profile_id, created_at);

UPDATE email_templates
SET body = REPLACE(body, '{{atlas_signature}}', '{{default_signature}}')
WHERE business_profile_id = 1;
