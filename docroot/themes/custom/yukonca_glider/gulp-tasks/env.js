const PRODUCTION_ENV    = 'production';
const DEVELOPMENT_ENV   = 'development';
// Default to development. This site commits non-minified compiled assets and
// relies on Drupal's CSS/JS aggregation to minify at runtime, so a plain
// `gulp build` / `npm run build` is unminified. For a minified build, run with
// `NODE_ENV=production` explicitly.
const currentEnv        = process.env.NODE_ENV || DEVELOPMENT_ENV;
const isProductionEnv   = currentEnv === PRODUCTION_ENV;
const isDevelopmentEnv  = !isProductionEnv;

export {
  isProductionEnv,
  isDevelopmentEnv,
};
