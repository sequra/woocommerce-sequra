import { test, expect } from '../fixtures/test';
import DataProvider from '../fixtures/utils/DataProvider.mjs';

test.describe('Widget settings', () => {

  test('Change settings', async ({ page, helper, widgetSettingsPage, dataProvider }) => {
    // Setup
    const { dummy_config, clear_config } = helper.webhooks;
    await helper.executeWebhook({ webhook: clear_config }); // Clear the configuration.
    await helper.executeWebhook({ webhook: dummy_config, args: [{ name: 'widgets', value: '0' }] }); // Setup with widgets disabled.

    const defaultSettings = dataProvider.defaultWidgetOptions();
    const widgetOptions = dataProvider.widgetOptions();
    const onlyProductSettings = dataProvider.onlyProductWidgetOptions();
    const onlyCartSettings = dataProvider.onlyCartWidgetOptions();

    const emptyStr = "";
    const invalidSelector = "!.summary .price>.amount,.summary .price ins .amount";
    const invalidJSON = "{";

    const invalidSettingsList = [
      { ...defaultSettings, widgetConfig: emptyStr },
      { ...defaultSettings, widgetConfig: invalidJSON },
      { ...onlyProductSettings, product: { ...onlyProductSettings.product, priceSel: emptyStr } },
      { ...onlyProductSettings, product: { ...onlyProductSettings.product, priceSel: invalidSelector } },
      { ...onlyProductSettings, product: { ...onlyProductSettings.product, altPriceSel: invalidSelector } },
      { ...onlyProductSettings, product: { ...onlyProductSettings.product, altPriceTriggerSel: invalidSelector } },
      { ...onlyProductSettings, product: { ...onlyProductSettings.product, locationSel: emptyStr } },
      { ...onlyProductSettings, product: { ...onlyProductSettings.product, locationSel: invalidSelector } },
      { ...onlyProductSettings, product: { ...onlyProductSettings.product, customLocations: [{ ...widgetOptions.product.customLocations[0], locationSel: invalidSelector }] } },
      { ...onlyProductSettings, product: { ...onlyProductSettings.product, customLocations: [{ ...widgetOptions.product.customLocations[0], widgetConfig: invalidJSON }] } },
      { ...onlyCartSettings, cart: { ...onlyCartSettings.cart, priceSel: emptyStr } },
      { ...onlyCartSettings, cart: { ...onlyCartSettings.cart, priceSel: invalidSelector } },
      { ...onlyCartSettings, cart: { ...onlyCartSettings.cart, locationSel: emptyStr } },
      { ...onlyCartSettings, cart: { ...onlyCartSettings.cart, locationSel: invalidSelector } },
    ]

    // Execution.
    await widgetSettingsPage.goto();
    await widgetSettingsPage.expectLoadingShowAndHide();

    // Test cancellation of the changes.
    await widgetSettingsPage.fillForm(widgetOptions);
    await widgetSettingsPage.cancel();
    await widgetSettingsPage.expectConfigurationMatches(defaultSettings);

    // Test invalid values.
    for (const invalid of invalidSettingsList) {
      await widgetSettingsPage.fillForm(invalid);
      await widgetSettingsPage.expectErrorMessageToBeVisible();
      await page.reload();
      await widgetSettingsPage.expectLoadingShowAndHide();
    }

    // Test valid values.
    await widgetSettingsPage.fillForm(widgetOptions);
    await widgetSettingsPage.save();
    await widgetSettingsPage.expectConfigurationMatches(widgetOptions);
    // Test if changes persist after reload.
    await page.reload();
    await widgetSettingsPage.expectLoadingShowAndHide();
    await widgetSettingsPage.expectConfigurationMatches(widgetOptions);
  });

  test('Show widget on product page', async ({ backOffice, helper, widgetSettingsPage, dataProvider, productPage }) => {
    // Setup
    const { dummy_config, clear_config } = helper.webhooks;
    await helper.executeWebhook({ webhook: clear_config }); // Clear the configuration.
    await helper.executeWebhook({ webhook: dummy_config, args: [{ name: 'widgets', value: '0' }] }); // Setup with widgets disabled.

    // Execution
    for (const uiVersion of [DataProvider.UI_BLOCKS, DataProvider.UI_CLASSIC]) {
      const expectWidgetsToBeVisible = async (pp3WidgetOption, sp1WidgetOption, i1WidgetOption) => {
        await productPage.expectWidgetToBeVisible(pp3WidgetOption);
        await productPage.expectWidgetToBeVisible(sp1WidgetOption);
        await productPage.expectWidgetToBeVisible(i1WidgetOption);
      }
      // Set the settings according the UI version.
      await widgetSettingsPage.goto();
      await widgetSettingsPage.expectLoadingShowAndHide();
      await widgetSettingsPage.fillForm(dataProvider.widgetOptions({ uiVersion }));
      await widgetSettingsPage.save();
      await backOffice.logout();
      // Set the UI version and a compatible theme.
      const theme = dataProvider.themeForUiVersion(uiVersion);
      await helper.executeWebhook({ webhook: helper.webhooks.set_theme, args: [{ name: 'theme', value: theme }] });

      // Simple product.
      let slugOpt = { slug: 'sunglasses', uiVersion };
      await productPage.goto(slugOpt);

      const { pp3FrontEndWidgetOptions, sp1FrontEndWidgetOptions, i1FrontEndWidgetOptions } = dataProvider;

      await expectWidgetsToBeVisible(pp3FrontEndWidgetOptions(slugOpt), sp1FrontEndWidgetOptions(slugOpt), i1FrontEndWidgetOptions(slugOpt));

      // Variable product.
      slugOpt = { ...slugOpt, slug: 'hoodie', uiVersion };
      const pp3WidgetOptionSalePrice = pp3FrontEndWidgetOptions(slugOpt);
      const pp3WidgetOptionRegularPrice = { ...pp3WidgetOptionSalePrice, amount: 8500 };
      const sp1WidgetOptionSalePrice = sp1FrontEndWidgetOptions(slugOpt);
      const sp1WidgetOptionRegularPrice = { ...sp1WidgetOptionSalePrice, amount: 8500 };
      const i1WidgetOptionSalePrice = i1FrontEndWidgetOptions(slugOpt);
      const i1WidgetOptionRegularPrice = { ...i1WidgetOptionSalePrice, amount: 8500 };
      let variationOptions = [
        // Variation having regular price.
        { attributeName: 'logo', value: 'Yes', pp3: pp3WidgetOptionRegularPrice, i1: i1WidgetOptionRegularPrice, sp1: sp1WidgetOptionRegularPrice },
        // Variation having sale price.
        { attributeName: 'logo', value: 'No', pp3: pp3WidgetOptionSalePrice, i1: i1WidgetOptionSalePrice, sp1: sp1WidgetOptionSalePrice },
      ];

      await productPage.goto(slugOpt);
      await expectWidgetsToBeVisible(pp3WidgetOptionSalePrice, sp1WidgetOptionSalePrice, i1WidgetOptionSalePrice);
      for (const { attributeName, value, pp3, i1, sp1 } of variationOptions) {
        await productPage.selectVariation(attributeName, value);
        await expectWidgetsToBeVisible(pp3, sp1, i1);
      }
      await productPage.clearVariations();
      await expectWidgetsToBeVisible(pp3WidgetOptionSalePrice, sp1WidgetOptionSalePrice, i1WidgetOptionSalePrice);
    }
  });

  test('Do not display the widget on the product page when the selector is invalid', async ({ backOffice, helper, widgetSettingsPage, dataProvider, productPage }) => {
    // Setup
    const { dummy_config, clear_config, set_theme } = helper.webhooks;
    await helper.executeWebhook({ webhook: clear_config }); // Clear the configuration.
    await helper.executeWebhook({ webhook: dummy_config, args: [{ name: 'widgets', value: '0' }] }); // Setup with widgets disabled.
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    await helper.executeWebhook({ webhook: set_theme, args: [{ name: 'theme', value: theme }] });

    const widgetOptions = dataProvider.onlyProductWidgetOptions();
    widgetOptions.product.customLocations[0].locationSel = '#product-addtocart-button-bad-selector';

    // Execution
    await widgetSettingsPage.goto();
    await widgetSettingsPage.expectLoadingShowAndHide();
    await widgetSettingsPage.fillForm(widgetOptions);
    await widgetSettingsPage.save();
    await backOffice.logout();

    const slugOpt = { slug: 'sunglasses' };
    await productPage.goto(slugOpt);

    await productPage.expectWidgetToBeVisible(dataProvider.pp3FrontEndWidgetOptions(slugOpt));
    await productPage.expectWidgetToBeVisible(dataProvider.sp1FrontEndWidgetOptions(slugOpt));
    await productPage.expectWidgetNotToBeVisible(dataProvider.i1FrontEndWidgetOptions(slugOpt));
  });

  test('Do not display the widget on the product page when custom location is disabled', async ({ backOffice, helper, widgetSettingsPage, dataProvider, productPage }) => {
    // Setup
    const { dummy_config, clear_config, set_theme } = helper.webhooks;
    await helper.executeWebhook({ webhook: clear_config }); // Clear the configuration.
    await helper.executeWebhook({ webhook: dummy_config, args: [{ name: 'widgets', value: '0' }] }); // Setup with widgets disabled.
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    await helper.executeWebhook({ webhook: set_theme, args: [{ name: 'theme', value: theme }] });

    const widgetOptions = dataProvider.onlyProductWidgetOptions();
    widgetOptions.product.customLocations[0].display = false;

    // Execution
    await widgetSettingsPage.goto();
    await widgetSettingsPage.expectLoadingShowAndHide();
    await widgetSettingsPage.fillForm(widgetOptions);
    await widgetSettingsPage.save();
    await backOffice.logout();

    const slugOpt = { slug: 'sunglasses' };
    await productPage.goto(slugOpt);

    await productPage.expectWidgetToBeVisible(dataProvider.pp3FrontEndWidgetOptions(slugOpt));
    await productPage.expectWidgetToBeVisible(dataProvider.sp1FrontEndWidgetOptions(slugOpt));
    await productPage.expectWidgetNotToBeVisible(dataProvider.i1FrontEndWidgetOptions(slugOpt));
  });

  test('Do not display widgets in the cart page when toggle is OFF', async ({ backOffice, helper, widgetSettingsPage, dataProvider, cartPage, productPage }) => {
    // Setup
    const { dummy_config, clear_config, set_theme, cart_version } = helper.webhooks;
    await helper.executeWebhook({ webhook: clear_config }); // Clear the configuration.
    await helper.executeWebhook({ webhook: dummy_config, args: [{ name: 'widgets', value: '1' }] }); // Setup with widgets disabled.
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    await helper.executeWebhook({ webhook: set_theme, args: [{ name: 'theme', value: theme }] });
    await helper.executeWebhook({ webhook: cart_version, args: [{ name: 'version', value: uiVersion }] });

    const widgetOptions = dataProvider.widgetOptions();
    widgetOptions.cart.display = false;

    // Execution
    await widgetSettingsPage.goto();
    await widgetSettingsPage.expectLoadingShowAndHide();
    await widgetSettingsPage.fillForm(widgetOptions);
    await widgetSettingsPage.save();
    await backOffice.logout();

    await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });
    await cartPage.goto();
    await cartPage.expectWidgetsNotToBeVisible();
  });

  test('Do not display widgets in the product listing page when toggle is OFF', async ({ backOffice, helper, widgetSettingsPage, dataProvider, categoryPage }) => {
    // Setup
    const { dummy_config, clear_config, set_theme } = helper.webhooks;
    await helper.executeWebhook({ webhook: clear_config });
    await helper.executeWebhook({ webhook: dummy_config, args: [{ name: 'widgets', value: '1' }] });
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    await helper.executeWebhook({ webhook: set_theme, args: [{ name: 'theme', value: theme }] });

    const widgetOptions = dataProvider.widgetOptions();
    widgetOptions.productListing.display = false;

    // Execution
    await widgetSettingsPage.goto();
    await widgetSettingsPage.expectLoadingShowAndHide();
    await widgetSettingsPage.fillForm(widgetOptions);
    await widgetSettingsPage.save();
    await backOffice.logout();

    await categoryPage.goto({ slug: 'accessories' });
    await categoryPage.expectMiniWidgetsNotToBeVisible('pp3');
  });

  test('Display widgets in the product listing page when settings are valid', async ({ backOffice, helper, widgetSettingsPage, dataProvider, categoryPage }) => {
    // Setup
    const { dummy_config, clear_config, set_theme } = helper.webhooks;
    await helper.executeWebhook({ webhook: clear_config });
    await helper.executeWebhook({ webhook: dummy_config, args: [{ name: 'widgets', value: '0' }] });
    // Execution
    for (const uiVersion of [DataProvider.UI_BLOCKS, DataProvider.UI_CLASSIC]) {
      // Set the UI version and a compatible theme.
      const theme = dataProvider.themeForUiVersion(uiVersion);
      await helper.executeWebhook({ webhook: set_theme, args: [{ name: 'theme', value: theme }] });

      const onlyProductListingWidgetOptions = dataProvider.onlyProductListingWidgetOptions({ uiVersion });
      const configurations = [
        { product: 'pp3', options: onlyProductListingWidgetOptions },
        {
          product: 'sp1', options: {
            ...onlyProductListingWidgetOptions,
            productListing: { ...onlyProductListingWidgetOptions.productListing, product: 'sp1', paymentMethod: 'Divide tu pago en 3' }
          }
        },
      ];

      for (const { product, options } of configurations) {
        await widgetSettingsPage.goto();
        await widgetSettingsPage.expectLoadingShowAndHide();
        await widgetSettingsPage.fillForm(options);
        await widgetSettingsPage.save();
        await backOffice.logout();

        await categoryPage.goto({ slug: 'accessories' });
        await categoryPage.expectAnyVisibleMiniWidget(product, { uiVersion });
      }
    }
  });

  test('Widget in the cart page changes according to quantity', async ({ backOffice, helper, widgetSettingsPage, dataProvider, cartPage, productPage }) => {
    // Setup
    const { dummy_config, clear_config } = helper.webhooks;
    await helper.executeWebhook({ webhook: clear_config }); // Clear the configuration.
    await helper.executeWebhook({ webhook: dummy_config, args: [{ name: 'widgets', value: '0' }] }); // Setup with widgets disabled.

    // Execution
    for (const uiVersion of [DataProvider.UI_BLOCKS, DataProvider.UI_CLASSIC]) {
      // Set the settings according the UI version.
      await widgetSettingsPage.goto();
      await widgetSettingsPage.expectLoadingShowAndHide();
      await widgetSettingsPage.fillForm(dataProvider.onlyCartWidgetOptions({ uiVersion }));
      await widgetSettingsPage.save();
      await backOffice.logout();
      // Set the UI version and a compatible theme.
      const theme = dataProvider.themeForUiVersion(uiVersion);
      await helper.executeWebhook({ webhook: helper.webhooks.set_theme, args: [{ name: 'theme', value: theme }] });
      await helper.executeWebhook({ webhook: helper.webhooks.cart_version, args: [{ name: 'version', value: uiVersion }] });

      const widgetOptions = dataProvider.cartFrontEndWidgetOptions({ amount: 9000 + 1000, registrationAmount: null, uiVersion });
      const widgetOptionsX2 = dataProvider.cartFrontEndWidgetOptions({ amount: 2 * 9000 + 1000, registrationAmount: null, uiVersion });
      const widgetOptionsOnlyShipping = dataProvider.cartFrontEndWidgetOptions({ amount: 1000, registrationAmount: null, uiVersion });

      await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });
      await cartPage.goto();
      await cartPage.expectWidgetToBeVisible(widgetOptions);
      // Apply 100% product price discount and check if message below limit is shown
      await cartPage.applyCoupon('free', { uiVersion });
      await cartPage.expectWidgetToBeVisible(widgetOptionsOnlyShipping);

      // Increase the quantity to 2 to check if the widget text changes.
      await cartPage.removeCoupon();
      await cartPage.setQuantity(2, { uiVersion });
      await cartPage.expectWidgetToBeVisible(widgetOptionsX2);
      // restore cart amount.
      await cartPage.setQuantity(1, { uiVersion });
      await cartPage.expectWidgetToBeVisible(widgetOptions);
      // Empty the cart.
      await cartPage.emptyCart();
    }
  });
});