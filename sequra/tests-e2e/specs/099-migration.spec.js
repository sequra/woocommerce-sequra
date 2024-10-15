import ConnectionSettingsPage from '../fixtures/ConnectionSettingsPage';
import GeneralSettingsPage from '../fixtures/GeneralSettingsPage';
import { expect, test } from '../fixtures/test';
import WidgetSettingsPage from '../fixtures/WidgetSettingsPage';

test.describe('Migration', () => {

  test('From v2.0.12 to v3.0.0', async ({ page, wpAdmin, sqHelper, baseURL, request }) => {
    await wpAdmin.gotoPlugins();
    // Deactivate current seQura plugin
    await wpAdmin.deactivatePlugin({ plugin: '_sequra/sequra.php' });
    // Remove data and tables for v3 from the database.
    await sqHelper.executeWebhook({ webhook: sqHelper.webhooks.REMOVE_DB_TABLES });
    // Install version 2.0.12
    await wpAdmin.uploadPlugin('https://downloads.wordpress.org/plugin/sequra.2.0.12.zip', { activate: true });
    // Set plugin configuration for version 2.0.12
    await sqHelper.executeWebhook({ webhook: sqHelper.webhooks.V2_CONFIG });

    // Upgrade to v3.0.0
    const url = new URL(baseURL);
    url.searchParams.set('sq-webhook', 'plugin_zip');
    await wpAdmin.uploadPlugin(url.toString(), { filename: 'sequra.zip', method: 'POST', activate: true, upgrade: true });

    // Check if the widget settings were migrated correctly.
    const widgetSettingsPage = new WidgetSettingsPage(page, baseURL, expect, request);
    await widgetSettingsPage.goto();
    await widgetSettingsPage.expectLoadingShowAndHide();
    const defaultWidgetSettings = widgetSettingsPage.getDefaultSettings();
    const customLoc = {
      paymentMethod: "Paga Después",
      display: true,
      locationSel: ".summary .price>.amount,.summary .price ins .amount",
      widgetConfig: '{"alignment":"left"}'
    };
    await widgetSettingsPage.expectConfigurationMatches({
      ...defaultWidgetSettings,
      enabled: true,
      customLocations: [
        customLoc,
        {
          ...customLoc,
          paymentMethod: "Divide tu pago en 3",
        },
        {
          ...customLoc,
          paymentMethod: "Paga Fraccionado",
        },
        {
          ...customLoc,
          paymentMethod: "Divide en 3 0,00 €/mes (DECOMBINED)",
          locationSel: "",
          widgetConfig: defaultWidgetSettings.widgetConfig,
          display: false
        }
      ]
    });

    // Check if the connection settings were migrated correctly.
    const connectionSettingsPage = new ConnectionSettingsPage(page, baseURL, expect, request);
    await connectionSettingsPage.goto();
    // await connectionSettingsPage.expectLoadingShowAndHide();
    await connectionSettingsPage.expectConfigurationMatches({
      username: 'dummy',
      password: process.env.DUMMY_PASSWORD,
      env: 'sandbox'
    });

    // Check if general settings were migrated correctly.
    const generalSettingsPage = new GeneralSettingsPage(page, baseURL, expect, request);
    await generalSettingsPage.goto();
    await generalSettingsPage.expectLoadingShowAndHide();
    await generalSettingsPage.expectAllowedIPAddressesToHaveValue('212.80.211.33');
    await generalSettingsPage.expectExcludedCategoriesToBeEmpty();
    await generalSettingsPage.expectExcludedProductsToBeEmpty();
    await generalSettingsPage.expectEnabledForServicesToBe(false);
    await generalSettingsPage.expectAvailableCountries([
      { country: 'ES', ref: 'dummy' }
    ]);
  });
});