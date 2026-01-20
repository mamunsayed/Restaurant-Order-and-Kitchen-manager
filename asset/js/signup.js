// Client-side validation
document.getElementById("signupForm").onsubmit = function (e) {
  var isValid = true;

  // Reset errors
  var errors = document.querySelectorAll(".field-error");
  for (var i = 0; i < errors.length; i++) {
    errors[i].style.display = "none";
  }

  // Full name validation
  var fullName = document.getElementById("full_name").value.trim();
  if (fullName === "") {
    showError("full_name_error", "Full name is required");
    isValid = false;
  } else if (fullName.length < 2) {
    showError("full_name_error", "Full name must be at least 2 characters");
    isValid = false;
  }

  // Username validation
  var username = document.getElementById("username").value.trim();
  if (username === "") {
    showError("username_error", "Username is required");
    isValid = false;
  } else if (username.length < 3) {
    showError("username_error", "Username must be at least 3 characters");
    isValid = false;
  } else if (!/^[a-zA-Z0-9_]+$/.test(username)) {
    showError(
      "username_error",
      "Username can only contain letters, numbers and underscore",
    );
    isValid = false;
  }

  // Email validation
  var email = document.getElementById("email").value.trim();
  if (email === "") {
    showError("email_error", "Email is required");
    isValid = false;
  } else if (!isValidEmail(email)) {
    showError("email_error", "Please enter a valid email");
    isValid = false;
  }

  // Role validation
  var role = document.getElementById("role").value;
  if (role === "") {
    showError("role_error", "Please select a role");
    isValid = false;
  }

  // Password validation
  var password = document.getElementById("password").value;
  if (password === "") {
    showError("password_error", "Password is required");
    isValid = false;
  } else if (password.length < 6) {
    showError("password_error", "Password must be at least 6 characters");
    isValid = false;
  }

  // Confirm password validation
  var confirmPassword = document.getElementById("confirm_password").value;
  if (confirmPassword === "") {
    showError("confirm_password_error", "Please confirm your password");
    isValid = false;
  } else if (password !== confirmPassword) {
    showError("confirm_password_error", "Passwords do not match");
    isValid = false;
  }

  if (!isValid) {
    e.preventDefault();
    return false;
  }

  return true;
};

function showError(id, message) {
  var errorEl = document.getElementById(id);
  errorEl.textContent = message;
  errorEl.style.display = "block";
}

function isValidEmail(email) {
  var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}
