<?php

namespace Shopware\ServiceBundle\Test\App;

use PHPUnit\Framework\Attributes\CoversClass;
use Shopware\ServiceBundle\App\App;
use PHPUnit\Framework\TestCase;

#[CoversClass(App::class)]
class AppTest extends TestCase
{
    public function testManifestHashesContent(): void
    {
        $app = new App(__DIR__, 'TestApp', '6.6.0.0', '83d66aff96d408c304345558691adf4cbf6b14738f928f8e8c131e767a891939');

        static::assertSame(__DIR__, $app->location);
        static::assertSame('TestApp', $app->name);
        static::assertSame('6.6.0.0', $app->version);
        static::assertSame('83d66aff96d408c304345558691adf4cbf6b14738f928f8e8c131e767a891939', $app->hash);
    }
}
