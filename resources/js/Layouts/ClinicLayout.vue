<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import FlashToast from '@/Components/FlashToast.vue';
import { trans, loadLanguageAsync } from 'laravel-vue-i18n';

defineProps({
    title: {
        type: String,
        default: 'Clinic Portal',
    },
    fullWidth: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const auth = computed(() => page.props.auth?.user);
const currentTenant = computed(() => page.props.currentTenant);
const userClinics = computed(() => page.props.userClinics || []);
const currentLocale = computed(() => page.props.locale || 'en');

const isClinicMenuOpen = ref(false);
const isUserMenuOpen = ref(false);
const isMobileSidebarOpen = ref(false);

const navigationItems = computed(() => [
    { name: trans('Dashboard'), href: '/portal/dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: trans('Patients (L1)'), href: '/portal/patients', icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' },
    { name: trans('Clients / Owners'), href: '/portal/clients', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { name: trans('Pharmacy & Stock'), href: '/portal/inventory', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
    { name: trans('Clinic Staff'), href: '/portal/staff', icon: 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4' },
]);

const switchClinic = (tenantId) => {
    isClinicMenuOpen.value = false;
    router.post('/portal/switch-clinic', { tenant_id: tenantId });
};

const switchLocale = (lang) => {
    const url = new URL(window.location.href);
    url.searchParams.set('lang', lang);
    router.visit(url.pathname + url.search, { preserveScroll: true, preserveState: true });
};

const logout = () => {
    router.post('/portal/logout');
};
</script>

<template>
    <div class="min-h-screen bg-[#FAF8F5] flex flex-col antialiased">
        <!-- TOP CLINIC NAVIGATION BAR -->
        <header class="bg-white/95 backdrop-blur-md border-b border-stone-200/80 sticky top-0 z-40 transition-shadow">
            <div :class="[fullWidth ? 'max-w-[1800px]' : 'max-w-7xl', 'mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4']">
                <!-- Left: Logo & Mobile Toggle -->
                <div class="flex items-center space-x-3 rtl:space-x-reverse">
                    <button
                        type="button"
                        @click="isMobileSidebarOpen = !isMobileSidebarOpen"
                        class="md:hidden p-2 rounded-lg text-stone-600 hover:bg-stone-100 focus:outline-none"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <Link href="/portal/dashboard" class="flex items-center space-x-2.5 rtl:space-x-reverse group">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-700 flex items-center justify-center text-white shadow-xs group-hover:scale-105 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </div>
                        <div>
                            <span class="font-extrabold text-base tracking-tight text-stone-900 block leading-none">VetManager</span>
                            <span class="text-[10px] font-semibold text-emerald-700 uppercase tracking-widest block mt-0.5">
                                {{ $t('Clinic Portal') }}
                            </span>
                        </div>
                    </Link>

                    <!-- Active Clinic Selector Dropdown -->
                    <div class="relative hidden sm:block ms-4" v-if="currentTenant">
                        <button
                            type="button"
                            @click="isClinicMenuOpen = !isClinicMenuOpen"
                            class="inline-flex items-center space-x-2 rtl:space-x-reverse px-3 py-1.5 rounded-lg border border-stone-200 bg-stone-50 hover:bg-stone-100 text-xs font-semibold text-stone-800 transition"
                        >
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                            <span class="font-bold truncate max-w-[150px]">{{ currentTenant.name }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 uppercase font-bold">{{ currentTenant.plan || 'Standard' }}</span>
                            <svg class="w-3.5 h-3.5 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div
                            v-if="isClinicMenuOpen"
                            class="absolute start-0 mt-1.5 w-64 rounded-xl bg-white shadow-xl border border-stone-200 py-1.5 z-50 animate-fade-in"
                        >
                            <div class="px-3 py-1.5 text-[11px] font-bold text-stone-400 uppercase tracking-wider">
                                {{ $t('Switch Clinic Tenant') }}
                            </div>
                            <button
                                v-for="clinic in userClinics"
                                :key="clinic.id"
                                type="button"
                                @click="switchClinic(clinic.id)"
                                class="w-full text-start px-3 py-2 text-xs flex items-center justify-between hover:bg-emerald-50/80 transition"
                                :class="[clinic.id === currentTenant.id ? 'font-bold text-emerald-800 bg-emerald-50/50' : 'text-stone-700']"
                            >
                                <span class="truncate">{{ clinic.name }}</span>
                                <span v-if="clinic.id === currentTenant.id" class="text-emerald-600 text-xs font-bold">✓</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: Language Toggle & User Profile -->
                <div class="flex items-center space-x-2 sm:space-x-3 rtl:space-x-reverse">
                    <!-- Language Switcher -->
                    <button
                        v-if="currentLocale === 'en'"
                        @click="switchLocale('ar')"
                        type="button"
                        class="px-2.5 py-1 text-xs font-bold rounded-lg border border-stone-200 text-stone-700 hover:bg-stone-100 transition"
                    >
                        العربية
                    </button>
                    <button
                        v-else
                        @click="switchLocale('en')"
                        type="button"
                        class="px-2.5 py-1 text-xs font-bold rounded-lg border border-stone-200 text-stone-700 hover:bg-stone-100 transition"
                    >
                        English
                    </button>

                    <!-- User Menu -->
                    <div class="relative" v-if="auth">
                        <button
                            type="button"
                            @click="isUserMenuOpen = !isUserMenuOpen"
                            class="flex items-center space-x-2 rtl:space-x-reverse p-1.5 rounded-xl hover:bg-stone-100 transition text-start"
                        >
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs">
                                {{ auth.name ? auth.name.charAt(0).toUpperCase() : 'U' }}
                            </div>
                            <div class="hidden md:block leading-tight">
                                <span class="block text-xs font-bold text-stone-900 truncate max-w-[120px]">{{ auth.name }}</span>
                                <span class="block text-[10px] text-stone-500 capitalize">{{ auth.role || 'Staff' }}</span>
                            </div>
                            <svg class="w-3.5 h-3.5 text-stone-400 hidden md:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div
                            v-if="isUserMenuOpen"
                            class="absolute end-0 mt-1.5 w-48 rounded-xl bg-white shadow-xl border border-stone-200 py-1.5 z-50 animate-fade-in"
                        >
                            <div class="px-3 py-2 border-b border-stone-100">
                                <span class="block text-xs font-bold text-stone-900">{{ auth.name }}</span>
                                <span class="block text-[11px] text-stone-500 truncate">{{ auth.email }}</span>
                            </div>

                            <button
                                type="button"
                                @click="logout"
                                class="w-full text-start px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 flex items-center space-x-2 rtl:space-x-reverse transition"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span>{{ $t('Sign Out') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN LAYOUT BODY -->
        <div :class="[fullWidth ? 'max-w-[1800px]' : 'max-w-7xl', 'mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 w-full flex flex-col md:flex-row gap-6']">
            <!-- SIDEBAR NAVIGATION (Desktop) -->
            <aside class="hidden md:block w-56 shrink-0">
                <nav class="sticky top-24 space-y-1 bg-white p-3 rounded-2xl border border-stone-200 shadow-2xs">
                    <Link
                        v-for="item in navigationItems"
                        :key="item.name"
                        :href="item.href"
                        class="flex items-center space-x-2.5 rtl:space-x-reverse px-3 py-2 rounded-xl text-xs font-semibold transition"
                        :class="[
                            $page.url.startsWith(item.href)
                                ? 'bg-emerald-600 text-white shadow-xs font-bold'
                                : 'text-stone-600 hover:bg-stone-100 hover:text-stone-900',
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                        </svg>
                        <span>{{ item.name }}</span>
                    </Link>
                </nav>
            </aside>

            <!-- MOBILE SIDEBAR OVERLAY -->
            <div v-if="isMobileSidebarOpen" class="fixed inset-0 z-50 md:hidden bg-stone-900/40 backdrop-blur-xs flex">
                <div class="w-64 bg-white h-full p-4 space-y-2 shadow-2xl flex flex-col">
                    <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                        <span class="font-extrabold text-stone-900 text-sm">VetManager</span>
                        <button @click="isMobileSidebarOpen = false" class="text-stone-400 p-1">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <nav class="space-y-1 flex-1">
                        <Link
                            v-for="item in navigationItems"
                            :key="item.name"
                            :href="item.href"
                            @click="isMobileSidebarOpen = false"
                            class="flex items-center space-x-2.5 rtl:space-x-reverse px-3 py-2 rounded-xl text-xs font-semibold"
                            :class="[
                                $page.url.startsWith(item.href)
                                    ? 'bg-emerald-600 text-white font-bold'
                                    : 'text-stone-600 hover:bg-stone-100',
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            <span>{{ item.name }}</span>
                        </Link>
                    </nav>
                </div>
                <div class="flex-1" @click="isMobileSidebarOpen = false" />
            </div>

            <!-- PRIMARY CONTENT AREA -->
            <main class="flex-1 min-w-0">
                <slot />
            </main>
        </div>

        <FlashToast />
    </div>
</template>
