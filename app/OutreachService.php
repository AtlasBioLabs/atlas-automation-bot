<?php

declare(strict_types=1);

require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/QueueService.php';

final class OutreachService
{
    public static function queueInitial(int $leadId, ?int $businessProfileId = null, ?int $templateId = null): bool
    {
        $pdo = Database::pdo();
        $lead = self::lead($leadId);
        if ($businessProfileId !== null && $lead && (int) $lead['business_profile_id'] !== $businessProfileId) {
            return false;
        }
        if (!$lead || (bool) $lead['bounced'] || (bool) $lead['unsubscribed'] || in_array($lead['status'], stopped_statuses(), true)) {
            return false;
        }

        if ($templateId === null || $templateId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT * FROM email_templates WHERE id = ? AND business_profile_id = ? AND active = 1 LIMIT 1');
        $stmt->execute([$templateId, (int) $lead['business_profile_id']]);
        $template = $stmt->fetch();
        if (!$template) {
            return false;
        }

        $duplicate = $pdo->prepare('SELECT id FROM email_queue WHERE business_profile_id = ? AND lead_id = ? AND template_id = ? AND status = "pending" LIMIT 1');
        $duplicate->execute([(int) $lead['business_profile_id'], $leadId, (int) $template['id']]);
        if ($duplicate->fetch()) {
            return false;
        }

        self::queueEmail((int) $lead['business_profile_id'], $leadId, (int) $template['id'], now_sql());
        $pdo->prepare('UPDATE leads SET status = ?, updated_at = NOW() WHERE id = ?')->execute(['queued', $leadId]);
        return true;
    }

    public static function processDaily(?int $businessProfileId = null): array
    {
        if ($businessProfileId === null) {
            $allStats = [];
            foreach (BusinessProfile::all(false) as $business) {
                $allStats[(int) $business['id']] = self::processDaily((int) $business['id']);
            }
            return ['businesses' => $allStats];
        }

        $business = Settings::effectiveBusiness($businessProfileId) ?? BusinessProfile::find($businessProfileId);
        if (!$business || !(bool) $business['active']) {
            return ['processed' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'message' => 'Business profile is inactive or missing.'];
        }

        $today = new DateTimeImmutable('now');
        if ((int) $today->format('N') >= 6) {
            return ['processed' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'message' => 'Weekend sending is disabled.'];
        }

        $missing = BusinessProfile::requiredMailFieldsMissing($business);
        if ($missing) {
            return ['processed' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'message' => 'Business profile is missing required email fields: ' . implode(', ', $missing)];
        }

        $limit = (int) $business['daily_send_limit'];
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT q.*, l.company_name, l.contact_name, l.email, l.category, l.status AS lead_status, l.bounced, l.unsubscribed, l.unsubscribe_token,
                    t.id AS resolved_template_id, t.active AS template_active, t.subject, t.body, t.followup_stage
             FROM email_queue q
             JOIN leads l ON l.id = q.lead_id
             LEFT JOIN email_templates t ON t.id = q.template_id AND t.business_profile_id = q.business_profile_id
             WHERE q.business_profile_id = ? AND q.status = "pending" AND q.scheduled_at <= NOW()
             ORDER BY q.scheduled_at ASC, q.id ASC
             LIMIT ?'
        );
        $stmt->bindValue(1, $businessProfileId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $stats = ['processed' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'message' => 'Daily send complete.'];
        $campaignIds = [];
        foreach ($rows as $row) {
            $stats['processed']++;
            if (!empty($row['campaign_id'])) {
                $campaignIds[] = (int) $row['campaign_id'];
            }
            if (empty($row['template_id']) || empty($row['resolved_template_id'])) {
                self::markQueue((int) $row['id'], 'failed', 'Template is missing for this queue item.');
                $stats['failed']++;
                continue;
            }
            if ((int) $row['template_active'] !== 1) {
                self::markQueue((int) $row['id'], 'failed', 'Template is inactive for this queue item.');
                $stats['failed']++;
                continue;
            }
            if (self::shouldSkip($row)) {
                self::markQueue((int) $row['id'], 'skipped', 'Lead is stopped, bounced, or unsubscribed.');
                $stats['skipped']++;
                continue;
            }

            $row['business_profile_id'] = $businessProfileId;
            $subject = Mailer::render($row['subject'], $row, $business);
            $body = Mailer::render($row['body'], $row, $business);
            $result = Mailer::send($row['email'], $row['contact_name'] ?: $row['company_name'], $subject, $body, $business);
            self::logEmail($businessProfileId, (int) $row['lead_id'], (int) $row['template_id'], (int) $row['id'], $row['email'], $subject, $result);

            if ($result['sent']) {
                self::markQueue((int) $row['id'], 'sent', null);
                self::advanceLead($row);
                $stats['sent']++;
            } else {
                self::markQueue((int) $row['id'], 'failed', $result['error']);
                $stats['failed']++;
            }
        }
        foreach (array_values(array_unique($campaignIds)) as $campaignId) {
            self::refreshCampaignStatus($businessProfileId, $campaignId);
        }

        return $stats;
    }

    public static function dailyReportData(?int $businessProfileId = null): array
    {
        $businessProfileId ??= BusinessProfile::currentId();
        $today = date('Y-m-d');
        $tomorrow = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');

        return [
            'emails_sent_today' => self::countWhere('email_logs', 'business_profile_id = ? AND status = "sent" AND DATE(created_at) = ?', [$businessProfileId, $today]),
            'failed_emails_today' => self::countWhere('email_logs', 'business_profile_id = ? AND status = "failed" AND DATE(created_at) = ?', [$businessProfileId, $today]),
            'total_queued_today' => self::countWhere('email_queue', 'business_profile_id = ? AND DATE(created_at) = ?', [$businessProfileId, $today]),
            'skipped_today' => self::countWhere('email_queue', 'business_profile_id = ? AND status = "skipped" AND DATE(updated_at) = ?', [$businessProfileId, $today]),
            'pending_queue' => self::countWhere('email_queue', 'business_profile_id = ? AND status = "pending"', [$businessProfileId]),
            'campaigns_created_today' => self::countWhere('campaigns', 'business_profile_id = ? AND DATE(created_at) = ?', [$businessProfileId, $today]),
            'new_rfqs_today' => self::countWhere('rfqs', 'business_profile_id = ? AND DATE(created_at) = ?', [$businessProfileId, $today]),
            'interested_leads' => self::countWhere('leads', 'business_profile_id = ? AND status = "interested"', [$businessProfileId]),
            'followups_tomorrow' => self::countWhere('email_queue', 'business_profile_id = ? AND status = "pending" AND DATE(scheduled_at) = ?', [$businessProfileId, $tomorrow]),
            'unsubscribes_today' => self::countWhere('leads', 'business_profile_id = ? AND unsubscribed = 1 AND DATE(updated_at) = ?', [$businessProfileId, $today]),
            'bounces_today' => self::countWhere('leads', 'business_profile_id = ? AND bounced = 1 AND DATE(updated_at) = ?', [$businessProfileId, $today]),
        ];
    }

    public static function campaignReportRows(int $businessProfileId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.id, c.name, c.status, c.audience_type, c.scheduled_at, c.total_recipients, c.eligible_recipients, c.skipped_recipients,
                    COALESCE(SUM(q.status = "pending"), 0) AS pending_count,
                    COALESCE(SUM(q.status = "sent"), 0) AS sent_count,
                    COALESCE(SUM(q.status = "failed"), 0) AS failed_count,
                    COALESCE(SUM(q.status = "skipped"), 0) AS skipped_queue_count
             FROM campaigns c
             LEFT JOIN email_queue q ON q.campaign_id = c.id AND q.business_profile_id = c.business_profile_id
             WHERE c.business_profile_id = ?
             GROUP BY c.id
             ORDER BY c.created_at DESC
             LIMIT 25'
        );
        $stmt->execute([$businessProfileId]);
        return $stmt->fetchAll();
    }

    public static function rfqsBySource(int $businessProfileId): array
    {
        $stmt = Database::pdo()->prepare('SELECT source, COUNT(*) AS total FROM rfqs WHERE business_profile_id = ? GROUP BY source ORDER BY total DESC, source ASC');
        $stmt->execute([$businessProfileId]);
        return $stmt->fetchAll();
    }

    private static function lead(int $leadId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM leads WHERE id = ?');
        $stmt->execute([$leadId]);
        $lead = $stmt->fetch();
        return $lead ?: null;
    }

    private static function templateForLead(array $lead, int $stage): ?array
    {
        $pdo = Database::pdo();
        if ($stage === 0) {
            $stmt = $pdo->prepare('SELECT * FROM email_templates WHERE business_profile_id = ? AND active = 1 AND followup_stage = 0 AND category = ? ORDER BY id LIMIT 1');
            $stmt->execute([(int) $lead['business_profile_id'], $lead['category']]);
            $template = $stmt->fetch();
            if ($template) {
                return $template;
            }
        }

        $stmt = $pdo->prepare('SELECT * FROM email_templates WHERE business_profile_id = ? AND active = 1 AND followup_stage = ? ORDER BY id LIMIT 1');
        $stmt->execute([(int) $lead['business_profile_id'], $stage]);
        $template = $stmt->fetch();
        return $template ?: null;
    }

    private static function queueEmail(int $businessProfileId, int $leadId, int $templateId, string $scheduledAt): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO email_queue (business_profile_id, lead_id, template_id, scheduled_at, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, "pending", NOW(), NOW())'
        );
        $stmt->execute([$businessProfileId, $leadId, $templateId, $scheduledAt]);
    }

    private static function shouldSkip(array $row): bool
    {
        return (bool) $row['bounced']
            || (bool) $row['unsubscribed']
            || in_array($row['lead_status'], stopped_statuses(), true);
    }

    private static function markQueue(int $queueId, string $status, ?string $error): void
    {
        $sentAtSql = $status === 'sent' ? ', sent_at = NOW()' : '';
        $stmt = Database::pdo()->prepare("UPDATE email_queue SET status = ?, error_message = ?, updated_at = NOW(){$sentAtSql} WHERE id = ?");
        $stmt->execute([$status, $error, $queueId]);
    }

    private static function logEmail(int $businessProfileId, int $leadId, int $templateId, int $queueId, string $recipient, string $subject, array $result): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO email_logs (business_profile_id, lead_id, template_id, queue_id, recipient_email, subject, status, error_message, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$businessProfileId, $leadId, $templateId, $queueId, $recipient, $subject, $result['sent'] ? 'sent' : 'failed', $result['error']]);
    }

    private static function refreshCampaignStatus(int $businessProfileId, int $campaignId): void
    {
        $stmt = Database::pdo()->prepare(
            'SELECT
                SUM(status = "pending") AS pending_count,
                SUM(status = "failed") AS failed_count,
                SUM(status = "sent") AS sent_count,
                COUNT(*) AS total_count
             FROM email_queue
             WHERE business_profile_id = ? AND campaign_id = ?'
        );
        $stmt->execute([$businessProfileId, $campaignId]);
        $counts = $stmt->fetch() ?: ['pending_count' => 0, 'failed_count' => 0, 'sent_count' => 0, 'total_count' => 0];
        $status = ((int) $counts['pending_count'] > 0) ? 'queued' : 'completed';
        if ((int) $counts['total_count'] > 0 && (int) $counts['failed_count'] > 0 && (int) $counts['sent_count'] === 0) {
            $status = 'failed';
        }

        $update = Database::pdo()->prepare('UPDATE campaigns SET status = ?, updated_at = NOW() WHERE id = ? AND business_profile_id = ?');
        $update->execute([$status, $campaignId, $businessProfileId]);
    }

    private static function advanceLead(array $row): void
    {
        $stage = (int) $row['followup_stage'];
        $leadId = (int) $row['lead_id'];
        $pdo = Database::pdo();

        if ($stage === 0) {
            $next = (new DateTimeImmutable('+' . (int) Settings::option('FOLLOWUP_1_DAYS', Settings::get('followup_1_days', app_config('followup_1_days', 3), (int) $row['business_profile_id']), (int) $row['business_profile_id']) . ' days'))->format('Y-m-d H:i:s');
            $pdo->prepare('UPDATE leads SET status = "emailed", followup_stage = 0, last_contacted_at = NOW(), next_followup_at = ?, updated_at = NOW() WHERE id = ?')->execute([$next, $leadId]);
            $template = self::templateForLead($row, 1);
            if ($template) {
                self::queueEmail((int) $row['business_profile_id'], $leadId, (int) $template['id'], $next);
            }
            return;
        }

        if ($stage === 1) {
            $next = (new DateTimeImmutable('+' . (int) Settings::option('FOLLOWUP_2_DAYS', Settings::get('followup_2_days', app_config('followup_2_days', 7), (int) $row['business_profile_id']), (int) $row['business_profile_id']) . ' days'))->format('Y-m-d H:i:s');
            $pdo->prepare('UPDATE leads SET status = "follow_up_1_sent", followup_stage = 1, last_contacted_at = NOW(), next_followup_at = ?, updated_at = NOW() WHERE id = ?')->execute([$next, $leadId]);
            $template = self::templateForLead($row, 2);
            if ($template) {
                self::queueEmail((int) $row['business_profile_id'], $leadId, (int) $template['id'], $next);
            }
            return;
        }

        $pdo->prepare('UPDATE leads SET status = "follow_up_2_sent", followup_stage = 2, last_contacted_at = NOW(), next_followup_at = NULL, updated_at = NOW() WHERE id = ?')->execute([$leadId]);
    }

    private static function countWhere(string $table, string $where, array $params): int
    {
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
