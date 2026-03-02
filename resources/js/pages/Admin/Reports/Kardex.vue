<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Product } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, router } from '@inertiajs/vue3';
import { FileText, Search, Download, Calendar } from 'lucide-vue-next';

interface Props {
    products: Product[];
    locations: string[];
    movements?: {
        id: number;
        type: 'in' | 'out' | 'adjustment';
        quantity: number;
        description: string;
        date: string;
        user: string;
        reservation: string | null;
    }[] | null;
    selectedProduct?: Product | null;
    selectedLocation?: string | null;
    initialBalance: number;
    currentStock: number;
    filters?: {
        period: string;
        date_from: string | null;
        date_to: string | null;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reportes', href: '#' },
    { title: 'Kardex de Inventario', href: '/admin/reports/kardex' },
];

const selectedProductId = ref<number | string>(props.selectedProduct?.id ?? '');
const selectedLocation = ref<string>(props.selectedLocation ?? '');
const period = ref(props.filters?.period ?? 'month');
const dateFrom = ref(props.filters?.date_from ?? '');
const dateTo = ref(props.filters?.date_to ?? '');

function fetchKardex() {
    if (!selectedProductId.value || !selectedLocation.value) return;

    router.get('/admin/reports/kardex', {
        product_id: selectedProductId.value,
        location: selectedLocation.value,
        period: period.value,
        date_from: dateFrom.value,
        date_to: dateTo.value
    }, { preserveState: true, preserveScroll: true });
}

function calculateBalance(index: number) {
    if (!props.movements) return Number(props.initialBalance);

    let balance = Number(props.initialBalance);
    for (let i = 0; i <= index; i++) {
        const move = props.movements[i];
        if (move.type === 'in') balance += Number(move.quantity);
        else if (move.type === 'out') balance -= Number(move.quantity);
    }
    return balance;
}

function printReport() {
    window.print();
}

</script>

<template>
    <Head title="Kardex de Inventario" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between no-print">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <FileText class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Kardex de Inventario</h1>
                        <p class="text-sm text-muted-foreground">
                            Análisis detallado de entradas y salidas
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="rounded-xl border border-border bg-card p-5 shadow-sm space-y-4 no-print">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-muted-foreground uppercase">Producto</label>
                        <select
                            v-model="selectedProductId"
                            class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                            <option value="" disabled>Seleccione un producto</option>
                            <option v-for="prod in products" :key="prod.id" :value="prod.id">
                                {{ prod.name }} ({{ prod.category }})
                            </option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-muted-foreground uppercase">Sucursal</label>
                        <select
                            v-model="selectedLocation"
                            class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                            <option value="" disabled>Seleccione Sucursal</option>
                            <option v-for="loc in locations" :key="loc" :value="loc">
                                {{ loc }}
                            </option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-muted-foreground uppercase">Periodo</label>
                        <select
                            v-model="period"
                            class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                            <option value="month">Mes Actual</option>
                            <option value="year">Año Actual</option>
                            <option value="custom">Rango de Fechas</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <Button @click="fetchKardex" :disabled="!selectedProductId || !selectedLocation" class="w-full">
                            <Search class="mr-2 h-4 w-4" />
                            Generar Reporte
                        </Button>
                    </div>
                </div>

                <!-- Custom Range Dates -->
                <div v-if="period === 'custom'" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-border/50 animate-in fade-in slide-in-from-top-2">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-muted-foreground uppercase">Desde</label>
                        <Input v-model="dateFrom" type="date" class="h-9" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-muted-foreground uppercase">Hasta</label>
                        <Input v-model="dateTo" type="date" class="h-9" />
                    </div>
                </div>
            </div>

            <div v-if="movements" class="space-y-6">

                <!-- Report Info Card -->
                <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
                    <div class="flex flex-col md:flex-row justify-between items-start gap-4">
                        <div>
                            <h2 class="text-xl font-bold flex items-center gap-2">
                                <Calendar class="h-5 w-5 text-primary" />
                                Kárdex de Inventario
                            </h2>
                            <p class="text-muted-foreground">
                                Sucursal: <span class="font-bold text-foreground">{{ selectedLocation }}</span>
                                | Periodo: <span class="font-medium text-foreground italic">{{
                                    period === 'month' ? 'Mes Actual' : period === 'year' ? 'Año Actual' : `Del ${dateFrom} al ${dateTo}`
                                }}</span>
                            </p>
                        </div>
                        <Button variant="outline" class="no-print" @click="printReport">
                            <Download class="h-4 w-4 mr-2" /> Exportar PDF
                        </Button>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6 bg-muted/30 p-4 rounded-lg">
                        <div class="space-y-1">
                            <p class="text-[10px] text-muted-foreground uppercase font-bold">Artículo</p>
                            <p class="font-bold text-sm">{{ selectedProduct?.name }}</p>
                        </div>
                        <div class="space-y-1 text-center font-mono">
                            <p class="text-[10px] text-muted-foreground uppercase font-bold">Unidad</p>
                            <p class="font-medium text-sm">Pza.</p>
                        </div>
                        <div class="space-y-1 text-center">
                            <p class="text-[10px] text-muted-foreground uppercase font-bold">Saldo Inicial</p>
                            <p class="font-bold text-sm text-blue-600">{{ initialBalance }}</p>
                        </div>
                        <div class="space-y-1 text-right">
                            <p class="text-[10px] text-muted-foreground uppercase font-bold">Stock Actual</p>
                            <p class="font-black text-lg text-primary">{{ currentStock }}</p>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden dark:bg-zinc-950">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border bg-muted/60 text-left">
                                    <th class="px-4 py-3 font-bold text-muted-foreground">Fecha</th>
                                    <th class="px-4 py-3 font-bold text-muted-foreground">Detalle / Referencia</th>
                                    <th class="px-4 py-3 font-bold text-center bg-blue-500/5 text-blue-700 dark:text-blue-400">Entrada</th>
                                    <th class="px-4 py-3 font-bold text-center bg-red-500/5 text-red-700 dark:text-red-400">Salida</th>
                                    <th class="px-4 py-3 font-bold text-center bg-green-500/10 text-green-700 dark:text-green-400">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- INITIAL BALANCE ROW -->
                                <tr class="border-b border-border bg-muted/20 italic font-medium">
                                    <td class="px-4 py-2 text-muted-foreground">-</td>
                                    <td class="px-4 py-2 text-foreground">Saldo Anterior al periodo seleccionado</td>
                                    <td class="px-4 py-2 text-center">-</td>
                                    <td class="px-4 py-2 text-center">-</td>
                                    <td class="px-4 py-2 text-center font-bold">{{ initialBalance }}</td>
                                </tr>

                                <tr v-if="movements.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                        No se registraron movimientos en este periodo.
                                    </td>
                                </tr>
                                <tr
                                    v-for="(move, index) in movements"
                                    :key="move.id"
                                    class="border-b border-border/50 hover:bg-muted/30 transition-colors"
                                >
                                    <td class="px-4 py-2 whitespace-nowrap text-xs">
                                        {{ new Date(move.date).toLocaleDateString('es-BO') }}
                                        <span class="text-[10px] text-muted-foreground block">{{ new Date(move.date).toLocaleTimeString('es-BO', {hour:'2-digit', minute:'2-digit'}) }}</span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="font-medium text-xs">{{ move.description }}</div>
                                        <div v-if="move.reservation" class="text-[10px] text-blue-600 dark:text-blue-400 font-bold bg-blue-50 dark:bg-blue-900/20 px-1 rounded inline-block">
                                            {{ move.reservation }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-center font-bold bg-blue-500/5">
                                        <span v-if="move.type === 'in'">+{{ move.quantity }}</span>
                                        <span v-else class="text-muted-foreground/30">-</span>
                                    </td>
                                    <td class="px-4 py-2 text-center font-bold bg-red-500/5">
                                        <span v-if="move.type === 'out'">-{{ move.quantity }}</span>
                                        <span v-else class="text-muted-foreground/30">-</span>
                                    </td>
                                    <td class="px-4 py-2 text-center font-black bg-green-500/10">
                                        {{ calculateBalance(index) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div v-else class="rounded-xl border border-dashed border-border p-16 text-center text-muted-foreground no-print bg-card">
                <FileText class="mx-auto h-12 w-12 mb-4 opacity-20" />
                <p class="text-lg font-medium">Filtre por producto y sucursal para generar el Kardex</p>
                <p class="text-sm opacity-60">Seleccione los criterios en la parte superior</p>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    body {
        background-color: white !important;
    }
    .no-print {
        display: none !important;
    }
    .p-6 {
        padding: 0 !important;
    }
    .shadow-sm {
        box-shadow: none !important;
    }
    .border {
        border-color: #e5e7eb !important;
    }
    @page {
        margin: 1.5cm;
        size: auto;
    }
}
</style>
