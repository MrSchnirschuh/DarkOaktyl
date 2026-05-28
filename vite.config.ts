/// <reference types="vitest" />
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { dirname, resolve } from 'pathe';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';
import { visualizer } from 'rollup-plugin-visualizer';
import viteCompression from 'vite-plugin-compression';

const plugins = [
    react({
        babel: {
            plugins: ['babel-plugin-macros', 'babel-plugin-styled-components'],
        },
    }),
];

if (process.env.VITEST === undefined) {
    plugins.push(
        laravel({
            input: 'resources/scripts/index.tsx',
        }),
    );
}

plugins.push(
    viteCompression({
        algorithm: 'gzip',
        ext: '.gz',
        threshold: 1024,
        deleteOriginalAssets: false,
    }),
    viteCompression({
        algorithm: 'brotliCompress',
        ext: '.br',
        threshold: 1024,
        deleteOriginalAssets: false,
    }),
    visualizer({
        filename: 'public/build/stats.html',
        open: false,
        gzipSize: true,
        brotliSize: true,
    }),
);

export default defineConfig({
    define:
        process.env.VITEST === undefined
            ? {
                  'process.env': {},
                  'process.platform': null,
                  'process.version': null,
                  'process.versions': null,
              }
            : undefined,

    plugins,

    server: {
        cors: {
            origin: '*',
        },
    },

    resolve: {
        alias: {
            '@': resolve(dirname(fileURLToPath(import.meta.url)), 'resources', 'scripts'),
            '@definitions': resolve(
                dirname(fileURLToPath(import.meta.url)),
                'resources',
                'scripts',
                'api',
                'definitions',
            ),
            '@feature': resolve(
                dirname(fileURLToPath(import.meta.url)),
                'resources',
                'scripts',
                'components',
                'server',
                'features',
            ),
            '@account': resolve(
                dirname(fileURLToPath(import.meta.url)),
                'resources',
                'scripts',
                'components',
                'account',
            ),
            '@server': resolve(dirname(fileURLToPath(import.meta.url)), 'resources', 'scripts', 'components', 'server'),
            '@admin': resolve(dirname(fileURLToPath(import.meta.url)), 'resources', 'scripts', 'components', 'admin'),

            react: 'preact/compat',
            'react-dom': 'preact/compat',
            'react/jsx-runtime': 'preact/jsx-runtime',
            'react-dom/test-utils': 'preact/test-utils',
        },
    },

    build: {
        sourcemap: false,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        rollupOptions: {
            output: {
                manualChunks(id) {
                    // Isolate CodeMirror into its own chunk (heavy, rarely changes)
                    if (id.includes('node_modules/@codemirror')) {
                        return 'codemirror';
                    }

                    // Isolate xterm.js into its own chunk
                    if (id.includes('node_modules/xterm') || id.includes('node_modules/xterm-addon')) {
                        return 'xterm';
                    }

                    // Vendor chunk for all other node_modules
                    if (id.includes('node_modules')) {
                        // Keep small/stable libs together
                        if (
                            id.includes('preact') ||
                            id.includes('react-router-dom') ||
                            id.includes('easy-peasy') ||
                            id.includes('axios') ||
                            id.includes('formik') ||
                            id.includes('styled-components') ||
                            id.includes('twin.macro')
                        ) {
                            return 'vendor-core';
                        }
                        // UI libraries
                        if (
                            id.includes('@headlessui') ||
                            id.includes('@heroicons') ||
                            id.includes('@fortawesome') ||
                            id.includes('framer-motion')
                        ) {
                            return 'vendor-ui';
                        }
                        // Everything else
                        return 'vendor';
                    }
                },
            },
        },
    },

    test: {
        environment: 'happy-dom',
        include: ['resources/scripts/**/*.{spec,test}.{ts,tsx}'],
    },
});
