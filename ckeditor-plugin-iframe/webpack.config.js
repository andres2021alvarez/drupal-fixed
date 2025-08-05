const path = require('path');

module.exports = {
  entry: './src/iframe-lazy-loading.js',
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: 'iframe-lazy-loading.js',
    library: 'IframeLazyLoading',
    libraryTarget: 'umd',
    globalObject: 'this',
  },
  mode: 'production',
  module: {
    rules: [
      {
        test: /\.js$/,
        use: 'babel-loader',
        exclude: /node_modules/,
      },
    ],
  },
};
