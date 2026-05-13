<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';

Auth::require();
$businessId = BusinessProfile::currentId();
$id = (int) ($_GET['id'] ?? 0);
$stmt = Database::pdo()->prepare('SELECT * FROM email_templates WHERE id = ? AND business_profile_id = ?');
$stmt->execute([$id, $businessId]);
$template = $stmt->fetch();
if (!$template) {
    http_response_code(404);
    exit('Template not found.');
}
$template['body_text'] = (string) ($template['body_text'] ?: $template['body'] ?? '');
$template['body_html'] = (string) ($template['body_html'] ?? '');
$template['preheader'] = (string) ($template['preheader'] ?? '');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (['name', 'category', 'subject', 'preheader', 'body_html', 'body_text'] as $field) {
        $template[$field] = trim((string) ($_POST[$field] ?? ''));
    }
    $template['body_html'] = sanitize_template_body_html($template['body_html']);
    $template['body'] = $template['body_text'] !== '' ? $template['body_text'] : trim(strip_tags($template['body_html']));
    $template['followup_stage'] = (int) ($_POST['followup_stage'] ?? 0);
    $template['active'] = !empty($_POST['active']) ? 1 : 0;
    if ($template['name'] === '' || $template['subject'] === '' || ($template['body_html'] === '' && $template['body_text'] === '')) {
        $errors[] = 'Name, subject, and either HTML body or plain text fallback are required.';
    }
    if (!$errors) {
        $stmt = Database::pdo()->prepare('UPDATE email_templates SET name = ?, category = ?, subject = ?, preheader = ?, body = ?, body_html = ?, body_text = ?, followup_stage = ?, active = ?, updated_at = NOW() WHERE id = ? AND business_profile_id = ?');
        $stmt->execute([$template['name'], $template['category'], $template['subject'], $template['preheader'], $template['body'], $template['body_html'], $template['body_text'], $template['followup_stage'], $template['active'], $id, $businessId]);
        flash('success', 'Template updated.');
        redirect('/templates/index.php');
    }
}

render_header('Edit Template');
require __DIR__ . '/form.php';
render_footer();
