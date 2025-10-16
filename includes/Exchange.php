<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

if (!defined('ABSPATH')) exit;

class Exchange extends Constants
{
    protected $api_key;
    protected $plan;

    public function __construct()
    {
        $settings_obj = new Settings;
        $settings = $settings_obj->get_data();

        $this->api_key = $settings['exchangerate_api_key'] ?? '';
        $this->plan = $settings['exchangerate_plan'] ?? 'free';
    }

    public function get_exchange_rate($from_currency, $to_currency = 'USD')
    {
        $rates = $this->get_rates();
        $from_currency = strtoupper($from_currency);
        $to_currency   = strtoupper($to_currency);

        if (!isset($rates[$from_currency]) || !isset($rates[$to_currency]))
            return null;

        if ($from_currency === 'USD') {
            return $rates[$to_currency];
        } elseif ($to_currency === 'USD') {
            return 1 / $rates[$from_currency];
        } else {
            return $rates[$to_currency] / $rates[$from_currency];
        }
    }

    protected function get_rates()
    {
        $transient_key = self::EXCHANGE_RATES . $this->plan;
        // read cache
        $rates = get_transient($transient_key);

        if ($rates !== false)
            return $rates;

        // no cache or expired, get from API
        $url = self::EXCHANGERATE_API_URL . "/{$this->api_key}/latest/USD";
        $response = wp_remote_get($url);

        if (is_wp_error($response))
            return [];

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['conversion_rates']))
            return [];

        $rates = $data['conversion_rates'];
        // set timelofe cache depends from plan
        $expiration = ($this->plan === 'paid') ? HOUR_IN_SECONDS : DAY_IN_SECONDS;
        // write to cache
        set_transient($transient_key, $rates, $expiration);

        return $rates;
    }

}
