<?php

declare(strict_types=1);

final class SettingsValidator
{
    public static function validate(array $fields): array
    {
        $errors = [];
        if (!in_array($fields['MAIL_PROVIDER'] ?? '', ['log', 'smtp'], true)) {
            $errors[] = 'Mail provider must be log or smtp.';
        }
        foreach (['MAIL_FROM_EMAIL', 'MAIL_REPLY_TO', 'ADMIN_EMAIL'] as $emailField) {
            if (($fields[$emailField] ?? '') !== '' && !filter_var($fields[$emailField], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "{$emailField} must be a valid email.";
            }
        }
        foreach (['DAILY_SEND_LIMIT', 'FOLLOWUP_1_DAYS', 'FOLLOWUP_2_DAYS', 'MAIL_SMTP_PORT'] as $numberField) {
            if (!ctype_digit((string) ($fields[$numberField] ?? '')) || (int) $fields[$numberField] < 1) {
                $errors[] = "{$numberField} must be a positive number.";
            }
        }
        foreach (['APP_NAME', 'BUSINESS_NAME', 'MAIL_FROM_NAME', 'MAIL_FROM_EMAIL', 'BUSINESS_ADDRESS', 'COMPLIANCE_FOOTER'] as $requiredField) {
            if (trim((string) ($fields[$requiredField] ?? '')) === '') {
                $errors[] = "{$requiredField} is required.";
            }
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
