<?php
require_once 'config/db.php';

// Add to Cart POST submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['cart_error'] = "Please log in to add items to your cart.";
        header("Location: user/login.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($quantity <= 0) {
        $quantity = 1;
    }

    try {
        // Verify product exists and check stock
        $stmt = $pdo->prepare("SELECT name, stock FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            $_SESSION['cart_error'] = "Product does not exist.";
        } elseif ($product['stock'] <= 0) {
            $_SESSION['cart_error'] = "Sorry, '{$product['name']}' is currently out of stock.";
        } elseif ($product['stock'] < $quantity) {
            $_SESSION['cart_error'] = "Only {$product['stock']} units of '{$product['name']}' are available in stock.";
        } else {
            // Check if product is already in user's cart
            $stmt_check = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt_check->execute([$user_id, $product_id]);
            $cart_item = $stmt_check->fetch();

            if ($cart_item) {
                // Update quantity, capping at stock
                $new_qty = $cart_item['quantity'] + $quantity;
                if ($new_qty > $product['stock']) {
                    $new_qty = $product['stock'];
                    $_SESSION['cart_warning'] = "Cart quantity adjusted to maximum available stock ({$product['stock']} units).";
                }
                $stmt_update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $stmt_update->execute([$new_qty, $cart_item['id']]);
            } else {
                // Insert new cart item
                $stmt_insert = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt_insert->execute([$user_id, $product_id, $quantity]);
            }
            $_SESSION['cart_success'] = "Added '{$product['name']}' to your cart!";
        }
    } catch (PDOException $e) {
        $_SESSION['cart_error'] = "Failed to add item to cart: " . $e->getMessage();
    }

    // Redirect to cart automatically after adding item
    header("Location: cart.php");
    exit;
}

// Fetch all categories for filter buttons
$categories = [];
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    // Fail silently
}

// Retrieve Search and Filter inputs
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Build SQL Query dynamically
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if ($category_filter > 0) {
    $query .= " AND p.category_id = :category_id";
    $params['category_id'] = $category_filter;
}

$query .= " ORDER BY p.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $error_msg = "Error loading products: " . $e->getMessage();
}

$page_title = "Shop Premium Goods";
require_once 'includes/header.php';
?>

<!-- Hero Banner Section -->
<div class="hero-section text-center text-md-start">
    <div class="row align-items-center">
        <div class="col-md-7 hero-content">
            <span class="badge border border-indigo-subtle text-indigo mb-3 px-3 py-2 rounded-pill fw-bold">New Arrivals In Store</span>
            <h1 class="hero-title mb-3">Elevate Your Everyday Essentials</h1>
            <p class="lead text-secondary mb-4">Discover curated luxury items, premium design aesthetics, and fast shipping with LuxeCommerce.</p>
            <a href="#shop-section" class="btn btn-premium">Explore Catalog</a>
        </div>
        <div class="col-md-5 d-none d-md-block text-center">
            <i class="bi bi-gem text-primary opacity-25" style="font-size: 13rem; filter: drop-shadow(0 0 40px rgba(99, 102, 241, 0.2));"></i>
        </div>
    </div>
</div>

<div id="shop-section" class="row mb-5">
    <!-- Filter Sidebar / Controls -->
    <div class="col-12 mb-4">
        <div class="glass-container p-4">
            <form action="index.php" method="GET" class="row g-3 align-items-center">
                <!-- Search bar -->
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control form-control-custom border-0" placeholder="Search premium products..." value="<?php echo sanitize($search); ?>">
                    </div>
                </div>

                <!-- Category Dropdown Filter -->
                <div class="col-md-4">
                    <select name="category" class="form-select form-control-custom border-0 bg-light">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($category_filter === (int)$cat['id']) ? 'selected' : ''; ?>>
                                <?php echo sanitize($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter & Reset Buttons -->
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-premium w-100"><i class="bi bi-sliders me-1"></i> Filter</button>
                    <?php if (!empty($search) || $category_filter > 0): ?>
                        <a href="index.php" class="btn btn-premium-outline border-2">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Notifications for Add to Cart -->
<?php if (isset($_SESSION['cart_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $_SESSION['cart_success']; unset($_SESSION['cart_success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['cart_warning'])): ?>
    <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $_SESSION['cart_warning']; unset($_SESSION['cart_warning']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['cart_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
        <i class="bi bi-x-circle-fill me-2"></i> <?php echo $_SESSION['cart_error']; unset($_SESSION['cart_error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Products Grid -->
<div class="row">
    <?php if (empty($products)): ?>
        <div class="col-12 text-center py-5">
            <i class="bi bi-emoji-frown text-muted display-3 mb-3"></i>
            <h4 class="fw-bold">No Products Found</h4>
            <p class="text-secondary">Try searching something else or switching category filters.</p>
        </div>
    <?php else: ?>
        <?php foreach ($products as $prod): ?>
            <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                <div class="product-card">
                    <div class="product-image-wrapper">
                        <!-- Category Badge -->
                        <span class="product-badge-category">
                            <?php echo sanitize($prod['category_name'] ?? 'Uncategorized'); ?>
                        </span>
                        
                        <!-- Product Image -->
                        <?php if (!empty($prod['image'])): ?>
                            <img src="assets/images/<?php echo sanitize($prod['image']); ?>" alt="<?php echo sanitize($prod['name']); ?>" onerror="this.src='https://placehold.co/400x400?text=No+Image';">
                        <?php else: ?>
                            <img src="https://placehold.co/400x400?text=No+Image" alt="<?php echo sanitize($prod['name']); ?>">
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-card-body d-flex flex-column">
                        <span class="small mb-2 fw-semibold">
                            <?php 
                            if ($prod['status'] === 'unavailable') {
                                echo '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Out of Stock</span>';
                            } else {
                                echo $prod['stock'] > 0 ? '<span class="text-success"><i class="bi bi-check2-circle me-1"></i>In Stock</span>' : '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Out of Stock</span>';
                            }
                            ?>
                        </span>
                        <h5 class="product-card-title">
                            <a href="product.php?id=<?php echo $prod['id']; ?>">
                                <?php echo sanitize($prod['name']); ?>
                            </a>
                        </h5>
                        <p class="text-secondary small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 38px;"><?php echo sanitize($prod['description']); ?></p>
                        
                        <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="product-card-price">$<?php echo number_format($prod['price'], 2); ?></span>
                            
                            <?php if ($prod['status'] === 'unavailable'): ?>
                                <button class="btn btn-sm btn-secondary disabled" disabled>Sold Out</button>
                            <?php elseif ($prod['stock'] > 0): ?>
                                <form action="index.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>" method="POST" class="d-inline">
                                    <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" name="add_to_cart" class="btn btn-sm btn-premium px-3 py-2">
                                        <i class="bi bi-cart-plus me-1"></i> Add
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary disabled" disabled>Sold Out</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
