var originalSubtotal = window.ORIGINAL_SUBTOTAL || 0;

function selectPaymentMethod(method) {
  var methods = document.querySelectorAll(".payment-method");
  for (var i = 0; i < methods.length; i++) {
    methods[i].classList.remove("selected");
    if (methods[i].getAttribute("data-method") === method) {
      methods[i].classList.add("selected");
    }
  }
  document.getElementById("paymentMethod").value = method;
}

function calculateTotal() {
  var discount =
    parseFloat(document.getElementById("discountInput").value) || 0;
  var discountAmount = (originalSubtotal * discount) / 100;
  var afterDiscount = originalSubtotal - discountAmount;
  var tax = afterDiscount * 0.05;
  var total = afterDiscount + tax;

  if (discount > 0) {
    document.getElementById("discountRow").style.display = "flex";
    document.getElementById("discountPercent").textContent = discount;
    document.getElementById("discountAmount").textContent =
      discountAmount.toFixed(2);
  } else {
    document.getElementById("discountRow").style.display = "none";
  }

  document.getElementById("taxAmount").textContent = tax.toFixed(2);
  document.getElementById("grandTotal").textContent = total.toFixed(2);
  document.getElementById("amountToPay").value = "$" + total.toFixed(2);
  document.getElementById("paidAmount").value = total.toFixed(2);

  calculateChange();
}

function calculateChange() {
  var total = parseFloat(document.getElementById("grandTotal").textContent);
  var paid = parseFloat(document.getElementById("paidAmount").value) || 0;
  var change = paid - total;

  if (change < 0) {
    document.getElementById("changeAmount").value = "Insufficient";
    document.getElementById("changeAmount").style.backgroundColor = "#ffebee";
    document.getElementById("changeAmount").style.color = "#c62828";
  } else {
    document.getElementById("changeAmount").value = "$" + change.toFixed(2);
    document.getElementById("changeAmount").style.backgroundColor = "#e8f5e9";
    document.getElementById("changeAmount").style.color = "#2e7d32";
  }
}

// Validate form
if (document.getElementById("paymentForm")) {
  document.getElementById("paymentForm").onsubmit = function (e) {
    var total = parseFloat(document.getElementById("grandTotal").textContent);
    var paid = parseFloat(document.getElementById("paidAmount").value) || 0;

    if (paid < total) {
      alert("Paid amount is less than total!");
      e.preventDefault();
      return false;
    }

    return confirm("Confirm payment of $" + total.toFixed(2) + "?");
  };
}
