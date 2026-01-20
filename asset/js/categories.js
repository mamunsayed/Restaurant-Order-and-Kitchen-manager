// Categories page: JS validation + AJAX CRUD (no UI change)

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
  var form = document.getElementById("categoryForm");
  if (!form) return;

  // Previously AJAX CRUD was disabled.
  // The categories page now uses module-specific AJAX calls to interact with the server.

  function csrf() {
    var el = document.querySelector('input[name="csrf_token"]');
    return el ? el.value : "";
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    var actionEl = form.querySelector('input[name="action"]');
    var idEl = form.querySelector('input[name="id"]');
    var name = (document.getElementById("name") || {}).value || "";
    name = name.trim();

    if (name === "") {
      alert("Please enter category name");
      return false;
    }

    var payload = {
      csrf_token: csrf(),
      id: idEl ? idEl.value : "",
      name: name,
      description: (
        (document.getElementById("description") || {}).value || ""
      ).trim(),
      status: (document.getElementById("status") || {}).value || "active",
    };

    var action = actionEl ? actionEl.value : "create";
    var key = action === "update" ? "categoryUpdate" : "categoryCreate";

    postJSON("../controller/CategoryAjaxController.php", key, payload, function (res) {
      alert(res.message || "Done");
      if (res.success) window.location.reload();
    });

    return false;
  });

  // Delete forms (inline)
  document.querySelectorAll("form").forEach(function (f) {
    var act = f.querySelector('input[name="action"][value="delete"]');
    var id = f.querySelector('input[name="id"]');
    if (!act || !id) return;

    f.addEventListener("submit", function (e) {
      e.preventDefault();
      if (!confirm("Are you sure you want to delete this category?"))
        return false;

      postJSON(
        "../controller/CategoryAjaxController.php",
        "categoryDelete",
        {
          csrf_token: csrf(),
          id: id.value,
        },
        function (res) {
          alert(res.message || "Deleted");
          if (res.success) window.location.reload();
        },
      );

      return false;
    });
  });
})();
