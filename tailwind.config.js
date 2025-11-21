/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",  // <--- INI PALING PENTING
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        // Tambahkan warna kustom kamu disini biar aman
        primary: '#ee4d2d', 
      }
    },
  },
  plugins: [],
}