document.addEventListener('DOMContentLoaded', function() {

    // Mobile nav toggle
    var navToggle = document.getElementById('navToggle');
    var navLinks = document.getElementById('navLinks');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function() {
            navLinks.classList.toggle('show');
        });
    }

    // Auto-hide alerts after 5 seconds
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 500);
        }, 5000);
    });

    // Confirm delete
    var deleteBtns = document.querySelectorAll('.btn-delete');
    deleteBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });

    // Confirm consume
    var consumeBtns = document.querySelectorAll('.btn-consume');
    consumeBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Mark this item as consumed?')) {
                e.preventDefault();
            }
        });
    });

    // Confirm donate
    var donateBtns = document.querySelectorAll('.btn-donate');
    donateBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Convert this item to a donation listing?')) {
                e.preventDefault();
            }
        });
    });

    // Confirm claim
    var claimBtns = document.querySelectorAll('.btn-claim');
    claimBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Claim this donation?')) {
                e.preventDefault();
            }
        });
    });

    // Open modal
    var modalTriggers = document.querySelectorAll('[data-modal]');
    modalTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            var target = document.getElementById(this.dataset.modal);
            if (target) target.classList.add('active');
        });
    });

    // Close modal on overlay click
    var modals = document.querySelectorAll('.modal-overlay');
    modals.forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });

    // Close modal buttons
    var closeModalBtns = document.querySelectorAll('.close-modal');
    closeModalBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            this.closest('.modal-overlay').classList.remove('active');
        });
    });

    // --- Auth pages: show/hide password -------------------------------
    var visibilityBtns = document.querySelectorAll('.toggle-visibility');
    visibilityBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetInput = document.getElementById(this.dataset.target);
            if (!targetInput) return;
            var icon = this.querySelector('i');
            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
                this.setAttribute('aria-label', 'Hide password');
            } else {
                targetInput.type = 'password';
                if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
                this.setAttribute('aria-label', 'Show password');
            }
        });
    });

    // --- Register page: live password strength meter ------------------
    var passwordField = document.getElementById('password');
    var strengthBar = document.querySelector('#passwordStrength .password-strength-bar span');
    var strengthLabel = document.querySelector('#passwordStrength .password-strength-label');

    function scorePassword(value) {
        var score = 0;
        if (value.length >= 8) score++;
        if (value.length >= 12) score++;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
        if (/[0-9]/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++;
        return score;
    }

    if (passwordField && strengthBar) {
        passwordField.addEventListener('input', function() {
            var value = passwordField.value;
            var score = scorePassword(value);
            var pct = Math.min(100, (score / 5) * 100);
            var color = '#dc3545';
            var label = 'At least 8 characters, with a letter and a number';

            if (value.length === 0) {
                pct = 0;
                label = 'At least 8 characters, with a letter and a number';
            } else if (score <= 1) {
                color = '#dc3545';
                label = 'Weak — add more characters or a number';
            } else if (score <= 3) {
                color = '#e67e22';
                label = 'Okay — try mixing in a symbol or capital letter';
            } else {
                color = '#2d6a4f';
                label = 'Strong password';
            }

            strengthBar.style.width = pct + '%';
            strengthBar.style.background = color;
            if (strengthLabel) strengthLabel.textContent = label;
        });
    }

    // --- Register page: live confirm-password + email hints -----------
    var confirmField = document.getElementById('confirm_password');
    var matchHint = document.getElementById('matchHint');

    function checkMatch() {
        if (!passwordField || !confirmField || !matchHint) return;
        if (confirmField.value.length === 0) {
            matchHint.textContent = '';
            matchHint.className = 'field-hint';
            return;
        }
        if (confirmField.value === passwordField.value) {
            matchHint.textContent = 'Passwords match';
            matchHint.className = 'field-hint hint-valid';
        } else {
            matchHint.textContent = 'Passwords do not match yet';
            matchHint.className = 'field-hint hint-invalid';
        }
    }
    if (confirmField) {
        confirmField.addEventListener('input', checkMatch);
        if (passwordField) passwordField.addEventListener('input', checkMatch);
    }

    var emailField = document.getElementById('email');
    var emailHint = document.getElementById('emailHint');
    if (emailField && emailHint) {
        emailField.addEventListener('input', function() {
            if (emailField.value.length === 0) {
                emailHint.textContent = '';
                emailHint.className = 'field-hint';
                return;
            }
            var valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value);
            emailHint.textContent = valid ? '' : 'Enter a valid email address';
            emailHint.className = valid ? 'field-hint' : 'field-hint hint-invalid';
        });
    }

    // --- Verify page: digits-only code input ---------------------------
    var codeField = document.getElementById('code');
    if (codeField) {
        codeField.addEventListener('input', function() {
            codeField.value = codeField.value.replace(/\D/g, '').slice(0, 6);
        });
        codeField.focus();
    }

});
