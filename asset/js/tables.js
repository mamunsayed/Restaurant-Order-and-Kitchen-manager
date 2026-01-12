// Tables page: AJAX CRUD (List view only)

(function () {
  var form = document.getElementById("tableForm");
  if (!form) return;

  // NOTE: AJAX CRUD is intentionally disabled.
  // This page now uses normal POST form submits to avoid AJAX-related errors.
  return;

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

    postJSON("../controller/AjaxController.php", key, payload, function (res) {
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
        "../controller/AjaxController.php",
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
