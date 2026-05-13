export type EquipmentFilePreviewKind = 'image' | 'video' | 'other';

/**
 * Classify a resolved storage URL for thumbnail preview (path may be extension-based).
 */
export function equipmentFilePreviewKind(resolvedUrl: string): EquipmentFilePreviewKind {
    const path = resolvedUrl.split('?')[0].toLowerCase();
    const ext = path.includes('.') ? (path.split('.').pop() ?? '') : '';

    if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext)) {
        return 'image';
    }

    if (['mp4', 'mov', 'webm'].includes(ext)) {
        return 'video';
    }

    return 'other';
}
