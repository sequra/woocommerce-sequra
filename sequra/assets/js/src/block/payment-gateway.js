import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';

const { registerPaymentMethod } = window.wc.wcBlocksRegistry
const { getSetting } = window.wc.wcSettings

const settings = getSetting('sequra_data', {})
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
        document.addEventListener('canMakePaymentLoading', (event) => {
            setContent('<div class="sq-loader" style="margin-top:1rem;margin-bottom:1rem;"><span class="sqp-spinner"></span></div>');
        });
        document.addEventListener('canMakePaymentReady', (event) => {
            setContent(event.detail.content);
            if ('undefined' !== typeof Sequra && 'function' === typeof Sequra.refreshComponents && 'function' === typeof Sequra.onLoad) {
                Sequra.onLoad(() => Sequra.refreshComponents());
            }
        });
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

        return new Promise((resolve) => {
            const onResolved = (canMakePayment, detail) => {
                settings.description = detail.content;
                document.dispatchEvent(new CustomEvent('canMakePaymentReady', { detail }));
                resolve(canMakePayment);
            }

            if (!isSolicitationAllowed()) {
                // Prevent unnecessary requests.
                onResolved(false, { content: '' });
                return;
            }

            // Hash the data to prevent unnecessary requests.
            const requestId = btoa(encodeURIComponent(JSON.stringify({ shippingAddress, billingAddress, cart })));

            document.dispatchEvent(new CustomEvent('canMakePaymentLoading', { detail: { requestId } }));

            // TODO: Implement check if requestId is pending. When page loads, this code is executed three times for the same requestId what can be avoided.
            const cachedContent = readFromCache(requestId);
            if (cachedContent) {
                onResolved('' !== cachedContent, { content: cachedContent });
                return;
            }

            settings.description = '';
            const data = new FormData();
            data.append('action', settings.blockContentAjaxAction);
            data.append('shippingAddress', JSON.stringify(shippingAddress));
            data.append('billingAddress', JSON.stringify(billingAddress));
            data.append('requestId', requestId);

            fetch(settings.blockContentUrl, {
                method: 'POST',
                credentials: 'same-origin', // Allow including cookies in the request.
                body: data
            }).then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                response.json().then(json => {
                    if (!json.requestId) {
                        // invalidate the request.
                        throw new Error('Request ID is missing');
                    }

                    writeToCache(json.requestId, json.content);
                    onResolved('' !== json.content, { content: json.content });
                }).catch(() => {
                    onResolved(false, { content: '' });
                });
            }).catch(() => {
                onResolved(false, { content: '' });
            });
        });
    },
    ariaLabel: label,
    // SupportsConfiguration Object that describes various features provided by the payment method.
    supports: {
        features: settings.supports,
    }
})