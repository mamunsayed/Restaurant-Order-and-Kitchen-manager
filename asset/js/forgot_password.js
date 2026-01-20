function submitForgot() {
  const email = document.getElementById("email").value.trim();
  const csrf = document.querySelector('input[name="csrf_token"]').value;

  document.getElementById("emailErr").innerText = "";
  if (email === "") {
    document.getElementById("emailErr").innerText = "Email is required";
    return false;
  }
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!re.test(email)) {
    document.getElementById("emailErr").innerText = "Invalid email";
    return false;
  }

  const xhr = new XMLHttpRequest();
  xhr.open("POST", "../controller/PasswordResetController.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xhr.onload = function () {
    try {
      const res = JSON.parse(this.responseText);
      alert(res.message || (res.success ? "Done" : "Failed"));
      if (res.success && res.reset_link) {
        // Local XAMPP: redirect to reset page
        window.location.href = res.reset_link;
      }
    } catch (e) {
      alert("Server error");
    }
  };
  xhr.send(
    "action=request&email=" +
      encodeURIComponent(email) +
      "&csrf_token=" +
      encodeURIComponent(csrf),
  );
  return false;
}
