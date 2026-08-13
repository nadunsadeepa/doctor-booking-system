<?php
/**
 * admin/doctor_delete.php
 * -----------------------------------------------------------
 * Module 06 - Doctor Management
 * POST-only. doctor_schedules rows are NOT set to cascade in
 * the live schema, so we delete them explicitly first.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid request.');
    redirect('admin/doctors.php');
}

$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT doctor_name, photo FROM doctors WHERE id = :id");
$stmt->execute(['id' => $id]);
$doctor = $stmt->fetch();

if ($doctor) {
    try {
        $pdo->prepare("DELETE FROM doctor_schedules WHERE doctor_id = :id")->execute(['id' => $id]);
        $pdo->prepare("DELETE FROM doctors WHERE id = :id")->execute(['id' => $id]);

        if ($doctor['photo'] && file_exists(__DIR__ . '/../uploads/profile/' . $doctor['photo'])) {
            @unlink(__DIR__ . '/../uploads/profile/' . $doctor['photo']);
        }

        set_flash('success', 'Dr. ' . $doctor['doctor_name'] . ' was deleted.');
    } catch (PDOException $e) {
        // Likely blocked by the appointments foreign key (doctor has bookings)
        set_flash('danger', 'Cannot delete Dr. ' . $doctor['doctor_name'] . ' — they have existing appointments. Set status to Inactive instead.');
    }
} else {
    set_flash('danger', 'Doctor not found.');
}

redirect('admin/doctors.php');
