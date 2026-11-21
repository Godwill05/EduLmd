/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './public/assets/js/*.js',
    './app/**/*.php',
  ],
  theme: {
    extend: {
      colors: {
        // Rouge bordeaux — couleur principale
        bordeaux: {
          50: '#FCF5F6',
          100: '#F9E8EB',
          200: '#F2D2D9',
          300: '#E4ACB8',
          400: '#CF7C8E',
          500: '#B55168',
          600: '#9B3450',
          700: '#822A40',
          800: '#6B2435',
          900: '#591E2C',
          950: '#310E17',
        },
        // Bleu — couleur secondaire
        bleu: {
          50: '#EFF4FF',
          100: '#DBE7FE',
          200: '#BFD4FE',
          300: '#93B5FD',
          400: '#608CFA',
          500: '#3B67F6',
          600: '#254AEB',
          700: '#1D38D8',
          800: '#1E31AF',
          900: '#1E2F8A',
          950: '#171F54',
        },
        encre: {
          DEFAULT: '#0B0B0C',
          doux: '#3F4147',
          muted: '#5C5F66',
          faint: '#8A8D94',
        },
        canvas: '#FBFBFC',
        ligne: '#E6E6EA',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'],
      },
      fontSize: {
        '2xs': ['0.6875rem', { lineHeight: '1rem' }],
      },
      borderRadius: {
        xl2: '1rem',
        xl3: '1.25rem',
      },
      boxShadow: {
        carte: '0 1px 2px 0 rgba(16, 24, 40, 0.04), 0 1px 3px 0 rgba(16, 24, 40, 0.06)',
        leve: '0 4px 6px -2px rgba(16, 24, 40, 0.05), 0 12px 16px -4px rgba(16, 24, 40, 0.08)',
        panneau: '0 8px 30px rgba(16, 24, 40, 0.10)',
        bordeaux: '0 4px 14px rgba(130, 42, 64, 0.24)',
        bleu: '0 4px 14px rgba(29, 56, 216, 0.20)',
      },
      transitionTimingFunction: {
        doux: 'cubic-bezier(0.22, 1, 0.36, 1)',
      },
      spacing: {
        18: '4.5rem',
        22: '5.5rem',
        68: '17rem',
        72: '18rem',
      },
    },
  },
  plugins: [],
}
