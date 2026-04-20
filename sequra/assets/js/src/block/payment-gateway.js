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
                || document.querySelector('[name="sequra_payment_method_data"][type="hidden"]');
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

const isDelegatedSelection = () => 'undefined' !== typeof SeQuraBlockIntegration && SeQuraBlockIntegration.isDelegatedSelection;

const Label = () => {
    if (isDelegatedSelection()) {
        return (
            <span style={{ width: '100%', display: 'inline-flex', alignItems: 'center', justifyContent: 'space-between', gap: '0.5em' }}>
                <span>{label}</span>
                <img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4NCjxzdmcgaWQ9IkNhcGFfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiBoZWlnaHQ9IjQwIiB3aWR0aD0iOTIiIHZpZXdCb3g9IjAgMCAxMjkgNTYiPg0KICA8ZGVmcz4NCiAgICA8c3R5bGU+DQogICAgICAuY2xzLTEgew0KICAgICAgICBmaWxsOiAjMDBjMmEzOw0KICAgICAgfQ0KICAgICAgLmNscy0yIHsNCiAgICAgICAgZmlsbDogI2ZmZjsNCiAgICAgICAgZmlsbC1ydWxlOiBldmVub2RkOw0KICAgICAgfQ0KICAgIDwvc3R5bGU+DQogIDwvZGVmcz4NCiAgPHJlY3QgY2xhc3M9ImNscy0xIiB3aWR0aD0iMTI5IiBoZWlnaHQ9IjU2IiByeD0iOC4yIiByeT0iOC4yIi8+DQogIDxnPg0KICAgIDxwYXRoIGNsYXNzPSJjbHMtMiIgZD0iTTY5LjMsMzYuNDVjLS42Ny0uMDItMS4zNi0uMTMtMi4wNS0uMzIsMS4yOS0xLjU1LDIuMjEtMy40MSwyLjY1LTUuNDEsLjY1LTIuOTYsLjIyLTYuMDYtMS4yMS04LjczLTEuNDMtMi42Ny0zLjc4LTQuNzYtNi42MS01Ljg3LTEuNTItLjYtMy4xMi0uOS00Ljc2LS45LTEuNCwwLTIuNzgsLjIyLTQuMSwuNjctMi44OCwuOTctNS4zMywyLjkzLTYuOSw1LjUzLTEuNTcsMi41OS0yLjE2LDUuNjctMS42Nyw4LjY1LC40OSwyLjk4LDIuMDQsNS43LDQuMzYsNy42NywyLjIzLDEuODgsNS4wNywyLjk1LDguMDIsMy4wMywuMzIsMCwuNjEtLjEzLC44My0uMzgsLjE2LS4xOSwuMjUtLjQ1LC4yNS0uNzJ2LTIuMTJjMC0uNi0uNDctMS4wNy0xLjA3LTEuMDktMS45My0uMDctMy43OC0uNzktNS4yMy0yLjAxLTEuNTQtMS4zLTIuNTctMy4xLTIuODktNS4wNy0uMzItMS45NywuMDctNC4wMSwxLjEtNS43MiwxLjA0LTEuNzIsMi42Ny0zLjAyLDQuNTgtMy42NSwuODgtLjMsMS43OS0uNDUsMi43My0uNDUsMS4wOCwwLDIuMTQsLjIsMy4xNSwuNiwxLjg5LC43NCwzLjQ0LDIuMTIsNC4zNywzLjg4LC45NSwxLjc3LDEuMjMsMy44MiwuOCw1Ljc3LS4zMywxLjUyLTEuMDksMi45My0yLjIsNC4wNy0uNzMtLjc1LTEuMzItMS42My0xLjc1LTIuNjQtLjQtLjk0LS42MS0xLjkzLS42NS0yLjk1LS4wMi0uNi0uNS0xLjA3LTEuMDktMS4wN2gtMi4xM2MtLjI4LDAtLjU1LC4xLS43MywuMjYtLjI0LC4yMS0uMzgsLjUxLS4zOCwuODQsLjA0LDEuNTcsLjM3LDMuMSwuOTgsNC41NiwuNjUsMS41NSwxLjU4LDIuOTQsMi43OCw0LjE0LDEuMiwxLjE5LDIuNiwyLjEyLDQuMTcsMi43NywxLjQ3LC42MSwzLjAxLC45Myw0LjU5LC45N2guMDJjLjMyLDAsLjYyLS4xNCwuODMtLjM4LC4xNi0uMTksLjI1LS40NSwuMjUtLjcydi0yLjFjLjAyLS4yOS0uMDgtLjU2LS4yOC0uNzctLjItLjIxLS40OC0uMzQtLjc3LS4zNVoiLz4NCiAgICA8cGF0aCBjbGFzcz0iY2xzLTIiIGQ9Ik0yMS4xNCwyOC45N2MtLjYzLS40NS0xLjMtLjc5LTEuOTktMS4wMi0uNzUtLjI2LTEuNTItLjUtMi4zMS0uNjktLjU3LS4xMi0xLjExLS4yNi0xLjYtLjQyLS40Ny0uMTQtLjkxLS4zMi0xLjMtLjUzbC0uMDgtLjAzYy0uMTUtLjA3LS4yNy0uMTUtLjM5LS4yMy0uMS0uMDctLjItLjE2LS4yNi0uMjItLjA1LS4wNS0uMDgtLjEtLjA4LS4xMi0uMDItLjA2LS4wMi0uMTEtLjAyLS4xNiwwLS4xNSwuMDctLjMsLjE5LS40MywuMTUtLjE3LC4zNS0uMywuNi0uNDEsLjI3LS4xMiwuNTgtLjIsLjk0LS4yNSwuMjQtLjAzLC40OC0uMDUsLjczLS4wNSwuMTIsMCwuMjQsMCwuNCwuMDIsLjU4LC4wMiwxLjE0LC4xNiwxLjYxLC40MSwuMzEsLjE1LDEuMDEsLjczLDEuNTYsMS4yMiwuMTQsLjEyLC4zMiwuMTksLjUxLC4xOSwuMTYsMCwuMzEtLjA1LC40My0uMTNsMi4yMi0xLjUyYy4xNy0uMTIsLjI5LS4zLC4zMy0uNSwuMDQtLjIxLDAtLjQxLS4xMy0uNTgtLjU4LS44NS0xLjQtMS41My0yLjM0LTEuOTgtMS4xMy0uNTgtMi40LS45Mi0zLjc2LTEuMDEtLjI3LS4wMi0uNTQtLjAzLS44MS0uMDMtLjYyLDAtMS4yMywuMDUtMS44MywuMTUtLjgyLC4xMy0xLjYyLC4zOC0yLjQsLjc1LS43MywuMzUtMS4zOCwuODctMS44OSwxLjUyLS41MiwuNjctLjgyLDEuNDctLjg3LDIuMy0uMDksLjgzLC4wNywxLjY2LC40OCwyLjQsLjM3LC42MywuOSwxLjE3LDEuNTMsMS41NywuNjQsLjQsMS4zNCwuNzMsMi4xMywuOTgsLjg1LC4yOCwxLjY1LC41MSwyLjQzLC43LC40MywuMTEsLjg3LC4yMywxLjM2LC4zOCwuMzgsLjEyLC43MSwuMjYsMS4wMSwuNDVsLjA4LC4wNGMuMjEsLjEzLC4zOCwuMjksLjQ5LC40NSwuMSwuMTYsLjE0LC4zMiwuMTIsLjU2LDAsLjIxLS4wOSwuNC0uMjIsLjU0LS4xNywuMTktLjQxLC4zNS0uNiwuNDRoLS4wNmwtLjA2LC4wMmMtLjMzLC4xNC0uNjksLjIzLTEuMDksLjI4LS4yNCwuMDMtLjQ4LC4wNC0uNzIsLjA0LS4xNywwLS4zNSwwLS41NC0uMDItLjc4LS4wMi0xLjU1LS4yNC0yLjIzLS42Mi0uNTItLjMzLS45Ny0uNzEtMS4zNC0xLjEyLS4xNC0uMTYtLjM1LS4yNi0uNTctLjI2LS4xNSwwLS4zMSwuMDQtLjQ1LC4xNGwtMi4zNCwxLjYyYy0uMTgsLjEyLS4zLC4zMi0uMzMsLjUzLS4wMywuMjEsLjAzLC40MywuMTcsLjYsLjcyLC44OCwxLjY0LDEuNTksMi42MSwyLjAzbC4wNCwuMDQsLjA1LC4wMmMxLjM0LC41NiwyLjczLC44Nyw0LjE1LC45MywuMzMsLjAyLC42NiwuMDQsMSwuMDQsLjU5LDAsMS4xOC0uMDQsMS43OC0uMTEsLjg5LS4xMSwxLjc1LS4zNiwyLjU4LS43MywuNzgtLjM3LDEuNDgtLjkyLDItMS41OSwuNTYtLjc1LC44OC0xLjY1LC45Mi0yLjU2LC4wOC0uOC0uMDctMS42MS0uNDEtMi4zNC0uMzItLjY0LS44LTEuMjItMS40MS0xLjY3WiIvPg0KICAgIDxwYXRoIGNsYXNzPSJjbHMtMiIgZD0iTTExMi40NCwyMC40OWMtNC45MSwwLTguOSwzLjkyLTguOSw4Ljc1czMuOTksOC43NSw4LjksOC43NWMyLjU1LDAsNC4wMi0xLjAxLDQuNzItMS42OHYuNmMwLC42LC40OSwxLjA5LDEuMDksMS4wOWgyYy42LDAsMS4wOS0uNDksMS4wOS0xLjA5di03LjY2YzAtNC44Mi0zLjk5LTguNzUtOC45LTguNzVabTAsNC4xNGMyLjU5LDAsNC42OSwyLjA3LDQuNjksNC42cy0yLjExLDQuNi00LjY5LDQuNi00LjctMi4wNi00LjctNC42LDIuMTEtNC42LDQuNy00LjZaIi8+DQogICAgPHBhdGggY2xhc3M9ImNscy0yIiBkPSJNMTAxLjM0LDIwLjVoMGMtMS4wNywuMDQtMi4xMSwuMjYtMy4wOSwuNjctMS4xLC40NC0yLjA4LDEuMDktMi45MiwxLjkxLS44MywuODItMS40OSwxLjc5LTEuOTQsMi44Ny0uNDEsLjk4LS42MywyLS42NSwzLjF2Ny44NmMwLC42LC41LDEuMDksMS4xMSwxLjA5aDIuMDJjLjYxLDAsMS4xMS0uNDksMS4xMS0xLjA5di03Ljg5Yy4wMi0uNTIsLjE0LTEuMDIsLjM0LTEuNSwuMjQtLjU3LC41OC0xLjA4LDEuMDItMS41MSwuNDQtLjQzLC45Ni0uNzcsMS41NC0xLjAxLC40Ny0uMiwuOTktLjMxLDEuNTUtLjM1LC41OS0uMDMsMS4wNi0uNTEsMS4wNi0xLjF2LTEuOTljLS4wMi0uMjktLjE1LS41Ny0uMzYtLjc2LS4yMS0uMTktLjQ2LS4yOS0uNzgtLjI5WiIvPg0KICAgIDxwYXRoIGNsYXNzPSJjbHMtMiIgZD0iTTg4Ljc2LDIwLjM0aC0yYy0uNiwwLTEuMSwuNDktMS4xLDEuMDl2OC40NGMtLjEyLDEuMDctLjU1LDEuOTctMS4yNiwyLjY3LS40LC40LS44NywuNzItMS4zOSwuOTMtLjU0LC4yMi0xLjEsLjMzLTEuNjYsLjMzcy0xLjEyLS4xMS0xLjY2LS4zM2MtLjUzLS4yMS0xLS41My0xLjM5LS45My0uNzEtLjcxLTEuMTQtMS42MS0xLjI2LTIuNzR2LTguMzdjMC0uNi0uNDktMS4wOS0xLjA5LTEuMDloLTJjLS42LDAtMS4wOSwuNDktMS4wOSwxLjA5djguMDdjMCwxLjE0LC4yMiwyLjIzLC42NSwzLjI1LC40MiwxLjAyLDEuMDQsMS45NSwxLjg1LDIuNzUsLjc5LC43OCwxLjcxLDEuNCwyLjc2LDEuODQsMS4wNSwuNDMsMi4xNSwuNjUsMy4yNiwuNjVzMi4yNC0uMjIsMy4yNi0uNjVjMS4wMi0uNDIsMS45NS0xLjA0LDIuNzYtMS44NCwxLjQtMS40LDIuMjYtMy4yMywyLjQ4LTUuMjl2LTguNzhjLS4wMS0uNi0uNTEtMS4wOC0xLjExLTEuMDhaIi8+DQogICAgPHBhdGggY2xhc3M9ImNscy0yIiBkPSJNMzQuNjIsMjAuNTNjLS4zOC0uMDUtLjc3LS4wOC0xLjE1LS4wOC0xLjczLDAtMy40MSwuNTEtNC44NSwxLjQ4LTEuNzcsMS4xOS0zLjA0LDIuOTctMy41OCw1LjAxLS41NSwyLjA1LS4zMyw0LjIzLC42MSw2LjEzLC45MywxLjksMi41MiwzLjQsNC40OSw0LjIyLDEuMDcsLjQ0LDIuMTksLjY2LDMuMzQsLjY2LC45NiwwLDEuOS0uMTYsMi44MS0uNDYsMS40OS0uNTIsMi44Mi0xLjQxLDMuODMtMi41OSwuMjEtLjI0LC4zLS41NiwuMjQtLjg4LS4wNi0uMzItLjI2LS42LS41Ni0uNzVsLTEuNTgtLjgyYy0uMTYtLjA5LS4zNS0uMTMtLjU0LS4xMy0uMzEsMC0uNjEsLjEyLS44MiwuMzQtLjU0LC41Mi0xLjE2LC45MS0xLjg0LDEuMTQtLjUxLC4xNy0xLjA0LC4yNS0xLjU3LC4yNS0uNjQsMC0xLjI2LS4xMi0xLjg0LS4zNy0xLjA4LS40NS0xLjk3LTEuMjgtMi40OS0yLjM0LS4wMy0uMDUtLjA1LS4xLS4wNy0uMTVoMTIuMDdjLjYxLDAsMS4xLS40OSwxLjEtMS4xdi0uOTFjLS4wMS0yLjExLS43OC00LjE0LTIuMTctNS43Mi0xLjQtMS42LTMuMzMtMi42My01LjQzLTIuOTFabS0zLjg2LDQuNThjLjgxLS41NCwxLjc2LS44MywyLjczLS44MywuMjIsMCwuNDMsLjAxLC42NSwuMDQsMS4xOCwuMTYsMi4yNiwuNzQsMy4wNSwxLjYzLC4zNSwuNCwuNjMsLjg1LC44NCwxLjM1aC05LjA3Yy4zNy0uODksMS0xLjY2LDEuODEtMi4yWiIvPg0KICA8L2c+DQo8L3N2Zz4NCg==" alt="seQura" />
            </span>
        );
    }
    return (
        <span style={{ width: '100%' }}>
            {label}
        </span>
    );
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

            // Delegated mode: skip AJAX, resolve immediately with hidden-input HTML.
            if (isDelegatedSelection()) {
                latestResolve = resolve;
                const hiddenInputHtml = `<input type="hidden" name="sequra_payment_method_data" value="${settings.delegatedPaymentMethodData}">`;
                onResolved(true, { content: hiddenInputHtml });
                return;
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