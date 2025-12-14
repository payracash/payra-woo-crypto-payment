<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

if (!defined('ABSPATH')) exit;

class Constants
{
    // === Statuses ===
    public const PENDING_STATUS  = 1;
    public const PAID_STATUS     = 2;
    public const EXPIRED_STATUS  = 3;
    public const REJECTED_STATUS = 4;
    public const ERROR_STATUS    = 5;

    // === General ===
    public const PLUGIN_WEBSITE_URL     = 'https://payra.cash';
    public const NEWS_ENDPOINT          = 'https://ipfs.io/ipns/k51qzi5uqu5dkvy9nvslenrk1h7hzojd9yefpe2tci4fhuu17sxgtp00s6f7gz/news/woocommerce-plugin-payra-cash.json';
    public const NEWS_ALL_URL           = 'https://dev.to/payracash';
    public const EXCHANGERATE_API_URL   = 'https://v6.exchangerate-api.com/v6';
    public const SETTINGS_PAGE_SLUG     = 'payracacr-page-settings';

    // === Dict ===
    public const DICT_SETTINGS_SYMBOL   = 'wc_payracacr_general_settings';

    // === Options ===
    public const NEWS_LAST_FETCH        = 'payracacr_news_last_fetch';
    public const LAST_NEWS_UPDATED_AT   = 'payracacr_last_news_updated_at';
    public const IS_UNREAD_NEWS         = 'payracacr_is_unread_news';
    public const SEARCH_RESULTS         = 'payracacr_search_results';
    public const EXCHANGE_RATES         = 'payracacr_exchange_rates_';
    public const CACHE_UPDATE           = 'payracacr_cache_update_';
    public const PLUGIN_VERSION         = 'payracacr_cash_plugin_version';
    public const INSTALLED_DB_VERSION   = 'payracacr_installed_db_version';

    public static $db_table_news;
    public static $db_table_settings;
    public static $db_table_networks;
    public static $db_table_stablecoins;
    public static $db_table_transactions;
    public static $db_table_transaction_statuses;

    protected $settings_table;
    protected $networks_table;
    protected $stablecoins_table;
    protected $transactions_table;
    protected $transaction_statuses_table;
    protected $news_table;

    public function __construct()
    {
        global $wpdb;

        self::$db_table_settings             = $wpdb->prefix . 'wc_payracacr_settings';
        self::$db_table_networks             = $wpdb->prefix . 'wc_payracacr_networks';
        self::$db_table_stablecoins          = $wpdb->prefix . 'wc_payracacr_stablecoins';
        self::$db_table_transactions         = $wpdb->prefix . 'wc_payracacr_transactions';
        self::$db_table_transaction_statuses = $wpdb->prefix . 'wc_payracacr_transaction_statuses';
        self::$db_table_news                 = $wpdb->prefix . 'wc_payracacr_news';

        // Escaped versions if needed
        $this->settings_table             = esc_sql(self::$db_table_settings);
        $this->networks_table             = esc_sql(self::$db_table_networks);
        $this->stablecoins_table          = esc_sql(self::$db_table_stablecoins);
        $this->transactions_table         = esc_sql(self::$db_table_transactions);
        $this->transaction_statuses_table = esc_sql(self::$db_table_transaction_statuses);
        $this->news_table                 = esc_sql(self::$db_table_news);
    }
    
}
