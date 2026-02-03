<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import { dashboard, login } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const servicesSearchQuery = ref('');

type Division = {
    id: string;
    name: string;
    description: string;
    icon: 'network' | 'database' | 'shield';
    services:
        | string[]
        | { name: string; formRequired: boolean }[]
        | { group?: string; items: string[] }[];
};

const divisions: Division[] = [
    {
        id: 'equipment',
        name: 'Equipment and Network Administration',
        description:
            'Manages IT hardware, network infrastructure, and technical maintenance.',
        icon: 'network',
        services: [
            {
                group: 'Hardware',
                items: [
                    'Computer repair',
                    'Laptop repair',
                    'Printer repair',
                    'CCTV issue/repair',
                    'End-User Equipment Installation, Setup, and Configuration (Connection of Computers, Monitors, Printers, Peripherals, and Workstation Relocation)',
                    'Request for new IT equipment (e.g., PC, printer, UPS)',
                    'End-User Devices Component Replacement',
                    'Inspect Unit',
                    'Borrow Unit',
                ],
            },
            {
                group: 'Software',
                items: [
                    'Software license or activation request',
                    'Install/Reformat Operating System',
                    'Installation of application software',
                    'System Reinstallation/Troubleshooting (TOIMS, GAAMS, ECPAC)',
                ],
            },
            {
                group: 'Network',
                items: [
                    'Network Connectivity Installation, Repair, and Maintenance Services (LAN and Fiber Optic Cabling, Network and Wireless Setup, Repairs, Upgrades, and Network Equipment Deployment)',
                ],
            },
            {
                group: 'Recovery',
                items: [
                    'Assess extent of hardware/software failure',
                    'Data Recovery',
                ],
            },
        ],
    },
    {
        id: 'system',
        name: 'System and Database Administration',
        description:
            'Oversees system development, database management, and user support services.',
        icon: 'database',
        services: [
            { name: 'System Development', formRequired: true },
            { name: 'System modification', formRequired: true },
            { name: 'System error / bug report', formRequired: true },
            { name: 'Request for new system module or enhancement', formRequired: false },
            { name: 'System account creation', formRequired: true },
            { name: 'Password reset or account recovery (gov mail)', formRequired: true },
            { name: 'System Documentation', formRequired: false },
            { name: 'System Prototyping', formRequired: false },
        ],
    },
    {
        id: 'governance',
        name: 'IT Governance',
        description:
            'Ensures compliance with ICT policies, data privacy standards, and promotes effective digital governance.',
        icon: 'shield',
        services: [
            'Handling of Data Privacy Concerns and Incident Reports',
            'ICT strategic and operational planning',
            'Coordination of IT Initiatives Across Offices',
            'Monitoring and Assessment of ICT Performance and Utilization',
            'Technology Transfer to End-Users',
        ],
    },
];

function getDivisionSearchText(division: Division): string {
    const parts = [division.name, division.description];
    if (division.id === 'equipment' && Array.isArray(division.services)) {
        (division.services as { group?: string; items: string[] }[]).forEach(
            (g) => {
                if (g.group) parts.push(g.group);
                g.items.forEach((s) => parts.push(s));
            },
        );
    }
    if (division.id === 'system' && Array.isArray(division.services)) {
        (division.services as { name: string }[]).forEach((s) =>
            parts.push(s.name),
        );
    }
    if (division.id === 'governance' && Array.isArray(division.services)) {
        (division.services as string[]).forEach((s) => parts.push(s));
    }
    return parts.join(' ').toLowerCase();
}

const filteredDivisions = computed(() => {
    const q = servicesSearchQuery.value.trim().toLowerCase();
    if (!q) return divisions;
    return divisions.filter((d) =>
        getDivisionSearchText(d).includes(q),
    );
});

function getFilteredServicesForDivision(division: Division) {
    const q = servicesSearchQuery.value.trim().toLowerCase();
    if (!q) return division.services;

    if (division.id === 'equipment') {
        const groups = division.services as { group?: string; items: string[] }[];
        return groups
            .map((g) => ({
                group: g.group,
                items: g.items.filter((s) => s.toLowerCase().includes(q)),
            }))
            .filter((g) => g.items.length > 0);
    }
    if (division.id === 'system') {
        const list = division.services as { name: string; formRequired: boolean }[];
        return list.filter((s) => s.name.toLowerCase().includes(q));
    }
    if (division.id === 'governance') {
        return (division.services as string[]).filter((s) =>
            s.toLowerCase().includes(q),
        );
    }
    return division.services;
}
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
                            src="/storage/logos/MISO360_LOGO.gif"
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

            <section
                id="services"
                class="relative mt-16 overflow-hidden rounded-3xl scroll-mt-24 border border-white/10 bg-[#0a0f1a] shadow-2xl"
            >
                <!-- Tech background: grid + gradient -->
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.15]"
                    aria-hidden="true"
                >
                    <div
                        class="absolute inset-0 bg-[linear-gradient(to_right,#334155_1px,transparent_1px),linear-gradient(to_bottom,#334155_1px,transparent_1px)] bg-size-[2rem_2rem]"
                    />
                    <div
                        class="absolute -inset-full bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(59,130,246,0.25),transparent)]"
                    />
                </div>

                <div class="relative px-5 py-8 sm:px-8 sm:py-10 lg:px-10">
                    <!-- Hero header -->
                    <div class="text-center">
                        <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl lg:text-4xl">
                            MISO360 Services Hub
                        </h2>
                        <p class="mt-2 max-w-xl mx-auto text-sm text-white/70 sm:text-base">
                            Explore ICT services across divisions in one unified platform.
                        </p>
                    </div>

                    <!-- Global search -->
                    <div class="mx-auto mt-6 max-w-xl">
                        <label for="services-search" class="sr-only">
                            Search services by keyword, division, or service name
                        </label>
                        <div
                            class="flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 shadow-inner backdrop-blur transition-[border-color,box-shadow] duration-200 focus-within:border-[#3b82f6]/50 focus-within:ring-2 focus-within:ring-[#3b82f6]/20"
                        >
                            <svg
                                class="h-4 w-4 shrink-0 text-white/40"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21 21l-5.2-5.2M11 16a5 5 0 100-10 5 5 0 000 10z"
                                />
                            </svg>
                            <input
                                id="services-search"
                                v-model="servicesSearchQuery"
                                type="search"
                                autocomplete="off"
                                placeholder="Search by keyword, division, or service..."
                                class="min-w-0 flex-1 bg-transparent text-sm text-white placeholder:text-white/40 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Division cards: vertical stack -->
                    <div
                        class="mt-8 flex flex-col gap-6"
                        role="list"
                    >
                        <TransitionGroup
                            name="services-card"
                            tag="div"
                            class="contents"
                        >
                            <article
                                v-for="division in filteredDivisions"
                                :key="division.id"
                                class="flex flex-col rounded-2xl border border-white/10 bg-white/5 p-5 shadow-lg backdrop-blur-xl transition-all duration-300 hover:border-[#3b82f6]/30 hover:shadow-[0_0_30px_-5px_rgba(59,130,246,0.2)] sm:p-6"
                                role="listitem"
                            >
                                <!-- Division name + icon -->
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#3b82f6]/20 text-[#93c5fd] ring-1 ring-[#3b82f6]/30"
                                        aria-hidden="true"
                                    >
                                        <!-- Network icon -->
                                        <svg
                                            v-if="division.icon === 'network'"
                                            class="h-5 w-5"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            />
                                        </svg>
                                        <!-- Database icon -->
                                        <svg
                                            v-else-if="division.icon === 'database'"
                                            class="h-5 w-5"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"
                                            />
                                        </svg>
                                        <!-- Shield icon -->
                                        <svg
                                            v-else-if="division.icon === 'shield'"
                                            class="h-5 w-5"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-base font-semibold text-white sm:text-lg">
                                            {{ division.name }}
                                        </h3>
                                        <p class="mt-1 text-xs text-white/70 sm:text-sm">
                                            {{ division.description }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Services -->
                                <div class="mt-4 flex flex-1 flex-col gap-4">
                                    <!-- Equipment: grouped chips -->
                                    <template
                                        v-if="
                                            division.id === 'equipment' &&
                                            getFilteredServicesForDivision(
                                                division,
                                            ).length
                                        "
                                    >
                                        <div
                                            v-for="(group, gIdx) in getFilteredServicesForDivision(division)"
                                            :key="'eq-' + gIdx"
                                            class="space-y-2"
                                        >
                                            <span
                                                v-if="(group as { group?: string }).group"
                                                class="text-[11px] font-medium uppercase tracking-wider text-white/50"
                                            >
                                                {{ (group as { group: string }).group }}
                                            </span>
                                            <div
                                                class="flex flex-wrap gap-2"
                                            >
                                                <span
                                                    v-for="item in (group as { items: string[] }).items"
                                                    :key="item"
                                                    class="rounded-lg border border-white/15 bg-white/5 px-2.5 py-1.5 text-xs text-white/90 transition-colors duration-200 hover:border-[#3b82f6]/25 hover:bg-white/10"
                                                >
                                                    {{ item }}
                                                </span>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- System & Database: chips + Form Required badge -->
                                    <template
                                        v-else-if="
                                            division.id === 'system' &&
                                            getFilteredServicesForDivision(
                                                division,
                                            ).length
                                        "
                                    >
                                        <div class="flex flex-wrap gap-2">
                                            <span
                                                v-for="s in getFilteredServicesForDivision(division)"
                                                :key="(s as { name: string }).name"
                                                class="inline-flex flex-wrap items-center gap-1.5 rounded-lg border border-white/15 bg-white/5 px-2.5 py-1.5 text-xs text-white/90 transition-colors duration-200 hover:border-[#3b82f6]/25 hover:bg-white/10"
                                            >
                                                {{ (s as { name: string }).name }}
                                                <span
                                                    v-if="(s as { formRequired: boolean }).formRequired"
                                                    class="rounded bg-amber-500/20 px-1.5 py-0.5 text-[10px] font-medium text-amber-300/90"
                                                >
                                                    Form Required
                                                </span>
                                            </span>
                                        </div>
                                    </template>

                                    <!-- IT Governance: stacked chips -->
                                    <template
                                        v-else-if="
                                            division.id === 'governance' &&
                                            getFilteredServicesForDivision(
                                                division,
                                            ).length
                                        "
                                    >
                                        <div class="flex flex-wrap gap-2">
                                            <span
                                                v-for="item in getFilteredServicesForDivision(division)"
                                                :key="item as string"
                                                class="rounded-lg border border-white/15 bg-white/5 px-2.5 py-1.5 text-xs text-white/90 transition-colors duration-200 hover:border-[#3b82f6]/25 hover:bg-white/10"
                                            >
                                                {{ item }}
                                            </span>
                                        </div>
                                    </template>

                                    <!-- No results within division when searching -->
                                    <p
                                        v-else
                                        class="text-xs text-white/50"
                                    >
                                        No matching services for this division.
                                    </p>
                                </div>
                            </article>
                        </TransitionGroup>
                    </div>

                    <!-- Empty state when search matches nothing -->
                    <p
                        v-if="filteredDivisions.length === 0"
                        class="mt-6 text-center text-sm text-white/50"
                    >
                        No divisions or services match your search. Try a different keyword.
                    </p>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
.services-card-enter-active,
.services-card-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}
.services-card-enter-from,
.services-card-leave-to {
    opacity: 0;
    transform: translateY(8px);
}
.services-card-move {
    transition: transform 0.35s ease;
}
</style>
