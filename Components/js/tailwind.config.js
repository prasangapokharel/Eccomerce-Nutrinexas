module.exports = {
  content: [
    "../App/views/**/*.php",
    "../Components/**/*.php",
    "../public/**/*.html"
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f0f6ff',
          100: '#e6f0ff',
          500: '#0A3167',
          600: '#082850',
          700: '#061f3d'
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif']
      }
    }
  },
  plugins: []
}