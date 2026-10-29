<?php
declare(strict_types=1);

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$student = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'course' => '',
    'enrolled_on' => '',
];
$errors = [];
$notFound = $id === false || $id === null;
$databaseUnavailable = false;
$connection = null;

if ($notFound) {
    http_response_code(404);
} else {
    try {
        $connection = db();
        $statement = $connection->prepare(
            'SELECT first_name, last_name, email, phone, course, enrolled_on
             FROM students
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $existingStudent = $statement->fetch();

        if ($existingStudent === false) {
            http_response_code(404);
            $notFound = true;
        } else {
            $student = array_map(
                static fn(mixed $value): string => $value === null ? '' : (string) $value,
                $existingStudent
            );
        }
    } catch (PDOException) {
        http_response_code(503);
        $databaseUnavailable = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$notFound && !$databaseUnavailable) {
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
            $errors = validateStudent($student, $connection, $id);
            if ($errors === []) {
                $update = $connection->prepare(
                    'UPDATE students
                     SET first_name = :first_name,
                         last_name = :last_name,
                         email = :email,
                         phone = :phone,
                         course = :course,
                         enrolled_on = :enrolled_on
                     WHERE id = :id'
                );
                $update->execute([
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'],
                    'email' => $student['email'],
                    'phone' => $student['phone'] === '' ? null : $student['phone'],
                    'course' => $student['course'],
                    'enrolled_on' => $student['enrolled_on'],
                    'id' => $id,
                ]);

                flash('success', 'Student updated');
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

$formAction = 'edit.php?id=' . (int) $id;
$submitLabel = 'Save changes';

require __DIR__ . '/partials/header.php';
?>
<section aria-labelledby="page-title">
    <?php if ($notFound): ?>
        <div class="error-state">
            <h1 id="page-title">Student not found</h1>
            <p>The requested student record does not exist.</p>
        </div>
    <?php elseif ($databaseUnavailable): ?>
        <div class="error-state">
            <h1 id="page-title">Student records are temporarily unavailable</h1>
            <p>We couldn't load this student record. Check that MySQL is running in XAMPP, then refresh this page.</p>
        </div>
    <?php else: ?>
        <div class="page-heading">
            <div>
                <h1 id="page-title">Edit student</h1>
                <p class="subheading">Update the student's details below.</p>
            </div>
        </div>
        <div class="card form-card">
            <?php require __DIR__ . '/partials/student-form.php'; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
