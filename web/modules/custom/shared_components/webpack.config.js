const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const fs = require('fs');

// Función para encontrar automáticamente todos los archivos .scss de componentes
function getEntries() {
  const entries = {};
  const componentsDir = path.join(__dirname, 'components');

  try {
    // Verificar si existe el directorio components
    if (!fs.existsSync(componentsDir)) {
      console.log('📁 Directorio components no encontrado, creándolo...');
      fs.mkdirSync(componentsDir, { recursive: true });
      return entries;
    }

    // Leer directorios de componentes
    const componentDirs = fs.readdirSync(componentsDir, { withFileTypes: true })
      .filter(dirent => dirent.isDirectory())
      .map(dirent => dirent.name);

    componentDirs.forEach(componentName => {
      const componentDir = path.join(componentsDir, componentName);
      const scssFile = path.join(componentDir, `${componentName}.scss`);

      // Verificar si existe el archivo .scss
      if (fs.existsSync(scssFile)) {
        entries[`components/${componentName}/${componentName}`] = scssFile;
        console.log(`✅ Encontrado: ${componentName}.scss`);
      }
    });

    if (Object.keys(entries).length === 0) {
      console.log('📝 No se encontraron archivos .scss en componentes');
    }

  } catch (error) {
    console.log('⚠️  Error leyendo directorio components:', error.message);
  }

  return entries;
}

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  const componentEntries = getEntries();

  // Configuración de entrada
  const entries = {
    // Solo agregar global si existe el archivo
    ...(fs.existsSync('./src/global.scss') ? { 'dist/global': './src/global.scss' } : {}),
    // Agregar JavaScript global si existe
    ...(fs.existsSync('./src/global.js') ? { 'dist/global': './src/global.js' } : {}),
    // Agregar componentes encontrados
    ...componentEntries
  };

  // Si no hay entradas, crear una entrada dummy
  if (Object.keys(entries).length === 0) {
    console.log('⚠️  No se encontraron archivos .scss, creando entrada dummy');
    entries['dist/dummy'] = path.join(__dirname, 'webpack-dummy.js');

    // Crear archivo dummy si no existe
    const dummyFile = path.join(__dirname, 'webpack-dummy.js');
    if (!fs.existsSync(dummyFile)) {
      fs.writeFileSync(dummyFile, '// Archivo dummy para webpack - se puede eliminar cuando tengas archivos .scss\nconsole.log("Shared Components Module loaded");');
    }
  }

  console.log('📦 Entradas de Webpack:', Object.keys(entries));

  return {
    entry: entries,

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
        },
        {
          test: /\.js$/,
          use: 'babel-loader',
          exclude: /node_modules/
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
        '**/*.twig',
        '**/*.yml'
      ]
    },

    stats: {
      colors: true,
      modules: false,
      chunks: false,
      chunkModules: false
    }
  };
};