<?php
/**
 * Plugin Name: Payra Cash Crypto Payment
 * Requires Plugins: woocommerce
 * Description: Accept crypto payments on-chain directly in WooCommerce with Payra Cash.
 * Version: 1.0.2
 * Author: Payra Cash
 * Author URI: https://payra.cash
 * License: GPLv2
 * Text Domain: payra-cash-crypto-payment
 * Domain Path: /lang
 * Requires Plugins: woocommerce
 * WC requires at least: 4.0
 * WC tested up to: 10.3.5
 */

if (!defined('ABSPATH')) exit;

if (!extension_loaded('gmp'))
{
    wp_die('Payra Cash requires GMP PHP extension. Please enable it.');
}

define('PAYRACACR_CASH_PLUGIN_VERSION', '1.0.2');
define('PAYRACACR_CASH_DB_VERSION', '2');
define('PAYRACACR_CASH_PLUGIN_FILE', __FILE__);
define('PAYRACACR_CASH_PLUGIN_PATH', plugin_dir_path(PAYRACACR_CASH_PLUGIN_FILE));
define('PAYRACACR_CASH_PLUGIN_URL', plugin_dir_url(PAYRACACR_CASH_PLUGIN_FILE));
define('PAYRACACR_CASH_PLUGIN_SLUG', plugin_basename(PAYRACACR_CASH_PLUGIN_FILE));

// ===============================
// Composer autoload
// ===============================
if (file_exists(__DIR__ . '/vendor/autoload.php'))
{
    require_once __DIR__ . '/vendor/autoload.php';
}


// ===============================
// Cron filter
// ===============================
add_filter('cron_schedules', function ($schedules) {
    $schedules['every_five_minutes'] = [
        'interval' => 300,
        'display' => __('Every 5 Minutes', 'payra-cash-crypto-payment'),
    ];
    return $schedules;
});

// ===============================
// Activation / Deactivation hooks
// ===============================
register_activation_hook(__FILE__, function()
{
    if (class_exists('Xxxwraithxxx\\PayraCashCryptoPayment\\Bootstrap')) {
        Xxxwraithxxx\PayraCashCryptoPayment\Bootstrap::payracacr_activate();
    }
});

register_deactivation_hook(__FILE__, function()
{
    if (class_exists('Xxxwraithxxx\\PayraCashCryptoPayment\\Bootstrap')) {
        Xxxwraithxxx\PayraCashCryptoPayment\Bootstrap::payracacr_deactivate();
    }
});

// ======================================================================
// Delay init until plugins are loaded to ensure WooCommerce is available
// ======================================================================
add_action('plugins_loaded', function ()
{
    /**
     * =========================
     * DB MIGRATIONS
     * =========================
     */
    $installed_db_version = get_option(Xxxwraithxxx\PayraCashCryptoPayment\Constants::INSTALLED_DB_VERSION, 1);
    $current_db_version = PAYRACACR_CASH_DB_VERSION;

    if (version_compare($installed_db_version, $current_db_version, '<')) {
        if (class_exists('Xxxwraithxxx\\PayraCashCryptoPayment\\Update')) {
            Xxxwraithxxx\PayraCashCryptoPayment\Update::update_db();
        }
        update_option(Xxxwraithxxx\PayraCashCryptoPayment\Constants::INSTALLED_DB_VERSION, $current_db_version);
    }

    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    add_filter('woocommerce_payment_gateways', function($gateways) {
        $gateways[] = 'Xxxwraithxxx\PayraCashCryptoPayment\Gateway';
        return $gateways;
    });

    if (class_exists('Xxxwraithxxx\\PayraCashCryptoPayment\\Init')) {
        Xxxwraithxxx\PayraCashCryptoPayment\Init::register_services();
    }
}, 11);

// ======================================================================
// Blocks
// ======================================================================
add_action('woocommerce_blocks_loaded', function ()
{
    add_action('woocommerce_blocks_payment_method_type_registration', function ($registry) {
        if (
            class_exists('\\Automattic\\WooCommerce\\Blocks\\Payments\\PaymentMethodRegistry') &&
            class_exists('\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType')
        ) {
            $registry->register(new Xxxwraithxxx\PayraCashCryptoPayment\Blocks);
        }
    });
});

// ===============================
// HPOS Compatibility
// ===============================
add_action('before_woocommerce_init', function()
{
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables', // HPOS
            __FILE__,
            true
        );
    }
});
