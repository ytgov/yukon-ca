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
      black: '#000000',
      gray: '#747474',
      purple: '#643F5D',
      yellow: '#FFCD57',
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
        base:           ['1.063rem', '1.625rem'],
        'default-base': ['1rem', '1.25rem'],
        'grande':       ['1.25rem', '1.625rem'],
        'legendes':     ['0.813rem', '1.25rem'],
      },
      fontFamily: {
        base:  ['Montserrat', 'sans-serif'],
      },
      minWidth: {
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
  },
  corePlugins: {
    container : false,
    preflight : false,
  },
  /* https://tailwindcss.com/docs/content-configuration#safelisting-classes */
  safelist: [],
};
