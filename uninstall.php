<?php

if (!defined('WP_UNINSTALL_PLUGIN')) die();

global $wpdb;

$tables_to_drop = [
    $wpdb->prefix . 'wc_payracacr_stablecoins',
    $wpdb->prefix . 'wc_payracacr_news',
    $wpdb->prefix . 'wc_payracacr_settings',
    $wpdb->prefix . 'wc_payracacr_transactions',
    $wpdb->prefix . 'wc_payracacr_transaction_statuses',
    $wpdb->prefix . 'wc_payracacr_networks',
];

foreach ($tables_to_drop as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

flush_rewrite_rules();
