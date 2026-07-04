<?php
require_once 'config/db.php';

// Access Control Gate: Redirect to login if guest
if (!isset($_SESSION['user_id'])) {
    $_SESSION['cart_error'] = "Please log in to check out.";
    header("Location: user/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error_msg = "";

// 1. Fetch Cart Items & check if cart is empty
$cart_items = [];
$cart_total = 0.00;

try {
    $stmt = $pdo->prepare("
        SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.stock 
        FROM cart c 
        INNER JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();

    if (empty($cart_items)) {
        header("Location: cart.php");
        exit;
    }

    // Calculate total price
    foreach ($cart_items as $item) {
        $cart_total += ($item['price'] * $item['quantity']);
    }
} catch (PDOException $e) {
    $error_msg = "Error loading checkout items: " . $e->getMessage();
}

// 2. Handle POST Order Placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $shipping_address = sanitize($_POST['shipping_address']);
    $contact_number = sanitize($_POST['contact_number']);

    if (empty($shipping_address) || empty($contact_number)) {
        $error_msg = "Please provide your shipping address and contact number.";
    } else {
        try {
            // Start transaction to maintain absolute database consistency
            $pdo->beginTransaction();

            // Double check stock levels for all items before creating order
            foreach ($cart_items as $item) {
                $stmt_stock = $pdo->prepare("SELECT name, stock FROM products WHERE id = ? FOR UPDATE");
                $stmt_stock->execute([$item['product_id']]);
                $prod = $stmt_stock->fetch();

                if (!$prod || $prod['stock'] < $item['quantity']) {
                    throw new Exception("Product '{$item['name']}' is out of stock or does not have sufficient quantity. Only {$prod['stock']} units left. Please update your cart.");
                }
            }

            // Insert into Orders table
            $stmt_order = $pdo->prepare("
                INSERT INTO orders (user_id, total_price, status, shipping_address, contact_number) 
                VALUES (?, ?, 'pending', ?, ?)
            ");
            $stmt_order->execute([$user_id, $cart_total, $shipping_address, $contact_number]);
            $order_id = $pdo->lastInsertId();

            // Insert into Order Items table & deduct stock
            $stmt_item_insert = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt_stock_deduct = $pdo->prepare("
                UPDATE products SET stock = stock - ? WHERE id = ?
            ");

            foreach ($cart_items as $item) {
                // Save historical price (in case price changes in product table later)
                $stmt_item_insert->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                // Deduct stock
                $stmt_stock_deduct->execute([$item['quantity'], $item['product_id']]);
            }

            // Empty the cart
            $stmt_clear_cart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt_clear_cart->execute([$user_id]);

            // Commit all changes
            $pdo->commit();

            $_SESSION['order_success'] = "Thank you! Your order has been placed successfully. Order ID: #LC-" . str_pad($order_id, 6, '0', STR_PAD_LEFT);
            header("Location: user/orders.php");
            exit;

        } catch (Exception $e) {
            // Rollback changes on any exception
            $pdo->rollBack();
            $error_msg = "Order failed: " . $e->getMessage();
        }
    }
}

$page_title = "Checkout";
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-extrabold text-primary">Checkout</h2>
        <p class="text-secondary mb-0">Fill in your delivery details to complete order</p>
    </div>
    <a href="cart.php" class="btn btn-premium-outline btn-sm">
        <i class="bi bi-cart3 me-1"></i> Return to Cart
    </a>
</div>

<?php if (!empty($error_msg)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
    </div>
<?php endif; ?>

<div class="row g-4 animate-fade-in">
    <!-- Shipping Address Form -->
    <div class="col-md-7">
        <div class="glass-container p-4">
            <h4 class="fw-bold mb-4"><i class="bi bi-truck me-2 text-primary"></i>Shipping Details</h4>
            
            <form action="checkout.php" method="POST">
                <!-- Shipping Address -->
                <div class="mb-3">
                    <label for="shipping_address" class="form-label small fw-bold text-secondary">Complete Shipping Address</label>
                    <textarea name="shipping_address" id="shipping_address" class="form-control form-control-custom" rows="4" placeholder="Enter street, apartment, city, state, postal code" required><?php echo isset($shipping_address) ? $shipping_address : ''; ?></textarea>
                </div>

                <!-- Contact Number -->
                <div class="mb-4">
                    <label for="contact_number" class="form-label small fw-bold text-secondary">Contact Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-telephone text-muted"></i></span>
                        <input type="text" name="contact_number" id="contact_number" class="form-control form-control-custom border-start-0" placeholder="e.g. +1234567890" required value="<?php echo isset($contact_number) ? $contact_number : ''; ?>">
                    </div>
                </div>

                <!-- Payment Method Info -->
                <div class="mb-4 p-3 bg-light rounded-3 border">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cash-coin text-success fs-4"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">Payment Method: Cash on Delivery (COD)</h6>
                            <span class="small text-muted">Pay with cash upon delivery of your items.</span>
                        </div>
                    </div>
                </div>

                <button type="submit" name="place_order" class="btn btn-premium w-100">
                    <i class="bi bi-bag-check-fill me-2"></i> Confirm and Place Order
                </button>
            </form>
        </div>
    </div>

    <!-- Order Review Sidebar -->
    <div class="col-md-5">
        <div class="glass-container p-4">
            <h4 class="fw-bold mb-4"><i class="bi bi-bag-check me-2 text-primary"></i>Review Order</h4>
            
            <ul class="list-group list-group-flush mb-3">
                <?php foreach ($cart_items as $item): ?>
                    <li class="list-group-item px-0 py-3 bg-transparent d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold text-dark"><?php echo sanitize($item['name']); ?></h6>
                            <span class="small text-muted">Qty: <?php echo $item['quantity']; ?> @ $<?php echo number_format($item['price'], 2); ?></span>
                        </div>
                        <span class="fw-bold text-dark">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="d-flex justify-content-between mb-2">
                <span class="text-secondary">Subtotal</span>
                <span class="fw-semibold text-dark">$<?php echo number_format($cart_total, 2); ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-secondary">Shipping</span>
                <span class="text-success fw-bold">FREE</span>
            </div>
            <hr class="my-3">
            <div class="d-flex justify-content-between">
                <span class="text-secondary fw-bold">Order Total</span>
                <span class="text-primary fw-extrabold fs-4">$<?php echo number_format($cart_total, 2); ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
