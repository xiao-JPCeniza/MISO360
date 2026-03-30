import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

/** Session flash keys shared via `HandleInertiaRequests` for Inertia pages. */
export interface FlashMessages {
    success?: string | null;
    error?: string | null;
    status?: string | null;
    warning?: string | null;
    info?: string | null;
}

export interface Auth {
    user: User | null;
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
    auth: Auth;
    sidebarOpen: boolean;
    flash: FlashMessages;
    notifications?: { unreadCount: number } | null;
    [key: string]: unknown;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    avatar_url?: string | null;
    role?: string;
    phone?: string | null;
    position_title?: string | null;
    office_designation_id?: number | null;
    is_active?: boolean;
    two_factor_enabled?: boolean;
    email_verified_at: string | null;
    admin_verified_at?: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export type BreadcrumbItemType = BreadcrumbItem;
