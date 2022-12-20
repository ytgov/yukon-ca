module.exports = {
  content: [
    './src/**/*.js',
    './templates/**/*.twig',
    './templates/*.html',
    './patterns/**/*.twig',
  ],
  theme: {
    extend: {
      fontSize: {
        xxs   : ['0.625rem', '0.75rem'],
        // xs    : ['0.75rem', '1rem'], // included in TW code
        // sm    : ['0.875rem', '1.125rem'], // included in TW code
        // 15:   ['.9375rem', '1.5rem'], // Custom size
        md    : ['1rem', '1.5rem'],
        // lg    : ['1.125rem', '1.5rem'],  // included in TW code
        // xl    : ['1.25rem', '1.5rem'],  // included in TW code
        // '2xl' : ['1.5rem', '2rem'],  // included in TW code
        // '3xl' : ['1.875rem', '2.5rem'],  // included in TW code
        // '4xl' : ['2.25rem', '2.5rem'],  // included in TW code
        // '5xl' : ['3rem', '3.5rem'],  // included in TW code
      },
      fontFamily: {
        title:  ['Sharp Sans', 'sans-serif'],
        button: ['Sharp Sans', 'sans-serif'],
        serif: ['Sharp Sans', 'sans-serif'],
        sans:   ['Inter', 'sans-serif'],
        body:   ['Inter', 'sans-serif'],
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
