<?php
/**
 * admin/sms_logs.php
 * -----------------------------------------------------------
 * Module 11 - SMS System
 * Shows every SMS attempt (sent / simulated / failed) logged
 * by sms/sms_helper.php.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../sms/sms_helper.php';

require_admin_login();

$logs = $pdo->query(
    "SELECT s.*, p.full_name
     FROM sms_logs s
     LEFT JOIN patients p ON p.id = s.patient_id
     ORDER BY s.sent_time DESC
     LIMIT 100"
)->fetchAll();

$modeNote = SMS_SIMULATION_MODE
    ? 'Simulation mode is ON — no real SMS is being sent. Every attempt is still logged below. Turn it off in config/sms_config.php once you add real Notify.lk credentials.'
    : 'Simulation mode is OFF — messages are being sent through Notify.lk for real.';

$pageTitle   = 'SMS Logs';
$currentPage = 'sms_logs';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="alert alert-<?= SMS_SIMULATION_MODE ? 'warning' : 'success' ?>"><?= htmlspecialchars($modeNote) ?></div>

<div class="admin-panel">
    <?php if ($logs): ?>
        <table class="admin-table">
            <thead><tr><th>Time</th><th>Patient</th><th>Phone</th><th>Message</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($logs as $l): ?>
                    <tr>
                        <td class="text-nowrap"><?= htmlspecialchars(date('d M, h:i A', strtotime($l['sent_time']))) ?></td>
                        <td><?= htmlspecialchars($l['full_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($l['phone']) ?></td>
                        <td style="max-width:320px;white-space:pre-line;"><?= htmlspecialchars($l['message']) ?></td>
                        <td>
                            <?php if ($l['status'] === 'sent'): ?>
                                <span class="badge-success-soft">Sent</span>
                            <?php elseif ($l['status'] === 'simulated'): ?>
                                <span class="badge-success-soft" style="background:#EEF2FF;color:#3949AB;">Simulated</span>
                            <?php else: ?>
                                <span class="badge-danger-soft">Failed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-muted mb-0">No SMS activity yet.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
