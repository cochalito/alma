<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { UserCircle, ShieldCheck, Eye, EyeOff } from 'lucide-vue-next';
import type { AppPageProps } from '@/types';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const page = usePage<AppPageProps>();
const user = page.props.auth.user;

const roleLabels: Record<string, string> = {
    GERENCIA: 'Gerencia',
    ADMINISTRACION: 'Administración',
    ADMINISTRACION_LA_PAZ: 'Administración La Paz',
    ADMINISTRACION_UYUNI: 'Administración Uyuni',
    RECEPCIONISTA: 'Recepcionista',
    RECEPCIONISTA_LA_PAZ: 'Recepcionista La Paz',
    RECEPCIONISTA_UYUNI: 'Recepcionista Uyuni',
};

const roleLabel = roleLabels[user.role] ?? user.role;

const showPassword = ref(false);
const showPasswordConfirm = ref(false);
const successMessage = ref('');

const form = useForm({
    name: user.name,
    email: user.email,
    password: '',
    password_confirmation: '',
});

// Reset form when modal opens
watch(
    () => props.open,
    (val) => {
        if (val) {
            form.name = user.name;
            form.email = user.email;
            form.password = '';
            form.password_confirmation = '';
            form.clearErrors();
            successMessage.value = '';
        }
    },
);

function closeModal() {
    emit('update:open', false);
}

function submit() {
    successMessage.value = '';
    form.patch('/profile/quick-update', {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = 'Perfil actualizado correctamente.';
            form.password = '';
            form.password_confirmation = '';
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="sm:max-w-md user-profile-modal">
            <DialogHeader class="modal-header-section">
                <div class="modal-icon-wrapper">
                    <UserCircle class="modal-icon" />
                </div>
                <DialogTitle class="modal-title">Mi Perfil</DialogTitle>
                <p class="modal-subtitle">Actualiza tu información personal</p>
            </DialogHeader>

            <!-- Role badge (read-only) -->
            <div class="role-badge-wrapper">
                <div class="role-badge">
                    <ShieldCheck class="role-icon" />
                    <span class="role-label">{{ roleLabel }}</span>
                </div>
            </div>

            <!-- Success message -->
            <div v-if="successMessage" class="success-banner">
                <span>✓ {{ successMessage }}</span>
            </div>

            <form @submit.prevent="submit" class="profile-form">
                <!-- Nombre -->
                <div class="form-group">
                    <label for="profile-name" class="form-label">Nombre completo</label>
                    <input
                        id="profile-name"
                        v-model="form.name"
                        type="text"
                        class="form-input"
                        :class="{ 'form-input--error': form.errors.name }"
                        placeholder="Tu nombre"
                        autocomplete="name"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="profile-email" class="form-label">Correo electrónico</label>
                    <input
                        id="profile-email"
                        v-model="form.email"
                        type="email"
                        class="form-input"
                        :class="{ 'form-input--error': form.errors.email }"
                        placeholder="correo@ejemplo.com"
                        autocomplete="email"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="profile-password" class="form-label">
                        Nueva contraseña
                        <span class="optional-tag">(opcional)</span>
                    </label>
                    <div class="password-wrapper">
                        <input
                            id="profile-password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            class="form-input password-input"
                            :class="{ 'form-input--error': form.errors.password }"
                            placeholder="Dejar vacío para no cambiar"
                            autocomplete="new-password"
                        />
                        <button
                            type="button"
                            class="toggle-password"
                            @click="showPassword = !showPassword"
                            tabindex="-1"
                        >
                            <EyeOff v-if="showPassword" class="eye-icon" />
                            <Eye v-else class="eye-icon" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password" />
                </div>

                <!-- Password Confirmation -->
                <div class="form-group" v-if="form.password">
                    <label for="profile-password-confirm" class="form-label">Confirmar contraseña</label>
                    <div class="password-wrapper">
                        <input
                            id="profile-password-confirm"
                            v-model="form.password_confirmation"
                            :type="showPasswordConfirm ? 'text' : 'password'"
                            class="form-input password-input"
                            :class="{ 'form-input--error': form.errors.password_confirmation }"
                            placeholder="Repetir nueva contraseña"
                            autocomplete="new-password"
                        />
                        <button
                            type="button"
                            class="toggle-password"
                            @click="showPasswordConfirm = !showPasswordConfirm"
                            tabindex="-1"
                        >
                            <EyeOff v-if="showPasswordConfirm" class="eye-icon" />
                            <Eye v-else class="eye-icon" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <DialogFooter class="profile-footer">
                    <Button
                        type="button"
                        variant="outline"
                        @click="closeModal"
                        class="btn-cancel"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="btn-save"
                    >
                        <span v-if="form.processing">Guardando...</span>
                        <span v-else>Guardar cambios</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.user-profile-modal {
    padding: 0;
    overflow: hidden;
    border-radius: 16px;
}

.modal-header-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 28px 28px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    border-radius: 0;
}

.modal-icon-wrapper {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    padding: 12px;
    backdrop-filter: blur(10px);
    margin-bottom: 4px;
}

.modal-icon {
    width: 36px;
    height: 36px;
    color: #fff;
}

.modal-title {
    color: #fff !important;
    font-size: 1.25rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    text-align: center;
}

.modal-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.82rem;
    text-align: center;
    margin: 0;
}

.role-badge-wrapper {
    display: flex;
    justify-content: center;
    padding: 14px 24px 6px;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #f0f4ff, #e8d5f5);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 999px;
    padding: 6px 14px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #5b4fcf;
}

.dark .role-badge {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
    border-color: rgba(102, 126, 234, 0.4);
    color: #a78bfa;
}

.role-icon {
    width: 14px;
    height: 14px;
}

.success-banner {
    margin: 0 24px;
    padding: 10px 14px;
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    border: 1px solid #6ee7b7;
    border-radius: 8px;
    color: #065f46;
    font-size: 0.85rem;
    font-weight: 500;
    text-align: center;
}

.dark .success-banner {
    background: linear-gradient(135deg, rgba(6, 95, 70, 0.3), rgba(5, 150, 105, 0.2));
    border-color: rgba(110, 231, 183, 0.3);
    color: #6ee7b7;
}

.profile-form {
    padding: 16px 24px 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.form-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 6px;
}

.dark .form-label {
    color: #d1d5db;
}

.optional-tag {
    font-weight: 400;
    color: #9ca3af;
    font-size: 0.78rem;
}

.form-input {
    width: 100%;
    padding: 9px 12px;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.875rem;
    background: #fff;
    color: #111827;
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
}

.dark .form-input {
    background: #1f2937;
    border-color: #374151;
    color: #f9fafb;
}

.form-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
}

.form-input--error {
    border-color: #ef4444 !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12) !important;
}

.password-wrapper {
    position: relative;
}

.password-input {
    padding-right: 40px;
}

.toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #9ca3af;
    padding: 2px;
    display: flex;
    align-items: center;
    transition: color 0.2s;
}

.toggle-password:hover {
    color: #667eea;
}

.eye-icon {
    width: 16px;
    height: 16px;
}

.profile-footer {
    padding-top: 4px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-cancel {
    font-size: 0.875rem;
}

.btn-save {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    font-size: 0.875rem;
    font-weight: 600;
    transition: opacity 0.2s, transform 0.1s;
}

.btn-save:hover:not(:disabled) {
    opacity: 0.9;
    transform: translateY(-1px);
}

.btn-save:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
