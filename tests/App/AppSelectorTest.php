<?php

declare(strict_types=1);

namespace Shopware\ServiceBundle\Test\App;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\App\App;
use Shopware\ServiceBundle\App\AppHasher;
use Shopware\ServiceBundle\App\AppLoader;
use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\App\NoSupportedAppException;

#[CoversClass(AppSelector::class)]
#[CoversClass(AppHasher::class)]
#[CoversClass(AppLoader::class)]
#[CoversClass(App::class)]
#[CoversClass(NoSupportedAppException::class)]
class AppSelectorTest extends TestCase
{
    private function getAppLoaderMock(): AppLoader&MockObject
    {
        $loader = static::createMock(AppLoader::class);
        $loader->expects(static::once())->method('load')->willReturn([
            new App('/6.6.0.0', 'MyCoolService', '6.6.0.0', 'hash1'),
            new App('/6.6.6.0', 'MyCoolService', '6.6.6.0', 'hash2'),
            new App('/6.7.0.0', 'MyCoolService', '6.7.0.0', 'hash3'),
            new App('/6.7.7.0', 'MyCoolService', '6.7.7.0', 'hash4'),
        ]);

        return $loader;
    }

    public function testShopware66DevVersion(): void
    {
        $selector = new AppSelector($this->getAppLoaderMock());
        $app = $selector->select('6.6.9999999.9999999-dev');
        static::assertSame('6.6.6.0', $app->version);
    }

    #[DataProvider('latestCompatibleManifestVersions')]
    public function testSelectsLatestCompatibleManifestVersion(string $shopwareVersion, string $selectedVersion): void
    {
        $selector = new AppSelector($this->getAppLoaderMock());
        $app = $selector->select($shopwareVersion);

        static::assertSame($selectedVersion, $app->version);
    }

    public function testThrowsIfNoCompatibleManifestVersionCanBeSelected(): void
    {
        $selector = new AppSelector($this->getAppLoaderMock());

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

    public function testSpecificThrowsIfAppWithVersionDoesNotExist(): void
    {
        $selector = new AppSelector($this->getAppLoaderMock());

        static::expectException(NoSupportedAppException::class);
        $selector->specific('6.5');
    }

    public function testSpecificReturnsExactMatch(): void
    {
        $selector = new AppSelector($this->getAppLoaderMock());
        $app = $selector->specific('6.6.0.0');

        static::assertSame('6.6.0.0', $app->version);
    }
}
