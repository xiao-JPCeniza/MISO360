import {
    type AppToastType,
    showAppModal,
    showAppToast,
} from '@/lib/notifications';

export function useNotifications() {
    return {
        toast: showAppToast,
        modal: showAppModal,
        success: (message: string, title?: string) =>
            showAppToast('success', message, title ?? 'Success'),
        error: (message: string, title?: string) =>
            showAppToast('error', message, title ?? 'Error'),
        warning: (message: string, title?: string) =>
            showAppToast('warning', message, title ?? 'Warning'),
        info: (message: string, title?: string) =>
            showAppToast('info', message, title ?? 'Notice'),
    };
}

export type { AppToastType };
