if (!window.SequraFE) {
    window.SequraFE = {};
}

(function () {
    /**
     * @typedef GeneralSettings
     * @property {boolean} sendOrderReportsPeriodicallyToSeQura
     * @property {boolean | null} showSeQuraCheckoutAsHostedPage
     * @property {string[] | null} allowedIPAddresses
     * @property {string[] | null} excludedCategories
     * @property {string[] | null} excludedProducts
     * @property {string | null} replacementPaymentMethod
     */

    /**
     * @typedef Category
     * @property {string} id
     * @property {string} name
     */

    /**
     * @typedef ShopPaymentMethod
     * @property {string} code
     * @property {string} name
     */

    /**
     * @typedef CountrySettings
     * @property {string} countryCode
     * @property {string} merchantId
     */

    /**
     * @typedef SellingCountry
     * @property {string} name
     * @property {string} code
     */

    /**
     * Handles general settings form logic.
     *
     * @param {{
     * generalSettings: GeneralSettings,
     * countrySettings: CountrySettings[],
     * shopCategories: Category[],
     * shopPaymentMethods: ShopPaymentMethod[],
     * sellingCountries: SellingCountry[],
     * connectionSettings: ConnectionSettings,
     * }} data
     * @param {{
     * saveGeneralSettingsUrl: string,
     * saveCountrySettingsUrl: string,
     * validateConnectionDataUrl: string,
     * page: string,
     * appState: string,
     * }} configuration
     * @constructor
     */
    function GeneralSettingsForm(data, configuration) {
        const {
            elementGenerator: generator,
            validationService: validator,
            utilities
        } = SequraFE;
        /** @type AjaxServiceType */
        const api = SequraFE.ajaxService;
        /** @type GeneralSettings */
        let activeGeneralSettings;
        /** @type CountrySettings[] */
        let activeCountryConfiguration;
        /** @type GeneralSettings */
        let changedGeneralSettings;
        /** @type CountrySettings[] */
        let changedCountryConfiguration;
        /** @type boolean */
        let haveGeneralSettingsChanged = false;
        /** @type boolean */
        let hasCountryConfigurationChanged = false;

        /** @type boolean */
        const useHostedPage = SequraFE?.generalSettings?.useHostedPage ?? true
        /** @type boolean */
        const useReplacementPaymentMethod = SequraFE?.generalSettings?.useReplacementPaymentMethod ?? false
        /** @type boolean */
        const useAllowedIPAddresses = SequraFE?.generalSettings?.useAllowedIPAddresses ?? true
        /** @type boolean */
        const useOrderReporting = SequraFE?.generalSettings?.useOrderReporting ?? false;

        /** @type GeneralSettings */
        const defaultGeneralSettingsData = {
            sendOrderReportsPeriodicallyToSeQura: false,
            showSeQuraCheckoutAsHostedPage: useHostedPage ? false : null,
            allowedIPAddresses: useAllowedIPAddresses ? [] : null,
            excludedCategories: [],
            excludedProducts: [],
            replacementPaymentMethod: useReplacementPaymentMethod ? '' : null,
            enabledForServices: false,
            allowFirstServicePaymentDelay: false,
            allowServiceRegItems: false,
            defaultServicesEndDate: 'P1Y'
        };

        /**
         * Handles form rendering.
         */
        this.render = () => {
            if (!activeCountryConfiguration) {
                activeCountryConfiguration = data?.countrySettings?.map((utilities.cloneObject))
            }

            if (!activeGeneralSettings) {
                activeGeneralSettings = utilities.cloneObject(defaultGeneralSettingsData);
                for (let key in activeGeneralSettings) {
                    activeGeneralSettings[key] = data?.generalSettings?.[key] ?? defaultGeneralSettingsData[key];
                }
            }

            changedCountryConfiguration = activeCountryConfiguration?.map((utilities.cloneObject))
            changedGeneralSettings = utilities.cloneObject(activeGeneralSettings)
            initForm();

            if (SequraFE.state.getCredentialsChanged()) {
                handleSave();

                return;
            }

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
                        title: configuration.appState === SequraFE.appStates.ONBOARDING ?
                            'countries.title' : 'generalSettings.title',
                        text: configuration.appState === SequraFE.appStates.ONBOARDING ?
                            'countries.description' : 'generalSettings.description'
                    })
                ])
            );

            const pageInnerContent = document.querySelector('.sq-content-inner');

            if (data.sellingCountries.length === 0) {
                pageInnerContent?.append(utilities.createFlashMessage('general.errors.countries.noCountries', "error"));

                return;
            }

            if (configuration.appState === SequraFE.appStates.SETTINGS && !SequraFE.isPromotional) {
                pageInnerContent?.append(
                    useHostedPage ? generator.createToggleField({
                        value: changedGeneralSettings.showSeQuraCheckoutAsHostedPage,
                        label: 'generalSettings.showCheckoutAsHostedPage.label',
                        description: 'generalSettings.showCheckoutAsHostedPage.description',
                        onChange: (value) => handleGeneralSettingsChange('showSeQuraCheckoutAsHostedPage', value)
                    }) : [],
                    useOrderReporting ? generator.createToggleField({
                        value: changedGeneralSettings.sendOrderReportsPeriodicallyToSeQura,
                        label: 'generalSettings.sendOrderReports.label',
                        description: 'generalSettings.sendOrderReports.description',
                        onChange: (value) => handleGeneralSettingsChange('sendOrderReportsPeriodicallyToSeQura', value)
                    }) : [],
                    useReplacementPaymentMethod ? generator.createDropdownField({
                        value: changedGeneralSettings.replacementPaymentMethod,
                        label: 'generalSettings.replacementPaymentMethod.label',
                        description: 'generalSettings.replacementPaymentMethod.description',
                        options: getPaymentMethodOptions(),
                        onChange: (value) => handleGeneralSettingsChange('replacementPaymentMethod', value)
                    }) : [],
                    useAllowedIPAddresses ? generator.createMultiItemSelectorField({
                        name: 'allowedIPAddresses-selector',
                        label: 'generalSettings.allowedIPAddresses.label',
                        description: 'generalSettings.allowedIPAddresses.description',
                        value: changedGeneralSettings.allowedIPAddresses?.join(', '),
                        searchable: false,
                        onChange: (value) => handleGeneralSettingsChange('allowedIPAddresses', value)
                    }) : [],
                    generator.createMultiItemSelectorField({
                        label: 'generalSettings.excludedCategories.label',
                        description: 'generalSettings.excludedCategories.description',
                        value: changedGeneralSettings.excludedCategories?.join(','),
                        options: data.shopCategories.map((category) => ({ label: category.name, value: category.id })),
                        onChange: (value) => handleGeneralSettingsChange('excludedCategories', value)
                    }),
                    generator.createMultiItemSelectorField({
                        label: 'generalSettings.excludedProducts.label',
                        description: 'generalSettings.excludedProducts.description',
                        value: changedGeneralSettings.excludedProducts?.join(','),
                        searchable: false,
                        onChange: (value) => handleGeneralSettingsChange('excludedProducts', value)
                    }),
                    // Add more fields here
                    generator.createToggleField({
                        value: changedGeneralSettings.enabledForServices,
                        label: 'generalSettings.enabledForServices.label',
                        description: 'generalSettings.enabledForServices.description',
                        onChange: handleEnabledForServicesChange
                    }),
                    generator.createToggleField({
                        // className: 'sq-log-settings-toggle',
                        className: 'sq-service-related-field',
                        value: changedGeneralSettings.allowFirstServicePaymentDelay,
                        label: 'generalSettings.allowFirstServicePaymentDelay.label',
                        description: 'generalSettings.allowFirstServicePaymentDelay.description',
                        onChange: (value) => handleGeneralSettingsChange('allowFirstServicePaymentDelay', value)
                    }),
                    generator.createToggleField({
                        className: 'sq-service-related-field',
                        value: changedGeneralSettings.allowServiceRegItems,
                        label: 'generalSettings.allowServiceRegItems.label',
                        description: 'generalSettings.allowServiceRegItems.description',
                        onChange: (value) => handleGeneralSettingsChange('allowServiceRegItems', value)
                    }),
                    generator.createTextField({
                        value: changedGeneralSettings.defaultServicesEndDate,
                        className: 'sq-text-input sq-default-services-end-date sq-service-related-field',
                        label: 'generalSettings.defaultServicesEndDate.label',
                        description: 'generalSettings.defaultServicesEndDate.description',
                        onChange: (value) => handleGeneralSettingsChange('defaultServicesEndDate', value)
                    }),

                )
            }

            pageInnerContent?.append(
                generator.createMultiItemSelectorField({
                    name: 'countries-selector',
                    label: 'countries.selector.label',
                    description: 'countries.selector.description',
                    value: changedCountryConfiguration.map((country) => country.countryCode).join(','),
                    options: data.sellingCountries.map((country) => ({ label: country.name, value: country.code })),
                    onChange: handleCountryChange
                })
            );

            renderCountries();
            data.sellingCountries.length !== 0 && renderControls();

            maybeShowServiceRelatedFields();
        }

        /**
         * Returns shop payment method options.
         *
         * @returns {[{label: string, value: string}]}
         */
        const getPaymentMethodOptions = () => {
            const options = [{ label: "None", value: "" }];
            const paymentMethods = data.shopPaymentMethods ? data.shopPaymentMethods : [];
            paymentMethods.map((shopPaymentMethod) => {
                options.push({
                    label: shopPaymentMethod.name.charAt(0).toUpperCase() + shopPaymentMethod.name.slice(1),
                    value: shopPaymentMethod.code
                })
            })

            return options;
        }

        /**
         * Renders country fields.
         */
        const renderCountries = () => {
            changedCountryConfiguration.map((countryConfig) => {
                document.querySelector('.sq-content-inner')?.append(generator.createCountryField({
                    countryCode: countryConfig.countryCode,
                    merchantId: countryConfig.merchantId,
                    onChange: (value) => handleMerchantChange(countryConfig.countryCode, value)
                }))
            })
        }

        /**
         * Renders form controls.
         */
        const renderControls = () => {
            configuration.appState === SequraFE.appStates.ONBOARDING ?
                document.querySelector('.sq-content-inner')?.append(
                    generator.createButtonField({
                        className: 'sq-continue sqm--block',
                        buttonType: 'primary',
                        buttonLabel: 'general.continue',
                        onClick: handleSave
                    })
                ) :
                document.querySelector('.sq-content')?.append(
                    generator.createPageFooter({
                        onSave: handleSave,
                        onCancel: () => {
                            const pageContent = document.querySelector('.sq-content');
                            while (pageContent?.firstChild) {
                                pageContent?.removeChild(pageContent.firstChild);
                            }

                            this.render();
                        }
                    })
                )
        }

        /**
         * Validates country configuration.
         *
         * @returns {boolean}
         */
        const isCountryConfigurationValid = () => {
            let errorCount = 0;

            if (!changedCountryConfiguration.length) {
                validator.validateRequiredField(
                    document.querySelector(`[name="countries-selector"]`),
                    'validation.requiredField'
                );

                errorCount++;
            }

            changedCountryConfiguration.map((setting) => {
                !validator.validateRequiredField(
                    document.querySelector(`[name="country_${setting.countryCode}"]`),
                    'validation.requiredField'
                ) && errorCount++;
            })

            return errorCount === 0;
        }

        /**
         * Handles changes to the merchant ids.
         *
         * @param countryCode
         * @param merchantId
         */
        const handleMerchantChange = (countryCode, merchantId) => {
            validator.validateRequiredField(
                document.querySelector(`[name="country_${countryCode}"]`),
                'validation.requiredField'
            );

            changedCountryConfiguration.find(
                (setting) => setting.countryCode === countryCode
            ).merchantId = merchantId;

            hasCountryConfigurationChanged = true;
            disableFooter(false);
        }

        /**
         * Handles configured country changes.
         *
         * @param countryCodes
         */
        const handleCountryChange = (countryCodes) => {
            validator.validateRequiredField(
                document.querySelector(`[name="countries-selector"]`),
                'validation.requiredField'
            );

            const countryElements = document.querySelectorAll('.sq-country-field-wrapper');
            countryElements.forEach((el) => el.remove());
            changedCountryConfiguration = countryCodes.map((code) => ({
                countryCode: code,
                merchantId: changedCountryConfiguration.find((config) => config.countryCode === code)?.merchantId || ''
            }));

            renderCountries();

            if (configuration.appState === SequraFE.appStates.ONBOARDING) {
                document.querySelector('.sq-continue').remove();
                renderControls();
            }

            hasCountryConfigurationChanged = true;
            disableFooter(false);
        }

        /**
         * Handles general settings changes.
         *
         * @param name
         * @param value
         */
        const handleGeneralSettingsChange = (name, value) => {
            changedGeneralSettings[name] = value;
            haveGeneralSettingsChanged = true;
            disableFooter(false);
        }


        const maybeShowServiceRelatedFields = () => {
            const selector = '.sq-field-wrapper:has(.sq-service-related-field),.sq-field-wrapper.sq-service-related-field'
            const hiddenClass = 'sqs--hidden';
            document.querySelectorAll(selector).forEach((el) => {
                if (changedGeneralSettings.enabledForServices) {
                    el.classList.remove(hiddenClass)
                } else {
                    el.classList.add(hiddenClass)
                }
            });
        }
        /**
         * Handles enabledForServices changes.
         * 
         * @param value
         */
        const handleEnabledForServicesChange = (value) => {
            // .sqs--hidden
            handleGeneralSettingsChange('enabledForServices', value)
            maybeShowServiceRelatedFields();
        }

        const areIPAddressesValid = () => {
            const valid = validator.validateIPList(changedGeneralSettings.allowedIPAddresses);
            validator.validateField(
                document.querySelector(`[name="allowedIPAddresses-selector"]`),
                !valid,
                'validation.invalidIPAddress'
            );
            return valid;
        }

        const isValidTimeDuration = () => {
            const valid = validator.validateDateOrDuration(changedGeneralSettings.defaultServicesEndDate);
            validator.validateField(
                document.querySelector('.sq-default-services-end-date'),
                !valid,
                'validation.invalidTimeDuration'
            );

            return valid;
        }

        /**
         * Verifying merchant ids for all selected countries.
         *
         * @returns {Promise<Awaited<unknown>[]>}
         */
        const validateMerchantIds = () => {
            const promises = [];

            changedCountryConfiguration.forEach((config) => {
                promises.push(api.post(
                    configuration.validateConnectionDataUrl,
                    { ...data.connectionSettings, merchantId: config.merchantId },
                    SequraFE.customHeader
                ))
            })

            return Promise.all(promises);
        }

        /**
         * Handles saving of the form.
         */
        const handleSave = () => {
            if (!isCountryConfigurationValid() || !areIPAddressesValid() || !isValidTimeDuration()) {
                return;
            }

            utilities.showLoader();

            areIPAddressesValid()
            validateMerchantIds()
                .then((results) => {
                    const hasError = results.some((result) => result.isValid === false);
                    hasError ? handleValidationError(results) : saveChangedData();
                });
        }

        /**
         * Handle merchant id validation error.
         *
         * @param {[{isValid: boolean, reason: string|null}]} results
         */
        const handleValidationError = (results) => {
            if (results[0].reason && !results[0].reason.includes('merchantId')) {
                SequraFE.responseService.errorHandler(
                    { errorCode: 'general.errors.connection.invalidUsernameOrPassword' }
                ).catch(() => {
                });

                utilities.hideLoader();

                return;
            }

            results.forEach((result, index) => {
                validator.validateField(
                    document.querySelector(`[name="country_${changedCountryConfiguration[index].countryCode}"]`),
                    !result.isValid,
                    'validation.invalidField'
                );
            });

            utilities.hideLoader();
        }

        /**
         * Save changed data.
         */
        const saveChangedData = () => {
            utilities.showLoader();

            const promises = [];

            haveGeneralSettingsChanged &&
                promises.push(api.post(configuration.saveGeneralSettingsUrl, changedGeneralSettings, SequraFE.customHeader));

            hasCountryConfigurationChanged &&
                promises.push(api.post(configuration.saveCountrySettingsUrl, changedCountryConfiguration, SequraFE.customHeader));

            Promise.all(promises)
                .then(() => {
                    disableFooter(true);
                    activeGeneralSettings = utilities.cloneObject(changedGeneralSettings);
                    activeCountryConfiguration = changedCountryConfiguration.map((utilities.cloneObject))

                    if (configuration.appState !== SequraFE.appStates.ONBOARDING && hasCountryConfigurationChanged) {
                        // if countries have been changed, payment methods should be retrieved from API again
                        SequraFE.state.setData('paymentMethods', null);
                    }

                    configuration.appState === SequraFE.appStates.SETTINGS &&
                        SequraFE.state.setData('generalSettings', activeGeneralSettings);
                    SequraFE.state.setData('countrySettings', activeCountryConfiguration);

                    haveGeneralSettingsChanged = false;
                    hasCountryConfigurationChanged = false;

                    let haveCredentialsChanges = SequraFE.state.getCredentialsChanged();
                    if (configuration.appState === SequraFE.appStates.ONBOARDING) {
                        if (haveCredentialsChanges) {
                            SequraFE.state.removeCredentialsChanged();
                            SequraFE.state.goToState(SequraFE.appStates.SETTINGS);

                            return;
                        }

                        const index = SequraFE.pages.onboarding.indexOf(SequraFE.appPages.ONBOARDING.COUNTRIES)
                        SequraFE.pages.onboarding.length > index + 1 ?
                            window.location.hash = configuration.appState + '-' + SequraFE.pages.onboarding[index + 1] :
                            window.location.hash = SequraFE.appStates.PAYMENT + '-' + SequraFE.appPages.PAYMENT.METHODS;
                    }

                    if (haveCredentialsChanges) {
                        SequraFE.state.removeCredentialsChanged();
                    }
                })
                .finally(() => {
                    configuration.appState !== SequraFE.appStates.ONBOARDING && utilities.hideLoader();
                });
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
    }

    SequraFE.GeneralSettingsForm = GeneralSettingsForm;
})();
