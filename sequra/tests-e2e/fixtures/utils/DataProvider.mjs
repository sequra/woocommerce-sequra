import { DataProvider as BaseDataProvider } from 'playwright-fixture-for-plugins';
// TODO: pending review
export default class DataProvider extends BaseDataProvider {

    static UI_BLOCKS = 'blocks';
    static UI_CLASSIC = 'classic';

    /**
     * Map the UI version to a theme that uses that UI version
     * @param {string} uiVersion One of DataProvider.UI_BLOCKS or DataProvider.UI_CLASSIC
     * @throws {Error} If the UI version is not recognized
     * @returns {string} The theme that uses that UI version
     */
    themeForUiVersion(uiVersion) {
        const themes = {
            [DataProvider.UI_BLOCKS]: 'twentytwentyfour',
            [DataProvider.UI_CLASSIC]: 'storefront',
        }
        if ('undefined' === typeof themes[uiVersion]) {
            throw new Error(`Invalid UI version: ${JSON.stringify(uiVersion)}. Must be one of DataProvider.UI_BLOCKS or DataProvider.UI_CLASSIC`);
        }
        return themes[uiVersion];
    }

    /**
    * Configuration for the widget form with all options enabled
    * @param {Object} options Allows extending the default behavior by defining additional options.
    * @param {string} options.uiVersion The UI version to use. One of DataProvider.UI_BLOCKS or DataProvider.UI_CLASSIC
    * @returns {import('playwright-fixture-for-plugins/src/utils/DataProvider').WidgetOptions} Configuration for the widget
    */
    widgetOptions(options = {}) {
        options = { uiVersion: DataProvider.UI_BLOCKS, ...options };
        if (options.uiVersion !== DataProvider.UI_BLOCKS && options.uiVersion !== DataProvider.UI_CLASSIC) {
            throw new Error(`Invalid UI version: ${JSON.stringify(options)}. Must be one of DataProvider.UI_BLOCKS or DataProvider.UI_CLASSIC`);
        }
        const widgetOptions = super.widgetOptions(options);
        const commonOptions = {
            ...widgetOptions,
            product: {
                ...widgetOptions.product,
                altPriceSel: '.woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount',
                altPriceTriggerSel: '.variations',
                customLocations: widgetOptions.product.customLocations.map(cl => ({
                    ...cl,
                    locationSel: cl.product === 'i1' ? 'form.cart' : cl.locationSel
                }))
            }
        };
        return ({
            [DataProvider.UI_BLOCKS]: {
                ...commonOptions,
                product: {
                    ...commonOptions.product,
                    priceSel: 'main .wc-block-components-product-price>.amount,main .wc-block-components-product-price ins .amount',
                    locationSel: 'main .wc-block-components-product-price'
                },
                cart: {
                    ...commonOptions.cart,
                    priceSel: '.wp-block-woocommerce-cart-totals-block .wc-block-components-totals-footer-item .wc-block-components-totals-item__value',
                    locationSel: '.wp-block-woocommerce-cart-totals-block .wc-block-components-totals-footer-item'
                },
                productListing: {
                    ...commonOptions.productListing,
                    priceSel: '.product .wc-block-components-product-price>.amount:first-child,.product .wc-block-components-product-price ins .amount',
                    locationSel: '.product .wc-block-components-product-price'
                }
            },
            [DataProvider.UI_CLASSIC]: {
                ...commonOptions,
                product: {
                    ...commonOptions.product,
                    priceSel: '.summary .price>.amount,.summary .price ins .amount',
                    locationSel: '.summary>.price'
                },
                cart: {
                    ...commonOptions.cart,
                    priceSel: '.order-total .amount',
                    locationSel: '.order-total'
                },
                productListing: {
                    ...commonOptions.productListing,
                    priceSel: '.product .price>.amount:first-child,.product .price ins .amount',
                    locationSel: '.product .price'
                }
            }
        })[options.uiVersion];
    }

    /**
     * @param {Object} options Additional options to configure the widget
     * @param {string} options.slug The product slug
     * @returns {FrontEndWidgetOptions} Options for the i1 widget
     */
    pp3FrontEndWidgetOptions = (options = {}) => {
        const args = this.getFrontEndWidgetProductArguments(options);
        if (!args) {
            throw new Error(`No front-end widget arguments found for slug: ${JSON.stringify(options)}`);
        }
        return this.frontEndWidgetOptions('pp3', null, args.amount, args.registrationAmount, options);
    }

    /**
     * @param {Object} options Additional options to configure the widget
     * @returns {FrontEndWidgetOptions} Options for the sp1 widget
     */
    sp1FrontEndWidgetOptions = (options = {}) => {
        const args = this.getFrontEndWidgetProductArguments(options);
        if (!args) {
            throw new Error(`No front-end widget arguments found for slug: ${JSON.stringify(options)}`);
        }
        return this.frontEndWidgetOptions('sp1', 'permanente', args.amount, args.registrationAmount, options);
    }

    /**
     * @param {Object} options Additional options to configure the widget
     * @returns {FrontEndWidgetOptions} Options for the i1 widget
     */
    i1FrontEndWidgetOptions = (options = {}) => {
        const args = this.getFrontEndWidgetProductArguments(options);
        if (!args) {
            throw new Error(`No front-end widget arguments found for slug: ${JSON.stringify(options)}`);
        }
        return this.frontEndWidgetOptions('i1', null, args.amount, args.registrationAmount, options);
        // const widget = this.widgetOptions();
        // return {
        //     ...this.frontEndWidgetOptions('i1', null, args.amount, args.registrationAmount),
        //     locationSel: widget.product.customLocations[0].locationSel || widget.product.locationSel,
        //     widgetConfig: widget.product.customLocations[0].widgetConfig || widget.product.widgetConfig,
        // };
    }

    /**
     * Convert the slug to the front-end widget arguments
     * @param {Object} options 
     * @param {string} options.slug The product slug.
     * @returns {Object|null} The arguments for the front-end widget or null if the slug is not recognized
     */
    getFrontEndWidgetProductArguments(options) {
        if ('undefined' === typeof options.slug) {
            return null;
        }
        return {
            "sunglasses": { amount: 9000, registrationAmount: null },
            "hoodie": { amount: 8000, registrationAmount: null }
        }[options.slug] || null;
    }
}