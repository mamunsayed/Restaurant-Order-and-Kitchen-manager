function submitReset() {
  const token = document.getElementById("token").value.trim();
  const pass = document.getElementById("password").value;
  const cpass = document.getElementById("cpassword").value;
  const csrf = document.querySelector('input[name="csrf_token"]').value;

  document.getElementById("passErr").innerText = "";
  document.getElementById("cpassErr").innerText = "";

  if (pass.length < 6) {
    document.getElementById("passErr").innerText =
      "Password must be at least 6 characters";
    return false;
  }
  if (pass !== cpass) {
    document.getElementById("cpassErr").innerText = "Passwords do not match";
    return false;
  }

  const xhr = new XMLHttpRequest();
  xhr.open("POST", "../controller/PasswordResetController.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xhr.onload = function () {
    try {
      const res = JSON.parse(this.responseText);
      alert(res.message || (res.success ? "Done" : "Failed"));
      if (res.success) {
        window.location.href = "index.php?success=Password reset successful";
      }
    } catch (e) {
      alert("Server error");
    }
  };
  xhr.send(
    "action=reset&token=" +
      encodeURIComponent(token) +
      "&password=" +
      encodeURIComponent(pass) +
      "&cpassword=" +
      encodeURIComponent(cpass) +
      "&csrf_token=" +
      encodeURIComponent(csrf),
  );
  return false;
}
