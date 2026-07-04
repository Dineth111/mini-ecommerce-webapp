<?php
require_once '../config/db.php';

// Redirect to shop if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$error_msg = "";
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_msg = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() > 0) {
                $error_msg = "Username or Email is already registered.";
            } else {
                // Hash Password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // Insert User into Database
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                if ($stmt->execute([$username, $email, $hashed_password])) {
                    $success_msg = "Account created successfully! You can now log in.";
                } else {
                    $error_msg = "Failed to register user. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    }
}

$page_title = "Register";
require_once '../includes/header.php';
?>

<div class="row justify-content-center align-items-center animate-fade-in" style="min-height: 70vh;">
    <div class="col-md-5">
        <div class="glass-container p-4 p-md-5">
            <div class="text-center mb-4">
                <h3 class="fw-extrabold text-primary">Create Your Account</h3>
                <p class="text-muted">Start shopping premium goods today</p>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="needs-validation" novalidate>
                <!-- Username -->
                <div class="mb-3">
                    <label for="username" class="form-label small fw-bold text-secondary">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-person text-muted"></i></span>
                        <input type="text" name="username" id="username" class="form-control form-control-custom border-start-0" placeholder="e.g. johndoe" required value="<?php echo isset($username) ? $username : ''; ?>">
                    </div>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label small fw-bold text-secondary">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-envelope text-muted"></i></span>
                        <input type="email" name="email" id="email" class="form-control form-control-custom border-start-0" placeholder="e.g. john@example.com" required value="<?php echo isset($email) ? $email : ''; ?>">
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label small fw-bold text-secondary">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" name="password" id="password" class="form-control form-control-custom border-start-0" placeholder="Min. 6 characters" required>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="mb-4">
                    <label for="confirm_password" class="form-label small fw-bold text-secondary">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-lock-fill text-muted"></i></span>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control form-control-custom border-start-0" placeholder="Repeat password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-premium w-100 mb-3">
                    <i class="bi bi-person-plus-fill me-2"></i>Register Account
                </button>
            </form>

            <div class="text-center">
                <p class="small text-muted mb-0">Already have an account? <a href="login.php" class="text-primary fw-bold text-decoration-none">Log In Here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
