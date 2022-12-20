<?php

/**
 * @file
 * Environment indicator settings.
 */

use Acquia\Blt\Robo\Common\EnvironmentDetector;

if (!EnvironmentDetector::isAhEnv()) {
  $config['environment_indicator.indicator']['name'] = 'Local';
  $config['environment_indicator.indicator']['bg_color'] = '#808080';
  $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
}
elseif (EnvironmentDetector::isProdEnv()) {
  $config['environment_indicator.indicator']['name'] = 'Prod';
  $config['environment_indicator.indicator']['bg_color'] = '#4C742C';
  $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
}
elseif (EnvironmentDetector::isStageEnv()) {
  $config['environment_indicator.indicator']['name'] = 'Stage';
  $config['environment_indicator.indicator']['bg_color'] = '#c50707';
  $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
}
elseif (EnvironmentDetector::isDevEnv()) {
  $config['environment_indicator.indicator']['name'] = 'Dev';
  $config['environment_indicator.indicator']['bg_color'] = '#d25e0f';
  $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
}
