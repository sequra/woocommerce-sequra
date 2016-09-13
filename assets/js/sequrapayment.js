var SequraHelper = {
  postToUrl: function (e) {
    var url = typeof(e) === 'string' ? e : jQuery(e).data('url');
    var form = jQuery("<form method='post'></form>");
    form.attr('action', url).hide().appendTo('body').submit();
    return false;
  },
  /*
   * Let's define all popups in a div with class sequra_popup and a unique id
   * The link that displays the popup will have this unique id in the rel attribute
   */
  preparePopup: function () {
    jQuery('.sequra_popup').not('.sequra_popup_prepared').each(function () {
      /**
       * Avoid attaching event twice if preparePopup is called more than once.
       */
      jQuery(this).addClass('sequra_popup_prepared');
      /**
       * Create relation between link and popup
       */
      popup_identifier = jQuery(this).attr('id');
      jQuery(document).delegate("[rel=" + popup_identifier + "]", 'click', function (event) {
        jQuery('#' + jQuery(this).attr('rel')).fadeIn();
        return false;
      });

      jQuery(this).on('click', function (event) {
        jQuery(this).fadeOut();
        return false;
      });
    });

    /*
     * Add close button to popups
     */
    jQuery('.sequra_popup .sequra_white_content.closeable').each(function () {
      if(jQuery(this).children('.sq-modal-head').length>0) return;
      jQuery(this).prepend(
        '  <div class="sq-modal-head">' +
        '   <div class="sq-head-title">' +
        '     <div class="sq-product-name">'+jQuery(this).attr('title')+'</div>' + 
        '     <div class="sq-product-logo"><span class="icon-sequra-logo"></span></div>' + 
        '   </div>' +
        '  </div>' +
        '  <a class="sequra_popup_close">cerrar</a>'
      );

    });

    jQuery(".sequra_popup_close").on('click', function (event) {
      jQuery(this).parent().parent().fadeOut();
      return false;
    });   
  }
};
var SequraPartPaymentMoreInfo = {
  draw: function (generic) {
    html = null;
    if(!generic){
      html = this.get_pp_popup_html(SequraCreditAgreementsInstance.creditAgreements);
    } else {
      html = this.get_pp_generic_popup_html();
    }
    if (0 < jQuery('#sequra_partpayments_popup').length) {
      jQuery('#sequra_partpayments_popup').html(html);
    } else {
      html = '<div id="sequra_partpayments_popup" class="sequra_popup">' + html + '</div>';
      jQuery('body').append(html);
    }
    SequraHelper.preparePopup();
  },

  get_pp_popup_html: function (ca) {
    var html = '' +
        '<div class="sequra_white_content closeable" title="Fracciona tu pago">' +
        '  <div class="sequra_content_popup">' +
        '    <h4>¿En cuántas cuotas podré fraccionar mi pago?</h4>' +
        '    <div>' +
        '    Podrás elegir entre:' +
        '      <ul>';
    var max = ca.length;
    for (var i = 0; i < max; i++) {
      html += '  <li><b class="instalment_count-js">' + ca[i]['instalment_count'] + ' cuotas</b> de <b class="instalment_total-js">' + ca[i]['instalment_total']['string'] + '</b>/mes';
    }
    html +='   <ul>' +
        '      <small>* Coste único incluido: <span class="instalment_fee-js">' +  ca[0]['instalment_fee']['string'] + '</span> por cuota</small>';
    if(ca[0]['down_payment_total']['value']>ca[0]['instalment_total']['value']){
      html += '<br/><small>* El primer pago será de <span class="down_payment_total-js">' +  ca[0]['down_payment_total']['string'] + '</span> entrada incluida</small>';
    }        
    html +=' </div>' +          
        '    <h4>¿Cómo funciona?</h4>' +
        '      <div>' +
        '        <ol>' +
        '          <li>Elige "Fracciona tu pago" con SeQura al realizar tu pedido y paga sólo la primera cuota.</li>' +
        '          <li>Recibe tu pedido.</li>' +
        '          <li>El resto de pagos se cargarán automáticamente a tu tarjeta.</li>' +
        '        </ol>' +
        '      <p></p>' +        
        '     </div>' +
        '  </div>' +
        '</div>';
    return html;
  },

  get_pp_generic_popup_html: function () {
    var html = '' +
        '<div class="sequra_white_content closeable" title="Fracciona tu pago">' +
        '  <div class="sequra_content_popup">' +
        '    <h4>¿Cómo funciona?</h4>' +
        '      <div>' +
        '        <ol>' +
        '          <li>Elige "Fracciona tu pago" con SeQura al realizar tu pedido y paga sólo la primera cuota.</li>' +
        '          <li>Recibe tu pedido.</li>' +
        '          <li>El resto de pagos se cargarán automáticamente a tu tarjeta.</li>' +
        '        </ol>' +
        '     </div>' +
        '    <h4>¿Cuánto cuesta?</h4>' +
        '    <div>' +
        '      <p>El único coste es de 3 a 12€ por cuota dependiendo del importe total del pedido.</p>' +
        '      <p></p>' +
        '    </div>' +      
        '  </div>' +
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

  this.product = function(){
      return options['product'];
  };

  this.get = function (total_amount) {
    total_amount = parseInt(total_amount);
    switch (options['product']) {
        case 'pp3':
            this.creditAgreements = this.for_pp3(total_amount);
            break;
        case 'pp2':
            this.creditAgreements = this.for_pp2(total_amount);
            break;
        default:
            this.creditAgreements = this.for_pp1(total_amount);
    }
    return this.creditAgreements;
  };

  this.for_pp1 = function (total_amount) {
    var ca = [],
        instalment_fee = (total_amount < 20000 ? 300 : 500),
        down_payment_amount = 0,
        drawdown_payment_amount = total_amount - down_payment_amount,
        setup_fee = 0,
        down_payment_fees = 0,
        down_payment_total = 0,
        max = options['instalment_counts'].length;
    for (var i = 0; i < max; i++) {
      var instalment_amount = Math.floor(total_amount / options['instalment_counts'][i]),
          coc_value = instalment_fee * options['instalment_counts'][i] + down_payment_fees,
          grand_total = total_amount + coc_value,
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
        'apr': {'value': apr_value, 'string': value_to_percentage_string(apr_value)},
        'down_payment_amount': {'value': down_payment_amount, 'string': value_to_currency_string(down_payment_amount)},
        'down_payment_fees': {'value': down_payment_fees, 'string': value_to_currency_string(down_payment_fees)},
        'down_payment_total': {'value': down_payment_total, 'string': value_to_currency_string(down_payment_total)},
        'drawdown_payment_amount': {
          'value': drawdown_payment_amount,
          'string': value_to_currency_string(drawdown_payment_amount)
        },        
        'interests': {'value': 0, 'string': value_to_percentage_string(0)},
        'setup_fee': {'value': setup_fee, 'string': value_to_currency_string(setup_fee)},
        'grand_total': {'value': grand_total, 'string': value_to_currency_string(grand_total)},        
        'total_with_tax': {'value': total_amount, 'string': value_to_currency_string(total_amount)}
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
        'down_payment_amount': {'value': down_payment_amount, 'string': value_to_currency_string(down_payment_amount)},
        'down_payment_fees': {'value': down_payment_fees, 'string': value_to_currency_string(down_payment_fees)},
        'down_payment_total': {'value': down_payment_total, 'string': value_to_currency_string(down_payment_total)},
        'drawdown_payment_amount': {
          'value': drawdown_payment_amount,
          'string': value_to_currency_string(drawdown_payment_amount)
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

  this.for_pp3 = function (total_amount) {
        var ca = new Array(),
            instalment_fee = this.pp3_instalment_fee(total_amount),
            setup_fee = 0,
            down_payment_fees = setup_fee + instalment_fee,
            over_max = Math.max(0,total_amount - 120000);
            max = options['instalment_counts'].length;

        for (var i = 0; i < max; i++) {
            var instalment_count = options['instalment_counts'][i],
                down_payment_amount = this.pp3_down_payment(total_amount,instalment_count),
                drawdown_payment_amount = total_amount - down_payment_amount,
                down_payment_total = down_payment_amount + down_payment_fees,

                instalment_amount = Math.floor(drawdown_payment_amount / (instalment_count-1)),
                apr_value = apr(drawdown_payment_amount, instalment_fee, (instalment_count-1), down_payment_fees),
                coc_value = instalment_fee * (instalment_count-1) + down_payment_fees,
                grand_total = total_amount + coc_value,
                instalment_total = instalment_amount + instalment_fee;

            ca.push({
                'apr': {'value': apr_value, 'string': value_to_percentage_string(apr_value)},
                'cost_of_credit': {'value': coc_value, 'string': value_to_currency_string(coc_value)},
                'over_max':  {'value': over_max, 'string': value_to_currency_string(over_max)},
                'down_payment_amount': {'value': down_payment_amount,'string': value_to_currency_string(down_payment_amount)},
                'down_payment_fees': {'value': down_payment_fees, 'string': value_to_currency_string(down_payment_fees)},
                'down_payment_total': {'value': down_payment_total, 'string': value_to_currency_string(down_payment_total)},
                'drawdown_payment_amount': {
                    'value': drawdown_payment_amount,
                    'string': value_to_currency_string(drawdown_payment_amount)
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
        apr_approximation_constant = (drawdown_payment - start_fee) / (instalment_amount + instalment_fee),
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

  this.pp3_instalment_fee = function (total_amount) {
    if (total_amount < 20100) return 300;
    if (total_amount < 40100) return 500;
    if (total_amount < 60100) return 700;
    if (total_amount < 80100) return 800;
    if (total_amount < 100100) return 1000;
    return 1200;
  };

  this.pp2_down_payment = function (total_amount) {
      return this.pp_down_payment(total_amount,160000,25);
  };

  this.pp3_down_payment = function (total_amount,instalment_count) {
      return this.pp_down_payment(total_amount,120000,100/instalment_count);
  };

  this.pp_down_payment = function (total_amount,max_amount,down_payment_percent) {
    over_max = Math.max(total_amount, max_amount) - max_amount;
    up_to_max = Math.min(total_amount, max_amount);

    return Math.floor((over_max + (down_payment_percent / 100) * up_to_max));
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
      selected_instalment_count = -1,
      options = { //Defaults
        container: '',
        price_container: '.sequra-product-price-js',
        creditAgreements: null,
        min_amount: 50
      };

  this.init = function () {
    window.SequraPartPaymentTeaserInstance = this;
    for (var attrname in settings) {
      options[attrname] = settings[attrname];
    }
    update();
  };

  this.preselect = function(value){
    var i;
    for (i=2;i>0;i--){
      if(options['creditAgreements'][i]['instalment_total']['value']>value*100)
        break;
    }
    jQuery('#instalment_count_selector').val(i);
    update();
  }

  this.draw = function () {
    html = '<div id="sequra_teaser_wrapper_1">';
    if (amount_total / 100 > options['min_amount']) {
      html += draw_simulator();
    } else {
      html += draw_teaser();
    }
    html += '</div>';
    html += '<div id="sequra_teaser_wrapper_2"><span class="sequra_more_info" rel="sequra_partpayments_popup" title="Más información"><span class="icon-info"></span></span><div class="sequra_small_logo">&nbsp;</div></div>';
    jQuery(options['container']).html(html);
    SequraPartPaymentMoreInfo.draw(true);
  };

  this.draw_teaser = function () {
    return '<span id="sequra_partpayment_teaser_low">* Fracciona tu pago desde ' + options['min_amount'] + ' €</span>';
  };

  this.draw_simulator = function () {
    var product = options['creditAgreements'][0]['product'];
    var max = options['creditAgreements'].length;
    var selected_instalment_count = jQuery('#instalment_count_selector').val();
    var html;

    html = '<select id="instalment_count_selector" onchange="SequraPartPaymentTeaserInstance.update()">';
    html += '<option value="-1">--</option>';
    for (var i = 0; i < max; i++) {
      html += '<option value="'+i+'"'+(i==selected_instalment_count?' selected ':'')+'>' + options['creditAgreements'][i]['instalment_count'] + '</option>';
    }
    html += '</select> cuotas de ';
    if (0 < jQuery('#instalment_count_selector').length && 0 <= jQuery('#instalment_count_selector').val()) {
      var ca = options['creditAgreements'][selected_instalment_count];
      html += '<b class="sequra-pricelike instalment_total-js">' + ca['instalment_total']['string'] + '</b>/mes';
      if ('pp2' == product) {
        html += '<p>(Entrada <span class="down_payment_total-js">' +  ca['down_payment_total']['string'] + '</span>)</p>' +
            '<div id="partpayment_summary">' +
            '<b>Resumen del pago</b><br/>' +
            'Paga ahora <span class="down_payment_total-js">' +  ca['down_payment_total']['string'] + '</span> y después <span class="instalment_count-js">' + ca['instalment_count'] + '</span> mensualidades de <span class="instalment_total-js">' + ca['instalment_total']['string'] + '</span><br/>';
        if (ca['setup_fee'] && 0 == ca['setup_fee']['value']) {
            html += 'Incluido el coste de <span class="instalment_fee-js">' + ca['instalment_fee']['string'] + '</span> por cuota. Sin intereses';
        } else {
            html += '<span class="setup_fee-js">' + ca['setup_fee']['string'] + ' de gastos de gestión incluidos en la entrada más <span class="instalment_fee-js">' + ca['instalment_fee']['string'] + '</span>/cuota. Sin intereses ';
        }
      } else {
        html += '<br/><small>* Coste único incluido: <span class="instalment_fee-js">' +  ca['instalment_fee']['string'] + '</span> por cuota</small>';
        if(ca['down_payment_total']['value']>ca['instalment_total']['value']){
          html += '<br/><small>* El primer pago será de <span class="down_payment_total-js">' +  ca['down_payment_total']['string'] + '</span> entrada incluida</small>';
        }
      }
    } else {
      html += ' -- €/mes';
    }
    return html;
  };

  this.update = function () {
    amount_total = SequraCreditAgreementsInstance.currency_string_to_value(jQuery(options['price_container']).text());
    options['creditAgreements'] = SequraCreditAgreementsInstance.get(amount_total);
    draw();
  };

  self.init();
};
