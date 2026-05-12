<?php

declare(strict_types=1);

final class LeadService
{
    public const VISIBILITY_ACTIVE = 'active';
    public const VISIBILITY_ARCHIVED = 'archived';
    public const VISIBILITY_ALL = 'all';

    public static function visibilityModes(): array
    {
        return [
            self::VISIBILITY_ACTIVE => 'Active leads',
            self::VISIBILITY_ARCHIVED => 'Archived leads',
            self::VISIBILITY_ALL => 'All leads',
        ];
    }

    public static function visibilityClause(string $visibility): string
    {
        return match ($visibility) {
            self::VISIBILITY_ARCHIVED => 'deleted_at IS NULL AND archived_at IS NOT NULL',
            self::VISIBILITY_ALL => '1=1',
            default => 'deleted_at IS NULL AND archived_at IS NULL',
        };
    }

    public static function stateLabel(array $lead): string
    {
        if (!empty($lead['deleted_at'])) {
            return 'Deleted';
        }
        if (!empty($lead['archived_at'])) {
            return 'Archived';
        }

        return 'Active';
    }

    public static function displayName(?array $lead): string
    {
        if (!$lead) {
            return 'Deleted lead';
        }
        if (!empty($lead['deleted_at'])) {
            return 'Deleted lead';
        }

        $name = trim((string) ($lead['contact_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        $company = trim((string) ($lead['company_name'] ?? ''));
        if ($company !== '') {
            return $company;
        }

        return trim((string) ($lead['email'] ?? '')) ?: 'Deleted lead';
    }

    public static function suppressionReason(array $lead): ?string
    {
        if (!empty($lead['deleted_at']) || !empty($lead['archived_at'])) {
            return 'archived_deleted';
        }
        if (!filter_var((string) ($lead['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
            return 'invalid_email';
        }
        if ((bool) ($lead['unsubscribed'] ?? false)) {
            return 'unsubscribed';
        }
        if ((bool) ($lead['bounced'] ?? false)) {
            return 'bounced';
        }
        if (in_array((string) ($lead['status'] ?? ''), stopped_statuses(), true)) {
            return 'stopped_status';
        }

        return null;
    }

    public static function archive(int $leadId, int $businessProfileId): bool
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE leads
             SET archived_at = COALESCE(archived_at, NOW()), next_followup_at = NULL, updated_at = NOW()
             WHERE id = ? AND business_profile_id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$leadId, $businessProfileId]);
        return $stmt->rowCount() > 0;
    }

    public static function restore(int $leadId, int $businessProfileId): bool
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE leads
             SET archived_at = NULL, updated_at = NOW()
             WHERE id = ? AND business_profile_id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$leadId, $businessProfileId]);
        return $stmt->rowCount() > 0;
    }

    public static function permanentlyDelete(int $leadId, int $businessProfileId): bool
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM leads WHERE id = ? AND business_profile_id = ? LIMIT 1');
        $stmt->execute([$leadId, $businessProfileId]);
        $lead = $stmt->fetch();
        if (!$lead || !empty($lead['deleted_at'])) {
            return false;
        }

        $notes = trim((string) ($lead['notes'] ?? ''));
        $notes .= ($notes !== '' ? "\n\n" : '') . 'Lead permanently deleted by admin on ' . now_sql() . '. Identifying data removed to preserve campaign and queue history.';
        $tombstoneEmail = 'deleted+' . $leadId . '-' . date('YmdHis') . '@deleted.local';
        $token = bin2hex(random_bytes(32));

        $update = Database::pdo()->prepare(
            'UPDATE leads
             SET company_name = "Deleted lead",
                 contact_name = NULL,
                 email = ?,
                 phone = NULL,
                 website = NULL,
                 country = NULL,
                 category = "Other",
                 source = "deleted",
                 status = "deleted",
                 notes = ?,
                 next_followup_at = NULL,
                 bounced = 1,
                 unsubscribed = 1,
                 unsubscribe_token = ?,
                 archived_at = COALESCE(archived_at, NOW()),
                 deleted_at = NOW(),
                 updated_at = NOW()
             WHERE id = ? AND business_profile_id = ?'
        );
        $update->execute([$tombstoneEmail, $notes, $token, $leadId, $businessProfileId]);

        $queue = Database::pdo()->prepare(
            'UPDATE email_queue
             SET status = "cancelled", error_message = "Lead permanently deleted by admin.", updated_at = NOW()
             WHERE business_profile_id = ? AND lead_id = ? AND status = "pending"'
        );
        $queue->execute([$businessProfileId, $leadId]);

        return $update->rowCount() > 0;
    }
}
