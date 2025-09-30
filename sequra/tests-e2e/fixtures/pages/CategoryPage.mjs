import { CategoryPage as BaseCategoryPage } from "playwright-fixture-for-plugins";
// TODO: pending review
/**
 * Category page
 */
export default class CategoryPage extends BaseCategoryPage {

    /**
    * Provide the category URL
    * @param {Object} options
    * @param {string} options.slug The category slug
    * @returns {string} The category URL
    */
    categoryUrl(options) {
        const { slug } = options;
        return `${this.baseURL}/${slug}.html`;
    }
}