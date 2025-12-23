function clearErrors() {
    document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
}

document.getElementById('resetForm').addEventListener('submit', function(e) {
    e.preventDefault();
    clearErrors();

    const newPassword = document.getElementById('newPassword').value.trim();
    const confirmPassword = document.getElementById('confirmPassword').value.trim();

    let hasError = false;

    if (!newPassword) {
        document.getElementById('newPasswordError').textContent = 'New password is required.';
        hasError = true;
    } else if (newPassword.length < 6) {
        document.getElementById('newPasswordError').textContent = 'Password must be at least 6 characters.';
        hasError = true;
    }

    if (!confirmPassword) {
        document.getElementById('confirmPasswordError').textContent = 'Please confirm the new password.';
        hasError = true;
    } else if (newPassword && confirmPassword && newPassword !== confirmPassword) {
        document.getElementById('confirmPasswordError').textContent = 'Passwords do not match.';
        hasError = true;
    }

    if (hasError) return;

    alert('Password updated successfully (demo). You can now log in with your new password.');
    window.location.href = 'login.html';
});