CREATE TABLE IF NOT EXISTS email_campaigns (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_profile_id INT UNSIGNED NOT NULL,
  campaign_name VARCHAR(190) NOT NULL,
  template_id INT UNSIGNED NOT NULL,
  send_type VARCHAR(20) NOT NULL,
  scheduled_at DATETIME NOT NULL,
  selected_lead_ids MEDIUMTEXT NULL,
  filter_rules MEDIUMTEXT NULL,
  preview_count INT UNSIGNED NOT NULL DEFAULT 0,
  skipped_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY email_campaigns_business_created_index (business_profile_id, created_at),
  KEY email_campaigns_template_index (template_id),
  CONSTRAINT email_campaigns_business_fk FOREIGN KEY (business_profile_id) REFERENCES business_profiles (id) ON DELETE CASCADE,
  CONSTRAINT email_campaigns_template_fk FOREIGN KEY (template_id) REFERENCES email_templates (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE email_queue ADD COLUMN IF NOT EXISTS campaign_id INT UNSIGNED NULL AFTER business_profile_id;
ALTER TABLE email_queue ADD COLUMN IF NOT EXISTS campaign_name VARCHAR(190) NULL AFTER campaign_id;
ALTER TABLE email_queue ADD KEY IF NOT EXISTS email_queue_campaign_index (campaign_id);
