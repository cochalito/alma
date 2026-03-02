<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData, type Product, type ProductCategory } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Package, Pencil, Plus, Search, ArrowUpDown, ArrowUp, ArrowDown, X, TrendingUp, TrendingDown } from 'lucide-vue-next';
import debounce from 'lodash/debounce';

interface ProductWithLocations extends Product {
    locations: { location: string; stock: number }[];
    total_stock: number;
}

interface Props {
    products: PaginatedData<ProductWithLocations>;
    filters?: { search?: string; sort?: string; direction?: string };
    locations: string[];
}

const props = withDefaults(defineProps<Props>(), {
    filters: () => ({}),
});

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
    return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(amount).replace('BOB', 'Bs.');
}

// Modal State
const isAdjustmentModalOpen = ref(false);
const adjustmentType = ref<'in' | 'out'>('in');
const selectedProductForAdjustment = ref<ProductWithLocations | null>(null);

const adjustmentForm = useForm({
    product_id: null as number | null,
    location: '',
    type: 'in' as 'in' | 'out',
    quantity: 1,
    description: '',
});

function openAdjustmentModal(product: ProductWithLocations, type: 'in' | 'out') {
    selectedProductForAdjustment.value = product;
    adjustmentType.value = type;
    adjustmentForm.product_id = product.id;
    adjustmentForm.type = type;
    adjustmentForm.location = props.locations[0] || '';
    adjustmentForm.quantity = 1;
    adjustmentForm.description = '';
    isAdjustmentModalOpen.value = true;
}

function submitAdjustment() {
    adjustmentForm.post('/admin/products/stock-adjustment', {
        onSuccess: () => {
            isAdjustmentModalOpen.value = false;
        },
    });
}

function getStockAt(product: ProductWithLocations, location: string) {
    const loc = product.locations?.find(l => l.location === location);
    return loc ? loc.stock : 0;
}

const search = ref(props.filters?.search ?? '');
const sortParams = ref({
    sort: props.filters?.sort ?? 'updated_at',
    direction: props.filters?.direction ?? 'desc'
});

const applyFilters = debounce(() => {
    router.get('/admin/products', {
        search: search.value,
        sort: sortParams.value.sort,
        direction: sortParams.value.direction
    }, { preserveState: true, replace: true });
}, 300);

watch(search, () => {
    applyFilters();
});

function handleSort(column: string) {
    if (sortParams.value.sort === column) {
        sortParams.value.direction = sortParams.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sortParams.value.sort = column;
        sortParams.value.direction = 'asc';
    }
    applyFilters();
}

function clearFilters() {
    search.value = '';
}
</script>

<template>
    <Head title="Productos Minibar" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between font-outfit">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Package class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Productos Minibar</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ products.total }} productos registrados
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

            <!-- Filtros -->
            <div class="rounded-xl border border-border bg-card p-4 shadow-sm flex flex-col sm:flex-row gap-4 items-end">
                <div class="flex-1 min-w-[200px] flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-muted-foreground uppercase">Buscar Producto</label>
                    <div class="relative">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input v-model="search" placeholder="Nombre, descripción o categoría..." class="pl-9 h-9" />
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
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground cursor-pointer hover:bg-muted/60 transition-colors" @click="handleSort('name')">
                                    <div class="flex items-center gap-1">Nombre <ArrowUp v-if="sortParams.sort==='name' && sortParams.direction==='asc'" class="h-3 w-3"/><ArrowDown v-else-if="sortParams.sort==='name' && sortParams.direction==='desc'" class="h-3 w-3"/><ArrowUpDown v-else class="h-3 w-3 opacity-30"/></div>
                                </th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground cursor-pointer hover:bg-muted/60 transition-colors" @click="handleSort('category')">
                                    <div class="flex items-center gap-1">Categoría <ArrowUp v-if="sortParams.sort==='category' && sortParams.direction==='asc'" class="h-3 w-3"/><ArrowDown v-else-if="sortParams.sort==='category' && sortParams.direction==='desc'" class="h-3 w-3"/><ArrowUpDown v-else class="h-3 w-3 opacity-30"/></div>
                                </th>
                                <th class="px-4 py-3 text-right font-semibold text-muted-foreground cursor-pointer hover:bg-muted/60 transition-colors" @click="handleSort('price')">
                                    <div class="flex items-center gap-1 justify-end">Precio <ArrowUp v-if="sortParams.sort==='price' && sortParams.direction==='asc'" class="h-3 w-3"/><ArrowDown v-else-if="sortParams.sort==='price' && sortParams.direction==='desc'" class="h-3 w-3"/><ArrowUpDown v-else class="h-3 w-3 opacity-30"/></div>
                                </th>
                                <!-- Dynamic Location Columns -->
                                <th v-for="loc in locations" :key="loc" class="px-4 py-3 text-center font-semibold text-muted-foreground">
                                    Stock {{ loc }}
                                </th>
                                <th class="px-4 py-3 text-center font-semibold text-muted-foreground cursor-pointer hover:bg-muted/60 transition-colors" @click="handleSort('is_active')">
                                    <div class="flex items-center gap-1 justify-center">Estado <ArrowUp v-if="sortParams.sort==='is_active' && sortParams.direction==='asc'" class="h-3 w-3"/><ArrowDown v-else-if="sortParams.sort==='is_active' && sortParams.direction==='desc'" class="h-3 w-3"/><ArrowUpDown v-else class="h-3 w-3 opacity-30"/></div>
                                </th>
                                <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="products.data.length === 0">
                                <td :colspan="5 + locations.length" class="px-4 py-12 text-center text-muted-foreground">
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

                                <!-- Stock per Location -->
                                <td v-for="loc in locations" :key="loc" class="px-4 py-3 text-center">
                                    <span
                                        :class="[
                                            'inline-flex h-6 min-w-8 items-center justify-center rounded-full px-2 text-xs font-semibold',
                                            getStockAt(product, loc) > 5
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                : getStockAt(product, loc) > 0
                                                    ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                        ]"
                                    >
                                        {{ getStockAt(product, loc) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <Badge :variant="product.is_active ? 'default' : 'secondary'">
                                        {{ product.is_active ? 'Activo' : 'Inactivo' }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="text-green-600 hover:text-green-700 hover:bg-green-50"
                                            title="Alta de Stock"
                                            @click="openAdjustmentModal(product, 'in')"
                                        >
                                            <TrendingUp class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="text-red-600 hover:text-red-700 hover:bg-red-50"
                                            title="Baja de Stock"
                                            @click="openAdjustmentModal(product, 'out')"
                                        >
                                            <TrendingDown class="h-4 w-4" />
                                        </Button>
                                        <Link :href="`/admin/products/${product.id}/edit`">
                                            <Button variant="ghost" size="icon" title="Editar Información">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </Link>
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

            <!-- Adjustment Modal -->
            <div v-if="isAdjustmentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-card border border-border rounded-xl shadow-lg w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
                    <div class="p-6 border-b border-border flex justify-between items-center bg-muted/20">
                        <div class="flex items-center gap-3">
                            <div :class="[
                                'flex h-10 w-10 items-center justify-center rounded-lg',
                                adjustmentType === 'in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                            ]">
                                <TrendingUp v-if="adjustmentType === 'in'" class="h-5 w-5" />
                                <TrendingDown v-else class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">{{ adjustmentType === 'in' ? 'Alta de Stock' : 'Baja de Stock' }}</h3>
                                <p class="text-xs text-muted-foreground">{{ selectedProductForAdjustment?.name }}</p>
                            </div>
                        </div>
                        <Button variant="ghost" size="icon" @click="isAdjustmentModalOpen = false">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>

                    <form @submit.prevent="submitAdjustment" class="p-6 space-y-4">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium">Sucursal</label>
                            <select
                                v-model="adjustmentForm.location"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option v-for="loc in locations" :key="loc" :value="loc">{{ loc }}</option>
                            </select>
                            <p v-if="adjustmentForm.errors.location" class="text-xs text-destructive">{{ adjustmentForm.errors.location }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium">Cantidad</label>
                            <Input v-model="adjustmentForm.quantity" type="number" min="1" required />
                            <p v-if="adjustmentForm.errors.quantity" class="text-xs text-destructive">{{ adjustmentForm.errors.quantity }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium">Descripción / Motivo {{ adjustmentType === 'out' ? '*' : '' }}</label>
                            <textarea
                                v-model="adjustmentForm.description"
                                rows="3"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                                :placeholder="adjustmentType === 'in' ? 'Opcional...' : 'Motivo de la baja (Requerido)...'"
                                :required="adjustmentType === 'out'"
                            ></textarea>
                            <p v-if="adjustmentForm.errors.description" class="text-xs text-destructive">{{ adjustmentForm.errors.description }}</p>
                        </div>

                        <div class="pt-2 flex gap-3">
                            <Button type="button" variant="outline" class="flex-1" @click="isAdjustmentModalOpen = false">Cancelar</Button>
                            <Button type="submit" :disabled="adjustmentForm.processing" :class="['flex-1', adjustmentType === 'in' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700']">
                                {{ adjustmentForm.processing ? 'Registrando...' : 'Confirmar' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
