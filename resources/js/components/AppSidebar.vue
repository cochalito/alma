<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
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
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    CalendarDays,
    Users,
    Settings,
    Activity,
    HelpCircle,
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
        href: '#',
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

const footerNavItems: NavItem[] = [
    {
        title: 'Configuración',
        href: '#',
        icon: Settings,
    },
    {
        title: 'Ayuda y Soporte',
        href: '#',
        icon: HelpCircle,
    },
];
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
            <NavMain :items="dashboardItems" />
            <NavMain title="Hotel" :items="hotelItems" />
            <NavMain title="Reportes" :items="reportItems" />
            <NavMain title="Admin" :items="adminItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
