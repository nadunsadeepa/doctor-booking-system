<?php
/**
 * generate_hash.php  (PROJECT ROOT)
 * -----------------------------------------------------------
 * Small one-time tool: type a plain-text password, get back
 * the bcrypt hash to paste into the database (via phpMyAdmin)
 * when manually creating test doctor/patient accounts.
 *
 * Open in browser:  http://localhost/doctor_booking_system/generate_hash.php
 *
 * SECURITY: delete this file once you're done setting up test
 * accounts. Never leave it on a live/production server.
 * -----------------------------------------------------------
 */
$hash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Hash Generator</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 60px auto; }
        input { padding: 8px; width: 250px; }
        button { padding: 8px 16px; }
        code { background: #f0f0f0; padding: 10px; display: block; word-break: break-all; margin-top: 15px; }
    </style>
</head>
<body>
    <h2>Password Hash Generator</h2>
    <form method="post">
        <input type="text" name="password" placeholder="Enter plain-text password" required>
        <button type="submit">Generate Hash</button>
    </form>
    <?php if ($hash): ?>
        <p>Copy this into the <code>password</code> column in phpMyAdmin:</p>
        <code><?= htmlspecialchars($hash) ?></code>
    <?php endif; ?>
</body>
</html>
