<script setup lang="ts">
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import CharterReservationModal from '@/components/CharterReservationModal.vue';
import { type BreadcrumbItem, type PaginatedData, type Reservation, type ReservationStatus, type Departament, type User, type Product, type Customer } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft, ChevronRight, Eye, Pencil, Plus, Trash2, ArrowUpDown, ArrowUp, ArrowDown, Search, X } from 'lucide-vue-next';
import debounce from 'lodash/debounce';

interface Props {
    reservations: PaginatedData<Reservation>;
    filters?: { search?: string; location?: string; departament_id?: string; date?: string; sort?: string; direction?: string };
    departments: Departament[];
    employees: User[];
    products: Product[];
    customers: Customer[];
}

const props = withDefaults(defineProps<Props>(), {
    filters: () => ({}),
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reservaciones', href: '/admin/reservations' },
];

const statusConfig: Record<ReservationStatus, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
    '1': { label: 'Confirmada', variant: 'default' },
    '2': { label: 'Check In',   variant: 'secondary' },
    '3': { label: 'Check Out',  variant: 'outline' },
    '4': { label: 'Cancelada', variant: 'destructive' },
};

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleString('es-VE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(amount);
}

function deleteReservation(id: number) {
    if (confirm('¿Estás seguro de que deseas eliminar esta reservación?')) {
        router.delete(`/admin/reservations/${id}`);
    }
}

const search = ref(props.filters?.search ?? '');
const selectedLocation = ref(props.filters?.location ?? '');
const selectedDepartment = ref(props.filters?.departament_id ?? '');
const selectedDate = ref(props.filters?.date ?? '');

const sortParams = ref({
    sort: props.filters?.sort ?? 'updated_at',
    direction: props.filters?.direction ?? 'desc'
});

const applyFilters = debounce(() => {
    router.get('/admin/reservations', {
        search: search.value,
        location: selectedLocation.value,
        departament_id: selectedDepartment.value,
        date: selectedDate.value,
        sort: sortParams.value.sort,
        direction: sortParams.value.direction
    }, { preserveState: true, replace: true });
}, 300);

watch([search, selectedLocation, selectedDepartment, selectedDate], () => {
    applyFilters();
});

function handleSort(column: string) {
    if (sortParams.value.sort === column) {
        sortParams.value.direction = sortParams.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sortParams.value.sort = column;
        sortParams.value.direction = 'asc';
    }
    applyFilters();
}

function clearFilters() {
    search.value = '';
    selectedLocation.value = '';
    selectedDepartment.value = '';
    selectedDate.value = '';
}

const isModalOpen = ref(false);
const activeReservation = ref<Reservation | null>(null);

function openCreateModal() {
    activeReservation.value = null;
    isModalOpen.value = true;
}

function openEditModal(res: Reservation) {
    activeReservation.value = res;
    isModalOpen.value = true;
}
</script>

<template>
    <Head title="Reservaciones" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <CalendarDays class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Reservaciones</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ reservations.total }} reservaciones en total
                        </p>
                    </div>
                </div>
                <Button @click="openCreateModal">
                    <Plus class="mr-2 h-4 w-4" />
                    Nueva Reservación
                </Button>
            </div>

            <!-- Filtros -->
            <div class="rounded-xl border border-border bg-card p-4 shadow-sm flex flex-col sm:flex-row gap-4 flex-wrap items-end">
                <div class="flex-1 min-w-[200px] flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-muted-foreground uppercase">Buscar</label>
                    <div class="relative">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input v-model="search" placeholder="Buscar por Nombre, DNI, o Nro..." class="pl-9 h-9" />
                    </div>
                </div>
                <div class="flex flex-col gap-1.5 min-w-[140px]">
                    <label class="text-xs font-semibold text-muted-foreground uppercase">Sucursal</label>
                    <select v-model="selectedLocation" class="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <option value="">Todas</option>
                        <option value="LP">La Paz</option>
                        <option value="UYUNI">Uyuni</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5 min-w-[140px]">
                    <label class="text-xs font-semibold text-muted-foreground uppercase">Departamento</label>
                    <select v-model="selectedDepartment" class="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <option value="">Todos</option>
                        <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                            {{ dept.code }} ({{ dept.location }})
                        </option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5 min-w-[160px]">
                    <label class="text-xs font-semibold text-muted-foreground uppercase">Fecha Activa</label>
                    <Input type="date" v-model="selectedDate" class="h-9" />
                </div>
                <Button variant="ghost" size="sm" class="h-9 text-muted-foreground hover:text-foreground" @click="clearFilters" v-if="search || selectedLocation || selectedDepartment || selectedDate">
                    <X class="h-4 w-4 mr-1" />
                    Limpiar
                </Button>
            </div>

            <!-- Table Card -->
            <div class="rounded-xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40">
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground cursor-pointer hover:bg-muted/60 transition-colors" @click="handleSort('id')">
                                    <div class="flex items-center gap-1"># Reserva <ArrowUp v-if="sortParams.sort==='id' && sortParams.direction==='asc'" class="h-3 w-3"/><ArrowDown v-else-if="sortParams.sort==='id' && sortParams.direction==='desc'" class="h-3 w-3"/><ArrowUpDown v-else class="h-3 w-3 opacity-30"/></div>
                                </th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Huésped</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground cursor-pointer hover:bg-muted/60" @click="handleSort('location')">
                                    <div class="flex items-center gap-1">Lugar/Depto <ArrowUp v-if="sortParams.sort==='location' && sortParams.direction==='asc'" class="h-3 w-3"/><ArrowDown v-else-if="sortParams.sort==='location' && sortParams.direction==='desc'" class="h-3 w-3"/><ArrowUpDown v-else class="h-3 w-3 opacity-30"/></div>
                                </th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground cursor-pointer hover:bg-muted/60" @click="handleSort('check_in')">
                                    <div class="flex items-center gap-1">Check-in <ArrowUp v-if="sortParams.sort==='check_in' && sortParams.direction==='asc'" class="h-3 w-3"/><ArrowDown v-else-if="sortParams.sort==='check_in' && sortParams.direction==='desc'" class="h-3 w-3"/><ArrowUpDown v-else class="h-3 w-3 opacity-30"/></div>
                                </th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground cursor-pointer hover:bg-muted/60" @click="handleSort('check_out')">
                                    <div class="flex items-center gap-1">Check-out <ArrowUp v-if="sortParams.sort==='check_out' && sortParams.direction==='asc'" class="h-3 w-3"/><ArrowDown v-else-if="sortParams.sort==='check_out' && sortParams.direction==='desc'" class="h-3 w-3"/><ArrowUpDown v-else class="h-3 w-3 opacity-30"/></div>
                                </th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Total</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground cursor-pointer hover:bg-muted/60" @click="handleSort('status')">
                                    <div class="flex items-center gap-1">Estado <ArrowUp v-if="sortParams.sort==='status' && sortParams.direction==='asc'" class="h-3 w-3"/><ArrowDown v-else-if="sortParams.sort==='status' && sortParams.direction==='desc'" class="h-3 w-3"/><ArrowUpDown v-else class="h-3 w-3 opacity-30"/></div>
                                </th>
                                <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="reservations.data.length === 0">
                                <td colspan="8" class="px-4 py-12 text-center text-muted-foreground">
                                    No hay reservaciones registradas aún.
                                </td>
                            </tr>
                            <tr
                                v-for="reservation in reservations.data"
                                :key="reservation.id"
                                class="border-b border-border/50 transition-colors hover:bg-muted/30"
                            >
                                <td class="px-4 py-3 font-mono font-medium text-primary">
                                    #{{ String(reservation.id).padStart(5, '0') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="reservation.customer" class="font-medium">{{ reservation.customer.firstname }} {{ reservation.customer.lastname }}</div>
                                    <div v-if="reservation.customer" class="text-xs text-muted-foreground">{{ reservation.customer.email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="reservation.departament" class="font-medium">{{ reservation.departament.code }}</div>
                                    <div v-if="reservation.departament" class="text-xs text-muted-foreground">{{ reservation.departament.location }}</div>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(reservation.check_in) }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(reservation.check_out) }}</td>
                                <td class="px-4 py-3 font-semibold text-primary">
                                    {{ formatCurrency(reservation.total_stay_cost + reservation.total_extra_cost) }}
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :variant="statusConfig[reservation.status].variant">
                                        {{ statusConfig[reservation.status].label }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <!--
                                        <Link :href="`/admin/reservations/${reservation.id}`">
                                            <Button variant="ghost" size="icon" title="Ver detalle">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        -->
                                        <Button variant="ghost" size="icon" title="Editar" @click="openEditModal(reservation)">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Eliminar"
                                            class="text-destructive hover:text-destructive"
                                            @click="deleteReservation(reservation.id)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="reservations.last_page > 1" class="flex items-center justify-between border-t border-border px-4 py-3">
                    <p class="text-sm text-muted-foreground">
                        Mostrando {{ reservations.from }}–{{ reservations.to }} de {{ reservations.total }}
                    </p>
                    <div class="flex items-center gap-1">
                        <template v-for="link in reservations.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                            >
                                <Button
                                    variant="outline"
                                    class="h-8 items-center justify-center p-0"
                                    :class="[
                                        link.active ? 'bg-primary text-primary-foreground hover:bg-primary/90' : '',
                                        link.label.includes('Previous') || link.label.includes('Next') ? 'px-3' : 'w-8'
                                    ]"
                                >
                                    <template v-if="link.label.includes('Previous')">
                                        <ChevronLeft class="h-4 w-4 mr-1" /> Anterior
                                    </template>
                                    <template v-else-if="link.label.includes('Next')">
                                        Siguiente <ChevronRight class="h-4 w-4 ml-1" />
                                    </template>
                                    <span v-else v-html="link.label"></span>
                                </Button>
                            </Link>
                        </template>
                    </div>
                </div>
            </div>

        </div>

        <CharterReservationModal
            v-model:open="isModalOpen"
            :reservation="activeReservation"
            :departments="departments"
            :employees="employees"
            :products="products"
            :customers="customers"
            default-location=""
        />

    </AppLayout>
</template>
