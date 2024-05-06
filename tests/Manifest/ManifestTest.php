<?php

namespace Manifest;

use PHPUnit\Framework\Attributes\CoversClass;
use Shopware\ServiceBundle\Manifest\Manifest;
use PHPUnit\Framework\TestCase;

#[CoversClass(Manifest::class)]
class ManifestTest extends TestCase
{
    public function testManifestHashesContent(): void
    {
        $manifest = new Manifest('6.6.0.0', 'my-manifest-content');

        static::assertSame('6.6.0.0', $manifest->version);
        static::assertSame('my-manifest-content', $manifest->content);
        static::assertSame('83d66aff96d408c304345558691adf4cbf6b14738f928f8e8c131e767a891939', $manifest->hash);
    }
}
