import { test } from '../fixtures/test';

test.describe.configure({ mode: 'serial' });
test.describe('Widget settings', () => {

  test.only('Change settings', async ({ page, widgetSettingsPage }) => {
    await widgetSettingsPage.setup({ widgets: false });
    await widgetSettingsPage.goto();
    await widgetSettingsPage.expectLoadingShowAndHide();

    const defaultSettings = {
      enabled: false,
      priceSel: ".summary .price>.amount,.summary .price ins .amount",
      altPriceSel: ".woocommerce-variation-price .price>.amount,.woocommerce-variation-price .price ins .amount,.woocommerce-variation-price .price .amount",
      altPriceTriggerSel: ".variations",
      locationSel: ".summary .price",
      widgetConfig: '{"alignment":"center","amount-font-bold":"true","amount-font-color":"#1C1C1C","amount-font-size":"15","background-color":"white","border-color":"#B1AEBA","border-radius":"","class":"","font-color":"#1C1C1C","link-font-color":"#1C1C1C","link-underline":"true","no-costs-claim":"","size":"M","starting-text":"only","type":"banner"}',
      customLocations: []
    }

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
    const invalidSettings  = [
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

  test('Show widget with single location', async ({ productPage, widgetSettingsPage }) => {
    // TODO: Show widget with single location. Test both Gutenberg and Classic editor. Test both simple and variable products.
  });

  test('Show widget with alternative locations', async ({ productPage, widgetSettingsPage }) => {
    // TODO: Show widget with multiple locations. Test both Gutenberg and Classic editor. Test both simple and variable products.
  });

});