/**
 * Normalize equipment image paths from the server for use in img src / links.
 * Prefers absolute URLs from the backend; supports legacy relative storage paths.
 */
export function resolveEquipmentImageUrl(image: string): string {
    const trimmed = image.trim();
    if (trimmed === '') {
        return '';
    }
    if (/^https?:\/\//i.test(trimmed)) {
        return trimmed;
    }
    if (trimmed.startsWith('/')) {
        return trimmed;
    }

    return `/storage/${trimmed.replace(/^\/+/, '')}`;
}
