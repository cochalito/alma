<script setup lang="ts">
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { FileText, Calendar, Filter } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface Sale {
    reservation_id: number;
    location: string;
    sale_date: string;
    customer_name: string;
    product_name: string;
    quantity: number;
    unit_price: number;
    subtotal: number;
}

interface Props {
    sales: Sale[];
    locations: string[];
    selectedLocation: string | null;
    filters: {
        date_from: string | null;
        date_to: string | null;
    };
    summary: {
        total_items: number;
        total_revenue: number;
    };
}

const props = defineProps<Props>();

const locationField = ref(props.selectedLocation || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

// Format helper
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(amount);
};

const formatDate = (dateString: string) => {
    const d = new Date(dateString);
    return d.toLocaleDateString('es-BO', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

function applyFilters() {
    router.get('/admin/reports/sales', {
        location: locationField.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
    }, { preserveState: true, replace: true });
}

function printReport() {
    window.print();
}
</script>

<template>
    <Head title="Reporte de Ventas" />
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }, { title: 'Reporte de Ventas', href: '/admin/reports/sales' }]">
        <div class="flex flex-col gap-6 p-6">
            
            <!-- Header (Hidden on print) -->
            <div class="flex items-center justify-between print:hidden">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <FileText class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Reporte de Ventas</h1>
                        <p class="text-sm text-muted-foreground">Detalle de productos vendidos y resumen de ventas.</p>
                    </div>
                </div>
                <Button @click="printReport" variant="outline">
                    Imprimir Reporte
                </Button>
            </div>

            <!-- Filters (Hidden on print) -->
            <div class="rounded-xl border border-border bg-card p-4 shadow-sm flex flex-wrap gap-4 items-end print:hidden">
                <div class="flex flex-col gap-1.5 min-w-[140px]">
                    <label class="text-xs font-semibold text-muted-foreground uppercase flex items-center gap-1">
                        <Calendar class="h-3 w-3" /> Fecha Inicio
                    </label>
                    <Input type="date" v-model="dateFrom" class="h-9" />
                </div>
                <div class="flex flex-col gap-1.5 min-w-[140px]">
                    <label class="text-xs font-semibold text-muted-foreground uppercase flex items-center gap-1">
                        <Calendar class="h-3 w-3" /> Fecha Fin
                    </label>
                    <Input type="date" v-model="dateTo" class="h-9" />
                </div>
                <div class="flex flex-col gap-1.5 min-w-[140px]" v-if="locations.length > 1">
                    <label class="text-xs font-semibold text-muted-foreground uppercase flex items-center gap-1">
                        <Filter class="h-3 w-3" /> Sucursal
                    </label>
                    <select v-model="locationField" class="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <option value="">Todas</option>
                        <option v-for="loc in locations" :key="loc" :value="loc">{{ loc }}</option>
                    </select>
                </div>
                
                <Button @click="applyFilters" class="h-9">
                    Aplicar Filtros
                </Button>
            </div>

            <!-- Print Header -->
            <div class="hidden print:block mb-6">
                <h2 class="text-xl font-bold text-center uppercase tracking-wider mb-2">Detalle de Ventas</h2>
                <div class="flex justify-between text-sm">
                    <div>
                        <p><span class="font-bold">Periodo:</span> {{ dateFrom || 'Inicio' }} al {{ dateTo || 'Fin' }}</p>
                        <p v-if="locationField"><span class="font-bold">Sucursal:</span> {{ locationField }}</p>
                    </div>
                    <div>
                        <p><span class="font-bold">Fecha Resumen:</span> {{ new Date().toLocaleDateString('es-BO') }}</p>
                    </div>
                </div>
                <div class="border-b-2 border-primary mt-4"></div>
            </div>

            <!-- Report Content -->
            <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden print:border-none print:shadow-none">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-muted/40 text-muted-foreground print:bg-gray-100 print:text-black">
                            <tr class="border-b border-border print:border-gray-300">
                                <th class="px-4 py-3 font-semibold">Fecha</th>
                                <th class="px-4 py-3 font-semibold">Sucursal</th>
                                <th class="px-4 py-3 font-semibold">Reserva/Huésped</th>
                                <th class="px-4 py-3 font-semibold">Producto</th>
                                <th class="px-4 py-3 font-semibold text-right">Cant.</th>
                                <th class="px-4 py-3 font-semibold text-right">Precio Un.</th>
                                <th class="px-4 py-3 font-semibold text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="sales.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-muted-foreground italic">
                                    No hay ventas registradas en las fechas seleccionadas.
                                </td>
                            </tr>
                            <tr v-for="(sale, idx) in sales" :key="idx" class="border-b border-border/50 hover:bg-muted/30 print:border-gray-200">
                                <td class="px-4 py-3 text-xs whitespace-nowrap">{{ formatDate(sale.sale_date) }}</td>
                                <td class="px-4 py-3">{{ sale.location }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-xs">#{{ String(sale.reservation_id).padStart(5, '0') }}</div>
                                    <div class="text-xs text-muted-foreground print:text-gray-600">{{ sale.customer_name || 'Desconocido' }}</div>
                                </td>
                                <td class="px-4 py-3 font-medium uppercase text-xs">{{ sale.product_name }}</td>
                                <td class="px-4 py-3 text-right">{{ sale.quantity }}</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(sale.unit_price) }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ formatCurrency(sale.subtotal) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-primary/5 print:bg-gray-100 border-t-2 border-border print:border-gray-400">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right font-bold uppercase text-xs">Totales Generales</td>
                                <td class="px-4 py-3 text-right font-bold">{{ summary.total_items }}</td>
                                <td></td>
                                <td class="px-4 py-3 text-right font-bold text-primary print:text-black text-base">{{ formatCurrency(summary.total_revenue) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Print Footer -->
            <div class="hidden print:block mt-8 text-center text-xs text-gray-500">
                Resumen generado por el sistema el {{ new Date().toLocaleString('es-BO') }}
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    @page {
        margin: 1.5cm;
    }
}
</style>
