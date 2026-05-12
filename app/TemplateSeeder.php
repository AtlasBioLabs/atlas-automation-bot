<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class TemplateSeeder
{
    public static function seedGeneric(int $businessProfileId): void
    {
        $templates = [
            ['Generic - Initial B2B outreach', 'Professional B2B introduction from {{brand_name}}', 0, 'Hello {{contact_name}},

I am reaching out from {{brand_name}}. {{tagline}}

We support professional {{industry}} conversations with clear coordination, practical next steps, and transparent business communication.

If this is relevant for {{company_name}}, I would be glad to share more details.

{{default_signature}}

Unsubscribe: {{unsubscribe_link}}'],
            ['Generic - Service business outreach', 'Service support for {{company_name}}', 0, 'Hello {{contact_name}},

I am contacting you from {{brand_name}} regarding professional service support for businesses in {{industry}}.

If {{company_name}} is reviewing outside support or vendor options, we can share capabilities, next steps, and fit criteria.

{{default_signature}}

Unsubscribe: {{unsubscribe_link}}'],
            ['Generic - Product supplier outreach', 'Supplier coordination from {{brand_name}}', 0, 'Hello {{contact_name}},

{{brand_name}} supports B2B product and supply conversations with clear documentation, practical coordination, and professional follow-up.

If {{company_name}} is reviewing supplier options, we can share availability, commercial details, and next steps.

{{default_signature}}

Unsubscribe: {{unsubscribe_link}}'],
            ['Generic - Follow-up 1', 'Following up from {{brand_name}}', 1, 'Hello {{contact_name}},

I wanted to follow up on my previous note from {{brand_name}}.

If this is relevant for {{company_name}}, I can share more details or route the conversation to the right next step.

{{default_signature}}

Unsubscribe: {{unsubscribe_link}}'],
            ['Generic - Follow-up 2', 'Closing the loop from {{brand_name}}', 2, 'Hello {{contact_name}},

I am closing the loop on my previous outreach.

If this becomes relevant for {{company_name}}, {{brand_name}} would be glad to help with a professional B2B conversation.

{{default_signature}}

Unsubscribe: {{unsubscribe_link}}'],
            ['Generic - RFQ confirmation', 'We received your request for {{brand_name}}', 9, 'Hello {{contact_name}},

Thank you for contacting {{brand_name}}. We received your request and will review the details before following up.

{{default_signature}}'],
            ['Generic - Quote follow-up', 'Following up on your {{brand_name}} quote', 3, 'Hello {{contact_name}},

I wanted to follow up on the quote conversation with {{brand_name}}.

If you have questions about scope, timing, or next steps, we would be glad to help.

{{default_signature}}

Unsubscribe: {{unsubscribe_link}}'],
            ['Generic - Re-engagement email', 'Checking in from {{brand_name}}', 4, 'Hello {{contact_name}},

I am checking in from {{brand_name}} in case this is a better time to reconnect.

If {{company_name}} is reviewing options in {{industry}}, we can share current capabilities and next steps.

{{default_signature}}

Unsubscribe: {{unsubscribe_link}}'],
        ];

        $stmt = Database::pdo()->prepare(
            'INSERT INTO email_templates (business_profile_id, name, category, subject, body, followup_stage, active, created_at, updated_at)
             VALUES (?, ?, "Other", ?, ?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE updated_at = NOW()'
        );
        foreach ($templates as [$name, $subject, $stage, $body]) {
            $stmt->execute([$businessProfileId, $name, $subject, $body, $stage]);
        }
    }
}
