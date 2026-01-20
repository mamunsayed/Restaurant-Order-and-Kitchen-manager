// Customers page: AJAX CRUD

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
  var form = document.getElementById("customerForm");
  if (!form) return;

  // Previously AJAX CRUD was disabled.
  // The customers page now uses module-specific AJAX calls to interact with the server.

  function csrf() {
    var el = document.querySelector('input[name="csrf_token"]');
    return el ? el.value : "";
  }

  function val(name) {
    var el = form.querySelector('[name="' + name + '"]');
    return el ? (el.value || "").trim() : "";
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    var actionEl = form.querySelector('input[name="action"]');
    var idEl = form.querySelector('input[name="id"]');

    var name = val("name");
    var email = val("email");
    var phone = val("phone");
    var address = val("address");

    if (name === "") {
      alert("Customer name required");
      return;
    }

    var payload = {
      csrf_token: csrf(),
      id: idEl ? idEl.value : "",
      name: name,
      email: email,
      phone: phone,
      address: address,
    };

    var action = actionEl ? actionEl.value : "create";
    var key = action === "update" ? "customerUpdate" : "customerCreate";

    postJSON("../controller/CustomerAjaxController.php", key, payload, function (res) {
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
      if (!confirm("Delete this customer?")) return;

      postJSON(
        "../controller/CustomerAjaxController.php",
        "customerDelete",
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
