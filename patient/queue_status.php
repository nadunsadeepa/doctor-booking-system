<?php
/**
 * patient/queue_status.php?id=X
 * -----------------------------------------------------------
 * Module 10 - Queue Number Generator (live tracker)
 * The actual queue-number MATH already happens in
 * book_appointment.php (Module 09) -- this page just shows a
 * real-time view of it: how many patients are ahead of you and
 * roughly how long the wait is, based on how many appointments
 * the doctor has already completed today.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_patient_login();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT a.*, d.doctor_name, d.hospital_name
     FROM appointments a
     JOIN doctors d ON d.id = a.doctor_id
     WHERE a.id = :id AND a.patient_id = :patient_id"
);
$stmt->execute(['id' => $id, 'patient_id' => $_SESSION['patient_id']]);
$appt = $stmt->fetch();

if (!$appt) {
    redirect('patient/dashboard.php');
}

// How many ahead of me have already been completed today for this doctor?
$completedStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM appointments
     WHERE doctor_id = :doctor_id AND appointment_date = :date AND status = 'Completed'"
);
$completedStmt->execute(['doctor_id' => $appt['doctor_id'], 'date' => $appt['appointment_date']]);
$completedCount = (int)$completedStmt->fetchColumn();
$nowServing = $completedCount + 1;

// Minutes per patient, for the wait estimate
$slotStmt = $pdo->prepare(
    "SELECT appointment_duration FROM doctor_schedules
     WHERE doctor_id = :id AND available_day = :day LIMIT 1"
);
$slotStmt->execute(['id' => $appt['doctor_id'], 'day' => date('l', strtotime($appt['appointment_date']))]);
$duration = (int)($slotStmt->fetchColumn() ?: 10);

$peopleAhead = max(0, $appt['queue_number'] - $nowServing);
$estimatedWaitMin = $peopleAhead * $duration;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Status | Doctor Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <meta http-equiv="refresh" content="30">
</head>
<body class="admin-body">
<div class="admin-content" style="max-width:520px;margin:0 auto;padding-top:40px;">

    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-3">← Back</a>

    <div class="admin-panel text-center mb-3">
        <div class="text-muted small">Dr. <?= htmlspecialchars($appt['doctor_name']) ?> · <?= htmlspecialchars($appt['hospital_name']) ?></div>
        <div class="text-muted small mb-3"><?= htmlspecialchars(date('l, d M Y', strtotime($appt['appointment_date']))) ?></div>

        <?php if ($appt['status'] === 'Completed'): ?>
            <div style="font-size:2rem;">✅</div>
            <h4 class="mt-2">Your visit is complete</h4>
        <?php elseif ($appt['status'] === 'Cancelled'): ?>
            <div style="font-size:2rem;">✖️</div>
            <h4 class="mt-2 text-danger">Appointment Cancelled</h4>
        <?php else: ?>
            <div class="row g-3">
                <div class="col-6">
                    <div class="stat-card">
                        <div class="stat-number">#<?= (int)$nowServing ?></div>
                        <div class="stat-label">Now Serving</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card">
                        <div class="stat-number">#<?= (int)$appt['queue_number'] ?></div>
                        <div class="stat-label">Your Number</div>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <?php if ($peopleAhead <= 0): ?>
                    <h5 class="text-success mb-0">You're up next!</h5>
                <?php else: ?>
                    <h5 class="mb-0"><?= $peopleAhead ?> patient<?= $peopleAhead === 1 ? '' : 's' ?> ahead of you</h5>
                    <p class="text-muted mb-0">Estimated wait: ~<?= $estimatedWaitMin ?> min</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <p class="text-muted small text-center">This page refreshes automatically every 30 seconds.</p>

</div>
</body>
</html>
