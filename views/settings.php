<?php
    if (!defined('ABSPATH')) exit;

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab switcher is read-only
    $current_tab = isset($_GET['tab'])
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab switcher is read-only
        ? wc_clean(sanitize_text_field(wp_unslash($_GET['tab'])))
        : (new Xxxwraithxxx\PayraCashCryptoPayment\Settings)->get_data()['payra_default_tab'];
?>
<h1><?php echo esc_html__('Payra Cash Dashboard', 'payra-cash-crypto-payment'); ?></h1>
<h2 class="nav-tab-wrapper">
    <a href="?page=<?php echo esc_attr(self::SETTINGS_PAGE_SLUG); ?>&tab=general"
       class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
        <?php echo esc_html__('General', 'payra-cash-crypto-payment'); ?>
    </a>

    <a href="?page=<?php echo esc_attr(self::SETTINGS_PAGE_SLUG); ?>&tab=transactions"
       class="nav-tab <?php echo $current_tab === 'transactions' ? 'nav-tab-active' : ''; ?>">
        <?php echo esc_html__('Transactions', 'payra-cash-crypto-payment'); ?>
    </a>

    <a href="?page=<?php echo esc_attr(self::SETTINGS_PAGE_SLUG); ?>&tab=news"
       class="nav-tab <?php echo $current_tab === 'news' ? 'nav-tab-active' : ''; ?>">
        <?php echo esc_html__('News', 'payra-cash-crypto-payment'); ?>
        <?php if (get_option(self::IS_UNREAD_NEWS, 0)) : ?>
            <span class="payra-badge payra-badge-pill payra-badge-info">N</span>
        <?php endif; ?>
    </a>

    <a href="?page=<?php echo esc_attr(self::SETTINGS_PAGE_SLUG); ?>&tab=cron"
       class="nav-tab <?php echo $current_tab === 'cron' ? 'nav-tab-active' : ''; ?>">
        <?php echo esc_html__('WP Cron', 'payra-cash-crypto-payment'); ?>
    </a>

    <a href="?page=<?php echo esc_attr(self::SETTINGS_PAGE_SLUG); ?>&tab=support"
       class="nav-tab <?php echo $current_tab === 'support' ? 'nav-tab-active' : ''; ?>">
        <?php echo esc_html__('Support', 'payra-cash-crypto-payment'); ?>
    </a>
</h2>
<?php
    if ($current_tab === 'general') {
        include_once 'tabs/general.php';
    } elseif ($current_tab === 'transactions') {
        include_once 'tabs/transactions.php';
    } elseif ($current_tab === 'news') {
        update_option(self::IS_UNREAD_NEWS, 0);
        include_once 'tabs/news.php';
    } elseif ($current_tab === 'cron') {
        include_once 'tabs/cron.php';
    } elseif ($current_tab === 'support') {
        include_once 'tabs/support.php';
    }
