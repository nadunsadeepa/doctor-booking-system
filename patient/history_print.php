<?php
/**
 * patient/history_print.php
 * -----------------------------------------------------------
 * Module 17 - Reports Download
 * Printable version of history.php, honoring the same filters
 * (status / from_date / to_date) passed in the query string.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_patient_login();

$patientId = $_SESSION['patient_id'];
$statusFilter = $_GET['status'] ?? 'all';
$fromDate = $_GET['from_date'] ?? '';
$toDate   = $_GET['to_date'] ?? '';
$validStatuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

$sql = "SELECT a.*, d.doctor_name, d.hospital_name
        FROM appointments a
        JOIN doctors d ON d.id = a.doctor_id
        WHERE a.patient_id = :id";
$params = ['id' => $patientId];

if (in_array($statusFilter, $validStatuses, true)) {
    $sql .= " AND a.status = :status";
    $params['status'] = $statusFilter;
}
if ($fromDate !== '' && strtotime($fromDate) !== false) {
    $sql .= " AND a.appointment_date >= :from_date";
    $params['from_date'] = $fromDate;
}
if ($toDate !== '' && strtotime($toDate) !== false) {
    $sql .= " AND a.appointment_date <= :to_date";
    $params['to_date'] = $toDate;
}
$sql .= " ORDER BY a.appointment_date DESC, a.queue_number DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$patientStmt = $pdo->prepare("SELECT full_name, nic FROM patients WHERE id = :id");
$patientStmt->execute(['id' => $patientId]);
$patient = $patientStmt->fetch();

$filterLabel = 'All appointments';
if ($statusFilter !== 'all') $filterLabel = $statusFilter . ' appointments';
if ($fromDate || $toDate) {
    $filterLabel .= ' (' . ($fromDate ?: '...') . ' to ' . ($toDate ?: '...') . ')';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment History | Doctor Booking System</title>
    <link href="../assets/css/print.css" rel="stylesheet">
</head>
<body class="print-page">

    <div class="print-toolbar">
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>

    <div class="print-header">
        <h1>🏥 Doctor Booking System</h1>
        <div class="meta">
            Appointment History<br>
            <?= htmlspecialchars($patient['full_name']) ?> (NIC: <?= htmlspecialchars($patient['nic']) ?>)
        </div>
    </div>

    <p class="text-muted" style="color:#64807D;font-size:0.9rem;"><?= htmlspecialchars($filterLabel) ?> — <?= count($appointments) ?> record(s)</p>

    <?php if ($appointments): ?>
        <table class="print-list">
            <thead>
                <tr><th>Doctor</th><th>Hospital</th><th>Date</th><th>Queue #</th><th>Time</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $a): ?>
                    <tr>
                        <td>Dr. <?= htmlspecialchars($a['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($a['hospital_name']) ?></td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($a['appointment_date']))) ?></td>
                        <td>#<?= (int)$a['queue_number'] ?></td>
                        <td><?= htmlspecialchars(date('h:i A', strtotime($a['appointment_time']))) ?></td>
                        <td><?= htmlspecialchars($a['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No appointments found for the selected filters.</p>
    <?php endif; ?>

    <div class="print-note">
        Generated on <?= date('d M Y, h:i A') ?>.
    </div>

</body>
</html>
