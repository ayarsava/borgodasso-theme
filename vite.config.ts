import { defineConfig } from 'vite';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const themeDir = path.dirname(fileURLToPath(import.meta.url));

/**
 * Vite compiles theme TS/SCSS into dist/ for WordPress enqueue.
 * Use `npm run watch` during development (rebuilds on change; refresh the browser).
 */
export default defineConfig({
  root: themeDir,
  base: './',
  publicDir: false,
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: path.resolve(themeDir, 'assets/js/main.ts'),
      output: {
        // Stable names so watch + BrowserSync can inject CSS without a full reload.
        entryFileNames: 'assets/[name].js',
        chunkFileNames: 'assets/[name].js',
        assetFileNames: 'assets/[name][extname]',
      },
    },
  },
  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern-compiler',
      },
    },
  },
});
