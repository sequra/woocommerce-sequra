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
     * @property {string|null} sel_for_target
     * @property {string|null} product
     * @property {string|null} country
     */

    /**
     * @typedef MiniWidget
     * @property {string|null} selForPrice
     * @property {string|null} selForLocation
     * @property {string} message
     * @property {string|null} messageBelowLimit
     * @property {string|null} product
     * @property {string|null} country
     * @property {string|null} title
     */

    /**
     * @typedef CountryPaymentMethod
     * @property {string|null} countryCode
     * @property {string|null} product
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
     * @property {string|null} selForCartDefaultLocation
     * @property {MiniWidget[]} cartMiniWidgets
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

        const miniWidgetLabels = {
            messages: {
                "ES": "Desde %s/mes con seQura",
                "FR": "À partir de %s/mois avec seQura",
                "IT": "Da %s/mese con seQura",
                "PT": "De %s/mês com seQura"
            },
            messagesBelowLimit: {
                "ES": "Fracciona con seQura a partir de %s",
                "FR": "Fraction avec seQura à partir de %s",
                "IT": "Frazione con seQura da %s",
                "PT": "Fração com seQura a partir de %s"
            }
        }

        /** @type WidgetSettings */
        const defaultFormData = {
            useWidgets: false,
            assetsKey: '',
            displayWidgetOnProductPage: false,
            widgetLabels: {
                message: miniWidgetLabels.messages['ES'],
                messageBelowLimit: miniWidgetLabels.messagesBelowLimit['ES']
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
            selForCartDefaultLocation: '.order-total',
            cartMiniWidgets: [...new Set(allPaymentMethods.map(pm => pm.countryCode))].map(countryCode => ({
                countryCode,
                product: null,
                title: null,
                selForPrice: null,
                selForLocation: null,
                message: miniWidgetLabels.messages[countryCode],
                messageBelowLimit: miniWidgetLabels.messagesBelowLimit[countryCode]
            }))
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
                    value: changedSettings.selForCartDefaultLocation,
                    name: 'selForCartDefaultLocation',
                    className: 'sq-text-input sq-cart-related-field',
                    label: 'widgets.cartDefaultLocationSel.label',
                    description: 'widgets.cartDefaultLocationSel.description',
                    onChange: (value) => handleChange('selForCartDefaultLocation', value)
                }),
                generator.createElement('div', 'sq-field-wrapper sq-mini-widgets-config-wrapper sq-cart-related-field'),

                // End of cart widget related fields
                generator.createToggleField({
                    value: changedSettings.showInstallmentAmountInProductListing,
                    label: 'widgets.showInProductListing.label',
                    description: 'widgets.showInProductListing.description',
                    onChange: (value) => handleChange('showInstallmentAmountInProductListing', value)
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

            renderLabelsConfiguration();
            renderLocations();
            renderCartWidgetConfigurator();
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
                    if (data && 'undefined' !== typeof data.display_widget) {
                        displayWidget = data.display_widget;
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
                        <input class="sq-table__row-field" type="text" value="${data && 'undefined' !== typeof data.sel_for_target ? data.sel_for_target : ''}">
                    </div>
                    <div class="sq-table__row-field-wrapper sq-table__row-field-wrapper--grow">
                    <label class="sq-table__row-field-label">${SequraFE.translationService.translate('widgets.configurator.label')}</label>
                    <span class="sqp-field-subtitle">${SequraFE.translationService.translate('widgets.configurator.description.start')}<a class="sq-link-button" href="https://live.sequracdn.com/assets/static/simulator.html" target="_blank"><span>${SequraFE.translationService.translate('widgets.configurator.description.link')}</span></a><span>${SequraFE.translationService.translate('widgets.configurator.description.end')} ${SequraFE.translationService.translate('widgets.locations.leaveEmptyToUseDefault')}</span></span>
                    <textarea class="sqp-field-component sq-text-input sq-text-area" rows="5">${data && 'undefined' !== typeof data.widget_styles ? data.widget_styles : ''}</textarea>
                    </div>
                    `
                },
                getRowHeader: (data) => {
                    return `
                    <div class="sq-table__row-field-wrapper">
                        <label class="sq-table__row-field-label">${SequraFE.translationService.translate('widgets.locations.paymentMethod')}</label>
                        <select class="sq-table__row-field">${allPaymentMethods ? allPaymentMethods.map((pm, idx) => {
                        if (!pm.supportsWidgets) {
                            return '';
                        }
                        const selected = data && data.product === pm.product && data.country === pm.countryCode && data.title === pm.title ? ' selected' : '';
                        return `<option key="${idx}" data-country-code="${pm.countryCode}" data-product="${pm.product}"${selected}>${pm.title}</option>`;
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
                        const sel_for_target = row.querySelector('input[type="text"]').value;
                        const widget_styles = row.querySelector('textarea').value;
                        const display_widget = row.querySelector('input[type="checkbox"]').checked;
                        const product = select.selectedIndex === -1 ? null : select.options[select.selectedIndex].dataset.product;
                        const country = select.selectedIndex === -1 ? null : select.options[select.selectedIndex].dataset.countryCode;
                        const title = select.selectedIndex === -1 ? null : select.options[select.selectedIndex].textContent;
                        customLocations.push({ sel_for_target, product, country, title, widget_styles, display_widget });
                    });
                    handleChange('customLocations', customLocations)
                },
                addRowText: 'widgets.locations.addRow',
                removeRowText: 'widgets.locations.removeRow',
            });
        }

        const renderCartWidgetConfigurator = () => {

            const miniWidgets = changedSettings.cartMiniWidgets;

            new Repeater({
                canAdd: false,
                canRemove: false,
                name: 'cartWidgetConfigurator',
                containerSelector: '.sq-mini-widgets-config-wrapper.sq-cart-related-field',
                data: [...new Set(allPaymentMethods.map(pm => pm.countryCode))],
                getHeaders: () => [
                    {
                        title: SequraFE.translationService.translate('widgets.cartConfig.headerTitle'),
                        description: SequraFE.translationService.translate('widgets.cartConfig.headerDescription')
                    },
                ],
                getRowContent: (countryCode) => {
                    const data = miniWidgets.find(miniWidget => miniWidget.countryCode === countryCode);
                    let message = miniWidgetLabels.messages[countryCode];
                    if (data && 'undefined' !== typeof data.message && data.message) {
                        message = data.message;
                    }

                    let messageBelowLimit = miniWidgetLabels.messagesBelowLimit[countryCode];
                    if (data && 'undefined' !== typeof data.messageBelowLimit && null !== data.messageBelowLimit) {
                        messageBelowLimit = data.messageBelowLimit;
                    }

                    return `
                    <div class="sq-table__row-field-wrapper">
                        <label class="sq-table__row-field-label">${SequraFE.translationService.translate('widgets.cartConfig.paymentMethod')}</label>
                        <select class="sq-table__row-field" data-field="paymentMethod" data-country-code="${countryCode}">
                            <option key="-1" data-country-code="" data-product="">${SequraFE.translationService.translate('widgets.cartConfig.disable')}</option>
                            ${allPaymentMethods ? allPaymentMethods.map((pm, idx) => {
                        if (countryCode !== pm.countryCode || !pm.supportsInstallmentPayments) {
                            return '';
                        }
                        const selected = data && data.product === pm.product && data.title === pm.title ? ' selected' : '';
                        return `<option key="${idx}" data-country-code="${pm.countryCode}" data-product="${pm.product}"${selected}>${pm.title}</option>`;
                    }).join('') : ''}
                        </select>
                    </div>
                     <div class="sq-table__row-field-wrapper sq-table__row-field-wrapper--grow">
                        <label class="sq-table__row-field-label">${SequraFE.translationService.translate('widgets.selForCartPrice.label')}</label>
                        <span class="sqp-field-subtitle">${SequraFE.translationService.translate('widgets.cartConfig.leaveEmptyToUseDefault')}</span>
                        <input class="sq-table__row-field" data-field="selForPrice" type="text" value="${data && 'undefined' !== typeof data.selForPrice ? data.selForPrice : ''}">
                    </div>
                     <div class="sq-table__row-field-wrapper sq-table__row-field-wrapper--grow">
                        <label class="sq-table__row-field-label">${SequraFE.translationService.translate('widgets.cartConfig.locationSelLabel')}</label>
                        <span class="sqp-field-subtitle">${SequraFE.translationService.translate('widgets.cartConfig.leaveEmptyToUseDefault')}</span>
                        <input class="sq-table__row-field" data-field="selForLocation" type="text" value="${data && 'undefined' !== typeof data.selForLocation ? data.selForLocation : ''}">
                    </div>

                     <div class="sq-table__row-field-wrapper sq-table__row-field-wrapper--grow">
                        <label class="sq-table__row-field-label">${SequraFE.translationService.translate('widgets.teaserMessage.label')}</label>
                        <span class="sqp-field-subtitle">${SequraFE.translationService.translate('widgets.teaserMessage.description')}</span>
                        <input class="sq-table__row-field" data-field="message" type="text" value="${message}">
                    </div>
                     <div class="sq-table__row-field-wrapper sq-table__row-field-wrapper--grow">
                        <label class="sq-table__row-field-label">${SequraFE.translationService.translate('widgets.messageBelowLimit.label')}</label>
                        <span class="sqp-field-subtitle">${SequraFE.translationService.translate('widgets.messageBelowLimit.description')}</span>
                        <input class="sq-table__row-field" data-field="messageBelowLimit" type="text" value="${messageBelowLimit}">
                    </div>
                   `
                },
                getRowHeader: (countryCode) => {
                    const flag = SequraFE.imagesProvider.flags[countryCode] || '';
                    return `${flag} <span class="sqp-field-title">${SequraFE.translationService.translate(`countries.${countryCode}.label`)}</span>`
                },
                handleChange: table => {
                    const cartMiniWidgets = [];
                    table.querySelectorAll('.sq-table__row').forEach(row => {
                        const select = row.querySelector('[data-field="paymentMethod"]');
                        const countryCode = select.dataset.countryCode;
                        const product = !select.selectedIndex ? null : select.options[select.selectedIndex].dataset.product;
                        const title = !select.selectedIndex ? null : select.options[select.selectedIndex].textContent;
                        const selForPrice = row.querySelector('[data-field="selForPrice"]').value;
                        const selForLocation = row.querySelector('[data-field="selForLocation"]').value;
                        let message = row.querySelector('[data-field="message"]').value;
                        if (!message) {
                            // Required field. If empty, set default value.
                            message = miniWidgetLabels.messages[countryCode];
                            row.querySelector('[data-field="message"]').value = message;
                        }
                        const messageBelowLimit = row.querySelector('[data-field="messageBelowLimit"]').value;

                        cartMiniWidgets.push({ countryCode, product, title, selForPrice, selForLocation, message, messageBelowLimit });
                    });
                    handleChange('cartMiniWidgets', cartMiniWidgets);

                }
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
                    if ('' !== location.sel_for_target && !isCssSelectorValid(location.sel_for_target)) {
                        throw new Error('Invalid selector');
                    }
                    if ('' !== location.widget_styles && !isJSONValid(location.widget_styles)) {
                        throw new Error('Invalid selector');
                    }
                    if (!allPaymentMethods.some(pm => pm.supportsWidgets && pm.product === location.product && pm.countryCode === location.country && pm.title === location.title)) {
                        throw new Error('Invalid payment method');
                    }
                    // Check if exists other location with the same product and country
                    if (value.filter(l => l.product === location.product && l.country === location.country && l.title === location.title).length > 1) {
                        throw new Error('Duplicated entry found');
                    }
                });
                return true;
            } catch {
                return false;
            }
        }

        const areMiniWidgetsValid = values => {
            try {
                values.forEach(miniWidget => {
                    const { countryCode, title, product } = miniWidget;
                    if (!countryCode) {
                        throw new Error('Invalid country code');
                    }
                    if ('' !== miniWidget.selForPrice && !isCssSelectorValid(miniWidget.selForPrice)) {
                        throw new Error('Invalid selector');
                    }
                    if ('' !== miniWidget.selForLocation && !isCssSelectorValid(miniWidget.selForLocation)) {
                        throw new Error('Invalid selector');
                    }
                    if (!miniWidget.message) {
                        throw new Error('Invalid selector');
                    }
                    if (title && product && !allPaymentMethods.some(pm => pm.supportsInstallmentPayments && pm.product === product && pm.countryCode === countryCode && pm.title === title)) {
                        throw new Error('Invalid payment method');
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

            if (name === 'selForPrice' || name === 'selForDefaultLocation') {
                const isValid = validator.validateCssSelector(
                    document.querySelector(`[name="${name}"]`),
                    true,
                    'validation.invalidField'
                );
                disableFooter(!isValid);
            }
            if (name === 'selForAltPrice' || name === 'selForAltPriceTrigger') {
                const isValid = validator.validateCssSelector(
                    document.querySelector(`[name="${name}"]`),
                    false,
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


            if (name === 'cartMiniWidgets') {
                const isValid = areMiniWidgetsValid(value);
                validator.validateField(
                    document.querySelector(`.sq-cart-related-field .sq-table`),
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
                    for (const name of ['selForCartPrice', 'selForCartDefaultLocation']) {
                        valid = validator.validateCssSelector(
                            document.querySelector(`[name="${name}"]`),
                            true,
                            'validation.invalidField'
                        ) && valid;
                    }

                    const isValid = areMiniWidgetsValid(changedSettings.cartMiniWidgets);
                    valid = isValid && valid;
                    validator.validateField(
                        document.querySelector(`.sq-cart-related-field .sq-table`),
                        !isValid,
                        'validation.invalidField'
                    );
                }

                if (changedSettings.showInstallmentAmountInProductListing) {
                    valid = validator.validateRequiredField(
                        document.querySelector('[name="labels-message"]'),
                        'validation.requiredField'
                    ) && valid;

                    valid = validator.validateRequiredField(
                        document.querySelector('[name="labels-message-below-limit"]'),
                        'validation.requiredField'
                    ) && valid;
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
