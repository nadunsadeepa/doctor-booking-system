<?php
/**
 * admin/categories.php
 * -----------------------------------------------------------
 * Module 07 - Disease Category
 * Matches live schema: disease_categories.category_name,
 * disease_categories.status ENUM('Active','Inactive').
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $category_name = clean_input($_POST['category_name'] ?? '');
        $description    = clean_input($_POST['description'] ?? '');

        if (strlen($category_name) < 2) {
            $errors[] = 'Please enter a category name.';
        } else {
            $check = $pdo->prepare("SELECT id FROM disease_categories WHERE category_name = :n LIMIT 1");
            $check->execute(['n' => $category_name]);
            if ($check->fetch()) {
                $errors[] = 'That category already exists.';
            } else {
                $pdo->prepare(
                    "INSERT INTO disease_categories (category_name, description, status)
                     VALUES (:n, :d, 'Active')"
                )->execute(['n' => $category_name, 'd' => $description ?: null]);

                set_flash('success', 'Category "' . $category_name . '" added.');
                redirect('admin/categories.php');
            }
        }
    }
}

$categories = $pdo->query(
    "SELECT c.*, COUNT(d.id) AS doctor_count
     FROM disease_categories c
     LEFT JOIN doctors d ON d.specialization_id = c.id
     GROUP BY c.id
     ORDER BY c.category_name"
)->fetchAll();

$pageTitle   = 'Disease Categories';
$currentPage = 'categories';
$flash       = get_flash();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="admin-panel mb-3">
    <h5>Add Category</h5>
    <form method="post" action="categories.php" class="row g-2 align-items-end">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="col-md-4">
            <label class="form-label">Name</label>
            <input type="text" name="category_name" class="form-control" placeholder="e.g. Eye Disease" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Description (optional)</label>
            <input type="text" name="description" class="form-control">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-clinic">Add</button>
        </div>
    </form>
</div>

<div class="admin-panel">
    <?php if ($categories): ?>
        <table class="admin-table">
            <thead><tr><th>Name</th><th>Description</th><th>Doctors</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['category_name']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($c['description'] ?? '—') ?></td>
                        <td><?= (int)$c['doctor_count'] ?></td>
                        <td>
                            <?php if ($c['status'] === 'Active'): ?>
                                <span class="badge-success-soft">Active</span>
                            <?php else: ?>
                                <span class="badge-danger-soft">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <a href="category_edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="#" class="btn btn-sm btn-outline-danger"
                               onclick="confirmDelete(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['category_name'])) ?>', <?= (int)$c['doctor_count'] ?>); return false;">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-muted mb-0">No categories yet.</p>
    <?php endif; ?>
</div>

<form id="deleteForm" action="category_delete.php" method="post" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function confirmDelete(id, name, doctorCount) {
    var msg = 'Delete "' + name + '"?';
    if (doctorCount > 0) {
        msg += ' Note: ' + doctorCount + ' doctor(s) are currently linked to it — deletion will be blocked until they are reassigned.';
    }
    if (confirm(msg)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
