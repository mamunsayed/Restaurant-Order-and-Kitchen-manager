// Menu Items page: JS validation + AJAX CRUD

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
  var form = document.getElementById("menuItemForm");
  if (!form) return;

  // Previously AJAX CRUD was disabled.
  // The menu items page now uses module-specific AJAX calls to interact with the server.

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
    var categoryId = (document.getElementById("category_id") || {}).value || "";
    var price = (document.getElementById("price") || {}).value || "";
    var status = (document.getElementById("status") || {}).value || "available";
    var description = (
      (document.getElementById("description") || {}).value || ""
    ).trim();

    if (name === "") {
      alert("Please enter item name");
      return;
    }
    if (!categoryId) {
      alert("Please select category");
      return;
    }
    if (price === "" || isNaN(price) || Number(price) <= 0) {
      alert("Please enter a valid price");
      return;
    }

    var payload = {
      csrf_token: csrf(),
      id: idEl ? idEl.value : "",
      name: name,
      category_id: categoryId,
      price: price,
      description: description,
      status: status,
    };

    var action = actionEl ? actionEl.value : "create";
    var key = action === "update" ? "menuUpdate" : "menuCreate";

    postJSON("../controller/MenuItemAjaxController.php", key, payload, function (res) {
      alert(res.message || "Done");
      if (res.success) window.location.reload();
    });
  });

  // Delete forms
  document.querySelectorAll("form").forEach(function (f) {
    var act = f.querySelector('input[name="action"][value="delete"]');
    var id = f.querySelector('input[name="id"]');
    if (!act || !id) return;

    f.addEventListener("submit", function (e) {
      e.preventDefault();
      if (!confirm("Are you sure you want to delete this item?")) return;

      postJSON(
        "../controller/MenuItemAjaxController.php",
        "menuDelete",
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
