const path = require('path')
const webpack = require('webpack')
var exec = require('child_process').exec
const MiniCssExtractPlugin = require('mini-css-extract-plugin')

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
          loader: 'babel-loader',
          options: {
            presets: [
              '@babel/preset-env',
              '@babel/preset-react',
              {
                plugins: [
                  '@babel/plugin-transform-runtime',
                  '@babel/plugin-proposal-class-properties'
                ]
              }
            ]
          },
        },
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
    {
        apply: (compiler) => {
            compiler.hooks.afterEmit.tap('AfterEmitPlugin', (compilation) => {
                const from = 'public', to = process.env.PWD + '/../../../public/vendor/laraberg'
                console.log("copying ", from, "=>", to);
                exec('cp -rf \"' + from + '\" \"' + to + "\"", (err, stdout, stderr) => {
                    if (stdout) process.stdout.write(stdout);
                    if (stderr) process.stderr.write(stderr);
                });
            });
        }
    }
  ]
}
