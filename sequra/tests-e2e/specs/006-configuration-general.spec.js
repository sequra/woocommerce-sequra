import { test, expect } from '../fixtures/test';

test.describe('Configuration', () => {

  test('Change allowed IP addresses', async ({ page, generalSettingsPage, productPage, checkoutPage }) => {

    await checkoutPage.setupForPhysicalProducts();

    // Test cancellation of the changes
    await generalSettingsPage.goto();
    await generalSettingsPage.expectLoadingShowAndHide();
    await generalSettingsPage.fillAllowedIPAddresses(['8.8.8.8']);
    await generalSettingsPage.cancel();
    await generalSettingsPage.expectAllowedIPAddressesToBeEmpty();

    // Test with invalid IP addresses
    const badIPAddressesMatrix = [
      ['a.b.c.d'],
      ['a.b.c.d', '1.1.1.256'],
      ['a.b.c.d', '1.1.1.256', 'lorem ipsum']
    ]
    for (const ipAddresses of badIPAddressesMatrix) {
      await generalSettingsPage.fillAllowedIPAddresses(ipAddresses);
      await generalSettingsPage.save({ expectLoadingShowAndHide: false });
      await expect(page.getByText('This field must contain only valid IP addresses.'), 'The error message under "Allowed IP addresses" field should be visible').toBeVisible();
      await page.reload();
      await generalSettingsPage.expectLoadingShowAndHide();
    }

    // Test with valid IP addresses
    const publicIP = await generalSettingsPage.getPublicIP();
    const notAllowedIPAddressesMatrix = [
      ['8.8.8.8']
    ]
    const allowedIPAddressesMatrix = [
      [],
      [publicIP],
      [publicIP, '8.8.8.8']
    ]

    const fillAndAssert = async (ipAddresses, available) => {
      await generalSettingsPage.fillAllowedIPAddresses(ipAddresses);
      await generalSettingsPage.save({});

      await generalSettingsPage.logout();

      await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

      await checkoutPage.goto();
      await checkoutPage.expectPaymentMethodsBeingReloaded();
      await checkoutPage.expectAnyPaymentMethod({ available });

      await generalSettingsPage.goto();
      await generalSettingsPage.expectLoadingShowAndHide();
    }

    for (const ipAddresses of notAllowedIPAddressesMatrix) {
      console.log('Fill not allowed IP addresses:', ipAddresses);
      await fillAndAssert(ipAddresses, false);
    }

    for (const ipAddresses of allowedIPAddressesMatrix) {
      console.log('Fill allowed IP addresses:', ipAddresses);
      await fillAndAssert(ipAddresses, true);
    }
  });

  test('Change excluded categories', async ({ generalSettingsPage, productPage, checkoutPage }) => {

    await checkoutPage.setupForPhysicalProducts();

    const allowedCategoriesMatrix = [
      [],
      ['Music']
      ['Music', 'Uncategorized'],
    ];

    const notAllowedCategoriesMatrix = [
      ['Accessories'],
      ['Accessories', 'Music'],
    ];

    // Test cancellation of the changes
    await generalSettingsPage.goto();
    await generalSettingsPage.expectLoadingShowAndHide();
    await generalSettingsPage.selectExcludedCategories(notAllowedCategoriesMatrix[0]);
    await generalSettingsPage.cancel();
    await generalSettingsPage.expectExcludedCategoriesToBeEmpty();


    const fillAndAssert = async (categories, available) => {
      await generalSettingsPage.selectExcludedCategories(categories);
      if (categories) {
        await generalSettingsPage.save({});
      }

      await generalSettingsPage.logout();

      await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

      await checkoutPage.goto();
      await checkoutPage.expectPaymentMethodsBeingReloaded();
      await checkoutPage.expectAnyPaymentMethod({ available });

      await generalSettingsPage.goto();
      await generalSettingsPage.expectLoadingShowAndHide();
    }

    for (const categories of notAllowedCategoriesMatrix) {
      await fillAndAssert(categories, false);
    }

    for (const categories of allowedCategoriesMatrix) {
      await fillAndAssert(categories, true);
    }
  });

  test('Change excluded products', async ({ generalSettingsPage, productPage, checkoutPage }) => {

    await checkoutPage.setupForPhysicalProducts();

    const allowedValuesMatrix = [
      [],
      ['14']
      ['woo-sunglasses-2'],
      ['14', 'woo-sunglasses-2'],
    ];

    const notAllowedValuesMatrix = [
      ['woo-sunglasses'], // The product SKU.
      ['13'], // The product ID.
      ['woo-sunglasses', 'woo-sunglasses-2'],
      ['woo-sunglasses', '14'],
      ['13', '14'],
      ['13', 'woo-sunglasses-2'],
    ];

    // Test cancellation of the changes
    await generalSettingsPage.goto();
    await generalSettingsPage.expectLoadingShowAndHide();
    await generalSettingsPage.fillExcludedProducts(notAllowedValuesMatrix[0]);
    await generalSettingsPage.cancel();
    await generalSettingsPage.expectExcludedProductsToBeEmpty();

    const fillAndAssert = async (values, available) => {
      await generalSettingsPage.fillExcludedProducts(values);
      if (values) {
        await generalSettingsPage.save({});
      }

      await generalSettingsPage.logout();

      await productPage.addToCart({ slug: 'sunglasses', quantity: 1 });

      await checkoutPage.goto();
      await checkoutPage.expectPaymentMethodsBeingReloaded();
      await checkoutPage.expectAnyPaymentMethod({ available });

      await generalSettingsPage.goto();
      await generalSettingsPage.expectLoadingShowAndHide();
    }

    for (const values of notAllowedValuesMatrix) {
      await fillAndAssert(values, false);
    }

    for (const values of allowedValuesMatrix) {
      await fillAndAssert(values, true);
    }
  });

  test('Change enabled for services', async ({ page, generalSettingsPage, checkoutPage }) => {

    await checkoutPage.setupForPhysicalProducts();
    await generalSettingsPage.goto();
    await generalSettingsPage.expectLoadingShowAndHide();

    // Test cancellation of the changes
    await generalSettingsPage.expectEnabledForServicesToBe(false);
    await generalSettingsPage.enableEnabledForServices();
    await generalSettingsPage.cancel();
    await generalSettingsPage.expectEnabledForServicesToBe(false);

    // Test fields validation.
    await generalSettingsPage.enableEnabledForServices();
    await generalSettingsPage.save({ expectLoadingShowAndHide: true });

    await generalSettingsPage.enableAllowFirstServicePaymentDelay();
    await generalSettingsPage.enableAllowRegistrationItems();
    await generalSettingsPage.save({ expectLoadingShowAndHide: true });
    await page.reload();
    await generalSettingsPage.expectLoadingShowAndHide();
    await generalSettingsPage.expectAllowFirstServicePaymentDelayToBe(true);
    await generalSettingsPage.expectAllowRegistrationItemsToBe(true);

    const fillDefaultServiceEndDateAndAssert = async (value, isValid) => {
      await generalSettingsPage.fillDefaultServicesEndDate(value);
      await generalSettingsPage.save({ expectLoadingShowAndHide: isValid });
      if (!isValid) {
        await expect(page.getByText('This field must contain only dates as 2017-08-31 or time duration as P3M15D (3 months and 15 days). Check ISO 8601'), 'The error message under "Default services end date" field should be visible').toBeVisible();
      }
      await page.reload();
      await generalSettingsPage.expectLoadingShowAndHide();
    }

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

    for (const value of notAllowedValues) {
      await fillDefaultServiceEndDateAndAssert(value, false);
    }

    for (const value of allowedValues) {
      await fillDefaultServiceEndDateAndAssert(value, true);
    }
  });

  test('Change available countries', async ({ page, generalSettingsPage, checkoutPage }) => {

    await checkoutPage.setupForPhysicalProducts();
    await generalSettingsPage.goto();
    await generalSettingsPage.expectLoadingShowAndHide();

    const defaultCountriesRef = [
      { country: 'ES', ref: 'dummy_automated_tests' },
      // { country: 'FR', ref: 'dummy_automated_tests_fr' },
      { country: 'IT', ref: 'dummy_automated_tests_it' },
      { country: 'PT', ref: 'dummy_automated_tests_pt' },
    ];

    await generalSettingsPage.expectAvailableCountries(defaultCountriesRef);
    
    // Test cancellation of the changes
    await generalSettingsPage.fillAvailableCountries([defaultCountriesRef[0]]);
    await generalSettingsPage.cancel();
    await generalSettingsPage.expectAvailableCountries(defaultCountriesRef);

    // Test wrong values.
    await generalSettingsPage.fillAvailableCountries([
      { country: 'ES', ref: 'dummy_wrong' }
    ]);

    // await page.pause();
    await generalSettingsPage.save({ expectLoadingShowAndHide: false });
    const errorMsgLocator = page.locator('.sq-country-field-wrapper .sqp-input-error').filter({hasText: 'This field is invalid.'});
    await expect(errorMsgLocator).toBeVisible();

    // Test valid values.
    await generalSettingsPage.fillAvailableCountries(defaultCountriesRef);
    await generalSettingsPage.save({ expectLoadingShowAndHide: true });
    await page.reload();
    await generalSettingsPage.expectLoadingShowAndHide();
    await generalSettingsPage.expectAvailableCountries(defaultCountriesRef);
  });
});