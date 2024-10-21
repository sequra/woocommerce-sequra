(function () {
    const ClassicCheckout = {

        SQ_PAYMENT_METHOD_ID: 'payment_method_sequra',

        init: function () {
            if ('undefined' !== typeof SeQuraCheckout && SeQuraCheckout.isBlockCheckout) {
                return
            }

            this.bindEvents();

            if (this.isJQueryActive()) {
                jQuery(document.body).on('updated_checkout', e => {
                    this.bindEvents()
                });
            }
        },

        /**
         * Save selected SeQura payment method in the context.
         * @param {string|null} id
         */
        saveCheckedPaymentOpt: function (id) {
            if ('undefined' === typeof SeQuraCheckout) {
                return;
            }
            SeQuraCheckout.selectedPaymentMethod = id;
        },

        /**
         * Get selected SeQura payment method from the context.
         * @returns {string|null}
         */
        getCheckedPaymentOpt: function () {
            if ('undefined' === typeof SeQuraCheckout) {
                return null
            }
            return SeQuraCheckout.selectedPaymentMethod;
        },

        checkSqProductOption: function (sqProductOptions) {
            let selectedPaymentMethod = this.getCheckedPaymentOpt();

            if (selectedPaymentMethod && !document.querySelector(`#${selectedPaymentMethod}`)) {
                selectedPaymentMethod = null;
                this.saveCheckedPaymentOpt(selectedPaymentMethod);
            }

            sqProductOptions.forEach((sqProductOption, i) => {
                let checked = sqProductOption.id === selectedPaymentMethod;
                if (i === 0 && !selectedPaymentMethod) {
                    checked = true;
                    this.saveCheckedPaymentOpt(sqProductOption.id);
                }
                sqProductOption.checked = checked;
            });
        },

        /**
         * Uncheck all SeQura payment options and reset the selected one.
         */
        uncheckSqOptions: function (sqProductOptions) {
            this.saveCheckedPaymentOpt(null);
            sqProductOptions.forEach(sqProductOption => sqProductOption.checked = false);
        },

        /**
         * Select SeQura payment method and option based on the current context.
         */
        maybeSelectSeQura: function (sqPaymentMethodInput, sqProductOptions, paymentMethods) {
            const isChecked = sqPaymentMethodInput && sqPaymentMethodInput.checked;
            const optionChecked = this.getCheckedPaymentOpt();
            if (!isChecked && !optionChecked) {
                return;
            }

            this.updateCheckedPaymentMethods(paymentMethods);
            this.checkSqProductOption(sqProductOptions);
        },

        removeInputRadioClass: function (sqPaymentMethodInput) {
            if (sqPaymentMethodInput) {
                sqPaymentMethodInput.classList.remove('input-radio');
            }
        },

        /**
         * Watch for changes in the WooCommerce payment methods 
         * and uncheck SeQura options if another payment method is selected.
         */
        addPaymentMethodChangeListener: function (paymentMethods, sqProductOptions) {
            paymentMethods.forEach(paymentMethod => {
                paymentMethod.addEventListener('change', e => {
                    if (e.target.id !== this.SQ_PAYMENT_METHOD_ID && e.target.checked) {
                        this.uncheckSqOptions(sqProductOptions);
                    }
                });
            });
        },

        updateCheckedPaymentMethods: function (paymentMethods, emitEvent = false) {
            paymentMethods.forEach(paymentMethod => {
                const checked = paymentMethod.id === this.SQ_PAYMENT_METHOD_ID;
                if (checked === paymentMethod.checked) {
                    return;
                }
                paymentMethod.checked = checked;

                if (emitEvent) {
                    if (this.isJQueryActive()) {
                        jQuery(paymentMethod).trigger('change');
                    } else {
                        paymentMethod.dispatchEvent(new Event('change'));
                    }
                }
            })
        },

        /**
         * Watch for changes in the SeQura product options selection
         * to save the selected payment method in the context and
         * trigger proper events.
         */
        addSqProductOptionChangeListener: function (sqProductOptions, paymentMethods) {
            sqProductOptions.forEach(sqProductOption => sqProductOption.addEventListener('change', e => {
                if (!e.target.checked) {
                    return
                }

                this.saveCheckedPaymentOpt(sqProductOption.id);
                this.updateCheckedPaymentMethods(paymentMethods, true);

                if (this.isJQueryActive()) {
                    jQuery(document.body).trigger('payment_method_selected');
                }
            }));
        },

        bindEvents: function () {
            const sqProductOptions = document.querySelectorAll('.sequra-payment-method__input');
            const paymentMethods = document.querySelectorAll('[name="payment_method"]');
            const sqPaymentMethodInput = document.getElementById(this.SQ_PAYMENT_METHOD_ID);

            this.removeInputRadioClass(sqPaymentMethodInput);
            this.maybeSelectSeQura(sqPaymentMethodInput, sqProductOptions, paymentMethods);
            this.addPaymentMethodChangeListener(paymentMethods, sqProductOptions);
            this.addSqProductOptionChangeListener(sqProductOptions, paymentMethods);
        },

        isJQueryActive: () => 'undefined' !== typeof jQuery,
    }

    document.addEventListener('DOMContentLoaded', () => ClassicCheckout.init());
})();