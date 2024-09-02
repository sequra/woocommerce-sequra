const TerserPlugin = require("terser-webpack-plugin");
const wpExports = require("@wordpress/scripts/config/webpack.config");
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
            ...wpExports,
            entry: {
                "payment-gateway.min": "./assets/js/src/block/payment-gateway.js",
            },
            output: {
                ...wpExports.output,
                path: path.resolve(__dirname, "./assets/js/dist/block")
            }
        },
        {
            plugins: adminPlugins,
            entry: "./assets/js/src/page/settings.js",
            output: {
                path: path.resolve(__dirname, './assets/js/dist/page'),
                filename: "settings.min.js",
            },
        },
        {
            plugins: frontPlugins,
            entry: "./assets/js/src/page/checkout.js",
            output: {
                path: path.resolve(__dirname, './assets/js/dist/page'),
                filename: "checkout.min.js",
            },
        },
        {
            plugins: frontPlugins,
            entry: "./assets/js/src/page/widget-facade.js",
            output: {
                path: path.resolve(__dirname, './assets/js/dist/page'),
                filename: "widget-facade.min.js",
            },
        },
        // Add more entries here for processing other files.
    ];

    return exports.map((asset) => {
        return Object.assign({}, config, asset);
    });
};
