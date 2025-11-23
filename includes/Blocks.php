<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (!defined('ABSPATH')) exit;

final class Blocks extends AbstractPaymentMethodType
{
    protected $name = 'payra_cash_crypto_payment';
    protected $gateway;
    protected $settings;

    public function initialize()
    {
        $gateways = WC()->payment_gateways()->payment_gateways();
        $this->gateway = $gateways[$this->name] ?? null;
        $this->settings = $this->gateway
            ? $this->gateway->settings
            : [];
    }

    public function is_active()
    {
        return $this->gateway && $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        wp_enqueue_script(
            'wc-payra-cash-crypto-payment-blocks-integration',
            plugins_url('block/payracacr-blocks.js', PAYRACACR_CASH_PLUGIN_FILE),
            ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n'],
            PAYRACACR_CASH_PLUGIN_VERSION,
            true
        );

        wp_add_inline_script(
            'wc-payra-cash-crypto-payment-blocks-integration',
            'window.wc = window.wc || {}; window.wc.wcSettings = window.wc.wcSettings || {}; window.wc.wcSettings["payracacr_crypto_payment_data"] = ' . wp_json_encode([
                'title' => $this->settings['title'] ?? __('Payra Cash', 'payra-cash-crypto-payment'),
                'description' => $this->settings['description'] ?? __('Pay with cryptocurrency On-Chain via Payra Cash.', 'payra-cash-crypto-payment'),
                'ariaLabel' => $this->settings['title'] ?? __('Payra Cash Crypto Payment', 'payra-cash-crypto-payment'),
            ]) . ';',
            'before'
        );

        return ['wc-payra-cash-crypto-payment-blocks-integration'];
    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->settings['title'] ?? __('Payra Cash Crypto Payment', 'payra-cash-crypto-payment'),
            'description' => $this->settings['description'] ?? __('Pay with cryptocurrency On-Chain via Payra Cash', 'payra-cash-crypto-payment'),
            'ariaLabel' => $this->settings['title'] ?? __('Payra Cash Crypto Payment', 'payra-cash-crypto-payment'),
            'supports' => ['products', 'default', 'virtual'],
        ];
    }

}
