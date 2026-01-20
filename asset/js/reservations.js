// Reservations page: AJAX CRUD

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
  var form = document.getElementById("reservationForm");
  if (!form) return;

  // Previously AJAX CRUD was disabled.
  // The reservations page now uses module-specific AJAX calls to interact with the server.

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

    var tableId = val("table_id");
    var customerName = val("customer_name");
    var customerPhone = val("customer_phone");
    var guestCount = val("guest_count");
    var date = val("reservation_date");
    var time = val("reservation_time");
    var notes = val("notes");
    var status = val("status") || "pending";

    if (!tableId) {
      alert("Please select table");
      return;
    }
    if (customerName === "") {
      alert("Customer name required");
      return;
    }
    if (customerPhone === "") {
      alert("Customer phone required");
      return;
    }
    if (!guestCount || isNaN(guestCount) || Number(guestCount) <= 0) {
      alert("Valid guest count required");
      return;
    }
    if (date === "" || time === "") {
      alert("Date and time required");
      return;
    }

    var payload = {
      csrf_token: csrf(),
      id: idEl ? idEl.value : "",
      table_id: tableId,
      customer_name: customerName,
      customer_phone: customerPhone,
      guest_count: guestCount,
      reservation_date: date,
      reservation_time: time,
      notes: notes,
      status: status,
    };

    var action = actionEl ? actionEl.value : "create";
    var key = action === "update" ? "reservationUpdate" : "reservationCreate";

    postJSON("../controller/ReservationAjaxController.php", key, payload, function (res) {
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
      if (!confirm("Delete this reservation?")) return;

      postJSON(
        "../controller/ReservationAjaxController.php",
        "reservationDelete",
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
