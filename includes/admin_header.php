<?php
// Determine relative root path dynamically
$path_to_root = "";
if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
    $path_to_root = "../";
} else {
    $path_to_root = "./";
}

// Global authentication gate for admin pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header("Location: " . $path_to_root . "admin/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " - LuxeCommerce Admin" : "LuxeCommerce Admin Panel"; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo $path_to_root; ?>assets/css/style.css" rel="stylesheet">
    <!-- Theme Manager Init -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body>

<?php if (isset($_SESSION['admin_id'])): ?>
<!-- Logged-in Admin Layout (Sidebar & Main content grid) -->
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <nav class="col-md-3 col-lg-2 d-md-block admin-sidebar collapse collapse-horizontal" id="sidebarMenu">
            <div class="position-sticky pt-3">
                <div class="px-4 py-3 mb-4 border-bottom border-secondary text-center">
                    <h5 class="text-white mb-0"><i class="bi bi-shield-lock me-2 text-indigo"></i>LuxeAdmin</h5>
                    <span class="badge bg-secondary mt-2">Administrator</span>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="admin-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="<?php echo $path_to_root; ?>admin/index.php">
                            <i class="bi bi-speedometer2 me-2"></i>Orders Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="admin-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'categories.php') ? 'active' : ''; ?>" href="<?php echo $path_to_root; ?>admin/categories.php">
                            <i class="bi bi-tags me-2"></i>Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="admin-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>" href="<?php echo $path_to_root; ?>admin/products.php">
                            <i class="bi bi-box-seam me-2"></i>Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="admin-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>" href="<?php echo $path_to_root; ?>admin/users.php">
                            <i class="bi bi-people me-2"></i>Customers
                        </a>
                    </li>
                    <li class="nav-item mt-4 border-top border-secondary pt-3">
                        <a class="admin-nav-link text-info" href="<?php echo $path_to_root; ?>index.php" target="_blank">
                            <i class="bi bi-shop me-2"></i>View Live Shop
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="admin-nav-link text-danger" href="<?php echo $path_to_root; ?>admin/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 animate-fade-in">
            <!-- Header for Mobile/Admin Panel navbar -->
            <header class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-dark d-md-none me-3" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                        <i class="bi bi-list"></i>
                    </button>
                    <h2 class="h4 mb-0"><?php echo isset($page_title) ? $page_title : "Dashboard"; ?></h2>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <!-- Theme Switcher Toggle -->
                    <button class="btn btn-sm btn-outline-secondary border-0" id="themeToggleBtn" onclick="toggleTheme()" title="Toggle Light/Dark Mode" type="button">
                        <i id="themeToggleIcon" class="bi bi-sun-fill" style="font-size: 1.15rem;"></i>
                    </button>
                    <div class="text-secondary small">
                        Logged in as: <strong><?php echo sanitize($_SESSION['admin_username']); ?></strong>
                    </div>
                </div>
            </header>
<?php else: ?>
<!-- Unauthenticated Header Wrapper (for login page) -->
<div class="position-absolute top-0 end-0 p-3">
    <!-- Theme Switcher Toggle for Login -->
    <button class="btn btn-sm btn-outline-secondary border-0" id="themeToggleBtn" onclick="toggleTheme()" title="Toggle Light/Dark Mode" type="button">
        <i id="themeToggleIcon" class="bi bi-sun-fill" style="font-size: 1.15rem;"></i>
    </button>
</div>
<div class="container py-5 animate-fade-in">
<?php endif; ?>

<script>
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme') || 'dark';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
    const icon = document.getElementById('themeToggleIcon');
    if (icon) {
        if (theme === 'light') {
            icon.className = 'bi bi-moon-stars-fill';
        } else {
            icon.className = 'bi bi-sun-fill';
        }
    }
}

// Initial icon sync on load
document.addEventListener('DOMContentLoaded', () => {
    const theme = localStorage.getItem('theme') || 'dark';
    updateThemeIcon(theme);
});
</script>
