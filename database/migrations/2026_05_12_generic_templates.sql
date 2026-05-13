UPDATE email_templates
SET
  subject = REPLACE(subject, 'Atlas BioLabs', '{{brand_name}}'),
  body = REPLACE(
    REPLACE(
      REPLACE(
        REPLACE(body, 'Atlas BioLabs', '{{brand_name}}'),
        'Atlas BioLabs supports peptide sourcing with MOQ flexibility, documentation support, batch transparency, and supply coordination for qualified B2B buyers.',
        '{{brand_name}} supports {{industry}} conversations with clear documentation, practical coordination, and professional B2B communication.'
      ),
      'Atlas BioLabs would be glad',
      '{{brand_name}} would be glad'
    ),
    '{{atlas_signature}}',
    '{{default_signature}}'
  )
WHERE business_profile_id = 1;

INSERT INTO email_templates (business_profile_id, name, category, subject, body, followup_stage, active, created_at, updated_at) VALUES
(1, 'Generic - Initial B2B outreach', 'Other', 'Professional B2B introduction from {{brand_name}}', 'Hello {{contact_name}},

I am reaching out from {{brand_name}}. {{tagline}}

We support professional {{industry}} conversations with clear coordination, practical next steps, and transparent business communication.

If this is relevant for {{company_name}}, I would be glad to share more details.

{{default_signature}}', 0, 1, NOW(), NOW()),
(1, 'Generic - Service business outreach', 'Other', 'Service support for {{company_name}}', 'Hello {{contact_name}},

I am contacting you from {{brand_name}} regarding professional service support for businesses in {{industry}}.

If {{company_name}} is reviewing outside support or vendor options, we can share capabilities, next steps, and fit criteria.

{{default_signature}}', 0, 1, NOW(), NOW()),
(1, 'Generic - Product supplier outreach', 'Other', 'Supplier coordination from {{brand_name}}', 'Hello {{contact_name}},

{{brand_name}} supports B2B product and supply conversations with clear documentation, practical coordination, and professional follow-up.

If {{company_name}} is reviewing supplier options, we can share availability, commercial details, and next steps.

{{default_signature}}', 0, 1, NOW(), NOW()),
(1, 'Generic - Follow-up 1', 'Other', 'Following up from {{brand_name}}', 'Hello {{contact_name}},

I wanted to follow up on my previous note from {{brand_name}}.

If this is relevant for {{company_name}}, I can share more details or route the conversation to the right next step.

{{default_signature}}', 1, 1, NOW(), NOW()),
(1, 'Generic - Follow-up 2', 'Other', 'Closing the loop from {{brand_name}}', 'Hello {{contact_name}},

I am closing the loop on my previous outreach.

If this becomes relevant for {{company_name}}, {{brand_name}} would be glad to help with a professional B2B conversation.

{{default_signature}}', 2, 1, NOW(), NOW()),
(1, 'Generic - RFQ confirmation', 'Other', 'We received your request for {{brand_name}}', 'Hello {{contact_name}},

Thank you for contacting {{brand_name}}. We received your request and will review the details before following up.

{{default_signature}}', 9, 1, NOW(), NOW()),
(1, 'Generic - Quote follow-up', 'Other', 'Following up on your {{brand_name}} quote', 'Hello {{contact_name}},

I wanted to follow up on the quote conversation with {{brand_name}}.

If you have questions about scope, timing, or next steps, we would be glad to help.

{{default_signature}}', 3, 1, NOW(), NOW()),
(1, 'Generic - Re-engagement email', 'Other', 'Checking in from {{brand_name}}', 'Hello {{contact_name}},

I am checking in from {{brand_name}} in case this is a better time to reconnect.

If {{company_name}} is reviewing options in {{industry}}, we can share current capabilities and next steps.

{{default_signature}}', 4, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE body = VALUES(body), subject = VALUES(subject), updated_at = NOW();
