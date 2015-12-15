SequraFraction = function(settings){
  var self = this;
  var options = {
    lang: String(window.navigator.userLanguage || window.navigator.language).substring(0,2),
    timestamp : new Date().getTime(),
    creditAgreements: null,
    currentCreditAgreement : null,
    showExtraText: false,
    element : document.body,
    product: "pp1",
    selectCallback : null,
    formatCurrency : null,
    preselectedCreditAgreement: null
  };

  this.init = function(){
    window.SequraFractionInstance = this;

    for (var attrname in settings) {
      options[attrname] = settings[attrname];
    }
    if(!options.creditAgreements || options.creditAgreements.length<1){
      options.creditAgreements = jQuery(".formish .credit_agreements").data(options.product);
    }
    var currentCA = options.creditAgreements.length;
    if(0 <= options.preselectedCreditAgreement && options.preselectedCreditAgreement < options.creditAgreements.length){
      options.currentCreditAgreement = options.creditAgreements[options.preselectedCreditAgreement];
      currentCA = options.preselectedCreditAgreement+1;
    }else{
      for(i = 0; i < options.creditAgreements.length; i++) {
        if (options.creditAgreements[i]['default']){
          options.currentCreditAgreement = options.creditAgreements[i];
          currentCA = i+1;
        }
      }
    }

    if(!options.currentCreditAgreement){
      console.log('Error: No tax range is applicable');
      return false;
    }

    if(typeof window.SequraFractionInstance !== 'undefined'){
      options.element.innerHTML = null;
    }

    self.draw();

    options.dragdealer = new Dragdealer('sequra-fraction-slider-'+options.timestamp, {
      steps: options.creditAgreements.length,
      callback: self.onSelect,
      animationCallback: self.whileDragged,
      requestAnimationFrame: true,
      speed: 0.4
    });
    self.terms_calculation();
    options.dragdealer.setStep(currentCA);
  };

  this.terms_calculation = function(){
    jQuery('.sequra_partpayment_instalment_total-js').html(options.currentCreditAgreement["instalment_total"]["string"]);
    jQuery('.sequra_partpayment_apr-js').html(options.currentCreditAgreement["apr"]["string"]);
    jQuery('.sequra_partpayment_cost_of_credit-js').html(options.currentCreditAgreement["cost_of_credit"]["string"]);
    jQuery('.sequra_partpayment_instalment_fee-js').html(options.currentCreditAgreement["instalment_fee"]["string"]);
    jQuery('.sequra_partpayment_instalment_count-js').html(options.currentCreditAgreement["instalment_count"]);
    jQuery('.sequra_partpayment_instalment_amount-js').html(options.currentCreditAgreement["instalment_total"]["string"]);
    if (options.currentCreditAgreement["down_payment_total"]) {
      jQuery('.sequra_partpayment_down_payment_amount-js').html(options.currentCreditAgreement["down_payment_total"]["string"]);
    }
  };

  this.toFloat = function(value){
    return parseFloat((parseInt(value)*0.01).toFixed(2));
  };

  this.formatCurrency = function(value){
    var floatVal = self.toFloat(value);
    if(typeof options.formatCurrency === 'function'){
      return options.formatCurrency(floatVal);
    } else {
      return floatVal.toFixed(2)+'€';
    }
  };


  this.draw = function(){
    var current = options.currentCreditAgreement;
    var html = '<div id="sequra-fraction-'+options.timestamp+'" class="sequra-fraction-wrapper">'
        + '           <div class="sequra-fraction-choose">'
        + '             Elige cuánto quieres pagar cada mes'
        + '           </div>'
        + '            <table style="margin: 0 auto;">'
        + '             <tr>'
        + '               <td class="sequra-fraction-title">Importe total de tu pedido: </td>'
        + '               <td class="sequra-fraction-total">'+current["total_with_tax"]["string"]+'</td>'
        + '             </tr>';
    if (options.product==="pp2") {
      html += ''
          + '             <tr>'
          + '               <td class="sequra-fraction-title">Entrada (a pagar ahora): </td>'
          + '               <td class="sequra-fraction-total">'+current["down_payment_total"]["string"]+'</td>'
          + '             </tr>'
          + '             <tr>'
          + '               <td class="sequra-fraction-title">A pagar en cuotas: </td>'
          + '               <td class="sequra-fraction-total">'+current["drawdown_payment_amount"]["string"]+'</td>'
          + '             </tr>';
    };
    html += ''
        + '           </table>'
        + '           <div id="sequra-fraction-slider-'+options.timestamp+'" class="dragdealer">'
        + '             <div class="handle"></div>'
        + '             <table class="dots-line">'
        + '               <tr>';

    for(var i = 0 ; i < options.creditAgreements.length; i++){
      html += '         <td class="'
          + (i === 0 ? 'first' : ( i + 1 === options.creditAgreements.length ? 'last' : ''))
          + '"><span class="dot" onclick="window.SequraFractionInstance.selectCA('+i+')"></span></td>';
    }

    html += '         </tr>'
        + '             </table>'
        + '           </div>'
        + '           <table class="sequra-fraction-quotas">'
        + '             <tr>';

    for(var i = 0 ; i < options.creditAgreements.length; i++){
      html+= '        <td class="sequra-fraction-quota-item-wrapper">'
          + '               <div onclick="window.SequraFractionInstance.selectCA('+i+')" id="sequra-fraction-quota-item-'+i+'" class="sequra-fraction-quota-item '+(i == 0 ? 'selected' : '')+'">'
          + '                 <div class="arrow"></div>'
          + '                 <div class="upper-btn">'
          + '                   '+options.creditAgreements[i]['instalment_count']+'  cuotas'
          + '                 </div>'
          + '                 <div class="lower-btn">'
          + '                   de '+options.creditAgreements[i]['instalment_total']['string']
          + '                 </div>'
          + '               </div>'
          + '             </td>';
    }

    html += '     </tr>'
        + '          </table>'
        + '          <div class="sq_row sequra-extra-info">Sin intereses. '
        + ' Coste de '+ current["instalment_fee"]["string"] +' por cuota incluido.';
    if (options.product==="pp2") {
      html += '<br />';
      if (current["down_payment_fees"]["value"] == current["instalment_fee"]["value"]) {
        html += ' Este coste también se cobrará por la entrada.';
      } else{
        html += ' La entrada incluye '+ current["down_payment_fees"]["string"] +' referentes a gastos de gestión.';
      };
    }
    html += ''
        + '           <br/>'
        + '           <br/>'
        + '          <small><a href="javascript:void(0)" onclick="window.SequraFractionInstance.showPopup()">Más información</a> sobre la TAE</small>'
        + '          </div>'
        + '         </div>';

    // console.log(html);
    self.appendHtml(options.element,html);
  };

  this.showPopup = function() {
    var html = '  <div class="sequra_popup" id="sequra_partpayments_agreement_popup">'
        + '    <div class="sequra_white_content closeable">'
        + '       <a class="sequra_popup_close_delegatedless" onclick="window.SequraFractionInstance.hidePopup()" href="javascript:void(0)">close</a>'
        + '        <div class="sequra_content_popup">'
        + '            <h1>Con este servicio puedes comprar ahora y dividir el pago en cuotas</h1>'
        + '            <div>'
        + '                <p>Para este servicio nuestro partner <a href="https://sequra.es/es/fraccionados" target="_blank">SeQura</a>'
        + '                    aplica un pequeño coste fijo a cada cuota. Para tu compra es de <span>'+options.currentCreditAgreement['instalment_fee']['string']+'</span> por cuota.'
        + '                    No hay ninguna otra comisión. Puedes pagar el total del importe cuando desees.'
        + '                </p>'
        + '                <p>En total habrás pagado <span>'+options.currentCreditAgreement['grand_total']['string']+'</span>, de los cuales <span>' + options.currentCreditAgreement['cost_of_credit']['string'] +'</span>'
        + '                    son comisión.'
        + '                </p>'
        + '                <p>Para esta compra la <abbr title="Tasa Anual Equivalente">TAE</abbr> es <span>'+ options.currentCreditAgreement['apr']['string'] + '</span>. La TAE no es el interés de la compra, ya que no'
        + '                    hay interés (el "TIN" o Tipo de Interés Nominal es fijo al 0%), pero se utiliza para comparar créditos. <em>Este número acostumbra a ser más alto cuando'
        + '                    el plazo de pago es más corto.</em>'
        + '                </p>'
        + '            </div>'
        + '        </div>'
        + '    </div>'
        + '</div>';
    console.log("MEC");
    self.appendHtml(document.body,html);
  };

  this.hidePopup = function(){
    var elem = document.getElementById('sequra_partpayments_agreement_popup');
    elem.parentNode.removeChild(elem);
  };

  this.appendHtml = function(el, str) {
    var div = document.createElement('div');
    div.innerHTML = str;
    while (div.children.length > 0) {
      el.appendChild(div.children[0]);
    }
  };

  this.whileDragged = function(){
    if (typeof options.dragdealer !== 'undefined'){
      self.adjustVisualElements();
    }
  };

  this.adjustVisualElements = function(){
    var currentCA = options.dragdealer.getStep()[0] - 1;
    options.currentCreditAgreement = options.creditAgreements[currentCA];
    for(var i = 0; i < options.creditAgreements.length; i++){
      var aBtn = document.getElementById('sequra-fraction-quota-item-'+i);
      aBtn.className = String(aBtn.className).replace('selected','');
    }

    var currentBtn = document.getElementById('sequra-fraction-quota-item-'+currentCA);
    currentBtn.className = currentBtn.className + ' selected';

    self.terms_calculation();
  };

  this.onSelect = function(){
    self.adjustVisualElements();

    if(typeof options.selectCallback === 'function'){
      options.selectCallback(options.currentCreditAgreement['instalment_count'],options.currentCreditAgreement['instalment_total']['string']);
    }
  };

  this.selectCA = function(index){
    options.dragdealer.setStep(index + 1);
  };

  this.partpayment_details_getter = function(){
    return options.currentCreditAgreement;
  };

  self.init();

};