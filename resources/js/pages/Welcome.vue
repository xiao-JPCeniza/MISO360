<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import { dashboard, login } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div
        class="min-h-screen scroll-smooth bg-gradient-to-b from-[#0b1b3a] via-[#0f2a5f] to-[#0b1b3a] text-white"
        style="font-family: 'Sora', ui-sans-serif, system-ui;"
    >
        <header
            class="sticky top-0 z-50 w-full border-b border-white/10 bg-white/95 text-[#0b1b3a] shadow-sm shadow-black/10 backdrop-blur"
        >
            <div class="mx-auto w-full max-w-6xl px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img
                            src="/favicon.svg"
                            alt="System logo"
                            class="h-10 w-10 rounded-full bg-white p-1"
                        />
                        <div>
                            <p class="text-sm uppercase tracking-[0.2em] text-[#2563eb]">
                                MSO 360
                            </p>
                            <p class="text-lg font-semibold leading-tight">
                                Management Services Office
                            </p>
                        </div>
                    </div>
                    <nav class="hidden items-center gap-6 text-sm font-semibold md:flex">
                        <a
                            href="#profile"
                            class="transition-colors hover:text-[#2563eb]"
                        >
                            MSO Profile
                        </a>
                        <a
                            href="#team"
                            class="transition-colors hover:text-[#2563eb]"
                        >
                            Our Team
                        </a>
                        <a
                            href="#services"
                            class="transition-colors hover:text-[#2563eb]"
                        >
                            Services
                        </a>
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="rounded-full border border-[#2563eb] bg-[#2563eb] px-4 py-2 text-white transition-colors hover:bg-[#1d4ed8]"
                        >
                            Dashboard
                        </Link>
                        <Link
                            v-else
                            :href="login()"
                            class="rounded-full border border-[#2563eb] px-4 py-2 text-[#2563eb] transition-colors hover:bg-[#2563eb]/10"
                        >
                            Log in
                        </Link>
                    </nav>
                </div>
                <nav
                    class="mt-3 flex flex-wrap items-center gap-3 text-sm font-semibold md:hidden"
                >
                    <a
                        href="#profile"
                        class="rounded-full border border-[#2563eb]/30 px-3 py-1.5 text-[#0b1b3a] transition-colors hover:bg-[#2563eb]/10"
                    >
                        MSO Profile
                    </a>
                    <a
                        href="#team"
                        class="rounded-full border border-[#2563eb]/30 px-3 py-1.5 text-[#0b1b3a] transition-colors hover:bg-[#2563eb]/10"
                    >
                        Our Team
                    </a>
                    <a
                        href="#services"
                        class="rounded-full border border-[#2563eb]/30 px-3 py-1.5 text-[#0b1b3a] transition-colors hover:bg-[#2563eb]/10"
                    >
                        Services
                    </a>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-full border border-[#2563eb] bg-[#2563eb] px-3 py-1.5 text-white transition-colors hover:bg-[#1d4ed8]"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-else
                        :href="login()"
                        class="rounded-full border border-[#2563eb] px-3 py-1.5 text-[#2563eb] transition-colors hover:bg-[#2563eb]/10"
                    >
                        Log in
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-6 pb-20 pt-14 lg:pt-20">
            <section
                id="profile"
                class="grid items-center gap-10 lg:grid-cols-[1.1fr_0.9fr]"
            >
                <div class="space-y-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[#93c5fd]">
                        MSO Profile
                    </p>
                    <h1 class="text-4xl font-bold leading-tight sm:text-5xl lg:text-6xl">
                        Building responsive, people-first management services.
                    </h1>
                    <p class="text-base text-white/80 sm:text-lg">
                        The Management Services Office (MSO) partners with every unit
                        to simplify processes, align resources, and deliver support
                        that keeps operations efficient and transparent.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <a
                            href="#services"
                            class="rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-[#0b1b3a] transition-transform hover:-translate-y-0.5"
                        >
                            Explore services
                        </a>
                        <a
                            href="#team"
                            class="rounded-full border border-white/30 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-white/10"
                        >
                            Meet the team
                        </a>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div
                        class="rounded-2xl bg-white/10 p-6 shadow-lg shadow-black/20 backdrop-blur"
                    >
                        <p class="text-3xl font-semibold">24/7</p>
                        <p class="mt-2 text-sm text-white/70">
                            Service support availability
                        </p>
                    </div>
                    <div
                        class="rounded-2xl bg-white/10 p-6 shadow-lg shadow-black/20 backdrop-blur"
                    >
                        <p class="text-3xl font-semibold">120+</p>
                        <p class="mt-2 text-sm text-white/70">
                            Active process improvements
                        </p>
                    </div>
                    <div
                        class="rounded-2xl bg-white/10 p-6 shadow-lg shadow-black/20 backdrop-blur"
                    >
                        <p class="text-3xl font-semibold">98%</p>
                        <p class="mt-2 text-sm text-white/70">
                            Internal client satisfaction
                        </p>
                    </div>
                    <div
                        class="rounded-2xl bg-white/10 p-6 shadow-lg shadow-black/20 backdrop-blur"
                    >
                        <p class="text-3xl font-semibold">36</p>
                        <p class="mt-2 text-sm text-white/70">
                            Cross-functional partners
                        </p>
                    </div>
                </div>
            </section>

            <section id="team" class="mt-16 scroll-mt-24">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[#93c5fd]">
                            Our Team
                        </p>
                        <h2 class="text-3xl font-semibold sm:text-4xl">
                            The people behind MSO 360
                        </h2>
                    </div>
                    <p class="max-w-md text-sm text-white/70">
                        A dedicated group of analysts, coordinators, and service
                        leaders focused on clarity, accountability, and care.
                    </p>
                </div>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        class="rounded-2xl bg-white/10 p-4 shadow-lg shadow-black/20 transition-transform hover:-translate-y-1"
                    >
                        <div
                            class="h-40 rounded-xl bg-gradient-to-br from-white/30 to-white/5"
                        ></div>
                        <div class="mt-4">
                            <p class="text-base font-semibold">Operations Team</p>
                            <p class="text-sm text-white/70">Process support and audit</p>
                        </div>
                    </div>
                    <div
                        class="rounded-2xl bg-white/10 p-4 shadow-lg shadow-black/20 transition-transform hover:-translate-y-1"
                    >
                        <div
                            class="h-40 rounded-xl bg-gradient-to-br from-white/30 to-white/5"
                        ></div>
                        <div class="mt-4">
                            <p class="text-base font-semibold">Client Services</p>
                            <p class="text-sm text-white/70">Frontline assistance</p>
                        </div>
                    </div>
                    <div
                        class="rounded-2xl bg-white/10 p-4 shadow-lg shadow-black/20 transition-transform hover:-translate-y-1"
                    >
                        <div
                            class="h-40 rounded-xl bg-gradient-to-br from-white/30 to-white/5"
                        ></div>
                        <div class="mt-4">
                            <p class="text-base font-semibold">Service Design</p>
                            <p class="text-sm text-white/70">Continuous improvements</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="services" class="mt-16 scroll-mt-24">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[#93c5fd]">
                            Services
                        </p>
                        <h2 class="text-3xl font-semibold sm:text-4xl">
                            What we can do for you
                        </h2>
                    </div>
                    <p class="max-w-md text-sm text-white/70">
                        Clear, well-defined services designed to keep your work moving
                        with confidence and speed.
                    </p>
                </div>
                <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        class="rounded-2xl bg-white/95 p-6 text-[#0b1b3a] shadow-xl shadow-black/10 transition-transform hover:-translate-y-1"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-[#2563eb]/10 text-[#2563eb]"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="h-5 w-5"
                                >
                                    <path d="M3 12h18" />
                                    <path d="M3 6h18" />
                                    <path d="M3 18h18" />
                                </svg>
                            </div>
                            <p class="text-lg font-semibold">Process Requests</p>
                        </div>
                        <p class="mt-4 text-sm text-[#334155]">
                            Submit and track operational requests with full visibility
                            and streamlined approvals.
                        </p>
                    </div>
                    <div
                        class="rounded-2xl bg-white/95 p-6 text-[#0b1b3a] shadow-xl shadow-black/10 transition-transform hover:-translate-y-1"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-[#2563eb]/10 text-[#2563eb]"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="h-5 w-5"
                                >
                                    <path d="M12 6v12" />
                                    <path d="M6 12h12" />
                                    <path d="M4 4h16v16H4z" />
                                </svg>
                            </div>
                            <p class="text-lg font-semibold">Data Reporting</p>
                        </div>
                        <p class="mt-4 text-sm text-[#334155]">
                            Access real-time dashboards and curated reports to inform
                            better decisions.
                        </p>
                    </div>
                    <div
                        class="rounded-2xl bg-white/95 p-6 text-[#0b1b3a] shadow-xl shadow-black/10 transition-transform hover:-translate-y-1"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-[#2563eb]/10 text-[#2563eb]"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="h-5 w-5"
                                >
                                    <path d="M12 3v18" />
                                    <path d="M3 12h18" />
                                    <path d="M7 7h10v10H7z" />
                                </svg>
                            </div>
                            <p class="text-lg font-semibold">Resource Planning</p>
                        </div>
                        <p class="mt-4 text-sm text-[#334155]">
                            Align staffing, budgets, and timelines across departments
                            with clear forecasts.
                        </p>
                    </div>
                    <div
                        class="rounded-2xl bg-white/95 p-6 text-[#0b1b3a] shadow-xl shadow-black/10 transition-transform hover:-translate-y-1"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-[#2563eb]/10 text-[#2563eb]"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="h-5 w-5"
                                >
                                    <path d="M12 2l4 8h6l-5 4 2 8-7-5-7 5 2-8-5-4h6z" />
                                </svg>
                            </div>
                            <p class="text-lg font-semibold">Policy Guidance</p>
                        </div>
                        <p class="mt-4 text-sm text-[#334155]">
                            Get curated policies, templates, and compliance checklists
                            tailored to your needs.
                        </p>
                    </div>
                    <div
                        class="rounded-2xl bg-white/95 p-6 text-[#0b1b3a] shadow-xl shadow-black/10 transition-transform hover:-translate-y-1"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-[#2563eb]/10 text-[#2563eb]"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="h-5 w-5"
                                >
                                    <path d="M4 7h16" />
                                    <path d="M4 12h16" />
                                    <path d="M4 17h10" />
                                </svg>
                            </div>
                            <p class="text-lg font-semibold">Document Control</p>
                        </div>
                        <p class="mt-4 text-sm text-[#334155]">
                            Manage controlled documents, revisions, and approvals with
                            full traceability.
                        </p>
                    </div>
                    <div
                        class="rounded-2xl bg-white/95 p-6 text-[#0b1b3a] shadow-xl shadow-black/10 transition-transform hover:-translate-y-1"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-[#2563eb]/10 text-[#2563eb]"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="h-5 w-5"
                                >
                                    <path d="M4 6h16" />
                                    <path d="M4 12h16" />
                                    <path d="M4 18h16" />
                                </svg>
                            </div>
                            <p class="text-lg font-semibold">Service Desk</p>
                        </div>
                        <p class="mt-4 text-sm text-[#334155]">
                            Centralized support for requests, questions, and issue
                            resolution with SLA tracking.
                        </p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
