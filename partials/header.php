<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

$flashMessage = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Records Manager</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-content">
            <a class="brand" href="index.php">Student Records</a>
            <nav aria-label="Main navigation">
                <a class="nav-link" href="index.php">Students</a>
            </nav>
        </div>
    </header>
    <main class="container main-content">
        <?php if ($flashMessage !== null): ?>
            <div class="flash flash-<?= e($flashMessage['type']) ?>" role="status">
                <?= e($flashMessage['msg']) ?>
            </div>
        <?php endif; ?>
