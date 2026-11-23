<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
if (is_logged_in()) { redirect(is_role('owner') ? 'dashboard.php' : 'doctordashboard.php'); }
$errors = [];
if (request_is_post()) {
    try {
        verify_csrf();
        $name = post_string('name'); $email = post_string('email'); $phone = post_string('phone'); $username = post_string('username'); $password = (string) ($_POST['password'] ?? '');
        if ($name === '' || $username === '' || $password === '') { throw new RuntimeException('Name, username, and password are required.'); }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new RuntimeException('Enter a valid email address.'); }
        if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) { throw new RuntimeException('Username must be 3–30 letters, numbers, dots, dashes, or underscores.'); }
        if (strlen($password) < 8) { throw new RuntimeException('Password must contain at least 8 characters.'); }
        $stmt = db()->prepare('INSERT INTO data_owners (name,email,phone,username,password_hash) VALUES (?,?,?,?,?)');
        $stmt->execute([$name, $email ?: null, $phone ?: null, $username, password_hash($password, PASSWORD_DEFAULT)]);
        flash('success', 'Data Owner account created. You can now sign in.');
        redirect('index.php');
    } catch (PDOException $exception) {
        $errors[] = $exception->getCode() === '23000' ? 'That username or email is already registered.' : 'Registration could not be completed.';
    } catch (Throwable $exception) { $errors[] = $exception->getMessage(); }
}
$pageTitle = 'Data Owner Registration'; require __DIR__ . '/header.php';
?>
<section class="card auth-card"><h1>Data Owner Registration</h1>
<?php if ($errors): ?><div class="alert alert-error"><ul class="error-list"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post"><?= csrf_field() ?><div class="form-grid">
<div class="field full"><label for="name">Full name</label><input id="name" name="name" value="<?= e(post_string('name')) ?>" required></div>
<div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="<?= e(post_string('email')) ?>"></div>
<div class="field"><label for="phone">Phone</label><input id="phone" name="phone" value="<?= e(post_string('phone')) ?>"></div>
<div class="field"><label for="username">Username</label><input id="username" name="username" value="<?= e(post_string('username')) ?>" required autocomplete="username"></div>
<div class="field"><label for="password">Password (minimum 8 characters)</label><input id="password" name="password" type="password" required autocomplete="new-password"></div>
</div><div class="actions"><button class="btn" type="submit">Register</button><a href="<?= e(url('index.php')) ?>">Back to login</a></div></form></section>
<?php require __DIR__ . '/footer.php'; ?>
