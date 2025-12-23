function clearErrors() {
    document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
}

function validateEmail(value) {
    return /^\S+@\S+\.\S+$/.test(value);
}

document.getElementById('forgotForm').addEventListener('submit', function(e) {
    e.preventDefault();
    clearErrors();

    const email = document.getElementById('email').value.trim();
    let hasError = false;

    if (!email) {
        document.getElementById('emailError').textContent = 'Email is required.';
        hasError = true;
    } else if (!validateEmail(email)) {
        document.getElementById('emailError').textContent = 'Enter a valid email address.';
        hasError = true;
    }

    if (hasError) return;

    alert('Password reset link sent (demo only). In a real system, an email would now be sent.');
    window.location.href = 'login.html';
});