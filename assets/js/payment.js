jQuery(function($) {
  toastr.options = {
    "positionClass": "toast-top-center",
    "timeOut": "3000",
    "closeButton": true,
    "progressBar": true,
    "showMethod": 'slideDown',
    "hideMethod": 'slideUp',
  };
});

function statusMessage(message, type = "info") {
  const statusBox = document.getElementById("payra-status");
  const statusText = statusBox.querySelector(".status-message");
  statusBox.classList.remove("status-info", "status-success", "status-error", "status-warning");
  statusBox.classList.add(`status-${type}`);
  statusText.textContent = message;
}

async function getOrderStatus(orderId) {
  try {
    const resp = await jQuery.ajax({
      url: PayraCaCrLocalize.ajaxUrl,
      type: 'POST',
      data: {
        action: 'payra_cash_get_order_status',
        order_id: orderId,
        _ajax_nonce: PayraCaCrLocalize.nonce,
      },
      dataType: 'json'
    });
    return resp;
  } catch (e) {
    console.error("AJAX error:", e);
  }
}
