UPDATE business_profiles
SET business_name = 'Atlas BioLabs',
    brand_name = 'Atlas BioLabs',
    tagline = 'Precision Research Compounds',
    sender_name = 'Atlas BioLabs',
    sender_email = 'hello@atlasbiolabs.co',
    reply_to_email = 'hello@atlasbiolabs.co',
    admin_notification_email = 'hello@atlasbiolabs.co',
    business_address = 'Atlas BioLabs, United States',
    website_url = 'https://www.atlasbiolabs.co',
    company_profile_url = 'https://www.atlasbiolabs.co/about',
    compliance_footer = 'You are receiving this professional B2B email from Atlas BioLabs because your company may be relevant to sourcing, documentation, MOQ, or qualified supply conversations. No medical or human-use claims are made.',
    default_signature = 'Atlas BioLabs
Precision Research Compounds
hello@atlasbiolabs.co
https://www.atlasbiolabs.co',
    updated_at = NOW()
WHERE id = 1;

INSERT INTO settings (business_profile_id, setting_key, setting_value, created_at, updated_at)
VALUES
  (1, 'MAIL_FROM_NAME', 'Atlas BioLabs', NOW(), NOW()),
  (1, 'MAIL_FROM_EMAIL', 'hello@atlasbiolabs.co', NOW(), NOW()),
  (1, 'MAIL_REPLY_TO', 'hello@atlasbiolabs.co', NOW(), NOW()),
  (1, 'ADMIN_EMAIL', 'hello@atlasbiolabs.co', NOW(), NOW()),
  (1, 'BUSINESS_ADDRESS', 'Atlas BioLabs, United States', NOW(), NOW()),
  (1, 'WEBSITE_URL', 'https://www.atlasbiolabs.co', NOW(), NOW()),
  (1, 'COMPANY_PROFILE_URL', 'https://www.atlasbiolabs.co/about', NOW(), NOW()),
  (1, 'BUSINESS_TAGLINE', 'Precision Research Compounds', NOW(), NOW()),
  (1, 'DEFAULT_SIGNATURE', 'Atlas BioLabs
Precision Research Compounds
hello@atlasbiolabs.co
https://www.atlasbiolabs.co', NOW(), NOW()),
  (1, 'COMPLIANCE_FOOTER', 'You are receiving this professional B2B email from Atlas BioLabs because your company may be relevant to sourcing, documentation, MOQ, or qualified supply conversations. No medical or human-use claims are made.', NOW(), NOW()),
  (1, 'UNSUBSCRIBE_FOOTER_TEXT', 'You can unsubscribe here:', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  setting_value = VALUES(setting_value),
  updated_at = NOW();

UPDATE email_templates
SET subject = REPLACE(REPLACE(subject, 'Atlas BioLabs', '{{brand_name}}'), 'Atlas%20BioLabs', '{{brand_name}}'),
    preheader = REPLACE(REPLACE(preheader, 'Atlas BioLabs', '{{brand_name}}'), 'Atlas%20BioLabs', '{{brand_name}}'),
    body = TRIM(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(REPLACE(body, 'Atlas BioLabs', '{{brand_name}}'), 'Atlas%20BioLabs', '{{brand_name}}'),
                                    'Precision Research Compounds',
                                    '{{business_tagline}}'
                                ),
                                'hello@atlasbiolabs.co',
                                '{{reply_to_email}}'
                            ),
                            'no-reply@example.com',
                            '{{reply_to_email}}'
                        ),
                        'https://www.atlasbiolabs.co/about',
                        '{{company_profile_url}}'
                    ),
                    'https://www.atlasbiolabs.co',
                    '{{website_url}}'
                ),
                'Business address placeholder',
                '{{business_address}}'
            ),
            CONCAT(CHAR(10), CHAR(10), 'Unsubscribe: {{unsubscribe_link}}'),
            ''
        )
    ),
    body_html = TRIM(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(REPLACE(body_html, 'Atlas BioLabs', '{{brand_name}}'), 'Atlas%20BioLabs', '{{brand_name}}'),
                                    'Precision Research Compounds',
                                    '{{business_tagline}}'
                                ),
                                'hello@atlasbiolabs.co',
                                '{{reply_to_email}}'
                            ),
                            'no-reply@example.com',
                            '{{reply_to_email}}'
                        ),
                        'https://www.atlasbiolabs.co/about',
                        '{{company_profile_url}}'
                    ),
                    'https://www.atlasbiolabs.co',
                    '{{website_url}}'
                ),
                'Business address placeholder',
                '{{business_address}}'
            ),
            'View company profile',
            'view our company profile'
        )
    ),
    body_text = TRIM(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(REPLACE(body_text, 'Atlas BioLabs', '{{brand_name}}'), 'Atlas%20BioLabs', '{{brand_name}}'),
                                    'Precision Research Compounds',
                                    '{{business_tagline}}'
                                ),
                                'hello@atlasbiolabs.co',
                                '{{reply_to_email}}'
                            ),
                            'no-reply@example.com',
                            '{{reply_to_email}}'
                        ),
                        'https://www.atlasbiolabs.co/about',
                        '{{company_profile_url}}'
                    ),
                    'https://www.atlasbiolabs.co',
                    '{{website_url}}'
                ),
                'Business address placeholder',
                '{{business_address}}'
            ),
            CONCAT(CHAR(10), CHAR(10), 'Unsubscribe: {{unsubscribe_link}}'),
            ''
        )
    ),
    updated_at = NOW()
WHERE business_profile_id = 1
  AND (
    subject LIKE '%Atlas BioLabs%'
    OR subject LIKE '%Atlas%20BioLabs%'
    OR preheader LIKE '%Atlas BioLabs%'
    OR preheader LIKE '%Atlas%20BioLabs%'
    OR body LIKE '%Atlas BioLabs%'
    OR body LIKE '%Atlas%20BioLabs%'
    OR body_html LIKE '%Atlas BioLabs%'
    OR body_html LIKE '%Atlas%20BioLabs%'
    OR body_text LIKE '%Atlas BioLabs%'
    OR body_text LIKE '%Atlas%20BioLabs%'
    OR body LIKE '%Unsubscribe: {{unsubscribe_link}}%'
    OR body_text LIKE '%Unsubscribe: {{unsubscribe_link}}%'
    OR body LIKE '%Business address placeholder%'
    OR body_text LIKE '%Business address placeholder%'
    OR body_html LIKE '%Business address placeholder%'
    OR body LIKE '%hello@atlasbiolabs.co%'
    OR body_text LIKE '%hello@atlasbiolabs.co%'
    OR body_html LIKE '%hello@atlasbiolabs.co%'
    OR body LIKE '%no-reply@example.com%'
    OR body_text LIKE '%no-reply@example.com%'
    OR body_html LIKE '%no-reply@example.com%'
  );

UPDATE email_templates
SET body = TRIM(REPLACE(body, 'Unsubscribe: {{unsubscribe_link}}', '')),
    body_text = TRIM(REPLACE(body_text, 'Unsubscribe: {{unsubscribe_link}}', '')),
    updated_at = NOW()
WHERE business_profile_id = 1
  AND (body LIKE '%Unsubscribe: {{unsubscribe_link}}%' OR body_text LIKE '%Unsubscribe: {{unsubscribe_link}}%');
