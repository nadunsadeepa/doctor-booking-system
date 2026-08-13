<?php
/**
 * patient/booking_success.php?id=X
 * -----------------------------------------------------------
 * Module 09 - Booking System (confirmation)
 * $id is an appointments.id -- verified to belong to the
 * logged-in patient before showing anything.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_patient_login();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT a.*, d.doctor_name, d.hospital_name, d.room_no, d.photo
     FROM appointments a
     JOIN doctors d ON d.id = a.doctor_id
     WHERE a.id = :id AND a.patient_id = :patient_id"
);
$stmt->execute(['id' => $id, 'patient_id' => $_SESSION['patient_id']]);
$appt = $stmt->fetch();

if (!$appt) {
    redirect('patient/categories.php');
}

$pageTitle   = 'Booking Confirmed';
$currentPage = 'categories';
require_once __DIR__ . '/../includes/patient_header.php';
?>

    <div class="admin-panel text-center mb-3 mt-4">
        <div style="font-size:2.5rem;">✅</div>
        <h3 class="mt-2 mb-1">Booking Confirmed!</h3>
        <p class="text-muted">You're all set. Details below.</p>
    </div>

    <div class="admin-panel">
        <table class="admin-table">
            <tbody>
                <tr><th style="width:40%;">Doctor</th><td>Dr. <?= htmlspecialchars($appt['doctor_name']) ?></td></tr>
                <tr><th>Hospital</th><td><?= htmlspecialchars($appt['hospital_name']) ?><?= $appt['room_no'] ? ' (Room ' . htmlspecialchars($appt['room_no']) . ')' : '' ?></td></tr>
                <tr><th>Date</th><td><?= htmlspecialchars(date('l, d M Y', strtotime($appt['appointment_date']))) ?></td></tr>
                <tr><th>Queue Number</th><td><span class="stat-number" style="font-size:1.4rem;">#<?= (int)$appt['queue_number'] ?></span></td></tr>
                <tr><th>Estimated Time</th><td><?= htmlspecialchars(date('h:i A', strtotime($appt['appointment_time']))) ?></td></tr>
                <tr><th>Status</th><td><span class="badge-success-soft"><?= htmlspecialchars($appt['status']) ?></span></td></tr>
            </tbody>
        </table>
        <div class="alert alert-warning mt-3 mb-0">
            Please arrive at least 15 minutes before your estimated time. Queue numbers run on a first-come basis, so actual time may shift slightly.
        </div>
    </div>

    <div class="text-center mt-3">
        <a href="queue_status.php?id=<?= $appt['id'] ?>" class="btn btn-clinic" style="width:auto;padding-inline:24px;">Track Queue</a>
        <a href="appointment_slip.php?id=<?= $appt['id'] ?>" target="_blank" class="btn btn-outline-secondary" style="width:auto;padding-inline:24px;">Download Slip</a>
        <a href="dashboard.php" class="btn btn-outline-secondary" style="width:auto;padding-inline:24px;">Go to Dashboard</a>
    </div>

<?php require_once __DIR__ . '/../includes/patient_footer.php'; ?>
