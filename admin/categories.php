<?php
require_once '../config/db.php';

// 1. POST Actions handling: Add, Edit, Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Category
    if (isset($_POST['add_category'])) {
        $name = sanitize($_POST['name']);
        if (empty($name)) {
            $_SESSION['admin_error'] = "Category name cannot be empty.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$name]);
                $_SESSION['admin_success'] = "Category '{$name}' added successfully.";
            } catch (PDOException $e) {
                $_SESSION['admin_error'] = "Failed to add category. It may already exist.";
            }
        }
    }

    // Edit Category
    if (isset($_POST['edit_category'])) {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['name']);
        if (empty($name)) {
            $_SESSION['admin_error'] = "Category name cannot be empty.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
                $stmt->execute([$name, $id]);
                $_SESSION['admin_success'] = "Category updated successfully to '{$name}'.";
            } catch (PDOException $e) {
                $_SESSION['admin_error'] = "Failed to update category. The name may be taken.";
            }
        }
    }

    // Delete Category
    if (isset($_POST['delete_category'])) {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['admin_success'] = "Category deleted successfully.";
        } catch (PDOException $e) {
            $_SESSION['admin_error'] = "Failed to delete category: " . $e->getMessage();
        }
    }

    header("Location: categories.php");
    exit;
}

// NOW include the admin header after redirects are processed
$page_title = "Manage Categories";
require_once '../includes/admin_header.php';

// 2. GET Check for editing state
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_category = $stmt->fetch();
    } catch (PDOException $e) {
        $error_msg = "Error retrieving category details.";
    }
}

// 3. Fetch all categories
$categories = [];
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Failed to load categories: " . $e->getMessage();
}
?>

<!-- Alerts -->
<?php if (isset($_SESSION['admin_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $_SESSION['admin_success']; unset($_SESSION['admin_success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['admin_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $_SESSION['admin_error']; unset($_SESSION['admin_error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4 animate-fade-in">
    <!-- Categories List Grid Column -->
    <div class="col-md-7">
        <div class="glass-container p-4">
            <h5 class="fw-bold mb-4"><i class="bi bi-tags me-2 text-primary"></i>Existing Categories</h5>
            
            <?php if (empty($categories)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-tag display-5"></i>
                    <p class="mt-2">No categories defined yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle table-premium mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="ps-3">ID</th>
                                <th scope="col">Name</th>
                                <th scope="col" class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-secondary"><?php echo $cat['id']; ?></td>
                                    <td><strong><?php echo sanitize($cat['name']); ?></strong></td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end gap-2">
                                            <!-- Edit button -->
                                            <a href="categories.php?edit=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-secondary rounded-3" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <!-- Delete button -->
                                            <form action="categories.php" method="POST" class="d-inline" onsubmit="return confirm('Deleting this category will set associated products category to Uncategorized. Proceed?');">
                                                <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                                <button type="submit" name="delete_category" class="btn btn-sm btn-outline-danger border-0">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Category Actions Forms Column -->
    <div class="col-md-5">
        <div class="glass-container p-4">
            <?php if ($edit_category): ?>
                <!-- Edit Category Form -->
                <h5 class="fw-bold mb-4 text-indigo"><i class="bi bi-pencil-square me-2"></i>Edit Category</h5>
                <form action="categories.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-secondary">Category Name</label>
                        <input type="text" name="name" id="name" class="form-control form-control-custom" value="<?php echo sanitize($edit_category['name']); ?>" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="edit_category" class="btn btn-premium w-100 border-0">Save Changes</button>
                        <a href="categories.php" class="btn btn-premium-outline w-100">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <!-- Add Category Form -->
                <h5 class="fw-bold mb-4"><i class="bi bi-plus-circle me-2 text-primary"></i>Add Category</h5>
                <form action="categories.php" method="POST">
                    <div class="mb-4">
                        <label for="name" class="form-label small fw-semibold text-secondary">Category Name</label>
                        <input type="text" name="name" id="name" class="form-control form-control-custom" placeholder="e.g. Health & Fitness" required>
                    </div>
                    <button type="submit" name="add_category" class="btn btn-premium w-100 border-0">
                        <i class="bi bi-plus-lg me-1"></i> Add Category
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
