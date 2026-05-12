<?php

declare(strict_types=1);

require_once __DIR__ . '/Mailer.php';

final class QueueService
{
    public const STOPPED_STATUSES = ['replied', 'interested', 'quoted', 'customer', 'not_interested', 'complained', 'invalid', 'bounced', 'unsubscribed'];

    public static function templates(int $businessProfileId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM email_templates WHERE business_profile_id = ? AND active = 1 ORDER BY followup_stage, name');
        $stmt->execute([$businessProfileId]);
        return $stmt->fetchAll();
    }

    public static function savedSegments(int $businessProfileId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM saved_segments WHERE business_profile_id = ? ORDER BY name');
        $stmt->execute([$businessProfileId]);
        return $stmt->fetchAll();
    }

    public static function preview(array $input, int $businessProfileId): array
    {
        $template = self::template((int) ($input['template_id'] ?? 0), $businessProfileId);
        if (!$template) {
            return ['errors' => ['A valid template is required.']];
        }

        $leadIds = self::resolveLeadIds($input, $businessProfileId);
        $leads = self::loadLeads($leadIds, $businessProfileId);
        $eligible = [];
        $skipped = [];
        foreach ($leads as $lead) {
            $reason = self::skipReason($lead, (int) $template['id']);
            if ($reason === null) {
                $eligible[] = $lead;
            } else {
                $skipped[] = ['lead' => $lead, 'reason' => $reason];
            }
        }
        $skippedReasonCounts = [];
        foreach ($skipped as $skippedLead) {
            $reason = (string) $skippedLead['reason'];
            $skippedReasonCounts[$reason] = ($skippedReasonCounts[$reason] ?? 0) + 1;
        }

        $sample = $eligible[0] ?? $leads[0] ?? null;
        $business = Settings::effectiveBusiness($businessProfileId) ?? BusinessProfile::find($businessProfileId);

        return [
            'errors' => [],
            'template' => $template,
            'business' => $business,
            'lead_ids' => array_map(fn (array $lead): int => (int) $lead['id'], $leads),
            'eligible' => $eligible,
            'skipped' => $skipped,
            'total_count' => count($leads),
            'eligible_count' => count($eligible),
            'skipped_count' => count($skipped),
            'skipped_reason_counts' => $skippedReasonCounts,
            'sample_subject' => $sample ? Mailer::render($template['subject'], $sample, $business) : '',
            'sample_body' => $sample ? Mailer::render($template['body'], $sample, $business) : '',
        ];
    }

    public static function createCampaign(array $input, int $businessProfileId): array
    {
        $preview = self::preview($input, $businessProfileId);
        if (!empty($preview['errors'])) {
            return ['created' => false, 'errors' => $preview['errors']];
        }

        $campaignName = trim((string) ($input['campaign_name'] ?? ''));
        if ($campaignName === '') {
            return ['created' => false, 'errors' => ['Campaign name is required.']];
        }

        $scheduledAt = self::normalizeScheduledAt((string) ($input['scheduled_at'] ?? ''));
        if ($scheduledAt === null) {
            return ['created' => false, 'errors' => ['A valid schedule date/time is required.']];
        }

        $sendType = (string) ($input['send_type'] ?? 'filtered');
        if (!in_array($sendType, ['single', 'selected', 'filtered'], true)) {
            $sendType = 'filtered';
        }
        $filterRules = self::filterRulesFromInput($input, $businessProfileId);
        $adminId = 0;
        if (session_status() === PHP_SESSION_ACTIVE || !headers_sent()) {
            start_app_session();
            $adminId = (int) ($_SESSION['admin_id'] ?? 0);
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO campaigns (business_profile_id, name, description, template_id, audience_type, filter_rules, total_recipients, eligible_recipients, skipped_recipients, skipped_reasons, status, scheduled_at, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "queued", ?, ?, NOW(), NOW())'
            );
            $stmt->execute([
                $businessProfileId,
                $campaignName,
                trim((string) ($input['campaign_description'] ?? $input['description'] ?? '')),
                (int) $preview['template']['id'],
                $sendType,
                json_encode($filterRules),
                $preview['total_count'],
                $preview['eligible_count'],
                $preview['skipped_count'],
                json_encode($preview['skipped_reason_counts']),
                $scheduledAt,
                $adminId > 0 ? $adminId : null,
            ]);
            $campaignId = (int) $pdo->lastInsertId();

            $queued = 0;
            $queue = $pdo->prepare(
                'INSERT INTO email_queue (business_profile_id, campaign_id, campaign_name, lead_id, template_id, scheduled_at, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, "pending", NOW(), NOW())'
            );
            foreach ($preview['eligible'] as $lead) {
                $queue->execute([$businessProfileId, $campaignId, $campaignName, (int) $lead['id'], (int) $preview['template']['id'], $scheduledAt]);
                $queued++;
            }

            $pdo->commit();
            return ['created' => true, 'campaign_id' => $campaignId, 'queued' => $queued, 'skipped' => $preview['skipped_count']];
        } catch (Throwable $throwable) {
            $pdo->rollBack();
            return ['created' => false, 'errors' => [$throwable->getMessage()]];
        }
    }

    public static function buildFilterWhere(array $filters, int $businessProfileId): array
    {
        $where = ['business_profile_id = ?'];
        $params = [$businessProfileId];

        foreach (['status', 'category', 'country', 'source', 'followup_stage'] as $field) {
            $value = trim((string) ($filters[$field] ?? ''));
            if ($value !== '') {
                $where[] = "{$field} = ?";
                $params[] = $value;
            }
        }

        $createdFrom = trim((string) ($filters['created_from'] ?? ''));
        if ($createdFrom !== '') {
            $where[] = 'DATE(created_at) >= ?';
            $params[] = $createdFrom;
        }

        $createdTo = trim((string) ($filters['created_to'] ?? ''));
        if ($createdTo !== '') {
            $where[] = 'DATE(created_at) <= ?';
            $params[] = $createdTo;
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $where[] = '(company_name LIKE ? OR contact_name LIKE ? OR email LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        return [$where, $params];
    }

    public static function normalizeScheduledAt(string $value): ?string
    {
        if (trim($value) === '') {
            return now_sql();
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value)
            ?: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value)
            ?: DateTimeImmutable::createFromFormat('Y-m-d H:i', $value);

        return $date ? $date->format('Y-m-d H:i:s') : null;
    }

    private static function resolveLeadIds(array $input, int $businessProfileId): array
    {
        $sendType = (string) ($input['send_type'] ?? 'filtered');
        if ($sendType === 'single') {
            return [(int) ($input['lead_id'] ?? 0)];
        }

        if ($sendType === 'selected') {
            $raw = $input['selected_lead_ids'] ?? [];
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $raw = is_array($decoded) ? $decoded : preg_split('/[,\\s]+/', $raw);
            }
            return array_values(array_unique(array_filter(array_map('intval', (array) $raw))));
        }

        [$where, $params] = self::buildFilterWhere(self::filterRulesFromInput($input, $businessProfileId), $businessProfileId);
        $stmt = Database::pdo()->prepare('SELECT id FROM leads WHERE ' . implode(' AND ', $where) . ' ORDER BY id ASC LIMIT 5000');
        $stmt->execute($params);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private static function filterRulesFromInput(array $input, ?int $businessProfileId = null): array
    {
        $filters = $input['filters'] ?? $input;
        $rules = [
            'q' => trim((string) ($filters['q'] ?? '')),
            'status' => trim((string) ($filters['status'] ?? '')),
            'category' => trim((string) ($filters['category'] ?? '')),
            'country' => trim((string) ($filters['country'] ?? '')),
            'source' => trim((string) ($filters['source'] ?? '')),
            'followup_stage' => trim((string) ($filters['followup_stage'] ?? '')),
            'created_from' => trim((string) ($filters['created_from'] ?? '')),
            'created_to' => trim((string) ($filters['created_to'] ?? '')),
        ];
        $segmentId = (int) ($filters['segment_id'] ?? $input['segment_id'] ?? 0);
        if ($segmentId > 0 && $businessProfileId !== null) {
            $stmt = Database::pdo()->prepare('SELECT filter_rules FROM saved_segments WHERE id = ? AND business_profile_id = ? LIMIT 1');
            $stmt->execute([$segmentId, $businessProfileId]);
            $saved = $stmt->fetchColumn();
            $savedRules = $saved ? json_decode((string) $saved, true) : [];
            if (is_array($savedRules)) {
                foreach ($rules as $key => $value) {
                    if ($value === '' && isset($savedRules[$key])) {
                        $rules[$key] = trim((string) $savedRules[$key]);
                    }
                }
            }
            $rules['segment_id'] = (string) $segmentId;
        }
        if (($input['send_type'] ?? '') === 'single') {
            $rules['lead_id'] = (string) ((int) ($input['lead_id'] ?? 0));
        }
        if (($input['send_type'] ?? '') === 'selected') {
            $selected = $input['selected_lead_ids'] ?? [];
            if (is_string($selected)) {
                $decoded = json_decode($selected, true);
                $selected = is_array($decoded) ? $decoded : preg_split('/[,\\s]+/', $selected);
            }
            $rules['selected_lead_ids'] = array_values(array_unique(array_filter(array_map('intval', (array) $selected))));
        }

        return $rules;
    }

    private static function loadLeads(array $leadIds, int $businessProfileId): array
    {
        $leadIds = array_values(array_unique(array_filter($leadIds)));
        if (!$leadIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($leadIds), '?'));
        $stmt = Database::pdo()->prepare("SELECT * FROM leads WHERE business_profile_id = ? AND id IN ({$placeholders}) ORDER BY id ASC");
        $stmt->execute(array_merge([$businessProfileId], $leadIds));
        return $stmt->fetchAll();
    }

    private static function template(int $templateId, int $businessProfileId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM email_templates WHERE id = ? AND business_profile_id = ? AND active = 1 LIMIT 1');
        $stmt->execute([$templateId, $businessProfileId]);
        $template = $stmt->fetch();
        return $template ?: null;
    }

    private static function skipReason(array $lead, int $templateId): ?string
    {
        if ((bool) $lead['unsubscribed']) {
            return 'unsubscribed';
        }
        if ((bool) $lead['bounced']) {
            return 'bounced';
        }
        if (in_array($lead['status'], self::STOPPED_STATUSES, true)) {
            return 'stopped_status';
        }

        $stmt = Database::pdo()->prepare('SELECT id FROM email_queue WHERE business_profile_id = ? AND lead_id = ? AND template_id = ? AND status = "pending" LIMIT 1');
        $stmt->execute([(int) $lead['business_profile_id'], (int) $lead['id'], $templateId]);
        if ($stmt->fetch()) {
            return 'duplicate_pending';
        }

        return null;
    }
}
