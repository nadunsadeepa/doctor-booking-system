/* =============================================================
   assets/js/register.js
   Live "passwords match" feedback on the registration form.
   ============================================================= */

document.addEventListener('DOMContentLoaded', function () {
    const password = document.getElementById('password');
    const confirm  = document.getElementById('confirm_password');
    const hint     = document.getElementById('matchHint');

    if (!password || !confirm || !hint) return;

    function checkMatch() {
        if (confirm.value === '') {
            hint.textContent = '';
            return;
        }
        if (password.value === confirm.value) {
            hint.textContent = 'Passwords match';
            hint.style.color = '#227044';
        } else {
            hint.textContent = 'Passwords do not match';
            hint.style.color = '#C4453B';
        }
    }

    password.addEventListener('input', checkMatch);
    confirm.addEventListener('input', checkMatch);
});
