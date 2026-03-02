import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;



export type DocumentType = '1' | '2' | '3' | '4'; // 1 CI, 2 DNI, 3 Pasaporte, 4 Otro
export type CustomerStatus = '0' | '1'; // 0 Inactivo, 1 Activo

export interface Customer {
    id: number;
    document_type: DocumentType | null;
    document_number: string | null;
    firstname: string;
    lastname: string;
    email: string;
    cellphone: string | null;
    status: CustomerStatus;
    created_at: string;
    updated_at: string;
}

export type ProductCategory = 'beverages' | 'snacks' | 'toiletries' | 'other';

export interface Product {
    id: number;
    name: string;
    description?: string;
    category: ProductCategory;
    price: number;
    total_stock: number;
    is_active: boolean;
    image_url?: string;
    created_at: string;
    updated_at: string;
}

export interface ReservationProduct {
    id: number;
    product: Product;
    quantity: number;
    unit_price: number;
    subtotal: number;
}

export interface Departament {
    id: number;
    location: string;
    code: string;
    cost: number | null;
    status: '0' | '1';
    created_at: string;
    updated_at: string;
}

export type ReservationStatus = '1' | '2' | '3' | '4'; // 1 Confirmado, 2 Check In, 3 Check Out, 4 Cancelado

export interface Reservation {
    id: number;
    employee_id: number;
    departament_id: number;
    customer_id: number;
    location: string;
    check_in: string;
    check_out: string;
    total_stay_cost: number;
    total_extra_cost: number;
    requests: string | null;
    comments: string | null;
    status: ReservationStatus;
    employee?: User;
    departament?: Departament;
    customer?: Customer;
    products: ReservationProduct[];
    created_at: string;
    updated_at: string;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}
