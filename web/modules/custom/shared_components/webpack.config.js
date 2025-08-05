const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const glob = require('glob');

// Función para encontrar automáticamente todos los archivos .scss de componentes
function getEntries() {
  const entries = {};

  // Buscar todos los archivos .scss en las carpetas de componentes
  const scssFiles = glob.sync('./components/**/*.scss');

  scssFiles.forEach(file => {
    // Extraer el nombre del componente del path
    const matches = file.match(/\/components\/([^\/]+)\/[^\/]+\.scss$/);
    if (matches) {
      const componentName = matches[1];
      entries[`components/${componentName}/${componentName}`] = file;
    }
  });

  return entries;
}

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';

  return {
    entry: {
      // Compilar archivos globales si los tienes
      'dist/global': './src/global.scss',
      // Agregar dinámicamente todos los componentes
      ...getEntries()
    },

    output: {
      path: path.resolve(__dirname),
      filename: '[name].js',
      clean: false // No limpiar para preservar otros archivos
    },

    module: {
      rules: [
        {
          test: /\.scss$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            {
              loader: 'sass-loader',
              options: {
                sassOptions: {
                  outputStyle: isProduction ? 'compressed' : 'expanded',
                  includePaths: [
                    path.resolve(__dirname, 'src/scss'),
                    path.resolve(__dirname, 'node_modules')
                  ]
                }
              }
            }
          ]
        },
        {
          test: /\.(png|jpg|jpeg|gif|svg)$/,
          type: 'asset/resource',
          generator: {
            filename: 'dist/images/[name][ext]'
          }
        },
        {
          test: /\.(woff|woff2|eot|ttf|otf)$/,
          type: 'asset/resource',
          generator: {
            filename: 'dist/fonts/[name][ext]'
          }
        }
      ]
    },

    plugins: [
      new MiniCssExtractPlugin({
        filename: '[name].css'
      })
    ],

    resolve: {
      extensions: ['.scss', '.css', '.js']
    },

    devtool: isProduction ? false : 'source-map',

    watch: argv.watch || false,
    watchOptions: {
      aggregateTimeout: 300,
      poll: 1000,
      ignored: [
        '**/node_modules',
        '**/dist',
        '**/*.twig'
      ]
    }
  };
};