import { test, expect } from '../fixtures/test';
import DataProvider from '../fixtures/utils/DataProvider.mjs';

async function assertWidgetAndPaymentMethodVisibility(available, productPage, cartPage, checkoutPage, dataProvider, helper) {
  const slugOpt = { slug: 'sunglasses' };
  await productPage.goto(slugOpt);
  if (available) {
    await productPage.expectWidgetToBeVisible(dataProvider.pp3FrontEndWidgetOptions({ ...slugOpt, widgetType: DataProvider.PRODUCT_WIDGET }));
    await productPage.expectWidgetToBeVisible(dataProvider.sp1FrontEndWidgetOptions({ ...slugOpt, widgetType: DataProvider.PRODUCT_WIDGET }));
    await productPage.expectWidgetToBeVisible(dataProvider.i1FrontEndWidgetOptions({ ...slugOpt, widgetType: DataProvider.PRODUCT_WIDGET }));
  } else {
    await productPage.expectWidgetsNotToBeVisible();
  }
  await productPage.addToCart({ ...slugOpt, quantity: 1 });
  await cartPage.goto();
  if (available) {
    const widgetOptions = dataProvider.cartFrontEndWidgetOptions({ amount: 10000, registrationAmount: null });
    await cartPage.expectWidgetToBeVisible(widgetOptions);
  } else {
    await cartPage.expectWidgetsNotToBeVisible();
  }
  await checkoutPage.goto();
  await checkoutPage.fillForm({ isShipping: true, ...dataProvider.shopper() });
  await checkoutPage.expectAnyPaymentMethod({ available, timeout: 30000 });
}

async function assertMiniWidgetVisibility(available, categoryPage) {
  await categoryPage.goto({ slug: 'accessories' });
  if (available) {
    await categoryPage.expectAnyVisibleMiniWidget('pp3');
  } else {
    await categoryPage.expectMiniWidgetsNotToBeVisible('pp3');
  }
}

test.describe('Configuration', () => {

  test('Change allowed IP addresses', async ({ helper, dataProvider, backOffice, page, generalSettingsPage, productPage, checkoutPage, cartPage, categoryPage }) => {
    // Setup
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    const { clear_config, dummy_config, checkout_version, cart_version, set_theme } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: cart_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_config, args: [{ name: 'widgets', value: '1' }] }
    ]);

    const badIPAddressesMatrix = [
      ['a.b.c.d'],
      ['a.b.c.d', '1.1.1.256'],
      ['a.b.c.d', '1.1.1.256', 'lorem ipsum']
    ]

    const publicIP = await dataProvider.publicIP();
    const notAllowedIPAddressesMatrix = [
      ['8.8.8.8']
    ]
    const allowedIPAddressesMatrix = [
      [],
      [publicIP],
      [publicIP, ...notAllowedIPAddressesMatrix[0]]
    ]

    const fillAndAssert = async (ipAddresses, available, categoryPage) => {
      await generalSettingsPage.fillAllowedIPAddresses(ipAddresses);
      await generalSettingsPage.save({ skipIfDisabled: true });
      await backOffice.logout();
      await assertMiniWidgetVisibility(available, categoryPage);
      await assertWidgetAndPaymentMethodVisibility(available, productPage, cartPage, checkoutPage, dataProvider, helper);
      await generalSettingsPage.goto();
      await generalSettingsPage.expectLoadingShowAndHide();
    }

    // Execution.
    await generalSettingsPage.goto();
    await generalSettingsPage.expectLoadingShowAndHide();

    // Test cancellation of the changes
    await generalSettingsPage.fillAllowedIPAddresses(notAllowedIPAddressesMatrix[0]);
    await generalSettingsPage.cancel();
    await generalSettingsPage.expectAllowedIPAddressesToBeEmpty();

    // Test with invalid IP addresses
    for (const ipAddresses of badIPAddressesMatrix) {
      await generalSettingsPage.fillAllowedIPAddresses(ipAddresses);
      await generalSettingsPage.save({ expectLoadingShowAndHide: false });
      await expect(page.getByText('This field must contain only valid IP addresses.'), 'The error message under "Allowed IP addresses" field should be visible').toBeVisible();
      await page.reload();
      await generalSettingsPage.expectLoadingShowAndHide();
    }

    // Test with valid IP addresses
    for (const ipAddresses of notAllowedIPAddressesMatrix) {
      console.log('Fill not allowed IP addresses:', ipAddresses);
      await fillAndAssert(ipAddresses, false, categoryPage);
    }

    for (const ipAddresses of allowedIPAddressesMatrix) {
      console.log('Fill allowed IP addresses:', ipAddresses);
      await fillAndAssert(ipAddresses, true, categoryPage);
    }
  });

  test('Change excluded categories', async ({ helper, dataProvider, backOffice, generalSettingsPage, productPage, checkoutPage, cartPage }) => {

    // Setup
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    const { clear_config, dummy_config, checkout_version, cart_version, set_theme } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: cart_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_config, args: [{ name: 'widgets', value: '1' }] }
    ]);

    const allowedCategoriesMatrix = [
      [],
      ['Hoodies'],
    ];

    const notAllowedCategoriesMatrix = [
      ['Accessories'],
    ];

    const fillAndAssert = async (categories, available) => {
      await generalSettingsPage.selectExcludedCategories(categories);
      await generalSettingsPage.save({ skipIfDisabled: true });
      await backOffice.logout();
      // TODO: There's no way to apply an availability filter to product listing widgets based on current category..
      // In the future, if we add a product filter by category, we can test both available and not available scenarios.
      // await assertMiniWidgetVisibility(available, categoryPage);
      await assertWidgetAndPaymentMethodVisibility(available, productPage, cartPage, checkoutPage, dataProvider, helper);
      await generalSettingsPage.goto();
      await generalSettingsPage.expectLoadingShowAndHide();
    }

    // Execution
    await generalSettingsPage.goto();
    await generalSettingsPage.expectLoadingShowAndHide();

    // Test cancellation of the changes
    await generalSettingsPage.selectExcludedCategories(notAllowedCategoriesMatrix[0]);
    await generalSettingsPage.cancel();
    await generalSettingsPage.expectExcludedCategoriesToBeEmpty();

    // Test with categories assigned to the product
    for (const categories of notAllowedCategoriesMatrix) {
      await fillAndAssert(categories, false);
    }

    // Test with categories not assigned to the product
    for (const categories of allowedCategoriesMatrix) {
      await fillAndAssert(categories, true);
    }
  });

  test('Change excluded products', async ({ helper, dataProvider, backOffice, generalSettingsPage, productPage, checkoutPage, cartPage }) => {

    // Setup
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    const { clear_config, dummy_config, checkout_version, cart_version, set_theme } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: cart_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_config, args: [{ name: 'widgets', value: '1' }] }
    ]);

    const sku = 'woo-sunglasses';// The product SKU.
    const allowedValuesMatrix = [
      [],
      ['woo-hoodie'],
      ['woo-hoodie', 'woo-hoodie-blue-logo']
    ];
    const notAllowedValuesMatrix = [
      [sku],
      [sku, ...allowedValuesMatrix[2]],
    ];

    const fillAndAssert = async (values, available) => {
      await generalSettingsPage.fillExcludedProducts(values);
      await generalSettingsPage.save({ skipIfDisabled: true });
      await backOffice.logout();
      await assertWidgetAndPaymentMethodVisibility(available, productPage, cartPage, checkoutPage, dataProvider, helper);
      await generalSettingsPage.goto();
      await generalSettingsPage.expectLoadingShowAndHide();
    }

    // Execution
    await generalSettingsPage.goto();
    await generalSettingsPage.expectLoadingShowAndHide();

    // Test cancellation of the changes
    await generalSettingsPage.fillExcludedProducts(notAllowedValuesMatrix[0]);
    await generalSettingsPage.cancel();
    await generalSettingsPage.expectExcludedProductsToBeEmpty();

    // Test including the product
    for (const values of notAllowedValuesMatrix) {
      await fillAndAssert(values, false);
    }

    // Test excluding the product
    for (const values of allowedValuesMatrix) {
      await fillAndAssert(values, true);
    }
  });

  test('Change available countries', async ({ helper, dataProvider, page, generalSettingsPage }) => {
    // Setup
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    const { clear_config, dummy_config, checkout_version, cart_version, set_theme } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: cart_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_config }
    ]);
    const countries = dataProvider.countriesMerchantRefs()
    const onlyFrance = countries.filter(c => c.code === 'FR');

    // Execution
    await generalSettingsPage.goto();
    await generalSettingsPage.expectLoadingShowAndHide();
    await generalSettingsPage.expectAvailableCountries({ countries });

    // Test cancellation of the changes
    await generalSettingsPage.fillAvailableCountries({ countries: onlyFrance });
    await generalSettingsPage.cancel();
    await generalSettingsPage.expectAvailableCountries({ countries });

    // Test valid values.
    await generalSettingsPage.fillAvailableCountries({ countries: onlyFrance });
    await generalSettingsPage.save({ expectLoadingShowAndHide: true });
    await generalSettingsPage.expectAvailableCountries({ countries: onlyFrance });
    await page.reload();
    await generalSettingsPage.expectLoadingShowAndHide();
    await generalSettingsPage.expectAvailableCountries({ countries: onlyFrance });
  });

  test('Service selling configuration is displayed correctly', async ({ helper, page, dataProvider, generalSettingsPage }) => {
    // Setup
    const uiVersion = DataProvider.UI_BLOCKS;
    const theme = dataProvider.themeForUiVersion(uiVersion);
    const { clear_config, dummy_config, dummy_services_config, checkout_version, set_theme } = helper.webhooks;
    await helper.executeWebhooksSequentially([
      { webhook: set_theme, args: [{ name: 'theme', value: theme }] },
      { webhook: checkout_version, args: [{ name: 'version', value: uiVersion }] },
      { webhook: clear_config },
      { webhook: dummy_config }
    ]);

    // Execution
    // Case 1: Configuration does not allows to sell services.
    await generalSettingsPage.goto();
    await generalSettingsPage.expectLoadingShowAndHide();
    await generalSettingsPage.expectServicesConfiguration();
    // Case 2: Configuration allows to sell services.
    const configuration = {
      enabledForServices: ['ES'],
      allowRegistrationItems: ['ES'],
      allowFirstServicePaymentDelay: ['ES'],
      defaultServicesEndDate: 'P1Y'
    };
    await helper.executeWebhooksSequentially([{ webhook: clear_config }, { webhook: dummy_services_config }]);
    await page.reload();
    await generalSettingsPage.expectLoadingShowAndHide();
    await generalSettingsPage.expectServicesConfiguration(configuration);
    // Test the form fields.
    const notAllowedValues = [
      '',
      'abc',
      '2017/08/31',
      '2023-02-29',
      '2023-02-30',
      '2023-02-31',
      '2023-01-32',
      '2023-04-31',
      '2023-00-00',
      '2023-01-31abc',
      'P1Y23',
      'P',
      'P1Y2M3MT',
      '2023-01-31P1Y',
    ];
    const allowedValues = [
      '2030-12-31',
      '2024-02-29',
      'P5Y',
      'P2Y3M',
    ];
    // Test cancellation of the changes
    await generalSettingsPage.fillDefaultServicesEndDate(allowedValues[0]);
    await generalSettingsPage.cancel();
    await generalSettingsPage.expectServicesConfiguration(configuration);
    // Test valid and invalid values.
    const fillDefaultServiceEndDateAndAssert = async (configuration, newValue, isValid) => {
      await generalSettingsPage.fillDefaultServicesEndDate(newValue);
      await generalSettingsPage.save({ expectLoadingShowAndHide: isValid, skipIfDisabled: true });
      if (!isValid) {
        await expect(page.getByText('This field must contain only dates as 2017-08-31 or time duration as P3M15D (3 months and 15 days). Check ISO 8601'), 'The error message under "Default services end date" field should be visible').toBeVisible();
      } else {
        configuration.defaultServicesEndDate = newValue;
      }
      await page.reload();
      await generalSettingsPage.expectServicesConfiguration(configuration);
    }
    for (const value of notAllowedValues) {
      await fillDefaultServiceEndDateAndAssert(configuration, value, false);
    }
    for (const value of allowedValues) {
      await fillDefaultServiceEndDateAndAssert(configuration, value, true);
    }
  });
});