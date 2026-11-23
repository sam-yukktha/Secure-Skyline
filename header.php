<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = isset($pageTitle) ? $pageTitle . ' · ' . app_config('app_name') : app_config('app_name');
$auth = user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(url('medical_files/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="shell brand-row">
        <a class="brand" href="<?= e(url($auth ? ($auth['role'] === 'owner' ? 'dashboard.php' : 'doctordashboard.php') : 'index.php')) ?>">
            <span class="brand-mark">S</span>
            <span>Secure Skyline</span>
        </a>
        <?php if ($auth): ?>
            <div class="signed-in">Signed in as <strong><?= e($auth['name']) ?></strong> · <?= e($auth['role'] === 'owner' ? 'Data Owner' : 'Data User') ?></div>
        <?php endif; ?>
    </div>
    <nav class="site-nav" aria-label="Primary navigation">
        <div class="shell nav-links">
            <?php if (!$auth): ?>
                <a href="<?= e(url('index.php')) ?>">Owner Login</a>
                <a href="<?= e(url('register.php')) ?>">Owner Register</a>
                <a href="<?= e(url('doctor-login.php')) ?>">Data User Login</a>
                <a href="<?= e(url('doctor-register.php')) ?>">Data User Register</a>
            <?php elseif ($auth['role'] === 'owner'): ?>
                <a href="<?= e(url('dashboard.php')) ?>">Dashboard & Upload</a>
                <a href="<?= e(url('view_file.php')) ?>">My Reports</a>
                <a href="<?= e(url('view_request.php')) ?>">Access Requests</a>
                <a href="<?= e(url('patientdashboard.php')) ?>">Health History</a>
                <a href="<?= e(url('view_health.php')) ?>">Medical Records</a>
                <a href="<?= e(url('logout.php')) ?>">Logout</a>
            <?php else: ?>
                <a href="<?= e(url('doctordashboard.php')) ?>">Dashboard & Search</a>
                <a href="<?= e(url('view_response.php')) ?>">My Requests</a>
                <a href="<?= e(url('skyline-query.php')) ?>">Skyline Query</a>
                <a href="<?= e(url('performance-report.php')) ?>">Performance Report</a>
                <a href="<?= e(url('report.php')) ?>">Clustering Demo</a>
                <a href="<?= e(url('logout.php')) ?>">Logout</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
<main class="shell main-content">
    <?php foreach (take_flashes() as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
