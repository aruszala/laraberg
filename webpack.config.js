const path = require('path')
const webpack = require('webpack')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const CopyPlugin = require('copy-webpack-plugin')

const externals = {
  react: 'React',
  'react-dom': 'ReactDOM'
}

module.exports = {
  mode: process.env.NODE_ENV,
  entry: './src/resources/js/laraberg.js',
  output: {
    filename: 'laraberg.js',
    path: path.resolve(__dirname, 'public/js')
  },
  devtool: 'source-map',
  externals: externals,
  module: {
    rules: [
      {
        test: /\.(js|jsx)$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader'
        }
      },
      {
        test: /\.(s*)css$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader
          },
          'css-loader',
          'postcss-loader',
          'sass-loader'
        ]
      }
    ]
  },
  plugins: [
    new MiniCssExtractPlugin({ filename: '../css/laraberg.css' }),
    new CopyPlugin([
      { from: path.resolve(__dirname, 'public/js') + '/' + "laraberg.js", to: path.resolve(__dirname, '../../../public/vendor/laraberg/js') + '/' + "laraberg.js" },
      { from: path.resolve(__dirname, 'public/js') + '/' + "laraberg.js.map", to: path.resolve(__dirname, '../../../public/vendor/laraberg/js') + '/' + "laraberg.js.map" },
      { from: path.resolve(__dirname, 'public/css') + '/' + "laraberg.css", to: path.resolve(__dirname, '../../../public/vendor/laraberg/css') + '/' + "laraberg.css" },
      { from: path.resolve(__dirname, 'public/css') + '/' + "laraberg.css.map", to: path.resolve(__dirname, '../../../public/vendor/laraberg/css') + '/' + "laraberg.css.map" },
    ])
  ]
}
