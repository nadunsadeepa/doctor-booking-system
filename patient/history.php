<?php
/**
 * patient/history.php
 * -----------------------------------------------------------
 * Module 16 - Appointment History
 * Status + date-range filters. Each row links to a printable
 * slip (Module 17), and the whole filtered list can be printed
 * / saved as PDF via history_print.php.
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

// Carry the current filters into the print view's query string
$printQuery = http_build_query(['status' => $statusFilter, 'from_date' => $fromDate, 'to_date' => $toDate]);

function history_status_badge($status)
{
    $map = [
        'Pending'   => 'badge-danger-soft',
        'Confirmed' => 'badge-success-soft',
        'Completed' => 'badge-success-soft',
        'Cancelled' => 'badge-danger-soft',
    ];
    return '<span class="' . ($map[$status] ?? 'badge-success-soft') . '">' . htmlspecialchars($status) . '</span>';
}

$pageTitle   = 'Appointment History';
$currentPage = 'history';
require_once __DIR__ . '/../includes/patient_header.php';
?>

<div class="pt-4 pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h2 class="mb-0">Appointment History</h2>
    <a href="history_print.php?<?= htmlspecialchars($printQuery) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
        🖨️ Print / Download
    </a>
</div>

<div class="admin-panel mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-auto">
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-control">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                <?php foreach ($validStatuses as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small mb-1">From</label>
            <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate) ?>">
        </div>
        <div class="col-auto">
            <label class="form-label small mb-1">To</label>
            <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-clinic">Filter</button>
        </div>
        <?php if ($statusFilter !== 'all' || $fromDate || $toDate): ?>
            <div class="col-auto">
                <a href="history.php" class="btn btn-outline-secondary">Clear</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<div class="admin-panel">
    <?php if ($appointments): ?>
        <table class="admin-table">
            <thead><tr><th>Doctor</th><th>Hospital</th><th>Date</th><th>Queue #</th><th>Time</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($appointments as $a): ?>
                    <tr>
                        <td>Dr. <?= htmlspecialchars($a['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($a['hospital_name']) ?></td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($a['appointment_date']))) ?></td>
                        <td>#<?= (int)$a['queue_number'] ?></td>
                        <td><?= htmlspecialchars(date('h:i A', strtotime($a['appointment_time']))) ?></td>
                        <td><?= history_status_badge($a['status']) ?></td>
                        <td><a href="appointment_slip.php?id=<?= $a['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Slip</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-muted mb-0">No appointments found for the selected filters.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/patient_footer.php'; ?>
