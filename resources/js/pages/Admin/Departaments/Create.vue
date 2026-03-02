<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft, Building, Save } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Departamentos', href: '/admin/departaments' },
    { title: 'Nuevo Departamento', href: '/admin/departaments/create' },
];

const form = useForm({
    code: '',
    location: 'LP',
    cost: undefined as number | undefined,
    status: '1',
});

function submit() {
    form.post('/admin/departaments');
}
</script>

<template>
    <Head title="Nuevo Departamento" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/admin/departaments">
                    <Button variant="ghost" size="icon">
                        <ChevronLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Building class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Nuevo Departamento</h1>
                        <p class="text-sm text-muted-foreground">Registra un nuevo departamento en el sistema</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <div class="grid gap-6 lg:grid-cols-3">

                    <!-- Main Fields -->
                    <div class="lg:col-span-2">
                        <div class="rounded-xl border border-border bg-card p-6 shadow-sm space-y-4">
                            <h2 class="text-base font-semibold">Datos del Departamento</h2>

                            <!-- Código -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium">Código del Departamento *</label>
                                <Input v-model="form.code" placeholder="Ej: 101, H-01" required />
                                <p v-if="form.errors.code" class="text-xs text-destructive">{{ form.errors.code }}</p>
                            </div>

                            <!-- Ubicación -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium">Ubicación *</label>
                                <select
                                    v-model="form.location"
                                    class="file:text-foreground placeholder:text-muted-foreground dark:bg-input/30 border-input h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                    required
                                >
                                    <option value="LP">La Paz (LP)</option>
                                    <option value="UYUNI">Uyuni (UYUNI)</option>
                                </select>
                                <p v-if="form.errors.location" class="text-xs text-destructive">{{ form.errors.location }}</p>
                            </div>

                            <!-- Costo Sugerido -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium">Costo por Noche Sugerido (Bs.)</label>
                                <Input v-model="form.cost" type="number" step="0.01" min="0" placeholder="Ej: 80.00" />
                                <p class="text-xs text-muted-foreground">Dejar en blanco si no tiene un costo fijo asignado.</p>
                                <p v-if="form.errors.cost" class="text-xs text-destructive">{{ form.errors.cost }}</p>
                            </div>

                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-4">
                        <!-- Estado -->
                        <div class="rounded-xl border border-border bg-card p-6 shadow-sm space-y-4">
                            <h2 class="text-base font-semibold">Estado del Departamento</h2>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium">Estado *</label>
                                <select
                                    v-model="form.status"
                                    class="file:text-foreground placeholder:text-muted-foreground dark:bg-input/30 border-input h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                    required
                                >
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                                <p v-if="form.errors.status" class="text-xs text-destructive">{{ form.errors.status }}</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
                            <h2 class="mb-4 text-base font-semibold">Acciones</h2>
                            <div class="flex flex-col gap-2">
                                <Button type="submit" :disabled="form.processing" class="w-full">
                                    <Save class="mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Guardando...' : 'Crear Departamento' }}
                                </Button>
                                <Link href="/admin/departaments" class="w-full">
                                    <Button variant="outline" class="w-full">Cancelar</Button>
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </AppLayout>
</template>
