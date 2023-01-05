#!/usr/local/bin/node
const { directories } = require('../webpack.config.common.js');

const names = process.argv.splice(2);

if (names.length === 0) {
  throw new Error(`Pass at least one of the following arguments: ${Object.keys(directories).join(', ')}.`);
}

// eslint-disable-next-line no-restricted-syntax
for (const name of names) {
  if (directories[name] === undefined) {
    throw new Error(`The "${name}" is not valid directory ID. Use one of: ${Object.keys(directories).join(', ')}.`);
  }

  // eslint-disable-next-line no-console
  console.log(directories[name]);
}
