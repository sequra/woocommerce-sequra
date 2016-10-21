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
      html = this.get_pp_checkout_popup_html(SequraCreditAgreementsInstance.creditAgreements);
    }else{
      html = this.get_pp_popup_html(SequraCreditAgreementsInstance.creditAgreements);
    }

    if (0 < jQuery('#sequra_partpayments_popup').length) {
      jQuery('#sequra_partpayments_popup').html(html);
    } else {
      html = '<div id="sequra_partpayments_popup" class="sequra_popup">' + html + '</div>';
      jQuery('body').append(html);
    }
    SequraHelper.preparePopup();
  },

  get_pp_checkout_popup_html: function (ca) {
    var html = '' +
        '<div class="sequra_white_content closeable" title="Fracciona tu pago">' +
        '  <div class="sequra_content_popup">' +
        '    <h4>¿En cuantas cuotas podré fraccionar mi pago?</h4>' +
        '    <div >' +
        '      <h5>Podrás elegir entre:</h5>' +
        '      <ul>';
    var max = ca.length;
    if(ca[0]['down_payment_total'].value>ca[0].instalment_total.value) {
      for (var i = 0; i < max; i++) {
        html += '  <li><b class="instalment_count-js">' + ca[i]['instalment_count'] + ' cuotas:</b>'+
                '    <ul>' +
                '      <li>· <span class="down_payment_total-js">' +  ca[i].down_payment_total.string + '</span> ahora' +
                '      <li>· y después ' + (ca[i]['instalment_count']-1) + ' cuotas de <b class="instalment_total-js">' + ca[i]['instalment_total']['string'] + '</b>/mes</li>' +
                '    </ul>' +
                '  </li>';
      }
      html += '</ul>';
    } else {
      for (var i = 0; i < max; i++) {
        html += '  <li><b class="instalment_count-js">' + ca[i]['instalment_count'] + ' cuotas</b> de <b class="instalment_total-js">' + ca[i]['instalment_total']['string'] + '</b>/mes';
      }
      html += '   <ul>';
    }
    html +=' </div>';
    if(ca[0].total_with_tax.value>ca[0].min_amount.value){
      html += '' +
        '    <div id="sequra_pp_example">' +
        '      <p>' +
        '        * Coste único incluido: <span class="instalment_fee-js">' +  ca[0]['instalment_fee']['string'] + '</span> por cuota' +
        '      </p>';
      if(ca[0]['down_payment_total'].value>ca[0].instalment_total.value){
        html += '' +
        '      <div class="small">' +
        '        Para este producto fraccionamos hasta <span class="max_amount-js">' +  ca[0].max_amount.string + '</span>' +
        '        por lo que <span class="over_max-js">' +  ca[0].over_max.string + '</span>' +
        '        se añaden a la primera cuota.' +
        '      </div>';
      }             
      html += '' + 
        '    </div>';
    }
    html += '' +   
        '  </div>' +
        '</div>';
    return html;
  },

  get_pp_popup_html: function (ca) {
    var html = '' +
        '<div class="sequra_white_content closeable" title="Fracciona tu pago">' +
        '  <div class="sequra_content_popup">' +
        '    <h4>¿Cómo funciona?</h4>' +
        '    <div>' +
        '      <ol>' +
        '        <li><span>Eliges "Fracciona tu pago" al realizar tu pedido y pagas sólo la primera cuota.</span></li>' +
        '        <li><span>Recibes tu pedido.</span></li>' +
        '        <li><span>El resto de pagos se cargarán automáticamente a tu tarjeta.</span></li>' +
        '      </ol>' +
        '    </div>';
    if(ca[0].total_with_tax.value>ca[0].min_amount.value){
      html += '' +
          '    <div id="sequra_pp_example">' +
          '      <p>' +
          '        Para <span class="total_with_tax-js">' + ca[0].total_with_tax.string + '</span> el coste es de ' +
          '        <span class="instalment_fee-js">' + ca[0].instalment_fee.string + '</span>/cuota ' +
          '        con lo que pagarías un total de <span class="grand_total-js">' + ca[0].grand_total.string + '</span>' +
          '      </p>';
      if(ca[0]['down_payment_total'].value>ca[0].instalment_total.value){
        html += '' +
            '      <div class="small">' +
            '        Para este producto fraccionamos hasta <span class="max_amount-js">' +  ca[0].max_amount.string + '</span>' +
            '        por lo que <span class="over_max-js">' +  ca[0].over_max.string + '</span>' +
            '        se añaden a la primera cuota que es de  <span class="down_payment_total-js">' +  ca[0].down_payment_total.string + '</span>.' +
            '      </div>';
      }
      html += '' +
          '    </div>';
    }
    html += '' +
        '  </div>' +
        '</div>';
    return html;
  }
};

SequraCreditAgreements = function (settings) {
  var self = this,
      creditAgreements = null,
      costs = null;
      options = {
        costs_json: null,
        product: 'pp3',
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
    costs = options['costs_json'][this.product()];
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
    this.creditAgreements = this.for_pp(total_amount);
    return this.creditAgreements;
  };

  this.for_pp = function (total_amount) {
    var ca = new Array(),
        instalment_fee = this.pp_instalment_fee(total_amount),
        setup_fee = 0,
        down_payment_fees = setup_fee + instalment_fee,
        over_max = Math.max(0,total_amount - costs.max_amount);
        max = costs.instalment_counts.length;

    for (var i = 0; i < max; i++) {
        var instalment_count = costs.instalment_counts[i],
            down_payment_amount = this.pp_down_payment(total_amount,instalment_count),
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
            'min_amount': {'value': costs.min_amount, 'string':value_to_currency_string(costs.min_amount)},
            'max_amount': {'value': costs.max_amount, 'string':value_to_currency_string(costs.max_amount)},
            'over_max':  {'value': over_max, 'string': value_to_currency_string(over_max)},
            'down_payment_amount': {'value': down_payment_amount,'string': value_to_currency_string(down_payment_amount)},
            'down_payment_fees': {'value': down_payment_fees, 'string': value_to_currency_string(down_payment_fees)},
            'down_payment_total': {'value': down_payment_total, 'string': value_to_currency_string(down_payment_total)},
            'drawdown_payment_amount': {'value': drawdown_payment_amount, 'string': value_to_currency_string(drawdown_payment_amount)},
            'grand_total': {'value': grand_total, 'string': value_to_currency_string(grand_total)},
            'instalment_amount': {'value': instalment_amount, 'string': value_to_currency_string(instalment_amount)},
            'instalment_count': costs.instalment_counts[i],
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

  this.pp_instalment_fee = function (total_amount) {
    for(i in costs.fees_table)
      if (total_amount < costs.fees_table[i][0]) return costs.fees_table[i][1];
    return costs.fees_table[costs.fees_table.length-1][1]
  };

  this.pp_down_payment = function (total_amount,instalment_count) {
    over_max = Math.max(total_amount, costs.max_amount) - costs.max_amount;
    up_to_max = Math.min(total_amount, costs.max_amount);

    return Math.floor((over_max + up_to_max/instalment_count));
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
        creditAgreements: null,
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
      if(options.creditAgreements[i].instalment_total.value>value)
        break;
    }
    jQuery('#instalment_count_selector').val(i);
    update();
  }

  this.draw = function () {
    info_icon_html = '' +
           '  <span class="sequra_more_info" rel="sequra_partpayments_popup" title="Más información"> ' +
           '    <span class="icon-info"></span>' +
           '  </span>';
    html = '<fieldset id="sequra_teaser_wrapper">';
    if (amount_total > options['creditAgreements'][0].min_amount.value) {
      html += draw_simulator(info_icon_html);
    } else {
      html += draw_teaser(info_icon_html);
    }
    html += '</fieldset>' +
            '<fieldset><legend class="sequra_small_logo">&nbsp;</legend></fieldset>';
    jQuery(options['container']).html(html);
    SequraPartPaymentMoreInfo.draw(true);
  };

  this.draw_teaser = function (info_icon_html) {
    return '<ul id="sequra_partpayment_teaser_low">' +
           '  <li>' +
           '    <span>' +
           '      Fracciona tu pago desde ' + options['creditAgreements'][0].min_amount.string + 
           '    </span>' + info_icon_html +
           '  </li>' +
           '</ul> ';
  };

  this.draw_simulator = function (info_icon_html) {
    var max = options.creditAgreements.length,
        selected_instalment_count = jQuery('#instalment_count_selector').val(),
        ca = options.creditAgreements[selected_instalment_count?selected_instalment_count:(max-1)],
        html,
        select_html;
 
    select_html = '<select id="instalment_count_selector" onchange="SequraPartPaymentTeaserInstance.update()">';
    for (var i = 0; i < max; i++) {
      select_html += '<option value="'+i+'"'+(i==selected_instalment_count?' selected ':'')+'>' + options.creditAgreements[i].instalment_count + '</option>';
    }
    select_html += '</select>';

    if(ca.down_payment_total.value>ca.instalment_total.value){
      html = '<legend id="sequra_teaser_head"> o bien </legend>'+
             'fracciona en ' + select_html + ' cuotas' + info_icon_html +
             '<ul>'+
             '  <li><span><span class="down_payment_total-js">' +  ca.down_payment_total.string + '</span> ahora</span></li>' +
             '  <li><span>Y después ' + (ca.instalment_count-1) + ' cuotas de <span class="instalment_total-js">' +  ca.instalment_total.string + '</span></li>' +
             '</ul>';
    } else {
      html = '<legend id="sequra_teaser_head"> o solo </legend>'+
             '<span class="instalment_total-js">' +  ca.instalment_total.string + '</span>/mes en ' + select_html + ' cuotas' + info_icon_html +
             '<ul>'+
             '  <li><span>Coste único incluido de <span class="instalment_fee-js">' +  ca.instalment_fee.string + '</span> por cuota</span></li>' +
             '</ul>';      
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