<?php
/**
 * includes/functions.php
 * -----------------------------------------------------------
 * Shared helper functions used across the whole project.
 * -----------------------------------------------------------
 */

/**
 * Clean user input (trim, strip tags, escape).
 */
function clean_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Redirect to a given path (relative to BASE_URL) and stop execution.
 */
function redirect($path)
{
    header("Location: " . BASE_URL . $path);
    exit();
}

/**
 * Generate & store a CSRF token in session, return it.
 */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a submitted CSRF token against the session token.
 */
function csrf_verify($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Show a one-time flash message (stored in session, printed once, then cleared).
 */
function set_flash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash()
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * ---------- Session guards ----------
 * Call the matching function at the very top of every
 * protected page (after app_config.php is included).
 */
function require_admin_login()
{
    if (empty($_SESSION['admin_id'])) {
        redirect('admin/login.php');
    }
}

function require_doctor_login()
{
    if (empty($_SESSION['doctor_id'])) {
        redirect('doctor/login.php');
    }
}

function require_patient_login()
{
    if (empty($_SESSION['patient_id'])) {
        redirect('patient/login.php');
    }
}

/**
 * Handle a doctor profile photo upload.
 * Returns the stored filename (to save in the DB) on success,
 * null if no file was chosen, or throws an Exception on error.
 *
 * @param array  $file      A single item from $_FILES, e.g. $_FILES['photo']
 * @param string $uploadDir Absolute filesystem path to uploads/profile/
 */
/**
 * Create an in-app notification for a patient (separate from SMS —
 * shows up in patient/notifications.php).
 */
function create_notification(PDO $pdo, int $patientId, string $title, string $message): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO notifications (patient_id, title, message, status) VALUES (:id, :title, :message, 'Unread')"
    );
    $stmt->execute(['id' => $patientId, 'title' => $title, 'message' => $message]);
}

/**
 * Handle a medical report upload (image or PDF).
 * Returns the stored filename on success, throws Exception on error.
 */
function upload_medical_report(array $file, string $uploadDir): string
{
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        throw new Exception('Please choose a file to upload.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload failed. Please try again.');
    }

    $allowed = ['jpg' => true, 'jpeg' => true, 'png' => true, 'pdf' => true];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!isset($allowed[$ext])) {
        throw new Exception('Only JPG, PNG and PDF files are allowed.');
    }

    if ($file['size'] > 8 * 1024 * 1024) { // 8MB limit
        throw new Exception('File must be smaller than 8MB.');
    }

    // Verify the file content actually matches its extension (not just renamed).
    if ($ext === 'pdf') {
        $header = file_get_contents($file['tmp_name'], false, null, 0, 5);
        if ($header !== '%PDF-') {
            throw new Exception('The uploaded file is not a valid PDF.');
        }
    } else {
        if (@getimagesize($file['tmp_name']) === false) {
            throw new Exception('The uploaded file is not a valid image.');
        }
    }

    $filename = 'report_' . bin2hex(random_bytes(8)) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
        throw new Exception('Could not save the uploaded file.');
    }

    return $filename;
}

function upload_doctor_photo(array $file, string $uploadDir): ?string
{
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // nothing chosen, that's OK (e.g. on edit, keep old photo)
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Photo upload failed. Please try again.');
    }

    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!array_key_exists($ext, $allowed)) {
        throw new Exception('Only JPG and PNG photos are allowed.');
    }

    if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
        throw new Exception('Photo must be smaller than 2MB.');
    }

    // Verify it's actually an image (not just a renamed file)
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception('The uploaded file is not a valid image.');
    }

    $filename = 'doctor_' . bin2hex(random_bytes(8)) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
        throw new Exception('Could not save the uploaded photo.');
    }

    return $filename;
}
