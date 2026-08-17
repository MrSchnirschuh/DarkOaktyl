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
        // ponytail: dev-only, restrict in production via nginx
        cors: {
            origin: ['http://localhost:3000', 'http://127.0.0.1:3000'],
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

            '@elements': resolve(dirname(fileURLToPath(import.meta.url)), 'resources', 'scripts', 'components', 'elements'),

            react: 'preact/compat',
            'react-dom': 'preact/compat',
            'react/jsx-runtime': 'preact/jsx-runtime',
            'react-dom/test-utils': 'preact/test-utils',
        },
    },

    build: {
        sourcemap: false,
        minify: false,
        // NO manualChunks — single bundle to avoid circular dependency TDZ issues
    },

    test: {
        environment: 'happy-dom',
        include: ['resources/scripts/**/*.{spec,test}.{ts,tsx}'],
    },
});