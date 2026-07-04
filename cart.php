<?php
require_once 'config/db.php';

// Access Control Gate: Redirect to login if guest
if (!isset($_SESSION['user_id'])) {
    $_SESSION['cart_error'] = "Please log in to access your shopping cart.";
    header("Location: user/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// 1. Handle POST actions: Update Quantity & Delete Item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Quantity
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $cart_id = (int)$_POST['cart_id'];
        $new_qty = (int)$_POST['quantity'];

        try {
            // Verify ownership and fetch stock
            $stmt = $pdo->prepare("
                SELECT c.id, c.quantity, p.stock, p.name 
                FROM cart c 
                INNER JOIN products p ON c.product_id = p.id 
                WHERE c.id = ? AND c.user_id = ?
            ");
            $stmt->execute([$cart_id, $user_id]);
            $item = $stmt->fetch();

            if ($item) {
                if ($new_qty <= 0) {
                    // Remove if quantity is 0 or negative
                    $stmt_del = $pdo->prepare("DELETE FROM cart WHERE id = ?");
                    $stmt_del->execute([$cart_id]);
                    $success_msg = "Removed '{$item['name']}' from cart.";
                } elseif ($new_qty > $item['stock']) {
                    // Cap at stock limit
                    $stmt_upd = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $stmt_upd->execute([$item['stock'], $cart_id]);
                    $error_msg = "Quantity for '{$item['name']}' limited to maximum available stock ({$item['stock']}).";
                } else {
                    // Standard update
                    $stmt_upd = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $stmt_upd->execute([$new_qty, $cart_id]);
                    $success_msg = "Cart updated successfully.";
                }
            }
        } catch (PDOException $e) {
            $error_msg = "Failed to update quantity: " . $e->getMessage();
        }
    }

    // Delete Item
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $cart_id = (int)$_POST['cart_id'];

        try {
            // Verify ownership and delete
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$cart_id, $user_id])) {
                $success_msg = "Item removed from cart.";
            }
        } catch (PDOException $e) {
            $error_msg = "Failed to remove item: " . $e->getMessage();
        }
    }

    // Redirect to prevent double submission
    if (!empty($success_msg)) $_SESSION['cart_success'] = $success_msg;
    if (!empty($error_msg)) $_SESSION['cart_error'] = $error_msg;
    header("Location: cart.php");
    exit;
}

// 2. Fetch all cart items for the logged-in customer
$cart_items = [];
$cart_total = 0.00;

try {
    $stmt = $pdo->prepare("
        SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.image, p.stock 
        FROM cart c 
        INNER JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();

    // Calculate cart total
    foreach ($cart_items as $item) {
        $cart_total += ($item['price'] * $item['quantity']);
    }
} catch (PDOException $e) {
    $error_msg = "Failed to retrieve cart items: " . $e->getMessage();
}

$page_title = "Your Cart";
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-extrabold text-primary">Your Shopping Cart</h2>
        <p class="text-secondary mb-0">Review and modify your items before checkout</p>
    </div>
    <a href="index.php" class="btn btn-premium-outline btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Continue Shopping
    </a>
</div>

<!-- Dynamic Alert banners -->
<?php if (isset($_SESSION['cart_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $_SESSION['cart_success']; unset($_SESSION['cart_success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['cart_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-octagon-fill me-2"></i> <?php echo $_SESSION['cart_error']; unset($_SESSION['cart_error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (empty($cart_items)): ?>
    <div class="card glass-container text-center py-5">
        <div class="card-body">
            <i class="bi bi-cart-x text-muted display-1 mb-3"></i>
            <h4 class="fw-bold">Your Cart is Empty</h4>
            <p class="text-muted">You haven't added any luxury items to your cart yet.</p>
            <a href="index.php" class="btn btn-premium mt-2">Shop Now</a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <!-- Cart Items List (Table) -->
        <div class="col-lg-8">
            <div class="glass-container p-4">
                <div class="table-responsive">
                    <table class="table align-middle table-premium mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col" class="text-center">Quantity</th>
                                <th scope="col" class="text-end">Subtotal</th>
                                <th scope="col" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <?php $item_subtotal = $item['price'] * $item['quantity']; ?>
                                <tr>
                                    <!-- Product Info & Image -->
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="assets/images/<?php echo sanitize($item['image']); ?>" class="cart-item-img border" alt="<?php echo sanitize($item['name']); ?>" onerror="this.src='https://placehold.co/80';">
                                            <?php else: ?>
                                                <img src="https://placehold.co/80" class="cart-item-img border" alt="<?php echo sanitize($item['name']); ?>">
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-1 fw-bold"><a href="product.php?id=<?php echo $item['product_id']; ?>" class="text-dark text-decoration-none hover-indigo"><?php echo sanitize($item['name']); ?></a></h6>
                                                <span class="text-primary fw-semibold small">$<?php echo number_format($item['price'], 2); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- Quantity Form -->
                                    <td style="max-width: 150px;">
                                        <form action="cart.php" method="POST" class="d-flex align-items-center gap-2 justify-content-center">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="number" name="quantity" class="form-control form-control-sm text-center form-control-custom px-1" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" style="width: 60px;" required>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-3" title="Update Quantity">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </form>
                                    </td>
                                    
                                    <!-- Subtotal -->
                                    <td class="text-end fw-bold text-dark">
                                        $<?php echo number_format($item_subtotal, 2); ?>
                                    </td>
                                    
                                    <!-- Actions (Remove) -->
                                    <td class="text-center">
                                        <form action="cart.php" method="POST" onsubmit="return confirm('Remove this item from your cart?');">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                                <i class="bi bi-trash-fill fs-5"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Order Summary Side Panel -->
        <div class="col-lg-4">
            <div class="glass-container p-4">
                <h4 class="fw-bold mb-4"><i class="bi bi-cart-check me-2"></i>Order Summary</h4>
                
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-secondary">Subtotal</span>
                    <span class="fw-bold text-dark">$<?php echo number_format($cart_total, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-secondary">Shipping</span>
                    <span class="text-success fw-bold">FREE</span>
                </div>
                <hr class="my-3">
                <div class="d-flex justify-content-between mb-4">
                    <span class="text-secondary fw-bold">Total</span>
                    <span class="text-primary fw-extrabold fs-4">$<?php echo number_format($cart_total, 2); ?></span>
                </div>

                <a href="checkout.php" class="btn btn-premium w-100">
                    <i class="bi bi-credit-card-2-front-fill me-2"></i> Proceed to Checkout
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
