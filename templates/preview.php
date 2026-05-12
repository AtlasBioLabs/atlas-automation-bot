<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';
require_once __DIR__ . '/../app/LeadService.php';

Auth::require();
$businessId = BusinessProfile::currentId();
$business = Settings::effectiveBusiness($businessId) ?? BusinessProfile::current();
$id = (int) ($_GET['id'] ?? 0);
$stmt = Database::pdo()->prepare('SELECT * FROM email_templates WHERE id = ? AND business_profile_id = ? LIMIT 1');
$stmt->execute([$id, $businessId]);
$template = $stmt->fetch();
if (!$template) {
    http_response_code(404);
    exit('Template not found.');
}

$leadStmt = Database::pdo()->prepare(
    'SELECT * FROM leads
     WHERE business_profile_id = ? AND deleted_at IS NULL
     ORDER BY archived_at IS NULL DESC, updated_at DESC
     LIMIT 100'
);
$leadStmt->execute([$businessId]);
$leads = $leadStmt->fetchAll();
$selectedLeadId = (int) ($_GET['lead_id'] ?? ($leads[0]['id'] ?? 0));
$selectedLead = null;
foreach ($leads as $lead) {
    if ((int) $lead['id'] === $selectedLeadId) {
        $selectedLead = $lead;
        break;
    }
}
$selectedLead ??= $leads[0] ?? [
    'business_profile_id' => $businessId,
    'contact_name' => 'Sample Contact',
    'company_name' => 'Sample Company',
    'email' => 'sample@example.com',
    'category' => 'Other',
    'unsubscribe_token' => bin2hex(random_bytes(8)),
];

$preview = Mailer::renderTemplate($template, $selectedLead, $business);

render_header('Template Preview');
?>
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form class="row g-3">
      <input type="hidden" name="id" value="<?= e($id) ?>">
      <div class="col-md-6">
        <label class="form-label">Sample lead</label>
        <select class="form-select" name="lead_id">
          <?php foreach ($leads as $lead): ?>
            <option value="<?= e($lead['id']) ?>"<?= selected((string) $selectedLeadId, (string) $lead['id']) ?>><?= e(LeadService::displayName($lead)) ?> - <?= e($lead['company_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6 d-flex align-items-end gap-2">
        <button class="btn btn-primary" type="submit">Preview</button>
        <a class="btn btn-outline-secondary" href="/templates/edit.php?id=<?= e($id) ?>">Back to template</a>
      </div>
    </form>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h2 class="h5">Rendered Details</h2>
        <div class="mb-3"><div class="small text-muted">Subject</div><div class="fw-semibold"><?= e($preview['subject']) ?></div></div>
        <div class="mb-3"><div class="small text-muted">Preheader</div><div><?= e($preview['preheader']) ?></div></div>
        <div class="mb-3"><div class="small text-muted">Sample lead</div><div><?= e(LeadService::displayName($selectedLead)) ?> / <?= e($selectedLead['company_name'] ?? '') ?></div></div>
        <div class="mb-3"><div class="small text-muted">Unsubscribe link</div><div class="small text-break"><?= e($preview['unsubscribe_link']) ?></div></div>
        <div><div class="small text-muted">Variables rendered</div>
          <ul class="small mb-0">
            <?php foreach ($preview['variables'] as $token => $value): ?>
              <li><code><?= e($token) ?></code>: <?= e((string) $value) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php if ($preview['missing_variables']): ?>
          <div class="alert alert-warning small mt-3 mb-0">Unresolved variables: <?= e(implode(', ', $preview['missing_variables'])) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5">Desktop Preview</h2>
        <iframe class="w-100 border rounded bg-white" style="min-height:640px;" srcdoc="<?= e($preview['html']) ?>"></iframe>
      </div>
    </div>
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5">Mobile Preview</h2>
        <iframe class="d-block mx-auto border rounded bg-white" style="width:100%;max-width:390px;min-height:640px;" srcdoc="<?= e($preview['html']) ?>"></iframe>
      </div>
    </div>
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5">Plain Text Fallback</h2>
        <pre class="bg-light border rounded p-3 mb-0" style="white-space:pre-wrap"><?= e($preview['text']) ?></pre>
      </div>
    </div>
  </div>
</div>
<?php render_footer(); ?>
