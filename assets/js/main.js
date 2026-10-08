/**
 * Client-Side Interactivity & Helper Functions
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Bootstrap Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 2. Demo Login Helper
    window.fillDemoCredentials = function (role) {
        const emailInput = document.getElementById('login-email');
        const passwordInput = document.getElementById('login-password');
        
        if (!emailInput || !passwordInput) return;

        if (role === 'admin') {
            emailInput.value = 'admin@campus.edu';
            passwordInput.value = 'AdminPassword123!';
        } else if (role === 'company') {
            emailInput.value = 'recruiter@google.com';
            passwordInput.value = 'CompanyPassword123!';
        } else if (role === 'student') {
            emailInput.value = 'alex.student@campus.edu';
            passwordInput.value = 'StudentPassword123!';
        }

        // Animate brief highlight
        [emailInput, passwordInput].forEach(el => {
            el.style.backgroundColor = '#ecfdf5';
            setTimeout(() => { el.style.backgroundColor = ''; }, 600);
        });
    };

    // 3. Confirm Delete / Status change
    window.confirmAction = function(message) {
        return confirm(message || 'Are you sure you want to perform this action?');
    };

    // 4. Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = bootstrap.Alert.getInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });
});
