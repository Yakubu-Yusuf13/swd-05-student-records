<?php
declare(strict_types=1);

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$readOnly = appIsReadOnly();
$today = new DateTimeImmutable('today');
$student = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'course' => '',
    'enrolled_on' => $today->format('Y-m-d'),
];
$errors = [];

if ($readOnly) {
    http_response_code(403);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($student as $field => $default) {
        if (isset($_POST[$field]) && is_string($_POST[$field])) {
            $student[$field] = trim($_POST[$field]);
        }
    }

    if (!isValidCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(400);
        $errors['_form'] = 'Your form session has expired. Please refresh the page and try again.';
    }

    if ($errors === []) {
        try {
            $connection = db();
            $errors = validateStudent($student, $connection);
            if ($errors === []) {
                $insert = $connection->prepare(
                    'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
                     VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
                );
                $insert->execute([
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'],
                    'email' => $student['email'],
                    'phone' => $student['phone'] === '' ? null : $student['phone'],
                    'course' => $student['course'],
                    'enrolled_on' => $student['enrolled_on'],
                ]);

                flash('success', 'Student added');
                redirect('index.php');
            }
        } catch (PDOException $exception) {
            if (isDuplicateEmailException($exception)) {
                $errors['email'] = 'A student with this email address already exists.';
            } else {
                http_response_code(503);
                $errors['_form'] = 'The database is temporarily unavailable. Your entries are still here; try again when MySQL is running.';
            }
        }
    }
}

$formAction = 'create.php';
$submitLabel = 'Add student';

require __DIR__ . '/partials/header.php';
?>
<section aria-labelledby="page-title">
    <?php if ($readOnly): ?>
        <div class="error-state">
            <h1 id="page-title">Read-only demo</h1>
            <p>Adding students is disabled in this public demo.</p>
        </div>
    <?php else: ?>
        <div class="page-heading">
            <div>
                <h1 id="page-title">Add student</h1>
                <p class="subheading">Enter the student's details below.</p>
            </div>
        </div>
        <div class="card form-card">
            <?php require __DIR__ . '/partials/student-form.php'; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
