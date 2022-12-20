import path           from 'path';
import glob           from 'glob';
import { isProductionEnv } from './env';

const paths = {
  styles: {
    source: 'src/sass/',
    destination: 'dist/css/',
  },
  scripts: {
    source: 'src/js',
    destination: 'dist/js',
    bundlesPath: './src/js/bundles',
  },
  images: 'img/',
  patterns: 'patterns',
  templates: 'templates',
};

const browserSyncOptions = {
  // Put your local site URL here to prevent Browsersync
  // from prompting you to add additional scripts to your page.
  proxy: 'http://192.168.64.100:8080',
  open: 'external',
  xip: true,
  logConnections: true,
};

const cssOptions = {
  files: path.join(paths.styles.destination, '**/*.css'),
  file: path.join(paths.styles.destination, '/styles.min.css'),
  destination: path.join(paths.styles.destination),
};

const sassOptions = {
  files: path.join(paths.styles.source, '**/*.scss'),
  file: path.join(paths.styles.source, 'styles.scss'),
  /* To compile tailwind again */
  filesToWatch: [
    path.join(paths.scripts.source, '**/*.js'),
    path.join(paths.templates, '**/*.twig'),
    path.join(paths.templates, '*.html'),
    path.join(paths.patterns, '**/*.twig'),
  ],
  destination: path.join(paths.styles.destination),
  compilerOptions: {
    errLogToConsole: true,
    outputStyle: isProductionEnv ? 'compressed' : 'expanded',
    includePaths: [
      path.join(process.cwd(), '/node_modules/'),
      path.join(process.cwd(), '/src/sass/'),
    ],
  },
};

const jsOptions = {
  files: path.join(paths.scripts.source, '**/*.js'),
  bundlesFiles: path.join(paths.scripts.bundlesPath, '*.js'),
  destination: path.join(paths.scripts.destination),
};

const bundles = glob.sync(`./${path.join(paths.scripts.bundlesPath, '*.js')}`);
const bundlesEntry = {};
if (bundles && bundles.length) {
  bundles.forEach((file) => {
    const bundleFile = path.basename(file);
    const bundleName = bundleFile && bundleFile.replace('.js', '');
    bundlesEntry[bundleName] = file;
  });
}

/* Webpack bundles configuration */
const defaultWebpackConfig = {
  mode: isProductionEnv ? 'production' : 'development',
  output: {
    filename: '[name].bundle.min.js',
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env'],
            compact: false,
          },
        },
      },
    ],
  },
  devtool: !isProductionEnv && 'source-map',
  externals: {
    jquery: 'jQuery',
  },
};

const webpackConfig = {
  ...defaultWebpackConfig,
  entry: bundlesEntry,
};

const imagesOptions = {
  files: `${paths.images}**/*.{png,gif,jpg,svg}`,
  destination: paths.images,
};

const jsLintingOptions = {
  files: {
    theme: [`${paths.scripts}**/*.js`, `!${paths.scripts}**/*.min.js`],
    gulp: ['gulpfile.babel.js', 'gulp-tasks/**/*.js'],
  },
};

const pa11yOptions = {
  urls: [
    // An array of urls to test.
    // For testing in a travis environment:
    // 'http://127.0.0.1:8888',
  ],
  failOnError: true, // fail the build on error
  showFailedOnly: true, // show errors only and override reporter
  reporter: 'console',
  includeWarnings: true, // including warnings by default. - set it to false to disable
  includeNotices: true, // including notices by default. - set it to false to disable
  log: {
    // eslint-disable-next-line no-console
    debug: console.log.bind(console),
    // eslint-disable-next-line no-console
    error: console.error.bind(console),
    // eslint-disable-next-line no-console
    info: console.info.bind(console),
  },
  standard: 'WCAG2AA', // choose from Section508, WCAG2A, WCAG2AA, and WCAG2AAA
  page: {
    settings: {
      loadImages: false,
      userName: '', // .htacess username
      password: '', // .htaccess password
    },
  },
  threshold: {
    // Set to -1 for no threshold.
    errors: 1,
    warnings: 10,
    notices: -1,
  },
};

const patternsPath = path.join(paths.patterns, '**/js/*.js');
const patternsJsFiles = glob.sync(patternsPath, { ignore: `${path.join(paths.patterns, '**/js/*.min.js')}` });

let patternsWebpackConfig = [];
if (patternsJsFiles && patternsJsFiles.length) {
  patternsWebpackConfig = patternsJsFiles.map((file) => {
    const parts = path.dirname(file).split(path.sep);
    parts.pop();
    const parent = parts.join(path.sep);
    const bundleFile = path.basename(file);
    const bundleName = bundleFile && bundleFile.replace('.js', '');

    return {
      ...defaultWebpackConfig,
      entry: {
        [bundleName]: `./${file}`,
      },
      output: {
        filename:`${parent}/dist/js/[name].min.js`,
      },
    };
  });
}

const patternsOptions = {
  css: {
    src: path.join(paths.patterns, '**/*.scss'),
    ommit: `!${path.join(paths.patterns, 'styles.scss')}`,
    dest: paths.patterns,
  },
  js: {
    src: [path.join(paths.patterns, '**/js/*.js'), `!${path.join(paths.patterns, '**/js/*.min.js')}`],
    dest: paths.patterns,
  },
  clean: path.join(paths.patterns, '**/dist'),
  webpackConfig: patternsWebpackConfig,
};

const iconFontOptions = {
  name        : 'theme-icons',
  inputDir    : './src/icons/pictos/',
  outputDir   : './dist/',
  fontsUrl    : '..',
  fontTypes   : ['ttf', 'woff', 'woff2'],
  assetTypes  : ['scss', 'html'],
  pathOptions : {
    scss: './src/sass/_theme-icons.scss',
  },
  templates   : {
    scss: 'src/icons/templates/css.hbs',
    html: 'src/icons/templates/html.hbs',
  },
  normalize   : true,
};

const options = {
  browserSync : browserSyncOptions,
  css         : cssOptions,
  sass        : sassOptions,
  js          : jsOptions,
  images      : imagesOptions,
  jsLinting   : jsLintingOptions,
  pa11y       : pa11yOptions,
  patterns    : patternsOptions,
  iconFont    : iconFontOptions,
  webpack     : webpackConfig,
};

export default options;

export {
  paths,
  browserSyncOptions,
  cssOptions,
  sassOptions,
  jsOptions,
  imagesOptions,
  jsLintingOptions,
  pa11yOptions,
  patternsOptions,
  iconFontOptions,
  webpackConfig,
};
