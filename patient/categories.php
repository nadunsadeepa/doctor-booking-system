<?php
/**
 * patient/categories.php
 * -----------------------------------------------------------
 * Module 09 - Booking System (step 1)
 * Patient picks their condition/disease category first, then
 * only doctors treating that category are shown.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_patient_login();

$categories = $pdo->query(
    "SELECT c.*, COUNT(d.id) AS doctor_count
     FROM disease_categories c
     LEFT JOIN doctors d ON d.specialization_id = c.id AND d.status = 'Active'
     WHERE c.status = 'Active'
     GROUP BY c.id
     ORDER BY c.category_name"
)->fetchAll();

$pageTitle   = 'Book Appointment';
$currentPage = 'categories';
require_once __DIR__ . '/../includes/patient_header.php';
?>
<head>
    <link href="../assets/css/patient.css" rel="stylesheet">
</head>
<!-- ====== DARK MODE TOGGLE ====== -->
<button class="dark-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
    <i class="fas fa-moon" id="toggleIcon"></i>
</button>

<div class="d-flex justify-content-between align-items-center mb-4 pt-4">
    <div>
        <h2 class="mb-1">What would you like to see a doctor about?</h2>
        <p class="text-muted mb-0">Choose a category to see available doctors.</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-secondary">Back</a>
</div>

<div class="row g-3">
    <?php foreach ($categories as $c): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="doctors.php?category_id=<?= $c['id'] ?>" class="text-decoration-none">
                <div class="stat-card text-center h-100">
                    <div style="font-size:1.6rem;" class="mb-1">🩺</div>
                    <div class="fw-bold" style="color:var(--text-primary);"><?= htmlspecialchars($c['category_name']) ?></div>
                    <div class="stat-label"><?= (int)$c['doctor_count'] ?> doctor<?= $c['doctor_count'] == 1 ? '' : 's' ?></div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<script>
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

<?php require_once __DIR__ . '/../includes/patient_footer.php'; ?>