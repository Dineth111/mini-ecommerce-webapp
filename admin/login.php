<?php
require_once '../config/db.php';

// Redirect to admin dashboard if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_msg = "Please enter both username and password.";
    } else {
        try {
            // Retrieve admin details
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Admin matches, create session
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];

                header("Location: index.php");
                exit;
            } else {
                $error_msg = "Invalid Admin credentials.";
            }
        } catch (PDOException $e) {
            $error_msg = "Database error: " . $e->getMessage();
        }
    }
}

$page_title = "Admin Login";
require_once '../includes/admin_header.php';
?>

<div class="row justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="col-md-5 col-lg-4">
        <div class="card glass-container shadow-lg border-0 p-4" style="background: rgba(15, 23, 42, 0.95); border: 1px solid rgba(255,255,255,0.1) !important; color: #fff;">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h3 class="fw-extrabold text-indigo" style="color: #818cf8;"><i class="bi bi-shield-lock-fill me-2"></i>LuxeAdmin</h3>
                    <p class="text-muted small">Authorize secure administrative access</p>
                </div>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0" role="alert" style="background-color: rgba(239, 68, 68, 0.2); color: #fca5a5;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <!-- Username -->
                    <div class="mb-3">
                        <label for="username" class="form-label small fw-semibold text-muted">Username / Email</label>
                        <div class="input-group">
                            <span class="input-group-text border-0" style="background: #1e293b; color: #94a3b8;"><i class="bi bi-person"></i></span>
                            <input type="text" name="username" id="username" class="form-control border-0" style="background: #1e293b; color: #fff;" placeholder="Admin Username" required value="<?php echo isset($username) ? $username : ''; ?>">
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold text-muted">Secret Passphrase</label>
                        <div class="input-group">
                            <span class="input-group-text border-0" style="background: #1e293b; color: #94a3b8;"><i class="bi bi-key"></i></span>
                            <input type="password" name="password" id="password" class="form-control border-0" style="background: #1e293b; color: #fff;" placeholder="Admin Password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-premium w-100 py-2 border-0">
                        <i class="bi bi-shield-lock me-2"></i>Authenticate
                    </button>
                </form>

                <div class="text-center mt-3">
                    <a href="../index.php" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left"></i> Return to Shop</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
