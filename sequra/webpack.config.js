const TerserPlugin = require("terser-webpack-plugin");
const path = require('path');

module.exports = (env, argv) => {

    const isProd = argv.mode !== 'development';

    const config = {
        module: {
            rules: []
        },
        stats: 'errors-only'
    };

    if (isProd) {
        // Uglify JS
        config.optimization = {
            minimizer: [new TerserPlugin()],
        };
        // Babel
        config.module.rules.push(
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: "babel-loader",
                    options: {
                        presets: ['@babel/preset-env']
                    }
                }
            }
        );
    }

    const frontPlugins = [];
    const adminPlugins = [];

    const exports = [
        {
            plugins: adminPlugins,
            entry: "./assets/js/src/settings-page.js",
            output: {
                path: path.resolve(__dirname, './assets/js'),
                filename: "settings-page.min.js",
            },
        },
        // Add more entries here for processing other files.
    ];

    return exports.map((asset) => {
        return Object.assign({}, config, asset);
    });
};
