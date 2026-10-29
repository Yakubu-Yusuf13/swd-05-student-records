<?php
declare(strict_types=1);

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="error-state" aria-labelledby="page-title">
        <h1 id="page-title">Method not allowed</h1>
        <p>Student records can only be deleted using the delete form.</p>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

if (!isValidCsrfToken($_POST['_csrf_token'] ?? null)) {
    http_response_code(400);
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="error-state" aria-labelledby="page-title">
        <h1 id="page-title">Request could not be verified</h1>
        <p>Refresh the student list and try again.</p>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    http_response_code(400);
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="error-state" aria-labelledby="page-title">
        <h1 id="page-title">Invalid student record</h1>
        <p>The student ID is missing or invalid.</p>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

try {
    $delete = db()->prepare('DELETE FROM students WHERE id = :id');
    $delete->execute(['id' => $id]);

    if ($delete->rowCount() === 0) {
        http_response_code(404);
        require __DIR__ . '/partials/header.php';
        ?>
        <section class="error-state" aria-labelledby="page-title">
            <h1 id="page-title">Student not found</h1>
            <p>The student record may already have been deleted.</p>
        </section>
        <?php
        require __DIR__ . '/partials/footer.php';
        exit;
    }

    flash('success', 'Student deleted');
    redirect('index.php');
} catch (PDOException) {
    http_response_code(503);
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="error-state" aria-labelledby="page-title">
        <h1 id="page-title">Student records are temporarily unavailable</h1>
        <p>We couldn't delete this student record. Check that MySQL is running in XAMPP, then try again.</p>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
}
