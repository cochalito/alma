<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import VueApexCharts from 'vue3-apexcharts';
import { FileText, Filter } from 'lucide-vue-next';

interface Props {
    currentLocation: string;
    occupancyChart: {
        categories: string[];
        series: Array<{
            name: string;
            data: number[];
        }>;
    };
    financialChart: {
        categories: string[];
        series: Array<{
            name: string;
            data: number[];
        }>;
    };
    comparisonChart: {
        categories: string[];
        series: Array<{
            name: string;
            data: number[];
        }>;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reporte de Reservas',
        href: '/admin/reports/reservations',
    },
];

const selectedLocation = ref(props.currentLocation);

watch(selectedLocation, (newLocation) => {
    router.get('/admin/reports/reservations', { location: newLocation }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
});

const occupancyOptions = computed(() => ({
    chart: {
        type: 'area' as const,
        height: 350,
        toolbar: { show: false },
        fontFamily: 'inherit'
    },
    dataLabels: {
        enabled: true,
        style: { colors: ['#0284c7'] }
    },
    stroke: {
        curve: 'smooth' as const,
        width: 3
    },
    xaxis: {
        categories: props.occupancyChart.categories,
        labels: {
            style: { colors: '#64748b' }
        }
    },
    yaxis: {
        title: {
            text: 'Departamentos',
            style: { color: '#64748b' }
        },
        labels: {
            style: { colors: '#64748b' }
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
                return val + " deptos ocupados"
            }
        }
    },
    colors: ['#0ea5e9'] // Blue
}));

const financialOptions = computed(() => ({
    chart: {
        type: 'line' as const,
        height: 350,
        toolbar: { show: false },
        fontFamily: 'inherit',
        dropShadow: {
            enabled: true,
            top: 3,
            left: 2,
            blur: 4,
            opacity: 0.2,
        }
    },
    dataLabels: {
        enabled: true,
        offsetY: -5,
        formatter: function (val: number) {
            return 'Bs. ' + new Intl.NumberFormat('es-BO').format(val);
        },
        style: {
            fontSize: '10px',
            colors: ['#16a34a']
        },
        background: { enabled: false }
    },
    stroke: {
        curve: 'smooth' as const,
        width: 3
    },
    markers: {
        size: 5,
        colors: ['#fff'],
        strokeColors: '#16a34a',
        strokeWidth: 2,
        hover: { size: 7 }
    },
    xaxis: {
        categories: props.financialChart.categories,
        labels: { style: { colors: '#64748b' } }
    },
    yaxis: {
        title: {
            text: 'Ingresos (Bs.)',
            style: { color: '#64748b' }
        },
        labels: {
            style: { colors: '#64748b' },
            formatter: function (val: number) {
                return new Intl.NumberFormat('es-BO').format(val);
            }
        }
    },
    tooltip: {
        y: {
            formatter: function (val: number) {
                return "Bs. " + new Intl.NumberFormat('es-BO').format(val);
            }
        }
    },
    colors: ['#16a34a'] // Green
}));

const comparisonOptions = computed(() => ({
    chart: {
        type: 'bar' as const,
        height: 350,
        toolbar: { show: false },
        fontFamily: 'inherit'
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '55%',
            borderRadius: 4,
        },
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent']
    },
    xaxis: {
        categories: props.comparisonChart.categories,
        labels: { style: { colors: '#64748b' } }
    },
    yaxis: {
        title: {
            text: 'Reservaciones',
            style: { color: '#64748b' }
        },
        labels: { style: { colors: '#64748b' } }
    },
    fill: {
        opacity: 1
    },
    tooltip: {
        y: {
            formatter: function (val: number) {
                return val + " reservas"
            }
        }
    },
    colors: ['#3b82f6', '#94a3b8'], // Blue for current, muted for past
    legend: {
        position: 'top' as const,
        horizontalAlign: 'center' as const
    },
}));

</script>

<template>
    <Head title="Reporte de Reservas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 overflow-x-auto p-6 bg-slate-50 dark:bg-zinc-950/50">

            <!-- Header & Filter -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-border pb-6">
                <div class="flex items-center gap-3">
                    <div class="bg-primary/10 p-3 rounded-full">
                        <FileText class="w-8 h-8 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                            Reporte de Reservas
                        </h1>
                        <p class="text-slate-500 dark:text-zinc-400 mt-1">
                            Análisis de ocupación y proyecciones financieras
                        </p>
                    </div>
                </div>
                <!-- Filter Dropdown -->
                <div class="flex items-center gap-2 bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 p-2 rounded-xl shadow-sm">
                    <Filter class="w-4 h-4 text-muted-foreground ml-2" />
                    <span class="text-sm font-medium text-muted-foreground ml-1">Sucursal:</span>
                    <select
                        v-model="selectedLocation"
                        class="bg-transparent border-none text-sm font-bold text-slate-800 dark:text-white outline-none focus:ring-0 cursor-pointer pl-1 pr-6 py-1"
                    >
                        <option value="AMBOS">AMBOS</option>
                        <option value="LP">LA PAZ</option>
                        <option value="UYUNI">UYUNI</option>
                    </select>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                <!-- Gráfico de Ocupación -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden flex flex-col">
                    <div class="mb-2">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">Ocupación Diaria (Últimos 10 Días)</h3>
                        <p class="text-sm text-slate-500">Cantidad de departamentos ocupados cada día</p>
                    </div>
                    <div class="mt-4 w-full flex-1 min-h-[350px]">
                            <VueApexCharts
                                type="area"
                                height="350"
                                :options="occupancyOptions"
                                :series="props.occupancyChart.series"
                            ></VueApexCharts>
                    </div>
                </div>

                <!-- Gráfico Financiero -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden flex flex-col">
                    <div class="mb-2">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">Ingresos por Mes (Últimos 8 Meses)</h3>
                        <p class="text-sm text-slate-500">Flujo monetario (Bs.) agrupado por meses concluídos</p>
                    </div>
                    <div class="mt-4 w-full flex-1 min-h-[350px]">
                            <VueApexCharts
                                type="line"
                                height="350"
                                :options="financialOptions"
                                :series="props.financialChart.series"
                            ></VueApexCharts>
                    </div>
                </div>

                <!-- Gráfico Comparativo Anual -->
                <div class="xl:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden flex flex-col">
                    <div class="mb-2">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">Comparativa de Reservas (Últimos 12 Meses vs Año Pasado)</h3>
                        <p class="text-sm text-slate-500">Cantidad de reservaciones mensuales registradas del curso actual contra el lapso pre-anual</p>
                    </div>
                    <div class="mt-4 w-full flex-1 min-h-[350px]">
                            <VueApexCharts
                                type="bar"
                                height="350"
                                :options="comparisonOptions"
                                :series="props.comparisonChart.series"
                            ></VueApexCharts>
                    </div>
                </div>

            </div>

        </div>
    </AppLayout>
</template>
