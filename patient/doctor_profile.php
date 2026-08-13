<?php
/**
 * patient/doctor_profile.php?id=X
 * -----------------------------------------------------------
 * Module 09 - Booking System (step 3)
 * Shows the doctor's profile + weekly schedule and lets the
 * patient pick a date. The actual slot check + queue number
 * happens in book_appointment.php on submit.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_patient_login();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT d.*, c.category_name
     FROM doctors d
     LEFT JOIN disease_categories c ON c.id = d.specialization_id
     WHERE d.id = :id AND d.status = 'Active'"
);
$stmt->execute(['id' => $id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    set_flash('danger', 'Doctor not found or not currently available.');
    redirect('patient/categories.php');
}

$scheduleStmt = $pdo->prepare(
    "SELECT * FROM doctor_schedules WHERE doctor_id = :id
     ORDER BY FIELD(available_day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')"
);
$scheduleStmt->execute(['id' => $id]);
$schedule = $scheduleStmt->fetchAll();
$availableDays = array_column($schedule, 'available_day');

$errors = get_flash(); // book_appointment.php redirects back here with a flash on failure

$pageTitle   = 'Dr. ' . $doctor['doctor_name'];
$currentPage = 'categories';
require_once __DIR__ . '/../includes/patient_header.php';
?>

    <a href="doctors.php?category_id=<?= (int)$doctor['specialization_id'] ?>" class="btn btn-outline-secondary btn-sm mt-4 mb-3">← Back</a>

    <?php if ($errors): ?>
        <div class="alert alert-<?= $errors['type'] === 'success' ? 'success' : 'danger' ?>">
            <?= htmlspecialchars($errors['message']) ?>
        </div>
    <?php endif; ?>

    <div class="admin-panel mb-3">
        <div class="d-flex gap-3 align-items-center">
            <?php if ($doctor['photo']): ?>
                <img src="../uploads/profile/<?= htmlspecialchars($doctor['photo']) ?>"
                     width="72" height="72" style="border-radius:50%;object-fit:cover;">
            <?php else: ?>
                <div class="admin-avatar" style="width:72px;height:72px;font-size:1.5rem;">
                    <?= htmlspecialchars(strtoupper(substr($doctor['doctor_name'], 0, 1))) ?>
                </div>
            <?php endif; ?>
            <div>
                <h4 class="mb-0">Dr. <?= htmlspecialchars($doctor['doctor_name']) ?></h4>
                <div class="text-muted"><?= htmlspecialchars($doctor['category_name'] ?? '') ?> · <?= htmlspecialchars($doctor['qualification']) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($doctor['hospital_name']) ?>
                    <?= $doctor['room_no'] ? ' · Room ' . htmlspecialchars($doctor['room_no']) : '' ?>
                </div>
            </div>
            <?php if ($doctor['consultation_fee'] !== null): ?>
                <div class="ms-auto text-end">
                    <div class="stat-number" style="font-size:1.3rem;">Rs. <?= number_format($doctor['consultation_fee'], 2) ?></div>
                    <div class="stat-label">Consultation Fee</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-panel mb-3">
        <h5>Weekly Schedule</h5>
        <?php if ($schedule): ?>
            <table class="admin-table">
                <thead><tr><th>Day</th><th>Time</th><th>Max Patients / Day</th></tr></thead>
                <tbody>
                    <?php foreach ($schedule as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['available_day']) ?></td>
                            <td><?= htmlspecialchars(date('h:i A', strtotime($s['start_time']))) ?> – <?= htmlspecialchars(date('h:i A', strtotime($s['end_time']))) ?></td>
                            <td><?= (int)$s['max_patients'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted mb-0">This doctor has no schedule set yet — booking is unavailable.</p>
        <?php endif; ?>
    </div>

    <?php if ($schedule): ?>
        <div class="admin-panel">
            <h5>Book an Appointment</h5>
            <form method="post" action="book_appointment.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input type="hidden" name="doctor_id" value="<?= $id ?>">

                <div class="mb-3">
                    <label class="form-label">Choose a date</label>
                    <input type="date" name="appointment_date" id="appointment_date" class="form-control"
                           min="<?= date('Y-m-d') ?>" required style="max-width:240px;">
                    <div class="form-text">
                        Available on: <?= htmlspecialchars(implode(', ', $availableDays)) ?>
                    </div>
                    <div id="dayWarning" class="alert-clinic-error mt-2" style="display:none;max-width:400px;">
                        The doctor is not available on that day. Please pick a different date.
                    </div>
                </div>

                <button type="submit" class="btn btn-clinic" style="width:auto;padding-inline:24px;">Book Now</button>
            </form>
        </div>
    <?php endif; ?>

<script>
// Client-side hint only -- the real check happens on the server.
var availableDays = <?= json_encode($availableDays) ?>;
var dateInput = document.getElementById('appointment_date');
var warning = document.getElementById('dayWarning');

dateInput.addEventListener('change', function () {
    var d = new Date(this.value + 'T00:00:00');
    var dayName = d.toLocaleDateString('en-US', { weekday: 'long' });
    warning.style.display = availableDays.includes(dayName) ? 'none' : 'block';
});
</script>

<?php require_once __DIR__ . '/../includes/patient_footer.php'; ?>
