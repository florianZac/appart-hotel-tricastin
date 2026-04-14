<?php

namespace App\Tests\Twig;

use App\Twig\CloudinaryExtension;
use PHPUnit\Framework\TestCase;

class CloudinaryExtensionTest extends TestCase
{
    private CloudinaryExtension $extension;

    protected function setUp(): void
    {
        // Simule CLOUDINARY_URL=cloudinary://key:secret@mycloud
        $this->extension = new CloudinaryExtension('cloudinary://123456:abcdef@mycloud');
    }

    public function testNullValueReturnsPlaceholder(): void
    {
        $result = $this->extension->imageUrl(null);
        $this->assertStringContainsString('unsplash.com', $result);
    }

    public function testEmptyStringReturnsPlaceholder(): void
    {
        $result = $this->extension->imageUrl('');
        $this->assertStringContainsString('unsplash.com', $result);
    }

    public function testNullValueWithCustomFallback(): void
    {
        $result = $this->extension->imageUrl(null, '', 'https://custom.com/img.jpg');
        $this->assertSame('https://custom.com/img.jpg', $result);
    }

    public function testCloudinaryUrlWithTransformations(): void
    {
        $url = 'https://res.cloudinary.com/mycloud/image/upload/v123/folder/image.jpg';
        $result = $this->extension->imageUrl($url, 'card');

        $this->assertStringContainsString('/upload/w_600,h_400,c_fill,q_auto,f_auto/', $result);
    }

    public function testCloudinaryUrlWithoutTransformations(): void
    {
        $url = 'https://res.cloudinary.com/mycloud/image/upload/v123/folder/image.jpg';
        $result = $this->extension->imageUrl($url, '');

        $this->assertSame($url, $result);
    }

    public function testExternalUrlReturnedAsIs(): void
    {
        $url = 'https://example.com/image.jpg';
        $result = $this->extension->imageUrl($url, 'card');

        $this->assertSame($url, $result);
    }

    public function testHttpUrlReturnedAsIs(): void
    {
        $url = 'http://example.com/image.jpg';
        $result = $this->extension->imageUrl($url);

        $this->assertSame($url, $result);
    }

    public function testRelativePathBuildsCloudinaryUrl(): void
    {
        $result = $this->extension->imageUrl('Tulette/image.jpg', 'thumb');

        $this->assertStringContainsString('res.cloudinary.com/mycloud', $result);
        $this->assertStringContainsString('w_200,h_130', $result);
        $this->assertStringContainsString('images/Tulette/image.jpg', $result);
    }

    public function testRelativePathWithoutTransformations(): void
    {
        $result = $this->extension->imageUrl('Tulette/image.jpg');

        $this->assertStringContainsString('res.cloudinary.com/mycloud', $result);
        $this->assertStringContainsString('images/Tulette/image.jpg', $result);
    }

    public function testPresetResolution(): void
    {
        $url = 'https://res.cloudinary.com/mycloud/image/upload/v1/test.jpg';

        $thumb = $this->extension->imageUrl($url, 'thumb');
        $this->assertStringContainsString('w_200,h_130', $thumb);

        $card = $this->extension->imageUrl($url, 'card');
        $this->assertStringContainsString('w_600,h_400', $card);

        $carousel = $this->extension->imageUrl($url, 'carousel');
        $this->assertStringContainsString('w_800,h_500', $carousel);

        $hero = $this->extension->imageUrl($url, 'hero');
        $this->assertStringContainsString('w_1600,h_700', $hero);
    }

    public function testCustomTransformationsPassthrough(): void
    {
        $url = 'https://res.cloudinary.com/mycloud/image/upload/v1/test.jpg';
        $result = $this->extension->imageUrl($url, 'w_300,h_300,c_crop');

        $this->assertStringContainsString('/upload/w_300,h_300,c_crop/', $result);
    }

    public function testNoCloudNameFallsBackToLocal(): void
    {
        $ext = new CloudinaryExtension('');
        $result = $ext->imageUrl('Tulette/image.jpg');

        $this->assertSame('/images/Tulette/image.jpg', $result);
    }

    public function testGetFiltersReturnsImageUrl(): void
    {
        $filters = $this->extension->getFilters();
        $this->assertCount(1, $filters);
        $this->assertSame('image_url', $filters[0]->getName());
    }
}
