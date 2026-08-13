<?php
/**
 * patient/doctors.php?category_id=X
 * -----------------------------------------------------------
 * Module 09 - Booking System (step 2)
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_patient_login();

$categoryId = (int)($_GET['category_id'] ?? 0);

$catStmt = $pdo->prepare("SELECT * FROM disease_categories WHERE id = :id AND status = 'Active'");
$catStmt->execute(['id' => $categoryId]);
$category = $catStmt->fetch();

if (!$category) {
    redirect('patient/categories.php');
}

$stmt = $pdo->prepare(
    "SELECT * FROM doctors WHERE specialization_id = :cat AND status = 'Active' ORDER BY doctor_name"
);
$stmt->execute(['cat' => $categoryId]);
$doctors = $stmt->fetchAll();

// Grab each doctor's available days in one go for a quick summary
$daysByDoctor = [];
if ($doctors) {
    $ids = array_column($doctors, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $sStmt = $pdo->prepare(
        "SELECT doctor_id, available_day FROM doctor_schedules WHERE doctor_id IN ($in)
         ORDER BY FIELD(available_day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')"
    );
    $sStmt->execute($ids);
    foreach ($sStmt->fetchAll() as $row) {
        $daysByDoctor[$row['doctor_id']][] = substr($row['available_day'], 0, 3);
    }
}

$pageTitle   = $category['category_name'] . ' Doctors';
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
        <h2 class="mb-1"><?= htmlspecialchars($category['category_name']) ?> Doctors</h2>
        <p class="text-muted mb-0">Pick a doctor to see their schedule and book a slot.</p>
    </div>
    <a href="categories.php" class="btn btn-outline-secondary">Back</a>
</div>

<?php if ($doctors): ?>
    <div class="row g-3">
        <?php foreach ($doctors as $d): ?>
            <div class="col-md-6">
                <div class="admin-panel d-flex gap-3">
                    <?php if ($d['photo']): ?>
                        <img src="../uploads/profile/<?= htmlspecialchars($d['photo']) ?>"
                             width="64" height="64" style="border-radius:50%;object-fit:cover;flex-shrink:0;">
                    <?php else: ?>
                        <div class="admin-avatar" style="width:64px;height:64px;font-size:1.3rem;flex-shrink:0;background:var(--bg-body);color:var(--text-primary);display:flex;align-items:center;justify-content:center;border-radius:50%;">
                            <?= htmlspecialchars(strtoupper(substr($d['doctor_name'], 0, 1))) ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex-grow-1">
                        <h5 class="mb-0">Dr. <?= htmlspecialchars($d['doctor_name']) ?></h5>
                        <div class="text-muted small mb-1"><?= htmlspecialchars($d['qualification']) ?> · <?= (int)$d['experience'] ?> yrs exp.</div>
                        <div class="small mb-2"><?= htmlspecialchars($d['hospital_name']) ?></div>
                        <div class="small mb-2">
                            <?= !empty($daysByDoctor[$d['id']]) ? htmlspecialchars(implode(', ', $daysByDoctor[$d['id']])) : 'No schedule set' ?>
                        </div>
                        <a href="doctor_profile.php?id=<?= $d['id'] ?>" class="btn btn-clinic" style="width:auto;padding-inline:20px;">Book Appointment</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="admin-panel"><p class="text-muted mb-0">No doctors available in this category right now.</p></div>
<?php endif; ?>

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