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
                    const stealSelection = !this.isUpdatedCheckoutReselectionDisabled();
                    if(!this.isUpdatedCheckoutListenerDelayed()) {
                        this.bindEvents(stealSelection);
                        return;
                    }

                    setTimeout(() => this.bindEvents(stealSelection), this.getUpdatedCheckoutListenerDelay());
                });
            }
        },

        /**
         * Verifies if re-selecting SeQura after updated_checkout is disabled.
         * Defaults to false (current behavior) if not defined.
         *
         * @returns {boolean}
         */
        isUpdatedCheckoutReselectionDisabled: function () {
            return 'undefined' === typeof SeQuraCheckout || 'undefined' === typeof SeQuraCheckout.isUpdatedCheckoutReselectionDisabled ? false : !!SeQuraCheckout.isUpdatedCheckoutReselectionDisabled;
        },

        /**
         * Verifies if the updated checkout listener is delayed. Defaults to false if not defined.
         * 
         * @returns {boolean}
         */
        isUpdatedCheckoutListenerDelayed: function () {
            return 'undefined' === typeof SeQuraCheckout || 'undefined' === typeof SeQuraCheckout.isUpdatedCheckoutListenerDelayed ? false : !!SeQuraCheckout.isUpdatedCheckoutListenerDelayed;
        },

        /**
         * Returns the delay for the updated checkout listener. Defaults to 0 if not defined.
         * 
         * @returns {number}
         */
        getUpdatedCheckoutListenerDelay: function () {
            const delay = 'undefined' === typeof SeQuraCheckout || 'undefined' === typeof SeQuraCheckout.updatedCheckoutListenerDelay ? 0 : parseInt(SeQuraCheckout.updatedCheckoutListenerDelay, 10);
            return Number.isNaN(delay) ? 0 : delay;
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
            if ('undefined' === typeof SeQuraCheckout || 'undefined' === typeof SeQuraCheckout.selectedPaymentMethod) {
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
         * When stealSelection is false and another payment method is checked,
         * the rendered selection is respected instead of re-selecting SeQura.
         */
        maybeSelectSeQura: function (sqPaymentMethodInput, sqProductOptions, paymentMethods, stealSelection = true) {
            const isChecked = sqPaymentMethodInput && sqPaymentMethodInput.checked;
            const optionChecked = this.getCheckedPaymentOpt();
            if (!isChecked && !optionChecked) {
                return;
            }

            if (!stealSelection && !isChecked) {
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
         * Elements already bound are skipped, so calling this on every
         * updated_checkout doesn't stack duplicate listeners.
         */
        addPaymentMethodChangeListener: function (paymentMethods) {
            paymentMethods.forEach(paymentMethod => {
                if (paymentMethod.dataset.sequraChangeListener) {
                    return;
                }
                paymentMethod.dataset.sequraChangeListener = '1';
                paymentMethod.addEventListener('change', e => {
                    if (e.target.id !== this.SQ_PAYMENT_METHOD_ID && e.target.checked) {
                        this.uncheckSqOptions(document.querySelectorAll('.sequra-payment-method__input'));
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

                        if (paymentMethod.id === this.SQ_PAYMENT_METHOD_ID && paymentMethod.checked) {
                            // This refreshes the complete order button text.
                            jQuery(paymentMethod).trigger('click');
                        }
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
         * Elements already bound are skipped, so calling this on every
         * updated_checkout doesn't stack duplicate listeners.
         */
        addSqProductOptionChangeListener: function (sqProductOptions) {
            sqProductOptions.forEach(sqProductOption => {
                if (sqProductOption.dataset.sequraChangeListener) {
                    return;
                }
                sqProductOption.dataset.sequraChangeListener = '1';
                sqProductOption.addEventListener('change', e => {
                    if (!e.target.checked) {
                        return
                    }

                    this.saveCheckedPaymentOpt(sqProductOption.id);
                    this.updateCheckedPaymentMethods(document.querySelectorAll('[name="payment_method"]'), true);

                    if (this.isJQueryActive()) {
                        jQuery(document.body).trigger('payment_method_selected');
                    }
                });
            });
        },

        bindEvents: function (stealSelection = true) {
            const sqProductOptions = document.querySelectorAll('.sequra-payment-method__input');
            const paymentMethods = document.querySelectorAll('[name="payment_method"]');
            const sqPaymentMethodInput = document.getElementById(this.SQ_PAYMENT_METHOD_ID);

            this.removeInputRadioClass(sqPaymentMethodInput);
            this.maybeSelectSeQura(sqPaymentMethodInput, sqProductOptions, paymentMethods, stealSelection);
            this.addPaymentMethodChangeListener(paymentMethods);
            this.addSqProductOptionChangeListener(sqProductOptions);

            if ('undefined' !== typeof Sequra && 'function' === typeof Sequra.refreshComponents && 'function' === typeof Sequra.onLoad) {
                Sequra.onLoad(() => Sequra.refreshComponents());
            }
        },

        isJQueryActive: () => 'undefined' !== typeof jQuery,
    }

    document.addEventListener('DOMContentLoaded', () => ClassicCheckout.init());
})();