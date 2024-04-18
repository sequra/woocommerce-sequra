const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const path = require('path');
module.exports = {
    ...defaultConfig,
    entry: {
        index: './assets/js/src/index.js'
    },
    output: {
        ...defaultConfig.output,
        path: path.resolve(__dirname, 'assets/js/dist')
    }
};
