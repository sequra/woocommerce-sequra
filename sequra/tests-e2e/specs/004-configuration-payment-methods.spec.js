import { test } from '../fixtures/test';

test.describe('Configuration', () => {
  test('Payment methods', async ({ paymentMethodsSettingsPage }) => {
    await paymentMethodsSettingsPage.expectAvailablePaymentMethodsAreVisible({ merchant: 'dummy', countries: ['ES', 'FR', 'PT', 'IT'] });
  });
});