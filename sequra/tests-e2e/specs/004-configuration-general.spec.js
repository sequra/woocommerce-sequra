import { test, expect } from '../fixtures/test';

test.describe.configure({ mode: 'serial' });
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
      await checkoutPage.expectAnyPaymentMethod({ available });

      await generalSettingsPage.goto();
      await generalSettingsPage.expectLoadingShowAndHide();
    }

    for (const ipAddresses of notAllowedIPAddressesMatrix) {
      await fillAndAssert(ipAddresses, false);
    }

    for (const ipAddresses of allowedIPAddressesMatrix) {
      await fillAndAssert(ipAddresses, true);
    }
  });

  test('Change excluded categories', async ({ page, generalSettingsPage, productPage, checkoutPage }) => {

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
});