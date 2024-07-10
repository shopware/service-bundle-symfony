<?php

namespace Shopware\ServiceBundle\Test\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Shopware\ServiceBundle\App\AppLoader;
use Shopware\ServiceBundle\DependencyInjection\ShopwareServiceExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(ShopwareServiceExtension::class)]
class ShopwareServiceExtensionTest extends TestCase
{
    public function testLoadConfig(): void
    {
        $extension = new ShopwareServiceExtension();
        $container = new ContainerBuilder();
        $extension->load([], $container);

        static::assertTrue($container->hasDefinition(AppLoader::class));

        $appDir = $container->getDefinition(AppLoader::class)->getArgument(0);

        static::assertSame('%kernel.project_dir%/app', $appDir);
    }
}
