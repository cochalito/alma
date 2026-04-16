<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { type Reservation, type Departament, type User, type Product, type Customer, type AppPageProps } from '@/types';
import axios from 'axios';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Plus, Trash2, AlertTriangle, Lock, Printer, Banknote, QrCode, CreditCard, ChevronDown, ChevronUp } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

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

// Payment management state
const paymentForm = useForm({
    amount: 0,
    payment_method: 'EFECTIVO',
    description: '',
});

const totalPaid = computed(() => {
    if (!props.reservation?.payments) return 0;
    return props.reservation.payments.reduce((acc: number, p: any) => acc + Number(p.amount), 0);
});

const balanceDue = computed(() => {
    if (!props.reservation) return 0;
    const totalDue = Number(form.total_stay_cost) + Number(form.total_extra_cost);
    return Number((totalDue - totalPaid.value).toFixed(2));
});

const isPaymentsExpanded = ref(false);

watch(() => props.open, (isOpen) => {
    if (isOpen && props.reservation) {
        // Pre-fill amount with balance due when opening modal for existing reservation
        paymentForm.amount = balanceDue.value > 0 ? balanceDue.value : 0;
        
        // Start expanded if already checked out, otherwise collapsed
        isPaymentsExpanded.value = props.reservation.status === '3';
    }
});

// Auto-update payment amount when balance changes (e.g. adding products)
watch(balanceDue, (newBalance) => {
    if (newBalance > 0) {
        paymentForm.amount = newBalance;
    } else {
        paymentForm.amount = 0;
    }
});

function submitPayment() {
    if (!props.reservation) return;
    if (paymentForm.amount <= 0) return;

    if (paymentForm.amount > balanceDue.value + 0.01) {
        alert(`El monto no puede ser mayor al saldo pendiente (Bs. ${balanceDue.value})`);
        return;
    }

    paymentForm.post(`/admin/reservations/${props.reservation.id}/payments`, {
        preserveScroll: true,
        onSuccess: () => {
            paymentForm.reset('amount', 'description');
            paymentForm.payment_method = 'EFECTIVO';
        },
    });
}

function deletePayment(id: number) {
    if (!confirm('¿Estás seguro de que deseas eliminar este pago?')) return;
    
    router.delete(`/admin/payments/${id}`, {
        preserveScroll: true
    });
}


const formatForInput = (dateStr: string) => {
    if (!dateStr) return '';
    return dateStr.slice(0, 10);
};

const page = usePage<AppPageProps>();

const localCustomers = ref<Customer[]>([...props.customers]);

const customerSearch = ref('');
const showCustomerDropdown = ref(false);

const filteredCustomers = computed(() => {
    const sorted = [...localCustomers.value].sort((a, b) =>
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
    const existing = form.products.find(p => Number(p.product_id) === Number(product.id));
    const qtyToAdd = Number(selectedQuantity.value);

    if (existing) {
        existing.quantity = Number(existing.quantity) + qtyToAdd;
        existing.subtotal = Number((existing.quantity * existing.unit_price).toFixed(2));
    } else {
        form.products.push({
            product_id: product.id,
            name: product.name,
            quantity: qtyToAdd,
            unit_price: Number(product.price),
            subtotal: Number((qtyToAdd * product.price).toFixed(2)),
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
    const total = form.products.reduce((acc, curr) => acc + Number(curr.subtotal), 0);
    form.total_extra_cost = Number(total.toFixed(2));
}

const nightlyCost = ref<number | string>('');

const stayNights = computed(() => {
    if (!form.check_in || !form.check_out) return 0;
    const start = new Date(form.check_in);
    const end = new Date(form.check_out);
    if (!isNaN(start.getTime()) && !isNaN(end.getTime()) && start < end) {
        const diffTime = Math.abs(end.getTime() - start.getTime());
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    }
    return 0;
});

watch([nightlyCost, stayNights], ([nc, nights]) => {
    if (nc !== '') {
        form.total_stay_cost = Number((Number(nc) * (nights > 0 ? nights : 1)).toFixed(2));
    } else {
        form.total_stay_cost = 0;
    }
});

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
            form.total_stay_cost = Number(newVal.total_stay_cost);
            form.total_extra_cost = Number(Number(newVal.total_extra_cost).toFixed(2));
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

            const start = new Date(newVal.check_in.slice(0, 10));
            const end = new Date(newVal.check_out.slice(0, 10));
            let nights = 0;
            if (!isNaN(start.getTime()) && !isNaN(end.getTime()) && start < end) {
                nights = Math.ceil(Math.abs(end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
            }
            if (nights > 0) {
                nightlyCost.value = Number((newVal.total_stay_cost / nights).toFixed(2));
            } else {
                nightlyCost.value = newVal.total_stay_cost;
            }

            // Important: recalculate to fix any imprecision stored in DB
            recalculateExtraCost();
        } else {
            form.reset();
            form.location = props.defaultLocation;
            form.employee_id = page.props.auth.user.id;
            form.products = [];
            localCustomers.value = [...props.customers];
            nightlyCost.value = '';
        }
        
        // Reset purely visual UI states on open
        customerSearch.value = '';
        showCustomerDropdown.value = false;
        cancelAddCustomer();
        
        form.clearErrors();
    }
}, { immediate: true });

// Automatically set location based on selected department
watch(() => form.departament_id, (deptId) => {
    if (deptId) {
        const dept = props.departments.find(d => d.id === deptId);
        if (dept) {
            form.location = dept.location;
            if (!props.reservation && dept.cost) { // only on new reservation, set nightly cost to department cost
                nightlyCost.value = dept.cost;
            }
        }
    }
});

// Checkout confirmation
const showCheckoutConfirm = ref(false);
const pendingCheckoutStatus = ref<string | null>(null);

// A reservation already in checkout state cannot be edited
const isCheckedOut = computed(() => props.reservation?.status === '3');

function handleStatusChange(event: Event) {
    const select = event.target as HTMLSelectElement;
    const newValue = select.value;
    if (newValue === '3' && form.status !== '3') {
        // Prevent checkout if there is balance due
        if (balanceDue.value > 0.01) {
            alert(`ATENCIÓN: No se puede realizar Check Out porque aún existe un saldo pendiente de Bs. ${balanceDue.value.toFixed(2)}. Por favor, registre el pago total antes de continuar.`);
            select.value = form.status; // Revert selection
            return;
        }

        // Revert the visual selection (confirmation will handle the change)
        select.value = form.status;
        pendingCheckoutStatus.value = '3';
        showCheckoutConfirm.value = true;
    } else {
        form.status = newValue;
    }
}

function confirmCheckout() {
    form.status = '3';
    showCheckoutConfirm.value = false;
    pendingCheckoutStatus.value = null;
}

function cancelCheckout() {
    showCheckoutConfirm.value = false;
    pendingCheckoutStatus.value = null;
}

function closeDialog() {
    emit('update:open', false);
    form.reset();
    showCheckoutConfirm.value = false;
    pendingCheckoutStatus.value = null;
}

function submit() {
    if (isCheckedOut.value) return;
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
        <DialogContent class="sm:max-w-[1100px] w-[95vw] md:w-[90vw] max-h-[95vh] overflow-y-auto bg-card text-card-foreground">
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

            <!-- Locked banner for checked-out reservations -->
            <div v-if="isCheckedOut" class="flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-700 px-4 py-3 text-amber-700 dark:text-amber-400 text-sm font-medium">
                <Lock class="h-4 w-4 shrink-0" />
                Esta reserva está en estado <strong class="ml-1">Check Out</strong> y ya no puede ser editada.
            </div>

            <form @submit.prevent="submit" class="py-2 flex flex-col gap-6" :class="{ 'pointer-events-none opacity-60': isCheckedOut }">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                    
                    <!-- PRIMERA COLUMNA: Datos principales -->
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                                    <Input v-model="qcForm.email" type="email" placeholder="Correo" class="h-8 text-xs" />
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
                <div class="grid grid-cols-1 gap-4">
                    <!-- Fechas de Estadía -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Fechas de Estadía (Ingreso - Salida) <span class="text-destructive">*</span></label>
                        <div class="flex items-center rounded-md border border-input bg-background overflow-hidden focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2 h-10">
                            <input v-model="form.check_in" type="date" required class="flex-1 bg-transparent px-3 py-2 text-sm focus:outline-none h-full min-w-0" />
                            <div class="flex items-center justify-center px-3 h-full border-x border-input bg-muted/30 text-muted-foreground">
                                <span class="text-xs font-medium uppercase">Hasta</span>
                            </div>
                            <input v-model="form.check_out" type="date" required class="flex-1 bg-transparent px-3 py-2 text-sm focus:outline-none h-full min-w-0" />
                        </div>
                        <div v-if="form.errors.check_in || form.errors.check_out" class="flex flex-col gap-1 mt-0.5">
                            <p v-if="form.errors.check_in" class="text-xs text-destructive">{{ form.errors.check_in }}</p>
                            <p v-if="form.errors.check_out" class="text-xs text-destructive">{{ form.errors.check_out }}</p>
                        </div>
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

                        <!-- Confirmation card shown inline replacing the select -->
                        <div v-if="showCheckoutConfirm" class="rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-700 p-3 space-y-3">
                            <div class="flex items-center gap-2 text-amber-700 dark:text-amber-400">
                                <AlertTriangle class="h-4 w-4 shrink-0" />
                                <span class="text-xs font-semibold">Confirmar Check Out</span>
                            </div>
                            <p class="text-xs text-muted-foreground leading-relaxed">
                                ¿Está seguro? Una vez en <strong>Check Out</strong> la reserva no podrá editarse.
                            </p>
                            <div class="flex gap-2">
                                <Button type="button" variant="outline" size="sm" class="h-7 text-xs flex-1" @click="cancelCheckout">Cancelar</Button>
                                <Button type="button" size="sm" class="h-7 text-xs flex-1 bg-amber-600 hover:bg-amber-700 text-white" @click="confirmCheckout">
                                    Sí, Check Out
                                </Button>
                            </div>
                        </div>

                        <select
                            v-else
                            :value="form.status"
                            @change="handleStatusChange"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            required
                        >
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

                        <div class="flex flex-col gap-3 mt-2 mb-2 items-end">
                            <div class="w-full sm:w-80">
                                <div class="flex justify-between items-center text-sm gap-4">
                                    <label class="font-medium text-right flex-1">Costo por Noche (Bs.) <span class="text-destructive">*</span></label>
                                    <div class="w-32 shrink-0">
                                        <Input v-model="nightlyCost" type="number" step="0.01" required class="h-9 text-right font-medium" />
                                    </div>
                                </div>
                            </div>
                            <div class="w-full sm:w-80 bg-muted/10 p-3 pt-2 pb-2 rounded-lg border border-border">
                                <div class="flex justify-between items-center text-sm gap-4">
                                    <label class="font-medium flex-1 text-muted-foreground">Costo de Estadía <span class="text-[10px] font-semibold tracking-wider uppercase ml-1 opacity-70">({{ stayNights }} Noche{{ stayNights !== 1 ? 's' : '' }})</span></label>
                                    <div class="w-32 shrink-0 text-right">
                                        <span class="font-bold text-primary tabular-nums">Bs. {{ Number(form.total_stay_cost).toFixed(2) }}</span>
                                    </div>
                                </div>
                                <p v-if="form.errors.total_stay_cost" class="text-xs text-destructive text-right mt-1">{{ form.errors.total_stay_cost }}</p>
                            </div>
                        </div>
                    </div> <!-- Termina Primera Columna -->

                    <!-- SEGUNDA COLUMNA: Productos y Costos Extras -->
                    <div class="space-y-5 bg-muted/20 p-5 rounded-xl border border-border/50 flex flex-col h-full">
                        <h3 class="text-sm font-semibold border-b pb-2">Productos y Costos Extras</h3>

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
                                    <Input :model-value="Number(form.total_extra_cost).toFixed(2)" readonly tabindex="-1" class="h-8 text-right font-medium bg-muted opacity-80" />
                                </div>
                            </div>
                            <!-- Grand Total -->
                            <div class="flex justify-between items-center text-lg font-bold text-primary pt-3 border-t border-border/50 gap-4">
                                <span class="text-right flex-1 uppercase tracking-wider text-sm whitespace-nowrap">Costo Total</span>
                                <span class="w-32 text-right shrink-0">Bs. {{ (Number(form.total_stay_cost) + Number(form.total_extra_cost)).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="hidden">
                        <template v-if="reservation">
                            <input type="hidden" v-model="form.employee_id" />
                        </template>
                        <input type="hidden" v-model="form.location" />
                    </div>
                </div> <!-- Termina Segunda Columna -->
            </div> <!-- Termina Grid Principal -->
            
            <!-- SECCIÓN DE PAGOS (Solo para Reservas Existentes) -->
            <div v-if="reservation" class="mt-4 rounded-xl border border-blue-200 bg-blue-50/30 dark:bg-blue-950/20 dark:border-blue-900/50 overflow-hidden">
                <button 
                    type="button" 
                    @click="isPaymentsExpanded = !isPaymentsExpanded"
                    class="w-full flex items-center justify-between p-4 hover:bg-blue-100/30 transition-colors"
                >
                    <div class="flex items-center gap-3">
                        <Banknote class="h-5 w-5 text-blue-600" />
                        <h3 class="text-lg font-bold">Control de Pagos</h3>
                        <Badge v-if="!isPaymentsExpanded" :variant="balanceDue <= 0 ? 'secondary' : 'default'" class="ml-2">
                             Saldo: Bs. {{ balanceDue.toFixed(2) }}
                        </Badge>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-blue-600/70 uppercase">
                            {{ isPaymentsExpanded ? 'Contraer' : 'Gestionar Pagos' }}
                        </span>
                        <ChevronUp v-if="isPaymentsExpanded" class="h-5 w-5 text-blue-600" />
                        <ChevronDown v-else class="h-5 w-5 text-blue-600" />
                    </div>
                </button>

                <div v-if="isPaymentsExpanded" class="p-5 pt-0">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 border-t border-blue-100 dark:border-blue-900/30 pt-5">
                    <!-- Historial de Pagos -->
                    <div class="lg:col-span-2 space-y-3">
                        <h4 class="text-xs font-bold uppercase text-muted-foreground tracking-wider">Historial de Pagos</h4>
                        <div v-if="reservation.payments && reservation.payments.length > 0" class="rounded-lg border bg-background overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/50 text-xs">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold">Fecha</th>
                                        <th class="px-3 py-2 text-left font-semibold">Método</th>
                                        <th class="px-3 py-2 text-left font-semibold">Nota</th>
                                        <th class="px-3 py-2 text-right font-semibold">Monto</th>
                                        <th class="px-3 py-2 text-center w-10"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="payment in reservation.payments" :key="payment.id" class="border-t hover:bg-muted/30 transition-colors">
                                        <td class="px-3 py-2 text-xs text-muted-foreground">
                                            {{ new Date(payment.created_at).toLocaleDateString('es-BO') }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold">
                                                <Banknote v-if="payment.payment_method === 'EFECTIVO'" class="h-3 w-3 text-green-600" />
                                                <QrCode v-else-if="payment.payment_method === 'QR'" class="h-3 w-3 text-blue-600" />
                                                <CreditCard v-else class="h-3 w-3 text-purple-600" />
                                                {{ payment.payment_method }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-xs italic">{{ payment.description || '-' }}</td>
                                        <td class="px-3 py-2 text-right font-bold">Bs. {{ Number(payment.amount).toFixed(2) }}</td>
                                        <td class="px-3 py-2 text-center">
                                            <button type="button" @click="deletePayment(payment.id)" class="text-destructive hover:scale-110 transition-transform">
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-muted/20 font-bold border-t">
                                    <tr>
                                        <td colspan="3" class="px-3 py-2 text-right uppercase text-xs">Total Pagado:</td>
                                        <td class="px-3 py-2 text-right text-green-700 dark:text-green-500">Bs. {{ totalPaid.toFixed(2) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-8 rounded-lg border border-dashed border-blue-200 bg-blue-50/20 text-blue-400">
                            <Banknote class="h-10 w-10 mb-2 opacity-20" />
                            <p class="text-sm italic">No se han registrado pagos aún.</p>
                        </div>
                    </div>

                    <!-- Agregar Pago y Saldo -->
                    <div class="space-y-4">
                        <div class="p-4 rounded-lg bg-background border border-blue-100 shadow-sm space-y-3">
                            <h4 class="text-xs font-bold uppercase text-muted-foreground tracking-wider">Registrar Nuevo Pago</h4>
                            <form @submit.prevent="submitPayment" class="space-y-3">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-medium">Monto (Bs.)</label>
                                    <Input v-model="paymentForm.amount" type="number" step="0.01" min="0.01" required class="h-9" />
                                    <p v-if="paymentForm.errors.amount" class="text-[10px] text-destructive italic mt-0.5">{{ paymentForm.errors.amount }}</p>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-medium">Método de Pago</label>
                                    <select v-model="paymentForm.payment_method" class="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-sm h-9">
                                        <option value="EFECTIVO">EFECTIVO</option>
                                        <option value="QR">QR</option>
                                        <option value="TARJETA">TARJETA</option>
                                    </select>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-medium">Descripción / Nota</label>
                                    <Input v-model="paymentForm.description" placeholder="Ej: Pago parcial, Adelanto..." class="h-9" />
                                </div>
                                <Button type="submit" :disabled="paymentForm.processing || paymentForm.amount <= 0" class="w-full bg-blue-600 hover:bg-blue-700 text-white mt-1">
                                    <Plus class="h-4 w-4 mr-2" />
                                    {{ paymentForm.processing ? 'Procesando...' : 'Confirmar Pago' }}
                                </Button>
                            </form>
                        </div>

                        <!-- Resumen Financiero -->
                        <div class="p-4 rounded-lg border-2" :class="balanceDue <= 0 ? 'bg-green-50 border-green-200 dark:bg-green-950/20 dark:border-green-900' : 'bg-amber-50 border-amber-200 dark:bg-amber-950/20 dark:border-amber-900'">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-bold uppercase text-muted-foreground">Saldo Pendiente</span>
                                <Badge :variant="balanceDue <= 0 ? 'secondary' : 'default'">
                                    {{ balanceDue <= 0 ? 'Pagado Total' : 'Pendiente' }}
                                </Badge>
                            </div>
                            <p class="text-3xl font-black mt-1" :class="balanceDue <= 0 ? 'text-green-600' : 'text-amber-600'">
                                Bs. {{ balanceDue.toFixed(2) }}
                            </p>
                        </div>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter class="flex gap-2 pt-4 border-t mt-4" :class="reservation && (reservation.status === '2' || reservation.status === '3') ? 'justify-between' : 'justify-end'">
                <div v-if="reservation && (reservation.status === '2' || reservation.status === '3')">
                    <a :href="`/admin/reservations/${reservation.id}/print`" target="_blank" class="pointer-events-auto inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 opacity-100 hover:opacity-100" style="opacity: 1 !important;">
                        <Printer class="mr-2 h-4 w-4" />
                        Imprimir Nota
                    </a>
                </div>
                <div class="flex gap-2">
                    <Button type="button" variant="outline" @click="closeDialog" :disabled="form.processing" class="pointer-events-auto">
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing || isCheckedOut" class="min-w-[140px]">
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </Button>
                </div>
            </DialogFooter>
        </form>
    </DialogContent>
</Dialog>
</template>
