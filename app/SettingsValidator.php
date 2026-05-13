<?php

declare(strict_types=1);

final class SettingsValidator
{
    public static function validate(array $fields): array
    {
        $errors = [];
        if (!in_array($fields['MAIL_PROVIDER'] ?? '', ['log', 'smtp', 'brevo_api'], true)) {
            $errors[] = 'Mail provider must be log, smtp, or brevo_api.';
        }
        foreach (['MAIL_FROM_EMAIL', 'MAIL_REPLY_TO', 'ADMIN_EMAIL'] as $emailField) {
            if (($fields[$emailField] ?? '') !== '' && !filter_var($fields[$emailField], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "{$emailField} must be a valid email.";
            }
        }
        foreach (['WEBSITE_URL', 'COMPANY_PROFILE_URL'] as $urlField) {
            $value = trim((string) ($fields[$urlField] ?? ''));
            if ($value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
                $errors[] = "{$urlField} must be a valid URL.";
            }
        }
        foreach (['DAILY_SEND_LIMIT', 'FOLLOWUP_1_DAYS', 'FOLLOWUP_2_DAYS', 'MAIL_SMTP_PORT'] as $numberField) {
            if (!ctype_digit((string) ($fields[$numberField] ?? '')) || (int) $fields[$numberField] < 1) {
                $errors[] = "{$numberField} must be a positive number.";
            }
        }
        foreach (['APP_NAME', 'BUSINESS_NAME', 'MAIL_FROM_NAME', 'MAIL_FROM_EMAIL', 'MAIL_REPLY_TO', 'BUSINESS_ADDRESS', 'COMPLIANCE_FOOTER', 'UNSUBSCRIBE_FOOTER_TEXT'] as $requiredField) {
            if (trim((string) ($fields[$requiredField] ?? '')) === '') {
                $errors[] = "{$requiredField} is required.";
            }
        }
        if (!valid_timezone_name((string) ($fields['TIMEZONE'] ?? ''))) {
            $errors[] = 'TIMEZONE must be a valid IANA timezone.';
        }
        if (($fields['MAIL_PROVIDER'] ?? '') === 'smtp') {
            foreach (['MAIL_FROM_NAME', 'MAIL_FROM_EMAIL', 'MAIL_SMTP_HOST', 'MAIL_SMTP_PORT', 'MAIL_SMTP_USER'] as $requiredField) {
                if (trim((string) ($fields[$requiredField] ?? '')) === '') {
                    $errors[] = "{$requiredField} is required when MAIL_PROVIDER=smtp.";
                }
            }
        }

        return $errors;
    }
}
