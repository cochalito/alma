<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem, type AppPageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    LayoutDashboard,
    CalendarDays,
    Users,
    Activity,
    Coffee,
    Ship,
    FileText,
    User,
    Building,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const dashboardItems: NavItem[] = [
    {
        title: 'Panel de Control',
        href: dashboard(),
        icon: LayoutDashboard,
    },
];

const hotelItems: NavItem[] = [
    {
        title: 'Reservaciones',
        href: '/admin/reservations',
        icon: CalendarDays,
    },
    {
        title: 'Charter',
        href: '/admin/charter',
        icon: Ship,
    },
    {
        title: 'Huéspedes',
        href: '/admin/customers',
        icon: Users,
    },
    {
        title: 'Productos',
        href: '/admin/products',
        icon: Coffee,
    },
];

const reportItems: NavItem[] = [
    {
        title: 'Reporte de Reservas',
        href: '/admin/reports/reservations',
        icon: FileText,
    },
    {
        title: 'Reporte de Productos',
        href: '/admin/reports/kardex',
        icon: FileText,
    },
    {
        title: 'Reporte de Actividad',
        href: '/admin/reports/activity',
        icon: Activity,
    },
];

const adminItems: NavItem[] = [
    {
        title: 'Usuarios',
        href: '/admin/users',
        icon: User,
    },
    {
        title: 'Departamentos',
        href: '/admin/departaments',
        icon: Building,
    },
];

const page = usePage<AppPageProps>();
const userRole = computed(() => page.props.auth.user.role || '');

function hasAccess(menuTitle: string): boolean {
    const role = userRole.value;

    if (role === 'GERENCIA') return true;

    if (role.startsWith('ADMINISTRACION')) {
        return !['Reporte de Reservas', 'Reporte de Actividad'].includes(menuTitle);
    }

    if (role.startsWith('RECEPCIONISTA')) {
        return ['Panel de Control', 'Reservaciones', 'Charter', 'Huéspedes', 'Reporte de Productos'].includes(menuTitle);
    }

    return false;
}

const filteredDashboardItems = computed(() => dashboardItems.filter(item => hasAccess(item.title)));
const filteredHotelItems = computed(() => hotelItems.filter(item => hasAccess(item.title)));
const filteredReportItems = computed(() => reportItems.filter(item => hasAccess(item.title)));
const filteredAdminItems = computed(() => adminItems.filter(item => hasAccess(item.title)));
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain v-if="filteredDashboardItems.length" :items="filteredDashboardItems" />
            <NavMain v-if="filteredHotelItems.length" title="Hotel" :items="filteredHotelItems" />
            <NavMain v-if="filteredReportItems.length" title="Reportes" :items="filteredReportItems" />
            <NavMain v-if="filteredAdminItems.length" title="Admin" :items="filteredAdminItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
