<?php
    if (!defined('ABSPATH')) exit;

    global $wpdb;

    $settings = (new Xxxwraithxxx\PayraCashCryptoPayment\Settings())->get_data();
    $networks_table = self::$db_table_networks;
    $stablecoins_table = self::$db_table_stablecoins;
    $networks = $wpdb->get_results("SELECT * FROM {$networks_table}", ARRAY_A);

    foreach ($networks as $key => $network) {
        $networks[$key]['tokens'] = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$stablecoins_table} WHERE network_id = %d", $network['id']), ARRAY_A
        );
    }

    // === JS General ===
    $js_admin_file = PAYRACACR_CASH_PLUGIN_PATH . 'assets/js/admin-general.js';
    wp_enqueue_script(
        'payracacr-admin-general',
        PAYRACACR_CASH_PLUGIN_URL . 'assets/js/admin-general.js',
        [],
        file_exists($js_admin_file) ? filemtime($js_admin_file) : false,
        true
    );

    // JS General
    $available_networks = [];
    foreach ($networks as $net) {
        $available_networks[$net['name']] = $net['label'];
    }

    wp_localize_script('payracacr-admin-general', 'PayraCaCrAdmin', [
        'availableNetworks' => $available_networks,
        'msgRemove' => esc_html__('Remove', 'payra-cash-crypto-payment'),
        'msgCopied' => esc_html__('Copied!', 'payra-cash-crypto-payment'),
    ]);

?>
<div class="wrap">
    <?php
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only displaying a status flag, no data is modified
        if (!empty($_GET['updated'])):
    ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Settings saved.', 'payra-cash-crypto-payment'); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('save_payra_settings', 'payra_settings_nonce'); ?>
        <input type="hidden" name="action" value="payra_save_settings">
        <div class="payra-card">
            <h2><?php esc_html_e('Merchant Settings', 'payra-cash-crypto-payment'); ?></h2>
            <div style="display:flex; gap:30px; align-items:flex-start;">
                <div style="flex:1;">
                    <p class="description">
                        <?php esc_html_e('To use the system, you need to create a', 'payra-cash-crypto-payment'); ?>
                        <strong>Merchant ID</strong>
                        <?php esc_html_e('on the website', 'payra-cash-crypto-payment'); ?>
                        <a href="<?php echo esc_url(self::PLUGIN_WEBSITE_URL . '/products/on-chain-payments/merchant-registration#registration-form'); ?>" target="_blank">payra.cash</a>.
                        <?php esc_html_e('Please note that a separate Merchant ID must be generated for each network you intend to use. Make sure to follow the instructions on the website carefully for each network to ensure proper setup and avoid any payment issues. Each network uses its own stablecoins, which should appear automatically in your account. However, if you wish, you can manually add them to your crypto wallet by clicking on the token name to copy its contract address and then importing it into your wallet.', 'payra-cash-crypto-payment'); ?>
                    </p>
                </div>
                <div style="flex:1;">
                    <p class="description">
                        <?php esc_html_e('Signature key is the private key of one of your wallet accounts, used only for signing orders. This account never receives funds and cannot change your payout address. Even if someone gains access to this key, they cannot steal your money — unless you keep funds on that account (which we strongly advise against). Remember, never store funds on this account, payouts always go to the wallet you registered, and that address cannot be changed.', 'payra-cash-crypto-payment'); ?>
                    </p>
                </div>
            </div>
            <table class="widefat striped" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th scope="col"><b><?php esc_html_e('Network', 'payra-cash-crypto-payment'); ?></b></th>
                        <th scope="col"><b><?php esc_html_e('Merchant ID', 'payra-cash-crypto-payment'); ?></b></th>
                        <th scope="col"><b><?php esc_html_e('Signature Key', 'payra-cash-crypto-payment'); ?></b></th>
                        <th scope="col"><b><?php esc_html_e('Stablecoin', 'payra-cash-crypto-payment'); ?></b></th>
                        <th scope="col"><b><?php esc_html_e('Active', 'payra-cash-crypto-payment'); ?></b></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($networks as $network):
                      $name = $network['name'];
                      $active = !empty($settings[$name]['active']);
                      $merchant_id = $settings[$name]['merchant_id'] ?? '';
                      $signature_key = $settings[$name]['signature_key'] ?? '';
                      $currency = $network['currency'];
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($network['label']); ?></strong></td>
                            <td><input type="text" name="<?php echo esc_attr($name); ?>_merchant_id" value="<?php echo esc_attr($merchant_id); ?>" class="regular-text" /></td>
                            <td><input type="text" name="<?php echo esc_attr($name); ?>_signature_key" value="<?php echo esc_attr($signature_key); ?>" class="regular-text" /></td>
                            <td>
                                <?php foreach ($network['tokens'] as $token): ?>
                                    <span class="payra-pill payra-tooltip copy-contract" data-contract="<?php echo esc_attr($token['contract_address']); ?>">
                                        <?php echo esc_html($token['display_symbol']); ?>
                                        <span class="payra-tooltip-text"><?php echo esc_html($token['contract_address']); ?></span>
                                    </span>
                                <?php endforeach; ?>
                            </td>
                            <td style="text-align:center;"><input type="checkbox" name="<?php echo esc_attr($name); ?>_active" value="1" <?php checked($active, 1); ?> /></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="payra-card">
            <h2><?php esc_html_e('RPC URL Settings', 'payra-cash-crypto-payment'); ?></h2>
            <p class="description payra-cash-description-half">
                <?php esc_html_e('To enable blockchain order status checks, you need to provide at least one RPC endpoint. You can either create a free QuickNode account and use the full RPC URL generated there, or use your own custom RPC endpoint from another provider or self-hosted node. A free QuickNode plan includes 10 million credits per month, with each order status check consuming approximately 20 credits, allowing for around 500,000 order checks monthly.', 'payra-cash-crypto-payment'); ?>
            </p>
            <p class="description payra-cash-description-half">
                <?php esc_html_e('If your store exceeds this limit, you can add additional free QuickNode endpoints or upgrade to a paid plan with higher usage limits. Multiple RPC endpoints can be added per network, and the system will automatically balance requests across all available endpoints for optimal performance and reliability.', 'payra-cash-crypto-payment'); ?>
            </p>
            <table class="form-table" id="rpc-url-settings-table">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('RPC URL', 'payra-cash-crypto-payment'); ?></label><br/>
                        <a href="https://www.quicknode.com" target="_blank"><small><?php esc_html_e('Create Quick Node account', 'payra-cash-crypto-payment'); ?></small></a>
                    </th>
                    <td>
                        <div id="network-rpc-fields">
                            <?php
                                $network_rpcs = $settings['network_rpcs_urls'] ?? [];
                                if (empty($network_rpcs)) {
                                    $network_rpcs = ['' => ['']];
                                }

                                $available_networks = [];
                                foreach ($networks as $net) {
                                    $available_networks[$net['name']] = $net['label'];
                                }

                                foreach ($network_rpcs as $network => $rpcs):
                                    foreach ($rpcs as $rpc):
                            ?>
                                <div class="network-rpc-field payra-cash-mt-6">
                                    <select name="network_rpcs_networks[]">
                                        <?php foreach ($available_networks as $key => $label): ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected($network, $key); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="network_rpcs_urls[]" placeholder="https://" value="<?php echo esc_attr($rpc); ?>" class="regular-text" />
                                    <button type="button" class="button remove-network-rpc"><?php esc_html_e('Remove', 'payra-cash-crypto-payment'); ?></button>
                                </div>
                            <?php endforeach; endforeach; ?>
                        </div>
                        <p class="payra-cash-mt-10">
                            <button type="button" class="button" id="add-network-rpc"><?php esc_html_e('Add another RPC', 'payra-cash-crypto-payment'); ?></button>
                        </p>
                        <p class="description payra-cash-description-under-button">
                            <small><?php esc_html_e('Choose a network and enter one or more RPC endpoints. Requests will be balanced across all available endpoints.', 'payra-cash-crypto-payment'); ?></small>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <div class="payra-card">
            <h2><?php esc_html_e('Exchange Rate', 'payra-cash-crypto-payment'); ?></h2>
            <p class="description payra-cash-description-half">
                <?php esc_html_e('Crypto payments are accepted in US dollars, so you need to convert your local currency amount to dollars. If your store already uses dollars as the currency, you can skip this step. Otherwise, you will need to create an account with the service and provide an API key. If you are using the free plan, the exchange rate is updated by the provider once every 24 hours. Paid plans offer more frequent updates, ranging from every 5 minutes up to every 60 minutes. For most cases, the free plan is more than sufficient.', 'payra-cash-crypto-payment'); ?>
            </p>
            <table class="form-table" id="exchangerate-settings-table">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Exchangerate (API Key only)', 'payra-cash-crypto-payment'); ?></label><br/>
                        <a href="https://www.exchangerate-api.com" target="_blank"><small><?php esc_html_e('Create Exchangerate account', 'payra-cash-crypto-payment'); ?></small></a>
                    </th>
                    <td id="exchangerate-fields">
                        <input type="text" name="exchangerate_api_key" value="<?php echo esc_attr($settings['exchangerate_api_key'] ?? ''); ?>" class="regular-text" />
                        <select name="exchangerate_plan">
                            <option value="free" <?php selected($settings['exchangerate_plan'] ?? '', 'free'); ?>>
                                <?php esc_html_e('Free Plan (refresh ~12h)', 'payra-cash-crypto-payment'); ?>
                            </option>
                            <option value="paid" <?php selected($settings['exchangerate_plan'] ?? '', 'paid'); ?>>
                                <?php esc_html_e('Any Paid Plan (refresh 1h)', 'payra-cash-crypto-payment'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <div class="payra-card">
            <h2><?php esc_html_e('Order Prefix', 'payra-cash-crypto-payment'); ?></h2>
            <p class="description payra-cash-description-half">
                <?php esc_html_e('Each payment in the Payra smart contract is uniquely identified by a combination of merchantID and orderID. Since WooCommerce order numbers may reset (e.g. after reinstalling the store, database reset, or using multiple shops with the same merchant account), it is important to define a custom prefix. The prefix will be attached to every WooCommerce order number before sending it to the blockchain (e.g. store1-152, shopA-45). The dash (-) is added automatically, so if you enter Shop1, the final ID will look like Shop1-736. This ensures that your Payra transactions remain globally unique and prevents conflicts across multiple stores or database resets. This field cannot be left empty. If you do not provide a prefix, the system will automatically assign the default prefix', 'payra-cash-crypto-payment'); ?>
                <code>ord</code>
                <?php esc_html_e('to ensure uniqueness.', 'payra-cash-crypto-payment'); ?>
            </p>
            <table class="form-table" id="exchangerate-settings-table">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Order ID Prefix', 'payra-cash-crypto-payment'); ?></label>
                    </th>
                    <td id="order-prefix-fields">
                        <input
                            type="text"
                            name="order_prefix"
                            id="order_prefix"
                            value="<?php echo esc_attr($settings['order_prefix'] ?? ''); ?>"
                            class="regular-text"
                        />
                    </td>
                </tr>
            </table>
        </div>
        <div class="payra-card">
            <h2><?php esc_html_e('Transaction Expiration Time', 'payra-cash-crypto-payment'); ?></h2>
            <p class="description payra-cash-description-half">
                <?php esc_html_e('Define the time (in minutes) after which a pending transaction is automatically marked as expired. Once expired, the payment can no longer be completed. Default is 30 minutes.', 'payra-cash-crypto-payment'); ?>
            </p>
            <table class="form-table" id="transaction-expiration-settings-table">
                <tr>
                    <th scope="row">
                        <label for="tx_expiration_time"><?php esc_html_e('Expiration Time (minutes)', 'payra-cash-crypto-payment'); ?></label>
                    </th>
                    <td>
                        <input
                            type="number"
                            name="tx_expiration_time"
                            id="tx_expiration_time"
                            value="<?php echo esc_attr($settings['tx_expiration_time'] ?? 30); ?>"
                            class="small-text"
                            min="1"
                        />
                    </td>
                </tr>
            </table>
        </div>
        <div class="payra-card">
            <h2><?php esc_html_e('Pagination Settings', 'payra-cash-crypto-payment'); ?></h2>
            <p class="description payra-cash-description-half">
                <?php esc_html_e('Configure how many items should be displayed per page in different sections of the plugin. This controls pagination and helps you adjust the length of lists like Transactions or News.', 'payra-cash-crypto-payment'); ?>
            </p>
            <table class="form-table" id="pagination-settings">
                <tr>
                    <th scope="row">
                        <label for="result_per_page_on_transactions">
                            <?php esc_html_e('Transactions per page', 'payra-cash-crypto-payment'); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="number"
                            name="result_per_page_on_transactions"
                            id="result_per_page_on_transactions"
                            value="<?php echo esc_attr($settings['result_per_page_on_transactions'] ?? 50); ?>"
                            min="1"
                            class="small-text"
                        />
                        <p class="description">
                            <small>
                                <?php esc_html_e('Enter a number greater than 0. Default is 50.', 'payra-cash-crypto-payment'); ?>
                            </small>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="result_per_page_on_news">
                            <?php esc_html_e('News per page', 'payra-cash-crypto-payment'); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="number"
                            name="result_per_page_on_news"
                            id="result_per_page_on_news"
                            value="<?php echo esc_attr($settings['result_per_page_on_news'] ?? 20); ?>"
                            min="1"
                            class="small-text"
                        />
                        <p class="description">
                            <small>
                                <?php esc_html_e('Enter a number greater than 0. Default is 20.', 'payra-cash-crypto-payment'); ?>
                            </small>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <div class="payra-card">
            <h2><?php esc_html_e('Default Tab', 'payra-cash-crypto-payment'); ?></h2>
            <p class="description payra-cash-description-half">
                <?php esc_html_e('Choose which settings tab should be displayed first when you open the Payra Cash settings page.', 'payra-cash-crypto-payment'); ?>
            </p>
            <table class="form-table" id="default-tab-settings-table">
                <tr>
                    <th scope="row">
                        <label for="payra_default_tab"><?php esc_html_e('Default Settings Tab', 'payra-cash-crypto-payment'); ?></label>
                    </th>
                    <td>
                        <select name="payra_default_tab" id="payra_default_tab">
                            <option value="general" <?php selected($settings['payra_default_tab'] ?? '', 'general'); ?>>
                                <?php esc_html_e('General', 'payra-cash-crypto-payment'); ?>
                            </option>
                            <option value="transactions" <?php selected($settings['payra_default_tab'] ?? '', 'transactions'); ?>>
                                <?php esc_html_e('Transactions', 'payra-cash-crypto-payment'); ?>
                            </option>
                            <option value="news" <?php selected($settings['payra_default_tab'] ?? '', 'news'); ?>>
                                <?php esc_html_e('News', 'payra-cash-crypto-payment'); ?>
                            </option>
                            <option value="support" <?php selected($settings['payra_default_tab'] ?? '', 'support'); ?>>
                                <?php esc_html_e('Support', 'payra-cash-crypto-payment'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <?php submit_button(__('Save Settings', 'payra-cash-crypto-payment')); ?>
    </form>
    <div class="tablenav bottom">
        <?php include(PAYRACACR_CASH_PLUGIN_PATH . 'views/footer.php'); ?>
        <br class="clear">
    </div>
</div>
