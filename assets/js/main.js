document.addEventListener('DOMContentLoaded', function() {
    // 1. Automatically dismiss Bootstrap alerts after 4 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 4000);
    });

    // 2. Add dynamic active navigation line highlight
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link-custom');
    navLinks.forEach(function(link) {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href)) {
            link.classList.add('text-primary');
            link.style.fontWeight = '700';
        }
    });

    // 3. Form Validation (Password matching confirmation helper)
    const registerForm = document.querySelector('form[action*="register.php"]');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please re-enter.');
            }
        });
    }
});
