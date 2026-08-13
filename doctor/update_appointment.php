<?php
/**
 * doctor/update_appointment.php
 * -----------------------------------------------------------
 * Module 08 - Doctor Dashboard
 * POST-only. A doctor can only update appointments that belong
 * to them (enforced via the WHERE clause below).
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../sms/sms_helper.php';

require_doctor_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid request.');
    redirect('doctor/dashboard.php');
}

$appointmentId = (int)($_POST['appointment_id'] ?? 0);
$newStatus     = $_POST['new_status'] ?? '';

if (!in_array($newStatus, ['Completed', 'Cancelled'], true)) {
    set_flash('danger', 'Invalid status.');
    redirect('doctor/dashboard.php');
}

$stmt = $pdo->prepare(
    "UPDATE appointments SET status = :status
     WHERE id = :id AND doctor_id = :doctor_id"
);
$stmt->execute([
    'status'     => $newStatus,
    'id'         => $appointmentId,
    'doctor_id'  => $_SESSION['doctor_id'],
]);

if ($stmt->rowCount() > 0) {
    if ($newStatus === 'Cancelled') {
        try {
            $info = $pdo->prepare(
                "SELECT p.id AS patient_id, p.phone, d.doctor_name, a.appointment_date
                 FROM appointments a
                 JOIN patients p ON p.id = a.patient_id
                 JOIN doctors d ON d.id = a.doctor_id
                 WHERE a.id = :id"
            );
            $info->execute(['id' => $appointmentId]);
            $row = $info->fetch();
            if ($row && $row['phone']) {
                $msg = "Your appointment with Dr. {$row['doctor_name']} on "
                     . date('d/m/Y', strtotime($row['appointment_date']))
                     . " has been cancelled by the doctor. Please book another slot.";
                send_sms($pdo, $row['patient_id'], $row['phone'], $msg);
            }
            if ($row) {
                create_notification(
                    $pdo,
                    (int)$row['patient_id'],
                    'Appointment Cancelled',
                    'Your appointment with Dr. ' . $row['doctor_name'] . ' on '
                        . date('d M Y', strtotime($row['appointment_date'])) . ' was cancelled by the doctor.'
                );
            }
        } catch (Throwable $e) {
            // Status update already succeeded -- SMS/notification are best-effort.
        }
    }
    set_flash('success', 'Appointment marked as ' . $newStatus . '.');
} else {
    set_flash('danger', 'Appointment not found.');
}

redirect('doctor/dashboard.php');
