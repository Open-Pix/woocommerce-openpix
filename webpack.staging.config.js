const { configEnv } = require('./webpack/webpackCommon');

module.exports = [
  configEnv({
    name: 'dev',
    env: 'dev',
    filename: 'woo-openpix-dev.js',
    openpixEnv: 'staging',
  }),
  configEnv({
    name: 'dev-contenthash',
    env: 'dev',
    filename: 'woo-openpix-dev-[contenthash].js',
    openpixEnv: 'staging',
  }),
];
