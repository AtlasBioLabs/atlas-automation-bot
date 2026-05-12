<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';

Auth::require();
$businessId = BusinessProfile::currentId();
$stmt = Database::pdo()->prepare('SELECT * FROM rfqs WHERE business_profile_id = ? ORDER BY created_at DESC LIMIT 250');
$stmt->execute([$businessId]);
$rfqs = $stmt->fetchAll();

render_header('RFQs');
?>
<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Date</th><th>Source</th><th>Name</th><th>Company</th><th>Email</th><th>Product</th><th>Quantity</th><th>Timeline</th><th>Items</th><th>Message</th></tr></thead>
      <tbody>
      <?php foreach ($rfqs as $rfq): ?>
        <tr>
          <td><?= e(format_app_datetime($rfq['created_at'], $businessId)) ?></td>
          <td><?= e($rfq['source'] ?? 'website_rfq') ?></td>
          <td><?= e($rfq['name']) ?></td>
          <td><?= e($rfq['company']) ?></td>
          <td><?= e($rfq['email']) ?></td>
          <td><?= e($rfq['product_interest']) ?></td>
          <td><?= e($rfq['estimated_quantity']) ?></td>
          <td><?= e($rfq['timeline'] ?? '') ?></td>
          <td class="small"><?= e($rfq['items_json'] ?? '') ?></td>
          <td class="small"><?= e($rfq['message']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_footer(); ?>
