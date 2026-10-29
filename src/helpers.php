<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url, true, 303);
    exit;
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'msg' => $msg,
    ];
}

/**
 * @return array{type: string, msg: string}|null
 */
function getFlash(): ?array
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);

    if (!isset($message['type'], $message['msg']) || !is_string($message['type']) || !is_string($message['msg'])) {
        return null;
    }

    return [
        'type' => $message['type'],
        'msg' => $message['msg'],
    ];
}

function csrfToken(): string
{
    if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function isValidCsrfToken(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['_csrf_token'])
        && is_string($_SESSION['_csrf_token'])
        && hash_equals($_SESSION['_csrf_token'], $token);
}

/**
 * @return list<string>
 */
function studentCourses(): array
{
    return [
        'Web Development',
        'Software Engineering',
        'Data Analysis',
        'UI/UX Design',
    ];
}

/**
 * @param array<string, string> $student
 * @return array<string, string>
 */
function validateStudent(array $student, PDO $connection, ?int $excludeId = null): array
{
    $errors = [];

    foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $field => $label) {
        if (preg_match('/^[\p{L} -]{2,60}$/u', $student[$field]) !== 1) {
            $errors[$field] = $label . ' must be 2–60 characters and contain only letters, spaces, or hyphens.';
        }
    }

    if (filter_var($student['email'], FILTER_VALIDATE_EMAIL) === false || strlen($student['email']) > 120) {
        $errors['email'] = 'Enter a valid email address (maximum 120 characters).';
    } else {
        $emailQuery = 'SELECT id FROM students WHERE email = :email';
        if ($excludeId !== null) {
            $emailQuery .= ' AND id <> :exclude_id';
        }
        $emailQuery .= ' LIMIT 1';

        $emailCheck = $connection->prepare($emailQuery);
        $parameters = ['email' => $student['email']];
        if ($excludeId !== null) {
            $parameters['exclude_id'] = $excludeId;
        }
        $emailCheck->execute($parameters);

        if ($emailCheck->fetchColumn() !== false) {
            $errors['email'] = 'A student with this email address already exists.';
        }
    }

    if ($student['phone'] !== '' && preg_match('/^0[789][01]\d{8}$/', $student['phone']) !== 1) {
        $errors['phone'] = 'Enter a valid phone number or leave this field blank.';
    }

    if (!in_array($student['course'], studentCourses(), true)) {
        $errors['course'] = 'Select a valid course.';
    }

    $today = new DateTimeImmutable('today');
    $enrolledOn = DateTimeImmutable::createFromFormat('!Y-m-d', $student['enrolled_on']);
    $dateErrors = DateTimeImmutable::getLastErrors();
    $validDate = $enrolledOn !== false
        && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
        && $enrolledOn->format('Y-m-d') === $student['enrolled_on'];

    if (!$validDate) {
        $errors['enrolled_on'] = 'Enter a valid enrollment date.';
    } elseif ($enrolledOn > $today) {
        $errors['enrolled_on'] = 'Enrollment date cannot be in the future.';
    }

    return $errors;
}

function isDuplicateEmailException(PDOException $exception): bool
{
    return $exception->getCode() === '23000'
        && isset($exception->errorInfo[1])
        && (int) $exception->errorInfo[1] === 1062;
}
