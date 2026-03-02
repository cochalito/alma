<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import VueApexCharts from 'vue3-apexcharts';
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
    Plus,
    UserPlus,
    UserCheck,
    DoorClosed,
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface StatSplit {
    LP: number;
    UYUNI: number;
}

interface Props {
    stats: {
        checkInsToday: StatSplit;
        checkOutsToday: StatSplit;
        occupiedDepartmentsToday: StatSplit;
        freeDepartmentsToday: StatSplit;
    };
    recentReservations: Array<{
        id: number;
        location: string;
        departament_code: string;
        customer_name: string;
        check_in: string;
        check_out: string;
        total_cost: number;
        status: string;
        updated_at: string;
    }>;
    chartData: {
        categories: string[];
        series: Array<{
            name: string;
            data: number[];
        }>;
    };
}

const props = defineProps<Props>();

import { computed } from 'vue';

function getStatusConfig(status: string) {
    switch (status) {
        case '1': return { label: 'Confirmada', class: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800' };
        case '2': return { label: 'Check-In', class: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800' };
        case '3': return { label: 'Check-Out', class: 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-900/30 dark:text-orange-400 dark:border-orange-800' };
        case '4': return { label: 'Cancelada', class: 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800' };
        default: return { label: 'Desconocido', class: 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700' };
    }
}

const formatDateTime = (dateStr: string) => {
    if (!dateStr) return '';
    const match = dateStr.match(/^(\d{4})-(\d{2})-(\d{2})\s(\d{2}:\d{2})/);
    if (match) {
        return `${match[3]}/${match[2]}/${match[1]} ${match[4]}`;
    }
    return dateStr;
};

const dashboardStats = computed(() => [
    {
        title: 'Check-In para hoy',
        lp: props.stats.checkInsToday.LP.toString(),
        uyuni: props.stats.checkInsToday.UYUNI.toString(),
        icon: UserPlus,
        bgClass: 'bg-[#7FC31F] text-white',
    },
    {
        title: 'Check-Out para hoy',
        lp: props.stats.checkOutsToday.LP.toString(),
        uyuni: props.stats.checkOutsToday.UYUNI.toString(),
        icon: UserCheck,
        bgClass: 'bg-[#6AA11A] text-white',
    },
    {
        title: 'Deptos. Ocupados',
        lp: props.stats.occupiedDepartmentsToday.LP.toString(),
        uyuni: props.stats.occupiedDepartmentsToday.UYUNI.toString(),
        icon: DoorClosed,
        bgClass: 'bg-[#7FC31F] text-white',
    },
    {
        title: 'Deptos. Libres',
        lp: props.stats.freeDepartmentsToday.LP.toString(),
        uyuni: props.stats.freeDepartmentsToday.UYUNI.toString(),
        icon: DoorOpen,
        bgClass: 'bg-[#6AA11A] text-white',
    },
]);
const chartOptions = computed(() => ({
    chart: {
        type: 'area' as const,
        height: 350,
        toolbar: { show: false },
        fontFamily: 'inherit'
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        curve: 'smooth' as const,
        width: 2
    },
    xaxis: {
        categories: props.chartData.categories,
        labels: {
            style: {
                colors: '#64748b'
            }
        }
    },
    yaxis: {
        title: {
            text: 'Cantidad',
            style: { color: '#64748b' }
        },
        labels: {
            style: {
                colors: '#64748b'
            }
        }
    },
    fill: {
        type: 'gradient' as const,
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.05,
            stops: [0, 90, 100]
        }
    },
    tooltip: {
        y: {
            formatter: function (val: number) {
                return val + " reservas"
            }
        }
    },
    legend: {
        position: 'top' as const,
        horizontalAlign: 'center' as const
    },
    colors: ['#0284c7', '#16a34a', '#ea580c', '#b91c1c'] // Blue, Green, Orange, Red
}));

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
                    v-for="stat in dashboardStats"
                    :key="stat.title"
                    class="relative overflow-hidden rounded-2xl p-6 shadow-lg transition-transform hover:-translate-y-1"
                    :class="stat.bgClass"
                >
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-medium text-sm lg:text-base">{{ stat.title }}</h3>
                            <div class="rounded-full bg-white/20 p-2 backdrop-blur-sm">
                                <component :is="stat.icon" class="h-5 w-5 text-white" />
                            </div>
                        </div>
                        <div class="flex items-center gap-6 mt-1">
                            <!-- La Paz -->
                            <div>
                                <span class="text-[10px] uppercase font-bold opacity-80 mb-0.5 block tracking-wider">La Paz</span>
                                <span class="text-3xl lg:text-4xl font-extrabold tracking-tight leading-none">{{ stat.lp }}</span>
                            </div>
                            <!-- Divisor -->
                            <div class="w-px h-8 bg-white/30 rounded"></div>
                            <!-- Uyuni -->
                            <div>
                                <span class="text-[10px] uppercase font-bold opacity-80 mb-0.5 block tracking-wider">Uyuni</span>
                                <span class="text-3xl lg:text-4xl font-extrabold tracking-tight leading-none">{{ stat.uyuni }}</span>
                            </div>
                        </div>
                    </div>
                    <!-- Elementos decorativos de fondo -->
                    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10 blur-xl"></div>
                    <div class="absolute -bottom-8 -left-8 h-32 w-32 rounded-full bg-black/10 blur-2xl"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Gráfico de Ocupación por Localidad -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Flujo de Reservas (Últimos 10 días)</h3>
                    </div>
                    <div class="mt-4 w-full" style="min-height: 350px;">
                            <VueApexCharts
                                type="area"
                                height="350"
                                :options="chartOptions"
                                :series="props.chartData.series"
                            ></VueApexCharts>
                    </div>
                </div>

                <!-- Tabla de Reservas Recientes -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Reservaciones Modificadas Recientemente</h3>
                        <Link href="/admin/charter" class="text-sm font-medium text-primary hover:underline">Ir a Charter &rarr;</Link>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-border">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-muted text-muted-foreground font-medium text-xs uppercase tracking-wider">
                                <tr>
                                    <th class="py-3 px-4 border-b border-border">Loc - Depto</th>
                                    <th class="py-3 px-4 border-b border-border">Fechas</th>
                                    <th class="py-3 px-4 border-b border-border text-center">Estado</th>
                                    <th class="py-3 px-4 border-b border-border">Costo Total</th>
                                    <th class="py-3 px-4 border-b border-border">Editado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-if="recentReservations.length === 0">
                                    <td colspan="5" class="py-8 text-center text-muted-foreground font-medium">No hay reservaciones recientes</td>
                                </tr>
                                <tr v-for="res in recentReservations" :key="res.id" class="hover:bg-muted/50 transition-colors">
                                    <td class="py-3 px-4 align-top">
                                        <div class="font-bold whitespace-nowrap"><span class="text-xs text-muted-foreground mr-1">{{ res.location }}</span> {{ res.departament_code }}</div>
                                        <div class="text-[10px] text-muted-foreground truncate max-w-[120px]" :title="res.customer_name">{{ res.customer_name }}</div>
                                    </td>
                                    <td class="py-3 px-4 align-top">
                                        <div class="whitespace-nowrap"><span class="text-xs text-green-600 font-semibold uppercase mr-1">IN</span> {{ formatDateTime(res.check_in) }}</div>
                                        <div class="whitespace-nowrap mt-1"><span class="text-xs text-red-500 font-semibold uppercase mr-1">OUT</span> {{ formatDateTime(res.check_out) }}</div>
                                    </td>
                                    <td class="py-3 px-4 align-top text-center">
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap" :class="getStatusConfig(res.status).class">
                                            {{ getStatusConfig(res.status).label }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 align-top font-bold text-primary whitespace-nowrap">
                                        Bs. {{ Number(res.total_cost).toFixed(2) }}
                                    </td>
                                    <td class="py-3 px-4 align-top whitespace-nowrap">
                                        <div class="text-xs font-semibold">Hace momento</div>
                                        <div class="text-[10px] text-muted-foreground mt-0.5">{{ res.updated_at }}</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
