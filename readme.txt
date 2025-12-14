=== Payra Cash Crypto Payment ===
Contributors: xxxwraithxxx
Tags: payment, payment-gateway, WooCommerce, crypto, cryptocurrency
Requires at least: 4.7
Tested up to: 6.9
Stable tag: 1.0.2
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://juicebox.money/v5/eth:46

Payra Cash Crypto Payment For WooCommerce plugin allows your customers pay crypto with MetaMask wallet.

== Description ==
**Payra Cash** is a decentralized crypto payment gateway for WooCommerce.
It enables your customers to pay in cryptocurrency directly on your store, using their MetaMask or other Web3-compatible wallets.

With Payra Cash, payments are processed on-chain – safe, transparent, and fully under your control.
No third-party accounts or registrations required.  Fast, secure and trustless – no intermediaries, no hidden fees.

= Key Features: =
* Accept payments in cryptocurrency with MetaMask and Web3 wallets.
* Works fully on-chain – no intermediaries.
* Order status updates automatically after payment confirmation.
* If user closes the page, you can still verify transaction from WooCommerce admin panel.
* Built-in Cron transaction monitoring with pending/paid/cancelled status
* Simple and fast integration – no coding required.

= Benefits for Store Owners: =
* No chargebacks – blockchain transactions are irreversible.
* Secure direct payments on-chain.
* Payments go directly to your wallet address.
* Easy setup, no company registration required.
* Full control of your funds, withdraw at any time.

= Benefits for Customers: =
* Quick and easy checkout with MetaMask.
* Pay with crypto instantly.
* Transparent transactions on blockchain.
* No additional registration or accounts.

= Supported Networks =
1. Polygon Mainnet

== Screenshots ==
1. Payra Cash checkout with MetaMask
2. WooCommerce general settings for Payra Cash
3. Transaction list in WooCommerce admin
4. Order details with crypto transaction status

== Installation ==
1. Upload the **Payra Cash** plugin files to the `/wp-content/plugins/payra-cash` directory, or install via the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **WooCommerce <span aria-hidden="true" class="wp-exclude-emoji"><span aria-hidden="true" class="wp-exclude-emoji">→</span></span> Settings <span aria-hidden="true" class="wp-exclude-emoji"><span aria-hidden="true" class="wp-exclude-emoji">→</span></span> Payments** and enable **Payra Cash**.
4. Configure your wallet settings and supported network.
5. Done – your customers can now pay with crypto!

== Frequently Asked Questions ==

= Does Payra Cash require registration? =
No. Payra Cash is fully decentralized. You only need your wallet.

= How do I withdraw the crypto? =
Since payments are direct on-chain, you already have access to the funds in your wallet.

= What happens if customer closes the browser before transaction completes? =
You can always verify and update transaction status manually from WooCommerce admin panel.

== Changelog ==

= 1.0.2 - 12 Dec 2025 =
* Added new `fee_in_wei` column to the transactions database table.
* Transaction fee is now stored directly in the database.
* Database migration updated to handle the new fee field.

= 1.0.1 - 22 Nov 2025 =
* Updated branding and media assets.
* New logo aligned with the refreshed Payra identity.
* Updated plugin links — Payra is now a suite of products; links now direct to specific product pages.
* Added new media sources (Dev.to, YouTube).
* Improved plugin metadata for clarity and consistency.
* Minor UX refinements (docs link cleanup, sidebar adjustments, updated info pages).

= 1.0.0 – 12 Sept 2025 =
* Initial release of the Payra Cash Crypto Payment plugin for WooCommerce.
* Support for MetaMask and Web3 wallets.
* Transaction monitoring system (pending / paid / cancelled).
* Admin manual payment verification.

== Upgrade Notice ==
= 1.0.2 =
Added new fee_in_wei column to transactions table

== External services ==

This plugin connects to external services to perform its functionality.

1. **ExchangeRate API** – used to get real-time currency conversion rates.
   - Data sent: API key (from the plugin settings), request for current exchange rates.
   - Data received: current exchange rate values for supported currencies.
   - Terms of Service & Privacy Policy: https://www.exchangerate-api.com/terms

2. **IPFS (InterPlanetary File System)** – used to retrieve the latest plugin-related news and updates.
   - Data sent: none (only requests content from a public IPFS gateway).
   - Data received: JSON file with informational content.
   - Service URL: https://ipfs.io

3. **Blockchain network providers (via Ethers.js)** – used to interact with blockchain networks (e.g. Ethereum, Polygon, BNB Chain) to process crypto payments.
   - Data sent: blockchain transactions initiated by users (wallet address, amount, gas fee).
   - Data received: transaction confirmations and status updates.
   - Depending on user network settings, this may use public RPC endpoints or providers such as QuickNode, Infura, Alchemy, or public node URLs.
   - Terms of Service: [QuickNode](https://www.quicknode.com/terms)
   - Privacy Policy: [QuickNode](https://www.quicknode.com/privacy)

No personal user data is sent to these services by this plugin.
