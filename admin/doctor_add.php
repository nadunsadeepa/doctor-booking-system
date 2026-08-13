<?php
/**
 * admin/doctor_add.php
 * -----------------------------------------------------------
 * Module 06 - Doctor Management
 * Matches the live schema: doctors.specialization_id (FK),
 * doctor_schedules (available_day, appointment_duration,
 * max_patients).
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

$categories = $pdo->query(
    "SELECT id, category_name FROM disease_categories WHERE status = 'Active' ORDER BY category_name"
)->fetchAll();

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

$errors = [];
$old = [
    'doctor_name' => '', 'gender' => '', 'specialization_id' => '', 'hospital_name' => '',
    'phone' => '', 'email' => '', 'qualification' => '', 'experience' => '',
    'room_no' => '', 'consultation_fee' => '',
    'start_time' => '', 'end_time' => '', 'appointment_duration' => '10', 'max_patients' => '',
    'available_days' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $doctor_name          = clean_input($_POST['doctor_name'] ?? '');
        $gender                = clean_input($_POST['gender'] ?? '');
        $specialization_id     = (int)($_POST['specialization_id'] ?? 0);
        $hospital_name          = clean_input($_POST['hospital_name'] ?? '');
        $phone                  = clean_input($_POST['phone'] ?? '');
        $email                  = clean_input($_POST['email'] ?? '');
        $qualification          = clean_input($_POST['qualification'] ?? '');
        $experience             = (int)($_POST['experience'] ?? -1);
        $room_no                = clean_input($_POST['room_no'] ?? '');
        $consultation_fee_raw   = trim($_POST['consultation_fee'] ?? '');
        $start_time             = $_POST['start_time'] ?? '';
        $end_time               = $_POST['end_time'] ?? '';
        $appointment_duration   = (int)($_POST['appointment_duration'] ?? 0);
        $max_patients           = (int)($_POST['max_patients'] ?? 0);
        $available_days         = $_POST['available_days'] ?? [];
        $password               = $_POST['password'] ?? '';
        $confirm                = $_POST['confirm_password'] ?? '';

        $old = compact('doctor_name', 'gender', 'specialization_id', 'hospital_name', 'phone', 'email',
                        'qualification', 'experience', 'room_no', 'available_days',
                        'start_time', 'end_time', 'appointment_duration', 'max_patients');
        $old['consultation_fee'] = $consultation_fee_raw;

        if (strlen($doctor_name) < 3)                         $errors[] = 'Please enter the doctor\'s name.';
        if (!in_array($gender, ['Male', 'Female'], true))     $errors[] = 'Please select a gender.';
        if ($specialization_id <= 0)                           $errors[] = 'Please select a disease category.';
        if ($hospital_name === '')                             $errors[] = 'Please enter the hospital name.';
        if (!preg_match('/^[0-9]{9,15}$/', $phone))           $errors[] = 'Please enter a valid phone number.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))       $errors[] = 'Please enter a valid email address.';
        if ($qualification === '')                             $errors[] = 'Please enter qualifications.';
        if ($experience < 0)                                    $errors[] = 'Please enter years of experience.';
        if ($consultation_fee_raw !== '' && !is_numeric($consultation_fee_raw)) {
            $errors[] = 'Consultation fee must be a number.';
        }
        if (empty($available_days))                            $errors[] = 'Please select at least one available day.';
        if ($start_time === '' || $end_time === '')           $errors[] = 'Please enter start and end time.';
        elseif ($start_time >= $end_time)                     $errors[] = 'End time must be after start time.';
        if ($appointment_duration <= 0)                         $errors[] = 'Please enter minutes per patient (e.g. 10).';
        if ($max_patients <= 0)                                 $errors[] = 'Max patients per day must be a positive number.';
        if (strlen($password) < 6)                              $errors[] = 'Password must be at least 6 characters.';
        elseif ($password !== $confirm)                         $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $check = $pdo->prepare("SELECT id FROM doctors WHERE email = :email LIMIT 1");
            $check->execute(['email' => $email]);
            if ($check->fetch()) {
                $errors[] = 'A doctor with this email already exists.';
            }
        }

        $photoFilename = null;
        if (empty($errors) && !empty($_FILES['photo']['name'])) {
            try {
                $photoFilename = upload_doctor_photo($_FILES['photo'], __DIR__ . '/../uploads/profile');
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "INSERT INTO doctors
                        (doctor_name, gender, specialization_id, hospital_name, phone, email, qualification,
                         experience, room_no, consultation_fee, photo, password, status)
                     VALUES
                        (:doctor_name, :gender, :specialization_id, :hospital_name, :phone, :email, :qualification,
                         :experience, :room_no, :consultation_fee, :photo, :password, 'Active')"
                );
                $stmt->execute([
                    'doctor_name'        => $doctor_name,
                    'gender'             => $gender,
                    'specialization_id'  => $specialization_id,
                    'hospital_name'      => $hospital_name,
                    'phone'              => $phone,
                    'email'              => $email,
                    'qualification'      => $qualification,
                    'experience'         => $experience,
                    'room_no'            => $room_no ?: null,
                    'consultation_fee'   => $consultation_fee_raw !== '' ? $consultation_fee_raw : null,
                    'photo'              => $photoFilename,
                    'password'           => password_hash($password, PASSWORD_DEFAULT),
                ]);
                $doctorId = $pdo->lastInsertId();

                $scheduleStmt = $pdo->prepare(
                    "INSERT INTO doctor_schedules
                        (doctor_id, available_day, start_time, end_time, appointment_duration, max_patients)
                     VALUES
                        (:doctor_id, :day, :start_time, :end_time, :duration, :max_patients)"
                );
                foreach ($available_days as $day) {
                    $scheduleStmt->execute([
                        'doctor_id'    => $doctorId,
                        'day'          => $day,
                        'start_time'   => $start_time,
                        'end_time'     => $end_time,
                        'duration'     => $appointment_duration,
                        'max_patients' => $max_patients,
                    ]);
                }

                $pdo->commit();

                set_flash('success', 'Dr. ' . $doctor_name . ' was added successfully.');
                redirect('admin/doctors.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Could not save doctor. Please try again.';
            }
        }
    }
}

$pageTitle   = 'Add Doctor';
$currentPage = 'doctors';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="admin-panel">
    <form method="post" action="doctor_add.php" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Doctor Name</label>
                <input type="text" name="doctor_name" class="form-control" required
                       value="<?= htmlspecialchars($old['doctor_name']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Photo</label>
                <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-control" required>
                    <option value="">Select</option>
                    <option value="Male"   <?= $old['gender'] === 'Male'   ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $old['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Disease Category</label>
                <select name="specialization_id" class="form-control" required>
                    <option value="">Select</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (string)$old['specialization_id'] === (string)$c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Hospital</label>
                <input type="text" name="hospital_name" class="form-control" required
                       value="<?= htmlspecialchars($old['hospital_name']) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" required
                       value="<?= htmlspecialchars($old['phone']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Email (used to log in)</label>
                <input type="email" name="email" class="form-control" required
                       value="<?= htmlspecialchars($old['email']) ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Qualification</label>
                <input type="text" name="qualification" class="form-control" required
                       placeholder="MBBS, MD (Cardiology)"
                       value="<?= htmlspecialchars($old['qualification']) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Experience (years)</label>
                <input type="number" min="0" name="experience" class="form-control" required
                       value="<?= htmlspecialchars((string)$old['experience']) ?>">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Room No</label>
                <input type="text" name="room_no" class="form-control"
                       value="<?= htmlspecialchars($old['room_no']) ?>">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Fee (Rs.)</label>
                <input type="text" name="consultation_fee" class="form-control"
                       value="<?= htmlspecialchars($old['consultation_fee']) ?>">
            </div>
        </div>

        <hr class="my-4">
        <h6 class="fw-bold mb-3">Schedule</h6>

        <div class="mb-3">
            <label class="form-label d-block">Available Days</label>
            <?php foreach ($days as $day): ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="available_days[]"
                           id="day_<?= $day ?>" value="<?= $day ?>"
                           <?= in_array($day, $old['available_days'], true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="day_<?= $day ?>"><?= $day ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">Starting Time</label>
                <input type="time" name="start_time" class="form-control" required
                       value="<?= htmlspecialchars($old['start_time']) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Ending Time</label>
                <input type="time" name="end_time" class="form-control" required
                       value="<?= htmlspecialchars($old['end_time']) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Minutes / Patient</label>
                <input type="number" min="1" name="appointment_duration" class="form-control" required
                       value="<?= htmlspecialchars((string)$old['appointment_duration']) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Max Patients / Day</label>
                <input type="number" min="1" name="max_patients" class="form-control" required
                       value="<?= htmlspecialchars((string)$old['max_patients']) ?>">
            </div>
        </div>
        <div class="form-text mb-3">
            This same time range, minutes-per-patient and max-patients limit applies to every day ticked above.
        </div>

        <hr class="my-4">
        <h6 class="fw-bold mb-3">Login Credentials</h6>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" minlength="6" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" minlength="6" required>
            </div>
        </div>

        <button type="submit" class="btn btn-clinic mt-2" style="width:auto;padding-inline:24px;">Save Doctor</button>
        <a href="doctors.php" class="btn btn-outline-secondary mt-2">Cancel</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
