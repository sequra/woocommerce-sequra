(function () {
    const ClassicCheckout = {
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

        bindEvents: function () {
            const sqProductOptions = document.querySelectorAll('.sequra-payment-method__input');
            const sqPaymentMethodId = 'payment_method_sequra';
            const paymentMethods = document.querySelectorAll('[name="payment_method"]');

            let isSqPaymentMethodChecked = false;
            paymentMethods.forEach(paymentMethod => {
                if (paymentMethod.id === sqPaymentMethodId && paymentMethod.checked) {
                    isSqPaymentMethodChecked = true;
                    if (sqProductOptions) {
                        sqProductOptions[0].checked = true;
                    }
                }

                paymentMethod.addEventListener('change', e => {
                    if (e.target.id === sqPaymentMethodId || !e.target.checked) {
                        return
                    }
                    sqProductOptions.forEach(sqProductOption => sqProductOption.checked = false);
                })
            });

            if (!isSqPaymentMethodChecked) {
                // Uncheck seQura payment methods that remain checked.
                sqProductOptions.forEach(sqProductOption => sqProductOption.checked = false);
            } else {
                // Uncheck all the other payment methods that remain checked
                paymentMethods.forEach(paymentMethod => {
                    if (paymentMethod.id !== sqPaymentMethodId && paymentMethod.checked) {
                        paymentMethod.checked = false;
                    }
                });
            }

            sqProductOptions.forEach(sqProductOption => sqProductOption.addEventListener('change', e => {
                if (!e.target.checked) {
                    return
                }

                paymentMethods.forEach(paymentMethod => {
                    paymentMethod.checked = paymentMethod.id === sqPaymentMethodId;
                    if (this.isJQueryActive()) {
                        jQuery(paymentMethod).trigger('change');
                    } else {
                        paymentMethod.dispatchEvent(new Event('change'));
                    }
                })
                if (this.isJQueryActive()) {
                    jQuery(document.body).trigger('payment_method_selected');
                }
            }))

        },
        isJQueryActive: () => 'undefined' !== typeof jQuery,
    }

    document.addEventListener('DOMContentLoaded', () => ClassicCheckout.init());
})();