(function () {
    const ClassicCheckout = {
        init: function () {
            if (!this.isClassicCheckout()) {
                return
            }

            this.bindEvents();

            if (this.isJQueryActive()) {
                jQuery(document.body).on('updated_checkout', e => {
                    console.log('updated_checkout')

                    this.bindEvents()

                });
            }
        },

        bindEvents: function () {
            const sqProductOptions = document.querySelectorAll('.sequra-payment-method__input');
            const sqPaymentMethodId = 'payment_method_sequra';
            const paymentMethods = document.querySelectorAll('[name="payment_method"]');

            paymentMethods.forEach(paymentMethod => {
                if (paymentMethod.id === sqPaymentMethodId && paymentMethod.checked) {
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
            }))

        },
        isClassicCheckout: () => !window.wc || !window.wc.blocksCheckout,
        isJQueryActive: () => 'undefined' !== typeof jQuery,
    }

    document.addEventListener('DOMContentLoaded', () => ClassicCheckout.init());
})();