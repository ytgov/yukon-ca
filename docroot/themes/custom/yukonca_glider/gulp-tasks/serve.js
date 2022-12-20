import { series }           from 'gulp';
import { watch }            from './watch';
import { serveInBrowser }   from './browser-sync';
import build                from './build';
import clean                from './clean';

const serve = (done) => series(clean, build, serveInBrowser, watch)(done);

export default serve;
