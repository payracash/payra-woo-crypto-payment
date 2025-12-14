<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <div class="payra-support-grid">
        <!-- Left column: FAQ -->
        <div class="payra-card support-faq">
            <h2><?php esc_html_e('F.A.Q.', 'payra-cash-crypto-payment'); ?></h2>
            <ul class="faq-list">
                <li>
                    <p class="margin-bottom-10">
                        <strong><?php esc_html_e('Where do I get my Merchant ID?', 'payra-cash-crypto-payment'); ?></strong>
                    </p>
                    <p>
                        <?php echo esc_html__('Your', 'payra-cash-crypto-payment'); ?>
                        <code><?php echo esc_html__('Merchant ID', 'payra-cash-crypto-payment'); ?></code>
                        <?php echo esc_html__('is like your personal crypto payment address on Payra. It’s linked to your wallet and smart contract, so there’s no account or login needed. You’ll need a Merchant ID for each blockchain where you want to accept payments — for example, Ethereum, Polygon, Flare, Linea, etc. But if you just want to use one network, that’s totally fine — one ID is all you need! You can get your', 'payra-cash-crypto-payment'); ?>
                        <code><?php echo esc_html__('Merchant ID', 'payra-cash-crypto-payment'); ?></code>
                        <?php esc_html_e('from', 'payra-cash-crypto-payment'); ?>
                        <a href="https://payra.cash" target="_blank">payra.cash</a>
                    </p>
                </li>
                <li>
                    <p class="margin-bottom-10">
                        <strong><?php esc_html_e('Do I need to add a custom prefix to my orders?', 'payra-cash-crypto-payment'); ?></strong>
                    </p>
                    <p>
                        <?php echo esc_html__('Yes. Each crypto transaction needs a unique prefix (default is', 'payra-cash-crypto-payment'); ?>
                        <code>ord</code>
                        <?php echo esc_html__(') to identify your order. This prefix must be different every time you reset your orders, start over, or run multiple shops. For example, if you use', 'payra-cash-crypto-payment'); ?>
                        <code>ord-1</code>
                        <?php esc_html_e('in one shop, you can’t use the same prefix in another shop or after resetting. Just make sure each order prefix is unique for your setup.', 'payra-cash-crypto-payment'); ?>
                    </p>
                </li>
                <li>
                    <p class="margin-bottom-10">
                        <strong><?php esc_html_e('Can a buyer pay for the same transaction twice?', 'payra-cash-crypto-payment'); ?></strong>
                    </p>
                    <p>
                        <?php echo esc_html__('No, buyer cannot pay twice for the same transaction. If a payment is still processing and you refresh the page, the pay button might appear again. But if you try to pay a second time, the system will tell you to check the transaction status instead of letting you pay again. Even if the button somehow appears, the blockchain will block any duplicate transaction, so no extra money will be taken.', 'payra-cash-crypto-payment'); ?>
                    </p>
                </li>
            </ul>
        </div>
        <!-- Right column: vids -->
        <div class="payra-card support-videos">
            <h2><?php esc_html_e('Video Tutorials', 'payra-cash-crypto-payment'); ?></h2>

            <div class="video-link" onclick="window.open('https://www.youtube.com/watch?v=8Nx6iCPh7Ao', '_blank')">
                🎬 <?php esc_html_e('Activate Plugin', 'payra-cash-crypto-payment'); ?>
            </div>

            <div class="video-link" onclick="window.open('https://www.youtube.com/watch?v=qenBKH81yds', '_blank')">
                🆔 <?php esc_html_e('Create Merchant ID', 'payra-cash-crypto-payment'); ?>
            </div>

            <div class="video-link" onclick="window.open('https://www.youtube.com/watch?v=qLaz1f8uV3k', '_blank')">
                🔑 <?php esc_html_e('Merchant ID & Signature Key', 'payra-cash-crypto-payment'); ?>
            </div>

            <div class="video-link" onclick="window.open('https://www.youtube.com/watch?v=tTwrjPRgPQc', '_blank')">
                🌐 <?php esc_html_e('RPC URLs Settings', 'payra-cash-crypto-payment'); ?>
            </div>

            <div class="video-link" onclick="window.open('https://www.youtube.com/watch?v=X2ZRDTLy1bQ', '_blank')">
                💱 <?php esc_html_e('Exchange Rate', 'payra-cash-crypto-payment'); ?>
            </div>

            <div class="video-link" onclick="window.open('https://www.youtube.com/watch?v=mHk8Ujj-rAA', '_blank')">
                🧩 <?php esc_html_e('Order Prefix', 'payra-cash-crypto-payment'); ?>
            </div>

            <div class="video-link" onclick="window.open('https://www.youtube.com/watch?v=3rUtNzNKHSs', '_blank')">
                ⚙️ <?php esc_html_e('Additional Settings', 'payra-cash-crypto-payment'); ?>
            </div>
        </div>
    </div>
    <div class="tablenav bottom">
        <?php include(PAYRACACR_CASH_PLUGIN_PATH . 'views/footer.php'); ?>
        <br class="clear">
    </div>
</div>
