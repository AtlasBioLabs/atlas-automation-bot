<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';

Auth::require();
$id = (int) ($_GET['id'] ?? 0);
$business = BusinessProfile::find($id);
if (!$business) {
    http_response_code(404);
    exit('Business profile not found.');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require __DIR__ . '/save.php';
    if (!$errors) {
        flash('success', 'Business profile updated.');
        redirect('/businesses/edit.php?id=' . $id);
    }
}

render_header('Edit Business');
require __DIR__ . '/form.php';
render_footer();
