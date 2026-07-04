<?php
require_once '../config/db.php';

// Access Control Gate: Redirect to login if guest
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$orders = [];

try {
    // Retrieve all orders for the current user
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Failed to load orders: " . $e->getMessage();
}

$page_title = "My Orders";
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-extrabold text-primary">Your Order History</h2>
        <p class="text-secondary mb-0">Track and review your orders</p>
    </div>
    <a href="../index.php" class="btn btn-premium-outline btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Continue Shopping
    </a>
</div>

<?php if (isset($error_msg)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-octagon me-2"></i> <?php echo $error_msg; ?>
    </div>
<?php endif; ?>

<?php if (empty($orders)): ?>
    <div class="card glass-container text-center py-5">
        <div class="card-body">
            <i class="bi bi-receipt text-muted display-1 mb-3"></i>
            <h4 class="fw-bold">No Orders Placed Yet</h4>
            <p class="text-muted">Once you check out items from your cart, your order history will appear here.</p>
            <a href="../index.php" class="btn btn-premium mt-2">Browse Store</a>
        </div>
    </div>
<?php else: ?>
    <!-- Loop through orders -->
    <div class="row">
        <?php foreach ($orders as $order): ?>
            <?php
            // Fetch items for this order
            try {
                $stmt_items = $pdo->prepare("
                    SELECT oi.*, p.name AS product_name, p.image AS product_image 
                    FROM order_items oi 
                    LEFT JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = ?
                ");
                $stmt_items->execute([$order['id']]);
                $items = $stmt_items->fetchAll();
            } catch (PDOException $e) {
                $items = [];
            }
            ?>
            <div class="col-12 mb-4">
                <div class="glass-container p-0 overflow-hidden shadow-sm">
                    <!-- Order Header -->
                    <div class="py-3 px-4 d-flex flex-wrap justify-content-between align-items-center border-bottom bg-white bg-opacity-50">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div>
                                <span class="text-muted small uppercase fw-bold" style="font-size: 0.7rem;">Order ID</span>
                                <p class="mb-0 fw-bold text-dark">#LC-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></p>
                            </div>
                            <div class="border-start ps-3 d-none d-sm-block">
                                <span class="text-muted small uppercase fw-bold" style="font-size: 0.7rem;">Date Placed</span>
                                <p class="mb-0 text-dark"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                            </div>
                            <div class="border-start ps-3">
                                <span class="text-muted small uppercase fw-bold" style="font-size: 0.7rem;">Shipping To</span>
                                <p class="mb-0 text-dark text-truncate" style="max-width: 250px;"><?php echo sanitize($order['shipping_address']); ?></p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <!-- Status Badge -->
                            <?php 
                            if ($order['status'] == 'pending') {
                                echo '<span class="order-badge-pending"><i class="bi bi-clock-history me-1"></i>Pending</span>';
                            } elseif ($order['status'] == 'shipped') {
                                echo '<span class="order-badge-shipped"><i class="bi bi-truck me-1"></i>Shipped</span>';
                            } else {
                                echo '<span class="order-badge-delivered"><i class="bi bi-check2-circle me-1"></i>Delivered</span>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light bg-opacity-50">
                                    <tr>
                                        <th scope="col" class="ps-4">Product</th>
                                        <th scope="col" class="text-center">Quantity</th>
                                        <th scope="col" class="text-end pe-4">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <?php if (!empty($item['product_image'])): ?>
                                                        <img src="../assets/images/<?php echo sanitize($item['product_image']); ?>" class="rounded-3 border" style="width: 50px; height: 50px; object-fit: cover;" onerror="this.src='https://placehold.co/50';">
                                                    <?php else: ?>
                                                        <img src="https://placehold.co/50" class="rounded-3 border" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold text-dark">
                                                            <?php echo sanitize($item['product_name'] ?? 'Product Removed'); ?>
                                                        </h6>
                                                        <span class="text-muted small">$<?php echo number_format($item['price'], 2); ?> each</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center fw-semibold text-secondary">
                                                x<?php echo $item['quantity']; ?>
                                            </td>
                                            <td class="text-end pe-4 fw-bold text-dark">
                                                $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Order Footer -->
                    <div class="py-3 px-4 d-flex justify-content-between align-items-center bg-white bg-opacity-50 border-top">
                        <span class="text-muted">Contact: <strong><?php echo sanitize($order['contact_number']); ?></strong></span>
                        <h5 class="mb-0 fw-extrabold text-primary">
                            Total: $<?php echo number_format($order['total_price'], 2); ?>
                        </h5>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
