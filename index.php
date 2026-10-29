<?php
declare(strict_types=1);

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

try {
    $statement = db()->query(
        'SELECT id, first_name, last_name, email, course, enrolled_on
         FROM students
         ORDER BY last_name, first_name'
    );
    $students = $statement->fetchAll();
} catch (PDOException) {
    http_response_code(503);
    $students = null;
}

require __DIR__ . '/partials/header.php';
?>
<section aria-labelledby="page-title">
    <?php if ($students === null): ?>
        <div class="error-state">
            <h1 id="page-title">Student records are temporarily unavailable</h1>
            <p>We couldn't connect to the student database. Check that MySQL is running in XAMPP, then refresh this page.</p>
        </div>
    <?php else: ?>
        <div class="page-heading">
            <div>
                <h1 id="page-title">Students</h1>
                <p class="subheading">View and manage student records.</p>
            </div>
            <a class="button" href="create.php">Add student</a>
        </div>

        <?php if ($students === []): ?>
            <div class="card empty-state">
                <h2>No students yet</h2>
                <p>Add a student to get started.</p>
            </div>
        <?php else: ?>
            <div class="card table-wrap">
                <table class="student-table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Course</th>
                            <th scope="col">Enrolled</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td class="student-name">
                                    <?= e($student['first_name'] . ' ' . $student['last_name']) ?>
                                </td>
                                <td><a href="mailto:<?= e($student['email']) ?>"><?= e($student['email']) ?></a></td>
                                <td><?= e($student['course']) ?></td>
                                <td><?= e((new DateTimeImmutable($student['enrolled_on']))->format('d M Y')) ?></td>
                                <td>
                                    <div class="actions">
                                        <a class="action-link" href="edit.php?id=<?= e($student['id']) ?>">Edit</a>
                                        <form class="action-form" action="delete.php" method="post" onsubmit="return confirm('Delete this student record?');">
                                            <input type="hidden" name="id" value="<?= e($student['id']) ?>">
                                            <button class="action-button" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
