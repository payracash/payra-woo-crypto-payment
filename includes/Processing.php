<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

if (!defined('ABSPATH')) exit;

class Processing extends Constants
{
    public function register()
    {
        add_action('wp_ajax_payra_cash_create_signature', [$this, 'payracacr_action_create_signature']);
        add_action('wp_ajax_nopriv_payra_cash_create_signature', [$this, 'payracacr_action_create_signature']);

        add_action('wp_ajax_payra_cash_get_network_data', [$this, 'payracacr_action_get_network_data']);
        add_action('wp_ajax_nopriv_payra_cash_get_network_data', [$this, 'payracacr_action_get_network_data']);

        add_action('wp_ajax_payra_cash_get_order_status', [$this, 'payracacr_action_get_order_status']);
        add_action('wp_ajax_nopriv_payra_cash_get_order_status', [$this, 'payracacr_action_get_order_status']);

        add_action('wp_ajax_payra_cash_update_transaction', [$this, 'payracacr_action_update_transaction']);
        add_action('wp_ajax_nopriv_payra_cash_update_transaction', [$this, 'payracacr_action_update_transaction']);

        add_action('wp_ajax_payra_cash_check_order_status', [$this, 'payracacr_action_check_order_status']);
        add_action('wp_ajax_nopriv_payra_cash_check_order_status', [$this, 'payracacr_action_check_order_status']);
    }

    function payracacr_action_check_order_status()
    {
        global $wpdb;

        check_ajax_referer('payra_cash_nonce');
        $order_id = intval($_POST['order_id'] ?? 0);

        if (!$order_id) {
            wp_send_json_error([
                'message' => __('Missing params', 'payra-cash-crypto-payment'),
                'status'  => 'error',
            ]);
        }

        $existing_tx = $wpdb->get_row(
            $wpdb->prepare("
                SELECT
                    t.*,
                    s.name AS status_name
                FROM {$this->transactions_table} t
                LEFT JOIN {$this->transaction_statuses_table} s
                    ON t.status_id = s.id
                WHERE t.order_id = %d
                LIMIT 1", $order_id
            ),
            ARRAY_A
        );

        wp_send_json_success(['order_status' => $existing_tx['status_name'] ?? '']);
    }

    public function payracacr_action_create_signature()
    {
        global $wpdb;

        check_ajax_referer('payra_cash_nonce');

        $order_id             = intval(wp_unslash($_POST['order_id'] ?? 0));
        $network_id           = intval(wp_unslash($_POST['network_id'] ?? 0));
        $token_id             = intval(wp_unslash($_POST['token_id'] ?? 0));
        $payer_wallet_address = sanitize_text_field(wp_unslash($_POST['payer_wallet_address'] ?? ''));

        if (!$payer_wallet_address) {
            wp_send_json_error(['message' => __('Wallet address required', 'payra-cash-crypto-payment')]);
        }

        if (!$order_id || !$network_id) {
            wp_send_json_error(['message' => __('Missing params', 'payra-cash-crypto-payment')]);
        }

        // 1. Get network
        $network = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->networks_table} WHERE id = %d", $network_id),
            ARRAY_A
        );

        if (!$network) {
            wp_send_json_error(['message' => __('Network not found', 'payra-cash-crypto-payment')]);
        }

        $network_name = $network['name'];

        // 2. Get token address
        $token = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->stablecoins_table} WHERE id = %d", $token_id),
            ARRAY_A
        );

        if (!$token) {
            wp_send_json_error(['message' => __('Token not found', 'payra-cash-crypto-payment')]);
        }

        $token_contract_address = $token['contract_address'];

        // 3. Get settings
        $settings_obj = new Settings;
        $settings = $settings_obj->get_data();

        // 4. Search key
        $signature_key = $settings[$network_name]['signature_key'] ?? null;
        if (!$signature_key) {
            wp_send_json_error(['message' => __('Signature key not set for network', 'payra-cash-crypto-payment')]);
        }

        $merchant_id = $settings[$network_name]['merchant_id'] ?? null;
        if (!$merchant_id) {
            wp_send_json_error(['message' => __('Merchant Id not set for network', 'payra-cash-crypto-payment')]);
        }

        // 5. Crate order id with prefix
        $order_prefix = $settings['order_prefix'] ?? null;
        if (!$order_prefix) {
            wp_send_json_error(['message' => __('Order prefix not set', 'payra-cash-crypto-payment')]);
        }

        $order_prefix_id = $order_prefix . '-' . $order_id;

        // 6. Get amount for payra
        $price = Helper::get_order_amount_usd($order_id);
        $amount_in_wei = Helper::to_token_units($price, $token['decimals']);

        // 7. Generate signature
        $timestamp = time();
        $sigGen = new SignatureGenerator;
        $signature = $sigGen->generate_signature(
            $merchant_id,
            $signature_key,
            $token_contract_address,
            $order_prefix_id,
            $amount_in_wei,
            $timestamp,
            $payer_wallet_address
        );

        // 8. Create or update transactions
        $wpdb->query(
            $wpdb->prepare("
                INSERT INTO {$this->transactions_table}
                    (order_id, order_prefix_id, network_id, currency_symbol, sender, price, amount_in_wei, created_at, status_id)
                VALUES (%d, %s, %d, %s, %s, %f, %s, %s, 1)
                ON DUPLICATE KEY UPDATE
                    sender = VALUES(sender),
                    price = VALUES(price),
                    amount_in_wei = VALUES(amount_in_wei),
                    created_at = VALUES(created_at)",
                $order_id,
                $order_prefix_id,
                $network_id,
                $token['display_symbol'],
                $payer_wallet_address,
                $price,
                $amount_in_wei,
                current_time('mysql')
            )
        );

        // 9. Return signature
        wp_send_json_success([
            'merchant_id'   => $merchant_id,
            'order_id'      => $order_prefix_id,
            'amount_in_wei' => $amount_in_wei,
            'timestamp'     => $timestamp,
            'signature'     => $signature,
        ]);

        wp_die();
    }

    function payracacr_action_get_network_data()
    {
        global $wpdb;

        check_ajax_referer('payra_cash_nonce');
        $network_id = isset($_POST['network_id']) ? intval(wp_unslash($_POST['network_id'])) : 0;

        if (!$network_id) {
            wp_send_json_error(['message' => __('Network ID not provided', 'payra-cash-crypto-payment')]);
        }

        $network = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->networks_table} WHERE id = %d", $network_id),
            ARRAY_A
        );

        if (!$network) {
            wp_send_json_error(['message' => __('Network not found', 'payra-cash-crypto-payment')]);
        }

        wp_send_json_success($network);
    }

    function payracacr_action_get_order_status()
    {
        global $wpdb;

        check_ajax_referer('payra_cash_nonce');
        $order_id = intval(wp_unslash($_POST['order_id'] ?? 0));

        if (!$order_id) {
            wp_send_json_error([
                'message' => __('Missing params', 'payra-cash-crypto-payment'),
                'status'  => 'error',
            ]);
        }

        // 1. Get transaction details
        $existing_tx = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->transactions_table} WHERE order_id = %d AND status_id = 1 LIMIT 1", $order_id),
            ARRAY_A
        );

        if (!$existing_tx) {
            wp_send_json_error([
                'message' => __('Transaction not exists', 'payra-cash-crypto-payment'),
                'status'  => 'error',
            ]);
        }

        // 2. Get network
        $network = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM  {$this->networks_table} WHERE id = %d", $existing_tx['network_id']),
            ARRAY_A
        );

        if (!$network) {
            wp_send_json_error([
                'message' => __('Network not found', 'payra-cash-crypto-payment'),
                'status'  => 'error',
            ]);
        }

        $network_name = $network['name'];

        // 3. Get settings
        $settings_obj = new Settings;
        $settings = $settings_obj->get_data();
        $merchant_id = $settings[$network_name]['merchant_id'] ?? null;

        if (!$merchant_id) {
            wp_send_json_error([
                'message' => __('Merchant Id not set for network', 'payra-cash-crypto-payment'),
                'status'  => 'error',
            ]);
        }

        // RPC URLS
        $rpc_url = Helper::get_random_rpc_url($network_name);

        // check order status
        $verifier = new VerifyPayment;
        $order_status = $verifier->get_order_status(
            $existing_tx['order_prefix_id'],
            $merchant_id,
            $rpc_url,
            $network['payra_contract_address']
        );

        if ($order_status["paid"] === true) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->payment_complete();

                Helper::update_transaction('status_id', 2, $existing_tx['id']);
                Helper::update_transaction('fee_in_wei', $order_status["fee"], $existing_tx['id']);

                wp_send_json_success([
                    'message' => __('Payment confirmed', 'payra-cash-crypto-payment'),
                    'status'  => 'paid',
                    'redirect_url' => $order->get_checkout_order_received_url(),
                    'order_status' => $order_status,
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('No order found', 'payra-cash-crypto-payment'),
                    'status'  => 'error',
                ]);
            }
        } elseif ($order_status["paid"] === false) {
            // Check if transaction expired
            if ((time() - strtotime($existing_tx['created_at'])) > (((new Settings)->get_data()['tx_expiration_time'] ?? 30) * 60)) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $order->update_status('cancelled', __('Payment expired or rejected.', 'payra-cash-crypto-payment'));
                }

                Helper::update_transaction('status_id', 3, $existing_tx['id']);
                wp_send_json_error([
                    'message' => __('Transaction expired and marked as rejected', 'payra-cash-crypto-payment'),
                    'status'  => 'expired',
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Payment not confirmed', 'payra-cash-crypto-payment'),
                    'status'  => 'pending',
                ]);
            }
        } else { // null
            wp_send_json_error([
                'message' => __('Error contacting blockchain endpoint', 'payra-cash-crypto-payment'),
                'status'  => 'error',
            ]);
        }
    }

    function payracacr_action_update_transaction()
    {
        global $wpdb;

        check_ajax_referer('payra_cash_nonce');
        $order_id = intval(wp_unslash($_POST['order_id'] ?? 0));
        $tx_hash  = sanitize_text_field(wp_unslash($_POST['tx_hash'] ?? ''));

        $existing_tx = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->transactions_table} WHERE order_id = %d LIMIT 1", $order_id),
            ARRAY_A
        );

        if (!$existing_tx) {
            wp_send_json_error(['message' => __('Transaction not exists', 'payra-cash-crypto-payment')]);
        }

        Helper::update_transaction('tx_hash', $tx_hash, $existing_tx['id']);

        wp_send_json_success();
    }

}
