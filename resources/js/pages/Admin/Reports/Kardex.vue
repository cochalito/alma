<script setup lang="ts">
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Product } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, router } from '@inertiajs/vue3';
import { FileText, Search, Download } from 'lucide-vue-next';

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
    currentStock: number;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reportes', href: '#' },
    { title: 'Kardex de Inventario', href: '/admin/reports/kardex' },
];

const selectedProductId = ref<number | string>(props.selectedProduct?.id ?? '');
const selectedLocation = ref<string>(props.selectedLocation ?? '');

function fetchKardex() {
    if (!selectedProductId.value || !selectedLocation.value) return;

    router.get('/admin/reports/kardex', {
        product_id: selectedProductId.value,
        location: selectedLocation.value
    }, { preserveState: true, preserveScroll: true });
}

function calculateBalance(index: number) {
    if (!props.movements) return 0;

    let balance = 0;
    for (let i = 0; i <= index; i++) {
        const move = props.movements[i];
        if (move.type === 'in') balance += move.quantity;
        else if (move.type === 'out') balance -= move.quantity;
        else balance += move.quantity; // Adjustment could be + or - directly? Actually we passed absolute diff and type 'in'/'out' so adjustment is just treated as in/out based on diff
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
                            Movimientos de entradas y salidas de productos
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="rounded-xl border border-border bg-card p-4 shadow-sm flex flex-col sm:flex-row gap-4 no-print">
                <div class="flex-1 min-w-[200px] flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-muted-foreground uppercase">Producto</label>
                    <select
                        v-model="selectedProductId"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                    >
                        <option value="" disabled>Seleccione un producto</option>
                        <option v-for="prod in products" :key="prod.id" :value="prod.id">
                            {{ prod.name }} ({{ prod.category }})
                        </option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px] flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-muted-foreground uppercase">Sucursal</label>
                    <select
                        v-model="selectedLocation"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                    >
                        <option value="" disabled>Seleccione Sucursal</option>
                        <option v-for="loc in locations" :key="loc" :value="loc">
                            {{ loc }}
                        </option>
                    </select>
                </div>
                <div class="flex items-end">
                    <Button @click="fetchKardex" :disabled="!selectedProductId || !selectedLocation">
                        <Search class="mr-2 h-4 w-4" />
                        Generar Kardex
                    </Button>
                </div>
            </div>

            <div v-if="movements" class="space-y-6">

                <!-- Report Header for Print -->
                <div class="rounded-xl border border-border bg-card p-6 shadow-sm flex flex-col gap-2">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-xl font-bold">Tarjeta de Control (Kárdex)</h2>
                            <p class="text-muted-foreground">Sucursal: <span class="font-semibold text-foreground">{{ selectedLocation }}</span></p>
                        </div>
                        <Button variant="outline" class="no-print" @click="printReport">
                            <Download class="h-4 w-4 mr-2" /> Imprimir / PDF
                        </Button>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-4 bg-muted/30 p-4 rounded-lg">
                        <div>
                            <p class="text-xs text-muted-foreground uppercase mb-1">CÓDIGO / ID</p>
                            <p class="font-medium font-mono">{{ selectedProduct?.id.toString().padStart(5, '0') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground uppercase mb-1">ARTÍCULO</p>
                            <p class="font-medium">{{ selectedProduct?.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground uppercase mb-1">UNIDAD</p>
                            <p class="font-medium">Unidad (Pza.)</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground uppercase mb-1">STOCK ACTUAL</p>
                            <p class="font-medium text-lg text-primary">{{ currentStock }}</p>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="rounded-xl border border-border bg-card shadow-sm print-table-wrapper">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border bg-muted/40 text-left">
                                    <th class="px-4 py-3 font-semibold text-muted-foreground">Fecha y Hora</th>
                                    <th class="px-4 py-3 font-semibold text-muted-foreground">Usuario</th>
                                    <th class="px-4 py-3 font-semibold text-muted-foreground w-1/3">Detalle / Referencia</th>
                                    <th class="px-4 py-3 font-semibold text-muted-foreground text-center bg-blue-500/5">Ingreso</th>
                                    <th class="px-4 py-3 font-semibold text-muted-foreground text-center bg-red-500/5">Salida</th>
                                    <th class="px-4 py-3 font-semibold text-foreground text-center bg-green-500/10">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="movements.length === 0">
                                    <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">
                                        No hay movimientos registrados para este producto en la sucursal seleccionada.
                                    </td>
                                </tr>
                                <tr
                                    v-for="(move, index) in movements"
                                    :key="move.id"
                                    class="border-b border-border/50 hover:bg-muted/30"
                                >
                                    <td class="px-4 py-2 whitespace-nowrap">{{ new Date(move.date).toLocaleString('es-VE') }}</td>
                                    <td class="px-4 py-2 text-muted-foreground">{{ move.user }}</td>
                                    <td class="px-4 py-2">
                                        <div>{{ move.description }}</div>
                                        <div v-if="move.reservation" class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                                            {{ move.reservation }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-center font-medium bg-blue-500/5">
                                        {{ move.type === 'in' ? move.quantity : '-' }}
                                    </td>
                                    <td class="px-4 py-2 text-center font-medium bg-red-500/5">
                                        {{ move.type === 'out' ? move.quantity : '-' }}
                                    </td>
                                    <td class="px-4 py-2 text-center font-bold bg-green-500/10 text-primary">
                                        {{ calculateBalance(index) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div v-else class="rounded-xl border border-dashed border-border p-12 text-center text-muted-foreground no-print">
                <FileText class="mx-auto h-8 w-8 mb-3 opacity-50" />
                <p>Seleccione un producto y sucursal para generar el Kardex</p>
            </div>

        </div>
    </AppLayout>
</template>

<style>
@media print {
    body {
        background-color: white !important;
    }
    .no-print {
        display: none !important;
    }
    .print-table-wrapper {
        box-shadow: none !important;
        border: none !important;
    }
    @page {
        margin: 1cm;
        size: landscape;
    }
}
</style>
