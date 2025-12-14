<?php
/**
 * Note for reviewers:
 * All PHP variables used in this JS file are passed via wp_localize_script().
 * No direct echo or json_encode() calls are used in JS context.
 */
?>

<?php if (!defined('ABSPATH')) exit; ?>
<h2><?php esc_html_e( 'Pay with Crypto via Payra Cash', 'payra-cash-crypto-payment' ); ?></h2>
<p><?php esc_html_e( 'Select network and currency to continue.', 'payra-cash-crypto-payment' ); ?></p>

<div id="payra-payment-ui" class="payra-form">
    <!-- Network -->
    <div class="form-group">
        <label for="payra-network">
            <?php esc_html_e('Network', 'payra-cash-crypto-payment'); ?> <span class="required">*</span>
        </label>
        <select id="payra-network" class="select2">
            <option value=""><?php esc_html_e('-- Choose --', 'payra-cash-crypto-payment'); ?></option>
            <?php foreach ($active_networks as $network_data) : ?>
                <option value="<?php echo esc_attr( $network_data['id'] ); ?>">
                    <?php echo esc_html( $network_data['label'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Currency / Token -->
    <div class="form-group">
        <label for="payra-currency">
            <?php esc_html_e('Currency', 'payra-cash-crypto-payment'); ?> <span class="required">*</span>
        </label>
        <select id="payra-currency" class="select2">
            <option value=""><?php esc_html_e('-- Choose --', 'payra-cash-crypto-payment'); ?></option>
        </select>
        <div id="payra-token-info">
            <div><span id="token-address-text"></span></div>
            <div><a href="#" id="add-to-wallet"><?php esc_html_e('Add to wallet', 'payra-cash-crypto-payment'); ?></a></div>
        </div>
    </div>

    <!-- Price -->
    <div class="form-group">
        <label for="payra-price"><?php esc_html_e('Price', 'payra-cash-crypto-payment'); ?></label>
        <div id="payra-price" class="payra-price"><?php echo esc_html($payra_amount . ' ' . $payra_currency); ?></div>
    </div>

    <!-- Pay Button -->
    <div class="form-group" id="payra-pay-container">
        <button id="payra-metamask-btn" class="payra-btn payra-btn--orange" disabled>
            <span class="btn-text"><?php esc_html_e('Pay with MetaMask', 'payra-cash-crypto-payment'); ?></span>
            <span class="btn-spinner" style="display:none;"></span>
            <span class="btn-glow"></span>
        </button>
    </div>

    <!-- Check Button -->
    <div class="form-group" id="payra-check-container" style="display:none;">
        <button id="payra-check-btn" class="payra-btn payra-btn--blue" data-check-order-id="<?php echo (int) $order_id; ?>">
            <span class="btn-text"><?php esc_html_e('Check Status', 'payra-cash-crypto-payment'); ?></span>
            <span class="btn-spinner" style="display:none;"></span>
            <span class="btn-glow"></span>
        </button>
    </div>

    <!-- Status Box -->
    <div id="payra-status" class="payra-status">
        <p class="status-message"><?php esc_html_e('Waiting for user action...', 'payra-cash-crypto-payment'); ?></p>
    </div>

    <!-- Animated particles -->
    <div class="particles-container">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="payra-branding">
        <a href="https://payra.cash" target="_blank">
            <img src="<?php echo esc_url(plugins_url('assets/img/payracash-logo.png', PAYRACACR_CASH_PLUGIN_FILE)); ?>" width="14" height="14" alt="Payra Cash logo"/>
        </a>
        <a href="https://payra.cash" target="_blank"><?php esc_html_e('Payra Cash', 'payra-cash-crypto-payment'); ?></a>
    </div>
</div>
