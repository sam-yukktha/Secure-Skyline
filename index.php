<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
if (is_logged_in()) {
    redirect(is_role('owner') ? 'dashboard.php' : 'doctordashboard.php');
}
$errors = [];
if (request_is_post()) {
    try {
        verify_csrf();
        $username = post_string('username');
        $password = (string) ($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            throw new RuntimeException('Enter both username and password.');
        }
        $stmt = db()->prepare('SELECT id, name, username, password_hash FROM data_owners WHERE username = ?');
        $stmt->execute([$username]);
        $owner = $stmt->fetch();
        if (!$owner || !password_verify($password, $owner['password_hash'])) {
            throw new RuntimeException('The Data Owner username or password is incorrect.');
        }
        login_user('owner', $owner);
        flash('success', 'Welcome back, ' . $owner['name'] . '.');
        redirect('dashboard.php');
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
    }
}
$pageTitle = 'Data Owner Login';
require __DIR__ . '/header.php';
?>
<section class="hero">
    <h1>Secure and Efficient Skyline Queries on Encrypted Data</h1>
    <p class="lead">A final-year educational prototype for encrypted medical-report storage, controlled access, multi-criteria skyline queries, and transparent server-side analysis.</p>
    <div class="workflow"><div>Owner adds medical data and uploads a report.</div><div>Report content is encrypted with authenticated encryption.</div><div>Data User searches and requests access.</div><div>Approved access is validated before server-side decryption.</div></div>
</section>
<section class="card auth-card">
    <h2>Data Owner Login</h2>
    <?php if ($errors): ?><div class="alert alert-error"><ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="field"><label for="username">Username</label><input id="username" name="username" value="<?= e(post_string('username')) ?>" required autocomplete="username"></div>
        <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
        <div class="actions"><button class="btn" type="submit">Login as Data Owner</button><a href="<?= e(url('register.php')) ?>">Create owner account</a></div>
    </form>
</section>
<?php require __DIR__ . '/footer.php'; ?>
