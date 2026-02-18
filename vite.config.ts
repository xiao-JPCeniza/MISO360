import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                chunkFileNames: (chunkInfo) => {
                    const name = chunkInfo.name ?? '';
                    const facade = chunkInfo.facadeModuleId ?? '';
                    const needsPathName = !name || name === '.' || name === 'index' || name === 'Index';
                    if (needsPathName) {
                        const pathPart = facade
                            ? path.dirname(facade).replace(/\\/g, '/').split('/').slice(-2).filter(Boolean).join('-')
                            : 'lazy';
                        return `assets/${pathPart || 'chunk'}-[hash].js`;
                    }
                    return `assets/[name]-[hash].js`;
                },
            },
        },
    },
});
