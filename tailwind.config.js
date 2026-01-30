/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          green: '#0D5C3D',
          'green-light': '#1A7B52',
          gold: '#D4AF37',
          'gold-light': '#F6D365',
        },
        background: {
          primary: '#0A2E1F',
          secondary: '#0D3F2C',
          tertiary: '#124A35',
        },
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}
