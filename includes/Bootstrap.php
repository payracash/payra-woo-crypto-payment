<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

if (!defined('ABSPATH')) exit;

class Bootstrap extends Constants
{
    public static function payracacr_activate()
    {
        global $wpdb;
        new self;

        if (!function_exists('maybe_create_table')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        $charset_collate  = $wpdb->get_charset_collate();

        // Tables
        $news_table                 = self::$db_table_news;
        $settings_table             = self::$db_table_settings;
        $networks_table             = self::$db_table_networks;
        $stablecoins_table          = self::$db_table_stablecoins;
        $transactions_table         = self::$db_table_transactions;
        $transaction_statuses_table = self::$db_table_transaction_statuses;

        // ===============================
        // Settings
        // ===============================
        maybe_create_table($settings_table, "
            CREATE TABLE {$settings_table} (
                id int(11) unsigned NOT NULL AUTO_INCREMENT,
                symbol VARCHAR(200) NOT NULL,
                settings LONGTEXT NULL,
                order_prefix VARCHAR(200) DEFAULT 'ord',
                result_per_page_on_transactions INT UNSIGNED DEFAULT 50,
                result_per_page_on_news INT UNSIGNED DEFAULT 20,
                tx_expiration_time INT UNSIGNED DEFAULT 30,
                payra_default_tab VARCHAR(50) DEFAULT 'transactions',
                cron_transaction_batch_size INT UNSIGNED DEFAULT 10,
                cron_transaction_sleep_seconds INT UNSIGNED DEFAULT 3,
                PRIMARY KEY (id),
                UNIQUE KEY (symbol)
            ) {$charset_collate};
        ");

        $exists = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$settings_table} WHERE symbol = %s", self::DICT_SETTINGS_SYMBOL)
        );

        if (!$exists) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert($settings_table, [
                'symbol' => self::DICT_SETTINGS_SYMBOL,
            ]);
        }

        // ===============================
        // Networks
        // ===============================
        maybe_create_table($networks_table, "
            CREATE TABLE {$networks_table} (
                id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                label VARCHAR(255) NOT NULL,
                name VARCHAR(100) NOT NULL,
                chain_id INT UNSIGNED NOT NULL,
                chain_id_hex VARCHAR(20) NOT NULL,
                rpc_url VARCHAR(500) NOT NULL,
                block_explorer_url VARCHAR(500) DEFAULT NULL,
                currency VARCHAR(50) NOT NULL,
                payra_contract_address VARCHAR(255) DEFAULT NULL,
                logo VARCHAR(500) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY (chain_id)
            ) {$charset_collate};
        ");

        $exists = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$networks_table} WHERE name = %s", 'polygon')
        );

        if (!$exists) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert($networks_table, [
                'label'                 => 'Polygon Mainnet',
                'name'                  => 'polygon',
                'chain_id'              => 137,
                'chain_id_hex'          => '0x89',
                'rpc_url'               => 'https://polygon-mainnet.infura.io',
                'block_explorer_url'    => 'https://polygonscan.com',
                'currency'              => 'POL',
                'payra_contract_address'=> '0xf30070da76B55E5cB5750517E4DECBD6Cc5ce5a8',
                'logo'                  => '/networks/polygon.webp',
                'description'           => 'A cost-effective and fast layer-2 solution compatible with Ethereum, perfect for dApps with high transaction volume.',
            ]);
        }

        // ===============================
        // Stablecoins
        // ===============================
        maybe_create_table($stablecoins_table, "
            CREATE TABLE {$stablecoins_table} (
                id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                network_id INT(11) UNSIGNED NOT NULL,
                symbol VARCHAR(20) NOT NULL,
                display_symbol VARCHAR(20) NOT NULL,
                contract_address VARCHAR(255) NOT NULL,
                decimals INT(11) NOT NULL DEFAULT 6,
                logo VARCHAR(500) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                FOREIGN KEY (network_id) REFERENCES $networks_table(id) ON DELETE CASCADE
            ) {$charset_collate};
        ");

        $polygon_id = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$networks_table} WHERE name = %s", 'polygon')
        );

        $stablecoins = [
            [
                'network_id'       => $polygon_id,
                'symbol'           => 'USDT0',
                'display_symbol'   => 'USDT',
                'contract_address' => '0xc2132D05D31c914a87C6611C10748AEb04B58e8F',
                'decimals'         => 6,
                'logo'             => '/tokens/polygon/usdt.webp',
                'description'      => 'Tether USD on Polygon',
            ],[
                'network_id'       => $polygon_id,
                'symbol'           => 'USDC',
                'display_symbol'   => 'USDC',
                'contract_address' => '0x3c499c542cEF5E3811e1192ce70d8cC03d5c3359',
                'decimals'         => 6,
                'logo'             => '/tokens/polygon/usdc.webp',
                'description'      => 'USD Coin on Polygon',
            ],
        ];

        foreach ($stablecoins as $coin) {
            $exists = $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$stablecoins_table} WHERE symbol = %s AND network_id = %d", $coin['symbol'], $polygon_id)
            );

            if (!$exists) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->insert($stablecoins_table, $coin);
            }
        }

        // ===============================
        // Transactions statuses
        // ===============================
        maybe_create_table($transaction_statuses_table, "
            CREATE TABLE {$transaction_statuses_table} (
                id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(50) NOT NULL,
                description VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (id)
            ) {$charset_collate};
        ");

        $statuses = [
            ['name' => 'PENDING', 'description' => 'Processing payment'],
            ['name' => 'PAID', 'description' => 'Payment confirmed'],
            ['name' => 'EXPIRED', 'description' => 'Payment expired'],
            ['name' => 'REJECTED', 'description' => 'Payment rejected'],
            ['name' => 'ERROR', 'description' => 'Payment error'],
        ];

        foreach ($statuses as $status) {
            $exists = $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$transaction_statuses_table} WHERE name = %s", $status['name'])
            );

            if (!$exists) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->insert($transaction_statuses_table, $status);
            }
        }

        // ===============================
        // Transactions
        // ===============================
        maybe_create_table($transactions_table, "
            CREATE TABLE {$transactions_table} (
                id int(11) unsigned NOT NULL AUTO_INCREMENT,
                order_id int(11) unsigned DEFAULT NULL,
                order_prefix_id varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                network_id INT(11) UNSIGNED NOT NULL,
                tx_hash varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                currency_symbol varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                sender varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                price DECIMAL(18,2) DEFAULT NULL,
                amount_in_wei BIGINT UNSIGNED NOT NULL,
                status_id INT(11) UNSIGNED DEFAULT 1,
                last_checked_at DATETIME DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                FOREIGN KEY (network_id) REFERENCES $networks_table(id) ON DELETE CASCADE,
                FOREIGN KEY (status_id) REFERENCES $transaction_statuses_table(id) ON DELETE RESTRICT,
                UNIQUE KEY unique_order_network (order_id, network_id),
                INDEX (network_id),
                INDEX (status_id)
            ) {$charset_collate};
        ");

        // ===============================
        // News
        // ===============================
        maybe_create_table($news_table, "
            CREATE TABLE {$news_table} (
                id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                id_key VARCHAR(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                title VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                url VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                excerpt TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                date_at DATE DEFAULT NULL,
                is_hidden TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY (id_key)
            ) {$charset_collate};
        ");

        // ===============================
        // Flush
        // ===============================
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }

        // ===============================
        // Cron setup
        // ===============================
        if (!wp_next_scheduled('payracacr_custom_cron_transactions')) {
            wp_schedule_event(time(), 'every_five_minutes', 'payracacr_custom_cron_transactions');
        }

        if (!wp_next_scheduled('payracacr_custom_cron_news')) {
            wp_schedule_event(time(), 'daily', 'payracacr_custom_cron_news');
        }

        // ===============================
        // Run migrations
        // ===============================
        Update::update_db();

        // ===============================
        // Set plugin version
        // ===============================
        update_option(self::PLUGIN_VERSION, PAYRACACR_CASH_PLUGIN_VERSION);
    }

    public static function payracacr_deactivate()
    {
        // ===============================
        // Cron cleanup
        // ===============================
        wp_clear_scheduled_hook('payracacr_custom_cron_transactions');
        wp_clear_scheduled_hook('payracacr_custom_cron_news');

        delete_option(self::NEWS_LAST_FETCH);
        delete_option(self::LAST_NEWS_UPDATED_AT);
        delete_option(self::IS_UNREAD_NEWS);
    }

}
