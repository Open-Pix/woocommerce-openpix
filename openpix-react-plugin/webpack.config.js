const path = require('path');

// const HtmlWebpackPlugin = require('html-webpack-plugin');
const dotEnv = require('dotenv-webpack');
const ReactRefreshPlugin = require('@pmmmwh/react-refresh-webpack-plugin');

const cwd = process.cwd();
const outputPath = path.join(cwd, 'build');

const PORT = parseInt(process.env.PORT || '6688');

module.exports = {
  mode: 'development',
  devtool: 'cheap-module-source-map',
  context: path.resolve(cwd, './'),
  entry: ['./src/index.tsx'],
  output: {
    path: outputPath,
    publicPath: '/',
    pathinfo: false,
    filename: 'main.js',
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
      {
        test: /\.(jpe?g|png|gif|svg|pdf|csv|xlsx|ttf|woff(2)?)$/i,
        use: [
          {
            loader: 'file-loader',
            options: {
              name: '[name].[ext]',
              outputPath: 'img/',
            },
          },
        ],
      },
      {
        test: /\.css$/,
        use: ['style-loader', 'css-loader'],
      },
    ],
  },
  devServer: {
    contentBase: outputPath,
    disableHostCheck: true,
    historyApiFallback: {
      disableDotRule: true,
    },
    hot: true,
    hotOnly: false,
    compress: true,
    port: PORT,
    sockPort: PORT,
    // wordpress related
    liveReload: false,
    writeToDisk: true,
    headers: { "Access-Control-Allow-Origin": "*" }
  },
  plugins: [
    new dotEnv({
      path: './.env',
    }),
    new ReactRefreshPlugin(),
    // new HtmlWebpackPlugin({
    //   template: './src/index.html',
    // }),
  ],
};
