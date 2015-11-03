var SequraHelper = {
    postToUrl: function(e) {
        var url = typeof(e) === 'string' ? e : jQuery(e).data('url');
        var form = jQuery("<form method='post'></form>");
        form.attr('action', url).hide().appendTo('body').submit();
        return false;
    },
    /*
     * Let's define all popups in a div with class sequra_popup and a unique id
     * The link that displays the popup will have this unique id in the rel attribute
     */
    preparePopup: function() {
        jQuery('.sequra_popup').not('.sequra_popup_prepared').each(function (){
            /**
             * Avoid attaching event twice if preparePopup is called more than once.
             */
            jQuery(this).addClass('sequra_popup_prepared');
            /**
             * Create relation between link and popup
             */
            popup_identifier = jQuery(this).attr('id');
            jQuery(document).delegate("*[rel="+popup_identifier+"]", 'click', function() {
                jQuery('#'+jQuery( this ).attr('rel')).fadeIn();
                return false;
            });
        });

        /*
         * Add close button to popups
         */
        jQuery('.sequra_popup .sequra_white_content.closeable').each(function (){
            jQuery(this).prepend('<a class="sequra_popup_close">close</a>');
        });

        jQuery(document).delegate(".sequra_popup_close", 'click', function() {
            jQuery(this).parent().parent().fadeOut();
            return false;
        });
    },

    preparePartPaymentAcordion: function(jump) {
        function displayFirstStep() {
            jQuery("#sequra_partpayment_alt_tittle").hide();
            jQuery("#sequra_partpayment_tittle").show();
            jQuery("#first_step_content").slideDown()
            jQuery("#second_step_content").slideUp()
        };
        function displaySecondStep() {
            jQuery("#sequra_partpayment_tittle").hide();
            jQuery("#first_step_content").slideUp();
            jQuery("#sequra_partpayment_alt_tittle").show();
            jQuery("#second_step_content").slideDown()
        };
        function jumpToSecondStep() {
            jQuery("#sequra_partpayment_tittle").hide();
            jQuery("#first_step_content").hide();
            jQuery("#sequra_partpayment_alt_tittle").show();
            jQuery("#second_step_content").show();
        };
        jQuery(document).delegate("#sequra_partpayment_tittle2, #part_payment_last_step", 'click', function() {
            displaySecondStep();
            return false;
        });
        jQuery(document).delegate("#sequra_partpayment_alt_tittle,#sequra_partpayment_tittle", 'click', function() {
            displayFirstStep();
            return false;
        })
        jQuery("#sequra_partpayment_alt_tittle").hide();

        jQuery(document).delegate('#sequra_partpayment_tittle2','click', function () {
            displaySecondStep();
        });
        jQuery(document).delegate('#part_payment_last_step','click', function () {
            displaySecondStep();
        });
        jQuery(document).delegate('#sequra_partpayment_alt_tittle','click', function () {
            displayFirstStep();
        }).hide();
        if (jump) {
            jumpToSecondStep();
        }
    }
};

var SequraPartPaymentMoreInfo = {
    draw: function () {
        ca = SequraCreditAgreementsInstance.creditAgreements;
        if ('pp1' == ca['product']) {
            html = this.get_pp1_popup_html(ca);
        } else {
            html = this.get_pp2_popup_html(ca);
        }
        if (0<jQuery('#sequra_partpayments_popup').length) {
            jQuery('#sequra_partpayments_popup').html(html);
        } else {
            html = '<div id="sequra_partpayments_popup" class="sequra_popup">' + html + '</div>';
            jQuery('body').append(html);
        }
        SequraHelper.preparePopup();
    },

    get_pp1_popup_html: function (ca) {
        var html = '' +
            '<div class="sequra_white_content closeable">' +
            '  <div class="sequra_content_popup">' +
            '    <h4>Elige cuánto quieres pagar cada vez</h4>' +
            '    <p class="sequra_colored_text">Puedes elegir entre 3, 6 ó 12 cuotas mensuales</p>' +
            '    <div>' +
            '      <ul>' +
            '        <li>Fácil de usar y sencillo. Tu compra hecha en menos de un minuto.</li>' +
            '        <li>El único coste es de 3€ por cuota o de 5€ si el pedido vale más de 200€. Sin intereses ocultos ni letra pequeña.</li>' +
            '        <li>La aprobación es instantánea, por lo que los productos se envían de forma inmediata.</li>' +
            '        <li>El primer pago se hace con tarjeta, pero los siguientes se pueden hacer con tarjeta o por transferencia bancaria.</li>' +
            '        <li>Puedes pagar la totalidad cuando tú quieras.</li>' +
            '        <li>Disponible para compras superiores a 50€.</li>' +
            '        <li>El servicio es ofrecido conjuntamente con <a class="sequra_blank_link" href="https://sequra.es/es/fraccionados" target="_blank">SeQura</a></li>' +
            '      </ul>' +
            '      <p>¿Tienes alguna pregunta? Habla con nosotros a través del 93 176 00 08 o envíanos un email a clientes@sequra.es.</p>' +
            '      <a href="https://www.sequra.es/es/fraccionados" target="_blank" class="button">Leer más</a>' +
            '    </div>' +
            '  </div>' +
            '  <div class="sequra_footer_popup">¿Cuánto cuesta? Para un pedido de 500€ el coste sería el siguiente:</br>' +
            '    <ul>' +
            '      <li>3 cuotas:&nbsp;&nbsp;&nbsp;Fijo TIN 0%, TAE 43,09%, Coste por cuota: 5€. Coste total del pedido: 515€</li>' +
            '      <li>6 cuotas:&nbsp;&nbsp;&nbsp;Fijo TIN 0%, TAE 32,79%, Coste por cuota: 5€. Coste total del pedido: 530€</li>' +
            '      <li>12 cuotas:&nbsp;Fijo TIN 0%, TAE 28,79%, Coste por cuota: 5€. Coste total del pedido: 560€</li>' +
            '    </ul>' +
            '  </div>' +
            '</div>';
        return html;
    },

    get_pp2_popup_html: function (ca) {
        var html = '' +
            '<div class="sequra_white_content closeable">' +
            '  <div class="sequra_content_popup">' +
            '    <h4>Fracciona tu pago con SeQura</h4>' +
            '    <div class="sequra_logo"></div>' +
            '      <div>' +
            '        <ul>' +
            '          <li>Al ir a realizar el pago, selecciona fraccionar tu compra con SeQura.</li>' +
            '          <li>La aprobación del crédito se hace de manera inmediata sin papeleos.</li>' +
            '          <li>Pagarás una entrada con tarjeta y el resto en cómodas mensualidades que se cargarán automáticamente en la misma tarjeta.</li>' +
            '          <li>Para una cantidad de <b class="total_with_tax-js">' + ca[0]['total_with_tax']['string'] + '</b>:' +
            '            <ul>' +
            '              <li>pagarás una entrada de <b class="down_payment_total-js">' + ca[0]['down_payment_total']['string'] + '</b> (incluye <span class="down_payment_fees-js">' + ca[0]['down_payment_fees']['string'] + '</span> de comisión) ahora</li>' +
            '              <li>y después podrás elegir entre:' +
            '                <ul>';
        var max = ca.length;
        for (var i = 0; i < max; i++) {
            html += '            <li><b class="instalment_count-js">' + ca[i]['instalment_count'] + ' mensualidades</b> de <b class="instalment_total-js">' + ca[i]['instalment_total']['string'] + '</b> (incluye <span class="instalment_fee-js">' + ca[i]['instalment_fee']['string'] + '</span> de comisión)</li>';
        }
        html += '            </ul>' +
            '              </li>' +
            '              <li>el total que acabarás pagando al final será el siguiente:' +
            '                <ul>';
        var max = ca.length;
        for (var i = 0; i < max; i++) {
            html += '            <li>si eliges <span class="instalment_count-js">' + ca[i]['instalment_count'] + ' mensualidades</span> pagarás <span class="grand_total-js">' + ca[i]['grand_total']['string'] + '</span> de los cuales <span class="cost_of_credit-js">' + ca[i]['cost_of_credit']['string'] + '</span> son comisión (TIN: <span class="interests-js">' + ca[i]['interests']['string'] + '</span>, TAE: <span class="apr-js">' + ca[i]['apr']['string'] + '</span>)</li>';
        }
        html += '            </ul>' +
            '              </li>' +
            '              <li>Estas cantidades incluyen todo lo que acabarás pagando y no hay más costes ocultos o letra pequeña.</li>' +
            '            </ul>' +
            '          </li>' +
            '          <li>Podrás pagar la totalidad del crédito en el momento que quieras y no te cobraremos ni costes de cancelación ni las comisiones de las mensualidades futuras que ya no pagarías. Igualmente podrás cambiar el número de mensualidades.</li>' +
            '          <li>Es un crédito sin intereses (TIN: 0%), aunque no gratis porque tiene unos costes fijos.</li>' +
            '          <li>La TAE no es el interés sino una medida que se utiliza para comparar créditos. Este número acostumbra a ser más alto cuando el plazo es más corto.</li>' +
            '          <li>¿Tienes alguna otra pregunta? Llama a SeQura al 93 176 00 08.</li>' +
            '        </ul>' +
            '     </div>' +
            '   </div>' +
            '</div>';
        return html;
    }
};


SequraCreditAgreements = function (settings) {
    var self = this,
        creditAgreements = null,
        options = {
            product: 'pp2',
            instalment_counts: [3, 6, 12],
            currency_symbol_l: '',
            currency_symbol_r: '€',
            decimal_separator: '.',
            thousands_separator: ' '
        };

    this.init = function () {
        window.SequraCreditAgreementsInstance = this;
        for (var attrname in settings) {
            options[attrname] = settings[attrname];
        }
    };

    this.get_ca_for = function (instalment_count) {
        max = creditAgreements.length;
        for (i = 0; i < max; i++) {
            if (instalment_count == creditAgreements[i])
                return creditAgreements[i];
        }
    };

    this.get = function (total_amount) {
        total_amount = parseInt(total_amount);
        if ('pp2' == options['product']) {
            this.creditAgreements = this.for_pp2(total_amount);
        } else {
            this.creditAgreements = this.for_pp1(total_amount);
        }
        return this.creditAgreements;
    };

    this.for_pp1 = function (total_amount) {
        var ca = [],
            instalment_fee = (total_amount < 20000 ? 300 : 500),
            max = options['instalment_counts'].length;
        for (var i = 0; i < max; i++) {
            var instalment_amount = Math.floor(total_amount / options['instalment_counts'][i]),
                apr_value = apr(total_amount - instalment_amount, instalment_fee, options['instalment_counts'][i] - 1);
            ca.push({
                'instalment_count': options['instalment_counts'][i],
                'instalment_amount': {'value': instalment_amount, 'string': value_to_currency_string(instalment_amount)},
                'instalment_fee': {'value': instalment_fee, 'string': value_to_currency_string(instalment_fee)},
                'instalment_total': {
                    'value': instalment_amount + instalment_fee,
                    'string': value_to_currency_string(instalment_amount + instalment_fee)
                },
                'product': options['product'],
                'apr': {'value': apr_value, 'string': value_to_percentage_string(apr_value)}
            });
        }
        return ca;
    };

    this.for_pp2 = function (total_amount) {
        var ca = new Array(),
            instalment_fee = this.pp2_instalment_fee(total_amount),
            down_payment_amount = this.pp2_down_payment(total_amount),
            drawdown_payment_amount = total_amount - down_payment_amount,
            setup_fee = (total_amount < 100000 ? 0 : 2000),
            down_payment_fees = setup_fee + instalment_fee,
            down_payment_total = down_payment_amount + down_payment_fees,
            max = options['instalment_counts'].length;

        for (var i = 0; i < max; i++) {
            var instalment_amount = Math.floor(drawdown_payment_amount / options['instalment_counts'][i]),
                apr_value = apr(drawdown_payment_amount, instalment_fee, options['instalment_counts'][i], down_payment_fees),
                coc_value = instalment_fee * options['instalment_counts'][i] + down_payment_fees,
                grand_total = total_amount + coc_value,
                instalment_total = instalment_amount + instalment_fee;

            ca.push({
                'apr': {'value': apr_value, 'string': value_to_percentage_string(apr_value)},
                'cost_of_credit': {'value': coc_value, 'string': value_to_currency_string(coc_value)},
                'down_payment_amount': {'value': down_payment_amount,'string': value_to_currency_string(down_payment_amount)},
                'down_payment_fees': {'value': down_payment_fees, 'string': value_to_currency_string(down_payment_fees)},
                'down_payment_total': {'value': down_payment_total, 'string': value_to_currency_string(down_payment_total)},
                'drawdown_payment_amount': {
                    'value': drawdown_payment_amount,
                    'string': value_to_percentage_string(drawdown_payment_amount)
                },
                'grand_total': {'value': grand_total, 'string': value_to_currency_string(grand_total)},
                'instalment_amount': {'value': instalment_amount, 'string': value_to_currency_string(instalment_amount)},
                'instalment_count': options['instalment_counts'][i],
                'instalment_fee': {'value': instalment_fee, 'string': value_to_currency_string(instalment_fee)},
                'instalment_total': {'value': instalment_total, 'string': value_to_currency_string(instalment_total)},
                'interests': {'value': 0, 'string': value_to_percentage_string(0)},
                'setup_fee': {'value': setup_fee, 'string': value_to_currency_string(setup_fee)},
                'total_with_tax': {'value': total_amount, 'string': value_to_currency_string(total_amount)}
            });
        }
        return ca;
    };

    this.apr = function (drawdown_payment, instalment_fee, instalment_count, start_fee) {
        if (!start_fee) start_fee = instalment_fee;
        var instalment_amount = drawdown_payment / instalment_count,
            apr_approximation_constant = (drawdown_payment - instalment_fee) / (instalment_amount + start_fee),
            minimum_boundary = 0.0,
            maximum_boundary = 1000.0,
            average_boundary = (minimum_boundary + maximum_boundary) / 2;

        apr_approximation_function = function (apr) {
            apr_approximated = 0;
            for (var i = 1; i <= instalment_count; i++) {
                apr_approximated += Math.pow((1 + apr), -(i / 12.0));
            }
            return apr_approximated - apr_approximation_constant;
        };

        while ((maximum_boundary - minimum_boundary) > 0.00001) {
            average_boundary = (minimum_boundary + maximum_boundary) / 2;
            var apr_minimum_boundary = apr_approximation_function(minimum_boundary),
                apr_average_boundary = apr_approximation_function(average_boundary);

            if (apr_average_boundary * apr_minimum_boundary > 0) {
                minimum_boundary = average_boundary;
            } else {
                maximum_boundary = average_boundary;
            }
        }
        return average_boundary * 100;
    };

    this.pp2_instalment_fee = function (total_amount) {
        if (total_amount < 20000) return 300;
        if (total_amount < 40000) return 500;
        if (total_amount < 60000) return 700;
        if (total_amount < 80000) return 900;
        return 1200;
    };

    this.pp2_down_payment = function (total_amount) {
        pp2_max_amount = 160000;
        pp2_down_payment_percent = 25.00;

        over_max = Math.max(total_amount, pp2_max_amount) - pp2_max_amount;
        up_to_max = Math.min(total_amount, pp2_max_amount);

        return Math.round((over_max + (pp2_down_payment_percent / 100) * up_to_max) * 100) / 100;
    };

    this.set_instalment_count = function (instalment_count) {
        options['instalment_count'] = instalment_count;
    };

    this.currency_string_to_value = function (str) {
        patt = new RegExp("[^\\" + options['decimal_separator'] + "\\d]", 'g');
        return Math.round(parseFloat(str.replace(patt, '').replace(options['decimal_separator'], '.')) * 100);
    };

    this.value_to_currency_string = function (val) {
        val = options['currency_symbol_l'] + (val / 100).toFixed(2) + options['currency_symbol_r'];
        return val.replace('.', options['decimal_separator']);
    };

    this.value_to_percentage_string = function (val) {
        val = val.toFixed(2) + ' %';
        //@todo: add thousands separator.
        return val.replace('.', options['decimal_separator']);
    };

    self.init();
};

SequraPartPaymentTeaser = function (settings) {
    var self = this,
        amount_total,
        options = { //Defaults
            container: '',
            price_container: '.sequra-product-price-js',
            creditAgreements: null
        };

    this.init = function () {
        window.SequraPartPaymentTeaserInstance = this;
        for (var attrname in settings) {
            options[attrname] = settings[attrname];
        }
        update();
    };

    this.draw = function () {//@todo: pp1 compatibility
        var max = options['creditAgreements'].length,
            html = '<p>Pagar en <select class="sequra-pricelike" id="instalment_count_selector" onchange="SequraPartPaymentTeaserInstance.update()">';
        for (var i = 0; i < max; i++) {
            html += '<option value="'+i+'">' + options['creditAgreements'][i]['instalment_count'] + '</option>';
        }
        html += '</select> mensualidades de ';
        var selected_instalment_count = -1;
        if (0<jQuery('#instalment_count_selector').length && 0<=jQuery('#instalment_count_selector').val()) {
            selected_instalment_count = jQuery('#instalment_count_selector').val();
            var ca = options['creditAgreements'][selected_instalment_count];
            html += '<b class="sequra-pricelike instalment_total-js">' + ca['instalment_total']['string'] + '</b></p>' +
                '<p>(Entrada <span class="down_payment_total-js">' +  ca['down_payment_total']['string'] + '</span>)</p>' +
                '<div id="partpayment_summary">' +
                '<b>Resumen del pago</b><br/>' +
                'Paga ahora <span class="down_payment_total-js">' +  ca['down_payment_total']['string'] + '</span> y después <span class="instalment_count-js">' + ca['instalment_count'] + '</span> mensualidades de <span class="instalment_total-js">' + ca['instalment_total']['string'] + '</span><br/>';
            if (0 == ca['setup_fee']['value']) {
                html += 'Incluido el coste de <span class="instalment_fee-js">' + ca['instalment_fee']['string'] + '</span> por cuota. Sin intereses';
            } else {
                html += '<span class="setup_fee-js">' + ca['setup_fee']['string'] + ' de gastos de gestión incluidos en la entrada más <span class="instalment_fee-js">' + ca['instalment_fee']['string'] + '</span> por cuota. Sin intereses';
            }
            html += '<a href="#" class="sequra_quotas_info" rel="sequra_partpayments_popup">+info</a>';
        } else {
            html += ' -- €';
        }
        jQuery(options['container']).html(html);
        SequraPartPaymentMoreInfo.draw();
        if(0>selected_instalment_count)
            jQuery('#instalment_count_selector').prepend('<option value="-1" selected="true">--</option>');
        else
            jQuery('#instalment_count_selector').val(selected_instalment_count);
    };

    this.update = function () {
        amount_total = SequraCreditAgreementsInstance.currency_string_to_value(jQuery(options['price_container']).text());
        options['creditAgreements'] = SequraCreditAgreementsInstance.get(amount_total);
        draw();
    };

    self.init();
};