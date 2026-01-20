// Kitchen page: AJAX status updates + auto refresh (no UI change)

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
  function csrf() {
    var el = document.querySelector('input[name="csrf_token"]');
    return el ? el.value : "";
  }

  // Auto refresh every 30 seconds
  setTimeout(function () {
    location.reload();
  }, 30000);

  // Intercept kitchen action forms
  document.querySelectorAll("form").forEach(function (f) {
    var actEl = f.querySelector('input[name="action"]');
    if (!actEl) return;

    var action = actEl.value;

    f.addEventListener("submit", function (e) {
      e.preventDefault();

      // mark single item ready
      if (action === "mark_item_ready") {
        var itemIdEl = f.querySelector('input[name="item_id"]');
        if (!itemIdEl) return;

        postJSON(
          "../controller/OrderAjaxController.php",
          "orderItemUpdateStatus",
          {
            csrf_token: csrf(),
            id: itemIdEl.value,
            status: "ready",
          },
          function (res) {
            alert(res.message || "Updated");
            if (res.success) location.reload();
          },
        );
        return;
      }

      // mark all items ready
      if (action === "mark_all_ready") {
        var orderIdEl = f.querySelector('input[name="order_id"]');
        if (!orderIdEl) return;

        postJSON(
          "../controller/OrderAjaxController.php",
          "orderMarkAllReady",
          {
            csrf_token: csrf(),
            id: orderIdEl.value,
          },
          function (res) {
            alert(res.message || "Updated");
            if (res.success) location.reload();
          },
        );
        return;
      }

      // mark served
      if (action === "mark_served") {
        var orderIdEl2 = f.querySelector('input[name="order_id"]');
        if (!orderIdEl2) return;

        postJSON(
          "../controller/OrderAjaxController.php",
          "orderUpdateStatus",
          {
            csrf_token: csrf(),
            id: orderIdEl2.value,
            status: "served",
          },
          function (res) {
            alert(res.message || "Updated");
            if (res.success) location.reload();
          },
        );
        return;
      }
    });
  });
})();
