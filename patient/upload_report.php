<?php
/**
 * patient/upload_report.php
 * -----------------------------------------------------------
 * Module 14 - Medical Report Upload
 * Stores files in uploads/reports/ and adds a row to
 * `medical_reports` with ai_result left NULL -- Module 15
 * (AI Report Scanner) will fill that column in later.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_patient_login();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        try {
            $filename = upload_medical_report($_FILES['report_file'] ?? [], __DIR__ . '/../uploads/reports');

            $stmt = $pdo->prepare(
                "INSERT INTO medical_reports (patient_id, report_image) VALUES (:pid, :file)"
            );
            $stmt->execute(['pid' => $_SESSION['patient_id'], 'file' => $filename]);

            try {
                create_notification(
                    $pdo,
                    $_SESSION['patient_id'],
                    'Report Uploaded',
                    'Your medical report was uploaded successfully. It will be screened shortly.'
                );
            } catch (Throwable $e) {
                // best-effort only
            }

            set_flash('success', 'Report uploaded successfully.');
            redirect('patient/reports.php');
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle   = 'Upload Medical Report';
$currentPage = 'reports';
require_once __DIR__ . '/../includes/patient_header.php';
?>

<div class="pt-4 pb-2">
    <h2 class="mb-0">Upload Medical Report</h2>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="admin-panel" style="max-width:560px;">
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

        <div class="mb-3">
            <label class="form-label">Report File</label>
            <input type="file" name="report_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
            <div class="form-text">JPG, PNG or PDF. Max 8MB.</div>
        </div>

        <button type="submit" class="btn btn-clinic" style="width:auto;padding-inline:24px;">Upload</button>
        <a href="reports.php" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/patient_footer.php'; ?>
