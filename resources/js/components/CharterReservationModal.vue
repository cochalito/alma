<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { type Reservation, type Departament, type User, type Product, type Customer, type AppPageProps } from '@/types';
import axios from 'axios';
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

const page = usePage<AppPageProps>();

const localCustomers = ref<Customer[]>([...props.customers]);

const customerSearch = ref('');
const showCustomerDropdown = ref(false);

const filteredCustomers = computed(() => {
    let sorted = [...localCustomers.value].sort((a, b) =>
        (a.firstname + ' ' + a.lastname).localeCompare(b.firstname + ' ' + b.lastname)
    );
    if (!customerSearch.value) return sorted.slice(0, 50);
    const s = customerSearch.value.toLowerCase();
    return sorted.filter(c =>
        (c.firstname + ' ' + c.lastname).toLowerCase().includes(s) ||
        (c.document_number || '').toLowerCase().includes(s)
    ).slice(0, 50);
});

const selectedCustomerDisplay = computed(() => {
    if (!form.customer_id) return '';
    const c = localCustomers.value.find(c => c.id === form.customer_id);
    return c ? `${c.firstname} ${c.lastname} — ${c.document_number ?? 'S/D'}` : '';
});

function selectCustomer(id: number) {
    form.customer_id = id;
    showCustomerDropdown.value = false;
    customerSearch.value = '';
}

function handleCustomerBlur() {
    window.setTimeout(() => {
        showCustomerDropdown.value = false;
    }, 200);
}

// Quick create customer
const isAddingCustomer = ref(false);
const qcSaving = ref(false);
const qcErrors = ref<any>({});
const qcForm = ref({
    firstname: '',
    lastname: '',
    email: '',
    document_number: '',
});

function cancelAddCustomer() {
    isAddingCustomer.value = false;
    qcForm.value = { firstname: '', lastname: '', email: '', document_number: '' };
    qcErrors.value = {};
}

async function saveQuickCustomer() {
    qcErrors.value = {};
    qcSaving.value = true;
    try {
        const response = await axios.post('/admin/customers/quick', qcForm.value);
        const newCustomer = response.data.customer;
        localCustomers.value.push(newCustomer);
        form.customer_id = newCustomer.id;
        cancelAddCustomer();
    } catch (e: any) {
        if (e.response && e.response.status === 422) {
            qcErrors.value = e.response.data.errors;
        }
    } finally {
        qcSaving.value = false;
    }
}

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
            form.employee_id = page.props.auth.user.id;
            form.products = [];
            localCustomers.value = [...props.customers];
        }
        form.clearErrors();
    }
}, { immediate: true });

// Automatically set location based on selected department
watch(() => form.departament_id, (deptId) => {
    if (deptId) {
        const dept = props.departments.find(d => d.id === deptId);
        if (dept) {
            form.location = dept.location;
        }
    }
});

// Recalculate cost when dates or department change
watch([() => form.departament_id, () => form.check_in, () => form.check_out], ([deptId, checkIn, checkOut]) => {
    // Only auto-calc if creating a new reservation or if actively changing fields, though to keep it simple we always calc if valid values.
    if (!deptId || !checkIn || !checkOut) return;

    const dept = props.departments.find(d => d.id === deptId);
    if (!dept || !dept.cost) return; // If dept doesn't have a suggested cost, we don't overwrite

    const start = new Date(checkIn);
    const end = new Date(checkOut);

    // Calculate nights difference
    if (!isNaN(start.getTime()) && !isNaN(end.getTime()) && start < end) {
        const diffTime = Math.abs(end.getTime() - start.getTime());
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        form.total_stay_cost = Number((diffDays * dept.cost).toFixed(2));
    }
});

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
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-medium">Huésped <span class="text-destructive">*</span></label>
                            <Button v-if="!isAddingCustomer" type="button" variant="ghost" size="sm" class="h-6 px-2 text-xs" @click="isAddingCustomer = true">
                                <Plus class="h-3 w-3 mr-1" /> Nuevo
                            </Button>
                        </div>

                        <div v-if="isAddingCustomer" class="p-3 border rounded-lg bg-muted/20 space-y-3">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <Input v-model="qcForm.firstname" placeholder="Nombre *" class="h-8 text-xs" />
                                    <p v-if="qcErrors.firstname" class="text-[10px] text-destructive mt-0.5">{{ qcErrors.firstname[0] }}</p>
                                </div>
                                <div>
                                    <Input v-model="qcForm.lastname" placeholder="Apellido *" class="h-8 text-xs" />
                                    <p v-if="qcErrors.lastname" class="text-[10px] text-destructive mt-0.5">{{ qcErrors.lastname[0] }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <Input v-model="qcForm.document_number" placeholder="Documento" class="h-8 text-xs" />
                                </div>
                                <div>
                                    <Input v-model="qcForm.email" type="email" placeholder="Correo *" class="h-8 text-xs" />
                                    <p v-if="qcErrors.email" class="text-[10px] text-destructive mt-0.5">{{ qcErrors.email[0] }}</p>
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 mt-2">
                                <Button type="button" variant="ghost" size="sm" class="h-7 text-xs" @click="cancelAddCustomer">Cancelar</Button>
                                <Button type="button" size="sm" class="h-7 text-xs" :disabled="qcSaving" @click="saveQuickCustomer">
                                    {{ qcSaving ? '...' : 'Revisar y añadir' }}
                                </Button>
                            </div>
                        </div>

                        <div v-else class="relative">
                            <Input
                                v-if="showCustomerDropdown || !form.customer_id"
                                v-model="customerSearch"
                                placeholder="Buscar huésped..."
                                class="h-10 text-sm"
                                @focus="showCustomerDropdown = true"
                                @blur="handleCustomerBlur"
                            />
                            <div v-else class="flex items-center justify-between border border-input bg-background px-3 py-2 rounded-lg text-sm cursor-pointer h-10 hover:bg-muted/30" @click="showCustomerDropdown = true">
                                <span class="truncate font-medium">{{ selectedCustomerDisplay }}</span>
                                <span class="text-[10px] uppercase text-muted-foreground ml-2 px-2 py-0.5 bg-muted rounded-full">Cambiar</span>
                            </div>

                            <div v-if="showCustomerDropdown" class="absolute z-50 w-full mt-1 bg-popover text-popover-foreground border rounded-md shadow-md max-h-60 overflow-y-auto">
                                <div v-if="filteredCustomers.length === 0" class="p-3 text-sm text-center text-muted-foreground">
                                    No se encontraron resultados
                                </div>
                                <div
                                    v-for="c in filteredCustomers"
                                    :key="c.id"
                                    @click="selectCustomer(c.id)"
                                    class="px-3 py-2 text-sm cursor-pointer hover:bg-muted border-b border-border/30 last:border-0"
                                >
                                    <div class="font-medium">{{ c.firstname }} {{ c.lastname }}</div>
                                    <div class="text-[10px] text-muted-foreground">{{ c.document_number ?? 'S/D' }}</div>
                                </div>
                            </div>

                            <p v-if="form.errors.customer_id" class="text-xs text-destructive mt-1">{{ form.errors.customer_id }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Atendido por</label>
                        <Input :model-value="page.props.auth.user.name" readonly tabindex="-1" class="bg-muted font-medium" />
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

                <div class="flex justify-end mt-2 mb-2">
                    <div class="w-full sm:w-80">
                        <div class="flex justify-between items-center text-sm gap-4">
                            <label class="font-medium text-right flex-1">Costo de Estadía (Bs.) <span class="text-destructive">*</span></label>
                            <div class="w-32 shrink-0">
                                <Input v-model="form.total_stay_cost" type="number" step="0.01" required class="h-9 text-right font-bold text-primary" />
                            </div>
                        </div>
                        <p v-if="form.errors.total_stay_cost" class="text-xs text-destructive text-right mt-1">{{ form.errors.total_stay_cost }}</p>
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
                                    <td class="py-2 px-3 text-right">Bs. {{ Number(item.unit_price).toFixed(2) }}</td>
                                    <td class="py-2 px-3 text-right">Bs. {{ Number(item.subtotal).toFixed(2) }}</td>
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
                            <!-- Extras below table -->
                            <div class="flex justify-between items-center text-sm gap-4">
                                <span class="font-medium text-muted-foreground text-right flex-1">Costos Extras:</span>
                                <div class="w-32 shrink-0">
                                    <Input :model-value="form.total_extra_cost" readonly tabindex="-1" class="h-8 text-right font-medium bg-muted opacity-80" />
                                </div>
                            </div>
                            <!-- Grand Total -->
                            <div class="flex justify-between items-center text-lg font-bold text-primary pt-3 border-t border-border/50 gap-4">
                                <span class="text-right flex-1 uppercase tracking-wider text-sm whitespace-nowrap">Costo Total</span>
                                <span class="w-32 text-right shrink-0">Bs. {{ (Number(form.total_stay_cost) + Number(form.total_extra_cost)).toFixed(2) }}</span>
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
