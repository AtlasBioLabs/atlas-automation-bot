<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';

Auth::require();
$businessId = BusinessProfile::currentId();
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $result = ['imported' => 0, 'skipped' => 0, 'errors' => []];
    if (empty($_FILES['csv']['tmp_name']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
        $result['errors'][] = 'CSV file is required.';
    } else {
        $handle = fopen($_FILES['csv']['tmp_name'], 'r');
        $headers = array_map(fn ($h) => trim(mb_strtolower((string) $h)), fgetcsv($handle) ?: []);
        $required = ['email', 'company_name'];
        foreach ($required as $column) {
            if (!in_array($column, $headers, true)) {
                $result['errors'][] = "Missing required column: {$column}";
            }
        }

        if (!$result['errors']) {
            $pdo = Database::pdo();
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($headers, array_pad($row, count($headers), ''));
                $email = normalize_email((string) ($data['email'] ?? ''));
                $company = trim((string) ($data['company_name'] ?? ''));
                if ($company === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $result['skipped']++;
                    continue;
                }
                $exists = $pdo->prepare('SELECT id FROM leads WHERE business_profile_id = ? AND email = ?');
                $exists->execute([$businessId, $email]);
                if ($exists->fetch()) {
                    $result['skipped']++;
                    continue;
                }
                $stmt = $pdo->prepare(
                    'INSERT INTO leads (business_profile_id, company_name, contact_name, email, phone, website, country, category, source, status, notes, unsubscribe_token, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
                );
                $category = trim((string) ($data['category'] ?? 'Other'));
                $status = trim((string) ($data['status'] ?? 'new'));
                $stmt->execute([
                    $businessId,
                    $company,
                    trim((string) ($data['contact_name'] ?? '')),
                    $email,
                    trim((string) ($data['phone'] ?? '')),
                    trim((string) ($data['website'] ?? '')),
                    trim((string) ($data['country'] ?? '')),
                    in_array($category, lead_categories(), true) ? $category : 'Other',
                    trim((string) ($data['source'] ?? 'CSV')),
                    in_array($status, lead_statuses(), true) ? $status : 'new',
                    trim((string) ($data['notes'] ?? '')),
                    bin2hex(random_bytes(32)),
                ]);
                $result['imported']++;
            }
            fclose($handle);
        }
    }
}

render_header('Import Leads');
?>
<div class="card shadow-sm">
  <div class="card-body">
    <?php if ($result): ?>
      <div class="alert alert-info">Imported: <?= e($result['imported']) ?>. Skipped: <?= e($result['skipped']) ?>. <?= e(implode(' ', $result['errors'])) ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">CSV file</label>
        <input class="form-control" type="file" name="csv" accept=".csv,text/csv" required>
        <div class="form-text">Required columns: email, company_name. Optional: contact_name, phone, website, country, category, source, status, notes.</div>
      </div>
      <button class="btn btn-primary" type="submit">Import leads</button>
    </form>
  </div>
</div>
<?php render_footer(); ?>
