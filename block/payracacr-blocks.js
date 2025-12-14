(function() {
    if (
        window.wc?.wcBlocksRegistry?.registerPaymentMethod &&
        window.wp?.element &&
        window.wc?.wcSettings
    ) {
        const __ = window.wp.i18n.__;
        const settings = window.wc.wcSettings["payracacr_crypto_payment_data"] || {};
        const { createElement } = window.wp.element;

        const title = settings.title || __('Payra Cash Crypto Payment', 'payra-cash-crypto-payment');
        const ariaLabel = settings.ariaLabel || __('Payra Cash', 'payra-cash-crypto-payment');
        const description = settings.description || __('Pay with cryptocurrency On-Chain via Payra Cash.', 'payra-cash-crypto-payment');

        window.wc.wcBlocksRegistry.registerPaymentMethod({
        name: "payra_cash_crypto_payment",
        label: createElement(
            "span",
            { style: { display: "flex", alignItems: "center", gap: "8px" } },
            createElement("span", null, title || "Payra Cash Crypto Payment"),
            createElement("img", {
                src: settings.icon || "/wp-content/plugins/payra-cash-crypto-payment/assets/img/payracash-logo.png",
                alt: "Payra Cash",
                style: { maxWidth: "40px", height: "auto" },
            }),
        ),
        ariaLabel: ariaLabel || "Payra Cash",
        supports: {
            features: ["products", "default", "virtual"],
        },
        canMakePayment: () => Promise.resolve(true),
        content: createElement("p", null, description),
        edit: createElement("p", null, description),
        save: null,
        });
    }
})();
