<?php
/**
 * patient/register.php
 * -----------------------------------------------------------
 * Patient self-registration.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['patient_id'])) {
    redirect('patient/dashboard.php');
}

$errors = [];
$old = ['full_name' => '', 'nic' => '', 'dob' => '', 'gender' => '',
        'phone' => '', 'address' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $full_name = clean_input($_POST['full_name'] ?? '');
        $nic       = clean_input($_POST['nic'] ?? '');
        $dob       = clean_input($_POST['date_of_birth'] ?? '');  // form name still "date_of_birth"
        $gender    = clean_input($_POST['gender'] ?? '');         // expects "Male"/"Female"/"Other"
        $phone     = clean_input($_POST['phone'] ?? '');
        $address   = clean_input($_POST['address'] ?? '');
        $email     = clean_input($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';

        $old = compact('full_name', 'nic', 'dob', 'gender', 'phone', 'address', 'email');

        // ---- Validation ----
        if (strlen($full_name) < 3) {
            $errors[] = 'Please enter your full name (at least 3 characters).';
        }

        if (!preg_match('/^([0-9]{9}[vVxX]|[0-9]{12})$/', $nic)) {
            $errors[] = 'Please enter a valid NIC number (old: 9 digits + V/X, new: 12 digits).';
        }

        if ($dob === '' || strtotime($dob) === false || strtotime($dob) > time()) {
            $errors[] = 'Please enter a valid date of birth.';
        }

        if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {  // ✅ capitalized
            $errors[] = 'Please select your gender.';
        }

        if (!preg_match('/^[0-9]{9,15}$/', $phone)) {
            $errors[] = 'Please enter a valid phone number (digits only).';
        }

        if (strlen($address) < 5) {
            $errors[] = 'Please enter your address.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        // ---- Duplicate checks (only if basic validation passed) ----
        if (empty($errors)) {
            $check = $pdo->prepare(
                "SELECT id FROM patients WHERE email = :email OR nic = :nic LIMIT 1"
            );
            $check->execute(['email' => $email, 'nic' => $nic]);
            if ($check->fetch()) {
                $errors[] = 'An account with this email or NIC already exists. Try logging in instead.';
            }
        }

        // ---- Insert ----
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO patients
                        (full_name, nic, dob, gender, phone, address, email, password)
                     VALUES
                        (:full_name, :nic, :dob, :gender, :phone, :address, :email, :password)"
                );
                $stmt->execute([
                    'full_name' => $full_name,
                    'nic'       => $nic,
                    'dob'       => $dob,
                    'gender'    => $gender,
                    'phone'     => $phone,
                    'address'   => $address,
                    'email'     => $email,
                    'password'  => password_hash($password, PASSWORD_DEFAULT),
                ]);

                try {
                    create_notification(
                        $pdo,
                        (int)$pdo->lastInsertId(),
                        'Welcome!',
                        'Welcome to Doctor Booking System, ' . $full_name . '! You can now book appointments with our doctors.'
                    );
                } catch (Throwable $e) {
                    // Account is already created -- a notification hiccup shouldn't block signup.
                }

                set_flash('success', 'Account created! Please sign in.');
                redirect('patient/login.php');
            } catch (PDOException $e) {
                $errors[] = 'Could not create account: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration | Doctor Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
</head>
<body class="login-page">

    <div class="login-shell" style="max-width: 720px; grid-template-columns: 1fr;">
        <div class="login-form-area">
            <h2>Create your patient account</h2>
            <p class="subtitle">Takes less than a minute</p>

            <?php if ($errors): ?>
                <div class="alert-clinic-error">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="registerForm" method="post" action="register.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="full_name">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required
                               value="<?= htmlspecialchars($old['full_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="nic">NIC Number</label>
                        <input type="text" class="form-control" id="nic" name="nic" required
                               placeholder="200012345678 or 991234567V"
                               value="<?= htmlspecialchars($old['nic'] ?? '') ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="date_of_birth">Date of Birth</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required
                               max="<?= date('Y-m-d') ?>"
                               value="<?= htmlspecialchars($old['dob'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="gender">Gender</label>
                        <select class="form-control" id="gender" name="gender" required>
                            <option value="">Select</option>
                            <option value="Male"   <?= ($old['gender'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($old['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other"  <?= ($old['gender'] ?? '') === 'Other'  ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" required
                               placeholder="0771234567"
                               value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="address">Address</label>
                    <input type="text" class="form-control" id="address" name="address" required
                           value="<?= htmlspecialchars($old['address'] ?? '') ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password"
                               minlength="6" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="confirm_password">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                               minlength="6" required>
                        <div id="matchHint" class="form-text"></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-clinic mt-2">Create Account</button>
            </form>

            <div class="role-switch">
                Already have an account? <a href="login.php">Sign in</a>
            </div>
        </div>
    </div>

    <script src="../assets/js/register.js"></script>
</body>
</html>