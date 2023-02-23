/**
 * Convert px to rem
 * @param pixels
 * @param base
 * @returns {`${number}rem`}
 */
const rem = (pixels, base = 16) => `${pixels / base}rem`;

/**
 * Get Font size and Line height as an array for Tailwind
 * @param size Font size
 * @param lineHeight Line height
 * @returns {[string,string]}
 */
const fontSize = (size, lineHeight = 16) => [rem(size), rem(lineHeight)];

module.exports = {
  content: [
    './src/**/*.js',
    './templates/**/*.twig',
    './templates/*.html',
    './patterns/**/*.twig',
  ],
  theme: {
    colors: {
      transparent: 'transparent',
      current: 'currentColor',
      white: '#FFFFFF',
      black: {
        DEFAULT: '#000000',
        light: '#333',
      },
      gray: {
        DEFAULT: '#999',
        light: '#F1F1F1',
        silver: '#D4C7CF',
        dark: '#747474',
        darker: '#595A59',
      },
      purple: '#643F5D',
      yellow: '#FFCD57',
      eden: '#0D3E4F',
      tiber: '#0B3442',
      blue: {
        0: '#0B3442',
        100: '#0D3E4F',
        200: '#005A65',
        300: '#00616D',
        400: '#00818F',
        500: '#008392',
      },
    },
    extend: {
      fontSize: {
        'h1-mobile':    ['1.75rem', '2.125rem'],
        h1:             ['3.5rem', '4.125rem'],
        'h2-mobile':    ['1.625rem', '2rem'],
        h2:             ['2.375rem', '2.875rem'],
        'h3-mobile':    ['1.375rem', '1.625rem'],
        h3:             ['1.75rem', '2.25rem'],
        'h4-mobile':    ['1.125rem', '1.438rem'],
        h4:             ['1.5rem', '1.875rem'],
        'h5-mobile':    ['0.938rem', '1.375rem'],
        h5:             ['1.188rem', '1.5rem'],
        'base-mobile':  ['0.938rem', '1.375rem'],
        heading: fontSize(22, 24),
        'default-base': ['1rem', '1.25rem'],
        grande:       ['1.25rem', '1.625rem'],
        legendes:     ['0.813rem', '1.25rem'],
      },
      fontFamily: {
        base:  ['Montserrat', 'Helvetica', 'Arial', 'sans-serif'],
      },
      margin: {
        15: '3.75rem',
      },
      width: {
        '8.5/12': '70%',
      },
      minWidth: {
        '8': '2rem',
      },
      lineHeight: {
        12: '3rem',
      },
      backgroundSize: {
        full: '100%',
      },
      spacing: {
      },
      textDecoration: ['focus-visible'],
      boxShadow: {
        card    : '0px 8.01379px 14.0241px rgba(166, 170, 172, 0.035), 0px 2.89843px 5.07226px rgba(166, 170, 172, 0.0243888)',
        footer  : '0 0px 5px -10px rgba(0, 0, 0, 0.04), 0 6px 12px -10px rgba(0, 0, 0, 0.05)',
        toast   : '0px 9px 42px rgba(102, 116, 137, 0.05), 0px 3.75998px 17.5466px rgba(102, 116, 137, 0.0359427), 0px 2.01027px 9.38125px rgba(102, 116, 137, 0.0298054), 0px 1.12694px 5.25905px rgba(102, 116, 137, 0.025), 0px 0.598509px 2.79304px rgba(102, 116, 137, 0.0201946), 0px 0.249053px 1.16225px rgba(102, 116, 137, 0.0140573)',
      },
    },
    screens: {
      xs      : '576px',
      sm      : '667px',
      md      : '768px',
      lg      : '992px',
      xl      : '1200px',
      '2xl'   : '1440px',
      '3xl'   : '1600px',
    },
    borderWidth: {
      DEFAULT: '1px',
      '0': '0',
    }
  },
  corePlugins: {
    container : false,
    preflight : false,
  },
  /* https://tailwindcss.com/docs/content-configuration#safelisting-classes */
  safelist: [],
};
