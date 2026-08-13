<?php
/**
 * admin/doctors.php
 * -----------------------------------------------------------
 * Module 06 - Doctor Management
 * NOTE: matches the actual live schema in your doctor_booking DB
 * (doctor_name, specialization_id, hospital_name, experience,
 *  room_no, consultation_fee, status ENUM('Active','Inactive')).
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare(
        "SELECT d.*, c.category_name
         FROM doctors d
         LEFT JOIN disease_categories c ON c.id = d.specialization_id
         WHERE d.doctor_name LIKE :q OR d.hospital_name LIKE :q OR c.category_name LIKE :q
         ORDER BY d.created_at DESC"
    );
    $stmt->execute(['q' => '%' . $search . '%']);
} else {
    $stmt = $pdo->query(
        "SELECT d.*, c.category_name
         FROM doctors d
         LEFT JOIN disease_categories c ON c.id = d.specialization_id
         ORDER BY d.created_at DESC"
    );
}
$doctors = $stmt->fetchAll();

$pageTitle   = 'Doctors';
$currentPage = 'doctors';
$flash       = get_flash();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="get" class="d-flex gap-2">
        <input type="text" name="q" class="form-control" placeholder="Search by name, hospital or category..."
               style="min-width:280px" value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-secondary" type="submit">Search</button>
        <?php if ($search !== ''): ?>
            <a href="doctors.php" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </form>
    <a href="doctor_add.php" class="btn btn-clinic" style="width:auto;">+ Add Doctor</a>
</div>

<div class="admin-panel">
    <?php if ($doctors): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Hospital</th>
                    <th>Phone</th>
                    <th>Fee</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doctors as $d): ?>
                    <tr>
                        <td>
                            <?php if ($d['photo']): ?>
                                <img src="../uploads/profile/<?= htmlspecialchars($d['photo']) ?>"
                                     alt="" width="36" height="36" style="border-radius:50%;object-fit:cover;">
                            <?php else: ?>
                                <div class="admin-avatar" style="width:36px;height:36px;">
                                    <?= htmlspecialchars(strtoupper(substr($d['doctor_name'], 0, 1))) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>Dr. <?= htmlspecialchars($d['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($d['category_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($d['hospital_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($d['phone'] ?? '—') ?></td>
                        <td><?= $d['consultation_fee'] !== null ? 'Rs. ' . number_format($d['consultation_fee'], 2) : '—' ?></td>
                        <td>
                            <?php if ($d['status'] === 'Active'): ?>
                                <span class="badge-success-soft">Active</span>
                            <?php else: ?>
                                <span class="badge-danger-soft">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <a href="doctor_view.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="doctor_edit.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="#" class="btn btn-sm btn-outline-danger"
                               onclick="confirmDelete(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['doctor_name'])) ?>'); return false;">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-muted mb-0">No doctors found<?= $search !== '' ? ' for "' . htmlspecialchars($search) . '"' : '' ?>.</p>
    <?php endif; ?>
</div>

<form id="deleteForm" action="doctor_delete.php" method="post" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function confirmDelete(id, name) {
    if (confirm('Delete Dr. ' + name + '? This cannot be undone.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
