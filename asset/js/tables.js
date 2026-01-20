// Tables page: AJAX CRUD (List view only)

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
  var form = document.getElementById("tableForm");
  if (!form) return;

  // Previously AJAX CRUD was disabled.
  // The tables page now uses module-specific AJAX calls to interact with the server.

  function csrf() {
    var el = document.querySelector('input[name="csrf_token"]');
    return el ? el.value : "";
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    var actionEl = form.querySelector('input[name="action"]');
    var idEl = form.querySelector('input[name="id"]');

    var tableNumber = (
      (document.getElementById("table_number") || {}).value || ""
    ).trim();
    var capacity = (document.getElementById("capacity") || {}).value || "";
    var status = (document.getElementById("status") || {}).value || "available";

    if (tableNumber === "") {
      alert("Table number required");
      return;
    }
    if (capacity === "" || isNaN(capacity) || Number(capacity) <= 0) {
      alert("Valid capacity required");
      return;
    }

    var payload = {
      csrf_token: csrf(),
      id: idEl ? idEl.value : "",
      table_number: tableNumber,
      capacity: capacity,
      status: status,
    };

    var action = actionEl ? actionEl.value : "create";
    var key = action === "update" ? "tableUpdate" : "tableCreate";

    postJSON("../controller/TableAjaxController.php", key, payload, function (res) {
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
      if (!confirm("Are you sure you want to delete this table?")) return;

      postJSON(
        "../controller/TableAjaxController.php",
        "tableDelete",
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
