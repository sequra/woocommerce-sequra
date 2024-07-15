import {test, expect} from './fixtures';

test.beforeEach('Restore state', async ({page}) => {
    // TODO: restore the database to a known state
    console.log('Restore state');
});

test.describe('Checkout', () => {
  test('Make a payment with "Review test approve" names', async ({ page, cart, checkout }) => {
    await cart.add({page, product: 'beanie', quantity: 1 });
    await checkout.open({page});
    await checkout.fillWithReviewTestApprove({page});
    await checkout.placeOrderUsingCardPayment({page});
    await checkout.waitForOrderSuccess({page});
  });
});



// test('get started link', async ({ page }) => {
//   await page.goto('https://playwright.dev/');

//   // Click the get started link.
//   await page.getByRole('link', { name: 'Get started' }).click();

//   // Expects page to have a heading with the name of Installation.
//   await expect(page.getByRole('heading', { name: 'Installation' })).toBeVisible();
// });
