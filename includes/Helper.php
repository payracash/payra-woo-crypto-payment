<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

if (!defined('ABSPATH')) exit;

class Helper extends Constants
{
    public static function log($message)
    {
        if ((defined('WP_DEBUG') && WP_DEBUG) || (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG)) {
            if (is_array($message) || is_object($message)) {
                $message = print_r($message, true);
            }
            error_log('[PAYRA] ' . $message);
        }
    }

    public static function get_order_amount_usd($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return 0;
        }

        $amount = $order->get_total();
        $currency = $order->get_currency();
        $amount_usd = $amount;

        if ($currency !== 'USD') {
            $exchange_obj = new Exchange;
            $rate = $exchange_obj->get_exchange_rate($currency, 'USD');
            $amount_usd = round($amount * $rate, 2);
        }

        return $amount_usd;
    }

    public static function to_token_units($amount, $decimals)
    {
        $multiplier = bcpow('10', (string)$decimals, 0);
        return bcmul((string)$amount, $multiplier, 0);
    }

    public static function update_transaction($field, $value, $transactionId)
    {
        global $wpdb;

Helper::log('__#_$__$#__#_##_#_#_#_#_#_#_#__#_#_#_#_#_#_#_#_#_#_#_#_##_#_#_');
  Helper::log(Constants::$db_table_transactions);
  Helper::log($wpdb->last_error);
Helper::log('__#_$__$#__#_##_#_#_#_#_#_#_#__#_#_#_#_#_#_#_#_#_#_#_#_##_#_#_');


        $updated = $wpdb->update(
            Constants::$db_table_transactions,
            [ $field => $value ],       // field => new value
            [ 'id' => $transactionId ], // WHERE
            [ '%s' ],                   // format string
            [ '%d' ]                    // format ID
        );

        if ($updated === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                self::log("Failed to update transaction ID {$transactionId} → set {$field} = {$value}");
            }
            return false;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::log("Transaction ID {$transactionId} updated → set {$field} = {$value}");
        }

        return true;
    }

    public static function get_random_rpc_url(string $network): ?string
    {
        $settings_obj = new Settings();
        $settings = $settings_obj->get_data();

        $network_rpcs = $settings['network_rpcs_urls'][$network] ?? [];

        if (empty($network_rpcs)) {
            return null;
        }

        return $network_rpcs[array_rand($network_rpcs)];
    }

    public static function shorten_hash($hash, $start = 6, $end = 6)
    {
        return substr($hash, 0, $start) . '...' . substr($hash, -$end);
    }

}
