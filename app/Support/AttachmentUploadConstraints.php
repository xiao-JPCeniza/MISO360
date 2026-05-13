<?php

namespace App\Support;

/**
 * Shared validation for ticket-style file uploads (same types and size everywhere).
 */
final class AttachmentUploadConstraints
{
    /**
     * Comma-separated extension list for Laravel `extensions` rule (no spaces).
     */
    public const EXTENSION_LIST = 'jpg,jpeg,png,webp,mp4,mov,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,rtf,odt,ods,odp';

    /** Maximum upload size per file in kilobytes (20 MB). */
    public const MAX_KILOBYTES = 20480;

    /**
     * @return array<int, string>
     */
    public static function rules(): array
    {
        return [
            'file',
            'extensions:'.self::EXTENSION_LIST,
            'max:'.self::MAX_KILOBYTES,
        ];
    }
}
