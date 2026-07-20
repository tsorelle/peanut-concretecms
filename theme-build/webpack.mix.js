let mix = require("laravel-mix");

mix.webpackConfig({
  externals: {
    jquery: "jQuery",
    bootstrap: true,
    vue: "Vue",
    moment: "moment",
  },
});
// mix.setResourceRoot('../');
//mix.setPublicPath("../themes/basic_bedrock/");
mix.setPublicPath("./");
mix.sass("src/scss/app.scss", "output/css").js("src/js/app.js", "output/js");

// mix.browserSync({
//     proxy: 'c59.test' // You need to change this to your local dev URL for npm run watch or npx mix watch
// });
