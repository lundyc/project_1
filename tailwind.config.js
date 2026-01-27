/** @type {import('tailwindcss').Config} */
const colors = require('tailwindcss/colors');
module.exports = {
  content: [
    './app/views/**/*.php',
    './app/**/*.js',
    './public/**/*.php',
    './public/assets/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        bg: {
          primary: 'var(--bg-primary)',
          secondary: 'var(--bg-secondary)',
          tertiary: 'var(--bg-tertiary)',
        },
        text: {
          primary: 'var(--text-primary)',
          secondary: 'var(--text-secondary)',
          muted: 'var(--text-muted)',
        },
        border: {
          subtle: 'var(--border-subtle)',
          soft: 'var(--border-soft)',
        },
        accent: {
          primary: 'var(--accent-primary)',
          secondary: 'var(--accent-secondary)',
          danger: 'var(--accent-danger)',
          warning: 'var(--accent-warning)',
          success: 'var(--accent-success)',
        },
        // No override of default palette, so stone, slate, etc. are available
      },
    },
  },
  plugins: [],
}

