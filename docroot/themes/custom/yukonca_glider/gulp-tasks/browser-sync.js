import browserSync            from 'browser-sync';
import { browserSyncOptions } from './options';

const browserSyncWatcher = browserSync.create();

const serveInBrowser = (done) => {
  browserSyncWatcher.init(browserSyncOptions);
  done();
};

const browserSyncReload = (done) => {
  browserSyncWatcher.reload();
  done();
};

export {
  serveInBrowser,
  browserSyncReload,
};
