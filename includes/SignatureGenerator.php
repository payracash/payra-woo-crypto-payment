<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

use Elliptic\EC;
use Web3\Contracts\Ethabi;
use Web3\Utils;

if (!defined('ABSPATH')) exit;

class SignatureGenerator
{
    private EC $ec;
    private Ethabi $ethabi;
    private Utils $utils;

    public function __construct()
    {
        $this->ec = new EC('secp256k1');
        $this->ethabi = new Ethabi;
        $this->utils = new Utils;
    }

    public function generate_signature(
        string $merchant_id,
        string $signature_key,
        string $token_address,
        string $order_id,
        string $amount_in_wei,
        int $timestamp,
        string $payer_address
    ): string {

        $types = ['address', 'uint256', 'string', 'uint256', 'uint256', 'address'];
        $values = [$token_address, $merchant_id, $order_id, $amount_in_wei, $timestamp, $payer_address];

        $encoded = $this->ethabi->encodeParameters($types, $values);
        $messageHash = ltrim($this->utils::sha3($encoded), '0x');
        $prefixedMessage = "\x19Ethereum Signed Message:\n32" . hex2bin($messageHash);
        $finalHash = $this->utils::sha3($prefixedMessage);

        $key = $this->ec->keyFromPrivate($signature_key, 'hex');
        $signature = $key->sign($finalHash, ['canonical' => true]);

        $r = str_pad($signature->r->toString(16), 64, '0', STR_PAD_LEFT);
        $s = str_pad($signature->s->toString(16), 64, '0', STR_PAD_LEFT);
        $v = dechex($signature->recoveryParam + 27);

        return '0x' . $r . $s . $v;
    }

}
