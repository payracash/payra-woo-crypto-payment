<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

if (!defined('ABSPATH')) exit;

class Menu extends Constants
{
    public function register()
    {
        add_action('admin_menu', [$this, 'payracacr_action_admin_menu']);
    }

    public function payracacr_action_admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            __('Payra Cash Settings', 'payra-cash-crypto-payment'),
            __('Payra Cash', 'payra-cash-crypto-payment') . ' ' . (get_option(self::IS_UNREAD_NEWS, 0)
                ? '<span class="payra-badge payra-badge-pill payra-badge-info">N</span>'
                : ''),
            'manage_options',
            self::SETTINGS_PAGE_SLUG,
            [self::class, 'render_dashboard']
        );
    }

    public static function render_dashboard()
    {
        $settings_file = dirname(__FILE__, 2) . '/views/settings.php';
        if (!empty($settings_file) && file_exists($settings_file)) {
            include $settings_file;
        } else {
            echo '<p>' . esc_html__('Settings file not exists!', 'payra-cash-crypto-payment') . '</p>';
        }
    }

}
