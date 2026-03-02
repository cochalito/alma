<script setup lang="ts">
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Building, Pencil, Plus, Search, ArrowUpDown, ArrowUp, ArrowDown, X } from 'lucide-vue-next';
import debounce from 'lodash/debounce';

interface Departament {
    id: number;
    code: string;
    location: string;
    cost: number | null;
    status: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    departaments: PaginatedData<Departament>;
    filters?: { search?: string; sort?: string; direction?: string };
}

const props = withDefaults(defineProps<Props>(), {
    filters: () => ({}),
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Departamentos', href: '/admin/departaments' },
];

const search = ref(props.filters?.search ?? '');
const sortParams = ref({
    sort: props.filters?.sort ?? 'updated_at',
    direction: props.filters?.direction ?? 'desc'
});

const applyFilters = debounce(() => {
    router.get('/admin/departaments', {
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
    <Head title="Departamentos" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Building class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Departamentos</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ departaments.total }} departamentos en total
                        </p>
                    </div>
                </div>
                <Link href="/admin/departaments/create">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Departamento
                    </Button>
                </Link>
            </div>

            <!-- Filtros -->
            <div class="rounded-xl border border-border bg-card p-4 shadow-sm flex flex-col sm:flex-row gap-4 items-end">
                <div class="flex-1 min-w-[200px] flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-muted-foreground uppercase">Buscar Departamento</label>
                    <div class="relative">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input v-model="search" placeholder="Buscar por Código o Ubicación..." class="pl-9 h-9" />
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
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground cursor-pointer hover:bg-muted/60 transition-colors" @click="handleSort('code')">
                                    <div class="flex items-center gap-1">Código <ArrowUp v-if="sortParams.sort==='code' && sortParams.direction==='asc'" class="h-3 w-3"/><ArrowDown v-else-if="sortParams.sort==='code' && sortParams.direction==='desc'" class="h-3 w-3"/><ArrowUpDown v-else class="h-3 w-3 opacity-30"/></div>
                                </th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground cursor-pointer hover:bg-muted/60 transition-colors" @click="handleSort('location')">
                                    <div class="flex items-center gap-1">Ubicación <ArrowUp v-if="sortParams.sort==='location' && sortParams.direction==='asc'" class="h-3 w-3"/><ArrowDown v-else-if="sortParams.sort==='location' && sortParams.direction==='desc'" class="h-3 w-3"/><ArrowUpDown v-else class="h-3 w-3 opacity-30"/></div>
                                </th>
                                <th class="px-4 py-3 text-right font-semibold text-muted-foreground cursor-pointer hover:bg-muted/60 transition-colors" @click="handleSort('cost')">
                                    <div class="flex items-center gap-1 justify-end">Costo <ArrowUp v-if="sortParams.sort==='cost' && sortParams.direction==='asc'" class="h-3 w-3"/><ArrowDown v-else-if="sortParams.sort==='cost' && sortParams.direction==='desc'" class="h-3 w-3"/><ArrowUpDown v-else class="h-3 w-3 opacity-30"/></div>
                                </th>
                                <th class="px-4 py-3 text-center font-semibold text-muted-foreground cursor-pointer hover:bg-muted/60 transition-colors" @click="handleSort('status')">
                                    <div class="flex items-center gap-1 justify-center">Estado <ArrowUp v-if="sortParams.sort==='status' && sortParams.direction==='asc'" class="h-3 w-3"/><ArrowDown v-else-if="sortParams.sort==='status' && sortParams.direction==='desc'" class="h-3 w-3"/><ArrowUpDown v-else class="h-3 w-3 opacity-30"/></div>
                                </th>
                                <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="departaments.data.length === 0">
                                <td colspan="4" class="px-4 py-12 text-center text-muted-foreground">
                                    No hay departamentos registrados aún.
                                </td>
                            </tr>
                            <tr
                                v-for="departament in departaments.data"
                                :key="departament.id"
                                class="border-b border-border/50 transition-colors hover:bg-muted/30"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium font-mono text-base">{{ departament.code }}</div>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground font-medium">
                                    {{ departament.location }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium">
                                    <span v-if="departament.cost" class="text-green-600 dark:text-green-400">Bs. {{ departament.cost }}</span>
                                    <span v-else class="text-muted-foreground text-xs italic">No definido</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Badge :variant="departament.status === '1' ? 'default' : 'secondary'">
                                        {{ departament.status === '1' ? 'Activo' : 'Inactivo' }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link :href="`/admin/departaments/${departament.id}/edit`">
                                            <Button variant="ghost" size="icon" title="Editar">
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
                <div v-if="departaments.last_page > 1" class="flex items-center justify-between border-t border-border px-4 py-3">
                    <p class="text-sm text-muted-foreground">
                        Mostrando {{ departaments.from }}–{{ departaments.to }} de {{ departaments.total }}
                    </p>
                    <div class="flex items-center gap-1">
                        <template v-for="link in departaments.links" :key="link.label">
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

        </div>
    </AppLayout>
</template>
