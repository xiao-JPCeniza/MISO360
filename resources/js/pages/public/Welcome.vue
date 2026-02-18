<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';

import { dashboard, login } from '@/routes';

function goToSubmitRequest(serviceName: string): void {
    router.visit(
        `/submit-request?service=${encodeURIComponent(serviceName)}`,
        { preserveState: false },
    );
}

type ProfileSlide = {
    id: number;
    imageUrl: string | null;
    title: string;
    subtitle: string | null;
    textPosition: string;
};

const props = withDefaults(
    defineProps<{
        canRegister: boolean;
        profileSlides?: ProfileSlide[];
    }>(),
    {
        canRegister: true,
        profileSlides: () => [],
    },
);

const currentSlideIndex = ref(0);
const slideInterval = ref<ReturnType<typeof setInterval> | null>(null);
const AUTO_PLAY_MS = 6000;

const slides = computed(() => props.profileSlides ?? []);

function goToSlide(index: number, reset: boolean = false) {
    if (slides.value.length === 0) return;
    currentSlideIndex.value = ((index % slides.value.length) + slides.value.length) % slides.value.length;
    if (reset) resetAutoPlay();
}

function nextSlide() {
    goToSlide(currentSlideIndex.value + 1, true);
}

function prevSlide() {
    goToSlide(currentSlideIndex.value - 1, true);
}

function startAutoPlay() {
    if (slideInterval.value) clearInterval(slideInterval.value);
    if (slides.value.length <= 1) return;
    slideInterval.value = setInterval(() => {
        currentSlideIndex.value = (currentSlideIndex.value + 1) % slides.value.length;
    }, AUTO_PLAY_MS);
}

function resetAutoPlay() {
    startAutoPlay();
}

onMounted(() => {
    if (slides.value.length > 0) startAutoPlay();
});

onUnmounted(() => {
    if (slideInterval.value) clearInterval(slideInterval.value);
});

watch(
    () => slides.value.length,
    (len) => {
        if (len > 0 && !slideInterval.value) startAutoPlay();
    },
);

const servicesSearchQuery = ref('');

const TEAM_PROFILE_BASE = '/storage/MISO%20-%20ID';

type TeamMember = {
    id: string;
    fullName: string;
    shortName: string;
    fullDesignation: string;
    shortDesignation: string;
    imageSlug: string;
    /** When set, used as filename under TEAM_PROFILE_BASE instead of `${imageSlug}.jpg` */
    imageFilename?: string;
    roleLabel?: string;
};

const teamMembers: TeamMember[] = [
    {
        id: 'jimenez',
        fullName: 'Junel Jig G. Jimenez',
        shortName: 'Junel Jig G. Jimenez',
        fullDesignation: 'MGADH-I / MISO OIC',
        shortDesignation: 'MISO OIC',
        imageSlug: 'JIMENEZ-MISO',
        roleLabel: 'MISO OIC',
    },
    {
        id: 'meniano',
        fullName: 'Ronald Jay M. Meniano',
        shortName: 'Ronald Jay M. Meniano',
        fullDesignation: 'Senior Administrative Assistant II (Computer Operator IV)',
        shortDesignation: 'Senior Admin Assistant II',
        imageSlug: 'MENIANO-MISO',
    },
    {
        id: 'laid',
        fullName: 'Limwell E. Laid',
        shortName: 'Limwell E. Laid',
        fullDesignation: 'Information Systems Researcher I',
        shortDesignation: 'Information Systems Researcher I',
        imageSlug: 'LAID-MISO',
    },
    {
        id: 'baluma',
        fullName: 'Emmanuel R. Baluma',
        shortName: 'Emmanuel R. Baluma',
        fullDesignation: 'Administrative Aide VI (Clerk III)',
        shortDesignation: 'Administrative Aide VI',
        imageSlug: 'BALUMA-MISO',
    },
    {
        id: 'chavez',
        fullName: 'Randy B. Chavez',
        shortName: 'Randy B. Chavez',
        fullDesignation: 'Administrative Aide VI (Utility Foreman)',
        shortDesignation: 'Administrative Aide VI',
        imageSlug: 'CHAVEZ-MISO',
    },
    {
        id: 'dasilao',
        fullName: 'Ivan Kristoffer J. Dasilao',
        shortName: 'Ivan Kristoffer J. Dasilao',
        fullDesignation: 'Computer Programmer I',
        shortDesignation: 'Computer Programmer I',
        imageSlug: 'DASILAO-MISO',
    },
    {
        id: 'balendez',
        fullName: 'Rex Amiel L. Balendez',
        shortName: 'Rex Amiel L. Balendez',
        fullDesignation: 'Information Systems Analyst I',
        shortDesignation: 'Information Systems Analyst I',
        imageSlug: 'BALENDEZ-MISO',
    },
    {
        id: 'rambonanza',
        fullName: 'Mary Antonette S. Rambonanza',
        shortName: 'Mary Antonette S. Rambonanza',
        fullDesignation: 'Information Systems Researcher I',
        shortDesignation: 'Information Systems Researcher I',
        imageSlug: 'RAMBONANZA-MISO',
    },
    {
        id: 'ceniza',
        fullName: 'John Paul P. Ceniza',
        shortName: 'John Paul P. Ceniza',
        fullDesignation: 'Administrative Aide IV (Clerk II)',
        shortDesignation: 'Administrative Aide IV',
        imageSlug: 'CENIZA-MISO',
    },
    {
        id: 'jala',
        fullName: 'Kenn Cedric Jala',
        shortName: 'Kenn Cedric Jala',
        fullDesignation: 'Administrative Aide VI (Clerk III)',
        shortDesignation: 'Administrative Aide VI',
        imageSlug: 'JALA-MISO',
        imageFilename: 'JALA-MISO.jpg.jpg',
    },
];

const teamLead = computed(() => teamMembers.find((m) => m.id === 'jimenez')!);

type TeamGroup = {
    id: string;
    name: string;
    memberIds: string[];
};

const teamGroups: TeamGroup[] = [
    {
        id: 'equipment',
        name: 'Equipment & Network Administration',
        memberIds: ['meniano', 'chavez', 'baluma', 'laid'],
    },
    {
        id: 'system',
        name: 'System & Database Administration',
        memberIds: ['dasilao', 'balendez', 'ceniza', 'jala'],
    },
    {
        id: 'governance',
        name: 'Information Technology Governance',
        memberIds: ['rambonanza'],
    },
];

const membersById = computed(() => {
    const map: Record<string, TeamMember> = {};
    teamMembers.forEach((m) => { map[m.id] = m; });
    return map;
});

function getMember(id: string): TeamMember | undefined {
    return membersById.value[id];
}

function memberImageSrc(member: TeamMember): string {
    const filename = member.imageFilename ?? `${member.imageSlug}.jpg`;
    return `${TEAM_PROFILE_BASE}/${filename}`;
}

const flippedCardId = ref<string | null>(null);
const imageLoadFailed = ref<Record<string, boolean>>({});

function toggleFlip(id: string) {
    flippedCardId.value = flippedCardId.value === id ? null : id;
}

function isFlipped(id: string) {
    return flippedCardId.value === id;
}

function setImageFailed(id: string) {
    imageLoadFailed.value = { ...imageLoadFailed.value, [id]: true };
}

function imageFailed(id: string) {
    return imageLoadFailed.value[id] === true;
}

function memberInitial(member: TeamMember) {
    return member.shortName.trim().charAt(0).toUpperCase() || '?';
}

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
        name: 'System & Database Administration',
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
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div
        class="min-h-screen scroll-smooth bg-linear-to-b from-slate-50 via-white to-slate-50 text-foreground dark:from-[#0b1b3a] dark:via-[#0f2a5f] dark:to-[#0b1b3a] dark:text-white"
        style="font-family: 'Sora', ui-sans-serif, system-ui;"
    >
        <header
            class="sticky top-0 z-50 w-full border-b border-slate-200 bg-white/95 text-foreground shadow-sm shadow-black/5 backdrop-blur dark:border-white/10 dark:bg-[#0b1b3a]/95 dark:text-white dark:shadow-black/10"
        >
            <div class="mx-auto w-full max-w-6xl px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img
                            src="/storage/logos/IT_Logo.gif"
                            alt="System logo"
                            class="h-10 w-10 rounded-full bg-white p-1 dark:bg-white"
                        />
                        <div>
                            <p class="text-sm uppercase tracking-[0.2em] text-primary">
                                MISO 360
                            </p>
                            <p class="text-lg font-semibold leading-tight">
                                Management Information Systems Office
                            </p>
                        </div>
                    </div>
                    <nav class="hidden items-center gap-6 text-sm font-semibold md:flex">
                        <a
                            href="#profile"
                            class="transition-colors hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring rounded"
                        >
                            MISO Profile
                        </a>
                        <a
                            href="#team"
                            class="transition-colors hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring rounded"
                        >
                            Our Team
                        </a>
                        <a
                            href="#services"
                            class="transition-colors hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring rounded"
                        >
                            Services
                        </a>
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="rounded-full border border-primary bg-primary px-4 py-2 text-primary-foreground transition-colors hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            Dashboard
                        </Link>
                        <Link
                            v-else
                            :href="login()"
                            class="rounded-full border border-primary px-4 py-2 text-primary transition-colors hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                        Sign in to start
                        </Link>
                    </nav>
                </div>
                <nav
                    class="mt-3 flex flex-wrap items-center gap-3 text-sm font-semibold md:hidden"
                >
                    <a
                        href="#profile"
                        class="rounded-full border border-primary/30 px-3 py-1.5 text-foreground transition-colors hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        MISO Profile
                    </a>
                    <a
                        href="#team"
                        class="rounded-full border border-primary/30 px-3 py-1.5 text-foreground transition-colors hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        Our Team
                    </a>
                    <a
                        href="#services"
                        class="rounded-full border border-primary/30 px-3 py-1.5 text-foreground transition-colors hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        Services
                    </a>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-full border border-primary bg-primary px-3 py-1.5 text-primary-foreground transition-colors hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-else
                        :href="login()"
                        class="rounded-full border border-primary px-3 py-1.5 text-primary transition-colors hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                    Sign in to start
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-6 pb-20 pt-14 lg:pt-20">
            <section id="profile" class="flex flex-col gap-10">
                <!-- Profile Slides carousel: immersive, full-width feel with animated text -->
                <div
                    v-if="slides.length > 0"
                    class="profile-slider relative w-full overflow-hidden rounded-2xl border border-border bg-muted shadow-xl shadow-black/10 dark:border-white/10 dark:shadow-black/20"
                    style="aspect-ratio: 21/9; min-height: 220px;"
                >
                    <div
                        v-for="(slide, index) in slides"
                        :key="slide.id"
                        class="absolute inset-0 transition-opacity duration-500 ease-out"
                        :class="index === currentSlideIndex ? 'z-10 opacity-100' : 'z-0 opacity-0 pointer-events-none'"
                    >
                        <img
                            v-if="slide.imageUrl"
                            :src="slide.imageUrl"
                            :alt="slide.title"
                            class="absolute inset-0 h-full w-full object-contain object-center"
                            loading="lazy"
                            decoding="async"
                        />
                        <div
                            v-else
                            class="absolute inset-0 bg-muted"
                        />
                        <div
                            class="absolute inset-0"
                            :style="slide.textPosition === 'right'
                                ? { background: 'linear-gradient(to left, rgba(0,0,0,0.82), rgba(0,0,0,0.5), transparent 55%)' }
                                : { background: 'linear-gradient(to right, rgba(0,0,0,0.82), rgba(0,0,0,0.5), transparent 55%)' }"
                        />
                        <div
                            :key="`${slide.id}-${currentSlideIndex}`"
                            class="profile-slide-text absolute inset-y-0 flex flex-col justify-center px-8 py-8 sm:px-14 md:px-20"
                            :class="[
                                slide.textPosition === 'right' ? 'right-0 items-end text-right' : 'left-0 items-start text-left',
                                slide.textPosition === 'right' ? 'profile-slide-in-right' : 'profile-slide-in-left',
                            ]"
                        >
                            <p
                                v-if="slide.subtitle"
                                class="text-sm font-medium tracking-[0.15em] text-white/95 sm:text-base"
                            >
                                {{ slide.subtitle }}
                            </p>
                            <h2 class="mt-2 text-2xl font-bold uppercase leading-tight tracking-tight text-white drop-shadow-sm sm:text-4xl md:text-5xl lg:text-6xl">
                                {{ slide.title }}
                            </h2>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="absolute left-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/15 p-2.5 text-white backdrop-blur-md transition-all duration-200 hover:bg-white/30 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-white/50 sm:left-4"
                        aria-label="Previous slide"
                        @click="prevSlide"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="absolute right-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/15 p-2.5 text-white backdrop-blur-md transition-all duration-200 hover:bg-white/30 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-white/50 sm:right-4"
                        aria-label="Next slide"
                        @click="nextSlide"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    <div class="absolute bottom-5 left-1/2 z-20 flex -translate-x-1/2 items-center gap-3">
                        <button
                            v-for="(_, dotIndex) in slides"
                            :key="dotIndex"
                            type="button"
                            class="h-2 w-2 rounded-full transition-all duration-300"
                            :class="dotIndex === currentSlideIndex ? 'w-6 bg-white' : 'bg-white/50 hover:bg-white/80 hover:scale-125'"
                            :aria-label="`Go to slide ${dotIndex + 1}`"
                            @click="goToSlide(dotIndex, true)"
                        />
                    </div>
                </div>
            </section>

            <section id="team" class="mt-14 scroll-mt-24">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary dark:text-[#93c5fd]">
                            Meet the Team
                        </p>
                        <h2 class="text-2xl font-semibold text-foreground sm:text-3xl dark:text-white">
                            The people behind MISO 360
                        </h2>
                    </div>
                    <p class="max-w-md text-sm text-muted-foreground dark:text-white/70">
                        A dedicated group focused on clarity, accountability, and care.
                    </p>
                </div>

                <!-- Compact org chart: lead → labels → leads → members -->
                <div class="mt-6 flex flex-col items-center gap-5">
                    <!-- Lead card -->
                    <div class="flex flex-col items-center gap-3">
                        <div
                            class="team-flip-card team-flip-card-lead"
                            :class="{ 'team-flip-card-flipped': isFlipped(teamLead.id) }"
                            role="button"
                            tabindex="0"
                            :aria-label="`${teamLead.shortName}. Click to flip for details.`"
                            @click="toggleFlip(teamLead.id)"
                            @keydown.enter="toggleFlip(teamLead.id)"
                            @keydown.space.prevent="toggleFlip(teamLead.id)"
                        >
                            <div class="team-flip-inner">
                                <div class="team-flip-front team-flip-face">
                                    <div class="team-avatar team-avatar-lead">
                                        <span v-show="imageFailed(teamLead.id)" class="team-avatar-fallback" aria-hidden="true">{{ memberInitial(teamLead) }}</span>
                                        <img
                                            v-show="!imageFailed(teamLead.id)"
                                            :src="memberImageSrc(teamLead)"
                                            :alt="teamLead.shortName"
                                            loading="lazy"
                                            @error="setImageFailed(teamLead.id)"
                                        >
                                    </div>
                                    <p class="team-name team-name-lead">{{ teamLead.shortName }}</p>
                                </div>
                                <div class="team-flip-back team-flip-face team-flip-face-back">
                                    <p class="team-back-name">{{ teamLead.fullName }}</p>
                                    <p class="team-back-designation">{{ teamLead.fullDesignation }}</p>
                                    <p v-if="teamLead.roleLabel" class="team-back-role">{{ teamLead.roleLabel }}</p>
                                    <button type="button" class="team-back-close" @click.stop="toggleFlip(teamLead.id)">Tap to flip back</button>
                                </div>
                            </div>
                        </div>
                        <div class="h-4 w-px shrink-0 bg-border dark:bg-white/20" aria-hidden="true" />
                    </div>

                    <!-- Department labels: same-size cards, compact & bold -->
                    <div class="grid w-full max-w-4xl grid-cols-1 gap-3 sm:grid-cols-3">
                        <div v-for="group in teamGroups" :key="`label-${group.id}`" class="flex flex-col items-center">
                            <div class="team-dept-label">
                                <span class="team-dept-label-text">{{ group.name }}</span>
                            </div>
                            <div class="mt-2 h-3 w-px shrink-0 bg-border dark:bg-white/20" aria-hidden="true" />
                        </div>
                    </div>

                    <!-- Department leads -->
                    <div class="grid w-full max-w-4xl grid-cols-1 gap-4 sm:grid-cols-3">
                        <div v-for="group in teamGroups" :key="`lead-${group.id}`" class="flex flex-col items-center gap-2">
                            <template v-if="getMember(group.memberIds[0])">
                                <div
                                    class="team-flip-card team-flip-card-member"
                                    :class="{ 'team-flip-card-flipped': isFlipped(getMember(group.memberIds[0])!.id) }"
                                    role="button"
                                    tabindex="0"
                                    :aria-label="`${getMember(group.memberIds[0])!.shortName}. Click to flip for details.`"
                                    @click="toggleFlip(getMember(group.memberIds[0])!.id)"
                                    @keydown.enter="toggleFlip(getMember(group.memberIds[0])!.id)"
                                    @keydown.space.prevent="toggleFlip(getMember(group.memberIds[0])!.id)"
                                >
                                    <div class="team-flip-inner">
                                        <div class="team-flip-front team-flip-face">
                                            <div class="team-avatar">
                                                <span v-show="imageFailed(getMember(group.memberIds[0])!.id)" class="team-avatar-fallback" aria-hidden="true">{{ memberInitial(getMember(group.memberIds[0])!) }}</span>
                                                <img
                                                    v-show="!imageFailed(getMember(group.memberIds[0])!.id)"
                                                    :src="memberImageSrc(getMember(group.memberIds[0])!)"
                                                    :alt="getMember(group.memberIds[0])!.shortName"
                                                    loading="lazy"
                                                    @error="setImageFailed(getMember(group.memberIds[0])!.id)"
                                                >
                                            </div>
                                            <p class="team-name">{{ getMember(group.memberIds[0])!.shortName }}</p>
                                        </div>
                                        <div class="team-flip-back team-flip-face team-flip-face-back">
                                            <p class="team-back-name team-back-name-sm">{{ getMember(group.memberIds[0])!.fullName }}</p>
                                            <p class="team-back-designation team-back-designation-sm">{{ getMember(group.memberIds[0])!.fullDesignation }}</p>
                                            <button type="button" class="team-back-close team-back-close-sm" @click.stop="toggleFlip(getMember(group.memberIds[0])!.id)">Tap to flip back</button>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="group.memberIds.length > 1" class="h-3 w-px shrink-0 bg-border dark:bg-white/20" aria-hidden="true" />
                            </template>
                        </div>
                    </div>

                    <!-- Team members grid -->
                    <div class="grid w-full max-w-4xl grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4">
                        <div v-for="group in teamGroups" :key="`members-${group.id}`" class="flex flex-wrap justify-center gap-3">
                            <template v-for="memberId in group.memberIds.slice(1)" :key="memberId">
                                <div v-if="getMember(memberId)" class="flex justify-center">
                                    <div
                                        class="team-flip-card team-flip-card-member"
                                        :class="{ 'team-flip-card-flipped': isFlipped(memberId) }"
                                        role="button"
                                        tabindex="0"
                                        :aria-label="`${getMember(memberId)!.shortName}. Click to flip for details.`"
                                        @click="toggleFlip(memberId)"
                                        @keydown.enter="toggleFlip(memberId)"
                                        @keydown.space.prevent="toggleFlip(memberId)"
                                    >
                                        <div class="team-flip-inner">
                                            <div class="team-flip-front team-flip-face">
                                                <div class="team-avatar">
                                                    <span v-show="imageFailed(memberId)" class="team-avatar-fallback" aria-hidden="true">{{ getMember(memberId) ? memberInitial(getMember(memberId)!) : '' }}</span>
                                                    <img
                                                        v-show="!imageFailed(memberId)"
                                                        :src="memberImageSrc(getMember(memberId)!)"
                                                        :alt="getMember(memberId)!.shortName"
                                                        loading="lazy"
                                                        @error="setImageFailed(memberId)"
                                                    >
                                                </div>
                                                <p class="team-name team-name-sm">{{ getMember(memberId)!.shortName }}</p>
                                            </div>
                                            <div class="team-flip-back team-flip-face team-flip-face-back">
                                                <p class="team-back-name team-back-name-xs">{{ getMember(memberId)!.fullName }}</p>
                                                <p class="team-back-designation team-back-designation-xs">{{ getMember(memberId)!.fullDesignation }}</p>
                                                <button type="button" class="team-back-close team-back-close-xs" @click.stop="toggleFlip(memberId)">Tap to flip back</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            <section
                id="services"
                class="relative mt-16 overflow-hidden rounded-3xl scroll-mt-24 border border-neutral-200 bg-neutral-50 shadow-2xl dark:border-white/10 dark:bg-[#0a0f1a]"
            >
                <!-- Tech background: grid + gradient -->
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.12] dark:opacity-[0.15]"
                    aria-hidden="true"
                >
                    <div
                        class="absolute inset-0 bg-[linear-gradient(to_right,#94a3b8_1px,transparent_1px),linear-gradient(to_bottom,#94a3b8_1px,transparent_1px)] bg-size-[2rem_2rem] dark:bg-[linear-gradient(to_right,#334155_1px,transparent_1px),linear-gradient(to_bottom,#334155_1px,transparent_1px)]"
                    />
                    <div
                        class="absolute -inset-full bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(59,130,246,0.12),transparent)] dark:bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(59,130,246,0.25),transparent)]"
                    />
                </div>

                <div class="relative px-5 py-8 sm:px-8 sm:py-10 lg:px-10">
                    <!-- Hero header -->
                    <div class="text-center">
                        <h2 class="text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl lg:text-4xl dark:text-white">
                            MISO360 Services Hub
                        </h2>
                        <p class="mt-2 max-w-xl mx-auto text-sm text-neutral-600 sm:text-base dark:text-white/70">
                            Explore ICT services across divisions in one unified platform.
                        </p>
                    </div>

                    <!-- Global search -->
                    <div class="mx-auto mt-6 max-w-xl">
                        <label for="services-search" class="sr-only">
                            Search services by keyword, division, or service name
                        </label>
                        <div
                            class="flex items-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 py-2.5 shadow-inner backdrop-blur transition-[border-color,box-shadow] duration-200 focus-within:border-[#2563eb]/50 focus-within:ring-2 focus-within:ring-[#2563eb]/20 dark:border-white/15 dark:bg-white/5 focus-within:dark:border-[#3b82f6]/50 focus-within:dark:ring-[#3b82f6]/20"
                        >
                            <svg
                                class="h-4 w-4 shrink-0 text-neutral-400 dark:text-white/40"
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
                                class="min-w-0 flex-1 bg-transparent text-sm text-neutral-900 placeholder:text-neutral-400 focus:outline-none dark:text-white dark:placeholder:text-white/40"
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
                                class="flex flex-col rounded-2xl border border-neutral-200 bg-white/90 p-5 shadow-lg backdrop-blur-xl transition-all duration-300 hover:border-[#2563eb]/30 hover:shadow-[0_0_30px_-5px_rgba(37,99,235,0.15)] sm:p-6 dark:border-white/10 dark:bg-white/5 dark:hover:border-[#3b82f6]/30 dark:hover:shadow-[0_0_30px_-5px_rgba(59,130,246,0.2)]"
                                role="listitem"
                            >
                                <!-- Division name + icon -->
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#2563eb]/15 text-[#2563eb] ring-1 ring-[#2563eb]/25 dark:bg-[#3b82f6]/20 dark:text-[#93c5fd] dark:ring-[#3b82f6]/30"
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
                                        <h3 class="text-base font-semibold text-neutral-900 sm:text-lg dark:text-white">
                                            {{ division.name }}
                                        </h3>
                                        <p class="mt-1 text-xs text-neutral-600 sm:text-sm dark:text-white/70">
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
                                                class="text-[11px] font-medium uppercase tracking-wider text-neutral-500 dark:text-white/50"
                                            >
                                                {{ (group as { group: string }).group }}
                                            </span>
                                            <div
                                                class="flex flex-wrap gap-2"
                                            >
                                                <button
                                                    v-for="item in (group as { items: string[] }).items"
                                                    :key="item"
                                                    type="button"
                                                    class="rounded-lg border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-left text-xs text-neutral-800 transition-colors duration-200 hover:border-[#2563eb]/25 hover:bg-neutral-100 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[#2563eb] dark:border-white/15 dark:bg-white/5 dark:text-white/90 dark:hover:border-[#3b82f6]/25 dark:hover:bg-white/10 dark:focus-visible:outline-2 dark:focus-visible:outline-[#3b82f6] cursor-pointer"
                                                    @click="goToSubmitRequest(item)"
                                                >
                                                    {{ item }}
                                                </button>
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
                                            <button
                                                v-for="s in getFilteredServicesForDivision(division)"
                                                :key="(s as { name: string }).name"
                                                type="button"
                                                class="inline-flex flex-wrap items-center gap-1.5 rounded-lg border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-left text-xs text-neutral-800 transition-colors duration-200 hover:border-[#2563eb]/25 hover:bg-neutral-100 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[#2563eb] dark:border-white/15 dark:bg-white/5 dark:text-white/90 dark:hover:border-[#3b82f6]/25 dark:hover:bg-white/10 dark:focus-visible:outline-2 dark:focus-visible:outline-[#3b82f6] cursor-pointer"
                                                @click="goToSubmitRequest((s as { name: string }).name)"
                                            >
                                                {{ (s as { name: string }).name }}
                                                <span
                                                    v-if="(s as { formRequired: boolean }).formRequired"
                                                    class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-800 dark:bg-amber-500/20 dark:text-amber-300/90"
                                                >
                                                    Form Required
                                                </span>
                                            </button>
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
                                            <button
                                                v-for="item in getFilteredServicesForDivision(division)"
                                                :key="item as string"
                                                type="button"
                                                class="rounded-lg border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-left text-xs text-neutral-800 transition-colors duration-200 hover:border-[#2563eb]/25 hover:bg-neutral-100 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-[#2563eb] dark:border-white/15 dark:bg-white/5 dark:text-white/90 dark:hover:border-[#3b82f6]/25 dark:hover:bg-white/10 dark:focus-visible:outline-2 dark:focus-visible:outline-[#3b82f6] cursor-pointer"
                                                @click="goToSubmitRequest(item as string)"
                                            >
                                                {{ item }}
                                            </button>
                                        </div>
                                    </template>

                                    <!-- No results within division when searching -->
                                    <p
                                        v-else
                                        class="text-xs text-neutral-500 dark:text-white/50"
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
                        class="mt-6 text-center text-sm text-neutral-500 dark:text-white/50"
                    >
                        No divisions or services match your search. Try a different keyword.
                    </p>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
@keyframes profile-slide-in-left {
    from {
        opacity: 0;
        transform: translateX(-18%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
@keyframes profile-slide-in-right {
    from {
        opacity: 0;
        transform: translateX(18%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
.profile-slide-in-left {
    animation: profile-slide-in-left 0.6s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}
.profile-slide-in-right {
    animation: profile-slide-in-right 0.6s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

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

/* Team flip cards: compact, consistent, front = name only, back = full details */
.team-flip-card {
    position: relative;
    perspective: 1000px;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.team-flip-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px -8px rgb(0 0 0 / 0.18);
}
.dark .team-flip-card:hover {
    box-shadow: 0 8px 24px -8px rgb(0 0 0 / 0.4);
}
.team-flip-card:focus-visible {
    outline: 2px solid var(--ring);
    outline-offset: 2px;
}
.team-flip-card-lead {
    height: 220px;
    width: 180px;
    border-radius: 1rem;
    overflow: hidden;
}
.team-flip-card-member {
    height: 200px;
    width: 160px;
    border-radius: 0.875rem;
    overflow: hidden;
}
.team-flip-inner {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    transition: transform 0.45s cubic-bezier(0.4, 0, 0.2, 1);
    transform-style: preserve-3d;
}
.team-flip-card-flipped .team-flip-inner {
    transform: rotateY(180deg);
}
.team-flip-face {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    border-radius: inherit;
    border: 1px solid var(--color-border);
    background: var(--color-card);
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}
.dark .team-flip-face {
    border-color: rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.06);
}
.team-flip-face-back {
    transform: rotateY(180deg);
    justify-content: center;
    gap: 0.375rem;
    overflow: auto;
}
.team-avatar {
    position: relative;
    flex-shrink: 0;
    width: 4rem;
    height: 4rem;
    border-radius: 50%;
    overflow: hidden;
    background: var(--color-muted);
    border: 2px solid var(--color-primary);
    opacity: 0.9;
}
.team-avatar-lead {
    width: 5rem;
    height: 5rem;
    border-width: 2px;
}
.team-avatar img,
.team-avatar-fallback {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    object-fit: cover;
}
.team-avatar-fallback {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-muted-foreground);
    background: var(--color-muted);
}
.team-avatar-lead .team-avatar-fallback {
    font-size: 1.5rem;
}
.team-name {
    margin-top: 0.5rem;
    text-align: center;
    font-weight: 600;
    font-size: 0.875rem;
    line-height: 1.25;
    color: var(--color-foreground);
    line-clamp: 2;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.team-name-lead {
    font-size: 1rem;
}
.team-name-sm {
    font-size: 0.75rem;
    margin-top: 0.375rem;
}
/* Department label cards: same size, compact, bold */
.team-dept-label {
    width: 100%;
    min-height: 4.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1rem;
    text-align: center;
    font-size: 0.6875rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    line-height: 1.25;
    color: var(--color-foreground);
    background: color-mix(in srgb, var(--color-primary) 14%, transparent);
    border: 2px solid color-mix(in srgb, var(--color-primary) 50%, transparent);
    border-radius: 0.625rem;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.06);
}
.team-dept-label-text {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
    max-width: 100%;
}
.dark .team-dept-label {
    background: color-mix(in srgb, var(--color-primary) 22%, transparent);
    border-color: color-mix(in srgb, var(--color-primary) 55%, transparent);
    color: white;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.2);
}
.team-back-name {
    text-align: center;
    font-weight: 600;
    font-size: 0.8125rem;
    line-height: 1.3;
    color: var(--color-foreground);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.team-back-name-sm {
    font-size: 0.75rem;
}
.team-back-name-xs {
    font-size: 0.6875rem;
}
.team-back-designation {
    text-align: center;
    font-size: 0.6875rem;
    line-height: 1.35;
    color: var(--color-muted-foreground);
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.team-back-designation-sm {
    font-size: 0.625rem;
    -webkit-line-clamp: 2;
}
.team-back-designation-xs {
    font-size: 0.5625rem;
    -webkit-line-clamp: 2;
}
.team-back-role {
    text-align: center;
    font-size: 0.625rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--color-primary);
}
.team-back-close {
    margin-top: 0.25rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.625rem;
    font-weight: 600;
    color: var(--color-primary);
    background: color-mix(in srgb, var(--color-primary) 12%, transparent);
    border: 1px solid color-mix(in srgb, var(--color-primary) 40%, transparent);
    border-radius: 9999px;
    cursor: pointer;
    transition: background 0.15s ease;
}
.team-back-close:hover {
    background: color-mix(in srgb, var(--color-primary) 22%, transparent);
}
.team-back-close:focus-visible {
    outline: 2px solid var(--ring);
    outline-offset: 2px;
}
.team-back-close-sm {
    font-size: 0.5625rem;
    padding: 0.2rem 0.4rem;
}
.team-back-close-xs {
    font-size: 0.5rem;
    padding: 0.15rem 0.35rem;
}
@media (prefers-reduced-motion: reduce) {
    .team-flip-card {
        transition: none;
    }
    .team-flip-card:hover {
        transform: none;
    }
    .team-flip-inner {
        transition-duration: 0.15s;
    }
}
</style>
