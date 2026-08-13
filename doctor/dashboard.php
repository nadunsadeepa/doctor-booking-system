<?php
/**
 * doctor/dashboard.php
 * -----------------------------------------------------------
 * Module 08 - Doctor Dashboard
 * Reads from the `appointments` table (created ahead of time in
 * your live schema) joined with `patients`.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_doctor_login();

$doctorId = $_SESSION['doctor_id'];

$today = $pdo->prepare(
    "SELECT a.*, p.full_name, p.phone
     FROM appointments a
     JOIN patients p ON p.id = a.patient_id
     WHERE a.doctor_id = :id AND a.appointment_date = CURDATE()
     ORDER BY a.queue_number ASC"
);
$today->execute(['id' => $doctorId]);
$todayAppointments = $today->fetchAll();

$upcoming = $pdo->prepare(
    "SELECT a.*, p.full_name, p.phone
     FROM appointments a
     JOIN patients p ON p.id = a.patient_id
     WHERE a.doctor_id = :id AND a.appointment_date > CURDATE()
       AND a.status IN ('Pending','Confirmed')
     ORDER BY a.appointment_date ASC, a.queue_number ASC"
);
$upcoming->execute(['id' => $doctorId]);
$upcomingAppointments = $upcoming->fetchAll();

$completed = $pdo->prepare(
    "SELECT a.*, p.full_name, p.phone
     FROM appointments a
     JOIN patients p ON p.id = a.patient_id
     WHERE a.doctor_id = :id AND a.status = 'Completed'
     ORDER BY a.appointment_date DESC, a.queue_number ASC
     LIMIT 30"
);
$completed->execute(['id' => $doctorId]);
$completedAppointments = $completed->fetchAll();

$cancelled = $pdo->prepare(
    "SELECT a.*, p.full_name, p.phone
     FROM appointments a
     JOIN patients p ON p.id = a.patient_id
     WHERE a.doctor_id = :id AND a.status = 'Cancelled'
     ORDER BY a.appointment_date DESC, a.queue_number ASC
     LIMIT 30"
);
$cancelled->execute(['id' => $doctorId]);
$cancelledAppointments = $cancelled->fetchAll();

$completedTodayStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM appointments
     WHERE doctor_id = :id AND appointment_date = CURDATE() AND status = 'Completed'"
);
$completedTodayStmt->execute(['id' => $doctorId]);
$nowServing = (int)$completedTodayStmt->fetchColumn() + 1;

$flash = get_flash();

function status_badge($status)
{
    $map = [
        'Pending'   => 'badge-danger-soft',   // needs attention
        'Confirmed' => 'badge-success-soft',
        'Completed' => 'badge-success-soft',
        'Cancelled' => 'badge-danger-soft',
    ];
    $class = $map[$status] ?? 'badge-success-soft';
    return '<span class="' . $class . '">' . htmlspecialchars($status) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | Doctor Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-wrapper">
    <div class="admin-main" style="width:100%;">
        <div class="admin-topbar">
            <h1>Doctor Dashboard</h1>
            <div class="admin-user">
                <div class="admin-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['doctor_name'], 0, 1))) ?></div>
                <span>Dr. <?= htmlspecialchars($_SESSION['doctor_name']) ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger ms-2">Logout</a>
            </div>
        </div>

        <div class="admin-content">

            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>

            <?php if ($todayAppointments): ?>
                <div class="admin-panel mb-3 text-center">
                    <div class="text-muted small">Now Serving</div>
                    <div class="stat-number" style="font-size:2rem;">#<?= $nowServing ?></div>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= count($todayAppointments) ?></div>
                        <div class="stat-label">Today's Patients</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= count($upcomingAppointments) ?></div>
                        <div class="stat-label">Upcoming</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= count($completedAppointments) ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= count($cancelledAppointments) ?></div>
                        <div class="stat-label">Cancelled</div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-today">Today's Patients</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-upcoming">Upcoming</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-completed">Completed</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-cancelled">Cancelled</button></li>
            </ul>

            <div class="tab-content">

                <!-- Today's Patients -->
                <div class="tab-pane fade show active" id="tab-today">
                    <div class="admin-panel">
                        <?php if ($todayAppointments): ?>
                            <table class="admin-table">
                                <thead><tr><th>Queue #</th><th>Time</th><th>Patient</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach ($todayAppointments as $a): ?>
                                        <tr>
                                            <td><strong>#<?= (int)$a['queue_number'] ?></strong></td>
                                            <td><?= htmlspecialchars(date('h:i A', strtotime($a['appointment_time']))) ?></td>
                                            <td><?= htmlspecialchars($a['full_name']) ?></td>
                                            <td><?= htmlspecialchars($a['phone']) ?></td>
                                            <td><?= status_badge($a['status']) ?></td>
                                            <td class="text-nowrap">
                                                <?php if (in_array($a['status'], ['Pending','Confirmed'], true)): ?>
                                                    <?= appointment_action_buttons($a['id']) ?>
                                                <?php else: ?>—<?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted mb-0">No patients booked for today.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming -->
                <div class="tab-pane fade" id="tab-upcoming">
                    <div class="admin-panel">
                        <?php if ($upcomingAppointments): ?>
                            <table class="admin-table">
                                <thead><tr><th>Date</th><th>Queue #</th><th>Time</th><th>Patient</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach ($upcomingAppointments as $a): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('d M Y', strtotime($a['appointment_date']))) ?></td>
                                            <td>#<?= (int)$a['queue_number'] ?></td>
                                            <td><?= htmlspecialchars(date('h:i A', strtotime($a['appointment_time']))) ?></td>
                                            <td><?= htmlspecialchars($a['full_name']) ?></td>
                                            <td><?= status_badge($a['status']) ?></td>
                                            <td class="text-nowrap"><?= appointment_action_buttons($a['id']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted mb-0">No upcoming appointments.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Completed -->
                <div class="tab-pane fade" id="tab-completed">
                    <div class="admin-panel">
                        <?php if ($completedAppointments): ?>
                            <table class="admin-table">
                                <thead><tr><th>Date</th><th>Queue #</th><th>Patient</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($completedAppointments as $a): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('d M Y', strtotime($a['appointment_date']))) ?></td>
                                            <td>#<?= (int)$a['queue_number'] ?></td>
                                            <td><?= htmlspecialchars($a['full_name']) ?></td>
                                            <td><?= status_badge($a['status']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted mb-0">No completed appointments yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cancelled -->
                <div class="tab-pane fade" id="tab-cancelled">
                    <div class="admin-panel">
                        <?php if ($cancelledAppointments): ?>
                            <table class="admin-table">
                                <thead><tr><th>Date</th><th>Queue #</th><th>Patient</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($cancelledAppointments as $a): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('d M Y', strtotime($a['appointment_date']))) ?></td>
                                            <td>#<?= (int)$a['queue_number'] ?></td>
                                            <td><?= htmlspecialchars($a['full_name']) ?></td>
                                            <td><?= status_badge($a['status']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted mb-0">No cancelled appointments.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Hidden POST form used by the action buttons above -->
<form id="statusForm" action="update_appointment.php" method="post" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
    <input type="hidden" name="appointment_id" id="statusAppointmentId">
    <input type="hidden" name="new_status" id="statusNewStatus">
</form>

<script>
function setAppointmentStatus(id, status) {
    if (status === 'Cancelled' && !confirm('Cancel this appointment?')) return;
    document.getElementById('statusAppointmentId').value = id;
    document.getElementById('statusNewStatus').value = status;
    document.getElementById('statusForm').submit();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
/**
 * Small helper kept local to this page: renders the
 * Complete / Cancel buttons for a given appointment id.
 */
function appointment_action_buttons($id)
{
    return '<button type="button" class="btn btn-sm btn-outline-success" onclick="setAppointmentStatus(' . $id . ', \'Completed\')">Mark Completed</button> '
         . '<button type="button" class="btn btn-sm btn-outline-danger" onclick="setAppointmentStatus(' . $id . ', \'Cancelled\')">Cancel</button>';
}
