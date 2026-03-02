<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Product } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft, Package, Save } from 'lucide-vue-next';

interface Props {
    product: Product & { locations?: { location: string; stock: number }[] };
    locations: string[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Productos Minibar', href: '/admin/products' },
    { title: `Editar: ${props.product.name}`, href: '#' },
];

const initialFormState: Record<string, any> = {
    name: props.product.name,
    description: props.product.description ?? '',
    category: props.product.category,
    price: String(props.product.price),
    is_active: props.product.is_active,
};

props.locations.forEach(loc => {
    const locData = props.product.locations?.find((l: any) => l.location === loc);
    initialFormState['stock_' + loc] = locData ? locData.stock : 0;
});

const form = useForm(initialFormState);

function submit() {
    form.put(`/admin/products/${props.product.id}`);
}
</script>

<template>
    <Head :title="`Editar: ${product.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/admin/products">
                    <Button variant="ghost" size="icon">
                        <ChevronLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Package class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Editar Producto</h1>
                        <p class="text-sm text-muted-foreground">{{ product.name }}</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <div class="grid gap-6 lg:grid-cols-3">

                    <!-- Main Fields -->
                    <div class="lg:col-span-2">
                        <div class="rounded-xl border border-border bg-card p-6 shadow-sm space-y-4">
                            <h2 class="text-base font-semibold">Información del Producto</h2>

                            <!-- Nombre -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium">Nombre *</label>
                                <Input v-model="form.name" placeholder="Ej: Agua mineral 500ml" required />
                                <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>

                            <!-- Descripción -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium">Descripción</label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Descripción del producto..."
                                    class="placeholder:text-muted-foreground dark:bg-input/30 border-input w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] resize-none"
                                />
                                <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                            </div>

                            <!-- Categoría + Precio -->
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium">Categoría *</label>
                                    <select
                                        v-model="form.category"
                                        class="file:text-foreground placeholder:text-muted-foreground dark:bg-input/30 border-input h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                        required
                                    >
                                        <option value="beverages">Bebidas</option>
                                        <option value="snacks">Snacks</option>
                                        <option value="toiletries">Toiletries</option>
                                        <option value="other">Otros</option>
                                    </select>
                                    <p v-if="form.errors.category" class="text-xs text-destructive">{{ form.errors.category }}</p>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium">Precio (USD) *</label>
                                    <Input
                                        v-model="form.price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        required
                                    />
                                    <p v-if="form.errors.price" class="text-xs text-destructive">{{ form.errors.price }}</p>
                                </div>
                            </div>

                            <!-- Stock -->
                            <div class="space-y-4 pt-2">
                                <h3 class="text-sm font-semibold">Stock por Sucursal *</h3>
                                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    <div v-for="loc in locations" :key="loc" class="space-y-1.5">
                                        <label class="text-xs font-medium">{{ loc }}</label>
                                        <Input
                                            v-model="form['stock_' + loc]"
                                            type="number"
                                            min="0"
                                            placeholder="0"
                                            required
                                        />
                                        <p v-if="form.errors['stock_' + loc]" class="text-xs text-destructive">{{ form.errors['stock_' + loc] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-4">
                        <!-- Estado -->
                        <div class="rounded-xl border border-border bg-card p-6 shadow-sm space-y-4">
                            <h2 class="text-base font-semibold">Publicación</h2>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" class="sr-only" v-model="form.is_active" />
                                    <div
                                        class="h-5 w-9 rounded-full transition-colors"
                                        :class="form.is_active ? 'bg-primary' : 'bg-muted'"
                                    />
                                    <div
                                        class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                                        :class="{ 'translate-x-4': form.is_active }"
                                    />
                                </div>
                                <span class="text-sm font-medium">
                                    {{ form.is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </label>
                            <p class="text-xs text-muted-foreground">
                                Los productos inactivos no aparecen en el listado de reservas.
                            </p>
                        </div>

                        <!-- Info -->
                        <div class="rounded-xl border border-border bg-muted/30 p-4 text-sm space-y-2">
                            <p class="font-medium text-muted-foreground">Metadatos</p>
                            <div class="flex justify-between text-xs">
                                <span class="text-muted-foreground">ID</span>
                                <span class="font-mono font-medium">{{ product.id }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-muted-foreground">Creado</span>
                                <span>{{ new Date(product.created_at).toLocaleDateString('es-VE') }}</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
                            <h2 class="mb-4 text-base font-semibold">Acciones</h2>
                            <div class="flex flex-col gap-2">
                                <Button type="submit" :disabled="form.processing" class="w-full">
                                    <Save class="mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </Button>
                                <Link href="/admin/products" class="w-full">
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
