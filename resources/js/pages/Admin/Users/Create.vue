<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft, Users, Save } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Usuarios', href: '/admin/users' },
    { title: 'Nuevo Usuario', href: '/admin/users/create' },
];

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'ADMINISTRADOR',
    status: '1',
});

function submit() {
    form.post('/admin/users');
}
</script>

<template>
    <Head title="Nuevo Usuario" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/admin/users">
                    <Button variant="ghost" size="icon">
                        <ChevronLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Users class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Nuevo Usuario</h1>
                        <p class="text-sm text-muted-foreground">Registra un nuevo usuario en el sistema</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <div class="grid gap-6 lg:grid-cols-3">

                    <!-- Main Fields -->
                    <div class="lg:col-span-2">
                        <div class="rounded-xl border border-border bg-card p-6 shadow-sm space-y-4">
                            <h2 class="text-base font-semibold">Información del Usuario</h2>

                            <!-- Nombre -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium">Nombre Completo *</label>
                                <Input v-model="form.name" placeholder="Ej: Juan Pérez" required />
                                <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>

                            <!-- Email -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium">Correo Electrónico *</label>
                                <Input v-model="form.email" type="email" placeholder="correo@ejemplo.com" required />
                                <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                            </div>

                            <!-- Contraseña -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium">Contraseña *</label>
                                <Input v-model="form.password" type="password" placeholder="Tu contraseña" required minlength="8" />
                                <p class="text-xs text-muted-foreground">Mínimo 8 caracteres.</p>
                                <p v-if="form.errors.password" class="text-xs text-destructive">{{ form.errors.password }}</p>
                            </div>

                            <!-- Rol -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium">Rol *</label>
                                <select
                                    v-model="form.role"
                                    class="file:text-foreground placeholder:text-muted-foreground dark:bg-input/30 border-input h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                    required
                                >
                                    <option value="ADMINISTRADOR">Administrador</option>
                                    <option value="OPERADOR_LA_PAZ">Operador La Paz</option>
                                    <option value="OPERADOR_UYUNI">Operador Uyuni</option>
                                </select>
                                <p v-if="form.errors.role" class="text-xs text-destructive">{{ form.errors.role }}</p>
                            </div>

                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-4">
                        <!-- Estado -->
                        <div class="rounded-xl border border-border bg-card p-6 shadow-sm space-y-4">
                            <h2 class="text-base font-semibold">Estado del Usuario</h2>
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
                                    {{ form.processing ? 'Guardando...' : 'Crear Usuario' }}
                                </Button>
                                <Link href="/admin/users" class="w-full">
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
