<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

if (!defined('ABSPATH')) exit;

class Cron extends Constants
{
    public function register()
    {
        add_action('payracacr_custom_cron_transactions', [$this, 'payracacr_action_check_pending_transactions']);
        add_action('payracacr_custom_cron_news', [$this, 'payracacr_action_check_news']);
        add_action('admin_post_payracacr_save_cron_settings', [$this, 'payracacr_action_save_cron_settings']);
    }

    public function payracacr_action_save_cron_settings()
    {
        global $wpdb;

        if (!isset($_POST['cron_settings_nonce']) || !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['cron_settings_nonce'])),
            'payracacr_save_cron_settings'
        )) {
            wp_die('Security check failed');
        }

        // Cron transaction batch size
        $cron_transaction_batch_size = isset($_POST['cron_transaction_batch_size']) && $_POST['cron_transaction_batch_size'] !== ''
            ? max(1, intval($_POST['cron_transaction_batch_size']))
            : 10;

        // Cron transaction interval
        $cron_transaction_sleep_seconds = isset($_POST['cron_transaction_sleep_seconds']) && $_POST['cron_transaction_sleep_seconds'] !== ''
            ? max(1, intval($_POST['cron_transaction_sleep_seconds']))
            : 3;

        // Update settings in the database
        $wpdb->update(
            Settings::$db_table_settings, [
                'cron_transaction_batch_size' => $cron_transaction_batch_size,
                'cron_transaction_sleep_seconds' => $cron_transaction_sleep_seconds,
            ], [
                'symbol' => self::DICT_SETTINGS_SYMBOL
            ]
        );

        // Redirct
        wp_safe_redirect(
            add_query_arg(['page' => self::SETTINGS_PAGE_SLUG, 'tab' => 'cron', 'updated' => 'true'], admin_url('admin.php'))
        );

        return;
    }

    public function payracacr_action_check_news()
    {
        (new News)->fetch_news();
    }

    public function payracacr_action_check_pending_transactions()
    {
        global $wpdb;

        $settings = (new Settings)->get_data();
        $cron_transaction_batch_size = $settings['cron_transaction_batch_size'] ?? 10;
        $cron_transaction_sleep_seconds = $settings['cron_transaction_sleep_seconds'] ?? 3;

        $pending_transactions = $wpdb->get_results(
            $wpdb->prepare("
                SELECT *
                FROM {$this->transactions_table}
                WHERE status_id = %d
                  AND (last_checked_at IS NULL OR last_checked_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE))
                ORDER BY created_at ASC
                LIMIT %d
            ",
            self::PENDING_STATUS,
            $cron_transaction_batch_size),
            ARRAY_A
        );

        if (empty($pending_transactions)) return;

        foreach ($pending_transactions as $tx) {
            $this->check_single_transaction($tx);
            sleep($cron_transaction_sleep_seconds);
        }
    }

    public function check_single_transaction($transaction)
    {
        global $wpdb;

        $order_id = intval($transaction['order_id'] ?? 0);

        if (!$order_id) {
            return;
        }

        $network = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->networks_table} WHERE id = %d", $transaction['network_id']),
            ARRAY_A
        );

        if (!$network) {
            return;
        }

        $network_name = $network['name'];

        // Get Merchant ID
        $settings_obj = new Settings;
        $settings = $settings_obj->get_data();
        $merchant_id = $settings[$network_name]['merchant_id'] ?? null;

        if (!$merchant_id) {
            return;
        }

        // RPC URLS
        $rpc_url = Helper::get_random_rpc_url($network_name);

        // Check Blockchain status
        $verifier = new VerifyPayment;
        $order_status = $verifier->get_order_status(
            $transaction['order_prefix_id'],
            $merchant_id,
            $rpc_url,
            $network['payra_contract_address']
        );

        if ($order_status["paid"] === true) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->payment_complete();
                Helper::update_transaction('status_id', 2, $transaction['id']); // 2 = paid
                Helper::update_transaction('fee_in_wei', $order_status["fee"], $existing_tx['id']);
            }

            Helper::update_transaction('last_checked_at', current_time('mysql'), $transaction['id']);
        } elseif ($order_status["paid"] === false) {
            // Is transaction send
            $expiration_minutes = (int) ($settings['tx_expiration_time'] ?? 30);

            if ((time() - strtotime($transaction['created_at'])) > ($expiration_minutes * 60)) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $order->update_status('cancelled', __('Payment expired or rejected.', 'payra-cash-crypto-payment'));
                }

                Helper::update_transaction('status_id', 3, $transaction['id']); // 3 = expired
                Helper::update_transaction('last_checked_at', current_time('mysql'), $transaction['id']);
            } else {
                // Still pending, update time to check
                Helper::update_transaction('last_checked_at', current_time('mysql'), $transaction['id']);
            }
        }
    }

}
