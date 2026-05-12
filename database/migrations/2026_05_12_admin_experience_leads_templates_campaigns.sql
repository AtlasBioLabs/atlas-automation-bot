ALTER TABLE leads ADD COLUMN archived_at DATETIME NULL AFTER unsubscribed;
ALTER TABLE leads ADD COLUMN deleted_at DATETIME NULL AFTER archived_at;
ALTER TABLE leads ADD INDEX leads_archived_at_index (archived_at);
ALTER TABLE leads ADD INDEX leads_deleted_at_index (deleted_at);
ALTER TABLE leads ADD INDEX leads_business_archived_index (business_profile_id, archived_at, deleted_at);

ALTER TABLE email_templates ADD COLUMN preheader VARCHAR(255) NULL AFTER subject;
ALTER TABLE email_templates ADD COLUMN body_html MEDIUMTEXT NULL AFTER body;
ALTER TABLE email_templates ADD COLUMN body_text MEDIUMTEXT NULL AFTER body_html;

UPDATE email_templates
SET body_text = body
WHERE (body_text IS NULL OR body_text = '') AND body IS NOT NULL;

ALTER TABLE email_queue ADD COLUMN rendered_subject VARCHAR(255) NULL AFTER template_id;
ALTER TABLE email_queue ADD COLUMN rendered_preheader VARCHAR(255) NULL AFTER rendered_subject;
ALTER TABLE email_queue ADD COLUMN rendered_html MEDIUMTEXT NULL AFTER rendered_preheader;
ALTER TABLE email_queue ADD COLUMN rendered_text MEDIUMTEXT NULL AFTER rendered_html;
ALTER TABLE email_queue ADD COLUMN rendered_variables LONGTEXT NULL AFTER rendered_text;

CREATE TABLE IF NOT EXISTS campaign_skips (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_profile_id INT UNSIGNED NOT NULL,
    campaign_id INT UNSIGNED NOT NULL,
    lead_id INT UNSIGNED NULL,
    lead_company_name VARCHAR(190) NULL,
    lead_contact_name VARCHAR(190) NULL,
    lead_email VARCHAR(190) NULL,
    reason VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY campaign_skips_campaign_index (campaign_id),
    KEY campaign_skips_business_reason_index (business_profile_id, reason),
    CONSTRAINT campaign_skips_business_fk FOREIGN KEY (business_profile_id) REFERENCES business_profiles (id) ON DELETE CASCADE,
    CONSTRAINT campaign_skips_campaign_fk FOREIGN KEY (campaign_id) REFERENCES campaigns (id) ON DELETE CASCADE,
    CONSTRAINT campaign_skips_lead_fk FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
