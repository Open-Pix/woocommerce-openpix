const { configEnv } = require('./webpack/webpackCommon');

module.exports = [
  configEnv({
    name: 'prod',
    env: 'prod',
    filename: 'woo-openpix-prod.js',
    openpixEnv: 'production',
  }),
  configEnv({
    name: 'prod-contenthash',
    env: 'prod',
    filename: 'woo-openpix-prod-[contenthash].js',
    openpixEnv: 'production',
  }),
  configEnv({
    name: 'prod-final',
    env: 'prod',
    filename: 'woo-openpix.js',
    openpixEnv: 'production',
  }),
];
