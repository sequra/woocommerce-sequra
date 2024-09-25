import SeQuraHelper from '../fixtures/SeQuraHelper';
import { test, expect } from '../fixtures/test';

test.describe.configure({ mode: 'serial' });
test.describe('Widget settings', () => {

  test('Change settings', async ({ page, widgetSettingsPage }) => {
    await widgetSettingsPage.setup({ widgets: false });
    await widgetSettingsPage.goto();
    await widgetSettingsPage.expectLoadingShowAndHide();

    const defaultSettings = widgetSettingsPage.getDefaultSettings();

    const newSettings = {
      ...defaultSettings,
      enabled: false,
      cartMiniWidget: {
        ...defaultSettings.cartMiniWidget,
        enabled: true,
      },
      productListingMiniWidget: {
        ...defaultSettings.productListingMiniWidget,
        enabled: true,
      }
    }

    // test cancellation of the changes
    await widgetSettingsPage.fill(newSettings);
    await widgetSettingsPage.cancel()
    await widgetSettingsPage.expectConfigurationMatches(defaultSettings);

    const emptyStr = "";
    const invalidSelector = "!.summary .price>.amount,.summary .price ins .amount";
    // const invalidJSON = "{";

    const cartMiniWidgetOnly = {
      ...newSettings,
      productListingMiniWidget: {
        ...newSettings.productListingMiniWidget,
        enabled: false,
      }
    };

    const productListingMiniWidgetOnly = {
      ...newSettings,
      cartMiniWidget: {
        ...newSettings.cartMiniWidget,
        enabled: false,
      }
    };

    // Invalid values
    const invalidSettings = [
      // Cart Mini Widget
      {
        ...cartMiniWidgetOnly,
        cartMiniWidget: {
          ...cartMiniWidgetOnly.cartMiniWidget,
          priceSel: emptyStr
        }
      },
      {
        ...cartMiniWidgetOnly,
        cartMiniWidget: {
          ...cartMiniWidgetOnly.cartMiniWidget,
          priceSel: invalidSelector
        }
      },
      {
        ...cartMiniWidgetOnly,
        cartMiniWidget: {
          ...cartMiniWidgetOnly.cartMiniWidget,
          locationSel: emptyStr
        }
      },
      {
        ...cartMiniWidgetOnly,
        cartMiniWidget: {
          ...cartMiniWidgetOnly.cartMiniWidget,
          locationSel: invalidSelector
        }
      },
      // Product Listing Mini Widget
      {
        ...productListingMiniWidgetOnly,
        productListingMiniWidget: {
          ...productListingMiniWidgetOnly.productListingMiniWidget,
          priceSel: emptyStr
        }
      },
      {
        ...productListingMiniWidgetOnly,
        productListingMiniWidget: {
          ...productListingMiniWidgetOnly.productListingMiniWidget,
          priceSel: invalidSelector
        }
      },
      {
        ...productListingMiniWidgetOnly,
        productListingMiniWidget: {
          ...productListingMiniWidgetOnly.productListingMiniWidget,
          locationSel: emptyStr
        }
      },
      {
        ...productListingMiniWidgetOnly,
        productListingMiniWidget: {
          ...productListingMiniWidgetOnly.productListingMiniWidget,
          locationSel: invalidSelector
        }
      }
    ]

    for (const invalid of invalidSettings) {
      // console.log('invalid', invalid);
      await widgetSettingsPage.fill(invalid);
      await widgetSettingsPage.expectErrorMessageToBeVisible();
      await page.reload();
      await widgetSettingsPage.expectLoadingShowAndHide();
    }

    // Valid values
    await widgetSettingsPage.fill(newSettings);
    await widgetSettingsPage.save({ expectLoadingShowAndHide: true });
    await widgetSettingsPage.expectConfigurationMatches(newSettings);
    // test if changes persist after reload
    await page.reload();
    await widgetSettingsPage.expectLoadingShowAndHide();
    await widgetSettingsPage.expectConfigurationMatches(newSettings);
  });

  test('Show cart widget', async ({ productPage, cartPage, widgetSettingsPage, request }) => {

    let defaultSettings = widgetSettingsPage.getDefaultSettings();
    defaultSettings = {
      ...defaultSettings,
      enabled: false,
      cartMiniWidget: { ...defaultSettings.cartMiniWidget, enabled: true },
      productListingMiniWidget: { ...defaultSettings.productListingMiniWidget, enabled: false }
    };

    const themes = [
      // For Gutenberg blocks
      {
        theme: 'twentytwentyfour', version: 'blocks', settings: {
          ...defaultSettings,
          cartMiniWidget: {
            ...defaultSettings.cartMiniWidget,
            priceSel: '.wp-block-woocommerce-cart-totals-block .wc-block-components-totals-footer-item .wc-block-components-totals-item__value',
            locationSel: '.wp-block-woocommerce-cart-totals-block .wc-block-components-totals-footer-item',
          }
        }
      },
      // For Classic editor
      { theme: 'storefront', version: 'classic', settings: defaultSettings },
    ];

    const helper = new SeQuraHelper(request, expect);

    for (const { theme, version, settings } of themes) {

      await widgetSettingsPage.goto();
      await widgetSettingsPage.expectLoadingShowAndHide();

      await widgetSettingsPage.fill(settings);
      await widgetSettingsPage.save({ expectLoadingShowAndHide: true, skipIfDisabled: true });
      await widgetSettingsPage.logout();

      await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

      await helper.executeWebhook({ webhook: helper.webhooks.SET_THEME, args: [{ name: 'theme', value: theme }] });
      await helper.executeWebhook({ webhook: helper.webhooks.CART_VERSION, args: [{ name: 'version', value: version }] });

      const opt = { locationSel: settings.cartMiniWidget.locationSel, product: 'pp3', amount: 10000, message: 'Desde €11,33/mes con seQura' };

      // await page.pause();
      await cartPage.expectMiniWidgetToBeVisible(opt);
      // Apply 100% product price discount and check if message below limit is shown
      await cartPage.applyCoupon({ coupon: 'free', theme });
      await cartPage.expectMiniWidgetToBeVisible({ ...opt, navigate: false, amount: 1000, message: 'Fracciona con seQura a partir de €50,00' });
      // Clear coupon, add another product and check if mini widget is not shown
      await cartPage.removeCoupon({ theme });
      await cartPage.setQuantity({ quantity: 2, theme });
      await cartPage.expectMiniWidgetToBeVisible({ ...opt, navigate: false, visible: false, message: '', amount: 0 });
      // restore cart amount.
      await cartPage.setQuantity({ quantity: 1, theme });
      await cartPage.expectMiniWidgetToBeVisible({ ...opt, navigate: false, });
    }
  });

  test('Show product listing widget', async ({ shopPage, widgetSettingsPage, request }) => {

    let defaultSettings = widgetSettingsPage.getDefaultSettings();
    defaultSettings = {
      ...defaultSettings,
      enabled: false,
      cartMiniWidget: { ...defaultSettings.cartMiniWidget, enabled: false },
      productListingMiniWidget: { ...defaultSettings.productListingMiniWidget, enabled: true }
    };

    const themes = [
      // For Classic editor
      { theme: 'storefront', settings: defaultSettings },
      // For Gutenberg blocks
      {
        theme: 'twentytwentyfour', settings: {
          ...defaultSettings,
          productListingMiniWidget: {
            ...defaultSettings.productListingMiniWidget,
            priceSel: '.product .wc-block-components-product-price>.amount:first-child,.product .wc-block-components-product-price ins .amount',
            locationSel: '.product .wc-block-components-product-price',
          }
        }
      }
    ];

    const helper = new SeQuraHelper(request, expect);

    for (const { theme, settings } of themes) {
      await widgetSettingsPage.goto();
      await widgetSettingsPage.expectLoadingShowAndHide();

      await widgetSettingsPage.fill(settings);
      await widgetSettingsPage.save({ expectLoadingShowAndHide: true, skipIfDisabled: true });
      await widgetSettingsPage.logout();

      await helper.executeWebhook({ webhook: helper.webhooks.SET_THEME, args: [{ name: 'theme', value: theme }] });
      const opt = { locationSel: settings.productListingMiniWidget.locationSel, product: 'pp3' };
      await shopPage.expectMiniWidgetToBeVisible({ ...opt, amount: 5000, message: 'Desde €7,16/mes con seQura' });
      await shopPage.expectMiniWidgetToBeVisible({ ...opt, amount: 8000, message: 'Desde €9,66/mes con seQura' });
      await shopPage.expectMiniWidgetToBeVisible({ ...opt, amount: 9000, message: 'Desde €10,50/mes con seQura' });
    }
  });
});