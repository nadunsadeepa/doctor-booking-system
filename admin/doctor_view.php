<?php
/**
 * admin/doctor_view.php?id=X
 * -----------------------------------------------------------
 * Module 06 - Doctor Management
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT d.*, c.category_name
     FROM doctors d
     LEFT JOIN disease_categories c ON c.id = d.specialization_id
     WHERE d.id = :id"
);
$stmt->execute(['id' => $id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    set_flash('danger', 'Doctor not found.');
    redirect('admin/doctors.php');
}

$scheduleStmt = $pdo->prepare(
    "SELECT * FROM doctor_schedules WHERE doctor_id = :id
     ORDER BY FIELD(available_day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')"
);
$scheduleStmt->execute(['id' => $id]);
$schedule = $scheduleStmt->fetchAll();

$pageTitle   = 'Doctor Profile';
$currentPage = 'doctors';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-panel mb-3">
    <div class="d-flex gap-3 align-items-center mb-3">
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
            <div class="text-muted"><?= htmlspecialchars($doctor['category_name'] ?? 'No category') ?></div>
        </div>
        <div class="ms-auto">
            <?php if ($doctor['status'] === 'Active'): ?>
                <span class="badge-success-soft">Active</span>
            <?php else: ?>
                <span class="badge-danger-soft">Inactive</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4"><strong>Gender:</strong> <?= htmlspecialchars($doctor['gender']) ?></div>
        <div class="col-md-4"><strong>Hospital:</strong> <?= htmlspecialchars($doctor['hospital_name']) ?></div>
        <div class="col-md-4"><strong>Phone:</strong> <?= htmlspecialchars($doctor['phone']) ?></div>
        <div class="col-md-4"><strong>Email:</strong> <?= htmlspecialchars($doctor['email']) ?></div>
        <div class="col-md-4"><strong>Qualification:</strong> <?= htmlspecialchars($doctor['qualification']) ?></div>
        <div class="col-md-4"><strong>Experience:</strong> <?= (int)$doctor['experience'] ?> years</div>
        <div class="col-md-4"><strong>Room:</strong> <?= htmlspecialchars($doctor['room_no'] ?? '—') ?></div>
        <div class="col-md-4"><strong>Fee:</strong>
            <?= $doctor['consultation_fee'] !== null ? 'Rs. ' . number_format($doctor['consultation_fee'], 2) : '—' ?>
        </div>
    </div>
</div>

<div class="admin-panel">
    <h5>Weekly Schedule</h5>
    <?php if ($schedule): ?>
        <table class="admin-table">
            <thead><tr><th>Day</th><th>Start</th><th>End</th><th>Min / Patient</th><th>Max Patients</th></tr></thead>
            <tbody>
                <?php foreach ($schedule as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['available_day']) ?></td>
                        <td><?= htmlspecialchars(date('h:i A', strtotime($s['start_time']))) ?></td>
                        <td><?= htmlspecialchars(date('h:i A', strtotime($s['end_time']))) ?></td>
                        <td><?= (int)$s['appointment_duration'] ?> min</td>
                        <td><?= (int)$s['max_patients'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-muted mb-0">No schedule set.</p>
    <?php endif; ?>
</div>

<div class="mt-3">
    <a href="doctor_edit.php?id=<?= $id ?>" class="btn btn-outline-primary">Edit</a>
    <a href="doctors.php" class="btn btn-outline-secondary">Back to list</a>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
