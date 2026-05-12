INSERT INTO settings (business_profile_id, setting_key, setting_value, created_at, updated_at) VALUES
(1, 'APP_NAME', 'Atlas BioLabs', NOW(), NOW()),
(1, 'BUSINESS_NAME', 'Atlas BioLabs', NOW(), NOW()),
(1, 'BUSINESS_TAGLINE', 'Peptide sourcing support for qualified B2B buyers', NOW(), NOW()),
(1, 'MAIL_PROVIDER', 'log', NOW(), NOW()),
(1, 'MAIL_FROM_NAME', 'Atlas BioLabs', NOW(), NOW()),
(1, 'MAIL_FROM_EMAIL', 'no-reply@example.com', NOW(), NOW()),
(1, 'MAIL_REPLY_TO', '', NOW(), NOW()),
(1, 'MAIL_SMTP_HOST', '', NOW(), NOW()),
(1, 'MAIL_SMTP_PORT', '587', NOW(), NOW()),
(1, 'MAIL_SMTP_USER', '', NOW(), NOW()),
(1, 'ADMIN_EMAIL', 'admin@example.com', NOW(), NOW()),
(1, 'BUSINESS_ADDRESS', 'Business address placeholder', NOW(), NOW()),
(1, 'DAILY_SEND_LIMIT', '30', NOW(), NOW()),
(1, 'FOLLOWUP_1_DAYS', '3', NOW(), NOW()),
(1, 'FOLLOWUP_2_DAYS', '7', NOW(), NOW()),
(1, 'DEFAULT_SIGNATURE', 'Atlas BioLabs
Peptide sourcing support, MOQ flexibility, documentation support, batch transparency, and supply coordination.
Business address placeholder', NOW(), NOW()),
(1, 'COMPLIANCE_FOOTER', 'You are receiving this professional B2B email from Atlas BioLabs. This message is intended for qualified business sourcing conversations. No medical or human-use claims are made. You can unsubscribe using the link included in this email.', NOW(), NOW()),
(1, 'UNSUBSCRIBE_FOOTER_TEXT', 'You can unsubscribe using the link included in this email.', NOW(), NOW())
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW();
