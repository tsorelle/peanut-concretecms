# Peanut Theme Package for Concrete CMS v9


**note:** See the [Concrete CMS Bedrock Documentation](https://documentation.concretecms.org/developers/appendix/concrete-cms-bedrock-foundation-concrete-and-concrete-themes) for ideas on how to use this theme starting point.

## Description

**This is not a fully-fledged theme and should not be used on a production site.**

This package is based no the work of [davedew](https://github.com/davedew) who created the [afixia-theme-basic-bedrock](https://github.com/davedew/afixia-theme-basic-bedrock) as a starting point for quick theme building for [Concrete CMS](https://www.concretecms.com/).  

This package will automatically install the thumbnails for Bootstrap5 so there is no manaul setup of these needed in the Dashboard.

JS and CSS are setup as assets in the package controller and required in the page_theme.php.

---

## Installation

1. connect your site to the Marketplace
2. select the [Basic Bedrock theme](https://market.concretecms.com/products/basic-bedrock/77dc25f1-76d2-11ef-951f-0a97d4ce16b9)
3. download to your site
4. (optional) if you want to use [Laravel
   Mix](https://laravel.com/docs/master/mix), to develop your theme,
   run `npm install` from the `/build` directory. After that
   completes, you can run `npm run prod` to compile the SCSS and JS
   assets in the build directory.

**note:** when delivering your completed theme, do not include the `/build` directory.


## Personalize for your project

- Change the `icon.png` (97px x 97px) in the `packages/theme_basic_bedrock` directory.
- Change the `thumbnail.png` (360px x 270px) in the `packages/theme_basic_bedrock/themes/basic_bedrock` directory.
- To change the name and descriptions. In the example changes below, we change the theme to be named "**Rock Solid**":
  -  **Directory names**
     - `packages/theme_rock_solid`
     - `packages/theme_rock_solid/theme/rock_solid`
   - **Namespaces**
     - **File:** `packages/theme_rock_solid/controller.php`:
       - `namespace Concrete\Package\ThemeRockSolid;`
     - **File:** `packages/theme_rock_solid/theme/rock_solid/page_theme.php`:
       - `namespace Concrete\Package\ThemeRockSolid\Theme\RockSolid;`
   - **Names and Descriptions**
     - **File:** `packages/theme_rock_solid/controller.php`:
       - `protected $pkgHandle = 'theme_rock_solid';`
       - `protected $themePath = 'themes/rock_solid/';`
       - `protected $themeName = 'Rock Solid';`
       - `protected $themeHandle = 'rock_solid';`
       - `public function getPackageDescription`: return a new description.
       - `public function on_start()`: Names of assets.
     - **File:** `packages/theme_rock_solid/theme/rock_solid/page_theme.php`:
       - `public function getThemeName()`: return a new theme name.
       - `public function getThemeDescription()`: return a new description.
       - `$this->requireAsset('basic-bedrock-app');`: choose new asset names.
     - **File:** `packages/theme_rock_solid/theme/rock_solid/description.txt`: change content.
     - **File:** `basic_bedrock_build/webpack.mix.js`:
       - `mix.js('src/js/app.js', 'js').setPublicPath('../packages/theme_rock_solid/themes/rock_solid/');`
       - `mix.sass('src/scss/app.scss', 'css').setPublicPath('../packages/theme_rock_solid/themes/rock_solid/');`
  
## Developing

### Use / Editing JS and CSS

When making changes to the JavaScript or CSS, make your changes in the
`build/src` folder then rebuild the js and css files in
the package with `npm run dev` or `npm run prod` for minified
versions.

### Node / NPM / Laravel Mix Build CSS / JavaScript

In [/build](build/) you will see the Laravel Mix setup.
You should be able to use the following documentation from
[laravel-mix/docs/cli.md](https://github.com/laravel-mix/laravel-mix/blob/master/docs/cli.md).

**note:** The `package.json` was created from my environment.  You
might want to start over from your own.  You can do so with the
following:

Remove the `package.json`, `package-lock.json` (if exists), and the
`node_modules` (if exists) folder and start over with the following:

In the `build` folder do the following:

1. `npm init -y`
2. `npm install laravel-mix --save-dev`
3. `npm install @concretecms/bedrock`
4. Leave the `webpack.mix.js` and all other files alone, then run 
5. `npm run prod`

---

#### Setup npm run watch:

If you want to develop fast with [Browser
Sync](https://laravel-mix.com/docs/5.0/browsersync) you'll need to
update the webpack.mix.js file.  You'll find that I have added the
following in there:

```javascript
mix.browserSync({
    proxy: 'concrete.test' // You need to change this to your local dev URL for npm run watch or npm run watch
});
```
Make sure you change the proxy url to your local development url.

---

## CLI Commands:

To build assets for development, reach for the `npm run dev` command. Mix will then read your `webpack.mix.js` configuration file, and compile your assets.

```
npm run dev
```

#### Watch Assets for Changes

Particularly for larger projects, compilation can take a bit of time. For this reason, it's highly recommended that you instead leverage webpack's ability to watch your filesystem for changes. The `npx mix watch` command will handle this for you. Now, each time you update a file, Mix will automatically recompile the file and rebuild your bundle. 

```
npm run watch
```

#### Polling

In certain situations, webpack may not automatically detect changes. An example of this is when you're on an NFS volume inside virtualbox. If this is a problem, pass the `--watch-options-poll` option directly to webpack-cli to turn on manual polling. 
 
 ```
npm run watch -- --watch-options-poll=1000
```

Of course, you can add this to a build script within your `package.json` file.

#### Hot Module Replacement

Hot module replacement is a webpack featured that gives supporting modules the ability to "live update" in certain situations. A live-update is when your application refreshes without requiring a page reload. In fact, this is what powers Vue's live updates when developing. To turn this feature on, include the `--hot` flag. 

```
npm run hot
```

### Compiling for Production

When it comes time to build your assets for a production environment, Mix will set the appropriate webpack options, minify your source code, and optionally version your assets based on your Mix configuration file (`webpack.mix.js`). To build assets for production, include the `--production` flag - or the alias `-p` - to the Mix CLI. Mix will take care of the rest!

```
npm run prod
```
