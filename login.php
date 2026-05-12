<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

start_app_session();
if (Auth::user()) {
    redirect('/dashboard.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = normalize_email((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (Auth::attempt($email, $password)) {
        redirect('/dashboard.php');
    }
    $error = 'Invalid email or password.';
}

render_header('Admin Login');
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if (Auth::adminCount() === 0): ?>
          <div class="alert alert-warning small">No admin account exists yet. Run <code>php scripts/create_admin.php</code> after database setup.</div>
        <?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" required autocomplete="email">
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="password" required autocomplete="current-password">
          </div>
          <button class="btn btn-primary w-100" type="submit">Login</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php render_footer(); ?>
