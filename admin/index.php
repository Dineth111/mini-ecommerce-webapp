<?php
require_once '../config/db.php';

$page_title = "Orders Dashboard";
require_once '../includes/admin_header.php';

// 1. Handle Order Status Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = sanitize($_POST['status']);

    if (in_array($status, ['pending', 'shipped', 'delivered'])) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            $_SESSION['admin_success'] = "Order #LC-" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . " updated to " . ucfirst($status) . ".";
        } catch (PDOException $e) {
            $_SESSION['admin_error'] = "Failed to update order status: " . $e->getMessage();
        }
    }
    header("Location: index.php");
    exit;
}

// 2. Fetch Dashboard Statistics
try {
    // Total Orders count
    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0;
    
    // Total Revenue (Sales)
    $total_revenue = $pdo->query("SELECT SUM(total_price) FROM orders")->fetchColumn() ?: 0.00;
    
    // Total Registered Customers
    $total_customers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;
    
    // Total Catalog Size
    $total_catalog = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0;

    // Fetch all orders with customer details
    $orders_stmt = $pdo->query("
        SELECT o.*, u.username, u.email 
        FROM orders o 
        INNER JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC
    ");
    $orders = $orders_stmt->fetchAll();

} catch (PDOException $e) {
    $error_msg = "Dashboard load failed: " . $e->getMessage();
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

<!-- Stats Cards Grid -->
<div class="row g-4 mb-5">
    <!-- Total Revenue -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-success-subtle text-success">
                    <i class="bi bi-currency-dollar fs-3"></i>
                </div>
                <div>
                    <span class="text-secondary small uppercase fw-semibold">Total Revenue</span>
                    <h3 class="mb-0 fw-extrabold text-dark">$<?php echo number_format($total_revenue, 2); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Orders -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-primary-subtle text-primary">
                    <i class="bi bi-cart-check fs-3"></i>
                </div>
                <div>
                    <span class="text-secondary small uppercase fw-semibold">Orders Received</span>
                    <h3 class="mb-0 fw-extrabold text-dark"><?php echo $total_orders; ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Registered Users -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-info-subtle text-info">
                    <i class="bi bi-people fs-3"></i>
                </div>
                <div>
                    <span class="text-secondary small uppercase fw-semibold">Active Customers</span>
                    <h3 class="mb-0 fw-extrabold text-dark"><?php echo $total_customers; ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Products -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-warning-subtle text-warning">
                    <i class="bi bi-box-seam fs-3"></i>
                </div>
                <div>
                    <span class="text-secondary small uppercase fw-semibold">Catalog Size</span>
                    <h3 class="mb-0 fw-extrabold text-dark"><?php echo $total_catalog; ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Orders Listing -->
<div class="card border-0 shadow-sm rounded-4 bg-white p-4">
    <h5 class="fw-bold mb-4"><i class="bi bi-receipt me-2 text-primary"></i>Customer Orders List</h5>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="bi bi-receipt-cutoff text-muted display-4"></i>
            <h6 class="mt-3 text-secondary">No customer orders recorded yet.</h6>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle table-premium mb-0">
                <thead>
                    <tr>
                        <th scope="col" class="ps-3">Order ID</th>
                        <th scope="col">Customer</th>
                        <th scope="col">Order Date</th>
                        <th scope="col">Grand Total</th>
                        <th scope="col">Shipping Address</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-center">Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <!-- Main row -->
                        <tr>
                            <!-- Order ID -->
                            <td class="ps-3 fw-bold">
                                #LC-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                            </td>
                            <!-- Customer Info -->
                            <td>
                                <div>
                                    <strong class="text-dark"><?php echo sanitize($order['username']); ?></strong>
                                    <p class="mb-0 text-muted small"><?php echo sanitize($order['email']); ?></p>
                                </div>
                            </td>
                            <!-- Date -->
                            <td>
                                <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                            </td>
                            <!-- Grand Total -->
                            <td class="fw-bold text-indigo">
                                $<?php echo number_format($order['total_price'], 2); ?>
                            </td>
                            <!-- Shipping info -->
                            <td class="text-truncate" style="max-width: 180px;" title="<?php echo sanitize($order['shipping_address']); ?>">
                                <?php echo sanitize($order['shipping_address']); ?>
                            </td>
                            <!-- Status -->
                            <td>
                                <?php 
                                if ($order['status'] == 'pending') {
                                    echo '<span class="order-badge-pending">Pending</span>';
                                } elseif ($order['status'] == 'shipped') {
                                    echo '<span class="order-badge-shipped">Shipped</span>';
                                } else {
                                    echo '<span class="order-badge-delivered">Delivered</span>';
                                }
                                ?>
                            </td>
                            <!-- Manage Status & Expand items -->
                            <td>
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <!-- Trigger Drawer button -->
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#items-<?php echo $order['id']; ?>" aria-expanded="false" aria-controls="items-<?php echo $order['id']; ?>" title="View Items">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <!-- Status Update Form -->
                                    <form action="index.php" method="POST" class="d-inline-flex align-items-center gap-1">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" class="form-select form-select-sm" style="width: 110px;">
                                            <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="shipped" <?php echo ($order['status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo ($order['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-sm btn-premium py-1 px-2 border-0">Update</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Expandable Row (Order Items) -->
                        <tr class="collapse bg-light" id="items-<?php echo $order['id']; ?>">
                            <td colspan="7" class="p-4">
                                <div class="card shadow-sm border-0 rounded-3 p-3 bg-white">
                                    <h6 class="fw-bold mb-3 text-secondary">Items ordered:</h6>
                                    <?php
                                    try {
                                        $stmt_it = $pdo->prepare("
                                            SELECT oi.*, p.name AS product_name 
                                            FROM order_items oi 
                                            LEFT JOIN products p ON oi.product_id = p.id 
                                            WHERE oi.order_id = ?
                                        ");
                                        $stmt_it->execute([$order['id']]);
                                        $order_items = $stmt_it->fetchAll();
                                    } catch (PDOException $e) {
                                        $order_items = [];
                                    }
                                    ?>
                                    <ul class="list-group list-group-flush mb-0">
                                        <?php foreach ($order_items as $item): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                <div>
                                                    <strong class="text-dark"><?php echo sanitize($item['product_name'] ?? 'Product Removed'); ?></strong>
                                                    <span class="text-muted small ms-2">x<?php echo $item['quantity']; ?></span>
                                                </div>
                                                <span class="fw-semibold text-secondary">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <hr>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span>Customer Contact: <strong><?php echo sanitize($order['contact_number']); ?></strong></span>
                                        <span>Order Date: <strong><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></strong></span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
