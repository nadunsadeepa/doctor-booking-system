<?php
/**
 * patient/reports.php
 * -----------------------------------------------------------
 * Module 13/14 - Patient Dashboard / Medical Report Upload
 * AI screening (ai_result column) is Module 15 -- this page
 * already reads it so it'll "just work" once that's added.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_patient_login();

$stmt = $pdo->prepare(
    "SELECT * FROM medical_reports WHERE patient_id = :id ORDER BY uploaded_at DESC"
);
$stmt->execute(['id' => $_SESSION['patient_id']]);
$reports = $stmt->fetchAll();

$flash = get_flash();

function classification_badge($aiResult)
{
    if (!$aiResult || !preg_match('/Classification:\s*(.+)/i', $aiResult, $m)) {
        return null;
    }
    $c = trim($m[1]);
    $classes = [
        'Normal'                    => 'badge-success-soft',
        'Needs Doctor Review'       => 'badge-warning-soft',
        'Abnormal Values Detected'  => 'badge-danger-soft',
    ];
    $class = $classes[$c] ?? 'badge-success-soft';
    return '<span class="' . $class . '">' . htmlspecialchars($c) . '</span>';
}

function classification_details($aiResult)
{
    // Strip the "Classification: X" line, show the rest (summary + disclaimer)
    $lines = preg_split('/\r\n|\r|\n/', trim($aiResult));
    $rest = array_filter($lines, fn($l) => !preg_match('/^Classification:/i', $l));
    return nl2br(htmlspecialchars(trim(implode("\n", $rest))));
}

$pageTitle   = 'Medical Reports';
$currentPage = 'reports';
require_once __DIR__ . '/../includes/patient_header.php';
?>
<head>
    <link href="../assets/css/patient_report.css" rel="stylesheet">
</head>
<!-- ====== DARK MODE TOGGLE ====== -->
<button class="dark-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
    <i class="fas fa-moon" id="toggleIcon"></i>
</button>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> mt-3">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="pt-4 pb-2 d-flex justify-content-between align-items-center">
    <h2 class="mb-0">Medical Reports</h2>
    <a href="upload_report.php" class="btn btn-clinic" style="width:auto;padding-inline:20px;">+ Upload Report</a>
</div>

<div class="alert alert-warning">
    <strong>Note:</strong> AI screening is a highlighting tool only — it does not diagnose. Every result should be reviewed by a doctor.
</div>

<div class="admin-panel">
    <?php if ($reports): ?>
        <table class="admin-table">
            <thead><tr><th></th><th>Uploaded</th><th>File</th><th>AI Screening Result</th></tr></thead>
            <tbody>
                <?php foreach ($reports as $r): ?>
                    <?php
                        $isPdf = $r['report_image'] && str_ends_with(strtolower($r['report_image']), '.pdf');
                    ?>
                    <tr>
                        <td>
                            <?php if ($r['report_image'] && !$isPdf): ?>
                                <img src="../uploads/reports/<?= htmlspecialchars($r['report_image']) ?>"
                                     width="40" height="40" style="object-fit:cover;border-radius:6px;">
                            <?php elseif ($isPdf): ?>
                                <div style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:var(--bg-body);border-radius:6px;color:var(--text-secondary);">📄</div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(date('d M Y, h:i A', strtotime($r['uploaded_at']))) ?></td>
                        <td>
                            <?php if ($r['report_image']): ?>
                                <a href="../uploads/reports/<?= htmlspecialchars($r['report_image']) ?>" target="_blank">
                                    View <?= $isPdf ? 'PDF' : 'image' ?>
                                </a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['ai_result']): ?>
                                <?= classification_badge($r['ai_result']) ?? '' ?>
                                <div class="small text-muted mt-1"><?= classification_details($r['ai_result']) ?></div>
                            <?php elseif ($isPdf): ?>
                                <span class="text-muted small">AI screening supports images only</span>
                            <?php else: ?>
                                <form method="post" action="screen_report.php">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Screen with AI</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="text-center py-4">
            <div style="font-size:2rem;">📄</div>
            <p class="text-muted mb-0">No reports uploaded yet.</p>
            <a href="upload_report.php" class="btn btn-clinic mt-2" style="width:auto;padding-inline:24px;">Upload your first report</a>
        </div>
    <?php endif; ?>
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