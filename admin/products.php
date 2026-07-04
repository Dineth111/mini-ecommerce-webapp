<?php
require_once '../config/db.php';

$page_title = "Manage Products";
require_once '../includes/admin_header.php';

// Define the images directory path
$upload_dir = '../assets/images/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// 1. Handling Actions POST: Add, Edit, Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. Add Product
    if (isset($_POST['add_product'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $category_id = (int)$_POST['category_id'] > 0 ? (int)$_POST['category_id'] : null;
        $status = sanitize($_POST['status']) === 'unavailable' ? 'unavailable' : 'available';
        
        $image_name = null;

        // Image upload validation and processing
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_orig_name = basename($_FILES['image']['name']);
            $file_ext = strtolower(pathinfo($file_orig_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($file_ext, $allowed_extensions)) {
                $image_name = uniqid('prod_', true) . '.' . $file_ext;
                if (!move_uploaded_file($file_tmp, $upload_dir . $image_name)) {
                    $image_name = null;
                    $_SESSION['admin_error'] = "Failed to upload product image.";
                }
            } else {
                $_SESSION['admin_error'] = "Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.";
            }
        }

        if (!isset($_SESSION['admin_error'])) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, description, price, stock, category_id, image, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $price, $stock, $category_id, $image_name, $status]);
                $_SESSION['admin_success'] = "Product '{$name}' created successfully.";
            } catch (PDOException $e) {
                $_SESSION['admin_error'] = "Failed to create product: " . $e->getMessage();
            }
        }

        header("Location: products.php");
        exit;
    }

    // B. Edit Product
    if (isset($_POST['edit_product'])) {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $category_id = (int)$_POST['category_id'] > 0 ? (int)$_POST['category_id'] : null;
        $status = sanitize($_POST['status']) === 'unavailable' ? 'unavailable' : 'available';
        $existing_image = $_POST['existing_image'];
        
        $image_name = $existing_image;

        // Check if new image is uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_orig_name = basename($_FILES['image']['name']);
            $file_ext = strtolower(pathinfo($file_orig_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($file_ext, $allowed_extensions)) {
                $new_image_name = uniqid('prod_', true) . '.' . $file_ext;
                if (move_uploaded_file($file_tmp, $upload_dir . $new_image_name)) {
                    $image_name = $new_image_name;
                    // Delete old image file if it exists
                    if (!empty($existing_image) && file_exists($upload_dir . $existing_image)) {
                        unlink($upload_dir . $existing_image);
                    }
                } else {
                    $_SESSION['admin_error'] = "Failed to upload new product image.";
                }
            } else {
                $_SESSION['admin_error'] = "Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.";
            }
        }

        if (!isset($_SESSION['admin_error'])) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, image = ?, status = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $price, $stock, $category_id, $image_name, $status, $id]);
                $_SESSION['admin_success'] = "Product updated successfully.";
            } catch (PDOException $e) {
                $_SESSION['admin_error'] = "Failed to update product: " . $e->getMessage();
            }
        }

        header("Location: products.php");
        exit;
    }

    // C. Delete Product
    if (isset($_POST['delete_product'])) {
        $id = (int)$_POST['id'];

        try {
            // Retrieve image name before deletion to clean up storage
            $stmt_img = $pdo->prepare("SELECT image FROM products WHERE id = ?");
            $stmt_img->execute([$id]);
            $image_name = $stmt_img->fetchColumn();

            // Delete product row
            $stmt_del = $pdo->prepare("DELETE FROM products WHERE id = ?");
            if ($stmt_del->execute([$id])) {
                // Delete image file if it exists
                if (!empty($image_name) && file_exists($upload_dir . $image_name)) {
                    unlink($upload_dir . $image_name);
                }
                $_SESSION['admin_success'] = "Product deleted successfully.";
            }
        } catch (PDOException $e) {
            $_SESSION['admin_error'] = "Failed to delete product: " . $e->getMessage();
        }

        header("Location: products.php");
        exit;
    }
}

// 2. Fetch active UI views state (Default list, Add form, Edit form)
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_product = null;

if ($action === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_product = $stmt->fetch();
        if (!$edit_product) {
            $action = 'list';
        }
    } catch (PDOException $e) {
        $action = 'list';
    }
}

// Fetch categories for dropdown select
$categories = [];
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    // Fail silently
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

<!-- VIEW STATE: 1. ADD PRODUCT FORM -->
<?php if ($action === 'add'): ?>
    <div class="glass-container p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0"><i class="bi bi-plus-circle-fill me-2 text-primary"></i>Add New Product</h5>
            <a href="products.php" class="btn btn-premium-outline btn-sm">Cancel</a>
        </div>

        <form action="products.php" method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label small fw-semibold text-secondary">Product Name</label>
                    <input type="text" name="name" id="name" class="form-control form-control-custom" placeholder="e.g. Wireless Pro Earbuds" required>
                </div>
                <div class="col-md-6">
                    <label for="category_id" class="form-label small fw-semibold text-secondary">Category Association</label>
                    <select name="category_id" id="category_id" class="form-select form-control-custom">
                        <option value="0">Uncategorized</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo sanitize($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="price" class="form-label small fw-semibold text-secondary">Price (USD)</label>
                    <input type="number" name="price" id="price" class="form-control form-control-custom" step="0.01" min="0" placeholder="e.g. 99.99" required>
                </div>
                <div class="col-md-4">
                    <label for="stock" class="form-label small fw-semibold text-secondary">Quantity In Stock</label>
                    <input type="number" name="stock" id="stock" class="form-control form-control-custom" min="0" placeholder="e.g. 50" required>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label small fw-semibold text-secondary">Availability Status</label>
                    <select name="status" id="status" class="form-select form-control-custom">
                        <option value="available" selected>Available</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label small fw-semibold text-secondary">Product Description</label>
                    <textarea name="description" id="description" class="form-control form-control-custom" rows="4" placeholder="Detailed description of features, materials, sizing, etc."></textarea>
                </div>
                <div class="col-12">
                    <label for="image" class="form-label small fw-semibold text-secondary">Product Image</label>
                    <input type="file" name="image" id="image" class="form-control form-control-custom" accept="image/*">
                    <span class="small text-muted">Supports JPG, PNG, GIF, and WEBP. Maximum 2MB size suggested.</span>
                </div>
            </div>
            <button type="submit" name="add_product" class="btn btn-premium mt-4 px-4 py-2 border-0">
                <i class="bi bi-box-arrow-in-down me-1"></i> Save Product
            </button>
        </form>
    </div>

<!-- VIEW STATE: 2. EDIT PRODUCT FORM -->
<?php elseif ($action === 'edit' && $edit_product): ?>
    <div class="glass-container p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0 text-indigo"><i class="bi bi-pencil-square me-2"></i>Edit Product: <?php echo sanitize($edit_product['name']); ?></h5>
            <a href="products.php" class="btn btn-premium-outline btn-sm">Cancel</a>
        </div>

        <form action="products.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
            <input type="hidden" name="existing_image" value="<?php echo sanitize($edit_product['image']); ?>">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label small fw-semibold text-secondary">Product Name</label>
                    <input type="text" name="name" id="name" class="form-control form-control-custom" value="<?php echo sanitize($edit_product['name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="category_id" class="form-label small fw-semibold text-secondary">Category Association</label>
                    <select name="category_id" id="category_id" class="form-select form-control-custom">
                        <option value="0">Uncategorized</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ((int)$edit_product['category_id'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                                <?php echo sanitize($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="price" class="form-label small fw-semibold text-secondary">Price (USD)</label>
                    <input type="number" name="price" id="price" class="form-control form-control-custom" step="0.01" min="0" value="<?php echo $edit_product['price']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="stock" class="form-label small fw-semibold text-secondary">Quantity In Stock</label>
                    <input type="number" name="stock" id="stock" class="form-control form-control-custom" min="0" value="<?php echo $edit_product['stock']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label small fw-semibold text-secondary">Availability Status</label>
                    <select name="status" id="status" class="form-select form-control-custom">
                        <option value="available" <?php echo ($edit_product['status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                        <option value="unavailable" <?php echo ($edit_product['status'] === 'unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label small fw-semibold text-secondary">Product Description</label>
                    <textarea name="description" id="description" class="form-control form-control-custom" rows="4"><?php echo sanitize($edit_product['description']); ?></textarea>
                </div>
                
                <!-- Display Existing Image Preview -->
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary d-block">Current Product Image</label>
                    <?php if (!empty($edit_product['image'])): ?>
                        <img src="../assets/images/<?php echo sanitize($edit_product['image']); ?>" class="img-thumbnail rounded-3" style="max-height: 150px; object-fit: cover;" onerror="this.src='https://placehold.co/150';">
                    <?php else: ?>
                        <span class="text-muted small">No image uploaded</span>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <label for="image" class="form-label small fw-semibold text-secondary">Replace Image (Optional)</label>
                    <input type="file" name="image" id="image" class="form-control form-control-custom" accept="image/*">
                    <span class="small text-muted">Leave empty to keep current image. JPG, PNG, GIF, and WEBP supported.</span>
                </div>
            </div>
            
            <button type="submit" name="edit_product" class="btn btn-premium mt-4 px-4 py-2 border-0">
                Save Product Changes
            </button>
        </form>
    </div>

<!-- VIEW STATE: 3. PRODUCTS DATATABLE LISTING -->
<?php else: ?>
    <?php
    // Fetch all products
    $products = [];
    try {
        $stmt_list = $pdo->query("
            SELECT p.*, c.name AS category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.created_at DESC
        ");
        $products = $stmt_list->fetchAll();
    } catch (PDOException $e) {
        $error_msg = "Error retrieving product list.";
    }
    ?>

    <div class="glass-container p-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>Product Inventory Catalog</h5>
            <a href="products.php?action=add" class="btn btn-premium border-0 btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Add Product
            </a>
        </div>

        <?php if (empty($products)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-box display-4"></i>
                <h6 class="mt-3">No products found in database inventory.</h6>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle table-premium mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="ps-3">Photo</th>
                            <th scope="col">Product Info</th>
                            <th scope="col">Category</th>
                            <th scope="col">Price</th>
                            <th scope="col" class="text-center">Stock</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $prod): ?>
                            <tr>
                                <!-- Product Image Thumbnail -->
                                <td class="ps-3">
                                    <?php if (!empty($prod['image'])): ?>
                                        <img src="../assets/images/<?php echo sanitize($prod['image']); ?>" class="rounded-3 border" style="width: 50px; height: 50px; object-fit: cover;" onerror="this.src='https://placehold.co/50';">
                                    <?php else: ?>
                                        <img src="https://placehold.co/50" class="rounded-3 border" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                </td>
                                <!-- Product details -->
                                <td>
                                    <div>
                                        <strong class="text-dark"><?php echo sanitize($prod['name']); ?></strong>
                                        <p class="mb-0 text-muted small text-truncate" style="max-width: 250px;"><?php echo sanitize($prod['description']); ?></p>
                                    </div>
                                </td>
                                <!-- Category -->
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?php echo sanitize($prod['category_name'] ?? 'Uncategorized'); ?>
                                    </span>
                                </td>
                                <!-- Price -->
                                <td>
                                    $<?php echo number_format($prod['price'], 2); ?>
                                </td>
                                <!-- Stock levels -->
                                <td class="text-center">
                                    <?php if ($prod['stock'] > 0): ?>
                                        <span class="badge bg-success-subtle text-success px-2 py-1"><?php echo $prod['stock']; ?> available</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger px-2 py-1">Sold out</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Status -->
                                <td class="text-center">
                                    <?php if ($prod['status'] === 'available'): ?>
                                        <span class="badge bg-success-subtle text-success px-2 py-1"><i class="bi bi-eye-fill me-1"></i>Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger px-2 py-1"><i class="bi bi-eye-slash-fill me-1"></i>Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Action Buttons -->
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <!-- Edit Action -->
                                        <a href="products.php?action=edit&id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-outline-secondary rounded-3" title="Edit Product">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <!-- Delete Action -->
                                        <form action="products.php" method="POST" class="d-inline" onsubmit="return confirm('Confirm deletion of product?');">
                                            <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                                            <button type="submit" name="delete_product" class="btn btn-sm btn-outline-danger border-0" title="Delete Product">
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
<?php endif; ?>

<?php require_once '../includes/admin_footer.php'; ?>
