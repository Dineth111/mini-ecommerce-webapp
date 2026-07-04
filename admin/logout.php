<?php
require_once '../config/db.php';

// Unset administrative session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_email']);

// Redirect to admin login page
header("Location: login.php");
exit;
?>
