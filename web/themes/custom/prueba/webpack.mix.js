/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your application. See https://github.com/JeffreyWay/laravel-mix.
 |
*/
/* eslint-disable */
require("dotenv").config({ path: ".env.local" });
const mix = require("laravel-mix");
const glob = require("glob");
const path = require("path");
const { log } = require("console");
require("laravel-mix-stylelint");
require("laravel-mix-copy-watched");

/*
 |--------------------------------------------------------------------------
 | Configuration
 |--------------------------------------------------------------------------
*/
mix
  .sourceMaps()
  .webpackConfig({
    resolve: {
      symlinks: false, // Cambia esto a false en lugar de true
      modules: [
        path.resolve(__dirname, "node_modules"),
        path.resolve(__dirname, "components"),
        path.resolve(__dirname, "components/global"), // Asegúrate de incluir la ruta específica
      ],
    },
    watchOptions: {
      followSymlinks: true,
      ignored: /node_modules/,
      aggregateTimeout: 300,
      poll: 1000,
    },
    snapshot: {
      managedPaths: [],
    },
  })
  .disableNotifications()
  .options({
    processCssUrls: false,
  });

/*
 |--------------------------------------------------------------------------
 | Browsersync
 |--------------------------------------------------------------------------
*/
mix.browserSync({
  proxy: process.env.DRUPAL_BASE_URL,
  files: [
    path.resolve(__dirname, "components/**/*.scss"),
    path.resolve(__dirname, "components/**/*.js"),
    path.resolve(__dirname, "components/**/*.twig"),
    path.resolve(__dirname, "templates/**/*.twig"),
    path.resolve(__dirname, "build/css/*.css"),
    path.resolve(__dirname, "build/js/*.js"),
  ],
  stream: true,
});

/*
 |--------------------------------------------------------------------------
 | SASS
 |--------------------------------------------------------------------------
*/
mix.sass("src/scss/main.style.scss", "build/css/main.style.css");

for (const sourcePath of glob.sync('components/**/*.scss', { follow: true })) {
  const destinationPath = sourcePath.replace(/\.scss$/, '.css');
  mix.sass(sourcePath, destinationPath);
}

/*
 |--------------------------------------------------------------------------
 | JS
 |--------------------------------------------------------------------------
*/
mix.js("src/js/main.script.js", "build/js/main.script.js");

for (const sourcePath of glob.sync("components/**/_*.js")) {
  const destinationPath = sourcePath.replace(/\/_([^/]+\.js)$/, "/$1");
  mix.js(sourcePath, destinationPath);
}

/*
 |--------------------------------------------------------------------------
 | Style Lint
 |--------------------------------------------------------------------------
*/
mix.stylelint({
  configFile: "./.stylelintrc.json",
  context: "./src",
  failOnError: false,
  files: ["**/*.scss"],
  quiet: false,
  customSyntax: "postcss-scss",
});

/*
 |--------------------------------------------------------------------------
 | IMAGES / ICONS / VIDEOS / FONTS
 |--------------------------------------------------------------------------
*/
// Copia los recursos estáticos sin optimización de imágenes
mix.copyDirectoryWatched("src/assets/images", "build/assets/images");
mix.copyDirectoryWatched("src/assets/icons", "build/assets/icons");
mix.copyDirectoryWatched("src/assets/videos", "build/assets/videos");
mix.copyDirectoryWatched("src/assets/fonts/**/*", "build/fonts");
