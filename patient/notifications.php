<?php
/**
 * patient/notifications.php
 * -----------------------------------------------------------
 * Module 13 - Patient Dashboard
 * Notifications are created by: registration (welcome),
 * booking (confirmation), and doctor cancellation.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_patient_login();

$patientId = $_SESSION['patient_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf_token'] ?? '')) {
    if (($_POST['action'] ?? '') === 'mark_all_read') {
        $pdo->prepare("UPDATE notifications SET status = 'Read' WHERE patient_id = :id AND status = 'Unread'")
            ->execute(['id' => $patientId]);
    } elseif (($_POST['action'] ?? '') === 'mark_read') {
        $nid = (int)($_POST['notification_id'] ?? 0);
        $pdo->prepare("UPDATE notifications SET status = 'Read' WHERE id = :nid AND patient_id = :id")
            ->execute(['nid' => $nid, 'id' => $patientId]);
    }
    redirect('patient/notifications.php');
}

$stmt = $pdo->prepare(
    "SELECT * FROM notifications WHERE patient_id = :id ORDER BY created_at DESC LIMIT 50"
);
$stmt->execute(['id' => $patientId]);
$notifications = $stmt->fetchAll();

$pageTitle   = 'Notifications';
$currentPage = 'notifications';
require_once __DIR__ . '/../includes/patient_header.php';
?>

<div class="pt-4 pb-2 d-flex justify-content-between align-items-center">
    <h2 class="mb-0">Notifications</h2>
    <?php if ($notifications): ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="mark_all_read">
            <button type="submit" class="btn btn-outline-secondary btn-sm">Mark all as read</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($notifications): ?>
    <?php foreach ($notifications as $n): ?>
        <div class="admin-panel mb-2 d-flex justify-content-between align-items-start gap-3">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($n['status'] === 'Unread'): ?>
                        <span style="width:8px;height:8px;border-radius:50%;background:#E4573D;display:inline-block;"></span>
                    <?php endif; ?>
                    <strong><?= htmlspecialchars($n['title']) ?></strong>
                </div>
                <p class="mb-1 mt-1"><?= htmlspecialchars($n['message']) ?></p>
                <div class="text-muted small"><?= htmlspecialchars(date('d M Y, h:i A', strtotime($n['created_at']))) ?></div>
            </div>
            <?php if ($n['status'] === 'Unread'): ?>
                <form method="post" class="flex-shrink-0">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="mark_read">
                    <input type="hidden" name="notification_id" value="<?= $n['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Mark read</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="admin-panel text-center py-4">
        <div style="font-size:2rem;">🔔</div>
        <p class="text-muted mb-0">No notifications yet.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/patient_footer.php'; ?>
