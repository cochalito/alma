<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CharterReservationModal from '@/components/CharterReservationModal.vue';
import { type BreadcrumbItem, type Departament, type Reservation, type User, type Product, type Customer } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Ship, ChevronLeft, ChevronRight, Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ref, watch } from 'vue';
import { format, addDays, subDays, parseISO } from 'date-fns';

interface Props {
    location: string;
    date: string;
    departments: Departament[];
    reservations: Reservation[];
    employees: User[];
    products: Product[];
    customers: Customer[];
    weekDays: Array<{
        date: string;
        dayName: string;
        dayNumber: number;
        isToday: boolean;
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Charter', href: '/admin/charter' },
];

const isModalOpen = ref(false);
const selectedReservation = ref<Reservation | null>(null);

function openEditModal(reservation: Reservation) {
    selectedReservation.value = reservation;
    isModalOpen.value = true;
}

function openCreateModal() {
    selectedReservation.value = null;
    isModalOpen.value = true;
}

const selectedLocation = ref(props.location);
const selectedDate = ref(props.date);

function updateFilters() {
    router.get('/admin/charter', {
        location: selectedLocation.value,
        date: selectedDate.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

watch([selectedLocation, selectedDate], () => {
    updateFilters();
});

function previousWeek() {
    selectedDate.value = format(subDays(parseISO(selectedDate.value), 7), 'yyyy-MM-dd');
}

function nextWeek() {
    selectedDate.value = format(addDays(parseISO(selectedDate.value), 7), 'yyyy-MM-dd');
}

function goToToday() {
    selectedDate.value = format(new Date(), 'yyyy-MM-dd');
}

// Logic to check if a reservation starts on a specific day for a department in this week's perspective
function getReservationStart(deptId: number, dateStr: string) {
    const res = props.reservations.find(r => {
        if (r.departament_id !== deptId) return false;

        const checkIn = r.check_in.substring(0, 10);
        const checkOut = r.check_out.substring(0, 10);

        // If the reservation started exactly on this day
        if (checkIn === dateStr) return true;

        // If it's the first day of the VISIBLE week and the reservation started BEFORE but is still active
        if (dateStr === props.weekDays[0].date && checkIn < dateStr && checkOut > dateStr) return true;

        return false;
    });
    return res || null;
}

// Calculate how many cells it should occupy
function getReservationColspan(deptId: number, dateStr: string) {
    const res = getReservationStart(deptId, dateStr);
    if (!res) return 1;

    let colspan = 0;
    const startIndex = props.weekDays.findIndex(d => d.date === dateStr);
    const checkIn = res.check_in.substring(0, 10);
    const checkOut = res.check_out.substring(0, 10);

    for (let i = startIndex; i < props.weekDays.length; i++) {
        const currentDay = props.weekDays[i].date;
        if (currentDay >= checkIn && currentDay < checkOut) {
            colspan++;
        } else {
            break;
        }
    }
    return colspan;
}

// Check if a cell is covered by a colspan from a previous cell
function isCellCovered(deptId: number, dateStr: string) {
    const currentIndex = props.weekDays.findIndex(d => d.date === dateStr);

    for (let i = 0; i < currentIndex; i++) {
        const prevDay = props.weekDays[i].date;
        const res = getReservationStart(deptId, prevDay);
        if (res) {
            const colspan = getReservationColspan(deptId, prevDay);
            if (i + colspan > currentIndex) return true;
        }
    }
    return false;
}

function getReservationColor(status: string) {
    switch (status) {
        case '1': return 'bg-blue-100 text-blue-800 border-l-4 border-blue-500'; // Confirmada
        case '2': return 'bg-green-100 text-green-800 border-l-4 border-green-500'; // Check-In
        case '3': return 'bg-orange-100 text-orange-800 border-gray-400'; // Check-Out (user said Gray, but activity is activity)
        case '4': return 'bg-red-100 text-red-800 border-l-4 border-red-500'; // Cancelada
        default: return 'bg-gray-100 text-gray-800 border-gray-400';
    }
}
</script>

<template>
    <Head title="Charter" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Header & Filters & Actions -->
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <Ship class="h-5 w-5" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold tracking-tight">Charter</h1>
                            <p class="text-sm text-muted-foreground">Ocupación y disponibilidad semanal</p>
                        </div>
                    </div>

                    <!-- Legend moved to top -->
                    <div class="flex flex-wrap items-center gap-5 text-xs text-muted-foreground font-medium bg-muted/20 pb-2 rounded-md">
                        <div class="flex items-center gap-2">
                            <div class="h-3 w-3 rounded-full bg-blue-500 shadow-sm border border-blue-600/20"></div>
                            <span>Confirmada</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-3 w-3 rounded-full bg-green-500 shadow-sm border border-green-600/20"></div>
                            <span>Check-In</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-3 w-3 rounded-full bg-orange-400 shadow-sm border border-orange-500/20"></div>
                            <span>Check-Out</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-3 w-3 rounded-full bg-red-500 shadow-sm border border-red-600/20"></div>
                            <span>Cancelada</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col items-end gap-3">
                    <Button class="gap-2" @click="openCreateModal">
                        <Plus class="h-4 w-4" />
                        Nueva Reservación
                    </Button>

                    <div class="flex items-center gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-muted-foreground uppercase">Sucursal</label>
                            <select
                                v-model="selectedLocation"
                                class="flex h-10 w-40 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="LP">LA PAZ</option>
                                <option value="UYUNI">UYUNI</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-muted-foreground uppercase">Fecha</label>
                            <div class="flex items-center gap-2">
                                <Input
                                    type="date"
                                    v-model="selectedDate"
                                    class="w-48"
                                />
                                <div class="flex gap-1">
                                    <Button variant="outline" size="icon" @click="previousWeek">
                                        <ChevronLeft class="h-4 w-4" />
                                    </Button>
                                    <Button variant="outline" size="sm" @click="goToToday">Hoy</Button>
                                    <Button variant="outline" size="icon" @click="nextWeek">
                                        <ChevronRight class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Table -->
            <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse table-fixed">
                        <thead>
                            <tr class="bg-muted/40">
                                <th class="w-48 p-3 text-left border-r border-b border-border font-semibold text-muted-foreground">Deptos / Días</th>
                                <th v-for="day in weekDays" :key="day.date"
                                    class="p-2 text-center border-b border-border font-semibold"
                                    :class="{ 'bg-primary/10 text-primary': day.isToday, 'border-r border-border': true }"
                                >
                                    <div class="text-[10px] uppercase font-bold opacity-70">{{ day.dayName }}</div>
                                    <div class="text-lg leading-tight">{{ day.dayNumber }}</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="dept in departments" :key="dept.id" class="border-b border-border/50 hover:bg-muted/30 transition-colors">
                                <td class="p-3 border-r border-border bg-muted/5">
                                    <div class="font-bold text-sm leading-none">{{ dept.code }}</div>
                                    <div class="text-[10px] text-muted-foreground mt-1">{{ dept.location }}</div>
                                </td>

                                <template v-for="day in weekDays" :key="day.date">
                                    <template v-if="!isCellCovered(dept.id, day.date)">
                                        <td v-if="getReservationStart(dept.id, day.date)"
                                            :colspan="getReservationColspan(dept.id, day.date)"
                                            class="p-1 border-r border-border h-24 align-top"
                                        >
                                            <div @click="openEditModal(getReservationStart(dept.id, day.date)!)"
                                                 class="h-full w-full rounded-md p-2 text-xs shadow-sm flex flex-col justify-between overflow-hidden border cursor-pointer hover:ring-2 hover:ring-primary/50 transition-all hover:opacity-90 active:scale-[0.98]"
                                                 :class="getReservationColor(getReservationStart(dept.id, day.date)!.status)">
                                                <div>
                                                    <div class="font-bold truncate leading-tight">
                                                        {{ getReservationStart(dept.id, day.date)?.customer?.firstname }} {{ getReservationStart(dept.id, day.date)?.customer?.lastname }}
                                                    </div>
                                                    <div class="text-[9px] opacity-75 mt-0.5">
                                                        Ref: #{{ getReservationStart(dept.id, day.date)?.id }}
                                                    </div>
                                                </div>
                                                <div class="text-[9px] opacity-90 font-medium">
                                                    {{ getReservationStart(dept.id, day.date)?.check_in.substring(5) }} a {{ getReservationStart(dept.id, day.date)?.check_out.substring(5) }}
                                                </div>
                                            </div>
                                        </td>
                                        <td v-else class="p-1 border-r border-border h-24 transition-colors hover:bg-primary/5"></td>
                                    </template>
                                </template>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Edit Modal -->
        <CharterReservationModal
            v-model:open="isModalOpen"
            :reservation="selectedReservation"
            :departments="departments"
            :employees="employees"
            :products="products"
            :customers="customers"
            :default-location="selectedLocation"
        />
    </AppLayout>
</template>

<style scoped>
table {
    table-layout: fixed;
    border-spacing: 0;
}
</style>
