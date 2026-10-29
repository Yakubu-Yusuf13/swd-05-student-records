<?php
declare(strict_types=1);

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$student = null;
$databaseUnavailable = false;

if ($id === false || $id === null) {
    http_response_code(404);
} else {
    try {
        $statement = db()->prepare(
            'SELECT id, first_name, last_name, email, phone, course, enrolled_on, created_at, updated_at
             FROM students
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $student = $statement->fetch();

        if ($student === false) {
            http_response_code(404);
            $student = null;
        }
    } catch (PDOException) {
        http_response_code(503);
        $databaseUnavailable = true;
    }
}

require __DIR__ . '/partials/header.php';
?>
<section aria-labelledby="page-title">
    <?php if ($databaseUnavailable): ?>
        <div class="error-state">
            <h1 id="page-title">Student records are temporarily unavailable</h1>
            <p>We couldn't load this student record. Check that MySQL is running in XAMPP, then refresh this page.</p>
        </div>
    <?php elseif ($student === null): ?>
        <div class="error-state">
            <h1 id="page-title">Student not found</h1>
            <p>The requested student record does not exist.</p>
        </div>
    <?php else: ?>
        <div class="page-heading">
            <div>
                <h1 id="page-title"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h1>
                <p class="subheading">Student record details.</p>
            </div>
            <div class="actions">
                <a class="button" href="edit.php?id=<?= e($student['id']) ?>">Edit student</a>
                <a class="cancel-link" href="index.php">Back to students</a>
            </div>
        </div>
        <dl class="card student-details">
            <div class="detail-item">
                <dt>Email</dt>
                <dd><a href="mailto:<?= e($student['email']) ?>"><?= e($student['email']) ?></a></dd>
            </div>
            <div class="detail-item">
                <dt>Phone</dt>
                <dd><?= $student['phone'] === null ? 'Not provided' : e($student['phone']) ?></dd>
            </div>
            <div class="detail-item">
                <dt>Course</dt>
                <dd><?= e($student['course']) ?></dd>
            </div>
            <div class="detail-item">
                <dt>Enrolled</dt>
                <dd><?= e((new DateTimeImmutable($student['enrolled_on']))->format('d M Y')) ?></dd>
            </div>
            <div class="detail-item">
                <dt>Record created</dt>
                <dd><?= e((new DateTimeImmutable($student['created_at']))->format('d M Y, H:i')) ?></dd>
            </div>
            <div class="detail-item">
                <dt>Last updated</dt>
                <dd><?= e((new DateTimeImmutable($student['updated_at']))->format('d M Y, H:i')) ?></dd>
            </div>
        </dl>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
