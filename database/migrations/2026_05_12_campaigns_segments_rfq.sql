CREATE TABLE IF NOT EXISTS campaigns (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_profile_id INT UNSIGNED NOT NULL,
  name VARCHAR(190) NOT NULL,
  description TEXT NULL,
  template_id INT UNSIGNED NOT NULL,
  audience_type VARCHAR(30) NOT NULL,
  filter_rules MEDIUMTEXT NULL,
  total_recipients INT UNSIGNED NOT NULL DEFAULT 0,
  eligible_recipients INT UNSIGNED NOT NULL DEFAULT 0,
  skipped_recipients INT UNSIGNED NOT NULL DEFAULT 0,
  skipped_reasons MEDIUMTEXT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'queued',
  scheduled_at DATETIME NOT NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY campaigns_business_created_index (business_profile_id, created_at),
  KEY campaigns_template_index (template_id),
  KEY campaigns_status_index (business_profile_id, status),
  CONSTRAINT campaigns_business_fk FOREIGN KEY (business_profile_id) REFERENCES business_profiles (id) ON DELETE CASCADE,
  CONSTRAINT campaigns_template_fk FOREIGN KEY (template_id) REFERENCES email_templates (id) ON DELETE RESTRICT,
  CONSTRAINT campaigns_created_by_fk FOREIGN KEY (created_by) REFERENCES admins (id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS saved_segments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_profile_id INT UNSIGNED NOT NULL,
  name VARCHAR(190) NOT NULL,
  filter_rules MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY saved_segments_business_name_unique (business_profile_id, name),
  KEY saved_segments_business_index (business_profile_id),
  CONSTRAINT saved_segments_business_fk FOREIGN KEY (business_profile_id) REFERENCES business_profiles (id) ON DELETE CASCADE
);

ALTER TABLE rfqs ADD COLUMN IF NOT EXISTS source VARCHAR(120) NOT NULL DEFAULT 'website_rfq' AFTER business_profile_id;
ALTER TABLE rfqs ADD KEY IF NOT EXISTS rfqs_source_index (business_profile_id, source, created_at);
ALTER TABLE email_queue ADD KEY IF NOT EXISTS email_queue_campaign_status_index (business_profile_id, campaign_id, status);

INSERT INTO campaigns (
  id,
  business_profile_id,
  name,
  description,
  template_id,
  audience_type,
  filter_rules,
  total_recipients,
  eligible_recipients,
  skipped_recipients,
  skipped_reasons,
  status,
  scheduled_at,
  created_by,
  created_at,
  updated_at
)
SELECT
  id,
  business_profile_id,
  campaign_name,
  'Migrated from the original MVP campaign queue table.',
  template_id,
  send_type,
  filter_rules,
  preview_count + skipped_count,
  preview_count,
  skipped_count,
  NULL,
  'queued',
  scheduled_at,
  NULL,
  created_at,
  updated_at
FROM email_campaigns
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  updated_at = VALUES(updated_at);

INSERT INTO saved_segments (business_profile_id, name, filter_rules, created_at, updated_at)
VALUES
(1, 'US skincare formulators', '{"country":"US","category":"Skincare / cosmetic formulators"}', NOW(), NOW()),
(1, 'Ingredient distributors', '{"category":"Ingredient distributors"}', NOW(), NOW()),
(1, 'Contract manufacturers', '{"category":"Contract manufacturers"}', NOW(), NOW()),
(1, 'Research buyers', '{"category":"Research supply buyers"}', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  filter_rules = VALUES(filter_rules),
  updated_at = NOW();
