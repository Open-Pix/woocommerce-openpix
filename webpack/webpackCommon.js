const path = require('path');

const dotEnv = require('dotenv-webpack');
const { merge } = require('webpack-merge');
const webpack = require('webpack');

const cwd = process.cwd();
const outputPath = path.join(cwd, 'assets/js');

const now = new Date();
const buildTimeStamp = now.toISOString();

const common = {
  mode: 'production',
  context: path.resolve(cwd, './'),
  entry: ['./checkout/src/index.tsx'],
  output: {
    path: outputPath,
    publicPath: '/',
    pathinfo: false,
    libraryTarget: 'umd',
  },
  resolve: {
    extensions: ['.ts', '.tsx', '.js', '.json', '.mjs'],
  },
  module: {
    rules: [
      {
        test: /\.(js|jsx|ts|tsx)?$/,
        exclude: [/node_modules/],
        use: ['babel-loader?cacheDirectory'],
      },
    ],
  },
};

const configEnv = ({ name, env, filename, openpixEnv }) => {
  return merge(common, {
    name,
    output: {
      filename,
    },
    plugins: [
      new dotEnv({
        path: `./.env.${env}`,
      }),
      new webpack.DefinePlugin({
        'process.env.OPENPIX_ENV': JSON.stringify(openpixEnv),
        'process.env.COMMIT_SHA': JSON.stringify(process.env.COMMIT_SHA),
        'process.env.SENTRY_RELEASE': JSON.stringify(process.env.COMMIT_SHA),
        'process.env.BUILD_TIMESTAMP': JSON.stringify(buildTimeStamp),
      }),
    ],
  });
};

module.exports.configEnv = configEnv;
