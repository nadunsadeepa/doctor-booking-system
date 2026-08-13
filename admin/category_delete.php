<?php
/**
 * admin/category_delete.php
 * -----------------------------------------------------------
 * Module 07 - Disease Category
 * POST-only. In the live schema, doctors.specialization_id is
 * NOT NULL with a foreign key (no ON DELETE clause), so deleting
 * a category that still has doctors linked to it is blocked by
 * the database — we catch that and show a friendly message.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid request.');
    redirect('admin/categories.php');
}

$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT category_name FROM disease_categories WHERE id = :id");
$stmt->execute(['id' => $id]);
$category = $stmt->fetch();

if ($category) {
    try {
        $pdo->prepare("DELETE FROM disease_categories WHERE id = :id")->execute(['id' => $id]);
        set_flash('success', 'Category "' . $category['category_name'] . '" was deleted.');
    } catch (PDOException $e) {
        set_flash('danger', 'Cannot delete "' . $category['category_name'] . '" — doctors are still assigned to it. Move them to another category first, or set this one to Inactive instead.');
    }
} else {
    set_flash('danger', 'Category not found.');
}

redirect('admin/categories.php');
