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

// ─── Hotel Domain Types ───────────────────────────────────────────────────────

export interface Room {
    id: number;
    number: string;
    type: string;
    capacity: number;
    price_per_night: number;
    description?: string;
}

export type ProductCategory = 'beverages' | 'snacks' | 'toiletries' | 'other';

export interface Product {
    id: number;
    name: string;
    description?: string;
    category: ProductCategory;
    price: number;
    stock: number;
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

export type ReservationStatus = 'pending' | 'active' | 'completed' | 'cancelled';

export interface Reservation {
    id: number;
    user: User;
    room: Room;
    check_in: string;
    check_out: string;
    status: ReservationStatus;
    notes?: string;
    total_nights: number;
    total_amount: number;
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
