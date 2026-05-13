<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class TemplateSeeder
{
    public static function seedGeneric(int $businessProfileId): void
    {
        $templates = [
            [
                'name' => 'Generic - Initial B2B outreach',
                'subject' => 'Professional B2B introduction from {{brand_name}}',
                'preheader' => 'A concise introduction for qualified business outreach.',
                'stage' => 0,
                'text' => "Hello {{contact_name}},\n\nI am reaching out from {{brand_name}}. {{business_tagline}}\n\nWe support professional {{industry}} conversations with clear coordination, practical next steps, and transparent business communication.\n\nIf this is relevant for {{company_name}}, I would be glad to share more details.",
                'html' => '<p style="margin:0 0 16px;">Hello {{contact_name}},</p><p style="margin:0 0 16px;">I am reaching out from {{brand_name}}. {{business_tagline}}</p><p style="margin:0 0 16px;">We support professional {{industry}} conversations with clear coordination, practical next steps, and transparent business communication.</p><p style="margin:0;">If this is relevant for {{company_name}}, I would be glad to share more details.</p>',
            ],
            [
                'name' => 'Generic - Service business outreach',
                'subject' => 'Service support for {{company_name}}',
                'preheader' => 'Professional outreach for service-based business conversations.',
                'stage' => 0,
                'text' => "Hello {{contact_name}},\n\nI am contacting you from {{brand_name}} regarding professional service support for businesses in {{industry}}.\n\nIf {{company_name}} is reviewing outside support or vendor options, we can share capabilities, next steps, and fit criteria.",
                'html' => '<p style="margin:0 0 16px;">Hello {{contact_name}},</p><p style="margin:0 0 16px;">I am contacting you from {{brand_name}} regarding professional service support for businesses in {{industry}}.</p><p style="margin:0;">If {{company_name}} is reviewing outside support or vendor options, we can share capabilities, next steps, and fit criteria.</p>',
            ],
            [
                'name' => 'Generic - Product supplier outreach',
                'subject' => 'Supplier coordination from {{brand_name}}',
                'preheader' => 'Clear product-supply coordination for B2B buyers.',
                'stage' => 0,
                'text' => "Hello {{contact_name}},\n\n{{brand_name}} supports B2B product and supply conversations with clear documentation, practical coordination, and professional follow-up.\n\nIf {{company_name}} is reviewing supplier options, we can share availability, commercial details, and next steps.",
                'html' => '<p style="margin:0 0 16px;">Hello {{contact_name}},</p><p style="margin:0 0 16px;">{{brand_name}} supports B2B product and supply conversations with clear documentation, practical coordination, and professional follow-up.</p><p style="margin:0;">If {{company_name}} is reviewing supplier options, we can share availability, commercial details, and next steps.</p>',
            ],
            [
                'name' => 'Generic - Follow-up 1',
                'subject' => 'Following up from {{brand_name}}',
                'preheader' => 'A short follow-up on the earlier business outreach.',
                'stage' => 1,
                'text' => "Hello {{contact_name}},\n\nI wanted to follow up on my previous note from {{brand_name}}.\n\nIf this is relevant for {{company_name}}, I can share more details or route the conversation to the right next step.",
                'html' => '<p style="margin:0 0 16px;">Hello {{contact_name}},</p><p style="margin:0 0 16px;">I wanted to follow up on my previous note from {{brand_name}}.</p><p style="margin:0;">If this is relevant for {{company_name}}, I can share more details or route the conversation to the right next step.</p>',
            ],
            [
                'name' => 'Generic - Follow-up 2',
                'subject' => 'Closing the loop from {{brand_name}}',
                'preheader' => 'A final respectful follow-up.',
                'stage' => 2,
                'text' => "Hello {{contact_name}},\n\nI am closing the loop on my previous outreach.\n\nIf this becomes relevant for {{company_name}}, {{brand_name}} would be glad to help with a professional B2B conversation.",
                'html' => '<p style="margin:0 0 16px;">Hello {{contact_name}},</p><p style="margin:0 0 16px;">I am closing the loop on my previous outreach.</p><p style="margin:0;">If this becomes relevant for {{company_name}}, {{brand_name}} would be glad to help with a professional B2B conversation.</p>',
            ],
            [
                'name' => 'Generic - RFQ confirmation',
                'subject' => 'We received your request for {{brand_name}}',
                'preheader' => 'Your request is in and the team will review it shortly.',
                'stage' => 9,
                'text' => "Hello {{contact_name}},\n\nThank you for contacting {{brand_name}}. We received your request and will review the details before following up.",
                'html' => '<p style="margin:0 0 16px;">Hello {{contact_name}},</p><p style="margin:0;">Thank you for contacting {{brand_name}}. We received your request and will review the details before following up.</p>',
            ],
            [
                'name' => 'Generic - Quote follow-up',
                'subject' => 'Following up on your {{brand_name}} quote',
                'preheader' => 'A short follow-up after a quote or pricing conversation.',
                'stage' => 3,
                'text' => "Hello {{contact_name}},\n\nI wanted to follow up on the quote conversation with {{brand_name}}.\n\nIf you have questions about scope, timing, or next steps, we would be glad to help.",
                'html' => '<p style="margin:0 0 16px;">Hello {{contact_name}},</p><p style="margin:0 0 16px;">I wanted to follow up on the quote conversation with {{brand_name}}.</p><p style="margin:0;">If you have questions about scope, timing, or next steps, we would be glad to help.</p>',
            ],
            [
                'name' => 'Generic - Re-engagement email',
                'subject' => 'Checking in from {{brand_name}}',
                'preheader' => 'A calm re-engagement email for earlier contacts.',
                'stage' => 4,
                'text' => "Hello {{contact_name}},\n\nI am checking in from {{brand_name}} in case this is a better time to reconnect.\n\nIf {{company_name}} is reviewing options in {{industry}}, we can share current capabilities and next steps.",
                'html' => '<p style="margin:0 0 16px;">Hello {{contact_name}},</p><p style="margin:0 0 16px;">I am checking in from {{brand_name}} in case this is a better time to reconnect.</p><p style="margin:0;">If {{company_name}} is reviewing options in {{industry}}, we can share current capabilities and next steps.</p>',
            ],
        ];

        $stmt = Database::pdo()->prepare(
            'INSERT INTO email_templates (business_profile_id, name, category, subject, preheader, body, body_html, body_text, followup_stage, active, created_at, updated_at)
             VALUES (?, ?, "Other", ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE subject = VALUES(subject), preheader = VALUES(preheader), body = VALUES(body), body_html = VALUES(body_html), body_text = VALUES(body_text), followup_stage = VALUES(followup_stage), updated_at = NOW()'
        );
        foreach ($templates as $template) {
            $stmt->execute([
                $businessProfileId,
                $template['name'],
                $template['subject'],
                $template['preheader'],
                $template['text'],
                $template['html'],
                $template['text'],
                $template['stage'],
            ]);
        }
    }
}
