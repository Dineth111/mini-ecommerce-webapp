<?php
require_once '../config/db.php';

// Unset only customer session variables
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['email']);

// Redirect to login page
header("Location: login.php");
exit;
?>
