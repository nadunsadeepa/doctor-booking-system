<?php
/**
 * patient/profile.php
 * -----------------------------------------------------------
 * Module 13 - Patient Dashboard
 * Editable: full_name, phone, address, password.
 * Read-only: NIC, email, DOB, gender (identity fields — kept
 * stable on purpose; changing these would need admin support
 * in a real system).
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_patient_login();

$patientId = $_SESSION['patient_id'];

$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
$stmt->execute(['id' => $patientId]);
$patient = $stmt->fetch();

$errors = [];
$old = ['full_name' => $patient['full_name'], 'phone' => $patient['phone'], 'address' => $patient['address']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $full_name = clean_input($_POST['full_name'] ?? '');
        $phone     = clean_input($_POST['phone'] ?? '');
        $address   = clean_input($_POST['address'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $currentPassword = $_POST['current_password'] ?? '';

        $old = ['full_name' => $full_name, 'phone' => $phone, 'address' => $address];

        if (strlen($full_name) < 3)                  $errors[] = 'Please enter your full name.';
        if (!preg_match('/^[0-9]{9,15}$/', $phone))  $errors[] = 'Please enter a valid phone number.';
        if (strlen($address) < 5)                     $errors[] = 'Please enter your address.';

        $changingPassword = $newPassword !== '' || $confirmPassword !== '';
        if ($changingPassword) {
            if (!password_verify($currentPassword, $patient['password'])) {
                $errors[] = 'Current password is incorrect.';
            } elseif (strlen($newPassword) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New passwords do not match.';
            }
        }

        if (empty($errors)) {
            $sql = "UPDATE patients SET full_name = :full_name, phone = :phone, address = :address";
            $params = ['full_name' => $full_name, 'phone' => $phone, 'address' => $address, 'id' => $patientId];

            if ($changingPassword) {
                $sql .= ", password = :password";
                $params['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            $sql .= " WHERE id = :id";

            $pdo->prepare($sql)->execute($params);
            $_SESSION['patient_name'] = $full_name;

            set_flash('success', 'Profile updated.');
            redirect('patient/profile.php');
        }
    }
}

$pageTitle   = 'My Profile';
$currentPage = 'profile';
require_once __DIR__ . '/../includes/patient_header.php';
?>

<div class="pt-4 pb-2">
    <h2 class="mb-0">My Profile</h2>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="admin-panel mb-3">
    <div class="row g-3">
        <div class="col-md-4"><strong>NIC:</strong> <?= htmlspecialchars($patient['nic']) ?></div>
        <div class="col-md-4"><strong>Email:</strong> <?= htmlspecialchars($patient['email']) ?></div>
        <div class="col-md-4"><strong>Gender:</strong> <?= htmlspecialchars($patient['gender']) ?></div>
        <div class="col-md-4"><strong>Date of Birth:</strong> <?= htmlspecialchars(date('d M Y', strtotime($patient['dob']))) ?></div>
        <div class="col-md-8"><strong>Member since:</strong> <?= htmlspecialchars(date('d M Y', strtotime($patient['created_at']))) ?></div>
    </div>
    <div class="form-text mt-2">NIC and email are identity fields and can't be changed here — contact the clinic admin if these need to be corrected.</div>
</div>

<div class="admin-panel mb-3">
    <h5>Edit Details</h5>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" required
                       value="<?= htmlspecialchars($old['full_name']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" required
                       value="<?= htmlspecialchars($old['phone']) ?>">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control" required
                   value="<?= htmlspecialchars($old['address']) ?>">
        </div>

        <hr class="my-4">
        <h6 class="fw-bold mb-3">Change Password <span class="text-muted fw-normal small">(optional)</span></h6>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" minlength="6">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" minlength="6">
            </div>
        </div>

        <button type="submit" class="btn btn-clinic mt-2" style="width:auto;padding-inline:24px;">Save Changes</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/patient_footer.php'; ?>
