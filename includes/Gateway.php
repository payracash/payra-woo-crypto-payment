<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

if (!defined('ABSPATH')) exit;

class Gateway extends \WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'payra_cash_crypto_payment';
        $this->icon = plugins_url('assets/img/payracash-logo.png', PAYRACACR_CASH_PLUGIN_FILE);
        $this->has_fields = false;
        $this->method_title = __('Payra Cash Crypto Payment On-Chain', 'payra-cash-crypto-payment');
        $this->method_description = __('Accept crypto payments On-Chain with Payra Cash.', 'payra-cash-crypto-payment');
        $this->supports = ['products', 'default', 'virtual'];

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title = $this->get_option('title', 'Payra Cash Crypto Payment');
        $this->description = $this->get_option('description', __('Pay with cryptocurrency On-Chain via Payra Cash.', 'payra-cash-crypto-payment'));
        $this->enabled = $this->get_option('enabled', 'yes');

        // Save settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
    }

    public function get_icon()
    {
        $url = plugins_url('assets/img/payracash-logo.png', PAYRACACR_CASH_PLUGIN_FILE);
        $html = '<img src="' . esc_url($url) . '" alt="Payra Cash" width="50" height="50" class="payra-icon" />';

        return apply_filters('woocommerce_gateway_icon', $html, $this->id);
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title'       => __('Enable/Disable', 'payra-cash-crypto-payment'),
                'type'        => 'checkbox',
                'label'       => __('Enable Payra Crypto Payments', 'payra-cash-crypto-payment'),
                'default'     => 'yes',
            ],
            'title' => [
                'title'       => __('Title', 'payra-cash-crypto-payment'),
                'type'        => 'text',
                'default'     => __('Payra Cash', 'payra-cash-crypto-payment'),
            ],
            'description'     => [
                'title'       => __('Description', 'payra-cash-crypto-payment'),
                'type'        => 'textarea',
                'default'     => __('Pay with cryptocurrency On-Chain via Payra Cash.', 'payra-cash-crypto-payment'),
            ],
            'separator' => [
                'title'       => '',
                'type'        => 'title',
                'description' => '<hr>',
            ],
            'go_to_settings' => [
                'title'       => __('Settings', 'payra-cash-crypto-payment'),
                'type'        => 'text',
                'description' => sprintf(
                    '<a href="%s" class="button button-primary">%s</a>',
                    admin_url('admin.php?page=' . esc_attr(Constants::SETTINGS_PAGE_SLUG) . '&tab=general'),
                    __('Open Payra Manager', 'payra-cash-crypto-payment')
                ),
                'default'     => '',
            ],
        ];
    }

    public function validate_fields()
    {
        $settings_obj = new Settings;
        $settings = $settings_obj->get_data();

        if (empty($settings['order_prefix'])) {
            wc_add_notice(__('Please provide Order ID Prefix.', 'payra-cash-crypto-payment'), 'error');
            return false;
        }

        $store_currency = get_woocommerce_currency();
        if ($store_currency !== 'USD' && empty($settings['exchangerate_api_key'])) {
            wc_add_notice(__('Exchange Rate API Key is required when store currency is not USD.', 'payra-cash-crypto-payment'), 'error');
            return false;
        }

        // RPC URLS
        $network_rpcs_urls = $settings['network_rpcs_urls'] ?? [];

        if (!is_array($network_rpcs_urls) || empty($network_rpcs_urls)) {
            wc_add_notice(__('Please provide at least one RPC URL.', 'payra-cash-crypto-payment'), 'error');
            return false;
        }

        // Check if is any url in any network
        $network_rpcs_urls = $settings['network_rpcs_urls'] ?? [];

        $has_rpc = !empty(array_filter(
            array_merge(...array_values($network_rpcs_urls ?? [])),
            fn($rpc) => !empty(trim($rpc))
        ));

        if (!$has_rpc) {
            wc_add_notice(__('Please provide at least one RPC URL.', 'payra-cash-crypto-payment'), 'error');
            return false;
        }

        return true;
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        if (!$order) {
            wc_add_notice(__('Payment error: Order not found.', 'payra-cash-crypto-payment'), 'error');
            return null;
        }

        // Empty cart
        WC()->cart->empty_cart();

        // Redirect to receipt_page
        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }

    public function receipt_page($order_id)
    {
        global $wpdb;

        $order = wc_get_order($order_id);
        $amount = $order->get_total();
        $currency = $order->get_currency();

        $payra_amount = Helper::get_order_amount_usd($order_id);
        $payra_currency = 'USD';

        // Get settings array with selected networks (z JSON)
        $settings_obj = new Settings;
        $user_network_settings = $settings_obj->get_data();

        // all networks
        $networks_table = Constants::$db_table_networks;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $networks_from_db = $wpdb->get_results(
            "SELECT * FROM {$networks_table}",
            ARRAY_A
        );

        $active_networks = [];
        $networks_tokens = [];

        foreach ($networks_from_db as $network) {
            $name = $network['name'];
            if (!empty($user_network_settings[$name]['active'])) {

                $stablecoins_table = Constants::$db_table_stablecoins;
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $tokens = $wpdb->get_results(
                    $wpdb->prepare("SELECT * FROM {$stablecoins_table} WHERE network_id = %d", $network['id']),
                    ARRAY_A
                );

                $networks_tokens[$network['id']] = $tokens;
                $active_networks[$name] = [
                    'label' => $network['label'],
                    'id' => $network['id'] ?? [],
                ];
            }
        }

        $js_form_file = PAYRACACR_CASH_PLUGIN_PATH . 'assets/js/pay-form.js';
        wp_enqueue_script(
            'payracacr-pay-form',
            PAYRACACR_CASH_PLUGIN_URL . 'assets/js/pay-form.js',
            [],
            file_exists($js_form_file) ? filemtime($js_form_file) : false,
            true
        );

        wp_localize_script('payracacr-pay-form', 'PayraCaCrForm', [
            'chooseOptionLabel'     => __('-- Choose --', 'payra-cash-crypto-payment'),
            'msgMetaMaskNotFound'   => __('MetaMask not found! Please install it', 'payra-cash-crypto-payment'),
            'msgAddToWalletError'   => __('Add to wallet error', 'payra-cash-crypto-payment'),
            'msgWalletConnectError' => __('Wallet connect error', 'payra-cash-crypto-payment'),
            'msgCheckStatus'        => __('Check Status', 'payra-cash-crypto-payment'),
            'msgOrderPaidRedirect'  => __('Order paid successfully! Redirecting...', 'payra-cash-crypto-payment'),
            'msgOrderPending'       => __('Order is still pending...', 'payra-cash-crypto-payment'),
            'msgErrorCheckStatus'   => __('Error checking order status.', 'payra-cash-crypto-payment'),
            'msgProcessing'         => __('Processing...', 'payra-cash-crypto-payment'),
            'msgInvalidOrderStatus' => __('You can\'t pay for order with status:', 'payra-cash-crypto-payment'),
            'msgAjaxError'          => __('AJAX error', 'payra-cash-crypto-payment'),
            'msgNetworkError'       => __('Network data error', 'payra-cash-crypto-payment'),
            'msgApproveToken'       => __('Step 1/2: Approving token spend...', 'payra-cash-crypto-payment'),
            'msgCancelByUser'       => __('Cancel by user', 'payra-cash-crypto-payment'),
            'msgUnsupportedError'   => __('Unsupported error:', 'payra-cash-crypto-payment'),
            'msgUnknownError'       => __('Unknown error occurred', 'payra-cash-crypto-payment'),
            'msgStep2Processing'    => __('Step 2/2: Processing payment...', 'payra-cash-crypto-payment'),
            'msgProcessingPayment'  => __('Processing payment...', 'payra-cash-crypto-payment'),
            'msgRedirectFailed'     => __('Order paid, but the redirect failed. Contact the seller.', 'payra-cash-crypto-payment'),
            'msgCantGetLog'         => __('Can\'t get log, check status manually.', 'payra-cash-crypto-payment'),
            'msgSignatureFailed'    => __('Signature creation failed.', 'payra-cash-crypto-payment'),
            'msgChangingNetwork'    => __('Changing network on your wallet...', 'payra-cash-crypto-payment'),
            'msgAddedAndSwitched'   => __('Added and switched to:', 'payra-cash-crypto-payment'),
            'msgCouldNotAddNetwork' => __('Could not add network, please do it manually in MetaMask', 'payra-cash-crypto-payment'),
            'msgChainChanged'       => __('Chain changed, reloading provider...', 'payra-cash-crypto-payment'),
            'msgAccountsChanged'    => __('Accounts changed:', 'payra-cash-crypto-payment'),
            'tokensByNetwork'       => $networks_tokens ?? [],
            'orderId'               => (int) $order_id,
            'payraAmount'           => $payra_amount ?? '',
        ]);

        require_once(plugin_dir_path(PAYRACACR_CASH_PLUGIN_FILE) . '/views/payment/form.php');
    }
}
