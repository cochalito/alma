<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData, type Product, type ProductCategory } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Package, Pencil, Plus, Trash2 } from 'lucide-vue-next';

interface Props {
    products: PaginatedData<Product>;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Productos Minibar', href: '/admin/products' },
];

const categoryLabels: Record<ProductCategory, string> = {
    beverages: 'Bebidas',
    snacks: 'Snacks',
    toiletries: 'Toiletries',
    other: 'Otros',
};

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'USD' }).format(amount);
}

function deleteProduct(id: number) {
    if (confirm('¿Estás seguro de que deseas eliminar este producto?')) {
        router.delete(`/admin/products/${id}`);
    }
}
</script>

<template>
    <Head title="Productos Minibar" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Package class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Productos Minibar</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ products.total }} productos en total
                        </p>
                    </div>
                </div>
                <Link href="/admin/products/create">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Producto
                    </Button>
                </Link>
            </div>

            <!-- Table Card -->
            <div class="rounded-xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40">
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Nombre</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Categoría</th>
                                <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Precio</th>
                                <th class="px-4 py-3 text-center font-semibold text-muted-foreground">Stock</th>
                                <th class="px-4 py-3 text-center font-semibold text-muted-foreground">Estado</th>
                                <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="products.data.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">
                                    No hay productos registrados aún.
                                </td>
                            </tr>
                            <tr
                                v-for="product in products.data"
                                :key="product.id"
                                class="border-b border-border/50 transition-colors hover:bg-muted/30"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ product.name }}</div>
                                    <div v-if="product.description" class="text-xs text-muted-foreground line-clamp-1">
                                        {{ product.description }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge variant="outline">{{ categoryLabels[product.category] }}</Badge>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold">{{ formatCurrency(product.price) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        :class="[
                                            'inline-flex h-6 min-w-[2rem] items-center justify-center rounded-full px-2 text-xs font-semibold',
                                            product.stock > 5
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                : product.stock > 0
                                                    ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                        ]"
                                    >
                                        {{ product.stock }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Badge :variant="product.is_active ? 'default' : 'secondary'">
                                        {{ product.is_active ? 'Activo' : 'Inactivo' }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link :href="`/admin/products/${product.id}/edit`">
                                            <Button variant="ghost" size="icon" title="Editar">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Eliminar"
                                            class="text-destructive hover:text-destructive"
                                            @click="deleteProduct(product.id)"
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
                <div v-if="products.last_page > 1" class="flex items-center justify-between border-t border-border px-4 py-3">
                    <p class="text-sm text-muted-foreground">
                        Mostrando {{ products.from }}–{{ products.to }} de {{ products.total }}
                    </p>
                    <div class="flex items-center gap-1">
                        <template v-for="link in products.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" preserve-scroll>
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
