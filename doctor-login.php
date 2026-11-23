<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
if (is_logged_in()) { redirect(is_role('owner') ? 'dashboard.php' : 'doctordashboard.php'); }
$errors = [];
if (request_is_post()) {
    try {
        verify_csrf();
        $username = post_string('username'); $password = (string) ($_POST['password'] ?? '');
        if ($username === '' || $password === '') { throw new RuntimeException('Enter both username and password.'); }
        $stmt = db()->prepare('SELECT id, name, username, password_hash FROM data_users WHERE username = ?');
        $stmt->execute([$username]); $account = $stmt->fetch();
        if (!$account || !password_verify($password, $account['password_hash'])) { throw new RuntimeException('The Data User username or password is incorrect.'); }
        login_user('user', $account); flash('success', 'Welcome back, ' . $account['name'] . '.'); redirect('doctordashboard.php');
    } catch (Throwable $exception) { $errors[] = $exception->getMessage(); }
}
$pageTitle = 'Data User Login'; require __DIR__ . '/header.php';
?>
<section class="card auth-card"><h1>Data User Login</h1><p class="muted">For doctors/research users who search metadata and request controlled report access.</p>
<?php if ($errors): ?><div class="alert alert-error"><ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post"><?= csrf_field() ?><div class="field"><label for="username">Username</label><input id="username" name="username" value="<?= e(post_string('username')) ?>" required autocomplete="username"></div><div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"></div><div class="actions"><button class="btn" type="submit">Login as Data User</button><a href="<?= e(url('doctor-register.php')) ?>">Create user account</a></div></form></section>
<?php require __DIR__ . '/footer.php'; ?>
