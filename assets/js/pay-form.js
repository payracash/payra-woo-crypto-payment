jQuery(function($) {
    const chooseOptionLabel     = PayraCaCrForm.chooseOptionLabel;
    const msgMetaMaskNotFound   = PayraCaCrForm.msgMetaMaskNotFound;
    const msgAddToWalletError   = PayraCaCrForm.msgAddToWalletError;
    const msgWalletConnectError = PayraCaCrForm.msgWalletConnectError;
    const msgCheckStatus        = PayraCaCrForm.msgCheckStatus;
    const msgOrderPaidRedirect  = PayraCaCrForm.msgOrderPaidRedirect;
    const msgOrderPending       = PayraCaCrForm.msgOrderPending;
    const msgErrorCheckStatus   = PayraCaCrForm.msgErrorCheckStatus;
    const msgProcessing         = PayraCaCrForm.msgProcessing;
    const msgInvalidOrderStatus = PayraCaCrForm.msgInvalidOrderStatus;
    const msgAjaxError          = PayraCaCrForm.msgAjaxError;
    const msgNetworkError       = PayraCaCrForm.msgNetworkError;
    const msgApproveToken       = PayraCaCrForm.msgApproveToken;
    const msgCancelByUser       = PayraCaCrForm.msgCancelByUser;
    const msgUnsupportedError   = PayraCaCrForm.msgUnsupportedError;
    const msgUnknownError       = PayraCaCrForm.msgUnknownError;
    const msgStep2Processing    = PayraCaCrForm.msgStep2Processing;
    const msgProcessingPayment  = PayraCaCrForm.msgProcessingPayment;
    const msgRedirectFailed     = PayraCaCrForm.msgRedirectFailed;
    const msgCantGetLog         = PayraCaCrForm.msgCantGetLog;
    const msgSignatureFailed    = PayraCaCrForm.msgSignatureFailed;
    const msgChangingNetwork    = PayraCaCrForm.msgChangingNetwork;
    const msgAddedAndSwitched   = PayraCaCrForm.msgAddedAndSwitched;
    const msgCouldNotAddNetwork = PayraCaCrForm.msgCouldNotAddNetwork;
    const msgChainChanged       = PayraCaCrForm.msgChainChanged;
    const msgAccountsChanged    = PayraCaCrForm.msgAccountsChanged;
    const tokensByNetwork       = PayraCaCrForm.tokensByNetwork;
    const orderId               = PayraCaCrForm?.orderId || null;
    const payraAmount           = PayraCaCrForm?.payraAmount || null;
    const payraConfig           = PayraCaCrLocalize?.payraConfig || {};
    const ajaxUrl               = PayraCaCrLocalize.ajaxUrl;
    const ajaxNonce             = PayraCaCrLocalize.nonce;

    let userAddress = null;
    let provider = null;
    let signer = null;
    let network = null;

    function togglePayButton() {
        const networkId = $('#payra-network').val();
        const tokenId = $('#payra-currency').val();

        if (networkId && tokenId) {
            $('#payra-metamask-btn').prop('disabled', false);
        } else {
            $('#payra-metamask-btn').prop('disabled', true);
        }
    }

    $('#payra-network').on('change', function() {
        const networkId = this.value;
        const tokenSelect = document.getElementById('payra-currency');
        tokenSelect.innerHTML = `<option value="">${chooseOptionLabel}</option>`;

        if (tokensByNetwork[networkId]) {
            tokensByNetwork[networkId].forEach(token => {
                const opt = document.createElement('option');
                opt.value = token.id;
                opt.textContent = token.display_symbol;
                opt.dataset.symbol = token.symbol;
                tokenSelect.appendChild(opt);
            });
        }

        togglePayButton();
    });

    $('#payra-network, #payra-currency').select2({
        minimumResultsForSearch: Infinity,
        width: '100%'
    });

    // Token select change, show info, add to wallet
    $('#payra-currency').on('change', function () {
        const networkId = $('#payra-network').val();
        const tokenId = this.value;
        const token = tokensByNetwork[networkId]?.find(t => t.id === tokenId);

        if (token) {
            $('#payra-token-info').css('display','flex');
            $('#token-address-text').text(token.contract_address);
            $('#add-to-wallet').off('click').on('click', async (e) => {
                e.preventDefault();
                if (!window.ethereum) {
                    toastr.error(msgMetaMaskNotFound);
                    return;
                }

                try {
                    await window.ethereum.request({
                        method: 'wallet_watchAsset',
                        params: {
                            type: 'ERC20',
                            options: {
                                address: token.contract_address,
                                symbol: token.symbol,
                                decimals: token.decimals ?? 18,
                                image: token.logo ?? ''
                            },
                        },
                    });
                } catch (err) {
                    toastr.error(msgAddToWalletError);
                    console.error(msgAddToWalletError, err);
                }
            });
        } else {
            $('#payra-token-info').hide();
        }

        togglePayButton();
    });

    async function connectWallet() {
        if (typeof window.ethereum === 'undefined') {
            toastr.error(msgAddToWalletError);
            return null;
        }

        try {
            const provider = new ethers.providers.Web3Provider(window.ethereum);
            await provider.send("eth_requestAccounts", []); // popup MetaMask
            const signer = provider.getSigner();
            const address = await signer.getAddress();
            userAddress = address;
            return address;
        } catch (err) {
            toastr.error(msgWalletConnectError);
            console.error(msgWalletConnectError, err);
            return null;
        }
    }

    async function initProvider() {
        provider = new ethers.providers.Web3Provider(window.ethereum, "any");
        signer = provider.getSigner();
        network = await provider.getNetwork();
    }

    $('#payra-check-btn').on('click', async function (e) {
        e.preventDefault();

        const btn = $(this);
        const orderId = btn.data('check-order-id');

        setButtonLoading(btn, true, msgCheckStatus);

        try {
          const resp = await getOrderStatus(orderId);
          if (resp.success && resp.data.status === 'paid') {
              statusMessage(msgOrderPaidRedirect, "success");
              setButtonLoading(btn, false, msgCheckStatus);
              setTimeout(() => {
                  window.location.href = resp.data.redirect_url;
              }, 2000);
          } else {
              toastr.info(msgOrderPending);
              setButtonLoading(btn, false, msgCheckStatus);
          }
        } catch (error) {
            console.error(error);
            toastr.error(msgErrorCheckStatus);
            setButtonLoading(btn, false, msgCheckStatus);
        }
    });

    $('#payra-metamask-btn').on('click', async function (e) {
        e.preventDefault();

        const btn = $(this);
        setButtonLoading(btn, true, msgProcessing);

        // Check order statu
        try {
            const orderResp = await $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'payra_cash_check_order_status',
                    order_id: orderId,
                    _ajax_nonce: PayraCaCrLocalize.nonce,
                },
                dataType: 'json'
            });

            let status = (orderResp.data.order_status || "").toLowerCase();
            if (status && status !== "pending") {
                toastr.error(msgInvalidOrderStatus + " " + status);
                setButtonLoading(btn, false, "Pay with MetaMask");
                return;
            }

        } catch (e) {
            console.error(msgAjaxError, e);
            setButtonLoading(btn, false, "Pay with MetaMask");
        }

        let networkId = $('#payra-network').val();
        let currencyId = $('#payra-currency').val();

        if (!networkId || !currencyId) {
            toastr.warning('Please choose network and currency first!');
            setButtonLoading(btn, false, msgProcessing);
            return;
        }

        if (!userAddress) {
            let addr = await connectWallet();
            if (!addr) return;
        }

        await initProvider();

        let networkData = null;
        try {
            const resp = await $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'payra_cash_get_network_data',
                    network_id: networkId,
                    _ajax_nonce: PayraCaCrLocalize.nonce,
                },
                dataType: 'json'
            });

            if (resp.success) {
                networkData = resp.data;
            } else {
                console.error(msgNetworkError, resp.data.message);
            }
        } catch (e) {
            console.error(msgAjaxError, e);
        }

        if (!networkData) return;

        // Check current network and switch if needed
        if (networkData.chain_id !== network.chainId.toString()) {
            const switched = await switchNetwork(networkData);
            if (!switched) {
                setButtonLoading(btn, false, "Pay with MetaMask");
                return;
            }
            
            initProvider();
        }

        const forwarder = new ethers.Contract(networkData.payra_contract_address, payraConfig.payra_abi, signer);
        const payraCoreAddress = await forwarder.payraCore();
        const payraContract = new ethers.Contract(payraCoreAddress, payraConfig.payra_abi, signer);

        const tokenAddress = tokensByNetwork[networkId]?.find(t => t.id === currencyId)?.contract_address || null;
        if (!tokenAddress) return;

        const erc20Contract = new ethers.Contract(tokenAddress, payraConfig.erc20_abi, signer);
        const decimals = await erc20Contract.decimals();
        const amountInWei = ethers.utils.parseUnits(payraAmount.toString(), decimals);

        let step = 1;
        let totalSteps = 1;
        const currentAllowance = await erc20Contract.allowance(userAddress, payraCoreAddress);

        if (currentAllowance.lt(amountInWei)) {
            totalSteps = 2;
            statusMessage(msgApproveToken, "info");
            try {
                const approveTx = await erc20Contract.approve(payraCoreAddress, amountInWei);
                const receipt = await approveTx.wait();
            } catch (err) {
                if (err.code === 'ACTION_REJECTED') {
                    statusMessage(msgCancelByUser, "error");
                } else if (err.code) {
                    statusMessage(msgUnsupportedError + " " + err.code, "error");
                } else {
                    statusMessage(msgUnknownError, "error");
                }

                setButtonLoading(btn, false, "Pay with MetaMask");
                return;
            }
            step++;
        }

        if (totalSteps === 2) {
            statusMessage(msgStep2Processing, "info");
        } else {
            statusMessage(msgProcessingPayment, "info");
        }

        try {
            const resp = await $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'payra_cash_create_signature',
                    order_id: orderId,
                    network_id: networkId,
                    token_id: currencyId,
                    payer_wallet_address: userAddress,
                    _ajax_nonce: PayraCaCrLocalize.nonce,
                },
                dataType: 'json'
            });

            if (resp.success) {

              const data = payraContract.interface.encodeFunctionData("payOrder", [
                  tokenAddress,
                  ethers.BigNumber.from(resp.data.merchant_id),
                  resp.data.order_id,
                  ethers.BigNumber.from(resp.data.amount_in_wei),
                  ethers.BigNumber.from(resp.data.timestamp),
                  userAddress,
                  resp.data.signature
              ]);

              // Send transaction through forwarder
              const tx = await forwarder.forward(data, { value: 0 });

              try {
                  const updateTransaction = await $.ajax({
                      url: ajaxUrl,
                      type: 'POST',
                      data: {
                          action: 'payra_cash_update_transaction',
                          order_id: orderId,
                          tx_hash: tx.hash,
                          _ajax_nonce: PayraCaCrLocalize.nonce,
                      },
                      dataType: 'json'
                  });
              } catch (e) {
                  console.error(msgAjaxError, e);
              }

              const receipt = await tx.wait();
              const payraCoreIface = new ethers.utils.Interface(payraConfig.payra_abi);

              for (const log of receipt.logs) {
                  if (log.address.toLowerCase() === payraCoreAddress.toLowerCase()) {
                      try {
                          const parsed = payraCoreIface.parseLog(log);
                          if (parsed.name === "OrderPaid") {
                              statusMessage(msgOrderPaidRedirect, "success");
                              setTimeout(async () => {
                                  const resp = await getOrderStatus(orderId);
                                  if (resp.success && resp.data.status === 'paid') {
                                      window.location.href = resp.data.redirect_url;
                                  } else {
                                      statusMessage(msgRedirectFailed, "warning");
                                  }
                            }, 1000);
                          }
                      } catch (e) {
                          statusMessage(msgCantGetLog, "error");
                          document.getElementById("payra-pay-container").style.display = "none";
                          document.getElementById("payra-check-container").style.display = "";
                      }
                  }
              }
            } else {
                statusMessage(msgSignatureFailed, "error");
                setButtonLoading(btn, false, "Pay with MetaMask");
            }
        } catch (err) {
            if (err.code === 'ACTION_REJECTED') {
                statusMessage(msgCancelByUser, "error");
            } else if (err.code === 'UNPREDICTABLE_GAS_LIMIT') {
                statusMessage(msgCantGetLog, "error");
                document.getElementById("payra-pay-container").style.display = "none";
                document.getElementById("payra-check-container").style.display = "";
            } else if (err.code) {
                statusMessage(msgUnsupportedError + " " + err.code, "error");
            } else {
                statusMessage(msgUnknownError, "error");
            }

            setButtonLoading(btn, false, "Pay with MetaMask");
        }
    });

    async function switchNetwork(networkData) {
        if (!window.ethereum) {
            toastr.error(msgAddToWalletError);
            return false;
        }

        statusMessage(msgChangingNetwork, "warning");

        const targetChainIdHex = networkData.chain_id_hex;
        try {
            // try switch network
            await window.ethereum.request({
              method: "wallet_switchEthereumChain",
              params: [{ chainId: targetChainIdHex }],
            });
            return true;
        } catch (switchError) {
            // if network not in mm
            if (switchError.code === 4902) {
                try {
                    statusMessage(`Adding network to your wallet ${networkData.label}`, "warning");
                    await window.ethereum.request({
                        method: "wallet_addEthereumChain",
                        params: [{
                            chainId: targetChainIdHex,
                            chainName: networkData.name,
                            nativeCurrency: {
                                name: networkData.currency,
                                symbol: networkData.currency,
                                decimals: 18,
                            },
                            rpcUrls: [networkData.rpc_url],
                            blockExplorerUrls: [networkData.block_explorer_url],
                        }],
                    });
                    statusMessage(msgAddedAndSwitched + " " + networkData.label, "success");
                    return true;
                } catch (addError) {
                    statusMessage(msgCouldNotAddNetwork, "error");
                    setPayButtonLoading(false);
                    return false;
                }
            } else {
              statusMessage(msgCouldNotAddNetwork, "error");
              setPayButtonLoading(false);
              return false;
            }
        }
    }

    if (window.ethereum) {
        window.ethereum.on('chainChanged', () => {
            console.log(msgChainChanged);
            initProvider();
        });

        window.ethereum.on('accountsChanged', async (accounts) => {
            console.log(msgAccountsChanged, accounts);
            userAddress = accounts.length ? accounts[0] : null;
        });
    }

    function setButtonLoading(btn, isLoading, defaultText) {
        const text = btn.find('.btn-text');
        const spinner = btn.find('.btn-spinner');

        if (isLoading) {
            btn.prop('disabled', true);
            text.text(defaultText);
            spinner.show();
        } else {
            btn.prop('disabled', false);
            text.text(defaultText);
            spinner.hide();
        }
    }
});
