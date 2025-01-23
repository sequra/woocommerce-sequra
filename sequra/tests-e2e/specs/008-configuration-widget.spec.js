import SeQuraHelper from '../fixtures/SeQuraHelper';
import { test, expect } from '../fixtures/test';

test.describe('Widget settings', () => {

  test('Change settings', async ({ page, widgetSettingsPage }) => {
    await widgetSettingsPage.setup({ widgets: false });
    await widgetSettingsPage.goto();
    await widgetSettingsPage.expectLoadingShowAndHide();

    const defaultSettings = widgetSettingsPage.getDefaultSettings();

    const newSettings = {
      ...defaultSettings,
      enabled: true,
      priceSel: ".priceSel",
      altPriceSel: ".altPriceSel",
      altPriceTriggerSel: ".altPriceTriggerSel",
      locationSel: ".locationSel",
      widgetConfig: '{"alignment":"left","amount-font-bold":"true","amount-font-color":"#1C1C1C","amount-font-size":"15","background-color":"white","border-color":"#B1AEBA","border-radius":"","class":"","font-color":"#1C1C1C","link-font-color":"#1C1C1C","link-underline":"true","no-costs-claim":"","size":"M","starting-text":"only","type":"banner"}',
      customLocations: [
        {
          paymentMethod: "Paga Después",
          display: false,
          locationSel: ".price",
          widgetConfig: '{"alignment":"right","amount-font-bold":"true","amount-font-color":"#000000","amount-font-size":"15","background-color":"white","border-color":"#B1AEBA","border-radius":"5","class":"","font-color":"#1C1C1C","link-font-color":"#1C1C1C","link-underline":"true","no-costs-claim":"","size":"M","starting-text":"only","type":"banner","branding":"black"}'
        },
        {
          paymentMethod: "Divide tu pago en 3",
          display: true,
          locationSel: ".price2",
          widgetConfig: '{"alignment":"center","amount-font-bold":"true","amount-font-color":"#1C1C1C","amount-font-size":"15","background-color":"white","border-color":"#B1AEBA","border-radius":"","class":"","font-color":"#1C1C1C","link-font-color":"#1C1C1C","link-underline":"true","no-costs-claim":"","size":"M","starting-text":"only","type":"text"}'
        },
        {
          paymentMethod: "Paga Fraccionado",
          display: false,
          locationSel: ".price3",
          widgetConfig: ''
        }
      ]
    }

    // test cancellation of the changes
    await widgetSettingsPage.fill(newSettings);
    await widgetSettingsPage.cancel()
    await widgetSettingsPage.expectConfigurationMatches(defaultSettings);

    const emptyStr = "";
    const invalidSelector = "!.summary .price>.amount,.summary .price ins .amount";
    const invalidJSON = "{";

    // Invalid values
    const invalidSettings = [
      { ...defaultSettings, widgetConfig: emptyStr },
      { ...defaultSettings, widgetConfig: invalidJSON },
      { ...defaultSettings, enabled: true, priceSel: emptyStr },
      { ...defaultSettings, enabled: true, priceSel: invalidSelector },
      { ...defaultSettings, enabled: true, altPriceSel: invalidSelector },
      { ...defaultSettings, enabled: true, altPriceTriggerSel: invalidSelector },
      { ...defaultSettings, enabled: true, locationSel: emptyStr },
      { ...defaultSettings, enabled: true, locationSel: invalidSelector },
      { ...defaultSettings, enabled: true, customLocations: [{ ...newSettings.customLocations[0], locationSel: invalidSelector }] },
      { ...defaultSettings, enabled: true, customLocations: [{ ...newSettings.customLocations[0], widgetConfig: invalidJSON }] },
    ]

    for (const invalid of invalidSettings) {
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

  test('Show widget', async ({ productPage, widgetSettingsPage, request }) => {
    const defaultSettings = {
      ...widgetSettingsPage.getDefaultSettings(),
      enabled: true
    };
    const gutenbergBlocksSettings = {
      ...defaultSettings,
      priceSel: ".wc-block-components-product-price>.amount,.wc-block-components-product-price ins .amount",
      locationSel: ".wc-block-components-product-price"
    };

    const expectWidgetsAreVisible = async (opt) => {

      let i1Loc = opt.locationSel;
      let i1WidgetConfig = opt.widgetConfig;
      const i1CustomLoc = opt.customLocations.find(loc => loc.paymentMethod === "Paga Después")
      if (i1CustomLoc) {
        i1Loc = i1CustomLoc.locationSel;
        i1WidgetConfig = i1CustomLoc.widgetConfig;
      }
      await productPage.expectWidgetToBeVisible({ ...opt, product: 'pp3' });
      await productPage.expectWidgetToBeVisible({ ...opt, product: 'sp1', campaign: 'permanente' });
      await productPage.expectWidgetToBeVisible({ ...opt, locationSel: i1Loc, widgetConfig: i1WidgetConfig, product: 'i1' });
    }

    const customLocations = [
      {
        paymentMethod: "Paga Después",
        display: true,
        locationSel: ".single_add_to_cart_button",
        widgetConfig: '{"alignment":"right","amount-font-bold":"true","amount-font-color":"#000000","amount-font-size":"15","background-color":"white","border-color":"#B1AEBA","border-radius":"5","class":"","font-color":"#1C1C1C","link-font-color":"#1C1C1C","link-underline":"true","no-costs-claim":"","size":"M","starting-text":"only","type":"banner","branding":"black"}'
      }
    ]

    const themes = [
      { theme: 'storefront', settings: defaultSettings }, // For classic editor with default location
      { theme: 'storefront', settings: { ...defaultSettings, customLocations } }, // For classic editor with custom locations
      { theme: 'twentytwentyfour', settings: gutenbergBlocksSettings }, // For gutenberg blocks with default location
      { theme: 'twentytwentyfour', settings: { ...gutenbergBlocksSettings, customLocations } }, // For gutenberg blocks with custom locations
    ];

    const helper = new SeQuraHelper(request, expect);

    for (const { theme, settings } of themes) {
      await widgetSettingsPage.goto();
      await widgetSettingsPage.expectLoadingShowAndHide();
      await widgetSettingsPage.fill(settings);
      // await page.pause();
      await widgetSettingsPage.save({ expectLoadingShowAndHide: true, skipIfDisabled: true });

      await helper.executeWebhook({ webhook: helper.webhooks.SET_THEME, args: [{ name: 'theme', value: theme }] });

      // -- Test for simple product.
      await productPage.goto({ slug: 'sunglasses' });
      await expectWidgetsAreVisible({ ...settings, amount: 9000, registrationAmount: 0 });

      // -- Test for variable product.
      await productPage.goto({ slug: 'hoodie' });
      await expectWidgetsAreVisible({ ...settings, amount: 8000, registrationAmount: 0 });

      let variationOptions = [
        { attributeName: 'logo', value: 'Yes', opt: { ...settings, amount: 8500, registrationAmount: 0 } }, // Variation having regular price.
        { attributeName: 'logo', value: 'No', opt: { ...settings, amount: 8000, registrationAmount: 0 } } // Variation having sale price.
      ];

      for (const variationOpt of variationOptions) {
        await productPage.selectVariation(variationOpt);
        await expectWidgetsAreVisible(variationOpt.opt);
      }

      // -- Clear variations and test again.
      await productPage.clearVariations();
      await expectWidgetsAreVisible({ ...settings, amount: 8000, registrationAmount: 0 });
    }
  });

  test('Don\'t show widget for banned product', async ({ productPage, generalSettingsPage, widgetSettingsPage, wpAdmin, request }) => {
    const helper = new SeQuraHelper(request, expect);
    await helper.executeWebhook({ webhook: helper.webhooks.SET_THEME, args: [{ name: 'theme', value: 'twentytwentyfour' }] });
    await helper.executeWebhook({ webhook: helper.webhooks.SET_THEME, args: [{ name: 'theme', value: 'twentytwentyfour' }] });
    const productId = 13;

    await widgetSettingsPage.goto();
    await widgetSettingsPage.expectLoadingShowAndHide();
    await widgetSettingsPage.fill({
      ...widgetSettingsPage.getDefaultSettings(),
      enabled: true,
      priceSel: ".wc-block-components-product-price>.amount,.wc-block-components-product-price ins .amount",
      locationSel: ".wc-block-components-product-price"
    });
    await widgetSettingsPage.save({ expectLoadingShowAndHide: true, skipIfDisabled: true });

    const expectWidgetNotToBeVisible = async () => {
      await productPage.goto({ slug: 'sunglasses' });
      await productPage.expectWidgetsNotToBeVisible();
    }

    // Test by including the product SKU in "Excluded products" list.
    await generalSettingsPage.goto();
    await generalSettingsPage.expectLoadingShowAndHide();
    await generalSettingsPage.fillExcludedProducts(['woo-sunglasses']);
    await generalSettingsPage.save({});
    await expectWidgetNotToBeVisible();

    // Test by including the product ID in "Excluded products" list.
    await generalSettingsPage.goto();
    await generalSettingsPage.expectLoadingShowAndHide();
    await generalSettingsPage.fillExcludedProducts([`${productId}`]);
    await generalSettingsPage.save({});
    await expectWidgetNotToBeVisible();

    await generalSettingsPage.goto();
    await generalSettingsPage.expectLoadingShowAndHide();
    await generalSettingsPage.fillExcludedProducts([]); // Clear the excluded products.

    // Test by including the product category in "Excluded category" list.
    await generalSettingsPage.selectExcludedCategories(['Accessories']);
    await generalSettingsPage.save({});
    await expectWidgetNotToBeVisible();

    await generalSettingsPage.goto();
    await generalSettingsPage.expectLoadingShowAndHide();
    await generalSettingsPage.selectExcludedCategories([]); // Clear the excluded categories.
    await generalSettingsPage.save({});

    // Test by checking "Do not offer seQura for this product" on the product back-office page.
    await wpAdmin.gotoProduct({ productId });
    await wpAdmin.setProductAsBanned();
    await expectWidgetNotToBeVisible();

    await wpAdmin.gotoProduct({ productId });
    await wpAdmin.setProductAsBanned(false); // Restore previous state.
  });
});