<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User, type PaginatedData } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, router } from '@inertiajs/vue3';
import { Activity, Search, Eye, X, FileText, ChevronLeft, ChevronRight } from 'lucide-vue-next';

interface HistoryRecord {
    id: number;
    reservation_id: number;
    user_id: number | null;
    action: string;
    changes: Record<string, { old: any; new: any }>;
    created_at: string;
    user?: User;
    reservation?: {
        id: number;
        customer: { firstname: string; lastname: string };
    };
}

interface Props {
    histories: PaginatedData<HistoryRecord>;
    users: User[];
    recentReservations: { id: number; label: string }[];
    filters: {
        user_id: string | null;
        reservation_id: string | null;
        date_from: string | null;
        date_to: string | null;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reportes', href: '#' },
    { title: 'Actividad de Reservas', href: '/admin/reports/activity' },
];

const selectedUserId = ref(props.filters.user_id ?? '');
const selectedReservationId = ref(props.filters.reservation_id ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

function applyFilters() {
    router.get('/admin/reports/activity', {
        user_id: selectedUserId.value,
        reservation_id: selectedReservationId.value,
        date_from: dateFrom.value,
        date_to: dateTo.value
    }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    selectedUserId.value = '';
    selectedReservationId.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
}

function goToPage(url: string | null) {
    if (url) {
        router.get(url, {
            user_id: selectedUserId.value,
            reservation_id: selectedReservationId.value,
            date_from: dateFrom.value,
            date_to: dateTo.value
        }, { preserveState: true, preserveScroll: true });
    }
}

// Modal handling
const selectedHistory = ref<HistoryRecord | null>(null);

function viewDetails(history: HistoryRecord) {
    selectedHistory.value = history;
}

function closeDetails() {
    selectedHistory.value = null;
}

const friendlyFieldNames: Record<string, string> = {
    employee_id: 'Empleado asignado',
    departament_id: 'Depto. Asignado',
    customer_id: 'Huésped',
    location: 'Sucursal',
    check_in: 'Check-In',
    check_out: 'Check-Out',
    total_stay_cost: 'Costo Estadía',
    total_extra_cost: 'Costo Extra',
    requests: 'Solicitudes',
    comments: 'Comentarios',
    status: 'Estado',
    products: 'Productos del Minibar'
};

const statusLabels: Record<string, string> = {
    '1': 'Confirmada',
    '2': 'Check In',
    '3': 'Check Out',
    '4': 'Cancelada'
};

function formatValue(key: string, val: any) {
    if (val === null || val === undefined) return 'N/A';
    if (key === 'status') return statusLabels[val] || val;
    return val;
}
</script>

<template>
    <Head title="Reporte de Actividad" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Activity class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Actividad de Edición</h1>
                        <p class="text-sm text-muted-foreground">
                            Historial de cambios realizados en las reservas
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="rounded-xl border border-border bg-card p-5 shadow-sm space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-muted-foreground uppercase">Usuario</label>
                        <select
                            v-model="selectedUserId"
                            class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                            <option value="">Cualquier Usuario</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-muted-foreground uppercase">ID Reserva</label>
                        <Input v-model="selectedReservationId" placeholder="Ej. 123" class="h-9" type="number" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-muted-foreground uppercase">Desde</label>
                        <Input v-model="dateFrom" type="date" class="h-9" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-muted-foreground uppercase">Hasta</label>
                        <Input v-model="dateTo" type="date" class="h-9" />
                    </div>

                    <div class="flex items-end gap-2">
                        <Button @click="applyFilters" class="flex-1">
                            <Search class="mr-2 h-4 w-4" /> Buscar
                        </Button>
                        <Button @click="resetFilters" variant="outline" size="icon" title="Limpiar Filtros">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden dark:bg-zinc-950">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/60 text-left">
                                <th class="px-4 py-3 font-bold text-muted-foreground">Fecha y Hora</th>
                                <th class="px-4 py-3 font-bold text-muted-foreground">Usuario</th>
                                <th class="px-4 py-3 font-bold text-muted-foreground text-center">Reserva</th>
                                <th class="px-4 py-3 font-bold text-muted-foreground">Acción</th>
                                <th class="px-4 py-3 font-bold text-muted-foreground text-right">Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="histories.data.length === 0">
                                <td colspan="5" class="px-4 py-12 text-center text-muted-foreground">
                                    <div class="flex flex-col items-center gap-2">
                                        <FileText class="h-8 w-8 opacity-20" />
                                        <p>No se encontraron registros de actividad con los filtros actuales.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr
                                v-for="history in histories.data"
                                :key="history.id"
                                class="border-b border-border/50 hover:bg-muted/30 transition-colors"
                            >
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium">{{ new Date(history.created_at).toLocaleDateString('es-BO') }}</div>
                                    <div class="text-[10px] text-muted-foreground">{{ new Date(history.created_at).toLocaleTimeString('es-BO') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-primary/10 text-[10px] font-bold text-primary">
                                            {{ history.user?.name.substring(0, 2).toUpperCase() || 'SIS' }}
                                        </div>
                                        <span class="font-medium">{{ history.user?.name || 'Sistema' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded-full inline-block text-xs">
                                        #{{ history.reservation_id }}
                                    </div>
                                    <div class="text-[10px] text-muted-foreground mt-0.5">
                                        {{ history.reservation?.customer.firstname }} {{ history.reservation?.customer.lastname }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="history.action === 'updated'" class="inline-flex items-center rounded-md bg-yellow-50 dark:bg-yellow-900/20 px-2.5 py-0.5 text-xs font-semibold text-yellow-700 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800">
                                        Edición (Update)
                                    </span>
                                    <span v-else-if="history.action === 'created'" class="inline-flex items-center rounded-md bg-green-50 dark:bg-green-900/20 px-2.5 py-0.5 text-xs font-semibold text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800">
                                        Creación (Create)
                                    </span>
                                    <span v-else class="inline-flex items-center rounded-md bg-gray-50 dark:bg-gray-900/20 px-2.5 py-0.5 text-xs font-semibold text-gray-700 dark:text-gray-400 border border-gray-200 dark:border-gray-800">
                                        {{ history.action }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Button size="sm" variant="ghost" @click="viewDetails(history)" class="h-8">
                                        <Eye class="mr-2 h-4 w-4" /> Ver Cambios
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="histories.last_page > 1" class="flex items-center justify-between rounded-xl border border-border bg-card p-4 shadow-sm">
                <p class="text-sm text-muted-foreground">
                    Mostrando del <span class="font-bold text-foreground">{{ histories.from }}</span> al <span class="font-bold text-foreground">{{ histories.to }}</span> de <span class="font-bold text-foreground">{{ histories.total }}</span> registros
                </p>
                <div class="flex items-center gap-2">
                    <Button
                        v-for="(link, index) in histories.links"
                        :key="index"
                        :disabled="!link.url"
                        :variant="link.active ? 'default' : 'outline'"
                        size="icon"
                        class="h-8 w-8 text-xs"
                        @click="goToPage(link.url)"
                    >
                        <span v-html="link.label.includes('Previous') ? '&laquo;' : link.label.includes('Next') ? '&raquo;' : link.label"></span>
                    </Button>
                </div>
            </div>

        </div>

        <!-- Modal Detalles -->
        <div v-if="selectedHistory" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-in fade-in">
            <div class="bg-card w-full max-w-3xl rounded-xl shadow-lg flex flex-col max-h-[90vh]">

                <div class="flex items-center justify-between p-4 border-b border-border">
                    <div>
                        <h2 class="text-lg font-bold">Detalles de Edición</h2>
                        <p class="text-xs text-muted-foreground">Reserva #{{ selectedHistory.reservation_id }} - {{ new Date(selectedHistory.created_at).toLocaleString('es-BO') }}</p>
                    </div>
                    <Button variant="ghost" size="icon" @click="closeDetails" class="rounded-full">
                        <X class="h-5 w-5" />
                    </Button>
                </div>

                <div class="p-6 overflow-y-auto space-y-6">
                    <div v-if="!selectedHistory.changes || Object.keys(selectedHistory.changes).length === 0" class="text-center py-8 text-muted-foreground">
                        <p>No se registraron cambios específicos o no hay datos legibles.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="(change, key) in selectedHistory.changes" :key="key" class="border border-border rounded-lg overflow-hidden">
                            <div class="bg-muted px-4 py-2 border-b border-border font-semibold text-sm">
                                Campo: <span class="text-primary">{{ friendlyFieldNames[key] || key }}</span>
                            </div>

                            <div v-if="key !== 'products'" class="grid grid-cols-2 text-sm divide-x divide-border">
                                <div class="p-4 bg-red-500/5">
                                    <div class="text-[10px] uppercase font-bold text-red-700 dark:text-red-400 mb-1">Valor Anterior</div>
                                    <div class="text-muted-foreground line-through">{{ formatValue(key, change.old) }}</div>
                                </div>
                                <div class="p-4 bg-green-500/5">
                                    <div class="text-[10px] uppercase font-bold text-green-700 dark:text-green-400 mb-1">Nuevo Valor</div>
                                    <div class="font-medium text-foreground">{{ formatValue(key, change.new) }}</div>
                                </div>
                            </div>

                            <!-- Special handling for products array -->
                            <div v-else class="p-4">
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div class="bg-red-500/5 p-3 rounded border border-red-500/20">
                                        <div class="text-[10px] uppercase font-bold text-red-700 dark:text-red-400 mb-2">Lista Anterior</div>
                                        <ul class="space-y-2">
                                            <li v-if="!change.old || change.old.length === 0" class="text-muted-foreground italic text-xs">Sin productos</li>
                                            <li v-for="p in change.old" :key="p.product_id" class="text-xs">
                                                <span class="font-bold">{{ p.quantity }}x</span> {{ p.name }} (Bs. {{ p.subtotal }})
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="bg-green-500/5 p-3 rounded border border-green-500/20">
                                        <div class="text-[10px] uppercase font-bold text-green-700 dark:text-green-400 mb-2">Nueva Lista</div>
                                        <ul class="space-y-2">
                                            <li v-if="!change.new || change.new.length === 0" class="text-muted-foreground italic text-xs">Sin productos</li>
                                            <li v-for="p in change.new" :key="p.product_id" class="text-xs">
                                                <span class="font-bold">{{ p.quantity }}x</span> {{ p.name }} (Bs. {{ p.subtotal }})
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t border-border bg-muted/30 text-right">
                    <Button @click="closeDetails" variant="outline">Cerrar</Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
