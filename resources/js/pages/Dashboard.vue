<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarDays,
    CreditCard,
    DollarSign,
    DoorOpen,
    TrendingUp,
    Users,
    Clock,
    CheckCircle2,
    Plus
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

// Mock data para darle vida al dashboard
const stats = [
    {
        title: 'Reservas Hoy',
        value: '12',
        trend: '+2.5%',
        icon: CalendarDays,
        bgGradient: 'from-blue-500 to-indigo-600',
        textColor: 'text-blue-100',
    },
    {
        title: 'Ingresos Mensuales',
        value: '$14,500',
        trend: '+15.2%',
        icon: DollarSign,
        bgGradient: 'from-emerald-400 to-teal-500',
        textColor: 'text-emerald-100',
    },
    {
        title: 'Huéspedes Activos',
        value: '48',
        trend: '+5.0%',
        icon: Users,
        bgGradient: 'from-orange-400 to-pink-500',
        textColor: 'text-orange-100',
    },
    {
        title: 'Habitaciones Libres',
        value: '15',
        trend: '-2.4%',
        icon: DoorOpen,
        bgGradient: 'from-purple-500 to-indigo-500',
        textColor: 'text-purple-100',
    },
];

const recentActivity = [
    { id: 1, text: 'Check-in: Familia González (Hab. 204)', time: 'Hace 15 min', icon: CheckCircle2, color: 'text-emerald-500' },
    { id: 2, text: 'Nueva reserva: Juan Carlos (Hab. 101)', time: 'Hace 1 hora', icon: Plus, color: 'text-blue-500' },
    { id: 3, text: 'Consumo minibar: Hab 305 ($45.00)', time: 'Hace 2 horas', icon: CreditCard, color: 'text-orange-500' },
    { id: 4, text: 'Check-out: Empresa X (Hab. 412)', time: 'Hace 4 horas', icon: Clock, color: 'text-gray-500' },
];

</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 overflow-x-auto p-6 bg-slate-50 dark:bg-zinc-950/50">

            <!-- Bienvenida -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                        ¡Bienvenido a Alma Hotel! ✨
                    </h1>
                    <p class="text-slate-500 dark:text-zinc-400 mt-1">
                        Aquí tienes un resumen de la actividad de hoy.
                    </p>
                </div>
                <Link href="/admin/reservations/create">
                    <button class="flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-md transition-all hover:bg-primary/90 hover:scale-105 active:scale-95">
                        <Plus class="h-4 w-4" />
                        Nueva Reserva
                    </button>
                </Link>
            </div>

            <!-- Tarjetas de Estadísticas -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="stat in stats"
                    :key="stat.title"
                    class="relative overflow-hidden rounded-2xl p-6 shadow-lg transition-transform hover:-translate-y-1"
                    :class="`bg-linear-to-br ${stat.bgGradient} text-white`"
                >
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-medium" :class="stat.textColor">{{ stat.title }}</h3>
                            <div class="rounded-full bg-white/20 p-2 backdrop-blur-sm">
                                <component :is="stat.icon" class="h-5 w-5 text-white" />
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-bold tracking-tight">{{ stat.value }}</span>
                            <span class="text-sm font-medium flex items-center bg-white/10 px-2 py-0.5 rounded-full" :class="stat.textColor">
                                <TrendingUp v-if="stat.trend.startsWith('+')" class="mr-1 h-3 w-3" />
                                {{ stat.trend }}
                            </span>
                        </div>
                    </div>
                    <!-- Elementos decorativos de fondo -->
                    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10 blur-xl"></div>
                    <div class="absolute -bottom-8 -left-8 h-32 w-32 rounded-full bg-black/10 blur-2xl"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Gráfico de Ocupación (Placeholder Visual) -->
                <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Ocupación Semanal</h3>
                        <span class="text-sm text-slate-500 dark:text-zinc-400">Marzo 2026</span>
                    </div>
                    <div class="flex h-64 items-end gap-2 sm:gap-4 mt-4">
                        <div v-for="(height, i) in [40, 70, 45, 90, 65, 30, 80]" :key="i" class="w-full relative group">
                            <div class="absolute -top-10 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-800 text-white text-xs py-1 px-2 rounded pointer-events-none whitespace-nowrap z-10">
                                {{ height }}%
                            </div>
                            <div
                                class="w-full rounded-t-lg bg-linear-to-t from-blue-500 to-cyan-400 transition-all duration-500 group-hover:opacity-80"
                                :style="`height: ${height}%`"
                            ></div>
                            <div class="mt-3 text-center text-xs font-medium text-slate-500 dark:text-zinc-400">
                                {{ ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'][i] }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actividad Reciente -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-6">Actividad Reciente</h3>
                    <div class="space-y-6">
                        <div v-for="item in recentActivity" :key="item.id" class="flex gap-4">
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 dark:bg-zinc-800">
                                <component :is="item.icon" class="h-4 w-4" :class="item.color" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-700 dark:text-zinc-300">{{ item.text }}</p>
                                <p class="text-xs text-slate-500 dark:text-zinc-500 mt-1">{{ item.time }}</p>
                            </div>
                        </div>
                    </div>
                    <button class="mt-8 w-full rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                        Ver todo el historial
                    </button>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
