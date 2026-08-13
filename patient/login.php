<?php
/**
 * patient/login.php
 * -----------------------------------------------------------
 * Patient login form + login handling.
 * (Registration form comes in Module 04 - Registration System.)
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['patient_id'])) {
    redirect('patient/dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $email    = clean_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Please enter both email and password.';
        } else {
            $stmt = $pdo->prepare(
                "SELECT id, full_name, password, status
                 FROM patients WHERE email = :email LIMIT 1"
            );
            $stmt->execute(['email' => $email]);
            $patient = $stmt->fetch();

            $loginOk = $patient && $patient['status'] === 'active'
                       && password_verify($password, $patient['password']);

            $log = $pdo->prepare(
                "INSERT INTO login_logs (user_role, user_id, email, ip_address, status)
                 VALUES ('patient', :uid, :email, :ip, :status)"
            );
            $log->execute([
                'uid'    => $loginOk ? $patient['id'] : null,
                'email'  => $email,
                'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
                'status' => $loginOk ? 'success' : 'failed',
            ]);

            if ($loginOk) {
                session_regenerate_id(true);
                $_SESSION['patient_id']   = $patient['id'];
                $_SESSION['patient_name'] = $patient['full_name'];

                set_flash('success', 'Welcome back, ' . $patient['full_name'] . '!');
                redirect('patient/dashboard.php');
            } else {
                $error = 'Incorrect email or password.';
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
    <title>Patient Login | Doctor Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="../assets/css/login.css" rel="stylesheet">
</head>
<body>

    <!-- ====== DARK MODE TOGGLE ====== -->
    <button class="dark-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon" id="toggleIcon"></i>
    </button>

    <div class="login-shell">
        <!-- Left Panel -->
        <div class="login-side">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="role-tag">Patient Portal</div>
            <h1>Book the right doctor in a few clicks.</h1>
            <p>Sign in to choose your condition, pick a doctor and get your queue number by SMS.</p>
        </div>

        <!-- Right Panel -->
        <div class="login-form-area">
            <h2>Patient Sign In</h2>
            <p class="subtitle">Enter your credentials to continue</p>

            <?php if ($error): ?>
                <div class="alert-clinic-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form id="loginForm" method="post" action="login.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">

                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="you@example.com" required
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Enter your password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-clinic mt-2">Sign In</button>
            </form>

            <div class="role-switch">
                New here? <a href="register.php">Create an account</a><br>
                <a href="../admin/login.php">Admin Login</a>
                <span class="sep">|</span>
                <a href="../doctor/login.php">Doctor Login</a>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Dark mode toggle
        (function() {
            const toggleBtn = document.getElementById('darkModeToggle');
            const icon = document.getElementById('toggleIcon');
            const body = document.body;

            const savedMode = localStorage.getItem('darkMode');
            if (savedMode === 'enabled') {
                body.classList.add('dark-mode');
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }

            toggleBtn.addEventListener('click', function() {
                body.classList.toggle('dark-mode');
                const isDark = body.classList.contains('dark-mode');
                if (isDark) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                    localStorage.setItem('darkMode', 'enabled');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                    localStorage.setItem('darkMode', 'disabled');
                }
            });
        })();
    </script>
</body>
</html>