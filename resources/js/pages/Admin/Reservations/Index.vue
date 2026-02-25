<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData, type Reservation, type ReservationStatus } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft, ChevronRight, Eye, Pencil, Plus, Trash2 } from 'lucide-vue-next';

interface Props {
    reservations: PaginatedData<Reservation>;
    filters?: { search?: string; status?: string };
}

const props = withDefaults(defineProps<Props>(), {
    filters: () => ({}),
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reservaciones', href: '/admin/reservations' },
];

const statusConfig: Record<ReservationStatus, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
    pending:   { label: 'Pendiente',   variant: 'secondary' },
    active:    { label: 'Activa',      variant: 'default' },
    completed: { label: 'Completada', variant: 'outline' },
    cancelled: { label: 'Cancelada', variant: 'destructive' },
};

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('es-VE', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'USD' }).format(amount);
}

function deleteReservation(id: number) {
    if (confirm('¿Estás seguro de que deseas eliminar esta reservación?')) {
        router.delete(`/admin/reservations/${id}`);
    }
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
                <Link href="/admin/reservations/create">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nueva Reservación
                    </Button>
                </Link>
            </div>

            <!-- Table Card -->
            <div class="rounded-xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40">
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground"># Reserva</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Huésped</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Habitación</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Check-in</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Check-out</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Noches</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Total</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Estado</th>
                                <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="reservations.data.length === 0">
                                <td colspan="9" class="px-4 py-12 text-center text-muted-foreground">
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
                                    <div class="font-medium">{{ reservation.user.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ reservation.user.email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">Hab. {{ reservation.room.number }}</div>
                                    <div class="text-xs text-muted-foreground">{{ reservation.room.type }}</div>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(reservation.check_in) }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(reservation.check_out) }}</td>
                                <td class="px-4 py-3 text-center">{{ reservation.total_nights }}</td>
                                <td class="px-4 py-3 font-semibold">{{ formatCurrency(reservation.total_amount) }}</td>
                                <td class="px-4 py-3">
                                    <Badge :variant="statusConfig[reservation.status].variant">
                                        {{ statusConfig[reservation.status].label }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link :href="`/admin/reservations/${reservation.id}`">
                                            <Button variant="ghost" size="icon" title="Ver detalle">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Link :href="`/admin/reservations/${reservation.id}/edit`">
                                            <Button variant="ghost" size="icon" title="Editar">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </Link>
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
                                    size="icon"
                                    class="h-8 w-8"
                                    :class="{ 'bg-primary text-primary-foreground hover:bg-primary/90': link.active }"
                                >
                                    <ChevronLeft v-if="link.label.includes('&laquo;')" class="h-4 w-4" />
                                    <ChevronRight v-else-if="link.label.includes('&raquo;')" class="h-4 w-4" />
                                    <span v-else>{{ link.label }}</span>
                                </Button>
                            </Link>
                        </template>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
