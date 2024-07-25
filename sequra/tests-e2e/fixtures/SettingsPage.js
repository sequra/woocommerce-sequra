import SeQuraHelper from './SeQuraHelper';
import WpAdmin from './WpAdmin';

export default class SettingsPage {

    /**
     * @param {import('@playwright/test').Page} page
     * @param {string} baseURL
     * @param {string} pageHash Page hash to navigate to
     * @param {import('@playwright/test').Expect} expect
     * @param {import('@playwright/test').Request} request
     */
    constructor(page, baseURL, pageHash, expect, request) {
        this.page = page;
        this.expect = expect;
        this.wpAdmin = new WpAdmin(page, baseURL, expect);
        this.helper = new SeQuraHelper(request, expect);
        this.pageHash = pageHash

        this.selector = {
            username: '[name="username-input"]',
            password: '[name="password-input"]',
            primaryBtn: '.sq-button.sqt--primary',
            multiSelect: '.sq-multi-item-selector',
            dropdownListItem: '.sqp-dropdown-list-item',
            yesOption: '.sq-radio-input:has([type="radio"][value="true"])',
            assetsKey: '[name="assets-key-input"]',
            headerNavbar: '.sqp-header-top > .sqp-menu-items',
            env: {
                sandbox: 'label:has([value="sandbox"])',
            },
        };
    }

    async goto() {
        await this.wpAdmin.gotoSeQuraSettings({ page: this.pageHash });
    }

    async expectLoadingShowAndHide() {
        await this.page.locator('.sq-page-loader:not(.sqs--hidden)').waitFor({ state: 'attached', timeout: 10000 });
        await this.page.locator('.sq-page-loader.sqs--hidden').waitFor({ state: 'attached', timeout: 10000 });
    }
}