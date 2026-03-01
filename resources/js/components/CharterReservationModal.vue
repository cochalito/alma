<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { type Reservation, type Departament, type User, type Product, type Customer } from '@/types';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Plus, Trash2 } from 'lucide-vue-next';

interface Props {
    open: boolean;
    reservation: Reservation | null;
    departments: Departament[];
    employees: User[];
    products: Product[];
    customers: Customer[];
    defaultLocation: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const formatForInput = (dateStr: string) => {
    if (!dateStr) return '';
    return dateStr.replace(' ', 'T').slice(0, 16);
};

const form = useForm({
    employee_id: null as number | null,
    departament_id: null as number | null,
    customer_id: null as number | null,
    location: '',
    check_in: '',
    check_out: '',
    total_stay_cost: 0,
    total_extra_cost: 0,
    requests: '',
    comments: '',
    status: '1',
    products: [] as Array<{
        product_id: number;
        name: string;
        quantity: number;
        unit_price: number;
        subtotal: number;
    }>,
});

const selectedProduct = ref<number | ''>('');
const selectedQuantity = ref(1);

function addProduct() {
    if (!selectedProduct.value) return;

    const product = props.products.find(p => p.id === selectedProduct.value);
    if (!product) return;

    // Check if already added
    const existing = form.products.find(p => p.product_id === product.id);
    if (existing) {
        existing.quantity += selectedQuantity.value;
        existing.subtotal = existing.quantity * existing.unit_price;
    } else {
        form.products.push({
            product_id: product.id,
            name: product.name,
            quantity: selectedQuantity.value,
            unit_price: product.price,
            subtotal: selectedQuantity.value * product.price,
        });
    }

    // Reset selection
    selectedProduct.value = '';
    selectedQuantity.value = 1;

    recalculateExtraCost();
}

function removeProduct(index: number) {
    form.products.splice(index, 1);
    recalculateExtraCost();
}

function recalculateExtraCost() {
    form.total_extra_cost = form.products.reduce((acc, curr) => acc + curr.subtotal, 0);
}

// Update form when reservation changes
watch([() => props.reservation, () => props.open], ([newVal, isOpen]) => {
    if (isOpen) {
        if (newVal) {
            form.employee_id = newVal.employee_id;
            form.departament_id = newVal.departament_id;
            form.customer_id = newVal.customer_id;
            form.location = newVal.location;
            form.check_in = formatForInput(newVal.check_in);
            form.check_out = formatForInput(newVal.check_out);
            form.total_stay_cost = newVal.total_stay_cost;
            form.total_extra_cost = newVal.total_extra_cost;
            form.requests = newVal.requests ?? '';
            form.comments = newVal.comments ?? '';
            form.status = newVal.status;
            form.products = newVal.products ? newVal.products.map((p: any) => ({
                product_id: p.id,
                name: p.name,
                quantity: p.pivot.quantity,
                unit_price: p.pivot.unit_price,
                subtotal: p.pivot.subtotal,
            })) : [];
        } else {
            form.reset();
            form.location = props.defaultLocation;
            form.products = [];
        }
        form.clearErrors();
    }
}, { immediate: true });

function closeDialog() {
    emit('update:open', false);
    form.reset();
}

function submit() {
    if (props.reservation) {
        form.put(`/admin/reservations/${props.reservation.id}`, {
            onSuccess: () => {
                closeDialog();
            },
        });
    } else {
        form.post(`/admin/reservations`, {
            onSuccess: () => {
                closeDialog();
            },
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="sm:max-w-[700px] bg-card text-card-foreground">
            <DialogHeader>
                <DialogTitle class="text-xl">
                    {{ reservation ? 'Editar Reservación' : 'Nueva Reservación' }}
                    <span v-if="reservation" class="text-primary font-mono ml-2">#{{ String(reservation.id).padStart(5, '0') }}</span>
                </DialogTitle>
                <DialogDescription>
                    <span v-if="!reservation">Completa los datos para crear una nueva reserva.</span>
                    <template v-else-if="reservation?.customer">
                        Huésped: <span class="font-bold">{{ reservation.customer.firstname }} {{ reservation.customer.lastname }}</span>
                    </template>
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6 py-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" v-if="!reservation">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Huésped <span class="text-destructive">*</span></label>
                        <select v-model="form.customer_id" class="rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" required>
                            <option :value="null" disabled>Seleccionar huésped...</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">
                                {{ c.firstname }} {{ c.lastname }} — {{ c.document_number ?? 'S/D' }}
                            </option>
                        </select>
                        <p v-if="form.errors.customer_id" class="text-xs text-destructive">{{ form.errors.customer_id }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Atendido por <span class="text-destructive">*</span></label>
                        <select v-model="form.employee_id" class="rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" required>
                            <option :value="null" disabled>Seleccionar empleado...</option>
                            <option v-for="e in employees" :key="e.id" :value="e.id">
                                {{ e.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.employee_id" class="text-xs text-destructive">{{ form.errors.employee_id }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Check-in -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Check-in <span class="text-destructive">*</span></label>
                        <Input v-model="form.check_in" type="datetime-local" required />
                        <p v-if="form.errors.check_in" class="text-xs text-destructive">{{ form.errors.check_in }}</p>
                    </div>

                    <!-- Check-out -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Check-out <span class="text-destructive">*</span></label>
                        <Input v-model="form.check_out" type="datetime-local" required />
                        <p v-if="form.errors.check_out" class="text-xs text-destructive">{{ form.errors.check_out }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Departamento -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Departamento <span class="text-destructive">*</span></label>
                        <select v-model="form.departament_id" class="rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" required>
                            <option value="" disabled>Seleccionar departamento...</option>
                            <option v-for="d in departments" :key="d.id" :value="d.id">
                                {{ d.code }} — {{ d.location }}
                            </option>
                        </select>
                        <p v-if="form.errors.departament_id" class="text-xs text-destructive">{{ form.errors.departament_id }}</p>
                    </div>

                    <!-- Estado -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Estado <span class="text-destructive">*</span></label>
                        <select v-model="form.status" class="rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" required>
                            <option value="1">Confirmada</option>
                            <option value="2">Check In</option>
                            <option value="3">Check Out</option>
                            <option value="4">Cancelada</option>
                        </select>
                        <p v-if="form.errors.status" class="text-xs text-destructive">{{ form.errors.status }}</p>
                    </div>
                </div>

                <!-- Costo desplazado al final -->

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Requerimientos</label>
                        <textarea v-model="form.requests" class="rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring min-h-[60px]"></textarea>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Comentarios</label>
                        <textarea v-model="form.comments" class="rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring min-h-[60px]"></textarea>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-semibold border-b pb-1">Productos y Costos Extras</h3>

                    <div class="flex items-end gap-2 p-3 bg-muted/40 rounded-lg border border-border">
                        <div class="flex-1 flex flex-col gap-1.5 flex-wrap">
                            <label class="text-xs font-medium">Añadir Producto</label>
                            <select v-model="selectedProduct" class="rounded-md border border-input bg-background px-3 py-1.5 text-sm w-full">
                                <option value="" disabled>Seleccione producto...</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">
                                    {{ p.name }} (Bs. {{ p.price }})
                                </option>
                            </select>
                        </div>
                        <div class="w-20 flex flex-col gap-1.5">
                            <label class="text-xs font-medium">Cant.</label>
                            <Input v-model="selectedQuantity" type="number" min="1" class="h-8" />
                        </div>
                        <Button type="button" size="sm" @click="addProduct" :disabled="!selectedProduct">
                            <Plus class="h-4 w-4 mr-1" /> Add
                        </Button>
                    </div>

                    <div v-if="form.products.length > 0" class="rounded-md border border-border overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-muted/50 border-b border-border text-left">
                                <tr>
                                    <th class="py-2 px-3">Producto</th>
                                    <th class="py-2 px-3 text-center">Cant.</th>
                                    <th class="py-2 px-3 text-right">Precio</th>
                                    <th class="py-2 px-3 text-right">Subtotal</th>
                                    <th class="py-2 px-2 text-center w-8"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in form.products" :key="index" class="border-b border-border/50 last:border-0 hover:bg-muted/20">
                                    <td class="py-2 px-3 font-medium">{{ item.name }}</td>
                                    <td class="py-2 px-3 text-center">{{ item.quantity }}</td>
                                    <td class="py-2 px-3 text-right">Bs. {{ item.unit_price }}</td>
                                    <td class="py-2 px-3 text-right">Bs. {{ item.subtotal.toFixed(2) }}</td>
                                    <td class="py-2 px-2 text-center">
                                        <button type="button" @click="removeProduct(index)" class="text-destructive hover:opacity-70 p-1">
                                            <Trash2 class="h-3 w-3" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end pt-5 border-t border-border/50 mt-4">
                        <div class="w-full sm:w-80 space-y-3 bg-muted/10 p-4 rounded-lg border border-border/50">
                            <div class="flex justify-between items-center text-sm gap-4">
                                <span class="font-medium text-muted-foreground text-right flex-1">Costo de Estadía:</span>
                                <div class="w-32 shrink-0">
                                    <Input v-model="form.total_stay_cost" type="number" step="0.01" required class="h-8 text-right font-medium" />
                                </div>
                            </div>
                            <div class="flex justify-between items-center text-sm gap-4">
                                <span class="font-medium text-muted-foreground text-right flex-1">Costos Extras Totales:</span>
                                <div class="w-32 shrink-0 relative">
                                    <Input v-model="form.total_extra_cost" type="number" step="0.01" required :readonly="form.products.length > 0" class="h-8 text-right font-medium" :class="{'bg-muted/30': form.products.length > 0}" />
                                </div>
                            </div>
                            <div class="flex justify-between items-center text-base font-bold text-primary pt-3 border-t border-border/50 gap-4">
                                <span class="text-right flex-1 uppercase tracking-wider text-xs">Costo Total (Bs.)</span>
                                <span class="w-32 text-right shrink-0 text-xl">{{ (Number(form.total_stay_cost) + Number(form.total_extra_cost)).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden but submitted fields if editing -->
                <template v-if="reservation">
                    <input type="hidden" v-model="form.customer_id" />
                    <input type="hidden" v-model="form.employee_id" />
                </template>
                <input type="hidden" v-model="form.location" />

                <DialogFooter class="flex sm:justify-end gap-2 pt-4 border-t">
                    <Button type="button" variant="outline" @click="closeDialog" :disabled="form.processing">
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
