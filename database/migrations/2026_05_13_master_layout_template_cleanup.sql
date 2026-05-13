UPDATE email_templates
SET body = TRIM(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(body, '{{default_signature}}', ''),
                    'Reply to: {{reply_to_email}}',
                    ''
                ),
                CONCAT(CHAR(10), CHAR(10), 'Unsubscribe: {{unsubscribe_link}}'),
                ''
            ),
            'Unsubscribe: {{unsubscribe_link}}',
            ''
        )
    ),
    body_text = TRIM(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(body_text, '{{default_signature}}', ''),
                    'Reply to: {{reply_to_email}}',
                    ''
                ),
                CONCAT(CHAR(10), CHAR(10), 'Unsubscribe: {{unsubscribe_link}}'),
                ''
            ),
            'Unsubscribe: {{unsubscribe_link}}',
            ''
        )
    ),
    updated_at = NOW()
WHERE business_profile_id = 1;

UPDATE settings
SET setting_value = 'You can unsubscribe using the link included in this email.',
    updated_at = NOW()
WHERE business_profile_id = 1
  AND setting_key = 'UNSUBSCRIBE_FOOTER_TEXT'
  AND TRIM(setting_value) = '';
