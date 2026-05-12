<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';

Auth::require();
$businessId = BusinessProfile::currentId();
$errors = [];
$lead = ['company_name' => '', 'contact_name' => '', 'email' => '', 'phone' => '', 'website' => '', 'country' => '', 'category' => 'Other', 'source' => '', 'status' => 'new', 'notes' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($lead as $key => $value) {
        $lead[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $lead['email'] = normalize_email($lead['email']);
    if ($lead['company_name'] === '') {
        $errors[] = 'Company name is required.';
    }
    if (!filter_var($lead['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    if (!in_array($lead['category'], lead_categories(), true)) {
        $lead['category'] = 'Other';
    }
    if (!in_array($lead['status'], lead_statuses(), true)) {
        $lead['status'] = 'new';
    }

    if (!$errors) {
        try {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO leads (business_profile_id, company_name, contact_name, email, phone, website, country, category, source, status, notes, unsubscribe_token, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([
                $businessId,
                $lead['company_name'],
                $lead['contact_name'],
                $lead['email'],
                $lead['phone'],
                $lead['website'],
                $lead['country'],
                $lead['category'],
                $lead['source'],
                $lead['status'],
                $lead['notes'],
                bin2hex(random_bytes(32)),
            ]);
            $id = (int) Database::pdo()->lastInsertId();
            flash('success', 'Lead created. Select an email template on the lead edit page before queueing outreach.');
            redirect('/leads/edit.php?id=' . $id);
        } catch (PDOException $exception) {
            $errors[] = str_contains($exception->getMessage(), 'Duplicate') ? 'A lead with this email already exists.' : $exception->getMessage();
        }
    }
}

render_header('Create Lead');
require __DIR__ . '/form.php';
render_footer();
