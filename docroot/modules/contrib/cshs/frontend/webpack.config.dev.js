const { configure } = require('./webpack.config.common');

module.exports = configure(
  'development',
  'eval-cheap-module-source-map',
);
