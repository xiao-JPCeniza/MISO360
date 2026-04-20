<?php

namespace Tests\Unit;

use App\Support\EquipmentImageUrls;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EquipmentImageUrlsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.url', 'https://app.test');
        Config::set('filesystems.disks.public.url', 'https://app.test/storage');
        URL::forceRootUrl('https://app.test');
    }

    public function test_resolves_bare_path_using_public_disk_url(): void
    {
        $this->assertSame(
            'https://app.test/storage/inventory/photo.jpg',
            EquipmentImageUrls::resolve('inventory/photo.jpg'),
        );
    }

    public function test_strips_redundant_storage_prefix(): void
    {
        $this->assertSame(
            'https://app.test/storage/inventory/photo.jpg',
            EquipmentImageUrls::resolve('storage/inventory/photo.jpg'),
        );
    }

    public function test_preserves_absolute_http_urls(): void
    {
        $this->assertSame(
            'https://cdn.example.com/x.png',
            EquipmentImageUrls::resolve('https://cdn.example.com/x.png'),
        );
    }

    public function test_site_root_storage_path_uses_url_helper(): void
    {
        $this->assertSame(
            'https://app.test/storage/inventory/photo.jpg',
            EquipmentImageUrls::resolve('/storage/inventory/photo.jpg'),
        );
    }

    public function test_public_urls_prefers_array_over_legacy_single(): void
    {
        $urls = EquipmentImageUrls::publicUrls(
            ['inventory/a.jpg', 'inventory/b.jpg'],
            'inventory/legacy.jpg',
        );

        $this->assertSame([
            'https://app.test/storage/inventory/a.jpg',
            'https://app.test/storage/inventory/b.jpg',
        ], $urls);
    }

    public function test_public_urls_falls_back_to_legacy_single(): void
    {
        $urls = EquipmentImageUrls::publicUrls(null, 'inventory/z.jpg');

        $this->assertSame(['https://app.test/storage/inventory/z.jpg'], $urls);
    }

    public function test_public_urls_returns_empty_for_no_images(): void
    {
        $this->assertSame([], EquipmentImageUrls::publicUrls([], null));
        $this->assertSame([], EquipmentImageUrls::publicUrls(null, null));
    }
}
