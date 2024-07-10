<?php

declare(strict_types=1);

namespace Shopware\ServiceBundle\Test\App;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\App\App;
use Shopware\ServiceBundle\App\AppHasher;
use Shopware\ServiceBundle\App\AppLoader;
use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\App\NoSupportedAppException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[CoversClass(AppSelector::class)]
#[CoversClass(AppHasher::class)]
#[CoversClass(AppLoader::class)]
#[CoversClass(App::class)]
#[CoversClass(NoSupportedAppException::class)]
class AppSelectorTest extends TestCase
{
    public function testShopware66DevVersion(): void
    {
        $selector = new AppSelector(new AppLoader(__DIR__ . '/apps', new AppHasher(), new ArrayAdapter()));
        $manifest = $selector->select('6.6.9999999.9999999-dev');
        static::assertSame('6.6.6.0', $manifest->version);
    }

    #[DataProvider('latestCompatibleManifestVersions')]
    public function testSelectsLatestCompatibleManifestVersion(string $shopwareVersion, string $selectedVersion): void
    {
        $selector = new AppSelector(new AppLoader(__DIR__ . '/apps', new AppHasher(), new ArrayAdapter()));
        $manifest = $selector->select($shopwareVersion);

        static::assertSame($selectedVersion, $manifest->version);
    }

    public function testThrowsIfNoCompatibleManifestVersionCanBeSelected(): void
    {
        $selector = new AppSelector(new AppLoader(__DIR__ . '/apps', new AppHasher(), new ArrayAdapter()));

        static::expectException(NoSupportedAppException::class);
        $selector->select('6.5');
    }

    public static function latestCompatibleManifestVersions(): \Generator
    {
        yield '6.6.0.0' => ['6.6.0.0', '6.6.0.0'];
        yield '6.6.5.0' => ['6.6.5.0', '6.6.0.0'];
        yield '6.6.6.0' => ['6.6.6.0', '6.6.6.0'];
        yield '6.6.9.0' => ['6.6.9.0', '6.6.6.0'];

        yield '6.7.0.0' => ['6.7.0.0', '6.7.0.0'];
        yield '6.7.5.0' => ['6.7.5.0', '6.7.0.0'];
        yield '6.7.7.0' => ['6.7.7.0', '6.7.7.0'];
        yield '6.7.9.0' => ['6.7.9.0', '6.7.7.0'];
    }
}
