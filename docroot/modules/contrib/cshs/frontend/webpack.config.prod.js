const { BundleAnalyzerPlugin } = require('webpack-bundle-analyzer');
const { configure, directories } = require('./webpack.config.common');

module.exports = configure(
  'production',
  'source-map',
  [
    new BundleAnalyzerPlugin({
      openAnalyzer: false,
      analyzerMode: 'static',
      reportFilename: `${directories.artifacts}/bundle-structure.html`,
    }),
  ],
);
