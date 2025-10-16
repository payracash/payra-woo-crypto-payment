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
        add_action('upgrader_process_complete', [$this, 'payracacr_action_after_update'], 10, 2);
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
                set_transient(self::CACHE_UPDATE, $remote, 5 /*DAY_IN_SECONDS*/);
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
            //$res->active_installs = $decoded_remote->active_installs;
            //$res->rating = $decoded_remote->rating;
            //$res->ratings = $decoded_remote->ratings;
            //$res->num_ratings = $decoded_remote->num_ratings;

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
                set_transient(self::CACHE_UPDATE, $remote, 5 /*DAY_IN_SECONDS*/);
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

    function payracacr_action_after_update($upgrader_object, $options)
    {
        if ($options['action'] == 'update' && $options['type'] === 'plugin') {

            // Your plugin's TextDomain, for additional verification
            $your_plugin_text_domain = 'payra-cash-crypto-payment';
            $is_our_plugin_updated = false;
            $new_version = '';

            // ATTEMPT 1: Retrieve information about the new plugin directly from $upgrader_object
            if (isset($upgrader_object->new_plugin_data) && is_array($upgrader_object->new_plugin_data)) {
                if (isset($upgrader_object->new_plugin_data['TextDomain']) && $upgrader_object->new_plugin_data['TextDomain'] === $your_plugin_text_domain) {
                    $is_our_plugin_updated = true;
                    if (isset($upgrader_object->new_plugin_data['Version'])) {
                        $new_version = $upgrader_object->new_plugin_data['Version'];
                    }
                }
            }

            // ATTEMPT 2: If the first attempt didn’t identify the plugin, check skin->plugin_info
            if (!$is_our_plugin_updated && isset($upgrader_object->skin->plugin_info) && is_array($upgrader_object->skin->plugin_info)) {
                if (isset($upgrader_object->skin->plugin_info['TextDomain']) && $upgrader_object->skin->plugin_info['TextDomain'] === $your_plugin_text_domain) {
                    $is_our_plugin_updated = true;
                    if (isset($upgrader_object->skin->plugin_info['Version'])) {
                        $new_version = $upgrader_object->skin->plugin_info['Version'];
                    }
                }
            }

            // ATTEMPT 3: Check $options['plugins'] for bulk updates or when the above doesn’t work
            // In this case, we won’t get the TextDomain right away, only the slug, so we need to assume it’s our plugin
            // and only then, if necessary, use get_plugin_data() for the version if we don’t have it from other sources.
            if (!$is_our_plugin_updated && isset($options['plugins']) && is_array($options['plugins']) && in_array(PAYRACACR_CASH_PLUGIN_SLUG, $options['plugins'])) {
                 $is_our_plugin_updated = true;
                 // If we reached this point and new_version is still empty, try to fetch it using get_plugin_data()
                 // as a last resort, since we don’t have the parsed headers directly.
                 if (empty($new_version)) {
                     if (!function_exists('get_plugin_data')) {
                         require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                     }
                     $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . PAYRACACR_CASH_PLUGIN_SLUG);
                     if (isset($plugin_data['Version'])) {
                         $new_version = $plugin_data['Version'];
                     }
                 }
            }

            if ($is_our_plugin_updated) {
                delete_transient(self::CACHE_UPDATE);
                if (!empty($new_version)) {
                    update_option(Constants::NEEDS_UPDATE, true);
                    update_option(Constants::PLUGIN_VERSION, $new_version);
                } else {
                    Helper::log('Error: Payra Cash Crypto Payment has been updated, but the new version could not be retrieved.');
                }
            }
  	    }
    }

    public static function update_db()
    {
        global $wpdb;
    }

}
