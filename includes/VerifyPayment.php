<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

use Web3\Web3;
use Web3\Utils;
use Web3\Contract;
use Web3\Contracts\Ethabi;
use Web3\Providers\HttpProvider;

if (!defined('ABSPATH')) exit;

class VerifyPayment
{
    public function get_order_status($order_prefix_id, $merchant_id, $rpc_url, $payra_forward_contract_address)
    {
        try {
            $provider = new HttpProvider($rpc_url, 5);
            $web3 = new Web3($provider);
            $ethabi = new Ethabi;

            // Load ABI
            $payra_contract_abi = file_get_contents(dirname(__DIR__) . '/contracts/payraABI.json');
            $abi_array = json_decode($payra_contract_abi, true);

            // Find getOrderStatus
            $core_function = null;
            foreach ($abi_array as $entry) {
                if (isset($entry['type']) && $entry['type'] === 'function' && $entry['name'] === 'getOrderStatus') {
                    $core_function = $entry;
                    break;
                }
            }

            if (!$core_function) {
                throw new \Exception("Function getOrderStatus is not in ABI!");
            }

            // Search forward() in ABI
            $forward_function = null;
            foreach ($abi_array as $entry) {
                if (isset($entry['type']) && $entry['type'] === 'function' && $entry['name'] === 'forward') {
                    $forward_function = $entry;
                    break;
                }
            }

            if (!$forward_function) {
                throw new \Exception("Function forward is not in ABI!");
            }

            // 1. Calculate selector (4 byte keccak256)
            $input_types = array_column($core_function['inputs'], 'type');
            $signature = $core_function['name'] . '(' . implode(',', $input_types) . ')';
            $selector = substr(Utils::sha3($signature), 0, 10);

            // 2. Encode params
            $encoded_params = $ethabi->encodeParameters(
                $input_types,
                [$merchant_id, $order_prefix_id]
            );

            // 3. Build data for forward()
            $data = $selector . substr($encoded_params, 2);

            // 4. Create contract
            $forwarder = new Contract($web3->provider, [$forward_function]);
            $instance = $forwarder->at($payra_forward_contract_address);

            // 5. Call forward()
            $result_value = null;
            $done = false;
            $instance->call('forward', $data, function ($err, $result) use ($ethabi, $core_function, &$result_value, &$done) {
                if ($err !== null) {
                    $result_value = null;
                    $done = true;
                    return;
                }

                // 6. Decode output
                $output_types = ["bool", "address", "uint256", "uint256", "uint256"];
                $decoded = $ethabi->decodeParameters($output_types, $result[0]);
                $done = true;

                // Build return object
                $result_value = [
                    "paid" => !empty($decoded[0]) ? true : false,
                    "token" => $decoded[1],
                    "amount" => (int)$decoded[2]->toString(),
                    "fee" => (int)$decoded[3]->toString(),
                    "timestamp" => (int)$decoded[4]->toString(),
                ];
            });

            while (!$done) {
                usleep(1000);
            }

            return $result_value;

        } catch (\Throwable $e) {
            error_log("RPC URLS error: " . $e->getMessage());
            return $result_value = ["paid" => null];
        }
    }

}
