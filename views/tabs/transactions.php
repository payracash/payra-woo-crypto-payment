<?php
    if (!defined('ABSPATH')) exit;

    $per_page = (new Xxxwraithxxx\PayraCashCryptoPayment\Settings)->get_data()['result_per_page_on_transactions'];
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination param
    $paged = isset($_GET['paged']) ? max(1, intval(wp_unslash( $_GET['paged']))) : 1;

    $transaction_obj = new Xxxwraithxxx\PayraCashCryptoPayment\Transaction;
    $search_results = get_transient(self::SEARCH_RESULTS);

    if ($search_results !== false) {
        $transactions = (array) $search_results;
        delete_transient(self::SEARCH_RESULTS);
        $total_items = count((array) $transactions);
        $total_pages = 1;
    } else {
        $transactions = $transaction_obj->get_transactions($per_page, $paged);
        $total_items = $transaction_obj->get_total_count();
        $total_pages = ceil($total_items / $per_page);
    }

    // === JS Transactions ===
    $js_transactions_file = PAYRACACR_CASH_PLUGIN_PATH . 'assets/js/admin-transactions.js';
    wp_enqueue_script(
        'payracacr-admin-transactions',
        PAYRACACR_CASH_PLUGIN_URL . 'assets/js/admin-transactions.js',
        ['jquery'],
        file_exists($js_transactions_file) ? filemtime($js_transactions_file) : false,
        true
    );

    wp_localize_script('payracacr-admin-transactions', 'PayraCaCrTransactions', [
        'msgPaid'        => esc_html__('PAID', 'payra-cash-crypto-payment'),
        'msgExpired'     => esc_html__('EXPIRED', 'payra-cash-crypto-payment'),
        'msgCheck'       => esc_html__('Check', 'payra-cash-crypto-payment'),
        'msgPaidInfo'    => esc_html__('Order has been paid', 'payra-cash-crypto-payment'),
        'msgExpiredInfo' => esc_html__('Order has expired', 'payra-cash-crypto-payment'),
        'msgErrorBC'     => esc_html__('Could not contact blockchain endpoint', 'payra-cash-crypto-payment'),
        'msgPending'     => esc_html__('Still pending...', 'payra-cash-crypto-payment'),
        'msgErrorCheck'  => esc_html__('Error checking payment', 'payra-cash-crypto-payment'),
    ]);
?>

<div class="wrap">
    <div class="payra-card">
        <h2><?php esc_html_e('Transactions', 'payra-cash-crypto-payment'); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <td colspan="9">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="payra_search_transactions">
                            <?php wp_nonce_field('payra_search_transactions_nonce'); ?>
                            <input
                                name="transactions_search"
                                type="text"
                                placeholder="<?php esc_attr_e('Order ID, Tx, Amount, etc...', 'payra-cash-crypto-payment'); ?>" class="regular-text">
                            <input
                                type="submit"
                                name="submit"
                                id="submit_search"
                                class="button button-primary"
                                value="Search">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=' . esc_attr(self::SETTINGS_PAGE_SLUG) . '&tab=transactions')); ?>" class="button button-secondary"><?php esc_html_e('Transactions List', 'payra-cash-crypto-payment'); ?></a>
                        </form>
                    </td>
                </tr>
                <tr>
                    <th scope="col"><b><?php esc_html_e('Order Id', 'payra-cash-crypto-payment'); ?></b></th>
                    <th scope="col"><b><?php esc_html_e('Status', 'payra-cash-crypto-payment'); ?></b></th>
                    <th scope="col"><b><?php esc_html_e('Date / Time', 'payra-cash-crypto-payment'); ?></b></th>
                    <th scope="col"><b><?php esc_html_e('Price', 'payra-cash-crypto-payment'); ?></b></th>
                    <th scope="col"><b><?php esc_html_e('Fee', 'payra-cash-crypto-payment'); ?></b></th>
                    <th scope="col"><b><?php esc_html_e('Payer', 'payra-cash-crypto-payment'); ?></b></th>
                    <th scope="col"><b><?php esc_html_e('Network', 'payra-cash-crypto-payment'); ?></b></th>
                    <th scope="col"><b><?php esc_html_e('TX Hash', 'payra-cash-crypto-payment'); ?></b></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction):
                    $tx_hash = $transaction['tx_hash'] ?? '';
                ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo esc_url(admin_url('post.php?post=' . intval($transaction['order_id']) . '&action=edit')); ?>"><?php echo esc_html($transaction['order_prefix_id']); ?></a></strong>
                        </td>
                        <td>
                            <?php
                                $status = $transaction['status_name'];
                                $class = '';

                                switch (strtolower($status)) {
                                    case 'paid':
                                        $class = 'payra-pill--paid';
                                        break;
                                    case 'pending':
                                        $class = 'payra-pill--pending';
                                        break;
                                    case 'expired':
                                        $class = 'payra-pill--expired';
                                        break;
                                    case 'rejected':
                                        $class = 'payra-pill--rejected';
                                        break;
                                    case 'error':
                                        $class = 'payra-pill--error';
                                        break;
                                    default:
                                        $class = 'payra-pill--default';
                                }
                            ?>
                            <span class="payra-pill <?php echo esc_attr($class); ?> to-search" data-status="<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></span>
                            <?php if (strtolower($status) === 'pending'): ?>
                                <button
                                    class="payra-check-btn"
                                    data-transaction-order-id="<?php echo esc_attr($transaction['order_id']); ?>">
                                    <?php esc_html_e('Check', 'payra-cash-crypto-payment'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($transaction['created_at']); ?></td>
                        <td>
                            <?php
                                $symbol = $transaction['currency_symbol'];
                                $price  = $transaction['price'];
                                $fee_in_wei  = number_format(($transaction['fee_in_wei'] / 1_000_000), 4);
                                $class  = '';

                                switch (strtolower($symbol)) {
                                    case 'usdt':
                                        $class = 'currency--usdt';
                                        break;
                                    case 'usdc':
                                        $class = 'currency--usdc';
                                        break;
                                    default:
                                        $class = 'currency--default';
                                }
                            ?>
                            <span class="currency <?php echo esc_attr($class); ?>"><?php echo esc_html($symbol); ?></span> $<?php echo esc_html($price); ?>
                        </td>
                        <td class="fee-cell"><span class="currency <?php echo esc_attr($class); ?>">$<?php echo esc_html($fee_in_wei); ?></td>
                        <td><?php echo esc_html(Xxxwraithxxx\PayraCashCryptoPayment\Helper::shorten_hash($transaction['sender'])); ?></td>
                        <td><?php echo esc_html($transaction['network_label']); ?></td>
                        <td>
                            <?php if (!empty($transaction['tx_hash'])): ?>
                                <a href="<?php echo esc_url($transaction['block_explorer_url'] . '/tx/' . $transaction['tx_hash']); ?>" target="_blank"><?php echo esc_html(Xxxwraithxxx\PayraCashCryptoPayment\Helper::shorten_hash($transaction['tx_hash'])); ?></a>
                            <?php else: ?>
                                <span><?php esc_html_e('...', 'payra-cash-crypto-payment'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- PAGINATION -->
        <div class="tablenav bottom">
            <?php include(PAYRACACR_CASH_PLUGIN_PATH . 'views/partials/pagination.php'); ?>
        </div>
    </div>
    <div class="tablenav bottom">
        <?php include(PAYRACACR_CASH_PLUGIN_PATH . 'views/footer.php'); ?>
        <br class="clear">
    </div>
</div>
