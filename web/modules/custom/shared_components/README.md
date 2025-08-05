# Shared Components Module

Este módulo permite compartir componentes SDC (Single Directory
Components) entre diferentes sitios de un entorno multisite en Drupal.

## 📂 Estructura del proyecto

    shared_components/
    ├── build/
    ├── components/
    ├── node_modules/
    ├── shared_components.info.yml
    ├── shared_components.libraries.yml
    ├── shared_components.module
    ├── webpack.mix.js
    ├── webpack.config.js
    ├── package.json
    ├── package-lock.json
    ├── .nvmrc
    └── mix-manifest.json

## 🚀 Instalación y configuración

### 1. Seleccionar la versión de Node.js

Este proyecto utiliza **NVM (Node Version Manager)** para asegurar la
versión correcta de Node.js.

``` bash
nvm use
```

Si no tienes NVM instalado:

``` bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
```

Luego reinicia tu terminal e instala la versión requerida:

``` bash
nvm install
nvm use
```

### 2. Instalar dependencias

Instala todas las dependencias de Node.js necesarias:

``` bash
npm install
```

### 3. Ejecutar compilación en desarrollo

Para compilar los componentes y habilitar el modo de desarrollo con
recompilación automática:

``` bash
npm run dev
```

O bien, si deseas que la compilación observe cambios en tiempo real:

``` bash
npm run watch
```

### 4. Generar build para producción

Cuando el proyecto esté listo para producción:

``` bash
npm run production
```

Esto optimizará los archivos y generará la versión final en la carpeta
`build/`.

## 🛠️ Notas

-   Los componentes deben ser creados dentro de la carpeta
    `components/`.
-   Cada componente debe incluir al menos:
    -   `*.component.yml`
    -   `*.twig`
    -   `*.scss` (opcional)
    -   `*.js` (opcional)
-   El módulo se encarga de registrar las librerías automáticamente
    mediante `hook_library_info_build()`.
