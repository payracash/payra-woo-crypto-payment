<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

if (!defined('ABSPATH')) exit;

class Enqueue
{
    public function register()
    {
        add_action('wp_enqueue_scripts', [$this, 'payrcacr_enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'payrcacr_admin_assets']);
    }

    private function get_localize_data()
    {
        $data = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('payra_cash_nonce'),
        ];

        $payra_abi = [];
        $erc20_abi = [];

        // === ABI files ===
        $payra_abi_path = PAYRACACR_CASH_PLUGIN_PATH . 'contracts/payraABI.json';
        $erc20_abi_path = PAYRACACR_CASH_PLUGIN_PATH . 'contracts/erc20ABI.json';

        if (file_exists($payra_abi_path)) {
            $payra_abi = json_decode(file_get_contents($payra_abi_path), true);
        }

        if (file_exists($erc20_abi_path)) {
            $erc20_abi = json_decode(file_get_contents($erc20_abi_path), true);
        }

        $data['payraConfig'] = [
            'payra_abi' => $payra_abi,
            'erc20_abi' => $erc20_abi,
        ];

        return $data;
    }

    public function payrcacr_admin_assets()
    {
        global $wpdb;

        // === CSS ===
        $css_payra_file = PAYRACACR_CASH_PLUGIN_PATH . 'assets/css/general.css';
        wp_enqueue_style(
            'payracacr-general-style',
            PAYRACACR_CASH_PLUGIN_URL . 'assets/css/general.css',
            [],
            file_exists($css_payra_file) ? filemtime($css_payra_file) : false
        );

        $css_toastr_file = PAYRACACR_CASH_PLUGIN_PATH . 'assets/css/toastr-min.css';
        wp_enqueue_style(
            'payracacr-toastr-info-style',
            PAYRACACR_CASH_PLUGIN_URL . 'assets/css/toastr-min.css',
            [],
            file_exists($css_toastr_file) ? filemtime($css_toastr_file) : false
        );

        // === JS ===
        $js_payra_file = PAYRACACR_CASH_PLUGIN_PATH . 'assets/js/payment.js';
        wp_enqueue_script(
            'payracacr-payment-script',
            PAYRACACR_CASH_PLUGIN_URL . 'assets/js/payment.js',
            [],
            file_exists($js_payra_file) ? filemtime($js_payra_file) : false,
            true
        );

        $js_toastr_file = PAYRACACR_CASH_PLUGIN_PATH . 'assets/js/toastr-min.js';
        wp_enqueue_script(
            'payracacr-toastr-info-script',
            PAYRACACR_CASH_PLUGIN_URL . 'assets/js/toastr-min.js',
            [],
            file_exists($js_toastr_file) ? filemtime($js_toastr_file) : false,
            true
        );

        // === Localize for AJAX ===
        wp_localize_script('payracacr-payment-script', 'PayraCaCrLocalize', $this->get_localize_data());
    }

    public function payrcacr_enqueue_assets()
    {
        // === CSS ===
        $css_payra_file = PAYRACACR_CASH_PLUGIN_PATH . 'assets/css/general.css';
        wp_enqueue_style(
            'payracacr-general-style',
            PAYRACACR_CASH_PLUGIN_URL . 'assets/css/general.css',
            [],
            file_exists($css_payra_file) ? filemtime($css_payra_file) : false
        );

        $css_toastr_file = PAYRACACR_CASH_PLUGIN_PATH . 'assets/css/toastr-min.css';
        wp_enqueue_style(
            'payracacr-toastr-info-style',
            PAYRACACR_CASH_PLUGIN_URL . 'assets/css/toastr-min.css',
            [],
            file_exists($css_toastr_file) ? filemtime($css_toastr_file) : false
        );

        // === JS ===
        $js_payra_file = PAYRACACR_CASH_PLUGIN_PATH . 'assets/js/payment.js';
        wp_enqueue_script(
            'payracacr-payment-script',
            PAYRACACR_CASH_PLUGIN_URL . 'assets/js/payment.js',
            [],
            file_exists($js_payra_file) ? filemtime($js_payra_file) : false,
            true
        );

        $js_ethers_file = PAYRACACR_CASH_PLUGIN_PATH . 'assets/js/ethers-umd-min.js';
        wp_enqueue_script(
            'payracacr-ethers-web3-wallet-script',
            PAYRACACR_CASH_PLUGIN_URL . 'assets/js/ethers-umd-min.js',
            [],
            file_exists($js_ethers_file) ? filemtime($js_ethers_file) : false,
            true
        );

        $js_toastr_file = PAYRACACR_CASH_PLUGIN_PATH . 'assets/js/toastr-min.js';
        wp_enqueue_script(
            'payracacr-toastr-info-script',
            PAYRACACR_CASH_PLUGIN_URL . 'assets/js/toastr-min.js',
            [],
            file_exists($js_toastr_file) ? filemtime($js_toastr_file) : false,
            true
        );

        // === Localize for AJAX ===
        wp_localize_script('payracacr-payment-script', 'PayraCaCrLocalize', $this->get_localize_data());
    }

}
