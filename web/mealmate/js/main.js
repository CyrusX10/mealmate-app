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

});
