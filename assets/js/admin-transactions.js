jQuery(function($) {
    const msgPaid        = PayraCaCrTransactions.msgPaid;
    const msgExpired     = PayraCaCrTransactions.msgExpired;
    const msgCheck       = PayraCaCrTransactions.msgCheck;
    const msgPaidInfo    = PayraCaCrTransactions.msgPaidInfo;
    const msgExpiredInfo = PayraCaCrTransactions.msgExpiredInfo;
    const msgErrorBC     = PayraCaCrTransactions.msgErrorBC;
    const msgPending     = PayraCaCrTransactions.msgPending;
    const msgErrorCheck  = PayraCaCrTransactions.msgErrorCheck;

    // Click on status, search by filter
    $('.payra-pill').on('click', function() {
        const status = $(this).data('status');
        const form = $(this).closest('table').find('form')[0];
        const input = $(form).find('input[name="transactions_search"]');
        input.val(status);
        HTMLFormElement.prototype.submit.call(form);
    });

    // Check for pending
    $('.payra-check-btn').on('click', async function() {
        const btn = $(this);
        const orderId = btn.data('transaction-order-id');
        btn.addClass('blink').prop('disabled', true);

        try {
            const resp = await getOrderStatus(orderId);
            if (resp.success && resp.data.status === 'paid') {
                const pill = btn.closest('td').find('.payra-pill');
                pill.removeClass('payra-pill--pending')
                    .addClass('payra-pill--paid')
                    .text(msgPaid);

                // Show fee on table
                const feeFormatted = (resp.data.order_status.fee / 1e6).toFixed(4);
                const row = btn.closest('tr');
                const feeCell = row.find('.fee-cell .currency');
                feeCell.text(`$${feeFormatted}`);

                // remove
                btn.remove();
                toastr.success(msgPaidInfo);
            } else if (resp.data.status === 'expired') {
                const pill = btn.closest('td').find('.payra-pill');
                pill.removeClass('payra-pill--pending')
                    .addClass('payra-pill--expired')
                    .text(msgExpired);
                btn.remove();
                toastr.warning(msgExpiredInfo);
            } else if (resp.data.status === 'error') {
                btn.removeClass('blink').prop('disabled', false).text(msgCheck);
                toastr.error(msgErrorBC);
            } else {
                toastr.info(msgPending);
                btn.removeClass('blink').prop('disabled', false).text(msgCheck);
            }
        } catch (err) {
            console.error(err);
            toastr.error(msgErrorCheck);
            btn.removeClass('blink').prop('disabled', false).text(msgCheck);
        }
    });
});
