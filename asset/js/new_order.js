var selectedTable = null;
var selectedOrderType = "dine-in";

function selectOrderType(type) {
  selectedOrderType = type;
  document.getElementById("selectedOrderType").value = type;

  // Update UI
  var btns = document.querySelectorAll(".order-type-btn");
  for (var i = 0; i < btns.length; i++) {
    btns[i].classList.remove("selected");
    if (btns[i].getAttribute("data-type") === type) {
      btns[i].classList.add("selected");
    }
  }

  // Show/hide table section
  var tableSection = document.getElementById("tableSection");
  var deliveryField = document.getElementById("deliveryField");

  if (type === "dine-in") {
    tableSection.style.display = "block";
    deliveryField.classList.remove("show");
  } else if (type === "delivery") {
    tableSection.style.display = "none";
    deliveryField.classList.add("show");
    selectedTable = null;
    document.getElementById("selectedTable").value = "";
  } else {
    tableSection.style.display = "none";
    deliveryField.classList.remove("show");
    selectedTable = null;
    document.getElementById("selectedTable").value = "";
  }
}

function selectTable(tableId) {
  selectedTable = tableId;
  document.getElementById("selectedTable").value = tableId;

  // Update UI
  var btns = document.querySelectorAll(".table-btn");
  for (var i = 0; i < btns.length; i++) {
    if (
      btns[i].classList.contains("available") ||
      btns[i].classList.contains("selected")
    ) {
      btns[i].classList.remove("selected");
      if (
        !btns[i].classList.contains("occupied") &&
        !btns[i].classList.contains("reserved")
      ) {
        btns[i].classList.add("available");
      }
    }
    if (btns[i].getAttribute("data-id") == tableId) {
      btns[i].classList.remove("available");
      btns[i].classList.add("selected");
    }
  }
}

document.getElementById("orderForm").onsubmit = function (e) {
  if (selectedOrderType === "dine-in" && !selectedTable) {
    alert("Please select a table");
    e.preventDefault();
    return false;
  }

  if (selectedOrderType === "delivery") {
    var address = document
      .querySelector('textarea[name="delivery_address"]')
      .value.trim();
    if (address === "") {
      alert("Please enter delivery address");
      e.preventDefault();
      return false;
    }
  }

  return true;
};

// AJAX submit for creating order is intentionally disabled.
// The form submits normally (POST) to keep the flow simple and reliable.
