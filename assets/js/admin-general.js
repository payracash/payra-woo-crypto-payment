document.addEventListener("DOMContentLoaded", function() {
    //const msgRemove = PayraCaCrAdmin.msgRemove;
    const msgCopied = PayraCaCrAdmin.msgCopied;

    // Copy contract stablecoin
    document.querySelectorAll(".copy-contract").forEach(function(el) {
        el.addEventListener("click", function() {
            const contract = el.getAttribute("data-contract");
            navigator.clipboard.writeText(contract).then(function() {
                const tooltip = el.querySelector(".payra-tooltip-text");
                const original = tooltip.innerText;
                tooltip.innerText = msgCopied;
                setTimeout(() => tooltip.innerText = original, 1000);
            });
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.getElementById('add-network-rpc');
    const container = document.getElementById('network-rpc-fields');
    const availableNetworks = PayraCaCrAdmin.availableNetworks || {};

    const options = Object.entries(availableNetworks)
        .map(([value, label]) => `<option value="${value}">${label}</option>`)
        .join('');

    addBtn.addEventListener('click', function() {
        const template = `
            <div class="network-rpc-field payra-cash-mt-6">
                <select name="network_rpcs_networks[]">${options}</select>
                <input type="text" name="network_rpcs_urls[]" class="regular-text" placeholder="https://" />
                <button type="button" class="button remove-network-rpc">Remove</button>
            </div>`;
        container.insertAdjacentHTML('beforeend', template);
    });

    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-network-rpc')) {
            e.target.closest('.network-rpc-field').remove();
        }
    });
});
