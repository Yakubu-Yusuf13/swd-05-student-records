<?php
declare(strict_types=1);

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';
$readOnly = appIsReadOnly();
$inserted = 0;
$alreadyPresent = 0;
$databaseUnavailable = false;

if ($readOnly) {
    http_response_code(403);
} elseif ($confirmed) {
    $names = [
        ['Chinedu', 'Okonkwo'],
        ['Amina', 'Bello'],
        ['Emeka', 'Nwosu'],
        ['Zainab', 'Abdullahi'],
        ['Tunde', 'Adeleke'],
        ['Ngozi', 'Eze'],
        ['Musa', 'Ibrahim'],
        ['Folake', 'Akinyemi'],
        ['Ifeanyi', 'Obi'],
        ['Hauwa', 'Sani'],
        ['Chiamaka', 'Okafor'],
        ['Babatunde', 'Fashola'],
        ['Fatima', 'Yusuf'],
        ['Obinna', 'Eze'],
        ['Kemi', 'Adeyemi'],
        ['Seyi', 'Oluwaseun'],
        ['Adaeze', 'Nnamdi'],
        ['Ibrahim', 'Garba'],
        ['Yetunde', 'Balogun'],
        ['Uche', 'Madu'],
        ['Aisha', 'Mohammed'],
        ['Kayode', 'Ogunleye'],
        ['Chisom', 'Umeh'],
        ['Maryam', 'Lawal'],
        ['Damilola', 'Adebayo'],
        ['Oluwaseyi', 'Ajayi'],
        ['Nneka', 'Ekwueme'],
        ['Abubakar', 'Isah'],
        ['Temitope', 'Ojo'],
        ['Chukwudi', 'Ezenwa'],
        ['Bisi', 'Oladipo'],
        ['Hadiza', 'Musa'],
        ['Somto', 'Onyeka'],
        ['Olumide', 'Bamidele'],
        ['Amaka', 'Ibe'],
        ['Sadiq', 'Bello'],
        ['Funmilayo', 'Ogun'],
        ['Kelechi', 'Anyanwu'],
        ['Rashida', 'Tanko'],
        ['Tosin', 'Olawale'],
        ['Nnamdi', 'Ezeani'],
        ['Grace', 'Danladi'],
        ['Adewale', 'Ogunbiyi'],
        ['Chidera', 'Nwachukwu'],
        ['Safiya', 'Usman'],
        ['Femi', 'Akinola'],
        ['Ogechi', 'Nwankwo'],
        ['Yusuf', 'Abiola'],
        ['Bisola', 'Ogunleye'],
        ['Samuel', 'Onwuka'],
    ];
    $courses = studentCourses();
    $startDate = new DateTimeImmutable('2023-01-01');
    $today = new DateTimeImmutable('today');
    $dateRange = (int) $startDate->diff($today)->format('%a');

    try {
        $connection = db();
        $check = $connection->prepare('SELECT id FROM students WHERE email = :email LIMIT 1');
        $insert = $connection->prepare(
            'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
             VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
        );
        $connection->beginTransaction();

        foreach ($names as $index => [$firstName, $lastName]) {
            $number = $index + 1;
            $email = sprintf('seed-%03d@students.example.test', $number);
            $check->execute(['email' => $email]);
            if ($check->fetchColumn() !== false) {
                $alreadyPresent++;
                continue;
            }

            $enrolledOn = $startDate->modify('+' . (($index * 37) % ($dateRange + 1)) . ' days');
            $insert->execute([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => '0' . [7, 8, 9][$index % 3] . [0, 1][$index % 2] . sprintf('%08d', $number),
                'course' => $courses[$index % count($courses)],
                'enrolled_on' => $enrolledOn->format('Y-m-d'),
            ]);
            $inserted++;
        }

        $connection->commit();
    } catch (PDOException) {
        if (isset($connection) && $connection instanceof PDO && $connection->inTransaction()) {
            $connection->rollBack();
        }
        http_response_code(503);
        $databaseUnavailable = true;
    }
}

require __DIR__ . '/partials/header.php';
?>
<section aria-labelledby="page-title">
    <div class="page-heading">
        <div>
            <h1 id="page-title">Seed test students</h1>
            <p class="subheading">Add realistic fake Nigerian student records for testing pagination.</p>
        </div>
        <a class="cancel-link" href="index.php">Back to students</a>
    </div>

    <?php if ($databaseUnavailable): ?>
        <div class="error-state" role="alert">
            <h2>Student records are temporarily unavailable</h2>
            <p>We couldn't add the test students. Check that MySQL is running in XAMPP, then try again.</p>
        </div>
    <?php elseif ($readOnly): ?>
        <div class="error-state" role="status">
            <h2>Seeding is disabled</h2>
            <p>Seed records cannot be added while the app is in read-only demo mode.</p>
        </div>
    <?php elseif ($confirmed): ?>
        <div class="card empty-state">
            <h2>Seed complete</h2>
            <p><?= e($inserted) ?> students added. <?= e($alreadyPresent) ?> seed records were already present.</p>
            <p><a class="button" href="index.php">View students</a></p>
        </div>
    <?php else: ?>
        <div class="card empty-state">
            <h2>Confirmation required</h2>
            <p>This will add up to 50 fake students. Existing seed records will not be duplicated.</p>
            <p><a class="button" href="seed.php?confirm=yes">Add 50 test students</a></p>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
