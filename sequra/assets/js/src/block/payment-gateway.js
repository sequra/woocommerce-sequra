import { decodeEntities } from '@wordpress/html-entities';

const { registerPaymentMethod } = window.wc.wcBlocksRegistry
const { getSetting } = window.wc.wcSettings

const settings = getSetting('sequra_data', {})

const label = decodeEntities(settings.title)
const description = decodeEntities(settings.description || '')

const Content = () => {
    console.log(settings.paymentMethods)
    // return decodeEntities(settings.description || '')
    // return '';

    const methods = settings.paymentMethods.map((pm, i) => {
        return <SequraPaymentMethod key={i} {...pm} />
    })
    return (
        <>
            {
                description ? (
                    <span class="sequra-block__description">
                        {description}
                    </span>
                ) : null
            }
            {methods}
        </>
    );
}
const SequraPaymentMethod = ({ product, title, claim, costDescription, icon, campaign }) => {
    // console.log({ product, title, claim, costDescription, icon })
    // {decodeEntities(icon ? JSON.parse(icon) : '')}
    const cost = costDescription ? (
        <div class="sequra-payment-method__cost">
            <span class="sequra-payment-method__cost-label">{costDescription}</span>
        </div>) : null
    icon = null;
    const iconElem = icon ? (
        <div class="sequra-payment-method__icon" dangerouslySetInnerHTML={{ __html: icon }} />
    ) : null
    const inputVal = `${product}${campaign ? `:${campaign}` : ''}`
    // generate a unique id for the input based on microsecond timestamp
    // const inputId = window.performance.now()
    const handleOnChange = (e) => {
        //     // find closest element with class radio-control-wc-payment-method-options
        //     console.log(e.currentTarget.closest('.wc-block-components-radio-control-accordion-option').querySelector('.wc-block-components-radio-control__input'));
        //     e.currentTarget.closest('.wc-block-components-radio-control-accordion-option').querySelector('.wc-block-components-radio-control__input').checked = e.currentTarget.checked;
    }
    return (
        <div class="sequra-payment-method">
            <input onChange={handleOnChange} class="sequra-payment-method__input wc-block-components-radio-control__input" type="radio" name="sequraPaymentMethod" value={inputVal} id={inputVal}></input>
            <label for={inputVal}>
                {iconElem}
                <div class="sequra-payment-method__description">
                    <span class="sequra-payment-method__name" style={{ width: '100%' }}>{title}</span>
                    <span class="sequra-payment-method_claim" style={{ width: '100%' }}>{claim}</span>
                </div>
                {cost}
            </label>
        </div>
    )
}

const Icon = () => {
    return <img src="https://cdn.prod.website-files.com/62b803c519da726951bd71c2/62b803c519da72c35fbd72a2_Logo.svg" style={{ float: 'right', marginRight: '20px' }} />
}

// const Label = () => {
//     return settings.paymentMethods.map((pm, i) => {
//         // console.log(pm);
//         return <SequraPaymentMethod key={i} {...pm} />
//     })
// }
const Label = (props) => {
    // const { PaymentMethodLabel } = props.components
    // return <PaymentMethodLabel text={label} />
    return (
        <span style={{ width: '100%' }}>
            {label}
            <Icon />
        </span>
    )
}

// document.addEventListener('DOMContentLoaded', function () {
//     console.log(document.querySelectorAll('[name="radio-control-wc-payment-method-options"]'));
//     document.querySelectorAll('[name="radio-control-wc-payment-method-options"]').forEach((input) => {
//         input.addEventListener('change', function ({ currentTarget }) {
//             // if is unchecked then uncheck all other inputs
//             console.log(currentTarget);
//             if (currentTarget.checked && currentTarget.value !== 'sequra') {
//                 document.querySelectorAll('.sequra-payment-method__input').forEach((input) => {
//                     input.checked = false;
//                 });
//             }
//         });
//     });
// });

registerPaymentMethod({
    name: "sequra",
    label: <Label />,
    content: <Content />,
    edit: <Content />,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    }
})