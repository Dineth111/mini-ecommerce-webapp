</div> <!-- Close .container from header -->

<!-- Footer Section -->
<footer class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5 class="mb-3 text-white"><i class="bi bi-gem me-2"></i>LuxeCommerce</h5>
                <p class="small text-muted">A premium, highly secure E-Commerce application designed for simplicity, speed, and modern user experiences.</p>
            </div>
            <div class="col-md-4 mb-4">
                <h5 class="mb-3 text-white">Quick Links</h5>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="<?php echo $path_to_root; ?>index.php">Browse Products</a></li>
                    <li class="mb-2"><a href="<?php echo $path_to_root; ?>cart.php">Shopping Cart</a></li>
                    <li class="mb-2"><a href="<?php echo $path_to_root; ?>user/orders.php">Order History</a></li>
                    <li class="mb-2"><a href="<?php echo $path_to_root; ?>admin/login.php">Admin Panel</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5 class="mb-3 text-white">Need Support?</h5>
                <p class="small text-muted mb-2">Our support is active 24/7 to assist with your premium shopping experiences.</p>
                <div class="d-flex gap-3">
                    <a href="#" class="fs-5"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="fs-5"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="fs-5"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="fs-5"><i class="bi bi-github"></i></a>
                </div>
            </div>
        </div>
        <hr class="border-secondary my-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> LuxeCommerce. All rights reserved.</p>
            <p class="mb-0">Handcrafted with <i class="bi bi-heart-fill text-danger mx-1"></i> and Pure PHP.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JavaScript -->
<script src="<?php echo $path_to_root; ?>assets/js/main.js"></script>

</body>
</html>
