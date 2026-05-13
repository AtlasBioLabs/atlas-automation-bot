<?php

declare(strict_types=1);

require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/QueueService.php';
require_once __DIR__ . '/LeadService.php';

final class OutreachService
{
    public static function queueInitial(int $leadId, ?int $businessProfileId = null, ?int $templateId = null): bool
    {
        $pdo = Database::pdo();
        $lead = self::lead($leadId);
        if ($businessProfileId !== null && $lead && (int) $lead['business_profile_id'] !== $businessProfileId) {
            return false;
        }
        if (!$lead || LeadService::suppressionReason($lead) !== null) {
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

        $localNow = app_now($businessProfileId);
        if ((int) $localNow->format('N') >= 6) {
            return ['processed' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'message' => 'Weekend sending is disabled.'];
        }

        $missing = BusinessProfile::requiredMailFieldsMissing($business);
        if ($missing) {
            return ['processed' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'message' => 'Business profile is missing required email fields: ' . implode(', ', $missing)];
        }

        $limit = (int) $business['daily_send_limit'];
        [$todayStartUtc, $tomorrowStartUtc] = app_current_day_bounds_utc($businessProfileId);
        $sentToday = self::countWhere(
            'email_logs',
            'business_profile_id = ? AND status = "sent" AND created_at >= ? AND created_at < ?',
            [$businessProfileId, $todayStartUtc, $tomorrowStartUtc]
        );
        $remainingLimit = max(0, $limit - $sentToday);
        if ($remainingLimit === 0) {
            return ['processed' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'message' => 'Daily send limit already reached for this local business day.'];
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT q.*, l.company_name, l.contact_name, l.email, l.category, l.status AS lead_status, l.bounced, l.unsubscribed, l.unsubscribe_token,
                    l.archived_at, l.deleted_at,
                    t.id AS resolved_template_id, t.active AS template_active, t.subject, t.preheader, t.body, t.body_html, t.body_text, t.followup_stage
             FROM email_queue q
             JOIN leads l ON l.id = q.lead_id
             LEFT JOIN email_templates t ON t.id = q.template_id AND t.business_profile_id = q.business_profile_id
             WHERE q.business_profile_id = ? AND q.status = "pending" AND q.scheduled_at <= ?
             ORDER BY q.scheduled_at ASC, q.id ASC
             LIMIT ?'
        );
        $stmt->bindValue(1, $businessProfileId, PDO::PARAM_INT);
        $stmt->bindValue(2, now_sql());
        $stmt->bindValue(3, $remainingLimit, PDO::PARAM_INT);
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
                $reason = LeadService::suppressionReason([
                    'email' => $row['email'],
                    'status' => $row['lead_status'],
                    'bounced' => $row['bounced'],
                    'unsubscribed' => $row['unsubscribed'],
                    'archived_at' => $row['archived_at'],
                    'deleted_at' => $row['deleted_at'],
                ]) ?? 'stopped_status';
                self::markQueue((int) $row['id'], 'skipped', 'Lead skipped: ' . skip_reason_label($reason) . '.');
                $stats['skipped']++;
                continue;
            }

            $row['business_profile_id'] = $businessProfileId;
            $rendered = self::queueRenderPayload($row, $business);
            $pdo->prepare(
                'UPDATE email_queue
                 SET rendered_subject = ?, rendered_preheader = ?, rendered_html = ?, rendered_text = ?, rendered_variables = ?, updated_at = NOW()
                 WHERE id = ? AND business_profile_id = ?'
            )->execute([
                $rendered['subject'],
                $rendered['preheader'],
                $rendered['html'],
                $rendered['text'],
                json_encode($rendered['variables'] ?? [], JSON_UNESCAPED_SLASHES),
                (int) $row['id'],
                $businessProfileId,
            ]);
            $result = Mailer::send(
                $row['email'],
                $row['contact_name'] ?: $row['company_name'],
                $rendered['subject'],
                $rendered['text'],
                $business,
                ['html' => $rendered['html'], 'text' => $rendered['text'], 'preheader' => $rendered['preheader'], 'prepared' => true]
            );
            self::logEmail($businessProfileId, (int) $row['lead_id'], (int) $row['template_id'], (int) $row['id'], $row['email'], $rendered['subject'], $result);

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
        [$todayStartUtc, $tomorrowStartUtc] = app_current_day_bounds_utc($businessProfileId);
        [$tomorrowWindowStartUtc, $dayAfterTomorrowStartUtc] = app_tomorrow_day_bounds_utc($businessProfileId);

        return [
            'emails_sent_today' => self::countWhere('email_logs', 'business_profile_id = ? AND status = "sent" AND created_at >= ? AND created_at < ?', [$businessProfileId, $todayStartUtc, $tomorrowStartUtc]),
            'failed_emails_today' => self::countWhere('email_logs', 'business_profile_id = ? AND status = "failed" AND created_at >= ? AND created_at < ?', [$businessProfileId, $todayStartUtc, $tomorrowStartUtc]),
            'total_queued_today' => self::countWhere('email_queue', 'business_profile_id = ? AND created_at >= ? AND created_at < ?', [$businessProfileId, $todayStartUtc, $tomorrowStartUtc]),
            'skipped_today' => self::countWhere('email_queue', 'business_profile_id = ? AND status = "skipped" AND updated_at >= ? AND updated_at < ?', [$businessProfileId, $todayStartUtc, $tomorrowStartUtc]),
            'pending_queue' => self::countWhere('email_queue', 'business_profile_id = ? AND status = "pending"', [$businessProfileId]),
            'campaigns_created_today' => self::countWhere('campaigns', 'business_profile_id = ? AND created_at >= ? AND created_at < ?', [$businessProfileId, $todayStartUtc, $tomorrowStartUtc]),
            'new_rfqs_today' => self::countWhere('rfqs', 'business_profile_id = ? AND created_at >= ? AND created_at < ?', [$businessProfileId, $todayStartUtc, $tomorrowStartUtc]),
            'interested_leads' => self::countWhere('leads', 'business_profile_id = ? AND status = "interested"', [$businessProfileId]),
            'followups_tomorrow' => self::countWhere('email_queue', 'business_profile_id = ? AND status = "pending" AND scheduled_at >= ? AND scheduled_at < ?', [$businessProfileId, $tomorrowWindowStartUtc, $dayAfterTomorrowStartUtc]),
            'unsubscribes_today' => self::countWhere('leads', 'business_profile_id = ? AND unsubscribed = 1 AND updated_at >= ? AND updated_at < ?', [$businessProfileId, $todayStartUtc, $tomorrowStartUtc]),
            'bounces_today' => self::countWhere('leads', 'business_profile_id = ? AND bounced = 1 AND updated_at >= ? AND updated_at < ?', [$businessProfileId, $todayStartUtc, $tomorrowStartUtc]),
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

    public static function syncCampaignStatus(int $businessProfileId, int $campaignId): void
    {
        self::refreshCampaignStatus($businessProfileId, $campaignId);
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
        $lead = self::lead($leadId);
        if (!$lead) {
            return;
        }
        $business = Settings::effectiveBusiness($businessProfileId) ?? BusinessProfile::find($businessProfileId);
        $stmt = Database::pdo()->prepare('SELECT * FROM email_templates WHERE id = ? AND business_profile_id = ? LIMIT 1');
        $stmt->execute([$templateId, $businessProfileId]);
        $template = $stmt->fetch();
        if (!$template) {
            return;
        }
        $render = Mailer::renderTemplate($template, $lead, $business);
        $stmt = Database::pdo()->prepare(
            'INSERT INTO email_queue (business_profile_id, lead_id, template_id, rendered_subject, rendered_preheader, rendered_html, rendered_text, rendered_variables, scheduled_at, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", NOW(), NOW())'
        );
        $stmt->execute([
            $businessProfileId,
            $leadId,
            $templateId,
            $render['subject'],
            $render['preheader'],
            $render['html'],
            $render['text'],
            json_encode($render['variables'], JSON_UNESCAPED_SLASHES),
            $scheduledAt,
        ]);
    }

    private static function shouldSkip(array $row): bool
    {
        return LeadService::suppressionReason([
            'email' => $row['email'] ?? '',
            'status' => $row['lead_status'] ?? '',
            'bounced' => $row['bounced'] ?? 0,
            'unsubscribed' => $row['unsubscribed'] ?? 0,
            'archived_at' => $row['archived_at'] ?? null,
            'deleted_at' => $row['deleted_at'] ?? null,
        ]) !== null;
    }

    private static function markQueue(int $queueId, string $status, ?string $error): void
    {
        $sentAtSql = $status === 'sent' ? ', sent_at = NOW()' : '';
        $stmt = Database::pdo()->prepare("UPDATE email_queue SET status = ?, error_message = ?, updated_at = NOW(){$sentAtSql} WHERE id = ?");
        $stmt->execute([$status, $error, $queueId]);
    }

    private static function logEmail(int $businessProfileId, int $leadId, int $templateId, int $queueId, string $recipient, string $subject, array $result): void
    {
        $params = [
            $businessProfileId,
            $leadId,
            $templateId,
            $queueId,
            $recipient,
            $subject,
            $result['sent'] ? 'sent' : 'failed',
            $result['reference'] ?? null,
            $result['error'],
        ];

        try {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO email_logs (business_profile_id, lead_id, template_id, queue_id, recipient_email, subject, status, provider_reference, error_message, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute($params);
        } catch (PDOException $exception) {
            if (!str_contains(strtolower($exception->getMessage()), 'provider_reference')) {
                throw $exception;
            }

            $fallback = Database::pdo()->prepare(
                'INSERT INTO email_logs (business_profile_id, lead_id, template_id, queue_id, recipient_email, subject, status, error_message, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $fallback->execute([
                $businessProfileId,
                $leadId,
                $templateId,
                $queueId,
                $recipient,
                $subject,
                $result['sent'] ? 'sent' : 'failed',
                $result['error'],
            ]);
        }
    }

    private static function refreshCampaignStatus(int $businessProfileId, int $campaignId): void
    {
        $stmt = Database::pdo()->prepare(
            'SELECT
                SUM(status = "pending") AS pending_count,
                SUM(status = "failed") AS failed_count,
                SUM(status = "sent") AS sent_count,
                SUM(status = "cancelled") AS cancelled_count,
                SUM(status = "skipped") AS skipped_count,
                COUNT(*) AS total_count
             FROM email_queue
             WHERE business_profile_id = ? AND campaign_id = ?'
        );
        $stmt->execute([$businessProfileId, $campaignId]);
        $counts = $stmt->fetch() ?: ['pending_count' => 0, 'failed_count' => 0, 'sent_count' => 0, 'cancelled_count' => 0, 'skipped_count' => 0, 'total_count' => 0];
        $pending = (int) $counts['pending_count'];
        $failed = (int) $counts['failed_count'];
        $sent = (int) $counts['sent_count'];
        $cancelled = (int) $counts['cancelled_count'];
        $skipped = (int) $counts['skipped_count'];
        $total = (int) $counts['total_count'];

        $status = 'queued';
        if ($total === 0) {
            $status = 'draft';
        } elseif ($pending > 0 && ($sent > 0 || $failed > 0)) {
            $status = 'sending';
        } elseif ($pending > 0) {
            $status = 'queued';
        } elseif ($sent > 0 && $failed === 0 && $cancelled === 0 && $skipped === 0) {
            $status = 'completed';
        } elseif ($sent > 0 && ($failed > 0 || $cancelled > 0 || $skipped > 0)) {
            $status = 'partially_failed';
        } elseif ($cancelled > 0 && $sent === 0 && $failed === 0) {
            $status = 'cancelled';
        } elseif ($failed > 0) {
            $status = 'partially_failed';
        }

        $update = Database::pdo()->prepare('UPDATE campaigns SET status = ?, updated_at = NOW() WHERE id = ? AND business_profile_id = ?');
        $update->execute([$status, $campaignId, $businessProfileId]);
    }

    private static function queueRenderPayload(array $row, array $business): array
    {
        return Mailer::renderTemplate($row, $row, $business);
    }

    private static function advanceLead(array $row): void
    {
        $stage = (int) $row['followup_stage'];
        $leadId = (int) $row['lead_id'];
        $businessProfileId = (int) $row['business_profile_id'];
        $pdo = Database::pdo();
        $sentAtUtc = now_sql();
        $localNow = app_now($businessProfileId);

        if ($stage === 0) {
            $delayDays = (int) Settings::option('FOLLOWUP_1_DAYS', Settings::get('followup_1_days', app_config('followup_1_days', 3), $businessProfileId), $businessProfileId);
            $next = $localNow->modify('+' . $delayDays . ' days');
            $nextUtc = app_datetime_to_utc($next, $businessProfileId)?->format('Y-m-d H:i:s');
            $pdo->prepare('UPDATE leads SET status = "emailed", followup_stage = 0, last_contacted_at = ?, next_followup_at = ?, updated_at = NOW() WHERE id = ?')->execute([$sentAtUtc, $nextUtc, $leadId]);
            $template = self::templateForLead($row, 1);
            if ($template && $nextUtc !== null) {
                self::queueEmail($businessProfileId, $leadId, (int) $template['id'], $nextUtc);
            }
            return;
        }

        if ($stage === 1) {
            $delayDays = (int) Settings::option('FOLLOWUP_2_DAYS', Settings::get('followup_2_days', app_config('followup_2_days', 7), $businessProfileId), $businessProfileId);
            $next = $localNow->modify('+' . $delayDays . ' days');
            $nextUtc = app_datetime_to_utc($next, $businessProfileId)?->format('Y-m-d H:i:s');
            $pdo->prepare('UPDATE leads SET status = "follow_up_1_sent", followup_stage = 1, last_contacted_at = ?, next_followup_at = ?, updated_at = NOW() WHERE id = ?')->execute([$sentAtUtc, $nextUtc, $leadId]);
            $template = self::templateForLead($row, 2);
            if ($template && $nextUtc !== null) {
                self::queueEmail($businessProfileId, $leadId, (int) $template['id'], $nextUtc);
            }
            return;
        }

        $pdo->prepare('UPDATE leads SET status = "follow_up_2_sent", followup_stage = 2, last_contacted_at = ?, next_followup_at = NULL, updated_at = NOW() WHERE id = ?')->execute([$sentAtUtc, $leadId]);
    }

    private static function countWhere(string $table, string $where, array $params): int
    {
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
