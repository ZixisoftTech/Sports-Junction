/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./app/Views/**/*.php",
    "./public/**/*.php",
    "./public/assets/js/**/*.js"
  ],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        primary: "#00288e",
        "primary-container": "#1e40af",
        "on-primary": "#ffffff",
        secondary: "#006a61",
        "secondary-container": "#86f2e4",
        "on-secondary": "#ffffff",
        surface: "#f8f9ff",
        "on-surface": "#0b1c30",
        "on-surface-variant": "#444653",
        outline: "#757684",
        "outline-variant": "#c4c5d5",
        "surface-container": "#e5eeff",
        "surface-container-low": "#eff4ff",
        "surface-container-high": "#dce9ff",
        "surface-container-lowest": "#ffffff"
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        headline: ['Plus Jakarta Sans', 'sans-serif']
      }
    }
  },
  plugins: [
    require('@tailwindcss/typography'),
    require('@tailwindcss/forms'),
    require('@tailwindcss/aspect-ratio')
  ]
};
