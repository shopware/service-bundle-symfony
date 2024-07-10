<?php

namespace Shopware\ServiceBundle\Test\App;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\App\AppHasher;

#[CoversClass(AppHasher::class)]
class AppHasherTest extends TestCase
{
    public function testHashesFolderContent(): void
    {
        $hasher = new AppHasher();

        $hash = $hasher->hash(__DIR__ . '/apps/6.6.0.0');

        static::assertSame('a3d2e796d55e6834dbf3b8f93e9ab0f3961d47e0124cad5aab4816e35e1aa0d2', $hash);
    }
}
