/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './content.html',
  ],
  presets: [
    require('./preset.js')
  ],
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
    require('@tailwindcss/line-clamp'),
    require('@tailwindcss/aspect-ratio'),
  ],
}
