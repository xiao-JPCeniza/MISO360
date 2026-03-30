import Swal from 'sweetalert2';

import type { FlashMessages } from '@/types';

export type AppToastType = 'success' | 'error' | 'warning' | 'info';

export type { FlashMessages };

function isDarkDocument(): boolean {
    if (typeof document === 'undefined') {
        return false;
    }

    return document.documentElement.classList.contains('dark');
}

function toastColors(): { background: string; color: string } {
    if (isDarkDocument()) {
        return {
            background: 'hsl(222 40% 14%)',
            color: 'hsl(210 40% 98%)',
        };
    }

    return {
        background: 'hsl(0 0% 100%)',
        color: 'hsl(222 47% 11%)',
    };
}

const iconTitles: Record<AppToastType, string> = {
    success: 'Success',
    error: 'Error',
    warning: 'Warning',
    info: 'Notice',
};

function swalAnimationClasses(type: AppToastType): {
    showClass: Record<string, string>;
    hideClass: Record<string, string>;
} {
    return {
        showClass: {
            popup: `swal2-show app-toast-anim-in app-toast-anim-in--${type}`,
            icon: `app-toast-icon-anim app-toast-icon-anim--${type}`,
        },
        hideClass: {
            popup: `swal2-hide app-toast-anim-out app-toast-anim-out--${type}`,
        },
    };
}

/**
 * Non-blocking toast aligned with app theme (light/dark).
 */
export function showAppToast(type: AppToastType, message: string, title?: string): void {
    const text = message.trim();
    if (text.length === 0) {
        return;
    }

    const { background, color } = toastColors();
    const { showClass, hideClass } = swalAnimationClasses(type);

    void Swal.fire({
        toast: true,
        position: 'top-end',
        icon: type,
        title: title?.trim() || iconTitles[type],
        text,
        showConfirmButton: false,
        timer: type === 'error' ? 9000 : 6500,
        timerProgressBar: true,
        background,
        color,
        showClass,
        hideClass,
        didOpen(toast) {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        },
    });
}

/**
 * Modal for critical messages (optional; use sparingly).
 */
export function showAppModal(
    type: AppToastType,
    message: string,
    options?: { title?: string; confirmButtonText?: string },
): void {
    const text = message.trim();
    if (text.length === 0) {
        return;
    }

    const { background, color } = toastColors();
    const { showClass, hideClass } = swalAnimationClasses(type);

    void Swal.fire({
        icon: type,
        title: options?.title?.trim() || iconTitles[type],
        text,
        confirmButtonText: options?.confirmButtonText ?? 'OK',
        background,
        color,
        confirmButtonColor: 'hsl(221 83% 53%)',
        showClass,
        hideClass,
        customClass: {
            popup: 'swal2-modal-app',
            confirmButton: 'swal2-confirm-app',
        },
    });
}

function flashMessage(value: string | null | undefined): string | null {
    if (value === null || value === undefined) {
        return null;
    }

    const trimmed = String(value).trim();

    return trimmed.length > 0 ? trimmed : null;
}

/**
 * Maps session flash props to one or more toasts (error and warning first).
 */
export function showFlashToasts(flash: FlashMessages | null | undefined): void {
    if (!flash) {
        return;
    }

    const queue: Array<{ type: AppToastType; message: string; title?: string }> = [];

    const error = flashMessage(flash.error);
    if (error) {
        queue.push({ type: 'error', message: error });
    }

    const warning = flashMessage(flash.warning);
    if (warning) {
        queue.push({ type: 'warning', message: warning });
    }

    const success = flashMessage(flash.success);
    if (success) {
        queue.push({ type: 'success', message: success, title: 'Success' });
    } else {
        const status = flashMessage(flash.status);
        if (status) {
            queue.push({ type: 'success', message: status });
        }
    }

    const info = flashMessage(flash.info);
    if (info) {
        queue.push({ type: 'info', message: info });
    }

    queue.forEach((item, index) => {
        window.setTimeout(() => showAppToast(item.type, item.message, item.title), index * 420);
    });
}

export function flashPayloadSignature(flash: FlashMessages | null | undefined): string {
    if (!flash) {
        return '';
    }

    return JSON.stringify({
        success: flash.success ?? null,
        error: flash.error ?? null,
        status: flash.status ?? null,
        warning: flash.warning ?? null,
        info: flash.info ?? null,
    });
}

export function flashHasContent(flash: FlashMessages | null | undefined): boolean {
    if (!flash) {
        return false;
    }

    return (
        flashMessage(flash.error) !== null ||
        flashMessage(flash.warning) !== null ||
        flashMessage(flash.success) !== null ||
        flashMessage(flash.status) !== null ||
        flashMessage(flash.info) !== null
    );
}
