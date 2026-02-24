import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';

const { registerPaymentMethod } = window.wc.wcBlocksRegistry
const { getSetting } = window.wc.wcSettings

const settings = getSetting('sequra_data', {})

// RequestController: Manages debounce timer, AbortController, and pending requests
const RequestController = (() => {
    let debounceTimer = null;
    let currentAbortController = null;
    let pendingRequests = new Map(); // requestId -> Promise
    const DEBOUNCE_MS = 400; // Configurable; 400ms is a good starting point

    return {
        get debounceTimer() { return debounceTimer; },
        set debounceTimer(value) { debounceTimer = value; },
        get currentAbortController() { return currentAbortController; },
        set currentAbortController(value) { currentAbortController = value; },
        get pendingRequests() { return pendingRequests; },
        get DEBOUNCE_MS() { return DEBOUNCE_MS; }
    };
})();

// Helper to build FormData for the AJAX request
const buildFormData = (shippingAddress, billingAddress, requestId) => {
    const data = new FormData();
    data.append('action', settings.blockContentAjaxAction);
    data.append('shippingAddress', JSON.stringify(shippingAddress));
    data.append('billingAddress', JSON.stringify(billingAddress));
    data.append('requestId', requestId);
    return data;
};
const label = decodeEntities(settings.title)

const Content = (props) => {
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup } = eventRegistration;

    const [content, setContent] = useState(settings.description || '');

    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            const checkedInput = document.querySelector('[name="sequra_payment_method_data"]:checked')
            const data = checkedInput ? checkedInput.value : null;

            if (!data) {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: __('Please select a payment method.', 'sequra'),
                };
            }
            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: { "sequra_payment_method_data": data },
                },
            };
        });
        return () => unsubscribe();
    }, [
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onPaymentSetup,
    ]);

    useEffect(() => {
        const onLoading = (event) => {
            setContent('<div class="sq-loader" style="margin-top:1rem;margin-bottom:1rem;"><span class="sqp-spinner"></span></div>');
        };
        const onReady = (event) => {
            setContent(event.detail.content);
            setTimeout(() => {
                if ('undefined' !== typeof Sequra && 'function' === typeof Sequra.refreshComponents) {
                    Sequra.refreshComponents();
                }
            }, 0);
        };

        document.addEventListener('canMakePaymentLoading', onLoading);
        document.addEventListener('canMakePaymentReady', onReady);
        
        if ('undefined' !== typeof Sequra && 'function' === typeof Sequra.refreshComponents && 'function' === typeof Sequra.onLoad) {
            Sequra.onLoad(Sequra.refreshComponents());
        }

        // Cleanup: remove event listeners on unmount
        return () => {
            document.removeEventListener('canMakePaymentLoading', onLoading);
            document.removeEventListener('canMakePaymentReady', onReady);
        };
    }, []);

    return <div dangerouslySetInnerHTML={{ __html: decodeEntities(content) }} />
}

const Label = () => {
    return (
        <span style={{ width: '100%' }}>
            {label}
        </span>
    )
}

registerPaymentMethod({
    // A unique string to identify the payment method client side.
    name: "sequra",
    // A react node that will be used as a label for the payment method in the checkout.
    label: <Label />,
    // A react node for your payment method UI.
    content: <Content />,
    // A react node to display a preview of your payment method in the editor.
    edit: <Content />,
    // A callback to determine whether the payment method should be shown in the checkout.
    canMakePayment: ({ shippingAddress, billingAddress, cart }) => {
        const isSolicitationAllowed = () => 'undefined' !== typeof SeQuraBlockIntegration && SeQuraBlockIntegration.isSolicitationAllowed;

        const initCache = () => {
            if ('undefined' === typeof SeQuraBlockIntegration.cache) {
                SeQuraBlockIntegration.cache = {};
            }
        };

        const readFromCache = (key) => {
            initCache();
            return SeQuraBlockIntegration.cache[key];
        };

        const writeToCache = (key, value) => {
            initCache();
            SeQuraBlockIntegration.cache[key] = value;
        };

        let latestResolve = null;

        return new Promise((resolve) => {
            const onResolved = (canMakePayment, detail) => {
                settings.description = detail.content;
                document.dispatchEvent(new CustomEvent('canMakePaymentReady', { detail }));
                if (latestResolve) {
                    latestResolve(canMakePayment);
                }
            }

            if (!isSolicitationAllowed()) {
                // Prevent unnecessary requests.
                onResolved(false, { content: '' });
                return;
            }

            // If there's a previous unresolved promise from a debounce that got cleared,
            // resolve it as false so WooCommerce doesn't hang.
            if (latestResolve) {
                latestResolve(false);
            }
            latestResolve = resolve;

            // Hash the data to prevent unnecessary requests.
            const requestId = btoa(encodeURIComponent(JSON.stringify({ shippingAddress, billingAddress, cart })));

            // 1. Dispatch loading event immediately (visual feedback).
            document.dispatchEvent(new CustomEvent('canMakePaymentLoading', { detail: { requestId } }));

            // 2. Cache hit -> resolve immediately.
            const cachedContent = readFromCache(requestId);
            if (cachedContent !== undefined) {
                onResolved('' !== cachedContent, { content: cachedContent });
                return;
            }

            // 3. If the exact same requestId is already pending, reuse its Promise.
            if (RequestController.pendingRequests.has(requestId)) {
                RequestController.pendingRequests.get(requestId).then(({ canMake, detail }) => {
                    onResolved(canMake, detail);
                });
                return;
            }

            // 4. Debounce: clear any queued timer and abort any in-flight fetch.
            clearTimeout(RequestController.debounceTimer);
            if (RequestController.currentAbortController) {
                RequestController.currentAbortController.abort();
            }

            RequestController.debounceTimer = setTimeout(() => {
                const abortController = new AbortController();
                RequestController.currentAbortController = abortController;

                const fetchPromise = fetch(settings.blockContentUrl, {
                    method: 'POST',
                    credentials: 'same-origin', // Allow including cookies in the request.
                    body: buildFormData(shippingAddress, billingAddress, requestId),
                    signal: abortController.signal,
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(json => {
                    if (!json.requestId) {
                        // invalidate the request.
                        throw new Error('Request ID is missing');
                    }

                    writeToCache(json.requestId, json.content);
                    const canMake = '' !== json.content;
                    const detail = { content: json.content };
                    onResolved(canMake, detail);
                    return { canMake, detail };
                })
                .catch((err) => {
                    if (err.name !== 'AbortError') {
                        onResolved(false, { content: '' });
                    }
                    return { canMake: false, detail: { content: '' } };
                })
                .finally(() => {
                    RequestController.pendingRequests.delete(requestId);
                });

                RequestController.pendingRequests.set(requestId, fetchPromise);
            }, RequestController.DEBOUNCE_MS);
        });
    },
    ariaLabel: label,
    // SupportsConfiguration Object that describes various features provided by the payment method.
    supports: {
        features: settings.supports,
    }
})