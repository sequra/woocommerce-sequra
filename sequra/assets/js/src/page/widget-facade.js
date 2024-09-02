if (SequraWidgetFacade) {
    (function (i, s, o, g, r, a, m) { i['SequraConfiguration'] = g; i['SequraOnLoad'] = []; i[r] = {}; i[r][a] = function (callback) { i['SequraOnLoad'].push(callback); }; (a = s.createElement(o)), (m = s.getElementsByTagName(o)[0]); a.async = 1; a.src = g.scriptUri; m.parentNode.insertBefore(a, m); })(window, document, 'script', SequraWidgetFacade, 'Sequra', 'onLoad');
}
(function () {
    document.addEventListener('DOMContentLoaded', () => {
        if (!SequraWidgetFacade) {
            return;
        }

        SequraWidgetFacade = {
            ...SequraWidgetFacade,
            ...{
                mutationObserver: null,
                forcePriceSelector: true,
                presets: {
                    L: '{"alignment":"left"}',
                    R: '{"alignment":"right"}',
                    legacy: '{"type":"legacy"}',
                    legacyL: '{"type":"legacy","alignment":"left"}',
                    legacyR: '{"type":"legacy","alignment":"right"}',
                    minimal: '{"type":"text","branding":"none","size":"S","starting-text":"as-low-as"}',
                    minimalL: '{"type":"text","branding":"none","size":"S","starting-text":"as-low-as","alignment":"left"}',
                    minimalR: '{"type":"text","branding":"none","size":"S","starting-text":"as-low-as","alignment":"right"}'
                },

                init: function () {
                    // Remove duplicated objects from this.widgets.
                    const uniqueWidgets = [];
                    this.widgets.forEach(widget => {
                        Object.keys(widget).forEach(key => {
                            if (typeof widget[key] === 'string') {
                                widget[key] = this.decodeEntities(widget[key]);
                            }
                        });

                        if (!uniqueWidgets.some(w => w.price_src === widget.price_src && w.dest === widget.dest && w.product === widget.product && w.theme === widget.theme && w.reverse === widget.reverse && w.campaign === widget.campaign)) {
                            uniqueWidgets.push(widget);
                        }
                    });
                    this.widgets = uniqueWidgets;
                },

                getText: function (selector) {
                    return selector && document.querySelector(selector) ? document.querySelector(selector).textContent : "0";
                },

                nodeToCents: function (node) {
                    return this.textToCents(node ? node.textContent : "0");
                },

                selectorToCents: function (selector) {
                    return this.textToCents(this.getText(selector));
                },

                decodeEntities: function (encodedString) {
                    if (!encodedString.match(/&(nbsp|amp|quot|lt|gt|#\d+|#x[0-9A-Fa-f]+);/g)) {
                        return encodedString;
                    }
                    const elem = document.createElement('div');
                    elem.innerHTML = encodedString;
                    return elem.textContent;
                },

                textToCents: function (text) {
                    const thousandSeparator = this.decodeEntities(this.thousandSeparator);
                    const decimalSeparator = this.decodeEntities(this.decimalSeparator);

                    text = text.replace(/^\D*/, '').replace(/\D*$/, '');
                    if (text.indexOf(decimalSeparator) < 0) {
                        text += decimalSeparator + '00';
                    }
                    return this.floatToCents(
                        parseFloat(
                            text
                                .replace(thousandSeparator, '')
                                .replace(decimalSeparator, '.')
                        )
                    );
                },

                floatToCents: function (value) {
                    return parseInt(value.toFixed(2).replace('.', ''), 10);
                },

                refreshComponents: function () {
                    Sequra.onLoad(
                        function () {
                            Sequra.refreshComponents();
                        }
                    );
                },

                isVariableProduct: function (selector) {
                    return document.querySelector(selector) ? true : false;
                },

                getPriceSelector: function (widget) {
                    return !this.forcePriceSelector && this.isVariableProduct(widget.isVariableSel) ? widget.variationPriceSel : widget.priceSel;
                },

                /**
                 * Search for child elements in the parentElem that are targets of the widget
                 * @param {object} parentElem DOM element that may contains the widget's targets
                 * @param {object} widget  Widget object
                 * @param {string} observedAt Unique identifier to avoid fetch the same element multiple times
                 * @returns {array} Array of objects containing the target elements and a reference to the widget
                 */
                getWidgetTargets: function (parentElem, widget, observedAt) {

                    if (!widget.dest) {
                        widget.dest = '.single_add_to_cart_button';
                    }

                    const targets = [];
                    const children = parentElem.querySelectorAll(widget.dest);
                    const productObservedAttr = 'data-sequra-observed-' + widget.product;
                    for (const child of children) {
                        if (child.getAttribute(productObservedAttr) == observedAt) {
                            continue;// skip elements that are already observed in this mutation.
                        }
                        child.setAttribute(productObservedAttr, observedAt);
                        targets.push({ elem: child, widget: widget });
                    }
                    return targets;
                },

                /**
                 * Search for all the targets of the widgets in a parent element
                 * @param {object} parentElem DOM element that may contains the widget's targets
                 * @param {array} widgets List of widgets to be drawn in the page
                 * @param {string} observedAt Unique identifier to avoid fetch the same element multiple times
                 * @returns {array} Array of objects containing the target elements and a reference to the widget 
                 */
                getAllWidgetTargets: function (parentElem, widgets, observedAt) {
                    const targets = [];
                    for (const widget of widgets) {
                        const widgetTargets = this.getWidgetTargets(parentElem, widget, observedAt);
                        targets.push(...widgetTargets);
                    }
                    return targets;
                },

                /**
                 * Get an unique identifier to avoid fetch the same element multiple times
                 * @returns {number} The current timestamp
                 */
                getObservedAt: () => Date.now(),

                removeWidgetsOnPage: function () {
                    if (this.mutationObserver) {
                        this.mutationObserver.disconnect();
                    }
                    document.querySelectorAll('.sequra-promotion-widget').forEach(widget => widget.remove());
                    if (this.mutationObserver) {
                        this.mutationObserver.observe(document, { childList: true, subtree: true });
                    }
                },

                /**
                 * Paint the widgets in the page and observe the DOM to refresh the widgets when the page changes.
                 * @param {boolean} forcePriceSelector If true, the price selector will be forced to the simple product price selector.
                 */
                drawWidgetsOnPage: function (forcePriceSelector = true) {
                    if (!this.widgets.length) {
                        return;
                    }

                    this.forcePriceSelector = forcePriceSelector;

                    // First, draw the widgets in the page for the first time.
                    const widgetsTargets = this.getAllWidgetTargets(document, this.widgets, this.getObservedAt());
                    widgetsTargets.forEach(({ elem, widget }) => this.drawWidgetOnElement(widget, elem));

                    if (this.mutationObserver) {
                        this.mutationObserver.disconnect();
                    }

                    // Then, observe the DOM to refresh the widgets when the page changes.
                    this.mutationObserver = new MutationObserver((mutations) => {
                        const targets = []; // contains the elements that must be refreshed.
                        const observedAt = this.getObservedAt();

                        for (const mutation of mutations) {
                            if (!['childList', 'subtree'].includes(mutation.type)) {
                                continue; // skip mutations that not are changing the DOM.
                            }

                            const widgetTargets = this.getAllWidgetTargets(mutation.target, this.widgets, observedAt)
                            targets.push(...widgetTargets);
                        }

                        this.mutationObserver.disconnect();// disable the observer to avoid multiple calls to the same function.

                        targets.forEach(({ elem, widget }) => this.drawWidgetOnElement(widget, elem)); // draw the widgets.

                        this.mutationObserver.observe(document, { childList: true, subtree: true }); // enable the observer again.
                    });

                    this.mutationObserver.observe(document, { childList: true, subtree: true });
                },

                drawWidgetOnElement: function (widget, element) {
                    const priceSrc = this.getPriceSelector(widget);
                    const priceElem = document.querySelector(priceSrc);
                    if (!priceElem) {
                        console.error(priceSrc + ' is not a valid css selector to read the price from, for seQura widget.');
                        return;
                    }
                    const cents = this.nodeToCents(priceElem);

                    const className = 'sequra-promotion-widget';
                    const modifierClassName = className + '--' + widget.product;

                    const oldWidget = element.parentNode.querySelector('.' + className + '.' + modifierClassName);
                    if (oldWidget) {
                        if (cents == oldWidget.getAttribute('data-amount')) {
                            return; // no need to update the widget, the price is the same.
                        }

                        oldWidget.remove();// remove the old widget to draw a new one.
                    }

                    const promoWidgetNode = document.createElement('div');
                    promoWidgetNode.className = className + ' ' + modifierClassName;
                    promoWidgetNode.setAttribute('data-amount', cents);
                    promoWidgetNode.setAttribute('data-product', widget.product);

                    const theme = this.presets[widget.theme] ? this.presets[widget.theme] : widget.theme;
                    try {
                        const attributes = JSON.parse(theme);
                        for (let key in attributes) {
                            promoWidgetNode.setAttribute('data-' + key, "" + attributes[key]);
                        }
                    } catch (e) {
                        promoWidgetNode.setAttribute('data-type', 'text');
                    }

                    if (widget.campaign) {
                        promoWidgetNode.setAttribute('data-campaign', widget.campaign);
                    }
                    if (widget.registrationAmount) {
                        promoWidgetNode.setAttribute('data-registration-amount', widget.registrationAmount);
                    }

                    if (element.nextSibling) {//Insert after
                        element.parentNode.insertBefore(promoWidgetNode, element.nextSibling);
                        this.refreshComponents();
                    } else {
                        element.parentNode.appendChild(promoWidgetNode);
                    }
                }
            }
        };

        SequraWidgetFacade.init()
        Sequra.onLoad(() => {
            SequraWidgetFacade.drawWidgetsOnPage();
            // TODO: review following code and remove it if not needed
            if ('undefined' !== typeof jQuery) {
                const variationForm = jQuery('.variations_form');
                if (variationForm.length) {
                    variationForm.on('show_variation', () => SequraWidgetFacade.drawWidgetsOnPage(false));
                    variationForm.on('hide_variation', () => SequraWidgetFacade.drawWidgetsOnPage());
                }
            }
        });
    });
})();