import { test, expect } from './fixtures';

test.beforeAll('Setup', async ({ request }) => {
  const response = await request.post('./?sq-webhook=dummy_services_config');
  expect(response.status()).toBe(200);
  const json = await response.json();
  expect(json.success).toBe(true);
});


test.describe.configure({ mode: 'parallel' });
test.describe('Service checkout', () => {


  test('Make a successful payment using any shopper name', async ({ page, cart, checkout }) => {
    await cart.add({ page, product: 'album', quantity: 1 });
    await checkout.open({ page });
    await checkout.expectPp3ToBeVisible({ page });
    await checkout.fillWithNonSpecialShopperName({ page, fieldGroup: 'billing' });
    await checkout.placeOrderUsingPp3({ page });
    await checkout.waitForOrderSuccess({ page });
  });

});