<?php
declare(strict_types=1);

$courses = studentCourses();
?>
<form class="student-form" action="<?= e($formAction ?? '') ?>" method="post">
    <input type="hidden" name="_csrf_token" value="<?= e(csrfToken()) ?>">

    <?php if (isset($errors['_form'])): ?>
        <div class="form-error-summary" role="alert"><?= e($errors['_form']) ?></div>
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-field">
            <label for="first_name">First name</label>
            <input id="first_name" name="first_name" type="text" maxlength="60" autocomplete="given-name"
                   value="<?= e($student['first_name']) ?>" required
                   <?= isset($errors['first_name']) ? 'aria-invalid="true" aria-describedby="first_name-error"' : '' ?>>
            <?php if (isset($errors['first_name'])): ?>
                <p class="field-error" id="first_name-error"><?= e($errors['first_name']) ?></p>
            <?php endif; ?>
        </div>
        <div class="form-field">
            <label for="last_name">Last name</label>
            <input id="last_name" name="last_name" type="text" maxlength="60" autocomplete="family-name"
                   value="<?= e($student['last_name']) ?>" required
                   <?= isset($errors['last_name']) ? 'aria-invalid="true" aria-describedby="last_name-error"' : '' ?>>
            <?php if (isset($errors['last_name'])): ?>
                <p class="field-error" id="last_name-error"><?= e($errors['last_name']) ?></p>
            <?php endif; ?>
        </div>
        <div class="form-field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" maxlength="120" autocomplete="email"
                   value="<?= e($student['email']) ?>" required
                   <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>>
            <?php if (isset($errors['email'])): ?>
                <p class="field-error" id="email-error"><?= e($errors['email']) ?></p>
            <?php endif; ?>
        </div>
        <div class="form-field">
            <label for="phone">Phone <span class="field-hint">(optional)</span></label>
            <input id="phone" name="phone" type="tel" maxlength="20" autocomplete="tel"
                   value="<?= e($student['phone']) ?>"
                   <?= isset($errors['phone']) ? 'aria-invalid="true" aria-describedby="phone-error"' : '' ?>>
            <?php if (isset($errors['phone'])): ?>
                <p class="field-error" id="phone-error"><?= e($errors['phone']) ?></p>
            <?php endif; ?>
        </div>
        <div class="form-field">
            <label for="course">Course</label>
            <select id="course" name="course" required
                    <?= isset($errors['course']) ? 'aria-invalid="true" aria-describedby="course-error"' : '' ?>>
                <option value="">Select a course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course) ?>" <?= $student['course'] === $course ? 'selected' : '' ?>>
                        <?= e($course) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['course'])): ?>
                <p class="field-error" id="course-error"><?= e($errors['course']) ?></p>
            <?php endif; ?>
        </div>
        <div class="form-field">
            <label for="enrolled_on">Enrollment date</label>
            <input id="enrolled_on" name="enrolled_on" type="date" max="<?= e((new DateTimeImmutable('today'))->format('Y-m-d')) ?>"
                   value="<?= e($student['enrolled_on']) ?>" required
                   <?= isset($errors['enrolled_on']) ? 'aria-invalid="true" aria-describedby="enrolled_on-error"' : '' ?>>
            <?php if (isset($errors['enrolled_on'])): ?>
                <p class="field-error" id="enrolled_on-error"><?= e($errors['enrolled_on']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-actions">
        <button class="button" type="submit"><?= e($submitLabel ?? 'Save student') ?></button>
        <a class="cancel-link" href="index.php">Cancel</a>
    </div>
</form>
