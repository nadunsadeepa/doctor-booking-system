<?php
/**
 * admin/category_edit.php?id=X
 * -----------------------------------------------------------
 * Module 07 - Disease Category
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM disease_categories WHERE id = :id");
$stmt->execute(['id' => $id]);
$category = $stmt->fetch();

if (!$category) {
    set_flash('danger', 'Category not found.');
    redirect('admin/categories.php');
}

$errors = [];
$old = [
    'category_name' => $category['category_name'],
    'description'    => $category['description'],
    'status'         => $category['status'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $category_name = clean_input($_POST['category_name'] ?? '');
        $description    = clean_input($_POST['description'] ?? '');
        $status         = clean_input($_POST['status'] ?? 'Active');
        $old = ['category_name' => $category_name, 'description' => $description, 'status' => $status];

        if (strlen($category_name) < 2) {
            $errors[] = 'Please enter a category name.';
        } else {
            $check = $pdo->prepare("SELECT id FROM disease_categories WHERE category_name = :n AND id != :id LIMIT 1");
            $check->execute(['n' => $category_name, 'id' => $id]);
            if ($check->fetch()) {
                $errors[] = 'Another category already uses that name.';
            } else {
                $pdo->prepare(
                    "UPDATE disease_categories SET category_name = :n, description = :d, status = :s WHERE id = :id"
                )->execute(['n' => $category_name, 'd' => $description ?: null, 's' => $status, 'id' => $id]);

                set_flash('success', 'Category updated.');
                redirect('admin/categories.php');
            }
        }
    }
}

$pageTitle   = 'Edit Category';
$currentPage = 'categories';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="admin-panel" style="max-width:560px;">
    <form method="post" action="category_edit.php?id=<?= $id ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="category_name" class="form-control" required
                   value="<?= htmlspecialchars($old['category_name']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Description (optional)</label>
            <input type="text" name="description" class="form-control"
                   value="<?= htmlspecialchars($old['description'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
                <option value="Active"   <?= $old['status'] === 'Active'   ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= $old['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <button type="submit" class="btn btn-clinic" style="width:auto;padding-inline:24px;">Save</button>
        <a href="categories.php" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
