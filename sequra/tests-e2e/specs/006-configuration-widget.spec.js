import { test } from '../fixtures/test';

test.describe.configure({ mode: 'serial' });
test.describe('Widget settings', () => {

  test('Change settings', async ({ page, widgetSettingsPage }) => {
    await widgetSettingsPage.setup({ widgets: false });
    await widgetSettingsPage.goto();
    await widgetSettingsPage.expectLoadingShowAndHide();

    const defaultSettings = widgetSettingsPage.getDefaultSettings();

    const newSettings = {
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

  test.only('Show widget with single location', async ({ page, productPage, widgetSettingsPage }) => {
    // TODO: Show widget with single location. Test both Gutenberg and Classic editor. Test both simple and variable products.

    const defaultSettings = {
      ...widgetSettingsPage.getDefaultSettings(),
      enabled: true
    };
    const gutenbergBlocksSettings = {
      ...defaultSettings,
      priceSel: ".wc-block-components-product-price>.amount,.wc-block-components-product-price ins .amount",
      locationSel: ".wc-block-components-product-price"
    };

    // Gutenberg blocks
    await widgetSettingsPage.goto();
    await widgetSettingsPage.expectLoadingShowAndHide();
    // await page.pause();
    await widgetSettingsPage.fill(gutenbergBlocksSettings);
    await widgetSettingsPage.save({ expectLoadingShowAndHide: true, skipIfDisabled: true });

    // -- Test for simple product.
    await productPage.goto({ slug: 'sunglasses' });
    let opt = { ...gutenbergBlocksSettings, amount: 9000, registrationAmount: 0 };
    await productPage.expectWidgetToBeVisible({ ...opt, product: 'pp3' });
    await productPage.expectWidgetToBeVisible({ ...opt, product: 'sp1', campaign: 'permanente' });
    await productPage.expectWidgetToBeVisible({ ...opt, product: 'i1' });
    
    // -- Test for variable product.
    await productPage.goto({ slug: 'hoodie' });
    opt = { ...gutenbergBlocksSettings, amount: 8000, registrationAmount: 0 };
    await productPage.expectWidgetToBeVisible({ ...opt, product: 'pp3' });
    await productPage.expectWidgetToBeVisible({ ...opt, product: 'sp1', campaign: 'permanente' });
    await productPage.expectWidgetToBeVisible({ ...opt, product: 'i1' });

    //TODO: Select first variation having regular price and test again.
    //TODO: Select second variation having sale price and test again.
    //TODO: Clear variations and test again.
    
  });

  test('Show widget with alternative locations', async ({ productPage, widgetSettingsPage }) => {
    // TODO: Show widget with multiple locations. Test both Gutenberg and Classic editor. Test both simple and variable products.
  });

});