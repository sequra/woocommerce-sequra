/**
* Return a formatted price
* Copy from tools.js
*/
if (typeof(formatCurrency) !== typeof(Function)) {
	var formatCurrency = function (price, currencyFormat, currencySign, currencyBlank)
	{
		// if you modified this function, don't forget to modify the PHP function displayPrice (in the Tools.php class)
		blank = '';
		price = parseFloat(price.toFixed(6));
		price = ps_round(price, priceDisplayPrecision);
		if (currencyBlank > 0)
			blank = ' ';
		if (currencyFormat == 1)
			return currencySign + blank + formatNumber(price, priceDisplayPrecision, ',', '.');
		if (currencyFormat == 2)
			return (formatNumber(price, priceDisplayPrecision, ' ', ',') + blank + currencySign);
		if (currencyFormat == 3)
			return (currencySign + blank + formatNumber(price, priceDisplayPrecision, '.', ','));
		if (currencyFormat == 4)
			return (formatNumber(price, priceDisplayPrecision, ',', '.') + blank + currencySign);
		return price;
	}
}

/**
* Return a formatted number
* Copy from tools.js
*/
if (typeof(formatNumber) !== typeof(Function)) {
	var formatNumber = function (value, numberOfDecimal, thousenSeparator, virgule)
	{
		value = value.toFixed(numberOfDecimal);
		var val_string = value+'';
		var tmp = val_string.split('.');
		var abs_val_string = (tmp.length == 2) ? tmp[0] : val_string;
		var deci_string = ('0.' + (tmp.length == 2 ? tmp[1] : 0)).substr(2);
		var nb = abs_val_string.length;

		for (var i = 1 ; i < 4; i++)
			if (value >= Math.pow(10, (3 * i)))
				abs_val_string = abs_val_string.substring(0, nb - (3 * i)) + thousenSeparator + abs_val_string.substring(nb - (3 * i));

		if (parseInt(numberOfDecimal) == 0)
			return abs_val_string;
		return abs_val_string + virgule + (deci_string > 0 ? deci_string : '00');
	}
}
