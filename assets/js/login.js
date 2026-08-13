/* =============================================================
   assets/js/login.js
   Small progressive-enhancement script for the login pages.
   ============================================================= */

document.addEventListener('DOMContentLoaded', function () {

    // ---- Toggle password visibility ----
    const toggleBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            toggleBtn.textContent = isHidden ? 'Hide' : 'Show';
        });
    }

    // ---- Basic client-side check before submit (server still re-validates) ----
    const form = document.getElementById('loginForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            const email = form.querySelector('[name="email"]');
            const password = form.querySelector('[name="password"]');

            if (!email.value.trim() || !password.value.trim()) {
                e.preventDefault();
                alert('Please fill in both email and password.');
            }
        });
    }

});
