import { decodeEntities } from '@wordpress/html-entities';
import { useEffect } from '@wordpress/element';

const { registerPaymentMethod } = window.wc.wcBlocksRegistry
const { getSetting } = window.wc.wcSettings

const settings = getSetting('sequra_data', {})

const label = decodeEntities(settings.title)

const Content = (props) => {
    const { eventRegistration, emitResponse } = props;
    const { onPaymentProcessing, onCheckoutBeforeProcessing } = eventRegistration;

    useEffect(() => {
        const unsubscribe = onPaymentProcessing(async () => {
            const checkedInput = document.querySelector('[name="sequra_payment_method_data"]:checked')
            const data = checkedInput ? checkedInput.value : null;

            if (!data) {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: 'Please select a payment method.', // TODO: translate
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
        onPaymentProcessing,
    ]);

    useEffect(() => {
        const unsubscribe = onCheckoutBeforeProcessing(async () => {
            debugger
            // TODO: add iframe to the checkout
        });
        return () => unsubscribe();
    }, [
        onCheckoutBeforeProcessing
    ]);

    return <div dangerouslySetInnerHTML={{ __html: decodeEntities(settings.description || '') }} />
}

const Label = () => {
    return (
        <span style={{ width: '100%' }}>
            {label}
            <img src={decodeEntities(settings.icon)} alt={label} style={{ float: 'right', marginRight: '20px' }} />
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
    canMakePayment: () => true,
    ariaLabel: label,
    // SupportsConfiguration Object that describes various features provided by the payment method.
    supports: {
        features: settings.supports,
    }
})