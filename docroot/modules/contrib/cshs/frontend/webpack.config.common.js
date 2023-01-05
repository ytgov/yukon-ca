const { resolve } = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

function getDirectories() {
  const appRoot = __dirname;
  const src = `${appRoot}/src`;
  const repoRoot = resolve(appRoot, '..');
  const artifacts = `${repoRoot}/artifacts`;
  const dist = `${repoRoot}/build`;

  return Object.freeze({
    repoRoot,
    appRoot,
    dist,
    src,
    artifacts,
  });
}

/**
 * @type {{src: string, repoRoot: string, dist: string, appRoot: string, artifacts: string}}
 */
const directories = getDirectories();

/**
 * @type {string[]}
 */
const extensions = [
  '.js',
  '.ts',
];

/**
 * @type {string[]}
 */
const entrypoints = [
  'cshs',
  'jquery.simpler-select',
];

/**
 * @param {('development'|'test'|'production')} mode
 * @param {string} devtool
 * @param {Object[]} [plugins]
 *
 * @return {Object}
 */
function configure(mode, devtool, plugins = []) {
  process.env.NODE_ENV = mode;

  plugins.unshift(
    new MiniCssExtractPlugin({
      filename: '[name].css',
    }),
  );

  return {
    mode,
    plugins,
    devtool,
    target: ['web', 'es5'],
    entry: entrypoints.reduce((accumulator, name) => {
      accumulator[name] = `${directories.src}/${name}.ts`;

      return accumulator;
    }, {}),
    resolve: {
      extensions,
    },
    output: {
      path: directories.dist,
      filename: '[name].js',
    },
    optimization: {
      minimize: false,
    },
    module: {
      rules: [
        {
          test: /\.[t|j]s$/,
          use: ['babel-loader', 'ts-loader'],
          exclude: /node_modules/,
        },
        {
          test: /\.s?css$/,
          use: [
            MiniCssExtractPlugin.loader,
            {
              loader: 'css-loader',
              options: {
                importLoaders: 1,
              },
            },
            {
              loader: 'postcss-loader',
              options: {
                postcssOptions: {
                  plugins: [
                    require('autoprefixer'),
                    require('postcss-nested'),
                    require('postcss-css-variables'),
                    // This plugin must go after the "css-variables".
                    require('postcss-calc'),
                    require('cssnano'),
                  ],
                },
              },
            },
          ],
        },
      ],
    },
  };
}

module.exports = {
  directories,
  extensions,
  configure,
};
