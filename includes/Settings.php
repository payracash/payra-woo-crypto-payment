<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

if (!defined('ABSPATH')) exit;

class Settings extends Constants
{
    public function register()
    {
        add_filter('plugin_action_links_' . PAYRACACR_CASH_PLUGIN_SLUG, [$this, 'payracacr_filter_add_plugin_action_links']);
        add_filter('plugin_row_meta', [$this, 'payracacr_filter_row_meta'], 10, 2);
        add_action('admin_post_payra_save_settings', [$this, 'payracacr_action_payra_save_settings']);
    }

    public function payracacr_filter_add_plugin_action_links($links)
    {
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=payra_cash_crypto_payment') . '">' . __('Settings', 'payra-cash-crypto-payment') . '</a>';
        $links[] = $settings_link;

        $manager_link = '<a href="' . admin_url('admin.php?page=' . esc_attr(self::SETTINGS_PAGE_SLUG)) . '">' . __('Manager', 'payra-cash-crypto-payment') . '</a>';
        $links[] = $manager_link;

        $register_link = '<a href="' . esc_attr(self::PLUGIN_WEBSITE_URL) . '/products/on-chain-payments/merchant-registration#registration-form" target="_blank">' . __('Register', 'payra-cash-crypto-payment') . '</a>';
        $links[] = $register_link;

        return $links;
    }

    public function payracacr_filter_row_meta($links, $file)
    {
      if ($file === PAYRACACR_CASH_PLUGIN_SLUG) {
        $links[] = '<a href="' . esc_attr(self::PLUGIN_WEBSITE_URL) . '/docs?p=wordpress" target="_blank">Docs</a>';
        $links[] = '<a href="https://wordpress.org/support/plugin/payra-cash-crypto-payment" target="_blank">Community support</a>';
      }

      return $links;
    }


    public function payracacr_action_payra_save_settings(): void
    {
        global $wpdb;

        if (!isset($_POST['payra_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['payra_settings_nonce'])), 'save_payra_settings')) {
            wp_die(esc_html__('Security check failed', 'payra-cash-crypto-payment'));
        }

        $new_settings = [];
        $networks_available = $wpdb->get_results("SELECT * FROM {$this->networks_table}", ARRAY_A);
        foreach ($networks_available as $network) {
            $name = $network['name'];
            $new_settings[$name] = [
                'active'        => !empty($_POST["{$name}_active"]) ? 1 : 0,
                'merchant_id'   => sanitize_text_field(wp_unslash($_POST["{$name}_merchant_id"] ?? '')),
                'signature_key' => sanitize_text_field(wp_unslash($_POST["{$name}_signature_key"] ?? '')),
            ];
        }

        // RPC URL
        $network_rpcs_networks = isset($_POST['network_rpcs_networks']) && is_array($_POST['network_rpcs_networks'])
            ? array_map('sanitize_text_field', wp_unslash($_POST['network_rpcs_networks']))
            : [];

        $urls = isset($_POST['network_rpcs_urls']) && is_array($_POST['network_rpcs_urls'])
            ? array_map('sanitize_text_field', wp_unslash($_POST['network_rpcs_urls']))
            : [];

        $network_rpcs = [];
        foreach ($network_rpcs_networks as $i => $net) {
            $rpc = trim($urls[$i] ?? '');
            if (!empty($net) && !empty($rpc)) {
                $network_rpcs[$net][] = $rpc;
            }
        }

        $new_settings['network_rpcs_urls'] = $network_rpcs;

        // Exchangerate
        $new_settings['exchangerate_api_key'] = sanitize_text_field(wp_unslash($_POST['exchangerate_api_key'] ?? ''));
        $plan = isset($_POST['exchangerate_plan'])
            ? sanitize_text_field(wp_unslash($_POST['exchangerate_plan']))
            : '';

        $new_settings['exchangerate_plan'] = in_array($plan, ['free', 'paid'], true) ? $plan : 'free';

        // Prefix
        $order_prefix = sanitize_text_field(wp_unslash($_POST['order_prefix'] ?? ''));
        if (empty($order_prefix)) {
            $order_prefix = 'ord';
        }

        // Result per transactions page
        $result_per_page_on_transactions = isset($_POST['result_per_page_on_transactions']) && $_POST['result_per_page_on_transactions'] !== ''
            ? max(1, intval($_POST['result_per_page_on_transactions']))
            : 50;

        // Result per news page
        $result_per_page_on_news = isset($_POST['result_per_page_on_news']) && $_POST['result_per_page_on_news'] !== ''
            ? max(1, intval($_POST['result_per_page_on_news']))
            : 20;

        // Transaction expiration time
        $tx_expiration_time = isset($_POST['tx_expiration_time']) && $_POST['tx_expiration_time'] !== ''
            ? max(1, intval($_POST['tx_expiration_time']))
            : 30;

        // Default tab
        $payra_default_tab = sanitize_text_field(wp_unslash($_POST['payra_default_tab'] ?? ''));
        if (empty($payra_default_tab)) {
            $payra_default_tab = 'transactions';
        }

        // Update settings in the database
        $wpdb->update(
            self::$db_table_settings, [
                'settings' => wp_json_encode($new_settings),
                'order_prefix' => $order_prefix,
                'result_per_page_on_transactions' => $result_per_page_on_transactions,
                'result_per_page_on_news' => $result_per_page_on_news,
                'tx_expiration_time' => $tx_expiration_time,
                'payra_default_tab' => $payra_default_tab,
            ], [
                'symbol' => self::DICT_SETTINGS_SYMBOL
            ]
        );

        // Redirct
        wp_safe_redirect(
            add_query_arg(['page' => esc_attr(self::SETTINGS_PAGE_SLUG), 'tab' => 'general', 'updated' => 'true'], admin_url('admin.php'))
        );

        exit;
    }

    /**
     * Get Payra plugin settings from the database
     *
     * @return array
     */
    public function get_data(): array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->settings_table} WHERE symbol = %s", self::DICT_SETTINGS_SYMBOL)
        );

        $settings = [];

        if ($row && $row->settings) {
            $settings = json_decode($row->settings, true);
            if (!is_array($settings)) {
                $settings = [];
            }
        }

        $settings['order_prefix'] = (string) $row->order_prefix;
        $settings['result_per_page_on_transactions'] = (int) $row->result_per_page_on_transactions;
        $settings['result_per_page_on_news'] = (int) $row->result_per_page_on_news;
        $settings['tx_expiration_time'] = (int) $row->tx_expiration_time;
        $settings['payra_default_tab'] = (string) $row->payra_default_tab;
        $settings['cron_transaction_batch_size'] = (int) $row->cron_transaction_batch_size;
        $settings['cron_transaction_sleep_seconds'] = (int) $row->cron_transaction_sleep_seconds;

        return $settings;
    }

}
