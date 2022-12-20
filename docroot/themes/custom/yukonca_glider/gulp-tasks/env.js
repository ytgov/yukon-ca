const PRODUCTION_ENV    = 'production';
const currentEnv        = process.env.NODE_ENV || PRODUCTION_ENV;
const isProductionEnv   = currentEnv === PRODUCTION_ENV;
const isDevelopmentEnv  = !isProductionEnv;

export {
  isProductionEnv,
  isDevelopmentEnv,
};
