<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Reservation } from '@/types';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft } from 'lucide-vue-next';

interface Props {
    reservation: Reservation;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reservaciones', href: '/admin/reservations' },
    { title: `Reserva #${String(props.reservation.id).padStart(5, '0')}`, href: '#' },
];

const statusConfig = {
    '1': { label: 'Confirmada', variant: 'default' },
    '2': { label: 'Check In',   variant: 'secondary' },
    '3': { label: 'Check Out',  variant: 'outline' },
    '4': { label: 'Cancelada', variant: 'destructive' },
};

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleString('es-VE', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}
</script>

<template>
    <Head :title="`Reserva #${String(reservation.id).padStart(5, '0')}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 max-w-4xl mx-auto w-full">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/admin/reservations">
                        <Button variant="outline" size="icon">
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <CalendarDays class="h-5 w-5" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold tracking-tight">Detalle de Reservación</h1>
                            <p class="text-sm text-muted-foreground">#{{ String(reservation.id).padStart(5, '0') }}</p>
                        </div>
                    </div>
                </div>
                <Link :href="`/admin/reservations/${reservation.id}/edit`">
                    <Button>Editar Reserva</Button>
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Info Card -->
                <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
                    <div class="p-4 bg-muted/40 border-b border-border">
                        <h2 class="font-semibold">Información General</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div v-if="reservation.customer">
                            <p class="text-xs uppercase text-muted-foreground font-semibold">Huésped</p>
                            <p class="text-sm font-medium">{{ reservation.customer.firstname }} {{ reservation.customer.lastname }}</p>
                            <p class="text-xs text-muted-foreground">{{ reservation.customer.email }}</p>
                        </div>
                        <div v-if="reservation.departament">
                            <p class="text-xs uppercase text-muted-foreground font-semibold">Departamento</p>
                            <p class="text-sm font-medium">{{ reservation.departament.code }} — {{ reservation.departament.location }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-muted-foreground font-semibold">Ubicación</p>
                            <p class="text-sm font-medium">{{ reservation.location }}</p>
                        </div>
                        <div v-if="reservation.employee">
                            <p class="text-xs uppercase text-muted-foreground font-semibold">Atendido por</p>
                            <p class="text-sm font-medium">{{ reservation.employee.name }}</p>
                        </div>
                    </div>
                </div>

                <!-- Dates/Status Card -->
                <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
                    <div class="p-4 bg-muted/40 border-b border-border">
                        <h2 class="font-semibold">Estancia y Estado</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs uppercase text-muted-foreground font-semibold">Check-in</p>
                                <p class="text-sm font-medium">{{ formatDate(reservation.check_in) }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-muted-foreground font-semibold">Check-out</p>
                                <p class="text-sm font-medium">{{ formatDate(reservation.check_out) }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-muted-foreground font-semibold">Estado</p>
                            <p class="text-sm font-medium mt-1">
                                <span :class="{
                                    'px-2 py-0.5 rounded text-xs font-semibold': true,
                                    'bg-green-100 text-green-700': reservation.status === '1',
                                    'bg-blue-100 text-blue-700': reservation.status === '2',
                                    'bg-gray-100 text-gray-700': reservation.status === '3',
                                    'bg-red-100 text-red-700': reservation.status === '4',
                                }">
                                    {{ statusConfig[reservation.status].label }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Costs Card -->
            <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
                <div class="p-4 bg-muted/40 border-b border-border">
                    <h2 class="font-semibold">Resumen Económico</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-2 max-w-md">
                        <div class="flex justify-between">
                            <span class="text-sm text-muted-foreground">Costo de Estadía</span>
                            <span class="text-sm font-medium">Bs. {{ Number(reservation.total_stay_cost).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-muted-foreground">Gastos Extras</span>
                            <span class="text-sm font-medium">Bs. {{ Number(reservation.total_extra_cost).toFixed(2) }}</span>
                        </div>
                        <div class="border-t pt-2 mt-2 flex justify-between">
                            <span class="text-base font-bold text-primary">TOTAL</span>
                            <span class="text-base font-bold text-primary">Bs. {{ (Number(reservation.total_stay_cost) + Number(reservation.total_extra_cost)).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requests Fields -->
            <div v-if="reservation.requests || reservation.comments" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-if="reservation.requests" class="rounded-xl border border-border bg-card p-6 shadow-sm">
                    <h3 class="text-xs uppercase text-muted-foreground font-semibold mb-2">Requerimientos</h3>
                    <p class="text-sm italic text-muted-foreground">"{{ reservation.requests }}"</p>
                </div>
                <div v-if="reservation.comments" class="rounded-xl border border-border bg-card p-6 shadow-sm">
                    <h3 class="text-xs uppercase text-muted-foreground font-semibold mb-2">Comentarios internos</h3>
                    <p class="text-sm text-muted-foreground">{{ reservation.comments }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
