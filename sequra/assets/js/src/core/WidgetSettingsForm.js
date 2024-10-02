import { Repeater } from "./Repeater";

if (!window.SequraFE) {
    window.SequraFE = {};
}

(function () {
    /**
     * @typedef WidgetLabels
     * @property {string|null} message
     * @property {string|null} messageBelowLimit
     */

    /**
     * @typedef WidgetLocation
     * @property {string|null} selForTarget
     * @property {string|null} product
     * @property {string|null} country
     * @property {string|null} campaign
     */

    /**
     * @typedef MiniWidget
     * @property {string|null} selForPrice
     * @property {string|null} selForLocation
     * @property {string} message
     * @property {string|null} messageBelowLimit
     * @property {string|null} product
     * @property {string|null} country
     * @property {string|null} campaign
     */

    /**
     * @typedef CountryPaymentMethod
     * @property {string|null} countryCode
     * @property {string|null} product
     * @property {string|null} campaign
     * @property {string|null} title
     */

    /**
     * @typedef WidgetSettings
     * @property {boolean} useWidgets
     * @property {string|null} assetsKey
     * @property {boolean} displayWidgetOnProductPage
     * @property {boolean} showInstallmentAmountInProductListing
     * @property {boolean} showInstallmentAmountInCartPage
     * @property {WidgetLabels|null} widgetLabels
     * @property {string|null} widgetStyles
     * @property {string|null} selForPrice
     * @property {string|null} selForAltPrice
     * @property {string|null} selForAltPriceTrigger
     * @property {string|null} selForDefaultLocation
     * @property {WidgetLocation[]} customLocations
     * 
     * @property {string|null} selForCartPrice
     * @property {string|null} selForCartLocation
     * 
     * @property {string|null} selForListingPrice
     * @property {string|null} selForListingLocation
     */

    /**
     * Handles widgets settings form logic.
     *
     * @param {{
     * widgetSettings: WidgetSettings,
     * connectionSettings: ConnectionSettings,
     * countrySettings: CountrySettings[],
     * paymentMethods: PaymentMethod[],
     * allPaymentMethods: CountryPaymentMethod[],
     * }} data
     * @param {{
     * saveWidgetSettingsUrl: string,
     * getPaymentMethodsUrl: string,
     * getAllPaymentMethodsUrl: string,
     * page: string,
     * appState: string,
     * }} configuration
     * @constructor
     */
    function WidgetSettingsForm(data, configuration) {
        /** @type AjaxServiceType */
        const api = SequraFE.ajaxService;

        let allPaymentMethods = data.allPaymentMethods;

        const {
            elementGenerator: generator,
            validationService: validator,
            utilities
        } = SequraFE;

        /** @type WidgetSettings */
        let activeSettings;
        /** @type WidgetSettings */
        let changedSettings;
        /** @type string[] */
        let paymentMethodIds;
        /** @type boolean */
        let isAssetKeyValid = false;

        /** @type WidgetSettings */
        const defaultFormData = {
            useWidgets: false,
            assetsKey: '',
            displayWidgetOnProductPage: false,
            widgetLabels: {
                message: SequraFE.miniWidgetLabels.messages['ES'],
                messageBelowLimit: SequraFE.miniWidgetLabels.messagesBelowLimit['ES']
            },
            widgetStyles: '{"alignment":"center","amount-font-bold":"true","amount-font-color":"#1C1C1C","amount-font-size":"15","background-color":"white","border-color":"#B1AEBA","border-radius":"","class":"","font-color":"#1C1C1C","link-font-color":"#1C1C1C","link-underline":"true","no-costs-claim":"","size":"M","starting-text":"only","type":"banner"}',
            showInstallmentAmountInProductListing: false,
            showInstallmentAmountInCartPage: false,
            selForPrice: '.summary .price>.amount,.summary .price ins .amount',
            selForAltPrice: '.woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount',
            selForAltPriceTrigger: '.variations',
            selForDefaultLocation: '.summary>.price',
            customLocations: [],
            selForCartPrice: '.order-total .amount',
            selForCartLocation: '.order-total',
            selForListingPrice: '.product .price>.amount:first-child,.product .price ins .amount',
            selForListingLocation: '.product .price',
        };

        /**
         * Handles form rendering.
         */
        this.render = () => {
            if (!activeSettings) {
                activeSettings = utilities.cloneObject(defaultFormData);
                for (let key in activeSettings) {
                    activeSettings[key] = data?.widgetSettings?.[key] ?? defaultFormData[key];
                }
            }

            paymentMethodIds = data.paymentMethods?.map((paymentMethod) => paymentMethod.product);
            isAssetKeyValid = activeSettings.assetsKey && activeSettings.assetsKey.length !== 0;
            changedSettings = utilities.cloneObject(activeSettings)
            initForm();

            disableFooter(true);
            utilities.hideLoader();
        }

        /**
         * Initializes the form structure.
         */
        const initForm = () => {
            const pageContent = document.querySelector('.sq-content');
            pageContent?.append(
                generator.createElement('div', 'sq-content-inner', '', null, [
                    generator.createElement('div', 'sqp-flash-message-wrapper'),
                    generator.createPageHeading({
                        title: `widgets.title.${configuration.appState}`,
                        text: 'widgets.description'
                    }),
                    generator.createRadioGroupField({
                        value: changedSettings.useWidgets,
                        label: 'widgets.usePromotionalComponents.label',
                        options: [
                            { label: 'widgets.usePromotionalComponents.options.yes', value: true },
                            { label: 'widgets.usePromotionalComponents.options.no', value: false }
                        ],
                        onChange: (value) => handleChange('useWidgets', value)
                    })
                ])
            );

            renderAssetsKeyField();
            renderAdditionalSettings();
            renderControls();
            // maybeShowProductRelatedFields();
            maybeShowRelatedFields('.sq-product-related-field', changedSettings.displayWidgetOnProductPage);
            maybeShowRelatedFields('.sq-cart-related-field', changedSettings.showInstallmentAmountInCartPage);
            maybeShowRelatedFields('.sq-listing-related-field', changedSettings.showInstallmentAmountInProductListing);
        }

        /**
         * Renders the assets key field.
         */
        const renderAssetsKeyField = () => {
            const pageInnerContent = document.querySelector('.sq-content-inner');
            if (changedSettings.useWidgets) {
                pageInnerContent?.append(
                    generator.createTextField({
                        name: 'assets-key-input',
                        value: changedSettings.assetsKey,
                        className: 'sq-text-input',
                        label: 'widgets.assetKey.label',
                        description: 'widgets.assetKey.description',
                        onChange: (value) => handleChange('assetsKey', value)
                    })
                );

                if (changedSettings.assetsKey?.length !== 0) {
                    validator.validateField(
                        document.querySelector('[name="assets-key-input"]'),
                        !isAssetKeyValid,
                        'validation.invalidField'
                    );
                }
            }
        }

        /**
         * Renders additional widget settings.
         */
        const renderAdditionalSettings = () => {
            if (!changedSettings.useWidgets || !isAssetKeyValid) {
                return;
            }

            const pageInnerContent = document.querySelector('.sq-content-inner');

            pageInnerContent?.append(
                generator.createTextArea(
                    {
                        className: 'sq-text-input sq-text-area',
                        name: 'widget-styles',
                        label: 'widgets.configurator.label',
                        description: 'widgets.configurator.description.start',
                        value: changedSettings.widgetStyles,
                        onChange: (value) => handleChange('widgetStyles', value),
                        rows: 10
                    }
                ),
                generator.createToggleField({
                    value: changedSettings.displayWidgetOnProductPage,
                    label: 'widgets.displayOnProductPage.label',
                    description: 'widgets.displayOnProductPage.description',
                    onChange: (value) => handleChange('displayWidgetOnProductPage', value)
                }),
                // Product widget related fields
                generator.createTextField({
                    value: changedSettings.selForPrice,
                    name: 'selForPrice',
                    className: 'sq-text-input sq-product-related-field',
                    label: 'widgets.selForPrice.label',
                    description: 'widgets.selForPrice.description',
                    onChange: (value) => handleChange('selForPrice', value)
                }),
                generator.createTextField({
                    value: changedSettings.selForAltPrice,
                    name: 'selForAltPrice',
                    className: 'sq-text-input sq-product-related-field',
                    label: 'widgets.selForAltPrice.label',
                    description: 'widgets.selForAltPrice.description',
                    onChange: (value) => handleChange('selForAltPrice', value)
                }),
                generator.createTextField({
                    value: changedSettings.selForAltPriceTrigger,
                    name: 'selForAltPriceTrigger',
                    className: 'sq-text-input sq-product-related-field',
                    label: 'widgets.selForAltPriceTrigger.label',
                    description: 'widgets.selForAltPriceTrigger.description',
                    onChange: (value) => handleChange('selForAltPriceTrigger', value)
                }),
                generator.createTextField({
                    value: changedSettings.selForDefaultLocation,
                    name: 'selForDefaultLocation',
                    className: 'sq-text-input sq-product-related-field',
                    label: 'widgets.defaultLocationSel.label',
                    description: 'widgets.defaultLocationSel.description',
                    onChange: (value) => handleChange('selForDefaultLocation', value)
                }),
                generator.createElement('div', 'sq-field-wrapper sq-locations-container sq-product-related-field'),
                // End of product widget related fields
                generator.createToggleField({
                    value: changedSettings.showInstallmentAmountInCartPage,
                    label: 'widgets.showInCartPage.label',
                    description: 'widgets.showInCartPage.description',
                    onChange: (value) => handleChange('showInstallmentAmountInCartPage', value)
                }),

                generator.createTextField({
                    value: changedSettings.selForCartPrice,
                    name: 'selForCartPrice',
                    className: 'sq-text-input sq-cart-related-field',
                    label: 'widgets.selForCartPrice.label',
                    description: 'widgets.selForCartPrice.description',
                    onChange: (value) => handleChange('selForCartPrice', value)
                }),
                generator.createTextField({
                    value: changedSettings.selForCartLocation,
                    name: 'selForCartLocation',
                    className: 'sq-text-input sq-cart-related-field',
                    label: 'widgets.cartDefaultLocationSel.label',
                    description: 'widgets.cartDefaultLocationSel.description',
                    onChange: (value) => handleChange('selForCartLocation', value)
                }),

                // End of cart widget related fields
                generator.createToggleField({
                    value: changedSettings.showInstallmentAmountInProductListing,
                    label: 'widgets.showInProductListing.label',
                    description: 'widgets.showInProductListing.description',
                    onChange: (value) => handleChange('showInstallmentAmountInProductListing', value)
                }),

                generator.createTextField({
                    value: changedSettings.selForListingPrice,
                    name: 'selForListingPrice',
                    className: 'sq-text-input sq-listing-related-field',
                    label: 'widgets.selForListingPrice.label',
                    description: 'widgets.selForListingPrice.description',
                    onChange: (value) => handleChange('selForListingPrice', value)
                }),
                generator.createTextField({
                    value: changedSettings.selForListingLocation,
                    name: 'selForListingLocation',
                    className: 'sq-text-input sq-listing-related-field',
                    label: 'widgets.selForListingLocation.label',
                    description: 'widgets.selForListingLocation.description',
                    onChange: (value) => handleChange('selForListingLocation', value)
                })
            )

            document.querySelector('.sqp-textarea-field .sqp-field-subtitle').append(
                generator.createButtonLink({
                    className: 'sq-link-button',
                    text: 'widgets.configurator.description.link',
                    href: 'https://live.sequracdn.com/assets/static/simulator.html',
                    openInNewTab: true
                }),
                generator.createElement('span', '', 'widgets.configurator.description.end'),
            )

            // renderLabelsConfiguration();
            renderLocations();
        }

        const maybeShowRelatedFields = (relatedFieldClass, show) => {
            const selector = `.sq-field-wrapper:has(${relatedFieldClass}),.sq-field-wrapper${relatedFieldClass}`;
            const hiddenClass = 'sqs--hidden';
            document.querySelectorAll(selector).forEach((el) => {
                if (show) {
                    el.classList.remove(hiddenClass)
                } else {
                    el.classList.add(hiddenClass)
                }
            });
        }

        const renderLocations = () => {
            new Repeater({
                containerSelector: '.sq-locations-container',
                data: changedSettings.customLocations,
                getHeaders: () => [
                    {
                        title: SequraFE.translationService.translate('widgets.locations.headerTitle'),
                        description: SequraFE.translationService.translate('widgets.locations.headerDescription')
                    },
                ],
                getRowContent: (data) => {
                    let displayWidget = true;
                    if (data && 'undefined' !== typeof data.displayWidget) {
                        displayWidget = data.displayWidget;
                    }

                    return `
                    <div class="sq-table__row-field-wrapper sq-table__row-field-wrapper--grow sq-table__row-field-wrapper--space-between">
                       <h3 class="sqp-field-title">${SequraFE.translationService.translate('widgets.displayOnProductPage.label')}
                       <label class="sq-toggle"><input class="sqp-toggle-input" type="checkbox" ${displayWidget ? 'checked' : ''}><span class="sqp-toggle-round"></span></label>
                       </h3>
                       <span class="sqp-field-subtitle">${SequraFE.translationService.translate('widgets.displayOnProductPage.description')}</span>
                    </div>
                 
                     <div class="sq-table__row-field-wrapper sq-table__row-field-wrapper--grow">
                        <label class="sq-table__row-field-label">${SequraFE.translationService.translate('widgets.locations.selector')}</label>
                        <span class="sqp-field-subtitle">${SequraFE.translationService.translate('widgets.locations.leaveEmptyToUseDefault')}</span>
                        <input class="sq-table__row-field" type="text" value="${data && 'undefined' !== typeof data.selForTarget ? data.selForTarget : ''}">
                    </div>
                    <div class="sq-table__row-field-wrapper sq-table__row-field-wrapper--grow">
                    <label class="sq-table__row-field-label">${SequraFE.translationService.translate('widgets.configurator.label')}</label>
                    <span class="sqp-field-subtitle">${SequraFE.translationService.translate('widgets.configurator.description.start')}<a class="sq-link-button" href="https://live.sequracdn.com/assets/static/simulator.html" target="_blank"><span>${SequraFE.translationService.translate('widgets.configurator.description.link')}</span></a><span>${SequraFE.translationService.translate('widgets.configurator.description.end')} ${SequraFE.translationService.translate('widgets.locations.leaveEmptyToUseDefault')}</span></span>
                    <textarea class="sqp-field-component sq-text-input sq-text-area" rows="5">${data && 'undefined' !== typeof data.widgetStyles ? data.widgetStyles : ''}</textarea>
                    </div>
                    `
                },
                getRowHeader: (data) => {
                    let selectedFound = false;
                    return `
                    <div class="sq-table__row-field-wrapper">
                        <label class="sq-table__row-field-label">${SequraFE.translationService.translate('widgets.locations.paymentMethod')}</label>
                        <select class="sq-table__row-field">${allPaymentMethods ? allPaymentMethods.map((pm, idx) => {
                        if (!pm.supportsWidgets) {
                            return '';
                        }

                        let selected = '';
                        if(!selectedFound && data && data.product === pm.product && data.country === pm.countryCode && data.campaign === pm.campaign) {
                            selected = ' selected';
                            selectedFound = true;
                        }
                        const dataCampaign = pm.campaign ? ` data-campaign="${pm.campaign}"` : '';
                        return `<option key="${idx}" data-country-code="${pm.countryCode}" data-product="${pm.product}"${dataCampaign + selected}>${pm.title}</option>`;
                    }).join('') : ''
                        }
                        </select>
                    </div>
                   `
                },
                handleChange: table => {
                    const customLocations = [];
                    table.querySelectorAll('.sq-table__row').forEach(row => {
                        const select = row.querySelector('select');
                        const selForTarget = row.querySelector('input[type="text"]').value;
                        const widgetStyles = row.querySelector('textarea').value;
                        const displayWidget = row.querySelector('input[type="checkbox"]').checked;
                        const dataset = select.selectedIndex === -1 ? null : select.options[select.selectedIndex].dataset;

                        const product = 'undefined' !== typeof dataset.product ? dataset.product : null;
                        const country = 'undefined' !== typeof dataset.countryCode ? dataset.countryCode : null;
                        const campaign = 'undefined' !== typeof dataset.campaign ? dataset.campaign : null;
                        customLocations.push({ selForTarget, product, country, campaign, widgetStyles, displayWidget });
                    });
                    handleChange('customLocations', customLocations)
                },
                addRowText: 'widgets.locations.addRow',
                removeRowText: 'widgets.locations.removeRow',
            });
        }

        const renderLabelsConfiguration = () => {
            if (!changedSettings.showInstallmentAmountInProductListing) {
                return;
            }

            const pageInnerContent = document.querySelector('.sq-content-inner');

            if (!changedSettings.widgetLabels.message) {
                changedSettings.widgetLabels.message = miniWidgetLabels.messages.hasOwnProperty(SequraFE.adminLanguage) ?
                    miniWidgetLabels.messages[SequraFE.adminLanguage] : miniWidgetLabels.messages['ES'];
            }

            if (!changedSettings.widgetLabels.messageBelowLimit) {
                changedSettings.widgetLabels.messageBelowLimit = miniWidgetLabels.messagesBelowLimit.hasOwnProperty(SequraFE.adminLanguage) ?
                    miniWidgetLabels.messagesBelowLimit[SequraFE.adminLanguage] : miniWidgetLabels.messagesBelowLimit['ES'];
            }

            pageInnerContent?.append(
                generator.createTextField({
                    name: 'labels-message',
                    value: changedSettings.widgetLabels.message,
                    className: 'sq-text-input',
                    label: 'widgets.teaserMessage.label',
                    description: 'widgets.teaserMessage.description',
                    onChange: (value) => handleLabelChange('message', value)
                }),
                generator.createTextField({
                    name: 'labels-message-below-limit',
                    value: changedSettings.widgetLabels.messageBelowLimit,
                    className: 'sq-text-input',
                    label: 'widgets.messageBelowLimit.label',
                    description: 'widgets.messageBelowLimit.description',
                    onChange: (value) => handleLabelChange('messageBelowLimit', value)
                })
            );
        }

        /**
         * Renders form controls.
         */
        const renderControls = () => {
            const pageContent = document.querySelector('.sq-content');
            const pageInnerContent = document.querySelector('.sq-content-inner');

            if (configuration.appState === SequraFE.appStates.ONBOARDING) {
                pageInnerContent?.append(
                    generator.createButtonField({
                        className: 'sq-controls sqm--block',
                        buttonType: 'primary',
                        buttonLabel: 'general.continue',
                        onClick: handleSave
                    })
                )

                return;
            }

            pageContent?.append(
                generator.createPageFooter({
                    onSave: handleSave,
                    onCancel: () => {
                        utilities.showLoader();
                        const pageContent = document.querySelector('.sq-content');
                        while (pageContent?.firstChild) {
                            pageContent?.removeChild(pageContent?.firstChild);
                        }

                        this.render();
                    }
                })
            );
        }

        const isCssSelectorValid = selector => {
            try {
                document.querySelector(selector);
                return true;
            } catch {
                return false;
            }
        }

        const isCustomLocationValid = value => {
            try {
                value.forEach(location => {
                    if ('' !== location.selForTarget && !isCssSelectorValid(location.selForTarget)) {
                        throw new Error('Invalid selector');
                    }
                    if ('' !== location.widgetStyles && !isJSONValid(location.widgetStyles)) {
                        throw new Error('Invalid selector');
                    }
                    if (!allPaymentMethods.some(pm => pm.supportsWidgets && pm.product === location.product && pm.countryCode === location.country && pm.campaign === location.campaign)) {
                        throw new Error('Invalid payment method');
                    }
                    // Check if exists other location with the same product and country
                    if (value.filter(l => l.product === location.product && l.country === location.country && l.campaign === location.campaign).length > 1) {
                        throw new Error('Duplicated entry found');
                    }
                });
                return true;
            } catch {
                return false;
            }
        }

        /**
         * Handles the form input changes.
         *
         * @param name
         * @param value
         */
        const handleChange = (name, value) => {
            changedSettings[name] = value;
            disableFooter(false);

            if (name === 'useWidgets' || name === 'showInstallmentAmountInProductListing') {
                refreshForm();
            }

            if (name === 'widgetStyles') {
                const isValid = validator.validateJson(
                    document.querySelector('[name="widget-styles"]'),
                    value,
                    'validation.invalidJson'
                );
                disableFooter(!isValid);
            }

            if (name === 'assetsKey') {
                utilities.showLoader();
                isAssetsKeyValid()
                    .then((isValid) => {
                        isAssetKeyValid = isValid;
                        refreshForm();
                        validator.validateField(
                            document.querySelector('[name="assets-key-input"]'),
                            !isValid,
                            'validation.invalidField'
                        );
                    })
                    .finally(utilities.hideLoader);
            }

            if (name === 'displayWidgetOnProductPage') {
                maybeShowRelatedFields('.sq-product-related-field', value);
            }
            if (name === 'showInstallmentAmountInCartPage') {
                maybeShowRelatedFields('.sq-cart-related-field', value);
            }
            if (name === 'showInstallmentAmountInProductListing') {
                maybeShowRelatedFields('.sq-listing-related-field', value);
            }

            if (['selForPrice', 'selForDefaultLocation', 'selForAltPrice', 'selForAltPriceTrigger', 'selForCartPrice', 'selForCartLocation', 'selForListingPrice', 'selForListingLocation'].includes(name)) {
                const required = ['selForPrice', 'selForDefaultLocation', 'selForCartPrice', 'selForCartLocation', 'selForListingPrice', 'selForListingLocation'];
                const isValid = validator.validateCssSelector(
                    document.querySelector(`[name="${name}"]`),
                    required.includes(name),
                    'validation.invalidField'
                );
                disableFooter(!isValid);
            }

            if (name === 'customLocations') {
                const isValid = isCustomLocationValid(value);
                validator.validateField(
                    document.querySelector(`.sq-product-related-field .sq-table`),
                    !isValid,
                    'validation.invalidField'
                );
                disableFooter(!isValid);
            }
        }

        const handleLabelChange = (name, value) => {
            changedSettings['widgetLabels'][name] = value;
            disableFooter(false);
        }

        /**
         * Re-renders the form.
         */
        const refreshForm = () => {
            document.querySelector('.sq-content-inner')?.remove();
            configuration.appState !== SequraFE.appStates.ONBOARDING && document.querySelector('.sq-page-footer').remove();
            initForm();
        }

        /**
         * Handles the saving of the form.
         */
        const handleSave = () => {
            if (changedSettings.useWidgets && changedSettings.assetsKey?.length === 0) {
                validator.validateRequiredField(
                    document.querySelector('[name="assets-key-input"]'),
                    'validation.requiredField'
                )

                return;
            }

            if (changedSettings.useWidgets && !isAssetKeyValid) {
                return;
            }

            if (changedSettings.useWidgets) {
                let valid = isJSONValid(changedSettings.widgetStyles);

                validator.validateField(
                    document.querySelector(`[name="widget-styles"]`),
                    !valid,
                    'validation.invalidJSON'
                );

                if (changedSettings.displayWidgetOnProductPage) {
                    for (const name of ['selForPrice', 'selForDefaultLocation']) {
                        valid = validator.validateCssSelector(
                            document.querySelector(`[name="${name}"]`),
                            true,
                            'validation.invalidField'
                        ) && valid;
                    }
                    for (const name of ['selForAltPrice', 'selForAltPriceTrigger']) {
                        valid = validator.validateCssSelector(
                            document.querySelector(`[name="${name}"]`),
                            false,
                            'validation.invalidField'
                        ) && valid;
                    }

                    const isValid = isCustomLocationValid(changedSettings.customLocations);
                    valid = isValid && valid;
                    validator.validateField(
                        document.querySelector(`.sq-product-related-field .sq-table`),
                        !isValid,
                        'validation.invalidField'
                    );
                }

                if (changedSettings.showInstallmentAmountInCartPage) {
                    for (const name of ['selForCartPrice', 'selForCartLocation']) {
                        valid = validator.validateCssSelector(
                            document.querySelector(`[name="${name}"]`),
                            true,
                            'validation.invalidField'
                        ) && valid;
                    }
                }

                if (changedSettings.showInstallmentAmountInProductListing) {
                    // valid = validator.validateRequiredField(
                    //     document.querySelector('[name="labels-message"]'),
                    //     'validation.requiredField'
                    // ) && valid;

                    // valid = validator.validateRequiredField(
                    //     document.querySelector('[name="labels-message-below-limit"]'),
                    //     'validation.requiredField'
                    // ) && valid;

                    for (const name of ['selForListingPrice', 'selForListingLocation']) {
                        valid = validator.validateCssSelector(
                            document.querySelector(`[name="${name}"]`),
                            true,
                            'validation.invalidField'
                        ) && valid;
                    }
                }

                if (!valid) {
                    return;
                }
            }

            utilities.showLoader();
            api.post(configuration.saveWidgetSettingsUrl, changedSettings, SequraFE.customHeader)
                .then(() => {
                    if (configuration.appState === SequraFE.appStates.ONBOARDING) {
                        const index = SequraFE.pages.onboarding.indexOf(SequraFE.appPages.ONBOARDING.WIDGETS)
                        SequraFE.pages.onboarding.length > index + 1 ?
                            window.location.hash = configuration.appState + '-' + SequraFE.pages.onboarding[index + 1] :
                            window.location.hash = SequraFE.appStates.PAYMENT + '-' + SequraFE.appPages.PAYMENT.METHODS;
                    }

                    activeSettings = utilities.cloneObject(changedSettings);
                    SequraFE.state.setData('widgetSettings', activeSettings);

                    disableFooter(true);
                })
                .finally(utilities.hideLoader);
        }

        /**
         * Disables footer form controls.
         *
         * @param disable
         */
        const disableFooter = (disable) => {
            if (configuration.appState !== SequraFE.appStates.ONBOARDING) {
                utilities.disableFooter(disable);
            }
        }

        /**
         * Validates JSON string.
         *
         * @param jsonString
         *
         * @returns {boolean}
         */
        const isJSONValid = (jsonString) => {
            try {
                JSON.parse(jsonString);

                return true;
            } catch (e) {
                return false
            }
        }

        /**
         * Returns a Promise<boolean> for assets key validation.
         *
         * @returns {Promise<boolean>}
         */
        const isAssetsKeyValid = () => {
            const mode = data.connectionSettings.environment;
            const merchantId = data.countrySettings[0].merchantId;
            const assetsKey = changedSettings.assetsKey;
            const methods = paymentMethodIds.filter((id) => id !== 'i1').join('_');

            const validationUrl =
                `https://${mode}.sequracdn.com/scripts/${merchantId}/${assetsKey}/${methods}_cost.json`;

            let customHeader = {
                'Content-Type': 'text/plain'
            };

            return api.get(validationUrl, null, customHeader, null, SequraFE.customHeader).then(() => true).catch(() => false)
        }
    }

    SequraFE.WidgetSettingsForm = WidgetSettingsForm;
})();
