import { generateFonts } from 'fantasticon';
import {
  iconFontOptions,
} from './options';

const makeIconsFont = (done) => {
  generateFonts(iconFontOptions).then(() => {
    done();
  });
};

export default makeIconsFont;
