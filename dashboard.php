<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

Auth::require();
$pdo = Database::pdo();
$businessId = BusinessProfile::currentId();
$stats = [
    'Total leads' => 0,
    'New leads' => 0,
    'Emails sent today' => 0,
    'Follow-ups due' => 0,
    'Interested leads' => 0,
    'Bounced emails' => 0,
    'Unsubscribed leads' => 0,
    'RFQs received' => 0,
];
$count = function (string $sql, array $params = []) use ($pdo): int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
};
$stats['Total leads'] = $count('SELECT COUNT(*) FROM leads WHERE business_profile_id = ?', [$businessId]);
$stats['New leads'] = $count('SELECT COUNT(*) FROM leads WHERE business_profile_id = ? AND status = "new"', [$businessId]);
$stats['Emails sent today'] = $count('SELECT COUNT(*) FROM email_logs WHERE business_profile_id = ? AND status = "sent" AND DATE(created_at) = CURDATE()', [$businessId]);
$stats['Follow-ups due'] = $count('SELECT COUNT(*) FROM email_queue WHERE business_profile_id = ? AND status = "pending" AND scheduled_at <= NOW()', [$businessId]);
$stats['Interested leads'] = $count('SELECT COUNT(*) FROM leads WHERE business_profile_id = ? AND status = "interested"', [$businessId]);
$stats['Bounced emails'] = $count('SELECT COUNT(*) FROM leads WHERE business_profile_id = ? AND bounced = 1', [$businessId]);
$stats['Unsubscribed leads'] = $count('SELECT COUNT(*) FROM leads WHERE business_profile_id = ? AND unsubscribed = 1', [$businessId]);
$stats['RFQs received'] = $count('SELECT COUNT(*) FROM rfqs WHERE business_profile_id = ?', [$businessId]);
$stmt = $pdo->prepare('SELECT id, company_name, email, category, status, created_at FROM leads WHERE business_profile_id = ? ORDER BY created_at DESC LIMIT 8');
$stmt->execute([$businessId]);
$recentLeads = $stmt->fetchAll();

render_header('Dashboard');
?>
<div class="row g-3 mb-4">
  <?php foreach ($stats as $label => $value): ?>
    <div class="col-sm-6 col-xl-3">
      <div class="card stat-card shadow-sm">
        <div class="card-body">
          <div class="small text-muted"><?= e($label) ?></div>
          <div class="fs-3 fw-semibold"><?= e($value) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<div class="card shadow-sm">
  <div class="card-header bg-white fw-semibold">Recent Leads</div>
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Company</th><th>Email</th><th>Category</th><th>Status</th><th>Created</th></tr></thead>
      <tbody>
        <?php foreach ($recentLeads as $lead): ?>
          <tr>
            <td><a href="/leads/edit.php?id=<?= e($lead['id']) ?>"><?= e($lead['company_name']) ?></a></td>
            <td><?= e($lead['email']) ?></td>
            <td><?= e($lead['category']) ?></td>
            <td><?= badge_status($lead['status']) ?></td>
            <td><?= e($lead['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_footer(); ?>
