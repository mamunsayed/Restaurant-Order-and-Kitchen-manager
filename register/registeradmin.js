function clearErrors() {
    document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
}

function validateEmail(value) {
    return /^\S+@\S+\.\S+$/.test(value);
}

document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    clearErrors();

    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const restaurant = document.getElementById('restaurant').value.trim();
    const password = document.getElementById('password').value.trim();
    const confirmPassword = document.getElementById('confirmPassword').value.trim();

    let hasError = false;

    if (!name) {
        document.getElementById('nameError').textContent = 'Name is required.';
        hasError = true;
    }
    if (!email) {
        document.getElementById('emailError').textContent = 'Email is required.';
        hasError = true;
    } else if (!validateEmail(email)) {
        document.getElementById('emailError').textContent = 'Enter a valid email address.';
        hasError = true;
    }
    if (!restaurant) {
        document.getElementById('restaurantError').textContent = 'Restaurant / Organization is required.';
        hasError = true;
    }
    if (!password) {
        document.getElementById('passwordError').textContent = 'Password is required.';
        hasError = true;
    } else if (password.length < 6) {
        document.getElementById('passwordError').textContent = 'Password must be at least 6 characters.';
        hasError = true;
    }
    if (!confirmPassword) {
        document.getElementById('confirmPasswordError').textContent = 'Please confirm your password.';
        hasError = true;
    } else if (password && confirmPassword && password !== confirmPassword) {
        document.getElementById('confirmPasswordError').textContent = 'Passwords do not match.';
        hasError = true;
    }

    if (hasError) return;

    alert('Admin/Owner account created successfully. You can now log in.');
    window.location.href = 'login.html';
});