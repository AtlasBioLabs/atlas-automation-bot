<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';

Auth::require();
$businessId = BusinessProfile::currentId();
$errors = [];
$template = ['name' => '', 'category' => 'Other', 'subject' => '', 'preheader' => '', 'body' => '', 'body_html' => '', 'body_text' => '', 'followup_stage' => 0, 'active' => 1];

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
        $stmt = Database::pdo()->prepare('INSERT INTO email_templates (business_profile_id, name, category, subject, preheader, body, body_html, body_text, followup_stage, active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$businessId, $template['name'], $template['category'], $template['subject'], $template['preheader'], $template['body'], $template['body_html'], $template['body_text'], $template['followup_stage'], $template['active']]);
        flash('success', 'Template created.');
        redirect('/templates/index.php');
    }
}

render_header('Create Template');
require __DIR__ . '/form.php';
render_footer();
