<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Reservation } from '@/types';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft } from 'lucide-vue-next';

interface Props {
    reservation: Reservation;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reservaciones', href: '/admin/reservations' },
    { title: 'Detalle', href: '#' },
];
</script>

<template>
    <Head title="Detalle de Reservación" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/admin/reservations">
                    <Button variant="ghost" size="icon">
                        <ChevronLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <CalendarDays class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Detalle de la Reservación</h1>
                    </div>
                </div>
            </div>

            <!-- Card -->
            <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
                <p><strong>Huésped:</strong> {{ reservation.user.name }}</p>
                <p><strong>Habitación:</strong> {{ reservation.room.number }} - {{ reservation.room.type }}</p>
                <p><strong>Check-in:</strong> {{ reservation.check_in }}</p>
                <p><strong>Check-out:</strong> {{ reservation.check_out }}</p>
                <p><strong>Status:</strong> {{ reservation.status }}</p>
                <p><strong>Total Nights:</strong> {{ reservation.total_nights }}</p>
                <p><strong>Total Amount:</strong> {{ reservation.total_amount }}</p>
                <p v-if="reservation.notes"><strong>Notas:</strong> {{ reservation.notes }}</p>
                <div class="mt-4">
                    <Link :href="`/admin/reservations/${reservation.id}/edit`">
                        <Button>Editar</Button>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
