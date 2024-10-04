import SeQuraHelper from '../fixtures/SeQuraHelper';
import { test, expect } from '../fixtures/test';

test.describe.configure({ mode: 'serial' });
test.describe('Migration', () => {

  test('From v2.0.12 to v3.0.0', async ({ page, wpAdmin }) => {
    // Go to plugins page
    await wpAdmin.gotoPlugins();
    // Deactivate current seQura plugin
    await wpAdmin.deactivatePlugin({ plugin: '_sequra/sequra.php' });
    // Upload https://downloads.wordpress.org/plugin/sequra.2.0.12.zip
    await wpAdmin.uploadPlugin('https://downloads.wordpress.org/plugin/sequra.2.0.12.zip', {activate: true});
    // Activate plugin
    await page.pause();
  });
});