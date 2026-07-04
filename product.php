<?php
require_once 'config/db.php';

// Retrieve product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
} catch (PDOException $e) {
    $product = null;
}

if (!$product) {
    header("Location: index.php");
    exit;
}

// Add to Cart POST submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart_detail'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['cart_error'] = "Please log in to add items to your cart.";
        header("Location: user/login.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($quantity <= 0) {
        $quantity = 1;
    }

    try {
        if ($product['stock'] <= 0) {
            $_SESSION['cart_error'] = "Sorry, this product is currently out of stock.";
        } elseif ($product['stock'] < $quantity) {
            $_SESSION['cart_error'] = "Only {$product['stock']} units of '{$product['name']}' are available in stock.";
        } else {
            // Check if already in cart
            $stmt_check = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt_check->execute([$user_id, $product_id]);
            $cart_item = $stmt_check->fetch();

            if ($cart_item) {
                // Update quantity, cap at stock
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
        $_SESSION['cart_error'] = "Failed to add item: " . $e->getMessage();
    }

    // Redirect to prevent double-post
    header("Location: product.php?id=" . $product_id);
    exit;
}

$page_title = $product['name'];
require_once 'includes/header.php';
?>

<!-- Back Link -->
<div class="mb-4">
    <a href="index.php" class="btn btn-premium-outline btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Catalog
    </a>
</div>

<!-- Notifications -->
<?php if (isset($_SESSION['cart_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $_SESSION['cart_success']; unset($_SESSION['cart_success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['cart_warning'])): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $_SESSION['cart_warning']; unset($_SESSION['cart_warning']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['cart_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-x-circle-fill me-2"></i> <?php echo $_SESSION['cart_error']; unset($_SESSION['cart_error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Product Detail Display -->
<div class="glass-container p-4 p-md-5">
    <div class="row g-5">
        <!-- Product Image -->
        <div class="col-md-6">
            <div class="rounded-4 overflow-hidden border bg-light position-relative shadow-sm" style="padding-top: 100%;">
                <?php if (!empty($product['image'])): ?>
                    <img src="assets/images/<?php echo sanitize($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" onerror="this.src='https://placehold.co/600x600?text=No+Image';">
                <?php else: ?>
                    <img src="https://placehold.co/600x600?text=No+Image" alt="<?php echo sanitize($product['name']); ?>" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover">
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Specs & Purchase controls -->
        <div class="col-md-6 d-flex flex-column justify-content-center">
            <span class="badge border border-indigo-subtle text-indigo align-self-start mb-3 px-3 py-2 rounded-pill fw-bold">
                <?php echo sanitize($product['category_name'] ?? 'Uncategorized'); ?>
            </span>
            <h1 class="fw-extrabold mb-3 text-white"><?php echo sanitize($product['name']); ?></h1>
            
            <h2 class="text-primary fw-extrabold mb-4 fs-1">$<?php echo number_format($product['price'], 2); ?></h2>
            
            <hr class="my-4 opacity-10">

            <h5 class="fw-bold mb-2"><i class="bi bi-file-text me-2"></i>Product Details</h5>
            <p class="text-secondary mb-4 leading-relaxed"><?php echo nl2br(sanitize($product['description'])); ?></p>

            <div class="mb-4 d-flex align-items-center gap-3">
                <span class="fw-semibold text-secondary">Availability:</span>
                <?php if ($product['stock'] > 0): ?>
                    <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> <?php echo $product['stock']; ?> Units In Stock
                    </span>
                <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-semibold">
                        <i class="bi bi-x-circle me-1"></i> Out of Stock
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($product['stock'] > 0): ?>
                <form action="product.php?id=<?php echo $product['id']; ?>" method="POST" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <label for="quantity" class="form-label mb-0 fw-semibold text-secondary">Qty:</label>
                    </div>
                    <div class="col-3 col-md-2">
                        <input type="number" name="quantity" id="quantity" class="form-control form-control-custom text-center px-1" value="1" min="1" max="<?php echo $product['stock']; ?>" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" name="add_to_cart_detail" class="btn btn-premium px-4">
                            <i class="bi bi-cart-plus me-2"></i> Add to Cart
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <button class="btn btn-secondary btn-lg disabled w-50" disabled>
                    <i class="bi bi-exclamation-octagon me-2"></i> Sold Out
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
