<script setup lang="ts">
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData, type Product, type ProductCategory } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Package, Search, ShoppingCart, X } from 'lucide-vue-next';
import debounce from 'lodash/debounce';

interface ProductWithLocations extends Product {
    locations: { location: string; stock: number }[];
    total_stock: number;
}

interface Props {
    products: PaginatedData<ProductWithLocations>;
    filters?: { search?: string };
    allowedLocation?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    filters: () => ({}),
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Venta Externa', href: '/admin/external-sales' },
];

const categoryLabels: Record<ProductCategory, string> = {
    beverages: 'Bebidas',
    snacks: 'Snacks',
    toiletries: 'Toiletries',
    other: 'Otros',
};

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(amount).replace('BOB', 'Bs.');
}

// Modal State
const isSaleModalOpen = ref(false);
const selectedProduct = ref<ProductWithLocations | null>(null);

const saleForm = useForm({
    product_id: null as number | null,
    location: props.allowedLocation || 'UYUNI',
    quantity: 1,
});

function openSaleModal(product: ProductWithLocations) {
    selectedProduct.value = product;
    saleForm.product_id = product.id;
    saleForm.location = props.allowedLocation || 'UYUNI';
    saleForm.quantity = 1;
    isSaleModalOpen.value = true;
}

function submitSale() {
    saleForm.post('/admin/external-sales', {
        preserveScroll: true,
        onSuccess: () => {
            isSaleModalOpen.value = false;
        },
    });
}

function getStockAt(product: ProductWithLocations, location: string) {
    const loc = product.locations?.find((l: any) => l.location === location);
    return loc ? loc.stock : 0;
}

const search = ref(props.filters?.search ?? '');

const applyFilters = debounce(() => {
    router.get('/admin/external-sales', {
        search: search.value,
    }, { preserveState: true, replace: true });
}, 300);

watch(search, () => {
    applyFilters();
});

function clearFilters() {
    search.value = '';
}
</script>

<template>
    <Head title="Venta Externa" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between font-outfit">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <ShoppingCart class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Venta Externa</h1>
                        <p class="text-sm text-muted-foreground">
                            Venta directa de productos sin reserva
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="rounded-xl border border-border bg-card p-4 shadow-sm flex flex-col sm:flex-row gap-4 items-end">
                <div class="flex-1 min-w-[200px] flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-muted-foreground uppercase">Buscar Producto</label>
                    <div class="relative">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input v-model="search" placeholder="Nombre de producto..." class="pl-9 h-9" />
                    </div>
                </div>
                <Button variant="ghost" size="sm" class="h-9 text-muted-foreground hover:text-foreground" @click="clearFilters" v-if="search">
                    <X class="h-4 w-4 mr-1" />
                    Limpiar
                </Button>
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
                                <th v-if="!allowedLocation || allowedLocation === 'LP'" class="px-4 py-3 text-center font-semibold text-muted-foreground">Stock LP</th>
                                <th v-if="!allowedLocation || allowedLocation === 'UYUNI'" class="px-4 py-3 text-center font-semibold text-muted-foreground">Stock UYUNI</th>
                                <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="products.data.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">
                                    No se encontraron productos.
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

                                <td v-if="!allowedLocation || allowedLocation === 'LP'" class="px-4 py-3 text-center">
                                    <span
                                        :class="[
                                            'inline-flex h-6 min-w-8 items-center justify-center rounded-full px-2 text-xs font-semibold',
                                            getStockAt(product, 'LP') > 5
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                : getStockAt(product, 'LP') > 0
                                                    ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                        ]"
                                    >
                                        {{ getStockAt(product, 'LP') }}
                                    </span>
                                </td>

                                <td v-if="!allowedLocation || allowedLocation === 'UYUNI'" class="px-4 py-3 text-center">
                                    <span
                                        :class="[
                                            'inline-flex h-6 min-w-8 items-center justify-center rounded-full px-2 text-xs font-semibold',
                                            getStockAt(product, 'UYUNI') > 5
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                : getStockAt(product, 'UYUNI') > 0
                                                    ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                        ]"
                                    >
                                        {{ getStockAt(product, 'UYUNI') }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end">
                                        <Button
                                            variant="default"
                                            size="sm"
                                            class="bg-blue-600 hover:bg-blue-700 text-white"
                                            @click="openSaleModal(product)"
                                            :disabled="(allowedLocation ? getStockAt(product, allowedLocation) : Math.max(getStockAt(product, 'LP'), getStockAt(product, 'UYUNI'))) <= 0"
                                        >
                                            <ShoppingCart class="mr-2 h-4 w-4" />
                                            Vender
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
                            <span v-if="link.url === null" />
                            <Link v-else :href="link.url" preserve-scroll>
                                <Button
                                    variant="outline"
                                    class="h-8 items-center justify-center p-0"
                                    :class="[
                                        link.active ? 'bg-primary text-primary-foreground hover:bg-primary/90' : '',
                                        link.label.includes('Previous') || link.label.includes('Next') ? 'px-3' : 'w-8'
                                    ]"
                                >
                                    <template v-if="link.label.includes('Previous')">
                                        <ChevronLeft class="h-4 w-4 mr-1" /> Anterior
                                    </template>
                                    <template v-else-if="link.label.includes('Next')">
                                        Siguiente <ChevronRight class="h-4 w-4 ml-1" />
                                    </template>
                                    <span v-else v-html="link.label"></span>
                                </Button>
                            </Link>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Sale Modal -->
            <div v-if="isSaleModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-card border border-border rounded-xl shadow-lg w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
                    <div class="p-6 border-b border-border flex justify-between items-center bg-muted/20">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                                <ShoppingCart class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-sm text-muted-foreground font-semibold">Realizar Venta Interna / Externa</h3>
                                <p class="text-2xl font-extrabold text-green-600 dark:text-green-500 leading-tight mt-0.5">{{ selectedProduct?.name }}</p>
                            </div>
                        </div>
                        <Button variant="ghost" size="icon" @click="isSaleModalOpen = false">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>

                    <form @submit.prevent="submitSale" class="p-6 space-y-4">
                        <div class="space-y-1.5 font-semibold text-lg text-right mb-4">
                            Precio unitario: {{ selectedProduct ? formatCurrency(selectedProduct.price) : '' }}
                        </div>

                        <div class="space-y-1.5" v-if="!allowedLocation">
                            <label class="text-sm font-medium">Sucursal</label>
                            <select
                                v-model="saleForm.location"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option value="LP" :disabled="selectedProduct ? getStockAt(selectedProduct, 'LP') <= 0 : false">La Paz (Stock: {{ selectedProduct ? getStockAt(selectedProduct, 'LP') : 0 }})</option>
                                <option value="UYUNI" :disabled="selectedProduct ? getStockAt(selectedProduct, 'UYUNI') <= 0 : false">Uyuni (Stock: {{ selectedProduct ? getStockAt(selectedProduct, 'UYUNI') : 0 }})</option>
                            </select>
                            <p v-if="saleForm.errors.location" class="text-xs text-destructive">{{ saleForm.errors.location }}</p>
                        </div>
                        
                        <div class="space-y-1.5" v-else>
                            <label class="text-sm font-medium">Sucursal</label>
                            <div class="h-9 w-full rounded-md border border-input bg-muted px-3 py-1 text-sm shadow-sm flex items-center">
                                {{ allowedLocation === 'LP' ? 'La Paz' : 'Uyuni' }}
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium">Cantidad</label>
                            <Input v-model="saleForm.quantity" type="number" min="1" :max="selectedProduct ? getStockAt(selectedProduct, saleForm.location) : 1" required />
                            <p class="text-xs text-muted-foreground mt-1">
                                Stock disponible: {{ selectedProduct ? getStockAt(selectedProduct, saleForm.location) : 0 }}
                            </p>
                            <p v-if="saleForm.errors.quantity" class="text-xs text-destructive">{{ saleForm.errors.quantity }}</p>
                        </div>

                        <div class="mt-4 pt-4 border-t border-border space-y-1.5 font-bold text-xl text-right">
                            Total: {{ selectedProduct ? formatCurrency(selectedProduct.price * saleForm.quantity) : '' }}
                        </div>

                        <div class="pt-2 flex gap-3">
                            <Button type="button" variant="outline" class="flex-1" @click="isSaleModalOpen = false">Cancelar</Button>
                            <Button type="submit" :disabled="saleForm.processing || (selectedProduct ? getStockAt(selectedProduct, saleForm.location) < saleForm.quantity : false)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white">
                                {{ saleForm.processing ? 'Procesando...' : 'Confirmar Venta' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
