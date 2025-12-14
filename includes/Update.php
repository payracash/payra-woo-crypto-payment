<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

if (!defined('ABSPATH')) exit;

use \stdClass;

class Update extends Constants
{
    const PLUGIN_IPFS_URL = 'https://ipfs.io/ipns/k51qzi5uqu5dkvy9nvslenrk1h7hzojd9yefpe2tci4fhuu17sxgtp00s6f7gz';

    public function register()
    {
        add_filter('plugins_api', [$this, 'payracacr_filter_get_info'], 10, 3);
        add_filter('site_transient_update_plugins', [$this, 'payracacr_filter_push_update']);
    }

    public function payracacr_filter_get_info($res, $action, $args)
    {
        if ('plugin_information' !== $action) {
            return false;
        }

        if (dirname(PAYRACACR_CASH_PLUGIN_SLUG) !== $args->slug) {
            return false;
        }

        $remote = get_transient(self::CACHE_UPDATE);

        if ($remote === false) {
            $response = wp_remote_get(
                self::PLUGIN_IPFS_URL . '/woo/plugins/payra-cash-crypto-payment/wp-payra-cash-crypto-payment-update.json', [
                    'timeout' => 10,
                    'cache' => false,
                ]
            );

            if (is_wp_error($response)) {
                return false;
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);

            if ($code === 200 && !empty($body)) {
                $remote = $body;
                set_transient(self::CACHE_UPDATE, $remote, DAY_IN_SECONDS);
            } else {
                return false;
            }
        }

        $decoded_remote = json_decode($remote);

        if ($decoded_remote) {
            $res = new stdClass();
            $res->name = $decoded_remote->name;
            $res->slug = dirname(PAYRACACR_CASH_PLUGIN_SLUG);
            $res->version = $decoded_remote->version;
            $res->tested = $decoded_remote->tested;
            $res->requires = $decoded_remote->requires;
            $res->author = $decoded_remote->author;
            $res->author_profile = $decoded_remote->author_profile;
            $res->download_link = $decoded_remote->download_url;
            $res->trunk = $decoded_remote->download_url;
            $res->requires_php = $decoded_remote->requires_php;
            $res->last_updated = $decoded_remote->last_updated;
            $res->homepage = $decoded_remote->homepage;
            $res->donate_link = $decoded_remote->donate_link;
            $res->active_installs = $decoded_remote->active_installs;
            $res->rating = $decoded_remote->rating;
            $res->ratings = $decoded_remote->ratings;
            $res->num_ratings = $decoded_remote->num_ratings;

            $res->sections = [
                'description' => $decoded_remote->sections->description,
                'installation' => $decoded_remote->sections->installation,
                'changelog' => $decoded_remote->sections->changelog,
                'faq' => $decoded_remote->sections->faq,
                'screenshots' => $decoded_remote->sections->screenshots,
            ];

            $res->banners = [
                'low'  => $decoded_remote->banners->low ?? '',
                'high' => $decoded_remote->banners->high ?? '',
            ];

            return $res;
        }

        error_log('PayraCash: update JSON not valid.');
        return false;
    }

    function payracacr_filter_push_update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote = get_transient(self::CACHE_UPDATE);

        if ($remote === false) {
            $response = wp_remote_get(
                self::PLUGIN_IPFS_URL . '/woo/plugins/payra-cash-crypto-payment/wp-payra-cash-crypto-payment-update.json', [
                    'timeout' => 10,
                    'cache' => false,
                ]
            );

            if (is_wp_error($response)) {
                return $transient;
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);

            if ($code == 200 && !empty($body)) {
                $remote = $body;
                set_transient(self::CACHE_UPDATE, $remote, DAY_IN_SECONDS);
            } else {
                return $transient;
            }
        }

        $decoded_remote = json_decode($remote);

        if ($decoded_remote && version_compare(PAYRACACR_CASH_PLUGIN_VERSION, $decoded_remote->version, '<')) {
            $res = new stdClass();
            $res->slug = dirname(PAYRACACR_CASH_PLUGIN_SLUG);
            $res->plugin = PAYRACACR_CASH_PLUGIN_SLUG;
            $res->new_version = $decoded_remote->version;
            $res->tested = $decoded_remote->tested;
            $res->package = $decoded_remote->download_url;
            $transient->response[$res->plugin] = $res;
        }

        return $transient;
    }

    public static function update_db()
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $installed_db_version = get_option(Constants::INSTALLED_DB_VERSION, 1);

        // Miration to v2
        if (version_compare($installed_db_version, '2', '<')) {
            if (empty(Constants::$db_table_transactions)) {
                new Constants();
            }

            $transactions_table = Constants::$db_table_transactions;

            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SHOW COLUMNS FROM {$transactions_table} LIKE %s",
                    'fee_in_wei'
                )
            );

            if (empty($column_exists)) {
                $wpdb->query("
                    ALTER TABLE {$transactions_table}
                    ADD COLUMN fee_in_wei BIGINT UNSIGNED NOT NULL AFTER amount_in_wei
                ");
            }

            update_option(Constants::INSTALLED_DB_VERSION, '2');
        }
    }

}
