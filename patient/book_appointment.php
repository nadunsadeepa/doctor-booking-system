<?php
/**
 * patient/book_appointment.php
 * -----------------------------------------------------------
 * Module 09 - Booking System (core logic)
 * Module 10 (Queue Number Generator) is effectively implemented
 * right here, since "booking successful" is meaningless without
 * a queue number -- see the calculation below.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../sms/sms_helper.php';

require_patient_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid request. Please try again.');
    redirect('patient/categories.php');
}

$doctorId = (int)($_POST['doctor_id'] ?? 0);
$date     = $_POST['appointment_date'] ?? '';
$patientId = $_SESSION['patient_id'];

// ---- Basic validation ----
if ($doctorId <= 0 || $date === '' || strtotime($date) === false) {
    set_flash('danger', 'Please choose a valid date.');
    redirect('patient/doctor_profile.php?id=' . $doctorId);
}

if (strtotime($date) < strtotime(date('Y-m-d'))) {
    set_flash('danger', 'Please choose today or a future date.');
    redirect('patient/doctor_profile.php?id=' . $doctorId);
}

$doctorStmt = $pdo->prepare("SELECT * FROM doctors WHERE id = :id AND status = 'Active'");
$doctorStmt->execute(['id' => $doctorId]);
$doctor = $doctorStmt->fetch();

if (!$doctor) {
    set_flash('danger', 'This doctor is not available for booking.');
    redirect('patient/categories.php');
}

$dayName = date('l', strtotime($date)); // e.g. "Monday"

$scheduleStmt = $pdo->prepare(
    "SELECT * FROM doctor_schedules WHERE doctor_id = :id AND available_day = :day LIMIT 1"
);
$scheduleStmt->execute(['id' => $doctorId, 'day' => $dayName]);
$slot = $scheduleStmt->fetch();

if (!$slot) {
    set_flash('danger', 'Dr. ' . $doctor['doctor_name'] . ' is not available on ' . $dayName . '. Please pick another date.');
    redirect('patient/doctor_profile.php?id=' . $doctorId);
}

// ---- Prevent double-booking the same doctor on the same date ----
$dupStmt = $pdo->prepare(
    "SELECT id FROM appointments
     WHERE doctor_id = :doctor_id AND patient_id = :patient_id
       AND appointment_date = :date AND status != 'Cancelled' LIMIT 1"
);
$dupStmt->execute(['doctor_id' => $doctorId, 'patient_id' => $patientId, 'date' => $date]);
if ($dupStmt->fetch()) {
    set_flash('danger', 'You already have an appointment with this doctor on that date.');
    redirect('patient/doctor_profile.php?id=' . $doctorId);
}

try {
    $pdo->beginTransaction();

    // Lock existing rows for this doctor+date so two simultaneous
    // bookings can't both land on the same queue number.
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM appointments
         WHERE doctor_id = :doctor_id AND appointment_date = :date AND status != 'Cancelled'
         FOR UPDATE"
    );
    $countStmt->execute(['doctor_id' => $doctorId, 'date' => $date]);
    $bookedCount = (int)$countStmt->fetchColumn();

    if ($bookedCount >= (int)$slot['max_patients']) {
        $pdo->rollBack();
        set_flash('danger', 'Fully booked for ' . date('d M Y', strtotime($date)) . '. Please select another date.');
        redirect('patient/doctor_profile.php?id=' . $doctorId);
    }

    $queueNumber = $bookedCount + 1;
    $appointmentTime = date('H:i:s', strtotime($slot['start_time']) + $bookedCount * (int)$slot['appointment_duration'] * 60);

    $insert = $pdo->prepare(
        "INSERT INTO appointments (doctor_id, patient_id, appointment_date, queue_number, appointment_time, status)
         VALUES (:doctor_id, :patient_id, :date, :queue_number, :time, 'Confirmed')"
    );
    $insert->execute([
        'doctor_id'    => $doctorId,
        'patient_id'   => $patientId,
        'date'         => $date,
        'queue_number' => $queueNumber,
        'time'         => $appointmentTime,
    ]);
    $appointmentId = $pdo->lastInsertId();

    $pdo->commit();

    // ---- Side-effects (SMS + in-app notification) ----
    // The appointment is already safely committed above. Nothing
    // past this point should ever be able to break the booking or
    // stop the redirect -- an SMS/notification failure is not the
    // patient's problem, so every side-effect is isolated in its
    // own try/catch.
    try {
        $patientPhoneStmt = $pdo->prepare("SELECT phone FROM patients WHERE id = :id");
        $patientPhoneStmt->execute(['id' => $patientId]);
        $patientPhone = $patientPhoneStmt->fetchColumn();

        if ($patientPhone) {
            $smsText = build_booking_sms($doctor['doctor_name'], $queueNumber, $date, $appointmentTime);
            send_sms($pdo, $patientId, $patientPhone, $smsText);
        }
    } catch (Throwable $e) {
        // SMS is best-effort; swallow and move on.
    }

    try {
        create_notification(
            $pdo,
            $patientId,
            'Appointment Confirmed',
            'Dr. ' . $doctor['doctor_name'] . ' on ' . date('d M Y', strtotime($date))
                . ' at ' . date('h:i A', strtotime($appointmentTime)) . '. Queue No: ' . $queueNumber . '.'
        );
    } catch (Throwable $e) {
        // Same here -- never block the booking over a notification row.
    }

    redirect('patient/booking_success.php?id=' . $appointmentId);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'Something went wrong while booking. Please try again.');
    redirect('patient/doctor_profile.php?id=' . $doctorId);
}
