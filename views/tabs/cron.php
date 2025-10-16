<?php
    if (!defined('ABSPATH')) exit;
    $settings = (new Xxxwraithxxx\PayraCashCryptoPayment\Settings)->get_data();
?>
<div class="wrap">
    <?php
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only displaying a status flag, no data is modified
        if (!empty($_GET['updated'])):
    ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Settings saved.', 'payra-cash-crypto-payment'); ?></p></div>
    <?php endif; ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('payracacr_save_cron_settings', 'cron_settings_nonce'); ?>
        <input type="hidden" name="action" value="payracacr_save_cron_settings">
        <div class="payra-card">
            <h2><?php esc_html_e('Cron Jobs – Quick Overview', 'payra-cash-crypto-payment'); ?></h2>
            <p class="description payra-cash-description-half">
                <?php echo wp_kses_post(__('Our system uses <b>WordPress cron (WP-Cron)</b> to automatically process cryptocurrency transactions on a regular schedule. Cron jobs are tasks that are scheduled to run at specific intervals, allowing us to automate repetitive operations without manual intervention. For the Payra Cash Crypto Payment On-Chain gateway:', 'payra-cash-crypto-payment')); ?>
            </p>
            <ul class="payra-cash-description-half">
                <li><?php esc_html_e('The cron job runs every 5 minutes, checking the status of transactions.', 'payra-cash-crypto-payment'); ?></li>
                <li><?php esc_html_e('During each run, the system fetches a batch of transactions (currently 10 at a time) and verifies their status on the blockchain.', 'payra-cash-crypto-payment'); ?></li>
                <li><?php esc_html_e('Each transaction check is spaced out by a small internal interval (currently 3 seconds) to avoid overloading the blockchain node (e.g., Quick Node) or the database.', 'payra-cash-crypto-payment'); ?></li>
                <li><?php esc_html_e('This ensures that transaction statuses are kept up-to-date, and users see accurate information about their payments.', 'payra-cash-crypto-payment'); ?></li>
            </ul>
        </div>
        <div class="payra-card">
            <h2><?php esc_html_e('Cron Performance Settings', 'payra-cash-crypto-payment'); ?></h2>
            <p class="description payra-cash-description-half">
                <?php echo esc_html__('Adjust the batch size (e.g., increase from 10 to 20 or 30 transactions per run) to process more transactions per cycle. Adjust the internal interval between transaction checks (e.g., 2–10 seconds) to balance load and performance. Increasing batch size or reducing the interval will make the cron job heavier. If you have a high transaction volume, consider testing these values carefully to avoid database or blockchain node overload.', 'payra-cash-crypto-payment'); ?>
            </p>
            <table class="form-table" id="pagination-settings">
                <tr>
                    <th scope="row">
                        <label for="cron_transaction_batch_size"><?php esc_html_e('Batch size', 'payra-cash-crypto-payment'); ?></label>
                    </th>
                    <td>
                        <input type="number"
                               name="cron_transaction_batch_size"
                               id="cron_transaction_batch_size"
                               value="<?php echo esc_attr($settings['cron_transaction_batch_size'] ?? 10); ?>"
                               min="1"
                               class="small-text" />
                        <p class="description">
                            <small><?php esc_html_e('Enter a number greater than 0. Default is 10.', 'payra-cash-crypto-payment'); ?></small>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="cron_transaction_sleep_seconds"><?php esc_html_e('Set Interval', 'payra-cash-crypto-payment'); ?></label>
                    </th>
                    <td>
                        <input type="number"
                               name="cron_transaction_sleep_seconds"
                               id="cron_transaction_sleep_seconds"
                               value="<?php echo esc_attr($settings['cron_transaction_sleep_seconds'] ?? 3); ?>"
                               min="1"
                               class="small-text" />
                        <p class="description">
                            <small><?php esc_html_e('Enter a number greater than 0. Default is 3.', 'payra-cash-crypto-payment'); ?></small>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <?php submit_button(__('Save Settings', 'payra-cash-crypto-payment')); ?>
    </form>
    <div class="tablenav bottom">
        <?php include(PAYRACACR_CASH_PLUGIN_PATH . 'views/footer.php'); ?>
        <br class="clear">
    </div>
</div>
