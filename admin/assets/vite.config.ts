import {fileURLToPath, URL} from 'node:url'

import {defineConfig} from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [
        // Fixes warnings like : Failed to resolve component: fluent-button
        vue({
          template: {
            compilerOptions: {
              // treat all tags with a dash as custom elements
              isCustomElement: (tag) => tag.includes("Icon"),
            },
          },
        }),
      ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./src', import.meta.url))
        }
    },
    build: {
        emptyOutDir: true,
        minify: true,
        cssMinify: true,
        rollupOptions: {
            output: {
                entryFileNames: "[name].js",
                chunkFileNames: "[name].js",
                assetFileNames: "[name][extname]",
            },
        }
    },
    server: {
        port: 5000,
        hmr: {
            port: 5000,
            host: 'localhost',
            protocol: 'ws'
        }
    }
})
