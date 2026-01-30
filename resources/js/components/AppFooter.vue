<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ScanLine } from 'lucide-vue-next';
</script>

<template>
    <footer
        class="fixed bottom-0 left-0 right-0 z-40 border-t border-white/10 bg-white/95 shadow-[0_-10px_30px_-20px_rgba(15,23,42,0.4)] backdrop-blur dark:border-white/5 dark:bg-[#0b1b3a]/95"
    >
        <div class="mx-auto flex h-16 items-center justify-center px-4 md:max-w-7xl lg:px-6">
            <Link
                href="/scan"
                class="group relative -mt-8 inline-flex items-center justify-center rounded-full bg-[#2563eb] p-4 text-white shadow-lg shadow-[#2563eb]/40 transition hover:-translate-y-0.5 hover:bg-[#1d4ed8] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#93c5fd]"
                aria-label="Open QR scanner"
            >
                <span
                    class="absolute inset-0 rounded-full bg-[#2563eb]/30 blur-xl transition group-hover:bg-[#2563eb]/50"
                    aria-hidden="true"
                ></span>
                <ScanLine class="relative h-6 w-6" />
            </Link>
        </div>
    </footer>
</template>
