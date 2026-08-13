<?php
/**
 * patient/appointment_slip.php?id=X
 * -----------------------------------------------------------
 * Module 17 - Reports Download
 * A clean, printable appointment slip. "Print / Save as PDF"
 * uses the browser's native print dialog -- no PHP PDF library
 * needed, keeps the project dependency-free like everything else.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_patient_login();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT a.*, d.doctor_name, d.hospital_name, d.room_no, d.qualification, p.full_name, p.nic
     FROM appointments a
     JOIN doctors d ON d.id = a.doctor_id
     JOIN patients p ON p.id = a.patient_id
     WHERE a.id = :id AND a.patient_id = :pid"
);
$stmt->execute(['id' => $id, 'pid' => $_SESSION['patient_id']]);
$appt = $stmt->fetch();

if (!$appt) {
    redirect('patient/history.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Slip #<?= $appt['id'] ?> | Doctor Booking System</title>
    <link href="../assets/css/print.css" rel="stylesheet">
</head>
<body class="print-page">

    <div class="print-toolbar">
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>

    <div class="print-header">
        <h1>🏥 Doctor Booking System</h1>
        <div class="meta">
            Appointment Slip<br>
            Ref #<?= str_pad($appt['id'], 6, '0', STR_PAD_LEFT) ?>
        </div>
    </div>

    <table class="slip-table">
        <tr><th>Patient</th><td><?= htmlspecialchars($appt['full_name']) ?> (NIC: <?= htmlspecialchars($appt['nic']) ?>)</td></tr>
        <tr><th>Doctor</th><td>Dr. <?= htmlspecialchars($appt['doctor_name']) ?> — <?= htmlspecialchars($appt['qualification']) ?></td></tr>
        <tr><th>Hospital</th><td><?= htmlspecialchars($appt['hospital_name']) ?><?= $appt['room_no'] ? ' (Room ' . htmlspecialchars($appt['room_no']) . ')' : '' ?></td></tr>
        <tr><th>Date</th><td><?= htmlspecialchars(date('l, d M Y', strtotime($appt['appointment_date']))) ?></td></tr>
        <tr><th>Estimated Time</th><td><?= htmlspecialchars(date('h:i A', strtotime($appt['appointment_time']))) ?></td></tr>
        <tr><th>Queue Number</th><td><span class="queue-highlight">#<?= (int)$appt['queue_number'] ?></span></td></tr>
        <tr><th>Status</th><td><?= htmlspecialchars($appt['status']) ?></td></tr>
    </table>

    <div class="print-note">
        Please arrive at least 15 minutes before your estimated time. Queue numbers run on a first-come basis, so actual time may shift slightly.
        Generated on <?= date('d M Y, h:i A') ?>.
    </div>

</body>
</html>
