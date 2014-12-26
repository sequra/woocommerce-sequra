SequraFraction = function(settings){
  var self = this;
  var options = {
    lang: String(window.navigator.userLanguage || window.navigator.language).substring(0,2),
    totalPrice : null,
    timestamp : new Date().getTime(),
    quotas : [3, 6, 12],
    quotaPrices : [],
    taxRanges : [ {min: 0, max: 19999, tax: 300}, {min:20000,max:50000, tax: 500}],
    currentTax : false,
    showExtraText: false,
    element : document.body,
    tae : null,
    currentQuotaNumber : null,
    currentQuotaPrice : null,
    selectCallback : null,
    formatCurrency : null

  }

  this.init = function(){

    if(typeof window.SequraFractionInstance !== 'undefined'){
      console.log('window.SequraFractionInstance is defined',window.SequraFractionInstance);
      return false;
    }

    window.SequraFractionInstance = this;

    for (var attrname in settings) {
      options[attrname] = settings[attrname];
    }

    if(!options.totalPrice){
      console.log('Error: Must specify total price');
      return false;
    }

    for(var i = 0; i < options.taxRanges.length; i++){
      if(options.totalPrice >= options.taxRanges[i].min
        && options.totalPrice <= options.taxRanges[i].max){
        options.currentTax = options.taxRanges[i].tax;
        continue;
      }
    }

    if(!options.currentTax){
      console.log('Error: No tax range is applicable');
      return false;
    }

    self.draw();

    options.currentQuotaNumber = options.quotas[0];
    options.currentQuotaPrice =  parseFloat(options.quotaPrices[0].toFixed(2));
    options.tae = self.approximation(options.totalPrice, options.currentQuotaNumber, options.currentTax)

    options.dragdealer = new Dragdealer('sequra-fraction-slider-'+options.timestamp, {
                    steps: options.quotas.length,
                    callback: self.onSelect,
                    animationCallback: self.whileDragged,
                    requestAnimationFrame: true,
                    speed: 0.4
                  });
     self.terms_calculation();
  }
  
  this.terms_calculation = function(){
    document.getElementById('partPayments_total').innerHTML = self.formatCurrency(options.currentQuotaPrice * options.currentQuotaNumber);
    document.getElementById('partPayments_apr').innerHTML = self.toFloat(options.tae) + '%';
    document.getElementById('partPayments_fees').innerHTML = self.formatCurrency(options.currentTax * options.currentQuotaNumber);
    document.getElementById('partPayments_fee').innerHTML = self.formatCurrency(options.currentTax);
  }

  this.toFloat = function(value){
    return parseFloat((parseInt(value)*0.01).toFixed(2));
  }

  this.formatCurrency = function(value){
    var floatVal = self.toFloat(value)
    if(typeof options.formatCurrency === 'function'){
      return options.formatCurrency(floatVal);
    } else {
      return floatVal.toFixed(2)+'€';
    }
  }


  this.draw = function(){

    var html = '<div id="sequra-fraction-'+options.timestamp+'" class="sequra-fraction-wrapper">'
    + '           <div class="sequra-fraction-choose">'
    + '             Elige cuánto quieres pagar cada mes'
    + '           </div>'
    + '           <table width="100%">'
    + '             <tr>'
    + '               <td class="sequra-fraction-title">Importe total de tu pedido: </td>'
    + '               <td class="sequra-fraction-total">'+self.formatCurrency(options.totalPrice)+'</td>'
    + '             </tr>'
    + '           </table>'
    + '           <div id="sequra-fraction-slider-'+options.timestamp+'" class="dragdealer">'
    + '             <div class="handle"></div>'
    + '             <table class="dots-line">'
    + '               <tr>';

    for(var i = 0 ; i < options.quotas.length; i++){
      html += '         <td class="'
      + (i === 0 ? 'first' : ( i + 1 === options.quotas.length ? 'last' : ''))
      + '"><span class="dot" onclick="window.SequraFractionInstance.selectQuota('+i+')"></span></td>';
    }

    html += '         </tr>'
    + '             </table>'
    + '           </div>'
    + '           <table class="sequra-fraction-quotas">'
    + '             <tr>';

    for(var i = 0 ; i < options.quotas.length; i++){
      options.quotaPrices[i] = (options.totalPrice/options.quotas[i]) + options.currentTax ;
      html+= '        <td class="sequra-fraction-quota-item-wrapper">'
      + '               <div onclick="window.SequraFractionInstance.selectQuota('+i+')" id="sequra-fraction-quota-item-'+i+'" class="sequra-fraction-quota-item '+(i == 0 ? 'selected' : '')+'">'
      + '                 <div class="arrow"></div>'
      + '                 <div class="upper-btn">'
      + '                   '+options.quotas[i]+'  cuotas'
      + '                 </div>'
      + '                 <div class="lower-btn">'
      + '                   de '+self.formatCurrency(options.quotaPrices[i] - options.currentTax)
      + '                 </div>'
      + '               </div>'
      + '             </td>';
    }

    html += '     </tr>'
    + '          </table>'
    + '          <div class="row sequra-extra-info">'
    + '           Sin intereses. Coste de '+ self.formatCurrency(options.currentTax)+' por cuota no incluido.'
    + '           <br/>'
    + '           Total credito : <span id="sequra-total-credit">'+self.formatCurrency(options.quotaPrices[0] * options.quotas[0])+'</span>'
    + '           <br/>'
    + '           <br/>'
    + '          <small><span class="trigger" rel="sequra_partpayments_popup">Más información</span>'
    + '           sobre la TAE y nuestras comisiones transparentes.</small>'
    + '          </div>'
    + '         </div>';

    // console.log(html);
    self.appendHtml(options.element,html);
  }

  this.appendHtml = function(el, str) {
    var div = document.createElement('div');
      div.innerHTML = str;
        while (div.children.length > 0) {
        el.appendChild(div.children[0]);
      }
  }

  this.whileDragged = function(){
    if (typeof options.dragdealer !== 'undefined'){
      self.adjustVisualElements();
    }
  }

  this.adjustVisualElements = function(){
    var currentQuota = options.dragdealer.getStep()[0] - 1;

    for(var i = 0; i < options.quotas.length; i++){
      var aBtn = document.getElementById('sequra-fraction-quota-item-'+i);
      aBtn.className = String(aBtn.className).replace('selected','');
    }

    var currentBtn = document.getElementById('sequra-fraction-quota-item-'+currentQuota);
    currentBtn.className = currentBtn.className + ' selected';

    options.currentQuotaNumber = options.quotas[currentQuota] ;
    options.currentQuotaPrice = options.quotaPrices[currentQuota] ;
    options.tae = self.approximation(options.totalPrice, options.currentQuotaNumber, options.currentTax)

    document.getElementById('sequra-total-credit').innerHTML = self.formatCurrency(options.quotaPrices[currentQuota] * options.quotas[currentQuota]);
    self.terms_calculation();
  }

  this.onSelect = function(){
    self.adjustVisualElements();

    if(typeof options.selectCallback === 'function'){
      options.selectCallback(options.currentQuotaNumber,options.currentQuotaPrice);
    }
  }

  this.approximation = function (purchase_amount, instalment_count, instalment_fee) {
    var down_payment = purchase_amount / instalment_count,
      drawdown_payment = purchase_amount - down_payment,
      apr_approximation_constant = ((drawdown_payment - instalment_fee) / (down_payment + instalment_fee));

    var apr_approximation_function = function (apr) {
      var apr_approximated = 0;
      for (var i = 1; i < instalment_count; i++) { apr_approximated += Math.pow((1+apr), -(i/12)); }
      return apr_approximated - apr_approximation_constant;
    }

    var minimum_boundary = 0,
      maximum_boundary = 1000;
    while ( (maximum_boundary - minimum_boundary) > 0.00001 ) {
      var average_boundary = (minimum_boundary + maximum_boundary) / 2;
      var apr_minimum_boundary = apr_approximation_function(minimum_boundary);
      var apr_average_boundary = apr_approximation_function(average_boundary);

      if (apr_average_boundary * apr_minimum_boundary > 0) {
        minimum_boundary = average_boundary;
      } else {
        maximum_boundary = average_boundary;
      }
    }

    return ((average_boundary * 100).toFixed(2) * 100) | 0;
  }

  this.getInstalmentFees = function(){
    return options.currentTax ;
  }

  this.getQuotaNumber = function(){
    return options.currentQuotaNumber ;
  }

  this.getQuotaPrice = function(){
    return options.currentQuotaPrice ;
  }

   this.selectQuota = function(index){
    options.dragdealer.setStep(index + 1);
  }

  this.partpayment_details_getter = function(){
    return {
        product: 'pp1',
        instalment_count: options.currentQuotaNumber,
        instalment_fee: options.currentTax,
        instalment_amount: (options.currentQuotaPrice | 0),
        apr: options.tae,
        grand_total: options.totalPrice + (options.currentQuotaNumber * options.currentTax),
        cost_of_credit: options.currentQuotaNumber * options.currentTax
    };
  }

  self.init();

}
