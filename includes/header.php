<?php
// Determine relative root path dynamically
$path_to_root = "";
if (strpos($_SERVER['SCRIPT_NAME'], '/user/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
    $path_to_root = "../";
} else {
    $path_to_root = "./";
}

// Fetch dynamic cart count if user is logged in
$cart_count = 0;
if (isset($_SESSION['user_id']) && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_count = (int)$stmt->fetchColumn() ?: 0;
    } catch (PDOException $e) {
        // Silently fail or log cart query
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " - LuxeCommerce" : "LuxeCommerce - Premium E-Commerce Store"; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Custom stylesheet -->
    <link href="<?php echo $path_to_root; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light navbar-custom">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand navbar-brand-logo" href="<?php echo $path_to_root; ?>index.php">
            <i class="bi bi-gem me-2"></i>LuxeCommerce
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Left Navigation Links (Categories or Shop) -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="<?php echo $path_to_root; ?>index.php">
                        <i class="bi bi-grid-fill me-1"></i>Browse Shop
                    </a>
                </li>
            </ul>

            <!-- Right Navigation Controls -->
            <div class="d-flex align-items-center">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Logged-in Customer Navbar -->
                    <span class="text-secondary me-3 d-none d-md-inline">
                        Hello, <strong><?php echo sanitize($_SESSION['username']); ?></strong>
                    </span>

                    <a href="<?php echo $path_to_root; ?>user/orders.php" class="btn btn-sm btn-outline-secondary me-2 border-0">
                        <i class="bi bi-receipt me-1"></i>Orders
                    </a>

                    <a href="<?php echo $path_to_root; ?>cart.php" class="btn btn-sm btn-premium me-3 position-relative">
                        <i class="bi bi-cart3"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill badge-cart">
                            <?php echo $cart_count; ?>
                        </span>
                    </a>

                    <a href="<?php echo $path_to_root; ?>user/logout.php" class="btn btn-sm btn-premium-outline">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                <?php else: ?>
                    <!-- Guest Navbar -->
                    <a href="<?php echo $path_to_root; ?>user/login.php" class="btn btn-sm btn-premium-outline me-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login
                    </a>
                    <a href="<?php echo $path_to_root; ?>user/register.php" class="btn btn-sm btn-premium">
                        <i class="bi bi-person-plus me-1"></i>Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Page Content Starts Here -->
<div class="container py-5 flex-grow-1 animate-fade-in">
