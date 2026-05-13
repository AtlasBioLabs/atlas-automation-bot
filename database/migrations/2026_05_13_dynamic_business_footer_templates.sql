UPDATE email_templates
SET subject = REPLACE(subject, 'Atlas BioLabs', '{{brand_name}}'),
    preheader = REPLACE(preheader, 'Atlas BioLabs', '{{brand_name}}'),
    body = TRIM(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(body, 'Atlas BioLabs', '{{brand_name}}'),
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
                                    REPLACE(body_html, 'Atlas BioLabs', '{{brand_name}}'),
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
                                    REPLACE(body_text, 'Atlas BioLabs', '{{brand_name}}'),
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
    OR preheader LIKE '%Atlas BioLabs%'
    OR body LIKE '%Atlas BioLabs%'
    OR body_html LIKE '%Atlas BioLabs%'
    OR body_text LIKE '%Atlas BioLabs%'
    OR body LIKE '%Unsubscribe: {{unsubscribe_link}}%'
    OR body_text LIKE '%Unsubscribe: {{unsubscribe_link}}%'
    OR body LIKE '%Business address placeholder%'
    OR body_text LIKE '%Business address placeholder%'
    OR body_html LIKE '%Business address placeholder%'
    OR body LIKE '%hello@atlasbiolabs.co%'
    OR body_text LIKE '%hello@atlasbiolabs.co%'
    OR body_html LIKE '%hello@atlasbiolabs.co%'
  );
