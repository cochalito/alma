<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Reservation, type Room, type User } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft, Save } from 'lucide-vue-next';

interface Props {
    reservation: Reservation;
    rooms: Room[];
    users: User[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reservaciones', href: '/admin/reservations' },
    { title: `Editar #${String(props.reservation.id).padStart(5, '0')}`, href: '#' },
];

const form = useForm({
    user_id: String(props.reservation.user.id),
    room_id: String(props.reservation.room.id),
    check_in: props.reservation.check_in,
    check_out: props.reservation.check_out,
    status: props.reservation.status,
    notes: props.reservation.notes ?? '',
});

function submit() {
    form.put(`/admin/reservations/${props.reservation.id}`);
}
</script>

<template>
    <Head :title="`Editar Reservación #${String(reservation.id).padStart(5, '0')}`" />
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
                        <h1 class="text-2xl font-bold tracking-tight">
                            Editar Reservación <span class="font-mono text-primary">#{{ String(reservation.id).padStart(5, '0') }}</span>
                        </h1>
                        <p class="text-sm text-muted-foreground">Modifica los datos de la reserva</p>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <form @submit.prevent="submit">
                <div class="grid gap-6 lg:grid-cols-3">

                    <!-- Main Fields -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
                            <h2 class="mb-4 text-base font-semibold">Información de la Reserva</h2>
                            <div class="grid gap-4 sm:grid-cols-2">

                                <!-- Huésped -->
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium">Huésped *</label>
                                    <select
                                        v-model="form.user_id"
                                        class="file:text-foreground placeholder:text-muted-foreground dark:bg-input/30 border-input h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                        required
                                    >
                                        <option value="" disabled>Seleccionar huésped...</option>
                                        <option v-for="user in users" :key="user.id" :value="String(user.id)">
                                            {{ user.name }} — {{ user.email }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.user_id" class="text-xs text-destructive">{{ form.errors.user_id }}</p>
                                </div>

                                <!-- Habitación -->
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium">Habitación *</label>
                                    <select
                                        v-model="form.room_id"
                                        class="file:text-foreground placeholder:text-muted-foreground dark:bg-input/30 border-input h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                        required
                                    >
                                        <option value="" disabled>Seleccionar habitación...</option>
                                        <option v-for="room in rooms" :key="room.id" :value="String(room.id)">
                                            Hab. {{ room.number }} — {{ room.type }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.room_id" class="text-xs text-destructive">{{ form.errors.room_id }}</p>
                                </div>

                                <!-- Check-in -->
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium">Check-in *</label>
                                    <Input v-model="form.check_in" type="date" required />
                                    <p v-if="form.errors.check_in" class="text-xs text-destructive">{{ form.errors.check_in }}</p>
                                </div>

                                <!-- Check-out -->
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium">Check-out *</label>
                                    <Input v-model="form.check_out" type="date" required />
                                    <p v-if="form.errors.check_out" class="text-xs text-destructive">{{ form.errors.check_out }}</p>
                                </div>

                                <!-- Estado -->
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium">Estado</label>
                                    <select
                                        v-model="form.status"
                                        class="file:text-foreground placeholder:text-muted-foreground dark:bg-input/30 border-input h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                    >
                                        <option value="pending">Pendiente</option>
                                        <option value="active">Activa</option>
                                        <option value="completed">Completada</option>
                                        <option value="cancelled">Cancelada</option>
                                    </select>
                                    <p v-if="form.errors.status" class="text-xs text-destructive">{{ form.errors.status }}</p>
                                </div>

                            </div>

                            <!-- Notas -->
                            <div class="mt-4 space-y-1.5">
                                <label class="text-sm font-medium">Notas adicionales</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="3"
                                    placeholder="Observaciones, requerimientos especiales..."
                                    class="file:text-foreground placeholder:text-muted-foreground dark:bg-input/30 border-input w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] resize-none"
                                />
                            </div>
                        </div>

                        <!-- Productos de la Reserva (solo lectura) -->
                        <div v-if="reservation.products.length > 0" class="rounded-xl border border-border bg-card p-6 shadow-sm">
                            <h2 class="mb-4 text-base font-semibold">Productos Minibar Consumidos</h2>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-border/50">
                                        <th class="pb-2 text-left text-muted-foreground font-medium">Producto</th>
                                        <th class="pb-2 text-center text-muted-foreground font-medium">Cantidad</th>
                                        <th class="pb-2 text-right text-muted-foreground font-medium">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in reservation.products" :key="item.id" class="border-b border-border/30">
                                        <td class="py-2">{{ item.product.name }}</td>
                                        <td class="py-2 text-center">{{ item.quantity }}</td>
                                        <td class="py-2 text-right font-medium">${{ item.subtotal.toFixed(2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Sidebar Actions -->
                    <div class="space-y-4">
                        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
                            <h2 class="mb-4 text-base font-semibold">Acciones</h2>
                            <div class="flex flex-col gap-2">
                                <Button type="submit" :disabled="form.processing" class="w-full">
                                    <Save class="mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </Button>
                                <Link href="/admin/reservations" class="w-full">
                                    <Button variant="outline" class="w-full">Cancelar</Button>
                                </Link>
                            </div>
                        </div>

                        <!-- Info Card -->
                        <div class="rounded-xl border border-border bg-muted/30 p-4 text-sm space-y-2">
                            <p class="font-medium text-muted-foreground">Resumen</p>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Noches</span>
                                <span class="font-semibold">{{ reservation.total_nights }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Total</span>
                                <span class="font-semibold">${{ reservation.total_amount.toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </AppLayout>
</template>
