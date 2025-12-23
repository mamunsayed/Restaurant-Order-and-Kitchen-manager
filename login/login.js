function clearErrors() {
    document.getElementById("emailError").innerHTML = "";
    document.getElementById("passwordError").innerHTML = "";
}

function isValidEmail(email) {
    var atPos = email.indexOf("@");
    var dotPos = email.lastIndexOf(".");
    if (atPos < 1) return false;
    if (dotPos < atPos + 2) return false;
    if (dotPos + 2 >= email.length) return false;
    return true;
}

function validateLogin() {
    clearErrors();

    var email = document.getElementById("email").value;
    var password = document.getElementById("password").value;

    var hasError = false;

    if (email === "") {
        document.getElementById("emailError").innerHTML = "Please enter your email.";
        hasError = true;
    } else if (!isValidEmail(email)) {
        document.getElementById("emailError").innerHTML = "Please enter a valid email.";
        hasError = true;
    }

    if (password === "") {
        document.getElementById("passwordError").innerHTML = "Please enter your password.";
        hasError = true;
    } else if (password.length < 6) {
        document.getElementById("passwordError").innerHTML = "Password must be at least 6 characters.";
        hasError = true;
    }

    if (hasError) return false;

    alert("Login is valid. (Demo only)");
    return false; // stop actual form submission
}