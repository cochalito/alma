<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { UserPlus, ArrowLeft } from 'lucide-vue-next';
// @ts-ignore – VueTelInput is a valid named export in the ESM bundle
import { VueTelInput } from 'vue-tel-input';
import 'vue-tel-input/vue-tel-input.css';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Huéspedes', href: '/admin/customers' },
    { title: 'Nuevo Huésped', href: '/admin/customers/create' },
];

const form = useForm({
    document_type: '1',
    document_number: '',
    firstname: '',
    lastname: '',
    email: '',
    cellphone: '',
    status: '1',
});

function submit() {
    form.post('/admin/customers');
}
</script>

<template>
    <Head title="Nuevo Huésped" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 max-w-2xl mx-auto w-full">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/admin/customers">
                    <Button variant="outline" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <UserPlus class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Nuevo Huésped</h1>
                        <p class="text-sm text-muted-foreground">Registra un nuevo huésped en el sistema</p>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <form @submit.prevent="submit" class="rounded-xl border border-border bg-card p-6 shadow-sm flex flex-col gap-5">

                <!-- Nombres -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Nombre(s) <span class="text-destructive">*</span></label>
                        <input
                            v-model="form.firstname"
                            type="text"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            placeholder="Juan"
                            required
                        />
                        <p v-if="form.errors.firstname" class="text-xs text-destructive">{{ form.errors.firstname }}</p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Apellido(s) <span class="text-destructive">*</span></label>
                        <input
                            v-model="form.lastname"
                            type="text"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            placeholder="Pérez"
                            required
                        />
                        <p v-if="form.errors.lastname" class="text-xs text-destructive">{{ form.errors.lastname }}</p>
                    </div>
                </div>

                <!-- Documento -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Tipo de Documento</label>
                        <select
                            v-model="form.document_type"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="1">CI – Cédula de Identidad</option>
                            <option value="2">DNI – Doc. Nac. de Identidad</option>
                            <option value="3">Pasaporte</option>
                            <option value="4">Otro</option>
                        </select>
                        <p v-if="form.errors.document_type" class="text-xs text-destructive">{{ form.errors.document_type }}</p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Número de Documento</label>
                        <input
                            v-model="form.document_number"
                            type="text"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            placeholder="12345678"
                        />
                        <p v-if="form.errors.document_number" class="text-xs text-destructive">{{ form.errors.document_number }}</p>
                    </div>
                </div>

                <!-- Contacto -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Correo Electrónico <span class="text-destructive">*</span></label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            placeholder="correo@ejemplo.com"
                            required
                        />
                        <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Celular</label>
                        <VueTelInput
                            v-model="form.cellphone"
                            :preferred-countries="['BO', 'AR', 'BR', 'CL', 'PE', 'CO', 'MX', 'ES', 'US']"
                            default-country="BO"
                            mode="international"
                            :input-options="{ placeholder: '70012345' }"
                            class="vti-custom"
                        />
                        <p v-if="form.errors.cellphone" class="text-xs text-destructive">{{ form.errors.cellphone }}</p>
                    </div>
                </div>

                <!-- Estado -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Estado <span class="text-destructive">*</span></label>
                    <select
                        v-model="form.status"
                        class="rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                    >
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                    <p v-if="form.errors.status" class="text-xs text-destructive">{{ form.errors.status }}</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-2 border-t border-border">
                    <Link href="/admin/customers">
                        <Button type="button" variant="outline">Cancelar</Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Guardando...' : 'Guardar Huésped' }}
                    </Button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
