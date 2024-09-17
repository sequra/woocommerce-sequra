(function () {
    const ClassicCheckout = {
        init: function () {
            if (!this.isClassicCheckout()) {
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
                jQuery( document.body ).trigger( 'payment_method_selected' );
            }))

        },
        // isClassicCheckout: () => !window.wc || !window.wc.blocksCheckout,
        isClassicCheckout: () => document.querySelector('#payment_method_sequra') !== null,
        isJQueryActive: () => 'undefined' !== typeof jQuery,
    }

    document.addEventListener('DOMContentLoaded', () => ClassicCheckout.init());
})();