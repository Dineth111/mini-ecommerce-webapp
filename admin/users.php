<?php
require_once '../config/db.php';

$page_title = "Customer Accounts";
require_once '../includes/admin_header.php';

$users = [];
try {
    // Fetch users with their respective order counts
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.email, u.created_at, COUNT(o.id) AS total_orders 
        FROM users u 
        LEFT JOIN orders o ON u.id = o.user_id 
        GROUP BY u.id 
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Failed to load customers: " . $e->getMessage();
}
?>

<?php if (isset($error_msg)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-octagon me-2"></i> <?php echo $error_msg; ?>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 bg-white p-4">
    <h5 class="fw-bold mb-4"><i class="bi bi-people me-2 text-primary"></i>Registered Customer Accounts</h5>

    <?php if (empty($users)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-people-fill display-4"></i>
            <h6 class="mt-3">No customer accounts registered yet.</h6>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle table-premium mb-0">
                <thead>
                    <tr>
                        <th scope="col" class="ps-3">Customer ID</th>
                        <th scope="col">Username</th>
                        <th scope="col">Email Address</th>
                        <th scope="col">Date Registered</th>
                        <th scope="col" class="text-center">Orders Placed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <!-- Customer ID -->
                            <td class="ps-3 fw-bold text-secondary">
                                #CUST-<?php echo str_pad($user['id'], 5, '0', STR_PAD_LEFT); ?>
                            </td>
                            <!-- Username -->
                            <td>
                                <strong><?php echo sanitize($user['username']); ?></strong>
                            </td>
                            <!-- Email -->
                            <td>
                                <a href="mailto:<?php echo sanitize($user['email']); ?>" class="text-indigo text-decoration-none">
                                    <?php echo sanitize($user['email']); ?>
                                </a>
                            </td>
                            <!-- Date Registered -->
                            <td>
                                <?php echo date('M d, Y h:i A', strtotime($user['created_at'])); ?>
                            </td>
                            <!-- Order Count badge -->
                            <td class="text-center">
                                <?php if ($user['total_orders'] > 0): ?>
                                    <span class="badge bg-indigo-subtle text-primary px-3 py-2 rounded-pill fw-bold">
                                        <?php echo $user['total_orders']; ?> orders
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted px-3 py-2 rounded-pill">
                                        No orders
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
