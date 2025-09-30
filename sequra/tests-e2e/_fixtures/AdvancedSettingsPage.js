import SettingsPage from '../fixtures/SettingsPage';

export default class AdvancedSettingsPage extends SettingsPage {

    /**
     * @param {import('@playwright/test').Page} page
     * @param {string} baseURL
     * @param {import('@playwright/test').Request} request
     * @param {import('@playwright/test').Expect} expect
     */
    constructor(page, baseURL, request, expect) {
        super(page, baseURL, 'advanced-debug', expect, request);
    }

    async setup() {
        const webhooks = [
            this.helper.webhooks.CLEAR_CONFIG,
            this.helper.webhooks.DUMMY_CONFIG,
            this.helper.webhooks.REMOVE_LOG,
        ];

        for (const webhook of webhooks) {
            await this.helper.executeWebhook({ webhook });
        }
    }

    async expectLogIsEmpty() {
        await this.page.getByRole('cell', { name: 'No entries found' }).waitFor({ state: 'visible', timeout: 5000 });
    }

    async enableLogs({ enable = true }) {
        const enableLogsCheckbox = this.page.locator('input.sqp-toggle-input');
        await this.expect(enableLogsCheckbox, '"Enable logs" toggle is ' + (enable ? 'OFF' : 'ON')).toBeChecked({ checked: !enable });
        await this.page.locator('.sq-toggle').click();
        await this.expectLoadingShowAndHide();
    }

    async reloadLogs() {
        await this.page.getByRole('button', { name: 'Reload' }).click();
        await this.expectLoadingShowAndHide();
    }

    async removeLogs() {
        await this.page.getByRole('button', { name: 'Remove' }).click();
        const confirmRemoveBtn = this.page.locator('.sqp-footer .sq-button.sqt--danger');
        await confirmRemoveBtn.waitFor({ state: 'visible' });
        await confirmRemoveBtn.click();
        await this.expectLoadingShowAndHide();
    }

    async expectLogHasContent({ expectedLogs = [], nonExpectedLogs = [] }) {
        await this.expect(this.page.locator('.sqm--log').first(), 'Log datatable has content').toBeVisible();
        for (const log of expectedLogs) {
            const { level, message } = log;
            await this.expect(this.page.locator('.sqm--log.sqm--log-' + level.toLowerCase(), { hasText: message }), `Log of severity "${level}" found with message: ${message}`).toBeVisible();
        }
        for (const log of nonExpectedLogs) {
            const { level, message } = log;
            await this.expect(this.page.locator('.sqm--log.sqm--log-' + level.toLowerCase(), { hasText: message }), `Log of severity "${level}" not found with message: ${message}`).toHaveCount(0);
        }
    }

    async expectLogPaginationIsVisible() {
        await this.expect(this.page.locator('.datatable-pagination-list-item.sq-datatable__active'), 'Logs pagination is visible').toBeVisible();
    }

    async printLogs() {
        await this.helper.executeWebhook({ webhook: this.helper.webhooks.PRINT_LOGS });
    }

    async setSeverityLevel({ severityLevel }) {
        await this.page.locator('.sq-single-select-dropdown button').click();
        await this.page.waitForSelector('.sqp-dropdown-button + .sqp-dropdown-list .sqp-dropdown-list-item', { timeout: 1000 });
        await this.page.locator('.sqp-dropdown-button + .sqp-dropdown-list .sqp-dropdown-list-item', { hasText: severityLevel }).click();
        await this.expectLoadingShowAndHide();
    }
}