// Staff page: AJAX CRUD

(function () {
  // Define postJSON helper for this module. This avoids reliance on a global ajax.js file.
  function postJSON(url, key, payload, cb) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
      var res = null;
      try {
        res = JSON.parse(this.responseText);
      } catch (e) {
        return cb({ success: false, message: "Server error" });
      }
      cb(res);
    };
    xhr.onerror = function () {
      cb({ success: false, message: "Network error" });
    };
    xhr.send(key + "=" + encodeURIComponent(JSON.stringify(payload)));
  }
  var form = document.getElementById("staffForm");
  if (!form) return;

  // Previously AJAX CRUD was disabled.
  // The staff page now uses module-specific AJAX calls to interact with the server.

  function csrf() {
    var el = document.querySelector('input[name="csrf_token"]');
    return el ? el.value : "";
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    var actionEl = form.querySelector('input[name="action"]');
    var idEl = form.querySelector('input[name="id"]');

    var fullName = (
      (document.getElementById("full_name") || {}).value || ""
    ).trim();
    var email = ((document.getElementById("email") || {}).value || "").trim();
    var phone = ((document.getElementById("phone") || {}).value || "").trim();
    var position = (
      (document.getElementById("position") || {}).value || ""
    ).trim();
    var salary = (document.getElementById("salary") || {}).value || "0";
    var hireDate = (document.getElementById("hire_date") || {}).value || "";
    var address = ((document.getElementById("address") || {}).value || "").trim();
    var status = (document.getElementById("status") || {}).value || "active";

    if (fullName === "") {
      alert("Name required");
      return;
    }
    if (email === "") {
      alert("Email required");
      return;
    }

    var payload = {
      csrf_token: csrf(),
      id: idEl ? idEl.value : "",
      full_name: fullName,
      email: email,
      phone: phone,
      position: position,
      salary: salary,
      hire_date: hireDate,
      address: address,
      status: status,
    };

    var action = actionEl ? actionEl.value : "create";
    var key = action === "update" ? "staffUpdate" : "staffCreate";

    postJSON("../controller/StaffAjaxController.php", key, payload, function (res) {
      alert(res.message || "Done");
      if (res.success) window.location.reload();
    });
  });

  document.querySelectorAll("form").forEach(function (f) {
    var act = f.querySelector('input[name="action"][value="delete"]');
    var id = f.querySelector('input[name="id"]');
    if (!act || !id) return;

    f.addEventListener("submit", function (e) {
      e.preventDefault();
      if (!confirm("Are you sure you want to delete this staff?")) return;

      postJSON(
        "../controller/StaffAjaxController.php",
        "staffDelete",
        {
          csrf_token: csrf(),
          id: id.value,
        },
        function (res) {
          alert(res.message || "Deleted");
          if (res.success) window.location.reload();
        },
      );
    });
  });
})();
