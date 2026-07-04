<?php
require_once '../config/db.php';

// Redirect to shop if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = sanitize($_POST['login_input']);
    $password = $_POST['password'];

    if (empty($login_input) || empty($password)) {
        $error_msg = "Please fill in all fields.";
    } else {
        try {
            // Retrieve user details by email or username
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$login_input, $login_input]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Password matches, create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];

                // Redirect to homepage
                header("Location: ../index.php");
                exit;
            } else {
                $error_msg = "Invalid username/email or password.";
            }
        } catch (PDOException $e) {
            $error_msg = "Database error: " . $e->getMessage();
        }
    }
}

$page_title = "Log In";
require_once '../includes/header.php';
?>

<div class="row justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="col-md-5">
        <div class="card glass-container p-4">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h3 class="fw-extrabold text-primary">Welcome Back</h3>
                    <p class="text-muted">Sign in to access your dashboard and cart</p>
                </div>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <!-- Username or Email -->
                    <div class="mb-3">
                        <label for="login_input" class="form-label small fw-semibold text-secondary">Username or Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                            <input type="text" name="login_input" id="login_input" class="form-control form-control-custom border-start-0 ps-0" placeholder="Username or Email" required value="<?php echo isset($login_input) ? $login_input : ''; ?>">
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-lock text-muted"></i></span>
                            <input type="password" name="password" id="password" class="form-control form-control-custom border-start-0 ps-0" placeholder="Password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-premium w-100 mb-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Log In
                    </button>
                </form>

                <div class="text-center">
                    <p class="small text-muted mb-0">Don't have an account? <a href="register.php" class="text-primary fw-bold text-decoration-none">Register Here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
