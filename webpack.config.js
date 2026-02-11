const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = {
  // Entry point for bundling
  entry: {
    bundle: './src/index.ts',
  },

  // Output configuration
  output: {
    path: path.resolve(__dirname, 'scripts'),
    filename: '[name].js',
  },

  // External dependencies provided by Divi/WordPress
  externals: {
    // Third party dependencies
    jquery: 'jQuery',
    underscore: '_',
    lodash: 'lodash',
    react: ['vendor', 'React'],
    'react-dom': ['vendor', 'ReactDOM'],

    // WordPress dependencies
    '@wordpress/i18n': ['vendor', 'wp', 'i18n'],
    '@wordpress/hooks': ['vendor', 'wp', 'hooks'],

    // Divi dependencies
    '@divi/rest': ['divi', 'rest'],
    '@divi/data': ['divi', 'data'],
    '@divi/module': ['divi', 'module'],
    '@divi/module-utils': ['divi', 'moduleUtils'],
    '@divi/modal': ['divi', 'modal'],
    '@divi/field-library': ['divi', 'fieldLibrary'],
    '@divi/icon-library': ['divi', 'iconLibrary'],
    '@divi/module-library': ['divi', 'moduleLibrary'],
    '@divi/style-library': ['divi', 'styleLibrary'],
  },

  // Module rules for different file types
  module: {
    rules: [
      // TypeScript files
      {
        test: /\.tsx?$/,
        use: {
          loader: 'ts-loader',
          options: {
            transpileOnly: true, // Skip type checking - types come from @divi/types
          },
        },
        exclude: /node_modules/,
      },

      // JavaScript files
      {
        test: /\.jsx?$/,
        exclude: /node_modules/,
        use: [
          {
            loader: 'babel-loader',
            options: {
              compact: false,
              presets: [
                ['@babel/preset-env', {
                  modules: false,
                  targets: '> 5%',
                }],
                '@babel/preset-react',
              ],
              plugins: [
                '@babel/plugin-proposal-class-properties',
              ],
            },
          },
        ],
      },

      // SCSS/CSS files
      {
        test: /\.s?css$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              url: false,
              importLoaders: 2,
            },
          },
          'sass-loader',
        ],
      },
    ],
  },

  // Split CSS: style.scss → vb-bundle.css (VB only), module.scss → bundle.css (FE + VB)
  optimization: {
    splitChunks: {
      cacheGroups: {
        vb: {
          type: 'css/mini-extract',
          test: /[\\/]style(\.module)?\.(sc|sa|c)ss$/,
          chunks: 'all',
          enforce: true,
          name( _, chunks, cacheGroupKey ) {
            const chunkName = chunks[ 0 ].name;
            return `${ path.dirname(
              chunkName
            ) }/${ cacheGroupKey }-${ path.basename( chunkName ) }`;
          },
        },
        default: false,
      },
    },
  },

  // Resolve extensions
  resolve: {
    extensions: ['.tsx', '.ts', '.jsx', '.js', '.json'],
  },

  // Plugins
  plugins: [
    new MiniCssExtractPlugin({
      filename: '../styles/[name].css',
    }),
    new CopyWebpackPlugin({
      patterns: [
        {
          from: '**/module.json',
          context: 'src/components',
          to: path.resolve(__dirname, 'modules-json'),
          noErrorOnMissing: true,
        },
        {
          from: '**/module-default-render-attributes.json',
          context: 'src/components',
          to: path.resolve(__dirname, 'modules-json'),
          noErrorOnMissing: true,
        },
      ],
    }),
  ],

  // Development mode settings
  mode: process.env.NODE_ENV || 'development',
  devtool: process.env.NODE_ENV === 'production' ? false : 'source-map',
};
