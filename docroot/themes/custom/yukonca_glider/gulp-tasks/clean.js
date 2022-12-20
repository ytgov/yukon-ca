import { parallel }       from 'gulp';
import { cleanJs }        from './js';
import { cleanCss }       from './sass';
import { cleanPatterns }  from './patterns';

const clean = (done) => parallel(cleanJs, cleanCss, cleanPatterns)(done);

export default clean;
