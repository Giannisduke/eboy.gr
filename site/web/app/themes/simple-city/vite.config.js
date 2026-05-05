import { defineConfig } from 'vite'
//import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin'
import { wordpressPlugin, wordpressThemeJson } from '@roots/vite-plugin';
import vue from '@vitejs/plugin-vue'
import vuetify from 'vite-plugin-vuetify'


export default defineConfig({

  base: '/app/themes/simple-city/public/build/',
  build: {
    chunkSizeWarningLimit: 600,
  },
  server: {
    cors: true,
    strictPort: true,
  },
  plugins: [
   // tailwindcss(),
    vue(),
    vuetify({ autoImport: true }),
    laravel({
      input: [
        'resources/css/app.scss',
        'resources/js/app.js',
        'resources/css/editor.scss',
        'resources/js/editor.js',
      ],
      refresh: true,
    }),

    wordpressPlugin(),

    // Generate the theme.json file in the public/build/assets directory
    // based on the Tailwind config and the theme.json file from base theme folder
    wordpressThemeJson({
      disableTailwindColors: true,
      disableTailwindFonts: true,
      disableTailwindFontSizes: true,
    }),
  ],
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `
          @import "bootstrap/scss/functions";
          @import "/resources/css/custom/shared-variables";
          @import "bootstrap/scss/variables";
          @import "bootstrap/scss/variables-dark";
          @import "bootstrap/scss/maps";
          @import "bootstrap/scss/mixins";
        `,
      },
    },
  },
  resolve: {
    alias: {
      '@scripts': '/resources/js',
      '@styles': '/resources/css',
      '@fonts': '/resources/fonts',
      '@images': '/resources/images',
    },
  },
})
