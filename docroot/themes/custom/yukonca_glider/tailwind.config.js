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
  content: ['./src/**/*.js', './templates/**/*.twig', './templates/*.html', './patterns/**/*.twig'],
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
        100: '#F5F5F5',
        400: '#DDDDDD',
        light: '#F1F1F1',
        lightgray: '#EEE',
        lightborder: '#D4C7CF',
        dark: '#747474',
        darker: '#595A59',
      },
      purple: '#643F5D',
      yellow: '#FFCD57',
      alert: {
        background: '#FFF4E4',
        text: '#444142',
      },
      eden: '#0D3E4F',
      tiber: '#0B3442',
      blue: {
        0: '#0B3442',
        50: '#00535c',
        100: '#0D3E4F',
        200: '#005A65',
        300: '#00616D',
        400: '#00818F',
        500: '#008392',
        600: '#00454E',
        mid: '#72D0DC',
        icon: '#008190',
      },
      pagination: {
        seprater: '#7a7979',
      },
    },
    extend: {
      fontSize: {
        'h1-mobile': ['1.75rem', '2.125rem'],
        h1: ['3.5rem', '4.125rem'],
        'h2-mobile': ['1.625rem', '2rem'],
        h2: ['2.375rem', '2.875rem'],
        'h3-mobile': ['1.375rem', '1.625rem'],
        h3: ['1.75rem', '2.25rem'],
        'h4-mobile': ['1.125rem', '1.438rem'],
        h4: ['1.5rem', '1.875rem'],
        'h5-mobile': ['0.938rem', '1.375rem'],
        h5: ['1.188rem', '1.5rem'],
        'base-mobile': ['0.938rem', '1.375rem'],
        heading: fontSize(22, 24),
        'default-base': ['1rem', '1.25rem'],
        grande: ['1.25rem', '1.625rem'],
        legendes: ['0.813rem', '1.25rem'],
        '3.5xl': ['2.125rem', '2.3375rem'],

        /* Issue 211 */
        'page-heading': ['2rem', '2rem'],
        /* Issue 211 End */
      },
      fontFamily: {
        base: ['Montserrat', 'Helvetica', 'Arial', 'sans-serif'],
      },
      margin: {
        15: rem(15 * 4),
        7.5: rem(7.5 * 4),
      },
      padding: {
        2.5: rem(2.5 * 4),
        3.75: rem(3.75 * 4),
        7.5: rem(7.5 * 4),
      },
      width: {
        '8.5/12': '70%',
      },
      minWidth: {
        8: rem(8 * 4),
      },
      lineHeight: {
        12: rem(12 * 4),
        1.1: '1.1',
      },
      backgroundSize: {
        full: '100%',
      },
      textDecoration: ['focus-visible'],
      boxShadow: {
        card: '4px 2px 5px 0 rgb(81 42 68 / 25%)',
        footer: '0 0px 5px -10px rgba(0, 0, 0, 0.04), 0 6px 12px -10px rgba(0, 0, 0, 0.05)',
        toast: '0px 9px 42px rgba(102, 116, 137, 0.05), 0px 3.75998px 17.5466px rgba(102, 116, 137, 0.0359427), 0px 2.01027px 9.38125px rgba(102, 116, 137, 0.0298054), 0px 1.12694px 5.25905px rgba(102, 116, 137, 0.025), 0px 0.598509px 2.79304px rgba(102, 116, 137, 0.0201946), 0px 0.249053px 1.16225px rgba(102, 116, 137, 0.0140573)',
      },

      /* Issue 210 */
      borderWidth: {
        1: '1px',
      },
      /* Issue 210 End */
    },
    screens: {
      xs: '576px',
      sm: '667px',
      md: '768px',
      lg: '992px',
      xl: '1200px',
      '2xl': '1440px',
      '3xl': '1600px',
    },
    listStyleType: {
      circle: 'circle',
      none: 'none',
      disc: 'disc',
    },
  },
  corePlugins: {
    container: false,
    preflight: false,
  },
  /* https://tailwindcss.com/docs/content-configuration#safelisting-classes */
  safelist: [],
}
