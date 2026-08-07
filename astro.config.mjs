// @ts-check
import { defineConfig } from 'astro/config';

import tailwindcss from '@tailwindcss/vite';

const API_TARGET = process.env.PUBLIC_API_URL ?? 'http://localhost/micasajems/backend/public';

// https://astro.build/config
export default defineConfig({
  vite: {
    plugins: [tailwindcss()],
    server: {
      proxy: {
        '/api': {
          target: API_TARGET,
          changeOrigin: true,
        },
        '/admin': {
          target: API_TARGET,
          changeOrigin: true,
        },
      },
    },
  },
});