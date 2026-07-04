<?php if (isset($_SESSION['admin_id'])): ?>
        </main> <!-- Close .col-md-9 main -->
    </div> <!-- Close .row -->
</div> <!-- Close .container-fluid -->
<?php else: ?>
</div> <!-- Close .container for login page -->
<?php endif; ?>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JavaScript -->
<script src="<?php echo $path_to_root; ?>assets/js/main.js"></script>

</body>
</html>
