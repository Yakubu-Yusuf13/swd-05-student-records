<?php
declare(strict_types=1);

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$courses = studentCourses();
$search = isset($_GET['q']) && is_string($_GET['q'])
    ? substr(trim($_GET['q']), 0, 100)
    : '';
$selectedCourse = isset($_GET['course']) && is_string($_GET['course'])
    && in_array($_GET['course'], $courses, true)
        ? $_GET['course']
        : '';
$requestedPage = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$page = $requestedPage === false ? 1 : $requestedPage;
$perPage = 10;
$where = [];
$parameters = [];

if ($search !== '') {
    $where[] = '(first_name LIKE :first_name_search OR last_name LIKE :last_name_search OR email LIKE :email_search)';
    $searchPattern = '%' . $search . '%';
    $parameters['first_name_search'] = $searchPattern;
    $parameters['last_name_search'] = $searchPattern;
    $parameters['email_search'] = $searchPattern;
}

if ($selectedCourse !== '') {
    $where[] = 'course = :course';
    $parameters['course'] = $selectedCourse;
}

$whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
$students = null;
$totalStudents = 0;
$totalPages = 1;
$offset = 0;

try {
    $connection = db();
    $countStatement = $connection->prepare('SELECT COUNT(*) FROM students' . $whereSql);
    $countStatement->execute($parameters);
    $totalStudents = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($totalStudents / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $statement = $connection->prepare(
        'SELECT id, first_name, last_name, email, course, enrolled_on
         FROM students' . $whereSql . '
         ORDER BY last_name, first_name, id
         LIMIT :limit OFFSET :offset'
    );
    foreach ($parameters as $name => $value) {
        $statement->bindValue(':' . $name, $value, PDO::PARAM_STR);
    }
    $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();
    $students = $statement->fetchAll();
} catch (PDOException) {
    http_response_code(503);
}

$start = $totalStudents === 0 ? 0 : $offset + 1;
$end = min($offset + $perPage, $totalStudents);
$filterQuery = [];
if ($search !== '') {
    $filterQuery['q'] = $search;
}
if ($selectedCourse !== '') {
    $filterQuery['course'] = $selectedCourse;
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

        <form class="card student-filters" action="index.php" method="get">
            <div class="filter-field">
                <label for="q">Search students</label>
                <input id="q" name="q" type="search" value="<?= e($search) ?>" placeholder="Name or email">
            </div>
            <div class="filter-field">
                <label for="course">Course</label>
                <select id="course" name="course">
                    <option value="">All courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= e($course) ?>" <?= $selectedCourse === $course ? 'selected' : '' ?>>
                            <?= e($course) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-actions">
                <button class="button" type="submit">Search</button>
                <?php if ($search !== '' || $selectedCourse !== ''): ?>
                    <a class="cancel-link" href="index.php">Clear filters</a>
                <?php endif; ?>
            </div>
        </form>

        <p class="results-summary" role="status">
            Showing <?= e($start) ?>–<?= e($end) ?> of <?= e($totalStudents) ?>
            <?= $totalStudents === 1 ? 'student' : 'students' ?>
        </p>

        <?php if ($students === []): ?>
            <div class="card empty-state">
                <h2><?= $totalStudents === 0 && ($search !== '' || $selectedCourse !== '') ? 'No matching students' : 'No students yet' ?></h2>
                <p><?= $search !== '' || $selectedCourse !== '' ? 'Try changing or clearing your filters.' : 'Add a student to get started.' ?></p>
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
                                        <a class="action-link" href="show.php?id=<?= e($student['id']) ?>">View</a>
                                        <a class="action-link" href="edit.php?id=<?= e($student['id']) ?>">Edit</a>
                                        <form class="action-form" action="delete.php" method="post"
                                              onsubmit="return confirm(<?= e(json_encode(
                                                  'Delete ' . $student['first_name'] . ' ' . $student['last_name'] . '?',
                                                  JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                                              )) ?>);">
                                            <input type="hidden" name="id" value="<?= e($student['id']) ?>">
                                            <input type="hidden" name="_csrf_token" value="<?= e(csrfToken()) ?>">
                                            <button class="action-button" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Student list pages">
                    <?php if ($page > 1): ?>
                        <?php $previousUrl = 'index.php?' . http_build_query($filterQuery + ['page' => $page - 1]); ?>
                        <a class="page-link" href="<?= e($previousUrl) ?>" rel="prev">Previous</a>
                    <?php endif; ?>

                    <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                        <?php $pageUrl = 'index.php?' . http_build_query($filterQuery + ['page' => $pageNumber]); ?>
                        <?php if ($pageNumber === $page): ?>
                            <span class="page-link current-page" aria-current="page"><?= e($pageNumber) ?></span>
                        <?php else: ?>
                            <a class="page-link" href="<?= e($pageUrl) ?>"><?= e($pageNumber) ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <?php $nextUrl = 'index.php?' . http_build_query($filterQuery + ['page' => $page + 1]); ?>
                        <a class="page-link" href="<?= e($nextUrl) ?>" rel="next">Next</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
